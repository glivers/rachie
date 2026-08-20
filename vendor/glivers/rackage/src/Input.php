<?php namespace Rackage;

/**
 * Input Handler
 *
 * Provides unified access to HTTP input data from multiple sources (GET, POST, URL).
 * This class uses a pure static design pattern to provide global access to input
 * parameters throughout the application without passing data through function chains.
 *
 * Responsibilities:
 *   - Load and store GET parameters from $_GET superglobal
 *   - Load and store POST parameters from $_POST superglobal
 *   - Accept URL parameters extracted by the routing system
 *   - Merge all sources into unified access point
 *   - Provide XSS protection via automatic HTML escaping
 *   - Support source-specific parameter access
 *
 * Three Input Sources:
 *   1. GET - Query string parameters (?user=john&age=25)
 *   2. POST - Form data from POST requests
 *   3. URL - Route parameters extracted from URL segments (/user/123/edit → id=123)
 *
 * Merge Priority (later sources override earlier):
 *   GET → POST → URL
 *   If same key exists in multiple sources, URL value wins, then POST, then GET.
 *
 * Initialization Flow:
 *   1. Bootstrap calls Input::setGet()->setPost() at application start
 *   2. Router extracts URL parameters and calls Input::setUrl(['id' => '123'])
 *   3. Controllers access via Input::get('key'), Input::post('key'), Input::url('key')
 *
 * Security:
 *   - get() and post() methods automatically escape output with htmlentities(ENT_QUOTES)
 *   - Protects against XSS attacks when displaying user input
 *   - url() returns raw values (trusted, from route definitions not user input)
 *
 * Static Design Pattern:
 *   Uses static properties and methods (no instance creation).
 *   - Simple global access without dependency injection complexity
 *   - Data loaded once at bootstrap, available everywhere
 *   - No need to pass Input object through constructors
 *
 * Usage Example:
 *   // Bootstrap (index.php)
 *   Input::setGet()->setPost();
 *
 *   // In routing (Router/RouteParser)
 *   Input::setUrl(['id' => '123', 'action' => 'edit']);
 *
 *   // In controllers
 *   $username = Input::get('username');        // From any source (escaped)
 *   $password = Input::post('password');       // Only from POST (escaped)
 *   $id = Input::url('id');                    // Only from URL (raw)
 *
 *   if (Input::has('email')) {
 *       // Parameter exists in at least one source
 *   }
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Input
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

class Input {

	/**
	 * URL segment parameters (from routing system)
	 * @var array
	 */
	private static $url = [];

	/**
	 * GET request data (from $_GET superglobal)
	 * @var array
	 */
	private static $get = [];

	/**
	 * POST request data (from $_POST superglobal)
	 * @var array
	 */
	private static $post = [];

	/**
	 * Combined input data from all sources
	 * Merge order: GET → POST → URL (URL has highest priority)
	 * @var array
	 */
	private static $combined = [];

	/**
	 * Private constructor to prevent instantiation
	 *
	 * This class uses static methods only - no instances should be created.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning
	 *
	 * Ensures singleton-like behavior even though we don't use instances.
	 */
	private function __clone() {}

	// ===========================================================================
	// INITIALIZATION (Call at Bootstrap)
	// ===========================================================================

	/**
	 * Set both GET and POST data in one call
	 *
	 * Convenience method for initializing both sources at once.
	 * Equivalent to calling setGet()->setPost().
	 *
	 * Typical usage in bootstrap:
	 *   Input::setData(); // Loads $_GET and $_POST
	 *
	 * @return Input For method chaining (though class is static)
	 */
	public static function setData()
	{
		self::setGet();
		self::setPost();
		return new static;
	}

	/**
	 * Set GET data from $_GET superglobal
	 *
	 * Loads query string parameters into the Input class and merges them
	 * into the combined parameters array. Call this once at application bootstrap.
	 *
	 * Process:
	 * 1. Load $_GET into $get property
	 * 2. Merge into $combined array
	 * 3. Later calls to setPost() and setUrl() will override GET values
	 *
	 * Example:
	 *   URL: /page?user=john&age=25
	 *   Result: $get = ['user' => 'john', 'age' => '25']
	 *
	 * When to call:
	 *   Once at application bootstrap, before routing.
	 *
	 * @return Input For method chaining
	 */
	public static function setGet()
	{
		// Load GET parameters (use empty array if none)
		self::$get = $_GET ?? [];

		// Merge into combined array (GET has lowest priority)
		self::$combined = array_merge(self::$combined, self::$get);

		return new static;
	}

	/**
	 * Set POST data from $_POST superglobal
	 *
	 * Loads form POST data into the Input class and merges them into
	 * the combined parameters array. Call this once at application bootstrap.
	 *
	 * Process:
	 * 1. Load $_POST into $post property
	 * 2. Merge into $combined array (overrides GET if keys match)
	 * 3. Later call to setUrl() will override POST values
	 *
	 * Example:
	 *   Form POST: username=john&password=secret
	 *   Result: $post = ['username' => 'john', 'password' => 'secret']
	 *
	 * When to call:
	 *   Once at application bootstrap, before routing.
	 *
	 * @return Input For method chaining
	 */
	public static function setPost()
	{
		// Load POST parameters (use empty array if none)
		self::$post = $_POST ?? [];

		// Merge into combined array (POST overrides GET)
		self::$combined = array_merge(self::$combined, self::$post);

		return new static;
	}

	/**
	 * Set URL parameter data from routing system
	 *
	 * Accepts parameters extracted from URL segments by the Router/RouteParser.
	 * These are "clean" parameters with names defined in route definitions,
	 * not raw user input.
	 *
	 * Process:
	 * 1. Store parameters in $url property
	 * 2. Merge into $combined array (overrides GET and POST)
	 *
	 * Example:
	 *   Route: 'user' => 'User@show/id/action'
	 *   URL: /user/123/edit
	 *   Router calls: Input::setUrl(['id' => '123', 'action' => 'edit'])
	 *
	 * When to call:
	 *   Called automatically by RouteParser after parameter extraction.
	 *   Do not call manually unless building custom routing.
	 *
	 * Priority:
	 *   URL parameters have HIGHEST priority - they override GET and POST.
	 *
	 * @param array $params Associative array of URL parameters
	 * @return Input For method chaining
	 */
	public static function setUrl(array $params = [])
	{
		// Store URL parameters
		self::$url = $params;

		// Merge into combined array (URL has highest priority - overrides all)
		self::$combined = array_merge(self::$combined, self::$url);

		return new static;
	}

	// ===========================================================================
	// DATA ACCESS (Use in Controllers)
	// ===========================================================================

	/**
	 * Get input parameter from any source
	 *
	 * Retrieves parameter from merged input sources (GET, POST, URL).
	 * Checks all sources with priority: GET → POST → URL (URL wins).
	 *
	 * Security:
	 *   Returns RAW values. Escaping should be done at display level (templates with {{ }})
	 *   or at database level (query builder with prepared statements).
	 *
	 * Source Priority:
	 *   If same parameter exists in multiple sources, URL value is returned,
	 *   then POST, then GET.
	 *
	 * Usage:
	 *   Input::get('username');              // Get specific parameter (raw)
	 *   Input::get('age', 18);               // With default value
	 *   Input::get();                        // Get all parameters (array)
	 *
	 * Example:
	 *   URL: /user?name=John
	 *   POST: name=Jane
	 *   URL params: name=Jack
	 *   Input::get('name') returns 'Jack' (URL wins)
	 *
	 * @param string|null $name Parameter name, or null to get all parameters
	 * @param mixed $default Default value if parameter not found
	 * @return mixed Raw value if found, default value if not found, or array if name is null
	 */
	public static function get($name = null, $default = false)
	{
		// If no name provided, return all combined parameters
		if ($name === null) {
			return self::$combined;
		}

		// Check if parameter exists in any source
		if (array_key_exists($name, self::$combined)) {
			// Return raw value - escaping should happen at display or database level
			return self::$combined[$name];
		}

		// Parameter not found - return default
		return $default;
	}

	/**
	 * Check if parameter exists in any source
	 *
	 * Checks combined array for parameter existence (GET, POST, or URL).
	 * Does not return the value, only checks existence.
	 *
	 * Usage:
	 *   if (Input::has('email')) {
	 *       $email = Input::get('email');
	 *   }
	 *
	 * @param string $name Parameter name to check
	 * @return bool True if parameter exists in any source, false otherwise
	 */
	public static function has($name)
	{
		return array_key_exists($name, self::$combined);
	}

	/**
	 * Get POST parameter specifically
	 *
	 * Retrieves parameter ONLY from POST data, ignoring GET and URL sources.
	 * Useful when you specifically need form POST data.
	 *
	 * Security:
	 *   Returns RAW values. Escaping should be done at display level (templates with {{ }})
	 *   or at database level (query builder with prepared statements).
	 *
	 * Use cases:
	 *   - Form submissions (login, registration, etc.)
	 *   - Ensuring data came from POST not GET (CSRF protection)
	 *   - API endpoints that only accept POST
	 *
	 * Usage:
	 *   Input::post('password');             // Get specific POST parameter (raw)
	 *   Input::post('token', '');            // With default value
	 *   Input::post();                       // Get all POST data (array)
	 *
	 * @param string|null $name Parameter name, or null for all POST data
	 * @param mixed $default Default value if parameter not found
	 * @return mixed Raw value if found, default value if not found, or array if name is null
	 */
	public static function post($name = null, $default = false)
	{
		// If no name provided, return all POST parameters
		if ($name === null) {
			return self::$post;
		}

		// Check if parameter exists in POST data
		if (array_key_exists($name, self::$post)) {
			// Return raw value - escaping should happen at display or database level
			return self::$post[$name];
		}

		// Parameter not found - return default
		return $default;
	}

	/**
	 * Get URL parameter specifically
	 *
	 * Retrieves parameter ONLY from URL routing, ignoring GET and POST sources.
	 * These are parameters extracted from URL segments by the routing system.
	 *
	 * Security:
	 *   Returns RAW unescaped values. URL parameters come from route definitions,
	 *   not direct user input, so they're considered trusted. However, they
	 *   still originate from the URL, so escape before displaying in HTML.
	 *
	 * Use cases:
	 *   - Resource identifiers (user ID, post ID, etc.)
	 *   - Action names from routing
	 *   - Distinguishing route params from query string
	 *
	 * Usage:
	 *   Input::url('id');                    // Get specific URL parameter
	 *   Input::url('action', 'index');       // With default value
	 *   Input::url();                        // Get all URL parameters (array)
	 *
	 * Example:
	 *   Route: 'user' => 'User@show/id/action'
	 *   URL: /user/123/edit
	 *   Input::url('id') returns '123' (raw)
	 *   Input::url('action') returns 'edit' (raw)
	 *
	 * Note on escaping:
	 *   Unlike get() and post(), this returns raw values. Escape before display:
	 *   echo htmlspecialchars(Input::url('id'));
	 *
	 * @param string|null $name Parameter name, or null for all URL parameters
	 * @param mixed $default Default value if parameter not found
	 * @return mixed Raw value if found, default value if not found, or array if name is null
	 */
	public static function url($name = null, $default = false)
	{
		// If no name provided, return all URL parameters
		if ($name === null) {
			return self::$url;
		}

		// Return parameter value (raw, not escaped)
		// URL parameters come from routing system, not direct user input
		return self::$url[$name] ?? $default;
	}

}
