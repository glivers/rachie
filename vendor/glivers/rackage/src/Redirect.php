<?php namespace Rackage;

/**
 * Redirect Helper
 *
 * Provides URL redirection with support for flash messages, query parameters,
 * status codes, and common redirect patterns.
 *
 * Static Design:
 *   All methods are static - no instance creation required.
 *
 * Access Patterns:
 *
 *   In Controllers:
 *     use Rackage\Redirect;
 *
 *     class AuthController extends Controller {
 *         public function postLogin() {
 *             if ($valid) {
 *                 Redirect::to('dashboard')->flash('success', 'Welcome back!');
 *             } else {
 *                 Redirect::back()->flash('error', 'Invalid credentials');
 *             }
 *         }
 *     }
 *
 *   In Views:
 *     Redirect is automatically available (configured in view_helpers).
 *     No 'use' statement needed.
 *
 * Usage Patterns:
 *
 *   // Basic redirect
 *   Redirect::to('dashboard');
 *   Redirect::to('blog/post/123');
 *
 *   // With query parameters
 *   Redirect::to('search', ['q' => 'test', 'page' => 2]);
 *   // Redirects to: /search?q=test&page=2
 *
 *   // With flash message
 *   Redirect::to('login')->flash('error', 'Please login first');
 *   Redirect::back()->flash('success', 'Profile updated!');
 *
 *   // External URL
 *   Redirect::away('https://google.com');
 *
 *   // Common shortcuts
 *   Redirect::back();      // Previous page
 *   Redirect::home();      // Homepage
 *   Redirect::refresh();   // Reload current page
 *
 *   // With HTTP status code
 *   Redirect::to('moved', [], 301);  // Permanent redirect
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Redirect
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */
class Redirect {

    /**
     * Pending flash messages to set before redirect
     * @var array
     */
    private static $flashMessages = [];

    /**
     * Query parameters to append to redirect URL
     * @var array
     */
    private static $queryParams = [];

    /**
     * Private constructor to prevent instantiation
     * @return void
     */
    private function __construct() {}

    /**
     * Prevent cloning
     * @return void
     */
    private function __clone() {}

    // =========================================================================
    // PRIMARY REDIRECT METHODS
    // =========================================================================

