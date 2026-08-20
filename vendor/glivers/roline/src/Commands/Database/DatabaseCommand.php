<?php namespace Roline\Commands\Database;

/**
 * DatabaseCommand - Base Class for Database-Wide Operations
 *
 * Abstract base class providing shared functionality for all database-related CLI
 * commands in Roline. Handles database connection management, configuration access,
 * and common database-wide query operations like listing all tables.
 *
 * Architecture:
 *   - Template Method Pattern - Subclasses implement execute() for specific operations
 *   - Lazy Connection - PDO connection created only when needed
 *   - Configuration-Driven - Uses Registry to access database config
 *   - Multi-Driver Support - Designed to support MySQL, PostgreSQL, SQLite
 *
 * Distinction from TableCommand:
 *   - DatabaseCommand: Operations affecting entire database (drop all, export all, schema all)
 *   - TableCommand: Operations affecting single tables (create, update, delete, schema one)
 *
 * Common Database Operations Provided:
 *   - getDatabaseConfig()  - Retrieves active database configuration
 *   - getConnection()      - Creates PDO connection instance
 *   - getAllTables()       - Lists all tables in database
 *
 * Configuration Access:
 *   - Reads from Registry::dbConfig() (loaded from config/database.php)
 *   - Respects 'default' driver setting (mysql, postgresql, sqlite)
 *   - Provides fallback defaults if config missing
 *
 * Database Commands Hierarchy:
 *   DatabaseCommand (base)
 *   ├── DbSchema      - Display all tables and structures
 *   ├── DbExport      - Export entire database to SQL file
 *   ├── DbDrop        - Drop all tables (extremely destructive)
 *   └── DbSeed        - Populate database with test data
 *
 * Connection Management:
 *   - PDO instance created per-command execution
 *   - Connection not persisted (stateless commands)
 *   - Error handling with informative messages on connection failure
 *   - Uses configured charset for proper encoding
 *
 * Important Notes:
 *   - Commands operating on entire database are inherently more dangerous
 *   - Most implement multiple confirmation prompts before destructive actions
 *   - Always use through Roline CLI (not directly instantiated)
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Database
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Command;
use Rackage\Registry;

abstract class DatabaseCommand extends Command
{
    /**
     * Get database configuration
     *
     * Retrieves database configuration from Registry (loaded from config/database.php
     * during bootstrap). Returns configuration array for the default driver specified
     * in config (typically 'mysql').
     *
     * @return array Database configuration array (host, username, password, database, charset, etc.)
     */
    protected function getDatabaseConfig()
    {
        // Load database configuration from Registry
        $config = Registry::dbConfig();

        // Determine which driver is active (default to mysql)
        $default = $config['default'] ?? 'mysql';

        // Return driver-specific configuration
        return $config[$default] ?? [];
    }

    /**
     * Get PDO connection
     *
     * Creates a PDO database connection instance using configuration from Registry.
     * Currently supports MySQL with DSN format. Connection is not cached - each call
     * creates a new connection instance.
     *
     * @return \PDO PDO connection instance
     * @throws \PDOException If connection fails (caught and displayed with error message)
     */
    protected function getConnection()
    {
        // Get database configuration array
        $config = $this->getDatabaseConfig();

        // Build MySQL DSN connection string
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";

        try
        {
            // Create PDO connection with credentials
            return new \PDO($dsn, $config['username'], $config['password']);
        }
        catch (\PDOException $e)
        {
            // Connection failed - display error and exit
            $this->error("Database connection failed: {$e->getMessage()}");
            exit(1);
        }
    }

    /**
     * Get all table names
     *
     * Queries database to retrieve list of all table names. Uses SHOW TABLES query
     * for MySQL databases. Returns array of table names suitable for iteration.
     *
     * @return array Array of table names (strings)
     */
    protected function getAllTables()
    {
        // Get PDO connection instance
        $pdo = $this->getConnection();

        // Execute SHOW TABLES query (MySQL)
        $stmt = $pdo->query("SHOW TABLES");

        // Return first column of results (table names)
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
