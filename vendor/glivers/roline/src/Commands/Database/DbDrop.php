<?php namespace Roline\Commands\Database;

/**
 * DbDrop Command
 *
 * EXTREMELY DESTRUCTIVE command that drops the entire database permanently.
 * Implements triple confirmation system with database name typing requirement to prevent
 * accidental execution. Use with extreme caution - this action cannot be undone.
 *
 * What Gets Dropped:
 *   - The entire DATABASE (not just tables)
 *   - ALL tables and their data
 *   - ALL views, procedures, functions
 *   - ALL users permissions on this database
 *   - The database itself is completely removed from MySQL
 *
 * Confirmation System (Triple Safety):
 *   1. First Confirmation:
 *      - Shows database name and table count
 *      - Asks "Are you ABSOLUTELY sure?"
 *      - Can cancel at this point
 *
 *   2. Second Confirmation (Type Database Name):
 *      - User must type exact database name
 *      - Prevents accidental wrong-database drops
 *      - Ensures user is paying attention
 *
 *   3. Third Confirmation:
 *      - Final "Drop database now?" question
 *      - Last chance to abort operation
 *
 * Use Cases:
 *   - Completely removing a database
 *   - Cleaning up abandoned projects
 *   - Starting completely fresh (must recreate database after)
 *   - NEVER use on production without extreme caution
 *
 * Important Warnings:
 *   - THIS IS PERMANENT - No undo, no rollback, no recovery
 *   - ALWAYS backup database before running (use db:export first)
 *   - You will need to recreate the database manually after
 *   - Application will fail to connect after database is dropped
 *
 * Usage:
 *   php roline db:drop
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
use Roline\Utils\SchemaReader;

class DbDrop extends DatabaseCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Drop the entire database';
    }

    /**
     * Get command usage syntax
     *
     * @return string Empty string (no arguments required)
     */
    public function usage()
    {
        return '[database]';
    }

    /**
     * Display detailed help information
     *
     * Shows extreme warnings about destructive nature, explains triple confirmation
     * system, and provides usage examples.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Description:');
        Output::line('  Drops the ENTIRE DATABASE. This is EXTREMELY DESTRUCTIVE!');
        Output::line('  The database must be recreated manually after this operation.');
        Output::line();

        Output::info('Arguments:');
        Output::line('  [database]  Optional database name (defaults to config)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline db:drop              # Drop database from config');
        Output::line('  php roline db:drop myapp        # Drop "myapp"');
        Output::line();

        Output::info('Warning:');
        Output::line('  - This will DROP THE ENTIRE DATABASE!');
        Output::line('  - All tables, views, procedures, and data will be lost!');
        Output::line('  - This action CANNOT be undone!');
        Output::line('  - Multiple confirmations required');
        Output::line();

        Output::info('See also:');
        Output::line('  db:drop-tables  - Drop all tables (keeps database)');
        Output::line('  db:empty        - Empty tables (TRUNCATE, keeps structure)');
        Output::line();
    }

    /**
     * Execute database drop operation
     *
     * Drops the entire database after triple confirmation (yes/no, type database
     * name, final yes/no).
     *
     * @param array $arguments Command arguments (none required)
     * @return void Exits with status 0 on cancel/success, 1 on failure
     */
    public function execute($arguments)
    {
        try {
            // Get database configuration
            $dbConfig = Registry::dbConfig();
            $driver = $dbConfig['default'] ?? 'mysql';
            $config = $dbConfig[$driver] ?? [];

            // Get database name from argument or config
            $databaseName = !empty($arguments[0]) ? $arguments[0] : ($config['database'] ?? '');

            if (empty($databaseName)) {
                $this->error('No database name specified!');
                $this->line();
                $this->info('Usage: php roline db:drop [database]');
                exit(1);
            }

            // Display extreme warning banner
            $this->line();
            $this->error('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
            $this->error('WARNING: This will DROP THE ENTIRE DATABASE!');
            $this->error('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
            $this->line();

            // Check if database exists
            $result = Model::server()->sql("SHOW DATABASES LIKE '{$databaseName}'");
            if ($result->num_rows === 0) {
                $this->error("Database '{$databaseName}' does not exist!");
                $this->line();
                exit(1);
            }

            // Get table count for display
            $result = Model::server()->sql("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$databaseName}'");
            $row = $result->fetch_assoc();
            $tableCount = (int) $row['cnt'];

            // Display database info
            $this->info("Database: {$databaseName}");
            $this->info("Tables: {$tableCount}");
            $this->line();

            // Emphasize what will happen
            $this->error('This will PERMANENTLY DELETE:');
            $this->error('  - The database itself');
            $this->error('  - ALL tables and their data');
            $this->error('  - ALL views, procedures, functions');
            $this->error('  - ALL stored routines');
            $this->line();
            $this->error('This action CANNOT be undone!');
            $this->error('You will need to recreate the database manually!');
            $this->line();

            // First confirmation - general agreement
            $confirmed1 = $this->confirm("Are you ABSOLUTELY sure you want to DROP database '{$databaseName}'?");

            if (!$confirmed1) {
                // User cancelled at first confirmation
                $this->info('Operation cancelled.');
                $this->line();
                exit(0);
            }

            // Second confirmation - require typing database name
            $this->line();
            $this->info("To confirm, please type the database name: {$databaseName}");
            $typed = $this->ask('Database name:');

            // Validate typed database name matches
            if ($typed !== $databaseName) {
                $this->error('Database name does not match. Operation cancelled.');
                $this->line();
                exit(0);
            }

            // Third confirmation - final check
            $this->line();
            $confirmed3 = $this->confirm('FINAL WARNING: Drop the entire database now?');

            if (!$confirmed3) {
                // User cancelled at final confirmation
                $this->info('Operation cancelled.');
                $this->line();
                exit(0);
            }

            // All confirmations passed - drop the database
            $this->line();
            $this->info('Dropping database...');

            // Execute DROP DATABASE statement
            Model::server()->sql("DROP DATABASE `{$databaseName}`");

            // Database dropped successfully
            $this->line();
            $this->success("Database '{$databaseName}' has been dropped!");
            $this->line();
            $this->info("Recreate with: php roline db:create {$databaseName}");
            $this->line();

        } catch (\Exception $e) {
            // Drop operation failed (database connection, query error, permissions, etc.)
            $this->line();
            $this->error('Drop operation failed!');
            $this->line();
            $this->error('Error: ' . $e->getMessage());
            $this->line();
            exit(1);
        }
    }
}
