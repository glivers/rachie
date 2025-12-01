<?php

/**
 * Application Entry Point
 * 
 * This is the single entry point for all HTTP requests to the Rachie application.
 * The web server (Apache/Nginx) redirects all non-static requests here via .htaccess
 * or server configuration.
 * 
 * Request Flow:
 * 1. Browser requests a URL (e.g., example.com/admin/users)
 * 2. Web server checks if it's a static file (CSS, JS, images)
 * 3. If not static, request is routed to this file
 * 4. This file loads bootstrap.php which starts the framework
 * 5. Router handles the request and dispatches to controller
 * 
 * Error Handling:
 * - PHP errors are logged to vault/logs/error.log
 * - Errors are not displayed (handled by custom error handler)
 * - Bootstrap errors show formatted error page
 * 
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rachie
 * @package Core
 * @link https://github.com/glivers/rachie
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.0
 */

// ===========================================================================
// PERFORMANCE TRACKING
// ===========================================================================

// Record application start time for performance profiling
// This will be available in Registry::$rachie_app_start
//$rachie_app_start = microtime(true);

define('RACHIE_START', microtime(true));

// ===========================================================================
// ERROR REPORTING CONFIGURATION
// ===========================================================================

// Report all errors for logging
// E_ALL includes notices, warnings, and fatal errors
error_reporting(E_ALL);

// Don't display errors to users (security best practice)
// Errors will be handled by custom error handler
ini_set('display_errors', 'Off');

// Log errors to file for debugging
ini_set('log_errors', 'On');

// Set error log file path
// All PHP errors will be written here for troubleshooting
ini_set('error_log', dirname(__DIR__) . '/vault/logs/error.log');

// ===========================================================================
// BOOTSTRAP LOADING
// ===========================================================================

try {
	
	// Check if bootstrap file exists
	// Bootstrap handles all system initialization
	$bootstrap = __DIR__ . '/../system/bootstrap.php';
	
	if (!file_exists($bootstrap)) {
		throw new Exception(
			"Bootstrap file not found at: system/bootstrap.php. " .
			"This file is required to run the application. " .
			"Please restore if you deleted it."
		);
	}
	
	// Load the bootstrap file
	// This initializes the framework and starts request handling
	require_once $bootstrap;

} catch (Exception $e) {
	
	// ===========================================================================
	// BOOTSTRAP ERROR HANDLING
	// ===========================================================================
	// If we get here, something critical failed before the framework loaded
	
	// Check if this is a console/CLI request
	if (defined('CONSOLE_INSTANCE')) {
		
		// Console request - output plain text error
		echo "ERROR: " . $e->getMessage() . "\n";
		exit(1);
		
	} else {
		
		// Web request - display formatted error page
		$error = $e->getMessage();
		
		// Load error page template
		$errorPage = dirname(__DIR__) . '/system/Exceptions/View.php';
		
		if (file_exists($errorPage)) {
			include $errorPage;
		} else {
			// Fallback if error page is also missing
			echo "<!DOCTYPE html>
<html>
<head>
	<title>Application Error</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 40px; }
		.error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; }
	</style>
</head>
<body>
	<div class='error'>
		<h1>Application Error</h1>
		<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
		<p>Please contact the system administrator.</p>
	</div>
</body>
</html>";
		}
		
		exit(1);
	}
}