    /**
     * Redirect to internal path
     *
     * Redirects to a path within your application.
     * Optionally pass query parameters and HTTP status code.
     *
     * Examples:
     *   Redirect::to('dashboard');
     *   Redirect::to('blog/post/123');
     *   Redirect::to('search', ['q' => 'test', 'page' => 2]);
     *   Redirect::to('moved-page', [], 301);  // Permanent redirect
     *
     * With flash messages:
     *   Redirect::to('login')->flash('error', 'Please login first');
     *   Redirect::to('profile')->flash('success', 'Profile updated!');
     *
     * @param string $path Path to redirect to
     * @param array $queryParams Query parameters to append
     * @param int $statusCode HTTP status code (default: 302)
     * @return Redirect Returns instance for method chaining with flash()
     */
    public static function to($path, array $queryParams = [], $statusCode = 302)
    {
        // Build URL using Url helper
        $url = Url::link($path);

        // Merge stored query params with provided params
        $allParams = array_merge(self::$queryParams, $queryParams);

        // Append query parameters if any
        if (!empty($allParams)) {
            $queryString = http_build_query($allParams);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $queryString;
        }

        // Set flash messages if any
        self::setFlashMessages();

        // Clear stored query params
        self::$queryParams = [];

        // Perform redirect
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Redirect to external URL
     *
     * Redirects to an external URL (outside your application).
     * Validates that URL starts with http:// or https:// for security.
     *
     * Examples:
     *   Redirect::away('https://google.com');
     *   Redirect::away('https://github.com/user/repo');
     *
     * SECURITY NOTE:
     *   Only accepts URLs starting with http:// or https://.
     *   Prevents open redirect vulnerabilities.
     *
     * @param string $url External URL to redirect to
     * @param int $statusCode HTTP status code (default: 302)
     * @return void
     * @throws \InvalidArgumentException If URL is invalid
     */
    public static function away($url, $statusCode = 302)
    {
        // Validate URL for security (prevent open redirects)
        if (!self::isValidExternalUrl($url)) {
            throw new \InvalidArgumentException(
                "Invalid external URL. Must start with http:// or https://"
            );
        }

        // Set flash messages if any
        self::setFlashMessages();

        // Perform redirect
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Redirect back to previous page
     *
     * Redirects to the HTTP referer (previous page).
     * Falls back to homepage if referer is not available or external.
     *
     * Examples:
     *   Redirect::back();
     *   Redirect::back()->flash('success', 'Changes saved!');
     *
     * With fallback:
     *   // If no referer, goes to 'dashboard'
     *   Redirect::back('dashboard');
     *
     * SECURITY NOTE:
     *   Validates referer is from same domain to prevent open redirects.
     *
     * @param string $fallback Fallback path if no referer (default: home)
     * @return Redirect Returns instance for method chaining with flash()
     */
    public static function back($fallback = null)
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        // Validate referer is from same domain (security)
        if ($referer && self::isInternalUrl($referer)) {
            // Set flash messages if any
            self::setFlashMessages();

            header('Location: ' . $referer);
            exit;
        }

        // Fallback to specified path or home
        $fallbackPath = $fallback ?? self::getHomePath();
        self::to($fallbackPath);
    }

    /**
     * Refresh current page
     *
     * Reloads the current page.
     *
     * Examples:
     *   Redirect::refresh();
     *   Redirect::refresh()->flash('success', 'Page refreshed!');
     *
     * @return Redirect Returns instance for method chaining with flash()
     */
    public static function refresh()
    {
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';

        // Set flash messages if any
        self::setFlashMessages();

        header('Location: ' . $currentUrl);
        exit;
    }

    // =========================================================================
    // COMMON REDIRECT SHORTCUTS
    // =========================================================================

    /**
     * Redirect to homepage
     *
     * Redirects to the application homepage (default controller/action).
     *
     * Examples:
     *   Redirect::home();
     *   Redirect::home()->flash('success', 'Logged out successfully');
     *
     * @return Redirect Returns instance for method chaining with flash()
     */
    public static function home()
    {
        self::to(self::getHomePath());
    }

    /**
     * Redirect to intended URL or fallback
     *
     * Useful for post-login redirects. Redirects to intended URL stored
     * in session, or falls back to specified path.
     *
     * Examples:
     *   // Store intended URL before redirecting to login
     *   Session::set('intended_url', Request::url());
     *   Redirect::to('login');
     *
     *   // After login, redirect to intended URL
     *   Redirect::intended('dashboard');
     *
     * @param string $fallback Fallback path if no intended URL
     * @return void
     */
    public static function intended($fallback = null)
    {
        $intendedUrl = Session::get('intended_url');

        // Clear intended URL from session
        Session::remove('intended_url');

        if ($intendedUrl && self::isInternalUrl($intendedUrl)) {
            // Set flash messages if any
            self::setFlashMessages();

            header('Location: ' . $intendedUrl);
            exit;
        }

        // Fallback
        $fallbackPath = $fallback ?? self::getHomePath();
        self::to($fallbackPath);
    }

    // =========================================================================
    // FLASH MESSAGES
    // =========================================================================

    /**
     * Add flash message to redirect
     *
     * Sets a flash message that will be available on the next request.
     * Use this for displaying feedback after redirects.
     *
     * Examples:
     *   Redirect::to('login')->flash('error', 'Please login first');
     *   Redirect::back()->flash('success', 'Profile updated!');
     *   Redirect::to('posts')->flash('warning', 'Draft saved');
     *   Redirect::home()->flash('info', 'Welcome to the site');
     *
     * Multiple flash messages:
     *   Redirect::to('dashboard')
     *       ->flash('success', 'Login successful')
     *       ->flash('info', 'You have 3 new messages');
     *
     * @param string $key Flash message key (e.g., 'success', 'error', 'warning', 'info')
     * @param string $message Flash message content
     * @return Redirect Returns instance for method chaining
     */
    public static function flash($key, $message)
    {
        self::$flashMessages[$key] = $message;
        return new static;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Set pending flash messages in session
     *
     * Internal method to write flash messages before redirect.
     *
     * @return void
     */
    private static function setFlashMessages()
    {
        foreach (self::$flashMessages as $key => $message) {
            Session::flash($key, $message);
        }

        // Clear pending messages
        self::$flashMessages = [];
    }

    /**
     * Get homepage path from settings
     *
     * Returns the default controller/action from settings, or root path if not configured.
     *
     * @return string Homepage path
     */
    private static function getHomePath()
    {
        $settings = Registry::settings();

        // If default controller/action not configured, just go to root
        if (!isset($settings['default']['controller']) || !isset($settings['default']['action'])) {
            return '/';
        }

        $controller = $settings['default']['controller'];
        $action = $settings['default']['action'];
        $separator = $settings['url_separator'] ?? '/';

        return $controller . $separator . $action;
    }

    /**
     * Check if URL is valid external URL
     *
     * Validates that URL starts with http:// or https://.
     * Prevents open redirect vulnerabilities.
     *
     * @param string $url URL to validate
     * @return bool True if valid external URL
     */
    private static function isValidExternalUrl($url)
    {
        return preg_match('/^https?:\/\/.+/', $url) === 1;
    }

    /**
     * Check if URL is from same domain (internal)
     *
     * Validates that URL is from the same domain.
     * Used for security to prevent open redirects.
     *
     * @param string $url URL to check
     * @return bool True if internal URL
     */
    private static function isInternalUrl($url)
    {
        // Parse URL
        $parsedUrl = parse_url($url);
        $urlHost = $parsedUrl['host'] ?? '';

        // Get current host
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';

        // Check if same host or relative URL
        return empty($urlHost) || $urlHost === $currentHost;
    }

    // =========================================================================
    // QUERY PARAMETERS
    // =========================================================================

    /**
     * Add query parameter(s) to redirect
     *
     * Stores query parameters to be appended when redirect is executed.
     * Supports method chaining for building complex query strings.
     *
     * Accepts either:
     * - Key-value pair: with($key, $value)
     * - Array of params: with(['key' => 'value', 'key2' => 'value2'])
     *
     * Examples:
     *   // Single parameter
     *   Redirect::with('page', 2)->to('search');
     *   // Redirects to: /search?page=2
     *
     *   // Multiple parameters (chained)
     *   Redirect::with('q', 'test')->with('page', 2)->to('search');
     *   // Redirects to: /search?q=test&page=2
     *
     *   // Multiple parameters (array)
     *   Redirect::with(['q' => 'test', 'page' => 2])->to('search');
     *   // Redirects to: /search?q=test&page=2
     *
     *   // Mix both styles
     *   Redirect::with(['status' => 'active'])->with('sort', 'date')->to('posts');
     *   // Redirects to: /posts?status=active&sort=date
     *
     * Alternative: Pass params directly to to()
     *   Redirect::to('search', ['q' => 'test', 'page' => 2]);
     *
     * @param string|array $key Query parameter key or array of parameters
     * @param mixed $value Query parameter value (ignored if $key is array)
     * @return Redirect Returns instance for chaining
     */
    public static function with($key, $value = null)
    {
        // If first parameter is array, merge all params
        if (is_array($key)) {
            self::$queryParams = array_merge(self::$queryParams, $key);
        } else {
            // Single key-value pair
            self::$queryParams[$key] = $value;
        }

        return new static;
    }
}
