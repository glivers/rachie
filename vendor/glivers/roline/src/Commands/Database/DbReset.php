<?php namespace Roline\Commands\Database;

/**
 * DbReset Command
 *
 * Resets ALL tables in the database using TRUNCATE while preserving table structures.
 * Much faster than DELETE and resets auto-increment counters.
 *
 * What Gets Deleted:
 *   - ALL rows in ALL tables (complete data loss)
 *   - Data permanently removed from ALL tables
 *
 * What Gets Preserved:
 *   - ALL table structures
 *   - Column definitions
 *   - Indexes and constraints
 *   - Triggers
 *
 * What Gets Reset:
 *   - Auto-increment counters (reset to 1)
 *
 * TRUNCATE vs DELETE:
 *   - TRUNCATE is much faster (doesn't log individual row deletions)
 *   - TRUNCATE resets auto-increment counters
 *   - TRUNCATE cannot be rolled back (even in transaction)
 *   - DELETE is slower but keeps auto-increment values
 *
 * Safety Features:
 *   - Shows list of all tables before truncation
 *   - Requires user confirmation
 *   - Disables foreign key checks during operation
 *   - Continues through errors (truncates as many tables as possible)
 *   - Displays summary of successfully truncated tables
 *
 * Use Cases:
 *   - Clearing development database for fresh crawl
 *   - Resetting test data between test runs
 *   - Quick cleanup during development
 *   - Emptying all tables before re-importing data
 *
 * Usage:
 *   php roline db:reset
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

class DbReset extends DatabaseCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Reset all tables (TRUNCATE, resets auto-increment)';
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
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Description:');
        Output::line('  Resets ALL tables in the database using TRUNCATE.');
        Output::line('  Table structures are preserved, auto-increment counters reset to 1.');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline db:reset');
        Output::line();

        Output::info('Warning:');
        Output::line('  - This deletes ALL data from ALL tables!');
        Output::line('  - This action CANNOT be undone!');
        Output::line('  - Auto-increment counters will be reset to 1');
        Output::line('  - Table structures are preserved');
        Output::line();

        Output::info('See also:');
        Output::line('  db:drop         - Drop the entire database');
        Output::line('  db:drop-tables  - Drop all tables (keeps database)');
        Output::line();
    }

    /**
     * Execute database reset operation
     *
     * Resets ALL tables in database after confirmation.
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

            // Display warning banner
            $this->line();
            $this->error('WARNING: This will TRUNCATE ALL tables (delete all data)!');
            $this->line();

            // Get all tables from database using Model::sql()
            $result = Model::sql("SHOW TABLES");
            $tables = [];
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }

            // Check if database has any tables
            if (empty($tables)) {
                $this->info('No tables found in database.');
                $this->line();
                exit(0);
            }

            // Display database info and table count
            $this->info("Database: {$databaseName}");
            $this->info('Tables to truncate: ' . count($tables));
            $this->line();

            // Show all tables that will be truncated
            foreach ($tables as $tableName) {
                $this->line("  → {$tableName}");
            }

            // Emphasize irreversibility
            $this->line();
            $this->error('This action CANNOT be undone!');
            $this->info('Note: Table structures preserved, auto-increment counters reset');
            $this->line();

            // Confirmation
            $confirmed = $this->confirm("Are you sure you want to TRUNCATE ALL tables in {$databaseName}?");

            if (!$confirmed) {
                $this->info('Operation cancelled.');
                $this->line();
                exit(0);
            }

            // Begin truncating tables
            $this->line();
            $this->info('Truncating tables...');
            $this->line();

            // Disable foreign key checks for safe truncation
            Model::sql('SET FOREIGN_KEY_CHECKS=0');

            // Track successfully truncated tables count
            $truncatedCount = 0;

            // Truncate each table (continue through errors)
            foreach ($tables as $tableName) {
                try {
                    $this->info("  → Truncating {$tableName}...");

                    // Execute TRUNCATE TABLE statement (faster than DELETE, resets auto-increment)
                    Model::sql("TRUNCATE TABLE `{$tableName}`");

                    // Increment truncated count
                    $truncatedCount++;
                } catch (\Exception $e) {
                    // Table truncate failed - display error but continue
                    $this->error("  ✗ Failed to truncate {$tableName}: " . $e->getMessage());
                }
            }

            // Re-enable foreign key checks
            Model::sql('SET FOREIGN_KEY_CHECKS=1');

            // All tables processed - display summary
            $this->line();
            $this->success("Successfully truncated {$truncatedCount} tables!");
            $this->line();
            $this->info("All data deleted from '{$databaseName}'. Auto-increment counters reset.");
            $this->line();

        } catch (\Exception $e) {
            // Reset operation failed
            $this->line();
            $this->error('Reset operation failed!');
            $this->line();
            $this->error('Error: ' . $e->getMessage());
            $this->line();
            exit(1);
        }
    }
}
