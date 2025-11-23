<?php 
/**
 * Application Bootstrap
 * 
 * This file is the execution entry point for the Rachie application.
 * It performs critical system checks, loads configurations, and prepares
 * the application for request handling.
 *
 * Boot Sequence:
 * 1. bootstrap.php (this file) - System validation, configuration loading, Registry setup
 * 2. start.php - Initialize Input, load routes, create Router (web requests only)
 * 3. Router::dispatch() - Parse URL, match routes, dispatch controller
 *
 * This file:
 *   - Checks for required system files
 *   - Sets up error handling
 *   - Loads Composer autoloader
 *   - Initializes session
 *   - Loads all configuration files into Registry
 *   - Prepares application for routing (web) or console execution (CLI)
 * 
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rachie
 * @package Bootstrap
 * @link https://github.com/glivers/rachie
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.0
 */

// ===========================================================================
// SYSTEM VALIDATION - Check for required files before proceeding
// ===========================================================================

try {
	
	// -----------------------------------------------------------------------
	// Check Composer Autoloader
	// -----------------------------------------------------------------------
	// The autoloader is required for loading all framework and application classes.
	// If missing, run: composer install
	
	if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
		throw new Exception(
			"Composer autoloader missing! Please run 'composer install' from your terminal."
		);
	}

	// -----------------------------------------------------------------------
	// Check Core Configuration Files
	// -----------------------------------------------------------------------
	// These files contain essential application settings.
	// The application cannot run without them.
	
	// Application settings (timezone, paths, aliases, etc.)
	if (!file_exists(__DIR__ . '/../config/settings.php')) {
		throw new Exception(
			"Configuration file missing: config/settings.php. Please restore if deleted."
		);
	}

	// Database connection settings
	if (!file_exists(__DIR__ . '/../config/database.php')) {
		throw new Exception(
			"Database configuration missing: config/database.php. Please restore if deleted."
		);
	}

	// -----------------------------------------------------------------------
	// Check System Files
	// -----------------------------------------------------------------------
	
	// Application start/router file
	if (!file_exists(__DIR__ . '/start.php')) {
		throw new Exception(
			"System file missing: system/start.php. Please restore if deleted."
		);
	}

	// Application constants
	if (!file_exists(__DIR__ . '/../application/constants.php')) {
		throw new Exception(
			"Constants file missing: application/constants.php. Please restore if deleted."
		);
	}

	// Error handler
	if (!file_exists(__DIR__ . '/Exceptions/Shutdown.php')) {
		throw new Exception(
			"Error handler missing: system/Exceptions/Shutdown.php. Please restore if deleted."
		);
	}

	// ===========================================================================
	// CONFIGURATION LOADING - Load all config files
	// ===========================================================================

	// Load application settings
	$settings = require_once __DIR__ . '/../config/settings.php';

	// Set application timezone from configuration
	// This ensures all date/time functions (date(), time(), Date helper, etc.)
	// use the timezone specified in config/settings.php
	if (isset($settings['timezone'])) {
		date_default_timezone_set($settings['timezone']);
	}

	// Define development environment constant
	// This affects error display and debugging features
	if (isset($settings) && $settings['dev'] == true) {
		define('DEV', true);
	} else {
		define('DEV', false);
	}

	// Load database configuration
	$database = require_once __DIR__ . '/../config/database.php';

	// Load optional configuration files
	// These are not required but enable additional features
	
	// Cache configuration (optional)
	$cache = array();
	if (file_exists(__DIR__ . '/../config/cache.php')) {
		$cache = require_once __DIR__ . '/../config/cache.php';
	} elseif (DEV) {
		// Warn in development if cache config is missing
		trigger_error(
			'Cache configuration not found. Create config/cache.php to enable caching features.',
			E_USER_NOTICE
		);
	}

	// Mail configuration (optional)
	$mail = array();
	if (file_exists(__DIR__ . '/../config/mail.php')) {
		$mail = require_once __DIR__ . '/../config/mail.php';
	} elseif (DEV) {
		// Warn in development if mail config is missing
		trigger_error(
			'Mail configuration not found. Create config/mail.php to enable email features.',
			E_USER_NOTICE
		);
	}

	// ===========================================================================
	// ERROR HANDLING SETUP
	// ===========================================================================
	
	// Register custom error handler
	// This handles PHP errors, exceptions, and fatal errors gracefully
	require_once __DIR__ . '/Exceptions/Shutdown.php';

	// ===========================================================================
	// FRAMEWORK INITIALIZATION
	// ===========================================================================

	// Start PHP session
	// Required for session handling, flash messages, CSRF protection, etc.
	session_start();

	// Load Composer autoloader
	// Enables PSR-4 autoloading for all framework and application classes
	require_once __DIR__ . '/../vendor/autoload.php';

	// Load application constants
	// User-defined constants available throughout the application
	require_once __DIR__ . '/../application/constants.php';

	// ===========================================================================
	// REGISTRY CONFIGURATION - Store all configs for application-wide access
	// ===========================================================================

	// Load all configurations into Registry using method chaining
	// Registry provides centralized access to config and resources
	Rackage\Registry::setSettings($settings)
	                ->setDatabase($database)
	                ->setCache($cache)
	                ->setMail($mail)
	                ->setUrl($_GET['_rachie_route'] ?? '');

	// Store application start time for performance profiling
	Rackage\Registry::$rachie_app_start = $rachie_app_start;

	// Free memory by unsetting loaded config arrays
	// Registry has stored everything we need
	unset($database, $cache, $mail);

	// ===========================================================================
	// APPLICATION START
	// ===========================================================================

	// Check if this is a console request (CLI tools, artisan-style commands)
	// Console requests skip the web routing system
	if (!defined('CONSOLE_INSTANCE')) {
		
		// This is a web request - load the router
		// start.php contains the routing logic and controller dispatch
		$start = require_once __DIR__ . '/start.php';
		
		// Launch the application
		//$start();
	}

} 
catch (Exception $e) {
	
	// ===========================================================================
	// BOOTSTRAP ERROR HANDLING
	// ===========================================================================
	// If we get here, something critical failed during bootstrap
	// Display appropriate error message based on request type
	
	// Check if this is a console request
	if (defined('CONSOLE_INSTANCE')) {
		
		// Console request - output plain text error
		echo $e->getMessage();
		exit();
		
	} else {
		
		// Web request - display formatted error page
		$error = $e->getMessage();
		
		// Load error page template
		include __DIR__ . '/Exceptions/View.php';
		
		// Stop execution
		exit();
	}
}