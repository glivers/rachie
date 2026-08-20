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
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Rachie
 * @package Bootstrap 
 * @link https://github.com/glivers/rachie
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.0
 */

// ===========================================================================
// SYSTEM BOOTSTRAP - Load required files before proceeding
// ===========================================================================

try {
	
	// -----------------------------------------------------------------------
	// Load The Settings.php File
	// -----------------------------------------------------------------------
	// This file contains core application settings. Without it the framework freezes
	// The application cannot run without it.
	$settings = require_once (file_exists(__DIR__ . '/../config/overrides/settings.php')
	? __DIR__ . '/../config/overrides/settings.php'
	: __DIR__ . '/../config/settings.php');

	// Set application timezone from configuration
	// This ensures all date/time functions (date(), time(), Date helper, etc.)
	if (isset($settings['timezone'])) {
		date_default_timezone_set($settings['timezone']);
	}

	// Define development environment constant
	// This affects error display and debugging features
	define('DEBUG', isset($settings['debug']) ? $settings['debug'] : false);
	
	// ===========================================================================
	// ERROR HANDLING SETUP
	// ===========================================================================
	
	// Register custom error handler
	// This handles PHP errors, exceptions, and fatal errors gracefully
	require_once __DIR__ . '/Exceptions/Shutdown.php';	

	// -----------------------------------------------------------------------
	// Load application configuration
	// -----------------------------------------------------------------------
	
	// Load cached configuration if present
	$config = [];
	if(file_exists(__DIR__ . '/../vault/config.php')) {

		// Load all cached configuration into one main $config array
		$config = require_once __DIR__ . '/../vault/config.php';

		// Delete cached config if we are in debug mode
		// Helps to clean up when switching from production mode to dev mode
		if(isset($settings['debug']) &&  $settings['debug'] == true) {

			unlink(__DIR__ . '/../vault/config.php');
			// Reset config for manual loading
			$config	= [];
		}
	}
	
	// If configuration is loaded jump to framework initialization
	if($config) goto framework_init;
	
	// ===========================================================================
	// CONFIGURATION LOADING - Load all config files
	// ===========================================================================
	
	// Load security configuration
	// Helps to set the correct headers to secure the application
	$config['security'] = require_once (file_exists(__DIR__ . '/../config/overrides/security.php')
	? __DIR__ . '/../config/overrides/security.php'
	: __DIR__ . '/../config/security.php');
	
	// Load session settings
	// These help sync browser sessions configurations and browser cookies
	$config['session'] = require_once (file_exists(__DIR__ . '/../config/overrides/session.php')
	? __DIR__ . '/../config/overrides/session.php'
	: __DIR__ . '/../config/session.php');
	
	// Load database configuration
	// Use to access the databse through the model class
	$config['database'] = require_once (file_exists(__DIR__ . '/../config/overrides/database.php')
	? __DIR__ . '/../config/overrides/database.php'
	: __DIR__ . '/../config/database.php');
	
	// Load cache configuration
	// Used to 'cache' responses to avoid processing overhead
	$config['cache'] = require_once (file_exists(__DIR__ . '/../config/overrides/cache.php')
	? __DIR__ . '/../config/overrides/cache.php'
	: __DIR__ . '/../config/cache.php');
	
	// Load mail configuration
	// Used by Mail class to send email messages
	$config['mail'] = require_once (file_exists(__DIR__ . '/../config/overrides/mail.php')
	? __DIR__ . '/../config/overrides/mail.php'
	: __DIR__ . '/../config/mail.php');

	// Write configuration to file if in production
	if(isset($settings['debug']) && $settings['debug'] == false) {

		file_put_contents("{$settings['root']}/vault/config.php",
			"<?php return \n//Auto-generated configuration file\n" . var_export($config, true) . ";"
		);
	}
	
	// ===========================================================================
	// FRAMEWORK INITIALIZATION
	// ===========================================================================

	framework_init:

	// Define a private session storage path to prevent session hijacking on shared hosting
	session_save_path(__DIR__ . '/../vault/sessions');

	// Enable the cleanup "Lottery" (1% chance per request) so PHP's GC clears Session from custom folder
	ini_set('session.gc_probability', 1);
	ini_set('session.gc_divisor', 100);

	// Start PHP session, passing the application specific config params
	// Required for session handling, flash messages, CSRF protection, etc.
	session_start($config['session']);

	// Load Composer autoloader
	// Enables PSR-4 autoloading for all framework and application classes
	$autoloader = require_once __DIR__ . '/../vendor/autoload.php';

	// Load custom namespaces from config (if exists)
	// Allows developers to add Themes, Plugins, Modules, etc. without editing composer.json
	if (file_exists(__DIR__ . '/../application/autoload.php')) {
		$customNamespaces = require_once __DIR__ . '/../application/autoload.php';

		if (is_array($customNamespaces) && !empty($customNamespaces)) {
			foreach ($customNamespaces as $namespace => $path) {
				// Ensure namespace ends with \\
				if (substr($namespace, -1) !== '\\') {
					$namespace .= '\\';
				}

				// Make path absolute from application root
				$absolutePath = __DIR__ . '/../' . ltrim($path, '/');

				// Register with Composer's ClassLoader
				$autoloader->addPsr4($namespace, $absolutePath);
			}
		}
	}

	// Register template stream wrapper for production rendering
	// Enables zero-disk-I/O view rendering via 'template://render'
	Rackage\Templates\TemplateStream::register();

	// Load application constants
	// User-defined constants available throughout the application
	require_once __DIR__ . '/../application/constants.php';

	// ===========================================================================
	// REGISTRY CONFIGURATION - Store all configs for application-wide access
	// ===========================================================================

	// Load all configurations into Registry using method chaining
	// Registry provides centralized access to config and resources
	Rackage\Registry::settings($settings)
					->securityConfig($config['security'])
	                ->dbConfig($config['database'])
	                ->cacheConfig($config['cache'])
	                ->mailConfig($config['mail'])
	                ->setUrl($_GET['_rachie_route'] ?? '');

	// Store application start time for performance profiling
	Rackage\Registry::$rachie_app_start = RACHIE_START;

	// Free memory by unsetting loaded config arrays
	// Registry has stored everything we need
	// unset($config);

	// ===========================================================================
	// APPLICATION START
	// ===========================================================================

	// Check if this is a console request (CLI tools, artisan-style commands)
	// Console requests skip the web routing system
	if (!defined('ROLINE_INSTANCE')) {
		
		// This is a web request - load the router
		// start.php contains the routing logic and controller dispatch
		$start = require_once __DIR__ . '/start.php';		
	}

} 
catch (\Throwable $exception) {
	
	// ===========================================================================
	// BOOTSTRAP ERROR HANDLING
	// ===========================================================================
	// If we get here, something critical failed during bootstrap
	// Display appropriate error message based on request type
	
	// Detect the path accessed during error
	$path   	= $_SERVER['REQUEST_URI'] ?? 'CLI';
	
	// Get application root path from settings
	$root = $settings['root'];

	// Build stack trace for context
	$trace 		= $exception->getTraceAsString();
	$context 	= substr($trace, 0, (strpos($trace, "#10")) ? strpos($trace, "#10") - 2 : 2000);
	$context 	= preg_replace('/\n/', ' ', $context);

	// Get human-readable error type name
	$file 		= $exception->getFile();
	$line 		= $exception->getLine();
	$classname	= get_class($exception);
	$message 	= $exception->getMessage();

	// Compose error message for both display and logging
	$timestamp  	= "[" . date("d-M-Y H:i:s") . "]";
	$errorMessage 	= sprintf("[%s] [%s] %s in %s on line (%s) STACK TRACE: %s",
					    $classname, $path, $message, $file, $line, $context);

	// Remove absolute path and .php extension for cleaner display
	$errorMessage = str_replace([$root, '.php'], '', $errorMessage);

	// Compose error message for log file (plain text, no HTML)
	$errorLogged  = $timestamp . " " . $errorMessage;

	// Get error log file path from settings
	$logFile = $root . '/' . $settings['error_log'];

	// Write error to log file
	error_log($errorLogged . PHP_EOL, 3, $logFile);

	// Display error based on environment
	displayError($errorMessage, $settings);
}