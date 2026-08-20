<?php namespace Rackage;

/**
 * URL Helper
 *
 * Generates application URLs, asset URLs, and links with proper protocol
 * and base path handling. Automatically detects installation directory
 * and respects protocol configuration.
 *
 * Static Design:
 *   All methods are static - no instance creation required.
 *   Uses Registry to access configuration and request data.
 *
 * Configuration:
 *   - protocol: 'auto', 'http', or 'https' (from settings.php)
 *   - url_separator: '/' or '.' (for link generation)
 *
 * Common Use Cases:
 *   - Generate base URL: Url::base()
 *   - Link to assets: Url::assets('css/style.css')
 *   - Create routes: Url::link('user', '123', 'edit')
 *   - Safe URLs with encoding: Url::safe('search', $userInput)
 *
 * Examples:
 *   // In views
 *   <link href="{{ Url::assets('css/app.css') }}" rel="stylesheet">
 *   <a href="{{ Url::link('blog', $post->slug) }}">Read More</a>
 *
 *   // In controllers
 *   redirect(Url::link('dashboard'));
 *
 *   // With user input (auto-encodes)
 *   redirect(Url::safe('search', $_GET['query']));
 *
 * Security Note:
 *   - link() does NOT escape parameters - use for trusted data
 *   - safe() URL-encodes parameters - use for user input
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Url
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

use Rackage\Registry;

class Url {

	/**
	 * Private constructor to prevent instantiation
	 *
	 * @return void
	 */
	private function __construct(){}

	/**
	 * Prevent cloning
	 *
	 * @return void
	 */
	private function __clone(){}

	/**
	 * Build base URL from server variables and configuration
	 *
	 * Constructs the base URL including protocol and installation directory.
	 * Respects protocol configuration or auto-detects from server.
	 *
	 * @return string Base URL without trailing slash
	 */
	private static function buildBaseUrl()
	{
		// Use SERVER_NAME (trusted) and append non-standard ports
		$base = $_SERVER['SERVER_NAME'];
		$port = $_SERVER['SERVER_PORT'] ?? '80';

		// Append port if non-standard (not 80 for HTTP, not 443 for HTTPS)
		$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off");
		$standardPort = $isHttps ? '443' : '80';
		if ($port != $standardPort) {
			$base .= ':' . $port;
		}

		$url = Registry::url();

		// Strip query parameters from REQUEST_URI and decode URL encoding
		// Decoding is necessary because Registry::url() stores decoded values
		// but REQUEST_URI may contain encoded characters (especially with [B] flag)
		$requestPath = rawurldecode(strtok($_SERVER['REQUEST_URI'], '?'));

		if (!empty($url)) {
			$base .= substr($requestPath, 0, strpos($requestPath, Registry::url()));
		} else {
			$base .= $requestPath;
		}

		// Determine protocol
		$settings = Registry::settings();
		$configProtocol = $settings['protocol'] ?? 'auto';

		if ($configProtocol === 'auto') {
			// Auto-detect from server
			$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";
		} else {
			// Use configured protocol
			$protocol = $configProtocol;
		}

		return $protocol . '://' . $base;
	}

	/**
	 * Get base URL of the application
	 *
	 * Returns the root URL including protocol and installation directory.
	 *
	 * Examples:
	 *   Url::base()  // https://example.com/myapp/
	 *
	 * @return string Base URL
	 */
	public static function base()
	{
		return self::buildBaseUrl();
	}

	/**
	 * Get URL to asset file in public directory
	 *
	 * Generates full URL to assets (CSS, JS, images) in public/ folder.
	 *
	 * Examples:
	 *   Url::assets('css/style.css')      // https://example.com/public/css/style.css
	 *   Url::assets('images/logo.png')    // https://example.com/public/images/logo.png
	 *
	 * @param string|null $assetName Path to asset relative to public/
	 * @return string Full URL to asset
	 */
	public static function assets($assetName = null)
	{
		//return self::buildBaseUrl() . 'public/' . ltrim($assetName, '/');
		return self::buildBaseUrl() . ltrim($assetName, '/');
	}

	/**
	 * Generate application URL with parameters
	 *
	 * Creates URLs for routing within the application using configured separator.
	 * Accepts array or variadic arguments. Does NOT encode parameters.
	 *
	 * Examples:
	 *   Url::link('user', '123', 'edit')        // https://example.com/user/123/edit
	 *   Url::link(['blog', 'post', 'my-slug'])  // https://example.com/blog/post/my-slug
	 *   Url::link()                             // https://example.com/
	 *
	 * Security:
	 *   Parameters are NOT URL-encoded. Use safe() for user input.
	 *
	 * @param mixed ...$linkParams URL segments (array or variadic args)
	 * @return string Full application URL
	 */
	public static function link(...$linkParams)
	{
		$base = self::buildBaseUrl();

		// Handle no parameters
		if (empty($linkParams)) {
			return $base;
		}

		// Handle single array argument
		if (count($linkParams) === 1 && is_array($linkParams[0])) {
			$linkParams = $linkParams[0];
		}

		// Join with configured separator
		$settings = Registry::settings();
		$separator = $settings['url_component_separator'] ?? '/';
		$path = implode($separator, $linkParams);

		return $base . ltrim($path, '/');
	}

	/**
	 * Generate safe URL with encoded parameters
	 *
	 * Like link() but URL-encodes all parameters to prevent XSS and broken URLs.
	 * Use when parameters come from user input or contain special characters.
	 *
	 * Examples:
	 *   Url::safe('search', $_GET['query'])     // Encodes query parameter
	 *   Url::safe('user', 'john doe')           // Encodes space to %20
	 *   Url::safe(['blog', '../admin'])         // Prevents path traversal
	 *
	 * Security:
	 *   All parameters are passed through urlencode() for safety.
	 *
	 * @param mixed ...$linkParams URL segments to encode (array or variadic args)
	 * @return string Full URL with encoded parameters
	 */
	public static function safe(...$linkParams)
	{
		// Handle single array argument
		if (count($linkParams) === 1 && is_array($linkParams[0])) {
			$linkParams = $linkParams[0];
		}

		// Encode all parameters
		$escaped = array_map(function($param) {
			return urlencode($param);
		}, $linkParams);

		return self::link($escaped);
	}

}
