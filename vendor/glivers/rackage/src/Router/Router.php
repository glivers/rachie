<?php namespace Rackage\Router;

/**
 * Application Router
 * 
 * Handles HTTP request routing, controller resolution, and dispatch.
 * This class orchestrates the entire request lifecycle from URL parsing
 * to controller execution with filters.
 * 
 * Responsibilities:
 *   - Load and parse route definitions
 *   - Parse incoming request URLs
 *   - Match URLs to defined routes
 *   - Resolve controller and action
 *   - Execute @before and @after filters
 *   - Dispatch controller method with parameters
 *   - Handle routing errors
 * 
 * Architecture:
 *   This class is part of Rackage (the engine) and is updated via Composer.
 *   It uses dependency injection for testability.
 * 
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Routes
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.0
 */

use Rackage\Cache;
use Rackage\Request;
use ReflectionClass;
use Rackage\Registry;
use Rackage\Security;

class Router {

	/**
	 * Application settings from Registry
	 * @var array
	 */
	private $settings;

	/**
	 * Route definitions from routes.php
	 * @var array
	 */
	private $routes;

	/**
	 * Route parser instance
	 * @var RouteParser
	 */
	private $routeParser;

	/**
	 * Resolved controller name
	 * @var string
	 */
	private $controller;

	/**
	 * Resolved action/method name
	 * @var string
	 */
	private $action;

	/**
	 * Method parameters
	 * @var array
	 */
	private $parameters = array();

	/**
	 * Constructor - Initialize router with dependencies
	 *
	 * @param array $settings Application settings
	 * @param array $routes Route definitions
	 */
	public function __construct($settings, $routes)
	{
		$this->settings = $settings;
		$this->routes = $routes;
	}

	/**
	 * Main dispatch method - handles the entire request lifecycle
	 *
	 * Process:
	 * 1. Check if cached page exists and serve it
	 * 2. Parse the URL
	 * 3. Match against routes
	 * 4. Resolve controller and action
	 * 5. Validate controller and method exist
	 * 6. Execute filters
	 * 7. Dispatch controller method
	 *
	 * @return void
	 * @throws RouteException If routing fails
	 */
	public function dispatch()
	{
		try {
			
			// Set security headers and pre-flight response
			Security::headers();

			// Check cache first - serve and exit if hit
			if ($this->checkCache()) return;

			// Match route and resolve routing
			$this->matchRoute();
			$this->resolveController();
			$this->resolveAction();

			// Validate and dispatch
			$this->validateController();
			$this->dispatchController();

		} 
		catch (RouteException $exception) { throw $exception; }
	}

	// ===========================================================================
	// CACHE HANDLING
	// ===========================================================================

	/**
	 * Check if cached page exists and serve it
	 *
	 * Determines if current request should be cached based on:
	 * - Cache enabled in config
	 * - HTTP request method (only GET/HEAD)
	 * - URL not in exclusion list
	 *
	 * Only sets Registry::setShouldCache(true) after cache miss,
	 * since that's when View will actually need the flag.
	 * If cached version exists, outputs it and returns true.
	 *
	 * @return bool True if cache served, false otherwise
	 */
	private function checkCache()
	{
		$cacheConfig = Registry::cacheConfig();

		// Cache disabled?
		if (!$cacheConfig['enabled']) return false;

		// Check HTTP method
		$requestMethod = $_SERVER['REQUEST_METHOD'];
		if (!in_array($requestMethod, $cacheConfig['methods'])) return false;

		// Check URL exclusions
		$requestUri = Request::fullUri();
		foreach ($cacheConfig['exclude_urls'] as $pattern) {

			if ($this->urlMatches($requestUri, $pattern)) return false;
		}

		// Try to get from cache
		$cacheKey = 'page:' . md5($requestUri);
		if (Cache::has($cacheKey))
		{
			echo Cache::get($cacheKey);
			return true;
		}

		// Cache miss - set flag so View knows to store rendered output
		Registry::setShouldCache(true);

		return false;
	}

	/**
	 * Check if URL matches exclusion pattern
	 *
	 * Supports exact matches and wildcard patterns:
	 * - '/admin' matches only '/admin'
	 * - '/admin/*' matches '/admin/users', '/admin/posts', etc.
	 *
	 * @param string $url URL to check
	 * @param string $pattern Pattern to match against
	 * @return bool True if URL matches pattern
	 */
	private function urlMatches($url, $pattern)
	{
		// Exact match
		if ($url === $pattern) return true;

		// Wildcard match: '/admin/*' matches '/admin/anything'
		if (strpos($pattern, '*') !== false)
		{
			$regex = '#^' . str_replace('*', '.*', preg_quote($pattern, '#')) . '$#';
			return preg_match($regex, $url) === 1;
		}

		return false;
	}

