<?php namespace Exceptions;

/**
 * Base Exception Handler
 *
 * All application exceptions extend this class.
 * Provides unified error handling with logging, dev/prod modes, and custom error pages.
 *
 * Features:
 *   - Error logging to file (vault/logs/error.log)
 *   - Development mode: Shows detailed error messages with stack traces
 *   - Production mode: Shows generic error page, hides sensitive details
 *   - Custom error page templates
 *   - Stack trace formatting and path sanitization
 *
 * Usage:
 *   class DatabaseException extends \Exceptions\ExceptionClass {}
 *   class RouteException extends \Exceptions\ExceptionClass {}
 *
 *   throw new DatabaseException("Connection failed");
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Core
 * @package Core\Exceptions
 * @link https://github.com/glivers/rachie
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

use Rackage\Registry;

class ExceptionClass extends \Exception
{

	/**
	 * Site title for error page
	 * @var string
	 */
	public $siteTitle;

	/**
	 * Path to error log file
	 * @var string
	 */
	public $logFile;

	/**
	 * Development environment flag
	 * @var bool
	 */
	public $DEV = true;

	/**
	 * Path to error page HTML template
	 * @var string
	 */
	public $errorPage;

	/**
	 * Complete error message with HTML formatting
	 * @var string
	 */
	public $fullError;

	/**
	 * Error message to display to user (sanitized paths)
	 * @var string
	 */
	public $showError;

	/**
	 * Error message to write to log file (plain text)
	 * @var string
	 */
	public $logError;

	/**
	 * Constructor - Initialize exception handler
	 *
	 * Sets up error logging, dev/prod mode, and error page template path.
	 *
	 * @param string|null $errorMessage Error message
	 * @param int $errorCode Error code
	 * @param \Exception|null $previous Previous exception for chaining
	 * @return void
	 */
	public function __construct($errorMessage = null, $errorCode = 0, Exception $previous = null)
	{

		// Enable parent constructor
		parent::__construct($errorMessage, $errorCode, $previous);

		// Set site title from config
		$this->siteTitle = Registry::settings()['title'];

		// Set error log file path
		$this->logFile = Registry::settings()['root'] . '/' . Registry::settings()['error_log'];

		// Set development environment flag
		$this->DEV = Registry::settings()['dev'];

		// Set error page template path
		$this->errorPage = Registry::settings()['root'] . "/system/Exceptions/View.php";
	}

	/**
	 * Log message to file
	 *
	 * Writes error message to the error log file specified in config.
	 *
	 * @return $this For method chaining
	 */
	public function logMessage()
	{
		// Write error message to log file
		error_log($this->logError . PHP_EOL, 3, $this->logFile);

		return $this;
	}

	/**
	 * Build formatted message
	 *
	 * Constructs error message with file, line, and stack trace.
	 * Creates two versions: one for display (sanitized) and one for logging (complete).
	 *
	 * @return $this For method chaining
	 */
	public function buildMessage()
	{
		// Get stack trace
		$trace = $this->getTraceAsString();

		// Check if this is a DatabaseException - show full trace for DB errors
		$isDatabaseException = (get_class($this) === 'Rackage\Database\DatabaseException' ||
		                        is_subclass_of($this, 'Rackage\Database\DatabaseException'));

		// In dev mode, show full trace for DatabaseExceptions, truncate others
		if ($this->DEV && $isDatabaseException) {
			$context = $trace;
		} else {
			$context = substr($trace, 2, (strpos($trace, "#4")) ? strpos($trace, "#4") - 2 : 1000);
		}

		// Build complete error message
		$this->fullError = '<b>' . $this->getMessage() . ' ' . $this->getFile() . '(' . $this->getLine() . ')</b> As seen from ' . $context;

		// Remove absolute paths from display version
		$this->showError = str_replace(array(Registry::settings()['root'], '.php'), '', $this->fullError);

		// Remove HTML tags from log version
		$this->logError = str_replace(array('<b>', '</b>'), '', $this->fullError);

		return $this;
	}

	/**
	 * Display error message and stop execution
	 *
	 * Shows detailed error in development mode, generic message in production.
	 * Logs error to file and stops script execution.
	 *
	 * Safety:
	 *   - Checks if error template exists before including
	 *   - Falls back to plain HTML if template missing
	 *   - Prevents blank page if template is broken
	 *
	 * @return void
	 */
	public function errorShow()
	{
		//set HTTP 500 error code (works in both web and CLI/testing)
		http_response_code(500);
		
		// Build and log error message
		$this->buildMessage()->logMessage();

		// Check if this is a console request
		if (defined('ROLINE_INSTANCE')) {
			
			// Console request - output plain text error
			echo $this->showError;
			
		} 
		else {
			
			// Try to load error page template, fallback to plain HTML if missing
			if (file_exists($this->errorPage))
			{

				// Prepare error message for display
				$error = $this->showError;

				// Hide detailed errors in production
				if ($this->DEV === false)
				{
					$hideError = true;
				}		

				// Get site title
				$title = $this->siteTitle;

				include $this->errorPage;

			}
			else
			{
				// Fallback: display error without template
				echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';

				if ($this->DEV === false)
				{
					echo '<h1>An error occurred</h1>';
					echo '<p>The application encountered an error. Please contact the administrator.</p>';
				}
				else
				{
					echo '<h1>Error Handler Warning</h1>';
					echo '<p><strong>Error template missing:</strong> ' . htmlspecialchars($this->errorPage) . '</p>';
					echo '<hr><h2>Error Details:</h2>';
					echo '<pre>' . htmlspecialchars($error) . '</pre>';
				}

				echo '</body></html>';
			}	

			// Stop execution
			exit();
		}

	}
}
