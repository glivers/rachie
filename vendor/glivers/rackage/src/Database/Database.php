<?php namespace Rackage\Database;

/**
 * Database Connection Manager
 *
 * Handles database driver initialization and configuration.
 * This class centralizes database connection management for all supported drivers.
 *
 * Responsibilities:
 *   - Store database type and connection parameters
 *   - Initialize appropriate database driver (MySQL, PostgreSQL, SQLite)
 *   - Validate database type configuration
 *   - Throw exceptions for invalid configurations
 *
 * Supported Drivers:
 *   - mysql: MySQL database driver (Rackage\Database\MySQL\MySQL)
 *   - postgresql: PostgreSQL driver (future)
 *   - sqlite: SQLite driver (future)
 *
 * Usage:
 *   $db = new Database('mysql', $options);
 *   $connection = $db->initialize();
 *
 * Architecture:
 *   This class is part of Rackage (the engine) and is updated via Composer.
 *   Previously split into BaseDb (class) and DbImplement (trait), now unified
 *   for simplicity since the trait was only used in one place.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Database
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

use Rackage\Database\MySQL\MySQL;

class Database
{

	/**
	 * Database driver type
	 * @var string
	 */
	protected $type;

	/**
	 * Database connection parameters
	 * @var array
	 */
	protected $options;

	/**
	 * Constructor - Initialize database configuration
	 *
	 * Sets up the database type and connection parameters for later initialization.
	 *
	 * @param string $name Database driver type (e.g., 'mysql', 'postgresql', 'sqlite')
	 * @param array $options Connection parameters (host, username, password, database, etc.)
	 * @return void
	 */
	public function __construct($name, $options)
	{
		// Set database driver type
		$this->type = $name;

		// Set connection options
		$this->options = $options;
	}

	/**
	 * Initialize database connection
	 *
	 * Creates and returns the appropriate database driver instance based on
	 * the configured database type.
	 *
	 * Examples:
	 *   $db = new Database('mysql', [
	 *       'host' => 'localhost',
	 *       'username' => 'root',
	 *       'password' => '',
	 *       'database' => 'myapp',
	 *   ]);
	 *   $connection = $db->initialize();
	 *
	 * @return object Database driver instance (e.g., MySQL, PostgreSQL, SQLite)
	 * @throws DatabaseException If database type is invalid or not supported
	 */
	public function initialize()
	{
		// Validate database type is set
		if (!$this->type)
		{
			throw new DatabaseException("Invalid database type provided");
		}

		// Initialize appropriate driver based on type
		switch ($this->type)
		{
			case 'mysql':
				return new MySQL($this->options);
				break;

			default:
				throw new DatabaseException("Unsupported database type: {$this->type}");
				break;
		}
	}
}
