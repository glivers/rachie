<?php namespace Roline\Commands\Table;

/**
 * TableEmpty Command
 *
 * Empties a database table by deleting all rows while preserving the table structure.
 * This is a destructive operation that removes ALL data but keeps the table schema,
 * indexes, and auto-increment counter intact (unlike TRUNCATE which resets it).
 *
 * What Gets Deleted:
 *   - ALL rows/records in the table
 *   - ALL data within those rows
 *
 * What Gets Preserved:
 *   - Table structure and schema definition
 *   - Column definitions
 *   - Indexes and constraints
 *   - Auto-increment counter (continues from last value)
 *   - Triggers
 *
 * Why This Instead of TRUNCATE (table:reset):
 *   - DELETE respects foreign key constraints (TRUNCATE can fail)
 *   - Auto-increment counter preserved (useful for testing)
 *   - Can be rolled back if in transaction
 *   - Triggers are fired for each row
 *
 * Safety Features:
 *   - Displays warning about data loss
 *   - Requires explicit user confirmation
 *   - Shows table name before deletion
 *   - Validates table exists first
 *
 * When to Use:
 *   - Clearing test data during development
 *   - Resetting staging databases
 *   - Removing all records before re-importing
 *   - Cleaning up development/testing tables
 *   - NEVER on production without backup!
 *
 * Usage:
 *   php roline table:empty <tablename>
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Table
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Output;
use Roline\Schema\MySQLSchema;

class TableEmpty extends TableCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Empty table (delete all rows, preserve auto-increment)';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing required table name
     */
    public function usage()
    {
        return '<tablename|required>';
    }

    /**
     * Display detailed help information
     *
     * Shows usage examples and warnings about the destructive nature of this
     * operation. Explains difference from TRUNCATE and what gets preserved.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <tablename|required>  Database table name');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline table:empty users');
        Output::line('  php roline table:empty temp_data');
        Output::line();

        Output::info('What this does:');
        Output::line('  - Deletes ALL rows from the table');
        Output::line('  - Preserves table structure and indexes');
        Output::line('  - Keeps auto-increment counter (unlike TRUNCATE)');
        Output::line('  - Respects foreign key constraints');
        Output::line();

        Output::info('Difference from table:reset:');
        Output::line('  table:empty  - Uses DELETE (slow, safe, preserves auto-increment)');
        Output::line('  table:reset  - Uses TRUNCATE (fast, resets auto-increment)');
        Output::line();

        Output::info('Warning:');
        Output::line('  This deletes ALL data in the table!');
        Output::line('  This action cannot be undone!');
        Output::line();
    }

    /**
     * Execute table emptying with confirmation
     *
     * Deletes all rows from a database table after requiring explicit confirmation
     * from user. Displays warning and shows current row count before deletion.
     * Executes DELETE FROM statement.
     *
     * @param array $arguments Command arguments (table name at index 0)
     * @return void Exits with status 0 on cancel, 1 on failure
     */
    public function execute($arguments)
    {
        // Validate table name argument is provided
        if (empty($arguments[0])) {
            $this->error('Table name is required!');
            $this->line();
            $this->info('Usage: php roline table:empty <tablename>');
            exit(1);
        }

        $tableName = $arguments[0];

        // Create schema instance for database operations
        $schema = new MySQLSchema();

        // Validate table exists in database
        if (!$schema->tableExists($tableName)) {
            $this->error("Table '{$tableName}' does not exist!");
            exit(1);
        }

        // Get current row count to show user (fast estimate)
        try {
            $rowCount = $schema->getRowCountEstimate($tableName);
        } catch (\Exception $e) {
            // Count failed - proceed anyway
            $rowCount = 'unknown';
        }

        // Display warning about data loss
        $this->line();
        $this->error("WARNING: You are about to delete ALL rows from table: {$tableName}");
        $this->error("         Current row count: {$rowCount}");
        $this->error("         This action CANNOT be undone!");
        $this->line();
        $this->info("Note: Table structure will be preserved (unlike DROP TABLE)");
        $this->info("      Auto-increment counter will NOT be reset (unlike TRUNCATE)");
        $this->line();

        // Request user confirmation before proceeding
        $confirmed = $this->confirm("Are you sure you want to empty this table?");

        if (!$confirmed) {
            // User cancelled
            $this->info("Table emptying cancelled.");
            exit(0);
        }

        // Execute DELETE FROM statement
        try {
            $this->line();
            $this->info("Emptying table '{$tableName}'...");

            // Delete all rows via MySQLSchema
            $schema->emptyTable($tableName);

            // Table emptied successfully
            $this->line();
            $this->success("Table '{$tableName}' has been emptied.");
            $this->info("All rows deleted. Table structure preserved.");
            $this->line();

        } catch (\Exception $e) {
            // Empty failed (SQL execution, database connection, foreign key constraint, etc.)
            $this->line();
            $this->error("Failed to empty table!");
            $this->error("Error: " . $e->getMessage());
            $this->line();
            exit(1);
        }
    }
}