	// ===========================================================================
	// ROUTE MATCHING
	// ===========================================================================

	/**
	 * Match URL against defined routes
	 * 
	 * Checks if the current URL matches any defined routes in routes.php.
	 * If a match is found, the RouteParser handles controller/action resolution.
	 * 
	 * @return void
	 */
	private function matchRoute()
	{
		// Create route parser instance (parses URL in constructor)
		$this->routeParser = new RouteParser(
			Registry::url(),
			$this->routes
		);

		// Check if URL matches a defined route
		if ($this->routeParser->matchRoute()) {
			// Route matched - let RouteParser set controller, method, params from route
			$this->routeParser->setController()->setMethod()->setParameters();
		}
		else {

			// Consider direct URL mapping if routing mode is not strict
			$strictRounting 	= $this->settings['routing']['strict'] ?? false;
			
			if($strictRounting) {
				// Reject if no routes match and strict mode enabled
				$components 	= join( '/', array_slice($this->routeParser->urlComponents, 0, 2));
				throw new RouteException( "Undefined route to '{$components}'" );				
			}
			else {
				// No route matched - extract controller, method, params from URL
				$this->routeParser->setControllerUrl()->setMethodUrl()->setParameters();			
			}			
		}
	}

	// ===========================================================================
	// CONTROLLER RESOLUTION
	// ===========================================================================

	/**
	 * Resolve the controller name
	 * 
	 * Determines which controller to use based on:
	 * 1. Matched route (if route was matched)
	 * 2. URL parsing (if no route matched)
	 * 3. Default controller (if no controller in URL)
	 * 
	 * @return void
	 */
	private function resolveController()
	{
		// Check if route parser found a controller
		$controller = $this->routeParser->getController();

		if ($controller !== null) {
			// Use controller from matched route or URL
			$this->controller = $controller;
		} 
		else {
			// No controller in URL - use default
			$this->controller = $this->settings['default']['controller'];
		}
	}

	/**
	 * Resolve the action/method name
	 * 
	 * Determines which method to call based on:
	 * 1. Matched route (if route was matched)
	 * 2. URL parsing (if no route matched)
	 * 3. Default action (if no method in URL)
	 * 
	 * @return void
	 */
	private function resolveAction()
	{
		// Check if route parser found a method
		$method = $this->routeParser->getMethod();

		if ($method !== null) {
			// Use action from matched route or URL
			$this->action = $method;
		} 
		else {
			// No method in URL - use default action
			$this->action = $this->settings['default']['action'];
		}

		// Get parameters from route parser
		$this->parameters = $this->routeParser->getParameters();
	}

	// ===========================================================================
	// CONTROLLER VALIDATION & DISPATCH
	// ===========================================================================

	/**
	 * Validate that controller class and method exist
	 * 
	 * Checks:
	 * 1. Controller class exists
	 * 2. Controller extends base controller
	 * 3. Method exists on controller
	 * 4. Method is callable
	 * 
	 * @return void
	 * @throws RouteException If validation fails
	 */
	private function validateController()
	{
		// Build namespaced controller class name
		$controllerClass = 'Controllers\\' . ucwords($this->controller) . 'Controller';

		// Check if controller class exists
		if (!class_exists($controllerClass)) {

			// Check if catch-all routing is enabled
			if (Registry::settings()['catch_all']['enabled'] ?? false) {

				// Use catch-all controller instead of throwing error
				$this->controller 	= Registry::settings()['catch_all']['controller'];
				$this->action 		= Registry::settings()['catch_all']['method'];

				// Pass full URL as first parameter to catch-all method
				$this->parameters = [Registry::url()];

				// Rebuild controller class name and validate catch-all controller exists
				$controllerClass = 'Controllers\\' . ucwords($this->controller) . 'Controller';
				if (!class_exists($controllerClass)) {
					throw new RouteException(
						"Catch-all controller '{$controllerClass}' is not defined"
					);
				}
			} 
			else {
				// Catch-all disabled - throw original error
				throw new RouteException(
					"Controller class '{$controllerClass}' is not defined"
				);
			}
		}

		// Store the full controller class name
		$this->controller = $controllerClass;

		// Check if method exists
		if (!method_exists($this->controller, $this->action)) {
			
			// Try to call magic method to get action name
			$dispatch = new $this->controller;

			if (!$dispatch->{$this->action}()) {
				throw new RouteException(
					"Method '{$this->action}' does not exist on controller '{$this->controller}'"
				);
			}

			// Magic method returned action name
			$this->action = $dispatch->{$this->action}();
		}
	}

