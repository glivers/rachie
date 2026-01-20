<?php
/**
 * Database Configuration
 * 
 * Configure your database connections here.
 * Rachie supports multiple database drivers and connections.
 * 
 * Supported drivers: mysql, sqlite, postgresql
 * 
 * Quick Start:
 * 1. Set your 'default' driver (usually 'mysql')
 * 2. Fill in credentials for that driver
 * 3. Leave other drivers empty unless you need them
 * 
 * @category Configuration
 * @package  Rachie
 */

return array(

	// ===========================================================================
	// DEFAULT DATABASE DRIVER
	// ===========================================================================

	/**
	 * Default Database Connection
	 *
	 * The database driver to use when no specific connection is specified.
	 * This driver's configuration (below) will be used for all database operations.
	 *
	 * Options: 'mysql', 'sqlite', 'postgresql'
	 *
	 * Most common: 'mysql'
	 */
	'default' => 'mysql',

	// ===========================================================================
	// MIGRATIONS TRACKING TABLE
	// ===========================================================================

	/**
	 * Migrations Table Name
	 *
	 * The name of the table used to track which migrations have been run.
	 * This table is created automatically by Roline when you run migrations.
	 *
	 * Change this if 'migrations' conflicts with your application tables.
	 *
	 * Default: 'migrations'
	 */
	'migrations_table' => 'migrations',

	// ===========================================================================
	// MYSQL CONFIGURATION
	// ===========================================================================
	
	/**
	 * MySQL Database Settings
	 * 
	 * Standard MySQL/MariaDB connection settings.
	 * Most web hosting uses MySQL as the default database.
	 * 
	 * Common local settings:
	 *   host: 'localhost' or '127.0.0.1'
	 *   username: 'root' (local) or your hosting username
	 *   password: '' (local with XAMPP/MAMP) or your hosting password
	 *   port: 3306 (default MySQL port)
	 */
	'mysql' => array(		
		'host'     => 'localhost',           // Database server hostname or IP
		'username' => 'root',                // Database username
		'password' => '',                    // Database password
		'database' => 'ke_search',                    // Database name (e.g., 'my_app_db')
		'port'     => '3306',                // MySQL default port
		'charset'  => 'utf8mb4',             // Character encoding (utf8mb4 recommended for emoji support)
		'engine'   => 'InnoDB',              // Storage engine (InnoDB for transactions)
	),

	// ===========================================================================
	// SQLITE CONFIGURATION
	// ===========================================================================
	
	/**
	 * SQLite Database Settings
	 * 
	 * Lightweight file-based database. Perfect for:
	 *   - Small applications
	 *   - Development/testing
	 *   - Embedded databases
	 *   - No server setup required
	 * 
	 * For SQLite, only 'database' is required (path to .sqlite file)
	 * Example: 'database' => 'storage/database/app.sqlite'
	 */
	'sqlite' => array(
		'database' => '',                    // Path to SQLite file (e.g., 'storage/app.sqlite')
		'charset'  => 'utf8',                // Character encoding
		// Note: host, username, password, port, engine are not used by SQLite
	),

	// ===========================================================================
	// POSTGRESQL CONFIGURATION
	// ===========================================================================
	
	/**
	 * PostgreSQL Database Settings
	 * 
	 * Advanced open-source database with powerful features.
	 * Often used for:
	 *   - Large-scale applications
	 *   - Complex queries
	 *   - Advanced data types
	 *   - GIS applications (with PostGIS)
	 * 
	 * Default PostgreSQL port: 5432 (not 3306)
	 */
	'postgresql' => array(
		'host'     => 'localhost',           // Database server hostname
		'username' => 'postgres',            // Database username (often 'postgres')
		'password' => '',                    // Database password
		'database' => '',                    // Database name
		'port'     => '5432',                // PostgreSQL default port
		'charset'  => 'utf8',                // Character encoding
	),

	// ===========================================================================
	// NAMED CONNECTIONS (Advanced)
	// ===========================================================================

	/**
	 * Named Database Connections
	 *
	 * Define additional database connections for advanced use cases:
	 *   - Backup databases
	 *   - Read replicas (load balancing)
	 *   - Analytics databases (separate server)
	 *   - Archive databases (old data)
	 *   - Migration sources (old database to new)
	 *
	 * Each named connection MUST include a 'driver' key specifying which
	 * database driver to use ('mysql', 'sqlite', or 'postgresql').
	 *
	 * Usage in code:
	 *   UserModel::using('backup')->where('id', 1)->all();
	 *   OrderModel::using('analytics')->sum('total');
	 *   QueueModel::using('sqlite')->where('key', $key)->first();
	 *
	 * Note: You can also use Model::using('sqlite') or Model::using('postgresql')
	 * to connect to those drivers without creating a named connection. Named
	 * connections are useful when you need multiple connections of the same
	 * driver type (e.g., multiple MySQL servers).
	 *
	 * The 'default' driver does not need using() - just use Model methods directly.
	 *
	 * Example configurations (uncomment and modify as needed):
	 */

	// Backup MySQL database (separate server)
	// 'backup' => array(
	// 	'driver'   => 'mysql',               // REQUIRED: Which driver to use
	// 	'host'     => 'backup.server.com',   // Backup server hostname
	// 	'username' => 'backup_user',         // Database username
	// 	'password' => 'backup_pass',         // Database password
	// 	'database' => 'backup_db',           // Database name
	// 	'port'     => '3306',                // MySQL default port
	// 	'charset'  => 'utf8mb4',             // Character encoding
	// 	'engine'   => 'InnoDB',              // Storage engine
	// ),

	// Read replica for load balancing
	// 'read-replica-1' => array(
	// 	'driver'   => 'mysql',
	// 	'host'     => 'read1.db.com',
	// 	'username' => 'replica_user',
	// 	'password' => 'replica_pass',
	// 	'database' => 'myapp_db',
	// 	'port'     => '3306',
	// 	'charset'  => 'utf8mb4',
	// 	'engine'   => 'InnoDB',
	// ),

	// SQLite cache (if you need a named connection)
	// 'cache' => array(
	// 	'driver'   => 'sqlite',
	// 	'database' => 'storage/sqlite/cache.db',
	// 	'charset'  => 'utf8',
	// ),

);

/**
 * SECURITY NOTES:
 * 
 * 1. NEVER commit passwords to version control
 *    - Use environment variables in production
 *    - Add database.php to .gitignore
 *    - Use database.php.example for version control
 * 
 * 2. Use strong passwords in production
 *    - Avoid 'root' user in production
 *    - Create dedicated database users with limited permissions
 * 
 * 3. Database user should only have necessary privileges
 *    - SELECT, INSERT, UPDATE, DELETE for application
 *    - No DROP, CREATE unless necessary
 * 
 */