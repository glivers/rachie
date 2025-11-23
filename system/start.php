<?php
/**
 * Application Start
 * 
 * This file is Step 2 of the Rachie boot sequence.
 * It initializes request input, loads routes, and dispatches the router.
 *
 * Boot Sequence:
 * 1. bootstrap.php - System validation, configuration loading, Registry setup
 * 2. start.php (this file) - Initialize Input, load routes, create Router (web requests only)
 * 3. Router::dispatch() - Parse URL, match routes, dispatch controller
 *
 * The actual routing logic lives in Rackage\Routes\Router
 * which is part of the Rackage package (updated via Composer).
 * 
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Core
 * @package Rachie
 * @link https://github.com/glivers/rachie
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.0
 */

use Rackage\Routes\Router;
use Rackage\Registry;
use Rackage\Input;
use Rackage\Session;

// ===========================================================================
// INITIALIZE REQUEST INPUT
// ===========================================================================

// Capture GET and POST data for access throughout the application
// This makes request data available via Input::get() and Input::post()
// URL route parameters will be added later by RouteParser via Input::setUrl()
Input::setGet()->setPost();

// ===========================================================================
// LOAD ROUTE DEFINITIONS
// ===========================================================================

try {
	// Check if routes configuration file exists
	if (!file_exists(__DIR__ . '/../config/routes.php')) {
		throw new Rackage\Routes\RouteException(
			"Route configuration file not found: config/routes.php. Please restore if deleted."
		);
	}

	// Load route definitions
	$routes = require __DIR__ . '/../config/routes.php';

} catch (Rackage\Routes\RouteException $e) {
	$e->errorShow();
	exit();
}

// ===========================================================================
// CREATE AND DISPATCH ROUTER
// ===========================================================================

// Create router with settings and routes
$router = new Router(Registry::settings(), $routes);

// Dispatch the request
$router->dispatch();

// ===========================================================================
// AGE FLASH DATA
// ===========================================================================

// Move current flash to old flash for next request
// This allows flash messages to persist for exactly one request (perfect for redirects)
Session::ageFlashData();