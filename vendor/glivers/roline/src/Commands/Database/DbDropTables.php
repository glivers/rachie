<?php namespace Roline\Commands\Database;

/**
 * DbDropTables Command
 *
 * EXTREMELY DESTRUCTIVE command that drops ALL tables in the database permanently.
 * Implements triple confirmation system with database name typing requirement to prevent
 * accidental execution. Use with extreme caution - this action cannot be undone.
 *
 * What Gets Dropped:
 *   - ALL tables in the database (no exceptions)
 *   - ALL data in those tables (permanent data loss)
 *   - Table structures completely removed
 *   - Database itself remains (empty)
 *
 * Confirmation System (Triple Safety):
 *   1. First Confirmation:
 *      - Shows list of ALL tables that will be dropped
 *      - Asks "Are you ABSOLUTELY sure?"
 *      - Can cancel at this point
 *
 *   2. Second Confirmation (Type Database Name):
 *      - User must type exact database name
 *      - Prevents accidental wrong-database drops
 *      - Ensures user is paying attention
 *
 *   3. Third Confirmation:
 *      - Final "Drop all tables now?" question
 *      - Last chance to abort operation
 *
 * Safety Features:
 *   - Disables foreign key checks before dropping (SET FOREIGN_KEY_CHECKS=0)
 *   - Enables DROP TABLE IF EXISTS (prevents errors if table missing)
 *   - Re-enables foreign key checks after completion
 *   - Shows count of successfully dropped tables
 *   - Continues through errors (drops as many tables as possible)
 *   - Displays errors for tables that fail to drop
 *
 * Use Cases:
 *   - Resetting development database to clean state
 *   - Starting fresh before running migrations
 *   - Clearing test database between test runs
 *   - Removing all tables before database structure redesign
 *   - NEVER use on production without extreme caution
 *
 * Important Warnings:
 *   - THIS IS PERMANENT - No undo, no rollback, no recovery
 *   - ALWAYS backup database before running (use db:export first)
 *   - Does NOT drop database itself (only tables)
 *   - Does NOT exclude any tables (migrations table also dropped)
 *   - Foreign key relationships removed along with tables
 *
 * Usage:
 *   php roline db:drop-tables
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Database
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Output;
use Roline\Utils\SchemaReader;
use Rackage\Model;
use Rackage\Registry;

class DbDropTables extends DatabaseCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Drop all database tables (keeps database)';
    }

    /**
     * Get command usage syntax
     *
     * @return string Empty string (no arguments required)
     */
    public function usage()
    {
        return '';
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
        Output::line('  Drops ALL tables in the database. This is EXTREMELY DESTRUCTIVE!');
        Output::line('  The database itself remains (empty).');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline db:drop-tables');
        Output::line();

        Output::info('Warning:');
        Output::line('  - This will DROP ALL tables including data!');
        Output::line('  - This action CANNOT be undone!');
        Output::line('  - Multiple confirmations required');
        Output::line();

        Output::info('See also:');
        Output::line('  db:drop    - Drop the entire database');
        Output::line('  db:empty   - Empty tables (TRUNCATE, keeps structure)');
        Output::line();
    }

    /**
     * Execute database table drop operation
     *
     * Drops ALL tables in database after triple confirmation (yes/no, type database
     * name, final yes/no). Shows all tables before confirmation, disables foreign key
     * checks during operation, continues through errors, and displays summary of
     * successfully dropped tables.
     *
     * @param array $arguments Command arguments (none required)
     * @return void Exits with status 0 on cancel/success, 1 on failure
     */
    public function execute($arguments)
    {
        try {
            // Get database name from configuration
            $dbConfig = Registry::dbConfig();
            $driver = $dbConfig['default'] ?? 'mysql';
            $databaseName = $dbConfig[$driver]['database'] ?? 'database';

            // Display extreme warning banner
            $this->line();
            $this->error('WARNING: This will DROP ALL tables in the database!');
            $this->line();

            // Get all tables from database
            $schemaReader = new SchemaReader();
            $tables = $schemaReader->getTables();

            // Check if database has any tables
            if (empty($tables)) {
                $this->info('No tables found in database.');
                $this->line();
                exit(0);
            }

            // Display database info and table count
            $this->info("Database: {$databaseName}");
            $this->info('Tables to drop: ' . count($tables));
            $this->line();

            // Show all tables that will be dropped
            foreach ($tables as $tableName) {
                $this->line("  → {$tableName}");
            }

            // Emphasize irreversibility
            $this->line();
            $this->error('This action CANNOT be undone!');
            $this->line();

            // First confirmation - general agreement
            $confirmed1 = $this->confirm("Are you ABSOLUTELY sure you want to drop ALL {$databaseName} tables?");

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
            $confirmed3 = $this->confirm('Final confirmation. Drop all tables now?');

            if (!$confirmed3) {
                // User cancelled at final confirmation
                $this->info('Operation cancelled.');
                $this->line();
                exit(0);
            }

            // All confirmations passed - begin dropping tables
            $this->line();
            $this->info('Dropping tables...');
            $this->line();

            // Disable foreign key checks for safe table dropping
            Model::sql('SET FOREIGN_KEY_CHECKS=0');

            // Track successfully dropped tables count
            $droppedCount = 0;

            // Drop each table (continue through errors)
            foreach ($tables as $tableName) {
                try {
                    $this->info("  → Dropping {$tableName}...");

                    // Execute DROP TABLE statement
                    Model::sql("DROP TABLE IF EXISTS `{$tableName}`");

                    // Increment dropped count
                    $droppedCount++;
                } catch (\Exception $e) {
                    // Table drop failed - display error but continue
                    $this->error("  ✗ Failed to drop {$tableName}: " . $e->getMessage());
                }
            }

            // Re-enable foreign key checks
            Model::sql('SET FOREIGN_KEY_CHECKS=1');

            // All tables processed - display summary
            $this->line();
            $this->success("Successfully dropped {$droppedCount} tables!");
            $this->line();
            $this->info("Database '{$databaseName}' is now empty.");
            $this->line();

        } catch (\Exception $e) {
            // Drop operation failed (database connection, query error, etc.)
            $this->line();
            $this->error('Drop operation failed!');
            $this->line();
            $this->error('Error: ' . $e->getMessage());
            $this->line();
            exit(1);
        }
    }
}
