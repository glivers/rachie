<?php

/**
 * Fatal Error Handler
 *
 * This file handles PHP errors, warnings, and fatal errors before the framework
 * is fully initialized. It provides error logging and display for both development
 * and production environments.
 *
 * Loading Context:
 *   - Loaded AFTER config/settings.php ($settings array available)
 *   - Loaded AFTER DEV constant is defined
 *   - Loaded BEFORE Registry initialization
 *
 * Limitations:
 *   - Can access $settings array and DEV constant
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Core
 * @package Core\Exceptions
 * @link https://github.com/glivers/rachie
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

// ===========================================================================
// CONFIGURATION ACCESS
// ===========================================================================

// Make $settings available to all functions in this file
// $settings is loaded in bootstrap.php before this file is included
$settings;

// ===========================================================================
// ERROR TYPE MAPPING
// ===========================================================================

// Map error codes to human-readable names
$ERROR_TYPES = array(
	E_ERROR             => 'E_ERROR',
	E_WARNING           => 'E_WARNING',
	E_PARSE             => 'E_PARSE',
	E_NOTICE            => 'E_NOTICE',
	E_CORE_ERROR        => 'E_CORE_ERROR',
	E_CORE_WARNING      => 'E_CORE_WARNING',
	E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
	E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
	E_USER_ERROR        => 'E_USER_ERROR',
	E_USER_WARNING      => 'E_USER_WARNING',
	E_USER_NOTICE       => 'E_USER_NOTICE',
	E_STRICT            => 'E_STRICT',
	E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
	E_DEPRECATED        => 'E_DEPRECATED',
	E_USER_DEPRECATED   => 'E_USER_DEPRECATED'
);

// ===========================================================================
// PHP ERROR CONFIGURATION
// ===========================================================================

// Disable PHP's native error logging (we handle it ourselves)
ini_set('log_errors', 'Off');

// Define fatal error types
define('E_FATAL', E_ERROR | E_USER_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR);

// Set error reporting level
define('ERROR_REPORTING', E_ALL | E_STRICT);

// Register shutdown function (catches fatal errors)
register_shutdown_function('shut');

// Register error handler (catches non-fatal errors)
set_error_handler('handler');

// ===========================================================================
// SHUTDOWN HANDLER - Catches fatal errors
// ===========================================================================

/**
 * Shutdown handler - catches fatal errors that would otherwise kill the script
 *
 * This function is called when PHP shuts down, either normally or due to a fatal error.
 * It checks if the shutdown was caused by a fatal error and passes it to the error handler.
 *
 * @return void
 */
function shut()
{
	// Get the last error that occurred
	$error = error_get_last();

	// Check if it was a fatal error
	if ($error && ($error['type'] & E_FATAL))
	{
		// Pass to error handler
		handler($error['type'], $error['message'], $error['file'], $error['line']);
	}
}

// ===========================================================================
// ERROR HANDLER - Processes and displays errors
// ===========================================================================

/**
 * Error handler - processes all PHP errors and displays them appropriately
 *
 * This function handles all errors (fatal and non-fatal), logs them to the error log,
 * and displays them based on the environment (development vs production).
 *
 * @param int $errNo Error number (E_ERROR, E_WARNING, etc.)
 * @param string $errMsg Error message
 * @param string $errFile File where error occurred
 * @param int $errLine Line number where error occurred
 * @return void
 */
function handler($errNo, $errMsg, $errFile, $errLine)
{

	global $settings, $ERROR_TYPES;

	// Get human-readable error type name
	$type = isset($ERROR_TYPES[$errNo]) ? $ERROR_TYPES[$errNo] : 'UNKNOWN_ERROR';

	// Get application root path from settings
	$root = $settings['root'];

	// Build stack trace for context
	$exception = new Exception;
	$trace = $exception->getTraceAsString();
	$context = substr($trace, 0, (strpos($trace, "#10")) ? strpos($trace, "#10") - 2 : 2000);

	// Compose error message for display (with HTML formatting)
	$showError = "<b>$type: $errMsg in $errFile on line($errLine)</b> STACK TRACE: $context";

	// Remove absolute path and .php extension for cleaner display
	$showError = str_replace(array($root, '.php'), '', $showError);

	// Compose error message for log file (plain text, no HTML)
	$logError = "$type $errMsg in $errFile on line ($errLine) STACK TRACE: $context";

	// Get error log file path from settings
	$logFile = $root . '/' . $settings['error_log'];

	// Write error to log file
	error_log($logError . PHP_EOL, 3, $logFile);

	// Display error based on environment
	displayError($showError, $logError);
}

// ===========================================================================
// ERROR DISPLAY - Shows errors based on environment
// ===========================================================================

/**
 * Display error message based on environment and request type
 *
 * - Development: Shows detailed error with stack trace
 * - Production: Shows generic error page (hides sensitive details)
 * - Console: Outputs plain text error message
 * - Web: Loads HTML error page template
 *
 * Safety:
 *   - Checks if error template exists before including
 *   - Falls back to plain HTML if template missing or broken
 *   - Prevents blank page if error handler itself fails
 *
 * @param string $showError HTML-formatted error message for display
 * @param string $logError Plain text error message
 * @return void
 */
function displayError($showError, $logError)
{
	// Clear any output buffers to prevent partial rendering
	// This ensures only the error page displays (not mixed with app output)
	while (ob_get_level() > 0) {
		ob_end_clean();
	}

	// Check if this is a console request
	$isConsole = defined('ROLINE_INSTANCE');

	// Path to error page template
	$errorPage = dirname(__FILE__) . '/View.php';

	// PRODUCTION ENVIRONMENT - Hide detailed errors
	if (DEV === false)
	{
		if ($isConsole)
		{
			// Console: Show log message (no HTML)
			echo $logError;
		}
		else
		{
			global $settings;
			$title = $settings['title'];
			// Web: Show generic error page
			$hideError = true;
			$error = $showError;

			// Try to load template, fallback to plain HTML if missing
			if (file_exists($errorPage))
			{
				include $errorPage;
			}
			else
			{
				// Fallback: simple HTML error page
				echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
				echo '<h1>An error occurred</h1>';
				echo '<p>The application encountered an error. Please contact the administrator.</p>';
				echo '</body></html>';
			}

			exit();
		}
	}

	// DEVELOPMENT ENVIRONMENT - Show detailed errors
	else
	{
		if ($isConsole)
		{
			// Console: Show log message (no HTML)
			echo $logError;
		}
		else
		{
			global $settings;
			$title = $settings['title'];
			$hideError = false;

			// Web: Show detailed error page
			$error = $showError;

			// Try to load template, fallback to plain HTML if missing
			if (file_exists($errorPage))
			{
				include $errorPage;
			}
			else
			{
				// Fallback: show error directly with warning about missing template
				echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
				echo '<h1>Error Handler Warning</h1>';
				echo '<p><strong>Error template missing:</strong> ' . htmlspecialchars($errorPage) . '</p>';
				echo '<hr><h2>Error Details:</h2>';
				echo '<pre>' . htmlspecialchars($error) . '</pre>';
				echo '</body></html>';
			}

			exit();
		}
	}
}