	/**
	 * Dispatch the controller method
	 * 
	 * Process:
	 * 1. Create controller instance
	 * 2. Validate controller extends base controller
	 * 3. Initialize controller properties
	 * 4. Get method reflection info
	 * 5. Prepare method parameters
	 * 6. Execute filters (if enabled)
	 * 7. Call controller method
	 * 
	 * @return void
	 * @throws RouteException If dispatch fails
	 */
	private function dispatchController() 
	{
		// Create controller instance
		$dispatch = new $this->controller;

		// Ensure controller extends base controller
		if (!$dispatch instanceof \Rackage\Controller) {
			throw new RouteException(
				"Controller '{$this->controller}' must extend Rackage\\Controller"
			);
		}

		// Initialize controller properties
		$dispatch->_addSettings();

		// Get reflection info for method parameter handling
		$reflection = new ReflectionClass($dispatch);
		$method = $reflection->getMethod($this->action);

		// Get expected parameter count
		$expectedParams = count($method->getParameters());

		// Pad parameters array if needed
		if ($expectedParams > count($this->parameters)) {
			$this->parameters = array_pad(
				$this->parameters,
				$expectedParams,
				null
			);
		}

		// Check if filters are enabled
		if ($dispatch->enable_filters === true) {
			// Execute with filters
			$this->dispatchWithFilters($dispatch, $reflection, $method);
		} 
		else {
			// Execute without filters
			call_user_func_array( array($dispatch, $this->action), $this->parameters );
		}
	}

	// ===========================================================================
	// FILTER EXECUTION
	// ===========================================================================

	/**
	 * Dispatch controller method with @before and @after filters
	 * 
	 * Process:
	 * 1. Parse class-level filters
	 * 2. Parse method-level filters
	 * 3. Merge filters (class before, method before, method after, class after)
	 * 4. Execute @before filters
	 * 5. Execute controller method
	 * 6. Execute @after filters
	 * 
	 * @param object $dispatch Controller instance
	 * @param ReflectionClass $reflection Class reflection
	 * @param ReflectionMethod $method Method reflection
	 * @return void
	 * @throws RouteException If filter execution fails
	 */
	private function dispatchWithFilters($dispatch, $reflection, $method)
	{
		try {
			// Get class-level filters from docblock
			$classFilters = $this->parseFilters($reflection->getDocComment());

			// Get method-level filters from docblock
			$methodFilters = $this->parseFilters($method->getDocComment());

			// Merge filters in correct order
			$filters = $this->mergeFilters($classFilters, $methodFilters);

			// If no filters found, just execute method
			if ($filters === false || empty($filters)) {
				call_user_func_array(
					array($dispatch, $this->action),
					$this->parameters
				);
				return;
			}

			// Execute @before filters
			if (isset($filters['before'])) {
				$this->executeFilters($filters['before'], $dispatch, 'before');
			}

			// Execute controller method
			call_user_func_array(
				array($dispatch, $this->action),
				$this->parameters
			);

			// Execute @after filters
			if (isset($filters['after'])) {
				$this->executeFilters($filters['after'], $dispatch, 'after');
			}

		} catch (RouteException $exception) { throw $exception; }
	}

	/**
	 * Merge class-level and method-level filters
	 * 
	 * Order:
	 * 1. Class @before filters (run first)
	 * 2. Method @before filters
	 * 3. Method @after filters
	 * 4. Class @after filters (run last)
	 * 
	 * @param array|false $classFilters Class-level filters
	 * @param array|false $methodFilters Method-level filters
	 * @return array|false Merged filters or false if none
	 */
	private function mergeFilters($classFilters, $methodFilters)
	{
		// If no filters at all, return false
		if ($classFilters === false && $methodFilters === false) {
			return false;
		}

		// Initialize merged filters array
		$merged = array(
			'before' => array(),
			'after' => array()
		);

		// Add class-level @before filters first
		if ($classFilters && isset($classFilters['before'])) {
			$merged['before'] = array_merge(
				$merged['before'],
				$classFilters['before']
			);
		}

		// Add method-level @before filters
		if ($methodFilters && isset($methodFilters['before'])) {
			$merged['before'] = array_merge(
				$merged['before'],
				$methodFilters['before']
			);
		}

		// Add method-level @after filters first
		if ($methodFilters && isset($methodFilters['after'])) {
			$merged['after'] = array_merge(
				$merged['after'],
				$methodFilters['after']
			);
		}

		// Add class-level @after filters last
		if ($classFilters && isset($classFilters['after'])) {
			$merged['after'] = array_merge(
				$merged['after'],
				$classFilters['after']
			);
		}

		return $merged;
	}

