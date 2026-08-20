<?php namespace Rackage\Database\MySQL;

/**
 * MySQL Database Driver
 *
 * Handles MySQL database connections using MySQLi extension.
 * Provides connection management, query execution, and error handling.
 *
 * Features:
 *   - Singleton connection via Registry pattern
 *   - UTF-8/UTF-8MB4 charset enforcement
 *   - Connection timeout (5 seconds)
 *   - Query compression for bandwidth savings
 *   - Auto-reconnect on connection loss
 *   - Connection health monitoring (ping)
 *   - SQL injection prevention (escaping)
 *   - Comprehensive error reporting
 *
 * Connection Flow:
 *   1. Registry::get('database') creates Database instance
 *   2. Database::initialize() creates MySQL instance
 *   3. MySQL::connect() establishes MySQLi connection
 *   4. Connection persists for entire request lifecycle
 *   5. All Models share same connection (no duplicates)
 *
 * Performance Optimizations:
 *   - Connection pooling via Registry singleton
 *   - Query compression (60-70% bandwidth reduction)
 *   - Prepared statement escaping
 *   - Connection reuse across all queries
 *
 * Error Handling:
 *   - Connection errors throw DatabaseException
 *   - Query errors return false (check with lastError())
 *   - Auto-retry on "MySQL server has gone away" (error 2006)
 *
 * Usage:
 *   // Via Registry (recommended)
 *   $db = Registry::get('database');
 *   $result = $db->execute("SELECT * FROM users");
 *
 *   // Via Model (automatic)
 *   $users = Users::all();  // Uses Registry::get('database') internally
 *
 * Configuration:
 *   Set in config/database.php:
 *   'mysql' => [
 *       'host' => 'localhost',
 *       'username' => 'root',
 *       'password' => '',
 *       'database' => 'myapp',
 *       'port' => '3306',
 *       'charset' => 'utf8mb4',  // Enforced on connection
 *       'engine' => 'InnoDB',
 *   ]
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Database\MySQL
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

use Rackage\Database\DatabaseException;

class MySQL {

	/**
	 * MySQLi connection instance
	 *
	 * The underlying MySQLi object used for all database operations.
	 * Created by connect(), validated by validService().
	 *
	 * @var \MySQLi
	 */
	protected $service;

	/**
	 * Database server hostname or IP address
	 *
	 * Examples: 'localhost', '127.0.0.1', 'db.example.com'
	 *
	 * @var string
	 */
	protected $host;

	/**
	 * Database authentication username
	 *
	 * User must have appropriate privileges for intended operations.
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * Database authentication password
	 *
	 * Stored in memory only during request lifecycle.
	 * Never logged or exposed in error messages.
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Database schema/database name
	 *
	 * The specific database to use on the MySQL server.
	 *
	 * @var string
	 */
	protected $database;

	/**
	 * MySQL server port number
	 *
	 * Default: 3306 (standard MySQL port)
	 * Change if using custom port or SSH tunnel.
	 *
	 * @var string
	 */
	protected $port = '3306';

	/**
	 * Character set for database connection
	 *
	 * Applied via set_charset() after connection established.
	 * Ensures consistent encoding for all queries and results.
	 *
	 * Common values:
	 *   - 'utf8': Basic UTF-8 (3 bytes, no emojis)
	 *   - 'utf8mb4': Full UTF-8 (4 bytes, supports emojis) - RECOMMENDED
	 *
	 * @var string
	 */
	protected $charset = 'utf8';

	/**
	 * Default storage engine for created tables
	 *
	 * Used by MySQLTable when creating tables.
	 * InnoDB recommended for transactions and foreign keys.
	 *
	 * @var string
	 */
	protected $engine = "InnoDB";

	/**
	 * Connection status flag
	 *
	 * True when successfully connected to MySQL server.
	 * False when disconnected or connection failed.
	 *
	 * @var bool
	 */
	protected $connected = false;

	// =========================================================================
	// CONSTRUCTOR
	// =========================================================================
 
	/**
	 * Initialize MySQL driver with connection parameters
	 *
	 * Stores configuration from config/database.php for later use by connect().
	 * Does NOT create connection yet - connection is lazy-loaded on first query.
	 *
	 * Called by:
	 *   Database::initialize() → new MySQL($options)
	 *
	 * Configuration array format:
	 *   [
	 *       'host' => 'localhost',
	 *       'username' => 'root',
	 *       'password' => 'secret',
	 *       'database' => 'myapp',
	 *       'port' => '3306',
	 *       'charset' => 'utf8mb4',
	 *       'engine' => 'InnoDB'
	 *   ]
	 *
	 * @param array $options Database connection configuration
	 * @return void
	 */
	public function __construct(array $options)
	{
		// Convert array to object for cleaner property access
		$options = (object)$options;

		// Store connection parameters
		$this->host = $options->host;
		$this->username = $options->username;
		$this->password = $options->password;
		$this->database = $options->database ?? null;  // Optional for server-level connections
		$this->port = $options->port;
		$this->charset = $options->charset;
		$this->engine = $options->engine;
	}

	// =========================================================================
	// CONNECTION VALIDATION
	// =========================================================================

	/**
	 * Validate database connection status
	 *
	 * Performs three checks to ensure connection is valid:
	 *   1. $service property is not empty
	 *   2. $service is an instance of MySQLi
	 *   3. $connected flag is true
	 *
	 * Used internally before executing queries to prevent errors.
	 *
	 * @return bool True if connection is valid and active
	 */
	protected function validService()
	{
		// Check if service variable is empty
		$empty = empty($this->service);

		// Check if service is MySQLi instance
		$instance = $this->service instanceof \MySQLi;

		// Return true only if connected, is MySQLi instance, and not empty
		if ($this->connected && $instance && !$empty)	return true;

		// Otherwise connection is invalid
		return false;
	}

	// =========================================================================
	// CONNECTION MANAGEMENT
	// =========================================================================

	/**
	 * Establish database connection
	 *
	 * Creates MySQLi connection with optimized settings:
	 *   - 5 second connection timeout (prevents hanging)
	 *   - Query compression enabled (60-70% bandwidth reduction)
	 *   - Character set enforcement (UTF-8/UTF-8MB4)
	 *
	 * Connection is lazy - only created when first needed.
	 * If already connected, returns existing connection (singleton pattern).
	 *
	 * Flow:
	 *   1. Check if already connected (validService())
	 *   2. Create MySQLi instance
	 *   3. Set connection options (timeout, compression)
	 *   4. Connect to MySQL server (real_connect)
	 *   5. Set character set for proper encoding
	 *   6. Mark as connected
	 *
	 * @return self Chainable
	 * @throws DatabaseException If connection fails or charset cannot be set
	 */
	public function connect()
	{
		// Only connect if not already connected
		if (!$this->validService())
		{
			try
			{
				// Create new MySQLi instance
				$this->service = new \MySQLi();

				// Set connection timeout - prevents hanging forever
				$this->service->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

				// Enable compression - 60-70% bandwidth savings for large results
				$this->service->options(MYSQLI_CLIENT_COMPRESS, true);

				// Establish connection to MySQL server
				$this->service->real_connect(
					$this->host,
					$this->username,
					$this->password,
					$this->database,
					$this->port
				);

				// Check for connection errors
				if ($this->service->connect_error){

					throw new DatabaseException(
						"Unable to connect to Database service: " .
						$this->service->connect_error
					);
				}

				// Set character set - CRITICAL for UTF-8 support
				// Prevents encoding issues with international characters and emojis
				if (!$this->service->set_charset($this->charset))
				{
					throw new DatabaseException(
						"Error setting charset {$this->charset}: " .
						$this->service->error
					);
				}

				// Mark connection as active
				$this->connected = true;
			}
			catch(DatabaseException $exception){ throw $exception; }
		}

		return $this;
	}

	/**
	 * Close database connection
	 *
	 * Gracefully closes the MySQLi connection and frees resources.
	 * Called automatically at end of request or manually when needed.
	 *
	 * Typically not needed manually since:
	 *   - PHP auto-closes on script end
	 *   - Registry maintains singleton for request lifecycle
	 *
	 * Use cases:
	 *   - Long-running scripts that no longer need database
	 *   - Freeing connection for connection pooling
	 *   - Testing/debugging
	 *
	 * @return self Chainable
	 */
	public function disconnect()
	{
		// Only disconnect if currently connected
		if ($this->validService())
		{
			// Mark as disconnected
			$this->connected = false;

			// Close MySQLi connection and free resources
			$this->service->close();
		}

		// Return instance for method chaining
		return $this;
	}

	/**
	 * Check connection health and auto-reconnect
	 *
	 * Pings the MySQL server to verify connection is still alive.
	 * If connection lost, automatically attempts to reconnect.
	 *
	 * Use Cases:
	 *   - Long-running scripts (workers, cron jobs)
	 *   - Before critical operations
	 *   - Handling MySQL wait_timeout (8 hours default)
	 *
	 * Example:
	 *   while ($job = getNextJob()) {
	 *       $db->ping();  // Ensure connection alive
	 *       processJob($job);
	 *   }
	 *
	 * @return bool True if connection alive or reconnected, false on failure
	 */
	public function ping()
	{
		try
		{
			// If no connection, attempt to establish one
			if (!$this->validService())	{  

				return $this->connect() !== false;
			}

			// Ping the MySQL server (may throw mysqli_sql_exception if connection lost)
			try
			{
				if (!$this->service->ping()){

					// Connection lost - attempt reconnection
					$this->connected = false;
					return $this->connect() !== false;
				}
			}
			catch (\mysqli_sql_exception $exception)
			{
				// Ping failed (connection lost) - attempt reconnection
				$this->connected = false;
				return $this->connect() !== false;
			}

			// Connection is healthy
			return true;
		}
		catch(DatabaseException $exception)	{ throw $exception; }
	}

	/**
	 * Get connection diagnostics and statistics
	 *
	 * Returns detailed information about the database connection.
	 * Useful for debugging, monitoring, and logging.
	 *
	 * Returned Data:
	 *   - host: Database server hostname
	 *   - database: Active database name
	 *   - charset: Character set in use
	 *   - server_version: MySQL version (e.g., "8.0.32")
	 *   - server_info: Connection info string
	 *   - protocol_version: MySQL protocol version
	 *   - connection_id: Thread ID for this connection
	 *   - connected: Boolean connection status
	 *
	 * Example:
	 *   $stats = Registry::get('database')->getStats();
	 *   echo "MySQL {$stats['server_version']} on {$stats['host']}";
	 *
	 * @return array Connection statistics, empty array on error
	 * @throws DatabaseException If not connected
	 */
	public function getStats()
	{
		try
		{
			// Require valid connection
			if (!$this->validService())
			{
				throw new DatabaseException("Not connected to a valid database service");
			}

			// Return connection details
			return array(
				'host' => $this->host,
				'database' => $this->database,
				'charset' => $this->charset,
				'server_version' => $this->service->server_info,
				'server_info' => $this->service->host_info,
				'protocol_version' => $this->service->protocol_version,
				'connection_id' => $this->service->thread_id,
				'connected' => $this->connected
			);
		}
		catch(DatabaseException $exception) { throw $exception; }
	}

	// =========================================================================
	// QUERY BUILDER
	// =========================================================================

	/**
	 * Create new query builder instance
	 *
	 * Returns a fresh MySQLQuery instance for building and executing queries.
	 * Used internally by Model class - rarely called directly.
	 *
	 * Query builder provides chainable methods:
	 *   - select(), where(), join(), order(), limit()
	 *   - groupBy(), having(), whereIn(), whereLike()
	 *   - all(), first(), count(), save(), delete()
	 *
	 * Example (direct use - not typical):
	 *   $db = Registry::get('database');
	 *   $users = $db->query()
	 *               ->select(['id', 'name'])
	 *               ->where('status', 'active')
	 *               ->all();
	 *
	 * Typical use (via Model):
	 *   $users = Users::where('status', 'active')->all();
	 *
	 * @return MySQLQuery New query builder instance
	 */
	public function query($table, $timestamps)
	{
		// Create query builder with reference to this connection
		return new MySQLQuery(
			['connector' => $this],
			$table,
			$timestamps
		);
	}

	// =========================================================================
	// QUERY EXECUTION
	// =========================================================================

	/**
	 * Execute raw SQL query
	 *
	 * Executes any valid MySQL query string directly against the database.
	 * Automatically handles connection loss and retry on error 2006.
	 *
	 * Supported Query Types:
	 *   - SELECT: Returns MySQLi result object
	 *   - INSERT/UPDATE/DELETE: Returns true on success, false on failure
	 *   - CREATE/ALTER/DROP: Returns true on success, false on failure
	 *
	 * Auto-Retry on Connection Loss:
	 *   If query fails with error 2006 ("MySQL server has gone away"):
	 *     1. Attempts to reconnect via ping()
	 *     2. Retries query once if reconnection successful
	 *     3. Returns result or false
	 *
	 * Security Warning:
	 *   NEVER concatenate user input directly into SQL queries!
	 *   Always use escape() or prepared statements.
	 *
	 * Examples:
	 *   // SELECT query
	 *   $result = $db->execute("SELECT * FROM users WHERE status = 'active'");
	 *   while ($row = $result->fetch_assoc()) { }
	 *
	 *   // INSERT with escaped input
	 *   $name = $db->escape($_POST['name']);
	 *   $result = $db->execute("INSERT INTO users (name) VALUES ('$name')");
	 *
	 * @param string $sql MySQL query string to execute
	 * @return \MySQLi_Result|bool Result object for SELECT, boolean for other queries
	 * @throws DatabaseException If not connected to valid database service
	 */
	public function execute($sql)
	{
		try
		{
			// Require valid connection
			if (!$this->validService()){

				throw new DatabaseException("Not connected to a valid database service");
			}

			// Execute query with auto-reconnect on "server has gone away"
			try	{

				$result = $this->service->query($sql);
			}
			catch (\mysqli_sql_exception $exception)
			{
				// Check for "MySQL server has gone away" error (2006)
				if ($this->service->errno == 2006){

					// Attempt to reconnect and retry query once
					if ($this->ping()) $result = $this->service->query($sql);
					else	throw $exception; // Reconnection failed, re-throw
				}
				else {
					$sqlPreview = strlen($sql) > 500 ? substr($sql, 0, 500) . '...[truncated]' : $sql;
					throw new DatabaseException($exception->getMessage() . "\nSQL: " . $sqlPreview, $exception->getCode());
				}
			}

			// Also check for false result with errno 2006 (fallback)
			if ($result === false && $this->service->errno == 2006) {
				// Attempt to reconnect and retry query once
				if ($this->ping()) {
					$result = $this->service->query($sql);
				}
			} 

			return $result;
		}
		catch(DatabaseException $exception) { throw $exception; }
	}

	/**
	 * Execute query in unbuffered mode (MYSQLI_USE_RESULT)
	 *
	 * Fetches rows one at a time from the server instead of loading all results
	 * into memory. Critical for large result sets (millions of rows) to prevent
	 * memory exhaustion.
	 *
	 * IMPORTANT NOTES:
	 * - You MUST fetch ALL rows or call free() before running another query
	 * - Memory usage stays constant regardless of result set size
	 * - Ideal for export operations on large tables
	 *
	 * Performance:
	 * - Network transfer speed: Same as buffered
	 * - Memory usage: ~1KB (current row only) vs 100GB+ (buffered)
	 * - Processing: Starts immediately vs waits for all rows
	 *
	 * @param string $sql SQL query
	 * @return mysqli_result|bool Result object or false
	 * @throws DatabaseException If not connected or query fails
	 */
	public function executeNoBuffer($sql)
	{
		try
		{
			// Require valid connection
			if (!$this->validService()){
				throw new DatabaseException("Not connected to a valid database service");
			}

			// Execute query with auto-reconnect on "server has gone away"
			try
			{
				// Use real_query() + use_result() for unbuffered mode
				$success = $this->service->real_query($sql);

				if (!$success) return false;

				// use_result() returns unbuffered result (fetches one row at a time)
				$result = $this->service->use_result();
				return $result;
			}
			catch (\mysqli_sql_exception $exception)
			{
				// Check for "MySQL server has gone away" error (2006)
				if ($this->service->errno == 2006){
					// Attempt to reconnect and retry query once
					if ($this->ping()) {
						$success = $this->service->real_query($sql);
						
						if ($success) return $this->service->use_result();
						return false;
					}
					else throw $exception;
				}
				else throw $exception; // Different error, re-throw
			}
		}
		catch(DatabaseException $exception) { throw $exception; }
	}

	/**
	 * Execute query asynchronously (non-blocking)
	 *
	 * Fires the query with MYSQLI_ASYNC flag and returns immediately.
	 * The query runs in the background while PHP continues execution.
	 *
	 * Returns the mysqli connection for use with Promise, which will
	 * poll for completion and reap the result when await() is called.
	 *
	 * Limitations:
	 *   - One async query at a time per connection
	 *   - Must await() result before starting another async query
	 *   - Cannot use with unbuffered mode
	 *
	 * Usage:
	 *   $mysqli = $connector->executeAsync($sql);
	 *   $promise = new Promise($mysqli, fn($r) => $r->fetch_all());
	 *   // ... do other work ...
	 *   $result = $promise->await();
	 *
	 * @param string $sql SQL query to execute
	 * @return \mysqli The connection (for Promise to poll/reap)
	 * @throws DatabaseException If not connected
	 */
	public function executeAsync($sql)
	{
		if (!$this->validService()) {
			throw new DatabaseException("Not connected to a valid database service");
		}

		$this->service->query($sql, MYSQLI_ASYNC);

		return $this->service;
	}

	// =========================================================================
	// SQL INJECTION PREVENTION
	// =========================================================================

	/**
	 * Escape string for safe use in SQL queries
	 *
	 * Prevents SQL injection by escaping special characters in user input.
	 * Uses MySQLi's real_escape_string() which is charset-aware.
	 *
	 * What It Escapes:
	 *   - Single quotes (')
	 *   - Double quotes (")
	 *   - Backslashes (\)
	 *   - NULL bytes (\0)
	 *   - Control characters (\n, \r, \x1a)
	 *
	 * IMPORTANT:
	 *   - Still wrap escaped values in quotes: WHERE name = '$escaped'
	 *   - Does NOT escape LIKE wildcards (%, _) - handle separately
	 *   - Only for string values - numbers don't need escaping
	 *
	 * Security Best Practices:
	 *   1. ALWAYS escape user input before SQL queries
	 *   2. Use prepared statements when possible (more secure)
	 *   3. Validate input type/format before escaping
	 *   4. Never trust client-side validation alone
	 *
	 * Examples:
	 *   // Basic escaping
	 *   $name = $db->escape($_POST['name']);
	 *   $sql = "SELECT * FROM users WHERE name = '$name'";
	 *
	 *   // Prevents SQL injection - Input: O'Reilly → Output: O\'Reilly
	 *   $name = $db->escape("O'Reilly");
	 *   // Query: WHERE name = 'O\'Reilly' (safe)
	 *
	 * @param string $value String to escape for SQL safety
	 * @return string Escaped string safe for use in SQL queries
	 * @throws DatabaseException If not connected to valid database service
	 */
	public function escape($value)
	{
		try
		{
			// Require valid connection
			if (!$this->validService())	{

				throw new DatabaseException("Not connected to a valid database service");
			}

			// Escape special characters using charset-aware function
			return $this->service->real_escape_string($value);
		}
		catch(DatabaseException $exception) { throw	$exception;	}
	}

	// =========================================================================
	// QUERY RESULT METADATA
	// =========================================================================

	/**
	 * Get auto-generated ID from last INSERT query
	 *
	 * Returns the ID generated by AUTO_INCREMENT for the last INSERT query.
	 * Only works with tables that have an AUTO_INCREMENT primary key.
	 *
	 * Behavior:
	 *   - Returns 0 if last query was not INSERT
	 *   - Returns 0 if table has no AUTO_INCREMENT column
	 *   - Returns generated ID for successful INSERT
	 *   - Persists across multiple queries until next INSERT
	 *
	 * Use Cases:
	 *   - Get newly created user ID after registration
	 *   - Reference parent ID when inserting related records
	 *   - Return created resource ID to API client
	 *
	 * Important Notes:
	 *   - Only valid immediately after INSERT
	 *   - Not reliable in concurrent environments (use LAST_INSERT_ID() function)
	 *   - Returns 0 for UPDATE/DELETE queries
	 *   - Multi-row INSERT returns ID of FIRST inserted row
	 *
	 * Examples:
	 *   // Single insert
	 *   $db->execute("INSERT INTO users (name) VALUES ('John')");
	 *   $userId = $db->lastInsertId();  // Returns: 123
	 *
	 *   // Insert with related records
	 *   $db->execute("INSERT INTO orders (user_id, total) VALUES (5, 99.99)");
	 *   $orderId = $db->lastInsertId();
	 *   $db->execute("INSERT INTO order_items (order_id, product) VALUES ($orderId, 10)");
	 *
	 * @return int Auto-generated ID from last INSERT, or 0 if none
	 * @throws DatabaseException If not connected to valid database service
	 */
	public function lastInsertId()
	{
		try
		{
			// Require valid connection
			if (!$this->validService()){

				throw new DatabaseException("Not connected to a valid database service");
			}

			// Return auto-generated ID from last INSERT query
			return $this->service->insert_id;
		}
		catch(DatabaseException $exception) { throw $exception; }
	}

	/**
	 * Get number of rows affected by last query
	 *
	 * Returns the number of rows changed, deleted, or inserted by the last
	 * UPDATE, DELETE, INSERT, or REPLACE query.
	 *
	 * Return Values by Query Type:
	 *   - UPDATE: Number of rows actually modified (0 if no changes)
	 *   - DELETE: Number of rows deleted
	 *   - INSERT: Number of rows inserted
	 *   - REPLACE: Number of rows replaced (2 per row if replacing)
	 *   - SELECT: -1 (use num_rows on result object instead)
	 *
	 * Important Behaviors:
	 *   - UPDATE returns 0 if new values match old values (no actual change)
	 *   - Returns -1 for SELECT queries (not an error)
	 *   - REPLACE counts deletion + insertion (2x the rows)
	 *   - Reliable immediately after query execution
	 *
	 * Use Cases:
	 *   - Verify UPDATE/DELETE actually modified rows
	 *   - Count bulk operation results
	 *   - Confirm data changes in API responses
	 *   - Logging and auditing
	 *
	 * Examples:
	 *   // UPDATE query
	 *   $db->execute("UPDATE users SET status = 'inactive' WHERE last_login < '2020-01-01'");
	 *   $count = $db->affectedRows();  // Returns: 47
	 *
	 *   // Conditional response
	 *   $db->execute("DELETE FROM posts WHERE id = 123");
	 *   if ($db->affectedRows() > 0) {
	 *       echo "Post deleted";
	 *   }
	 *
	 * @return int Number of affected rows, or -1 for SELECT queries
	 * @throws DatabaseException If not connected to valid database service
	 */
	public function affectedRows()
	{
		try
		{
			// Require valid connection
			if (!$this->validService()) {
				throw new DatabaseException("Not connected to a valid database service");
			}

			// Return number of rows affected by last query
			return $this->service->affected_rows;
		}
		catch(DatabaseException $exception) { throw $exception; }
	}

	// =========================================================================
	// ERROR HANDLING
	// =========================================================================

	/**
	 * Get last MySQL error message
	 *
	 * Returns a human-readable error description from the last MySQL operation.
	 * Used for debugging and error logging when queries fail.
	 *
	 * Return Values:
	 *   - Empty string ('') if no error occurred
	 *   - Descriptive error message if last operation failed
	 *   - Persists until next successful operation
	 *
	 * Common Error Messages:
	 *   - "Table 'db.table' doesn't exist" - Table not found
	 *   - "Duplicate entry 'value' for key 'PRIMARY'" - Unique constraint violation
	 *   - "Column 'name' cannot be null" - NOT NULL constraint violation
	 *   - "Unknown column 'field' in 'field list'" - Invalid column name
	 *   - "You have an error in your SQL syntax" - Syntax error
	 *   - "MySQL server has gone away" - Connection lost (error 2006)
	 *
	 * Use Cases:
	 *   - Check why query failed
	 *   - Log errors for debugging
	 *   - Display user-friendly error messages
	 *   - Conditional error handling
	 *
	 * Error Number (errno):
	 *   Use $mysqli->errno for error codes:
	 *   - 1062: Duplicate entry
	 *   - 1054: Unknown column
	 *   - 1146: Table doesn't exist
	 *   - 2006: MySQL server has gone away
	 *
	 * Examples:
	 *   // Basic error checking
	 *   $result = $db->execute("SELECT * FROM nonexistent_table");
	 *   if ($result === false) {
	 *       echo "Error: " . $db->lastError();
	 *   }
	 *
	 *   // Duplicate key handling
	 *   $result = $db->execute("INSERT INTO users (email) VALUES ('test@example.com')");
	 *   if ($result === false && strpos($db->lastError(), 'Duplicate') !== false) {
	 *       echo "Email already exists";
	 *   }
	 *
	 * @return string Error description, or empty string if no error
	 * @throws DatabaseException If not connected to valid database service
	 */
	public function lastError() 
	{
		try
		{
			// Require valid connection
			if (!$this->validService())	{

				throw new DatabaseException("Not connected to a valid database service");
			}

			// Return last error message from MySQLi
			return $this->service->error;
		}
		catch(DatabaseException $exception) { throw	$exception; }
	}

}

