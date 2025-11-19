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
		'database' => '',                    // Database name (e.g., 'my_app_db')
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