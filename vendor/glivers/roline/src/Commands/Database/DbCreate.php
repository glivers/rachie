<?php namespace Roline\Commands\Database;

/**
 * DbCreate Command
 *
 * Creates the database specified in the configuration file.
 * Connects to MySQL server without selecting a database first, then creates it.
 *
 * Use Cases:
 *   - Initial project setup
 *   - After running db:drop to recreate the database
 *   - Setting up new environments (dev, staging, production)
 *
 * Usage:
 *   php roline db:create
 *   php roline db:create --if-not-exists
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Database
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\Model;
use Rackage\Registry;
use Roline\Output;

class DbCreate extends DatabaseCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Create the database';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax
     */
    public function usage()
    {
        return '[database] [--if-not-exists]';
    }

    /**
     * Display detailed help information
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Description:');
        Output::line('  Creates a database. Uses config/database.php if no name specified.');
        Output::line('  Connects to MySQL without selecting a database first.');
        Output::line();

        Output::info('Arguments:');
        Output::line('  [database]       Optional database name (defaults to config)');
        Output::line();

        Output::info('Options:');
        Output::line('  --if-not-exists  Only create if database does not exist (no error if exists)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline db:create                    # Create from config');
        Output::line('  php roline db:create myapp              # Create "myapp"');
        Output::line('  php roline db:create myapp --if-not-exists');
        Output::line();

        Output::info('Use Cases:');
        Output::line('  - Initial project setup');
        Output::line('  - After running db:drop to recreate');
        Output::line('  - Setting up new environments');
        Output::line();
    }

    /**
     * Execute database creation
     *
     * Connects to MySQL server without selecting a database, then creates
     * the database specified in configuration.
     *
     * @param array $arguments Command arguments
     * @return void Exits with status 0 on success, 1 on failure
     */
    public function execute($arguments)
    {
        try {
            // Get database configuration
            $dbConfig = Registry::dbConfig();
            $driver = $dbConfig['default'] ?? 'mysql';
            $config = $dbConfig[$driver] ?? [];

            $host = $config['host'] ?? 'localhost';
            $username = $config['username'] ?? 'root';
            $password = $config['password'] ?? '';
            $charset = $config['charset'] ?? 'utf8mb4';
            $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';
            $port = $config['port'] ?? 3306;

            // Check for --if-not-exists flag
            $ifNotExists = in_array('--if-not-exists', $arguments);

            // Get database name from argument or config
            $databaseName = null;
            foreach ($arguments as $arg) {
                if (strpos($arg, '--') !== 0) {
                    $databaseName = $arg;
                    break;
                }
            }

            // Fall back to config if no argument
            if (empty($databaseName)) {
                $databaseName = $config['database'] ?? '';
            }

            // Validate database name
            if (empty($databaseName)) {
                $this->error('No database name specified!');
                $this->line();
                $this->info('Usage: php roline db:create [database]');
                exit(1);
            }

            $this->line();
            $this->info("Creating database: {$databaseName}");
            $this->info("Charset: {$charset}");
            $this->info("Collation: {$collation}");
            $this->line();

            // Check if database already exists
            $result = Model::server()->sql("SHOW DATABASES LIKE '{$databaseName}'");
            $exists = $result->num_rows > 0;

            if ($exists) {
                if ($ifNotExists) {
                    $this->info("Database '{$databaseName}' already exists. Skipping.");
                    $this->line();
                    exit(0);
                } else {
                    $this->error("Database '{$databaseName}' already exists!");
                    $this->line();
                    $this->info("Use --if-not-exists to skip if already exists.");
                    $this->info("Use db:drop to drop the existing database first.");
                    $this->line();
                    exit(1);
                }
            }

            // Create the database
            Model::server()->sql("CREATE DATABASE `{$databaseName}` CHARACTER SET {$charset} COLLATE {$collation}");

            $this->success("Database '{$databaseName}' created successfully!");
            $this->line();
            $this->info("Next steps:");
            $this->line("  php roline migration:run     # Run migrations");
            $this->line("  php roline db:seed           # Seed data");
            $this->line();

        } catch (\Exception $e) {
            $this->line();
            $this->error('Database creation failed!');
            $this->line();
            $this->error('Error: ' . $e->getMessage());
            $this->line();
            exit(1);
        }
    }
}