	/**
	 * Execute a list of filters
	 * 
	 * Filters can be:
	 * 1. Single method name: ['checkAuth'] -> calls $dispatch->checkAuth()
	 * 2. Class and method: ['AuthFilter', 'check'] -> calls (new AuthFilter())->check()
	 * 
	 * @param array $filters List of filters to execute
	 * @param object $dispatch Controller instance
	 * @param string $type Filter type ('before' or 'after') for error messages
	 * @return void
	 * @throws RouteException If filter validation fails
	 */
	private function executeFilters($filters, $dispatch, $type)
	{
		foreach ($filters as $filter) {
			
			// Determine filter type by array count
			switch (count($filter)) {
				
				// Single element: method on current controller
				case 1:
					$filterMethod = $filter[0];

					// Validate method exists
					if (!method_exists($dispatch, $filterMethod)) {
						throw new RouteException(
							"@{$type} filter method '{$filterMethod}' does not exist on controller '{$this->controller}'"
						);
					}

					// Execute filter
					$dispatch->$filterMethod();
					break;

				// Two elements: external class and method
				case 2:
					$filterClass = $filter[0];
					$filterMethod = $filter[1];

					// Validate class exists
					if (!class_exists($filterClass)) {
						throw new RouteException(
							"@{$type} filter class '{$filterClass}' does not exist"
						);
					}

					// Validate method exists
					if (!method_exists($filterClass, $filterMethod)) {
						throw new RouteException(
							"@{$type} filter method '{$filterMethod}' does not exist on class '{$filterClass}'"
						);
					}

					// Execute filter
					(new $filterClass())->$filterMethod();
					break;
			}
		}
	}

	/**
	 * Parse @before and @after filters from docblock
	 *
	 * Extracts filter annotations from controller method docblocks.
	 * Supports both single method names and class/method pairs.
	 *
	 * Filter Syntax:
	 *
	 *   Single method (on current controller):
	 *     @before checkAuth
	 *     @after logActivity
	 *
	 *   External class and method:
	 *     @before AuthFilter, check
	 *     @before Filters\Security, validateToken
	 *
	 *   Multiple filters:
	 *     @before checkAuth
	 *     @before checkAdmin
	 *     @after clearCache
	 *     @after logActivity
	 *
	 * Examples:
	 *   Input:  "/** @before checkAuth @before validateInput @after log *\/"
	 *   Output: ['before' => [['checkAuth'], ['validateInput']], 'after' => [['log']]]
	 *
	 *   Input:  "/** @before AuthFilter check *\/"
	 *   Output: ['before' => [['AuthFilter', 'check']]]
	 *
	 *   Input:  "/** @param string $id *\/"  (no filters)
	 *   Output: []
	 *
	 * Notes:
	 *   - Commas are automatically stripped from filter names
	 *   - Extra whitespace is trimmed
	 *   - Returns empty array if no filters found (not false)
	 *   - Each @before/@after can have 1 parameter (method) or 2 (class, method)
	 *
	 * Filter Format:
	 *   - Method only: "methodName"
	 *   - Class + method: "ClassName, methodName" or "ClassName,methodName"
	 *   - Commas and extra spaces are automatically cleaned
	 *
	 * @param string $docblock The docblock comment string
	 * @return array Array with 'before' and/or 'after' keys, or empty array
	 */
	private function parseFilters($docblock)
	{
		// Match only @before and @after annotations (ignore @param, @return, etc.)
		$pattern = "#@(before|after)\s+([a-zA-Z0-9\\\\_,\s]+)#";

		preg_match_all($pattern, $docblock, $matches, PREG_SET_ORDER);

		// No filters found, return empty array
		if (empty($matches)) {
			return [];
		}

		$beforeFilters = [];
		$afterFilters = [];

		// Process each @before or @after annotation
		foreach ($matches as $match) {
			$filterType = $match[1];  // "before" or "after"
			$filterValue = $match[2]; // "checkAuth" or "AuthFilter, check"

			// Split by space to get individual parameters
			$parts = preg_split('/\s+/', trim($filterValue));

			// Clean each part (remove commas, trim whitespace, remove empties)
			$cleanParts = [];
			foreach ($parts as $part) {
				$cleaned = trim(str_replace(',', '', $part));
				if (!empty($cleaned)) {
					$cleanParts[] = $cleaned;
				}
			}

			// Add to appropriate filter array (as nested array)
			if ($filterType === 'before') {
				$beforeFilters[] = $cleanParts;
			} else {
				$afterFilters[] = $cleanParts;
			}
		}

		// Build return array
		$result = [];
		if (!empty($beforeFilters)) {
			$result['before'] = $beforeFilters;
		}
		if (!empty($afterFilters)) {
			$result['after'] = $afterFilters;
		}

		return $result;
	}

	// ===========================================================================
	// GETTERS (For Testing)
	// ===========================================================================

	/**
	 * Get resolved controller name (for testing)
	 * @return string
	 */
	public function getController()
	{
		return $this->controller;
	}

	/**
	 * Get resolved action name (for testing)
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Get method parameters (for testing)
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}
}