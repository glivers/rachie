<?php namespace Rackage;

use Rackage\Path;
use Rackage\Registry;

/** 
 * Log Helper
 *
 * Provides application logging with multiple severity levels and structured
 * context data. Writes formatted log entries to files in vault/logs/ directory.
 *
 * This helper uses a static API for simple logging and supports instance methods
 * for custom log files via the to() method. All log operations are non-blocking
 * and never halt script execution, even if writes fail.
 *
 * Log Levels (in order of severity):
 *   - ERROR: Runtime errors that should be investigated immediately
 *   - WARNING: Exceptional occurrences that are not errors (deprecated APIs, poor use of API)
 *   - INFO: Interesting events (user login, SQL logs, significant actions)
 *   - DEBUG: Detailed debug information for development
 *
 * File Structure:
 *   All logs written to vault/logs/ directory (determined from error_log setting):
 *   - error.log: ERROR messages (Log::error()) - auto-created if doesn't exist
 *   - warning.log: WARNING messages (Log::warning()) - auto-created if doesn't exist
 *   - info.log: INFO messages (Log::info()) - auto-created if doesn't exist
 *   - debug.log: DEBUG messages (Log::debug()) - auto-created if doesn't exist
 *   - custom.log: Custom files via Log::to('custom.log') - auto-created in log directory
 *
 * Log Format:
 *   [2024-01-15 14:30:45] [ERROR] Database connection failed {"host":"localhost","error":"Access denied"}
 *   [timestamp] [LEVEL] message {context_json}
 *
 * Context Data:
 *   Second parameter accepts associative array for structured logging.
 *   Automatically JSON-encoded for easy parsing by log analyzers.
 *
 * Common Use Cases:
 *   - Error tracking: Log exceptions and runtime errors
 *   - Security auditing: Track login attempts, permission changes, suspicious activity
 *   - Performance monitoring: Log slow queries, high memory usage, timeouts
 *   - Debugging: Track variable values, execution flow, API responses
 *   - User activity: Track important user actions for analytics
 *
 * Static vs Instance:
 *   Static methods (Log::error(), Log::info()) write to default files.
 *   Instance methods (Log::to('file.log')->error()) write to custom files.
 *   Use static for 99% of cases, instance for special log separation.
 *
 * Performance:
 *   - Non-blocking file writes with exclusive locks (LOCK_EX)
 *   - Auto-creates log directory if missing
 *   - Silently fails on write errors (never crashes app)
 *
 * Usage Examples:
 *
 *   // Simple error logging
 *   Log::error('Payment gateway timeout');
 *   Log::warning('Cache miss rate exceeds threshold');
 *   Log::info('User registered');
 *   Log::debug('API response received');
 *
 *   // With context data
 *   Log::error('Database query failed', [
 *       'query' => $sql,
 *       'error' => $db->error,
 *       'user_id' => Session::get('user_id')
 *   ]);
 *
 *   Log::warning('Slow query detected', [
 *       'query' => $sql,
 *       'duration_ms' => $duration,
 *       'threshold_ms' => 1000
 *   ]);
 *
 *   Log::info('User login', [
 *       'user_id' => $user['id'],
 *       'email' => $user['email'],
 *       'ip' => Request::ip()
 *   ]);
 *
 *   // Custom log file
 *   Log::to('security.log')->warning('Failed login attempt', [
 *       'email' => Input::post('email'),
 *       'ip' => Request::ip(),
 *       'user_agent' => Request::agent()
 *   ]);
 *
 *   // Exception logging
 *   try {
 *       $result = riskyOperation();
 *   } catch (Exception $e) {
 *       Log::error('Operation failed: ' . $e->getMessage(), [
 *           'exception' => get_class($e),
 *           'file' => $e->getFile(),
 *           'line' => $e->getLine(),
 *           'trace' => $e->getTraceAsString()
 *       ]);
 *   }
 *
 *   // Debug with backtrace and variable snapshot
 *   Log::debug('Execution snapshot', [
 *       'trace' => debug_backtrace(),
 *       'vars' => get_defined_vars()
 *   ]);
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Log
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

class Log {

	/**
	 * Default log files for each level
	 *
	 * Maps log levels to their respective log filenames. Files are auto-created in
	 * the log directory if they don't exist. Directory determined from error_log setting.
	 *
	 * @var array
	 */
	private static $defaultFiles = [
		'debug' => 'debug.log',
		'info' => 'info.log',
		'warning' => 'warning.log',
		'error' => 'error.log',
	];

	/**
	 * Custom log file for instance-based logging
	 *
	 * Filename set via Log::to('filename.log'). File is auto-created in the log
	 * directory if it doesn't exist. Used for specialized logging (security, api, etc.).
	 *
	 * @var string|null
	 */
	private $customFile = null;

	/**
	 * Cached log directory path
	 *
	 * Computed once from config/settings.php error_log setting on first log call.
	 * Cached to avoid repeated Registry/Path lookups when logging in loops or multiple calls.
	 *
	 * @var string|null
	 */
	private static $directory = null;

	/**
	 * Private constructor to prevent instantiation
	 *
	 * This class uses static methods only - no instances should be created
	 * directly. Use Log::to() to get an instance for custom file logging.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning
	 *
	 * Ensures singleton-like behavior even though we don't use instances
	 * for static methods.
	 */
	private function __clone() {}

	// ===========================================================================
	// STATIC LOG METHODS (Default Files)
	// ===========================================================================

	/**
	 * Log error message
	 *
	 * Logs runtime errors that should be investigated immediately.
	 * Use for exceptions, database errors, failed API calls, file system errors.
	 *
	 * Process:
	 * 1. Format message with timestamp and ERROR level
	 * 2. Append context data as JSON if provided
	 * 3. Write to error.log file (auto-created if doesn't exist)
	 * 4. Auto-create log directory if missing
	 *
	 * When to Use:
	 *   - Exceptions and fatal errors
	 *   - Database connection failures
	 *   - File system errors (permissions, not found)
	 *   - Payment gateway failures
	 *   - External API errors
	 *
	 * Usage:
	 *   Log::error('Database connection failed');
	 *   Log::error('Payment processing failed', ['order_id' => 123, 'gateway' => 'stripe']);
	 *
	 *   try {
	 *       $db = Database::connect();
	 *   } catch (Exception $e) {
	 *       Log::error('Database error: ' . $e->getMessage(), [
	 *           'file' => $e->getFile(),
	 *           'line' => $e->getLine()
	 *       ]);
	 *   }
	 *
	 * @param string $message Error message
	 * @param array $context Additional context data
	 * @return void
	 */
	public static function error($message, $context = [])
	{
		self::write('error', $message, $context);
	}

	/**
	 * Log warning message
	 *
	 * Logs exceptional occurrences that are not errors but should be reviewed.
	 * Use for deprecated features, slow operations, unusual API usage, validation failures.
	 *
	 * Process:
	 * 1. Format message with timestamp and WARNING level
	 * 2. Append context data as JSON if provided
	 * 3. Write to warning.log file (auto-created if doesn't exist)
	 * 4. Auto-create log directory if missing
	 *
	 * When to Use:
	 *   - Deprecated API usage
	 *   - Slow database queries (above threshold)
	 *   - High memory usage
	 *   - Failed login attempts (security)
	 *   - Cache misses above threshold
	 *   - Unusual API parameters
	 *
	 * Usage:
	 *   Log::warning('Slow query detected', ['duration_ms' => 2500, 'query' => $sql]);
	 *   Log::warning('Cache miss rate high', ['rate' => 0.85, 'threshold' => 0.7]);
	 *   Log::warning('Deprecated method called', ['method' => 'oldFunction', 'use_instead' => 'newFunction']);
	 *
	 * @param string $message Warning message
	 * @param array $context Additional context data
	 * @return void
	 */
	public static function warning($message, $context = [])
	{
		self::write('warning', $message, $context);
	}

	/**
	 * Log info message
	 *
	 * Logs interesting events and significant user actions.
	 * Use for user activity tracking, important state changes, scheduled tasks.
	 *
	 * Process:
	 * 1. Format message with timestamp and INFO level
	 * 2. Append context data as JSON if provided
	 * 3. Write to info.log file (auto-created if doesn't exist)
	 * 4. Auto-create log directory if missing
	 *
	 * When to Use:
	 *   - User login/logout
	 *   - User registration
	 *   - Password changes
	 *   - Important CRUD operations
	 *   - Scheduled task execution
	 *   - Email sent successfully
	 *   - Payment completed
	 *
	 * Usage:
	 *   Log::info('User registered', ['email' => $email, 'ip' => Request::ip()]);
	 *   Log::info('Email sent', ['to' => $recipient, 'subject' => $subject]);
	 *   Log::info('Scheduled task completed', ['task' => 'backup', 'duration' => $seconds]);
	 *
	 * @param string $message Info message
	 * @param array $context Additional context data
	 * @return void
	 */
	public static function info($message, $context = [])
	{
		self::write('info', $message, $context);
	}

	/**
	 * Log debug message
	 *
	 * Logs detailed debug information for development and troubleshooting.
	 * Use for variable dumps, execution flow, API responses, query details.
	 *
	 * Process:
	 * 1. Format message with timestamp and DEBUG level
	 * 2. Append context data as JSON if provided
	 * 3. Write to debug.log file (auto-created if doesn't exist)
	 * 4. Auto-create log directory if missing
	 *
	 * When to Use:
	 *   - Variable values during debugging
	 *   - Execution flow tracking
	 *   - API request/response data
	 *   - Query execution details
	 *   - Cache hit/miss details
	 *   - Session data inspection
	 *
	 * Usage:
	 *   Log::debug('API response received', ['status' => 200, 'body' => $response]);
	 *   Log::debug('Query executed', ['sql' => $sql, 'params' => $params, 'rows' => $count]);
	 *   Log::debug('Execution snapshot', ['trace' => debug_backtrace(), 'vars' => get_defined_vars()]);
	 *
	 * @param string $message Debug message
	 * @param array $context Additional context data
	 * @return void
	 */
	public static function debug($message, $context = [])
	{
		self::write('debug', $message, $context);
	}

	// ===========================================================================
	// INSTANCE METHODS (Custom Files)
	// ===========================================================================

	/**
	 * Create instance for custom log file
	 *
	 * Returns a Log instance configured to write to a custom file instead
	 * of the default log files. Useful for separating logs by purpose
	 * (security, cron jobs, integrations, etc.).
	 *
	 * Process:
	 * 1. Create new Log instance
	 * 2. Set custom file name
	 * 3. Return instance for chaining
	 *
	 * File Location:
	 *   All custom files written to vault/logs/ directory.
	 *   Provide just the filename, not full path.
	 *
	 * Common Use Cases:
	 *   - Security logs: Failed logins, permission changes, suspicious activity
	 *   - Integration logs: External API calls, webhooks, third-party services
	 *   - Scheduled task logs: Cron jobs, background workers
	 *   - Feature-specific logs: Payment processing, email delivery
	 *
	 * Usage:
	 *   Log::to('security.log')->error('Brute force detected', ['ip' => $ip, 'attempts' => 10]);
	 *   Log::to('cron.log')->info('Backup completed', ['size_mb' => $size, 'duration' => $seconds]);
	 *   Log::to('stripe.log')->warning('Webhook validation failed', ['signature' => $sig]);
	 *
	 * @param string $filename Log filename (stored in vault/logs/)
	 * @return Log Instance for chaining
	 */
	// public static function to($filename)
	// {
	// 	$instance = new self();
	// 	$instance->customFile = $filename;
	// 	return $instance;
	// }

	// /**
	//  * Instance: Log error to custom file
	//  *
	//  * Same as static error() but writes to custom file set via to().
	//  *
	//  * @param string $message Error message
	//  * @param array $context Additional context data
	//  * @return void
	//  */
	// public function error($message, $context = [])
	// {
	// 	$this->writeInstance('error', $message, $context);
	// }

	// /**
	//  * Instance: Log warning to custom file
	//  *
	//  * Same as static warning() but writes to custom file set via to().
	//  *
	//  * @param string $message Warning message
	//  * @param array $context Additional context data
	//  * @return void
	//  */
	// public function warning($message, $context = [])
	// {
	// 	$this->writeInstance('warning', $message, $context);
	// }

	// /**
	//  * Instance: Log info to custom file
	//  *
	//  * Same as static info() but writes to custom file set via to().
	//  *
	//  * @param string $message Info message
	//  * @param array $context Additional context data
	//  * @return void
	//  */
	// public function info($message, $context = [])
	// {
	// 	$this->writeInstance('info', $message, $context);
	// }

	// /**
	//  * Instance: Log debug to custom file
	//  *
	//  * Same as static debug() but writes to custom file set via to().
	//  *
	//  * @param string $message Debug message
	//  * @param array $context Additional context data
	//  * @return void
	//  */
	// public function debug($message, $context = [])
	// {
	// 	$this->writeInstance('debug', $message, $context);
	// }

	// ===========================================================================
	// INTERNAL HELPERS
	// ===========================================================================

	/**
	 * Write log entry (static method)
	 *
	 * Internal method that handles actual log writing for static log methods.
	 * Formats message and writes to appropriate file based on level.
	 *
	 * Process:
	 * 1. Build log entry: [timestamp] [LEVEL] message {context}
	 * 2. Determine target file from level
	 * 3. Get absolute filepath (auto-creates directory if missing)
	 * 4. Write to file with exclusive lock
	 * 5. Silently fail on write errors (never crash app)
	 *
	 * Format:
	 *   [2024-01-15 14:30:45] [ERROR] Database connection failed {"host":"localhost"}
	 *
	 * @param string $level Log level (debug, info, warning, error)
	 * @param string $message Log message
	 * @param array $context Additional context data
	 * @return void
	 */
	private static function write($level, $message, $context = [])
	{
		// Build log entry
		$entry = self::formatEntry($level, $message, $context);

		// Determine file
		$filename = self::$defaultFiles[$level] ?? 'error.log';
		$filepath = self::getLogPath($filename);

		// Write to file
		self::writeToFile($filepath, $entry);
	}

	/**
	 * Write log entry (instance method)
	 *
	 * Internal method for instance-based logging to custom files.
	 * Same as write() but uses custom file instead of default.
	 *
	 * @param string $level Log level
	 * @param string $message Log message
	 * @param array $context Additional context data
	 * @return void
	 */
	private function writeInstance($level, $message, $context = [])
	{
		// Build log entry
		$entry = self::formatEntry($level, $message, $context);

		// Use custom file
		$filepath = self::getLogPath($this->customFile);

		// Write to file
		self::writeToFile($filepath, $entry);
	}

	/**
	 * Format log entry
	 *
	 * Builds formatted log string with timestamp, level, message, and context.
	 *
	 * Format:
	 *   [2024-01-15 14:30:45] [ERROR] message
	 *   [2024-01-15 14:30:45] [ERROR] message {"key":"value"}
	 *
	 * Context Encoding:
	 *   - Empty array: not appended
	 *   - Non-empty: JSON-encoded and appended
	 *   - Encoding failure: appended as (context encoding failed)
	 *
	 * @param string $level Log level
	 * @param string $message Log message
	 * @param array $context Context data
	 * @return string Formatted log entry
	 */
	private static function formatEntry($level, $message, $context = [])
	{
		// Build timestamp
		$timestamp = date('Y-m-d H:i:s');

		// Build base entry
		$entry = "[{$timestamp}] [" . strtoupper($level) . "] {$message}";

		// Append context if provided
		if (!empty($context)) {
			
			$contextJson = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			
			if ($contextJson !== false) $entry .= " {$contextJson}";
			else $entry .= " (context encoding failed)";
		}

		return $entry;
	}

	/**
	 * Get full log file path
	 *
	 * Extracts log directory from error_log setting on first call, then caches it.
	 * Appends filename to cached directory path for subsequent calls.
	 *
	 * @param string $filename Log filename
	 * @return string Absolute path to log file
	 */
	private static function getLogPath($filename)
	{
		// Compute directory once on first call
		if (self::$directory === null) {
			$errorLog = Registry::settings()['error_log']; // e.g., 'vault/logs/error.log'
			$logDir = dirname($errorLog); // e.g., 'vault/logs'

			if ($logDir === '.') {
				// File in root directory (no subdirectory)
				self::$directory = Path::base();
			} else {
				// File in subdirectory
				self::$directory = Path::base() . DIRECTORY_SEPARATOR . $logDir;
			}
		}

		return self::$directory . DIRECTORY_SEPARATOR . $filename;
	}

	/**
	 * Write entry to log file
	 *
	 * Writes log entry to file with exclusive lock to prevent corruption.
	 * Auto-creates log directory if it doesn't exist. Silently fails on
	 * write errors to never crash the application.
	 *
	 * Process:
	 * 1. Get directory from filepath
	 * 2. Create directory if missing (with 0755 permissions)
	 * 3. Open file in append mode
	 * 4. Acquire exclusive lock (LOCK_EX)
	 * 5. Write entry with newline
	 * 6. Release lock and close file
	 * 7. Set file permissions to 0644 if new file
	 *
	 * File Locking:
	 *   Uses LOCK_EX to prevent multiple processes from writing simultaneously
	 *   and corrupting the log file.
	 *
	 * Error Handling:
	 *   All failures are silently ignored. Logging should never crash the app.
	 *
	 * @param string $filepath Absolute path to log file
	 * @param string $entry Formatted log entry
	 * @return void
	 */
	private static function writeToFile($filepath, $entry)
	{
		try {
			// Ensure log directory exists
			$logDir = dirname($filepath);
			if (!is_dir($logDir)) {
				@mkdir($logDir, 0755, true);
			}

			// Check if file exists (for permission setting later)
			$isNewFile = !file_exists($filepath);

			// Write to file with exclusive lock
			$handle = @fopen($filepath, 'a');
			if ($handle) {
				if (flock($handle, LOCK_EX)) {
					fwrite($handle, $entry . PHP_EOL);
					flock($handle, LOCK_UN);
				}
				fclose($handle);

				// Set permissions on new files
				if ($isNewFile) {
					@chmod($filepath, 0644);
				}
			}
		} catch (\Exception $e) {
			// Silently fail - logging should never crash the app
		}
	}

}
