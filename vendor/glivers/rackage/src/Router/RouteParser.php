<?php namespace Rackage\Router;

/**
 * Route Parser
 *
 * Parses route definitions and maps URLs to controllers/actions.
 * This class handles the complex logic of matching incoming URLs against
 * defined routes and extracting controller, method, and parameter information.
 *
 * Responsibilities:
 *   - Match URL against defined routes
 *   - Parse route metadata (format: "Controller@method/param1/param2")
 *   - Extract controller and method names from routes
 *   - Map URL segments to named parameters
 *   - Inject parameters into Input class for global access
 *
 * Route Format:
 *   Routes are defined as: 'routeName' => 'Controller@method/param1/param2'
 *   - routeName: The URL path to match (e.g., 'user', 'api/posts')
 *   - Controller: The controller class to dispatch to
 *   - method: The controller method to call
 *   - param1/param2: Named parameters extracted from URL
 *
 * Example:
 *   Route: 'user' => 'User@show/id/action'
 *   URL: /user/123/edit
 *   Result: UserController->show($id='123', $action='edit')
 *
 * Architecture:
 *   This class handles both URL parsing and route matching.
 *   It parses the URL string into components, then matches against defined routes
 *   to determine the controller, method, and parameters.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Routes
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

use Rackage\Routes\RouteException;
use Rackage\Arr;
use Rackage\Str;
use Rackage\Input;

class RouteParser {

	/**
	 * Route pattern delimiter (separates controller from method)
	 * @var string
	 */
	protected $pattern = '@';

	/**
	 * URL parameter separator (separates method from parameters)
	 * @var string
	 */
	protected $separator = '/';

	/**
	 * Route metadata string (e.g., "User@show/id/action")
	 * @var string
	 */
	protected $routeData;

	/**
	 * Method metadata string (e.g., "show/id/action")
	 * @var string
	 */
	protected $methodData;

	/**
	 * Parsed method metadata array (e.g., ['show', 'id', 'action'])
	 * Contains method name and parameter names from route definition
	 * @var array
	 */
	protected $methodArray = array();

	/**
	 * Resolved controller name
	 * @var string
	 */
	protected $controller = null;

	/**
	 * Resolved method name
	 * @var string
	 */
	protected $method = null;

	/**
	 * URL parameters (can be numeric or associative array)
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * Current URL string being parsed
	 * Example: "admin/posts/edit/5"
	 * @var string
	 */
	private $urlString;

	/**
	 * URL components array after splitting and cleaning
	 * Example: "admin/posts/edit/5" → ["admin", "posts", "edit", "5"]
	 * @var array
	 */
	public $urlComponents = [];

	/**
	 * Defined routes from routes.php
	 * @var array
	 */
	protected $routes = array();

	/**
	 * Matched route name
	 * @var string
	 */
	protected $route;

	/**
	 * Pattern match flag - true if route matched via wildcard pattern
	 * @var bool
	 */
	protected $isPatternMatch = false;

	/**
	 * Wildcard value captured from pattern route (everything after prefix/)
	 * @var string|null
	 */
	protected $wildcardValue = null;

	/**
	 * Number of URL segments to skip when extracting method/parameters
	 * Default: 1 (controller is segment [0], method is segment [1])
	 * Compound routes: 2 (route consumes [0] and [1], method is segment [2])
	 * @var int
	 */
	protected $segmentOffset = 1;

	/**
	 * Constructor - Initialize route parser and parse URL
	 *
	 * @param string $url The URL request string to parse
	 * @param array $routes Defined routes array
	 */
	public function __construct($url, array $routes)
	{
		$this->urlString = $url;
		$this->routes = $routes;

		// Sanitize URL and split into components
		// Example: "admin/posts/edit/5" → ["admin", "posts", "edit", "5"]
		$cleanUrl = Str::removeTags($url);
		$this->urlComponents = Arr::parts($this->separator, $cleanUrl)
			->clean()
			->trim()
			->get();
	}

	// ===========================================================================
	// ROUTE MATCHING
	// ===========================================================================

	/**
	 * Check if URL matches a defined route
	 *
	 * Attempts to match the first segment of the URL against defined routes.
	 * If a match is found, stores the route name and metadata for later processing.
	 *
	 * Process:
	 * 1. Check if URL has a controller segment
	 * 2. Look up controller segment in routes array
	 * 3. If found, store route name and metadata
	 *
	 * Example:
	 *   URL: /user/123/edit
	 *   Routes: ['user' => 'User@show/id/action', 'api' => 'Api@index']
	 *   Result: Matches 'user', stores route name and metadata
	 *
	 * @return bool True if route matched, false otherwise
	 */
	public function matchRoute()
	{
		// URL must have at least one segment
		if (empty($this->urlComponents)) { return false; }

		$controllerSegment = $this->urlComponents[0];

		// ============================================================================
		// COMPOUND ROUTE MATCHING (2-segment routes)
		// ============================================================================
		// Priority order: exact compound → compound wildcard → exact single → single wildcard
		//
		// Example: URL '/admin/posts/edit/5' checks in order:
		//   1. 'admin/posts' (exact) → AdminpostsController::edit(5)
		//   2. 'admin/posts/*' (wildcard) → if defined
		//   3. 'admin' (exact) → AdminController::posts()
		//   4. 'admin/*' (wildcard) → AdminController::catchAll('posts/edit/5')
		//
		// Performance: 4 hash lookups (~200ns total). Extremely fast.

		if (isset($this->urlComponents[1])) {
			$methodSegment = $this->urlComponents[1];

			// 1. Check exact compound route: 'admin/posts'
			$compoundKey = $controllerSegment . '/' . $methodSegment;
			if (isset($this->routes[$compoundKey])) {
				$this->route = $compoundKey;
				$this->routeData = $this->routes[$compoundKey];
				$this->segmentOffset = 2;
				return true;
			}

			// 2. Check compound wildcard route: 'admin/posts/*'
			$compoundWildcard = $compoundKey . '/*';
			if (isset($this->routes[$compoundWildcard])) {
				// Capture everything after 'admin/posts/' as wildcard
				// URL: /admin/posts/edit/5 → wildcard = 'edit/5'
				$prefixLength = strlen($controllerSegment) + strlen($methodSegment) + 2; // +2 for two slashes
				$this->wildcardValue = substr($this->urlString, $prefixLength);

				$this->route = $compoundWildcard;
				$this->routeData = $this->routes[$compoundWildcard];
				$this->isPatternMatch = true;
				$this->segmentOffset = 2;
				return true;
			}
		}

		// ============================================================================
		// SINGLE-SEGMENT ROUTE MATCHING
		// ============================================================================

		// 3. Check exact single route: 'admin'
		if (isset($this->routes[$controllerSegment])) {
			$this->route = $controllerSegment;
			$this->routeData = $this->routes[$controllerSegment];
			return true;
		}

		// 4. Check single wildcard route: 'admin/*'
		$singleWildcard = $controllerSegment . '/*';
		if (isset($this->routes[$singleWildcard])) {
			// Capture everything after 'admin/' as wildcard
			// URL: /admin/posts/edit → wildcard = 'posts/edit'
			$prefixLength = strlen($controllerSegment) + 1; // +1 for slash
			$this->wildcardValue = substr($this->urlString, $prefixLength);

			$this->route = $singleWildcard;
			$this->routeData = $this->routes[$singleWildcard];
			$this->isPatternMatch = true;
			return true;
		}

		// No route matched
		return false;
	}


	// ===========================================================================
	// CONTROLLER RESOLUTION
	// ===========================================================================

	/**
	 * Set controller from URL when no route matched
	 *
	 * Extracts controller from first URL component for automatic routing.
	 * Example: URL "/Blog/show/123" → controller = "Blog"
	 *
	 * @return RouteParser
	 */
	public function setControllerUrl()
	{
		$this->controller = $this->urlComponents[0] ?? null;
		return $this;
	}

	/**
	 * Set controller from route metadata
	 *
	 * Parses the route metadata to extract the controller name.
	 * Route metadata format: "Controller@method/param1/param2"
	 * This method extracts "Controller" and stores "method/param1/param2" for later.
	 *
	 * Process:
	 * 1. Split route metadata by '@' pattern
	 * 2. First part is controller, second part is method metadata
	 * 3. Validate controller exists
	 *
	 * Example:
	 *   Route data: "User@show/id/action"
	 *   Result: controller='User', methodData='show/id/action'
	 *
	 * @return RouteParser
	 * @throws RouteException If controller not defined in route
	 */
	public function setController()
	{
		// Parse route metadata: "Controller@method/params" -> ['Controller', 'method/params']
		$routeDataArray = Arr::parts($this->pattern, $this->routeData)
		                      ->clean()
		                      ->trim()
		                      ->get();

		// Validate controller exists in route definition
		if (!Arr::exists(0, $routeDataArray) || empty($routeDataArray[0])) {
			throw new RouteException("No controller associated with route: {$this->route}");
		}

		// Extract controller name
		$this->controller = $routeDataArray[0];

		// Store method metadata for later parsing (if exists)
		$this->methodData = (count($routeDataArray) > 1) ? $routeDataArray[1] : null;

		return $this;
	}

	/**
	 * Get resolved controller name
	 *
	 * @return string|null Controller name or null if not set
	 */
	public function getController()
	{
		return $this->controller;
	}

	// ===========================================================================
	// METHOD RESOLUTION
	// ===========================================================================

	/**
	 * Set method from URL when no route matched
	 *
	 * Extracts method from second URL component for automatic routing.
	 * Example: URL "/Blog/show/123" → method = "show"
	 *
	 * @return RouteParser
	 */
	public function setMethodUrl()
	{
		$this->method = $this->urlComponents[1] ?? null;
		return $this;
	}

	/**
	 * Set method from route metadata or URL
	 *
	 * Handles two scenarios:
	 * 1. Route has no method metadata - extract method from URL
	 * 2. Route has method metadata - parse it for method name and parameter names
	 *
	 * Process:
	 * - If no method metadata:
	 *   1. Check if controller has embedded parameters (e.g., "user/id")
	 *   2. Extract controller and parameter names if present
	 *   3. Extract method from URL at segmentOffset position
	 *
	 * - If method metadata exists:
	 *   1. Parse metadata string (e.g., "show/id/action" -> ['show', 'id', 'action'])
	 *   2. First element is method name, rest are parameter names
	 *   3. If URL also has a method, treat it as first parameter value
	 *
	 * Example flows:
	 *   Route: 'user' => 'User@show/id/action', URL: /user/123/edit
	 *   → method='show', methodArray=['show', 'id', 'action']
	 *
	 *   Route: 'api' => 'Api', URL: /api/users/list
	 *   → method='users' (from URL at offset 1)
	 *
	 *   Route: 'admin/posts' => 'adminposts', URL: /admin/posts/edit/5
	 *   → method='edit' (from URL at offset 2)
	 *
	 * @param int $offset Number of URL segments to skip (default: uses $this->segmentOffset)
	 * @return RouteParser
	 * @throws RouteException If method format is invalid
	 */
	public function setMethod($offset = null)
	{
		// Use provided offset or fall back to instance offset
		$offset = $offset ?? $this->segmentOffset;

		// Case 1: No method metadata - extract from URL
		if ($this->methodData === null) {

			// Check if controller name has embedded parameters
			// Example: controller might be "user/id/action"
			$keys = Arr::parts($this->separator, $this->controller)
			            ->clean()
			            ->trim()
			            ->get();

			if (count($keys) > 1) {
				// Controller has embedded params - extract them
				$this->controller = $keys[0];
				$this->methodArray = $keys;
			}

			// Extract method from URL at the specified offset
			$this->method = $this->urlComponents[$offset] ?? null;

			return $this;
		}

		// Case 2: Method metadata exists - parse it
		// Format: "show/id/action" -> ['show', 'id', 'action']
		$methodDataArray = Arr::parts($this->separator, $this->methodData)
		                       ->clean()
		                       ->trim()
		                       ->get();

		// Validate we got at least a method name
		if (empty($methodDataArray)) {
			throw new RouteException(
				"Invalid method format for route '{$this->route}': {$this->controller}@{$this->methodData}"
			);
		}

		// First element is method name
		$this->method = $methodDataArray[0];

		// Store full array including parameter names for later mapping
		$this->methodArray = $methodDataArray;

		// Handle edge case: URL has a method segment when route already defines method
		// Example: Route 'user'=>'User@show', URL '/user/edit' - 'edit' becomes a parameter
		if (isset($this->urlComponents[1])) {
			// Prepend URL method segment as first parameter value
			array_unshift($this->urlComponents, $this->urlComponents[1]);
		}

		return $this;
	}

	/**
	 * Get resolved method name
	 *
	 * @return string|null Method name or null if not set
	 */
	public function getMethod()
	{
		return $this->method;
	}

	// ===========================================================================
	// PARAMETER RESOLUTION
	// ===========================================================================

	/**
	 * Set parameters from URL and inject into Input class
	 *
	 * Extracts URL parameters and maps them to named parameters if route
	 * definition includes parameter names. Then injects them into the Input
	 * class for global access via Input::get() or Input::url().
	 *
	 * Process:
	 * 1. Get parameter values from URL parser
	 * 2. If route defined parameter names, create associative array
	 * 3. Map parameter names to values
	 * 4. Inject into Input class for global access
	 *
	 * Parameter Mapping:
	 *   Route: 'user' => 'User@show/id/action'
	 *   URL: /user/123/edit
	 *   methodArray: ['show', 'id', 'action']
	 *   URL values: ['123', 'edit']
	 *   Result: ['id' => '123', 'action' => 'edit']
	 *
	 *   Route: 'admin/posts' => 'adminposts'
	 *   URL: /admin/posts/edit/5
	 *   Offset: 2, params start at [3]
	 *   Result: ['5']
	 *
	 * Why inject into Input:
	 *   Controllers can access params both as method arguments AND via Input::get()
	 *   - Method args: public function show($id, $action) { }
	 *   - Input class: $id = Input::get('id') or Input::url('id')
	 *
	 * @param int $offset Number of URL segments to skip (default: uses $this->segmentOffset)
	 * @return RouteParser
	 */
	public function setParameters($offset = null)
	{
		// Use provided offset or fall back to instance offset
		$offset = $offset ?? $this->segmentOffset;

		// Get parameter values from URL starting at offset + 1
		// Example: /admin/posts/edit/5 with offset=2 → params from [3]: ['5']
		$values = array_slice($this->urlComponents, $offset + 1);

		// If this is a pattern match, prepend the wildcard value as first parameter
		// Example: Route 'blog/*', URL '/blog/my-post' → wildcard='my-post'
		if ($this->isPatternMatch && $this->wildcardValue !== null) {
			array_unshift($values, $this->wildcardValue);
		}

		// Check if route defined parameter names
		// methodArray[0] is method name, rest are parameter names
		if (count($this->methodArray) > 1) {

			// Extract parameter names (skip first element which is method name)
			$paramNames = array_slice($this->methodArray, 1);

			// Map parameter names to values if we have enough values
			if (count($values) >= count($paramNames)) {
				// Create associative array: ['id' => '123', 'action' => 'edit']
				$mapped = array_combine(
					$paramNames,
					array_slice($values, 0, count($paramNames))
				);

				// Merge with any extra unmapped parameters (keep as numeric)
				$extra = array_slice($values, count($paramNames));
				$this->parameters = array_merge($mapped, $extra);
			} else {
				// Not enough values for all parameter names - keep numeric
				$this->parameters = $values;
			}
		} else {
			// No parameter names defined - keep as numeric array
			$this->parameters = $values;
		}

		// Inject parameters into Input class for global access
		// Now controllers can use: Input::get('id') or Input::url('id')
		Input::setUrl($this->parameters);

		return $this;
	}

	/**
	 * Get URL parameters
	 *
	 * Returns either associative array (if route defined parameter names)
	 * or numeric array (if no parameter names defined).
	 *
	 * @return array Parameters array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

}
