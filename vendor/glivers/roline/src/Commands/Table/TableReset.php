<?php namespace Roline\Commands\Table;

/**
 * TableReset Command
 *
 * Resets a database table by truncating all rows and resetting the auto-increment
 * counter back to 1. This is a fast, destructive operation that completely wipes
 * the table clean and starts fresh. Much faster than DELETE for large tables.
 *
 * What Gets Reset:
 *   - ALL rows/records deleted instantly
 *   - Auto-increment counter reset to 1
 *   - ALL data within those rows
 *
 * What Gets Preserved:
 *   - Table structure and schema definition
 *   - Column definitions
 *   - Indexes and constraints
 *   - Triggers (but they don't fire during TRUNCATE)
 *
 * Why This Instead of DELETE (table:empty):
 *   - TRUNCATE is much faster (especially on large tables)
 *   - Resets auto-increment to 1 (clean slate)
 *   - Minimal transaction log overhead
 *   - Reclaims storage space immediately
 *
 * Important Differences from table:empty:
 *   - Does NOT respect foreign keys (may require disabling FK checks)
 *   - Does NOT fire triggers
 *   - Cannot be rolled back
 *   - Auto-increment resets to 1 (not preserved)
 *
 * Safety Features:
 *   - Displays warning about data loss and auto-increment reset
 *   - Requires explicit user confirmation
 *   - Shows table name before truncation
 *   - Validates table exists first
 *
 * When to Use:
 *   - Need to completely reset table with fresh IDs
 *   - Large tables where DELETE would be too slow
 *   - Tables with no foreign key dependencies
 *   - Development/testing clean slate scenarios
 *   - NEVER on production without backup!
 *
 * When NOT to Use:
 *   - Tables referenced by foreign keys (use table:empty)
 *   - Need to preserve auto-increment sequence
 *   - Need transactional safety (rollback capability)
 *   - Need triggers to fire on deletion
 *
 * Usage:
 *   php roline table:reset <tablename>
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

class TableReset extends TableCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Reset table (truncate, reset auto-increment)';
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
     * operation. Explains difference from DELETE (table:empty) and what
     * gets reset vs preserved.
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
        Output::line('  php roline table:reset users');
        Output::line('  php roline table:reset temp_data');
        Output::line();

        Output::info('What this does:');
        Output::line('  - TRUNCATES table (fast deletion)');
        Output::line('  - RESETS auto-increment counter to 1');
        Output::line('  - Preserves table structure and indexes');
        Output::line('  - Does NOT respect foreign keys (may fail if referenced)');
        Output::line('  - Does NOT fire triggers');
        Output::line();

        Output::info('Difference from table:empty:');
        Output::line('  table:empty  - Uses DELETE (slow, safe, preserves auto-increment)');
        Output::line('  table:reset  - Uses TRUNCATE (fast, resets auto-increment)');
        Output::line();

        Output::info('Warning:');
        Output::line('  This deletes ALL data and RESETS IDs to 1!');
        Output::line('  This action cannot be undone!');
        Output::line();
    }

    /**
     * Execute table reset with confirmation
     *
     * Truncates all rows from a database table and resets auto-increment counter
     * after requiring explicit confirmation. Displays warning, shows current row
     * count before truncation. Executes TRUNCATE TABLE statement.
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
            $this->info('Usage: php roline table:reset <tablename>');
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

        // Check if other tables reference this one via foreign keys
        $hasDependents = false;
        try {
            $dependents = $schema->getTablesReferencingTable($tableName);

            if (!empty($dependents)) {
                $hasDependents = true;
                $this->line();
                $this->error("⚠ WARNING: Other tables have foreign key references to '{$tableName}':");
                $this->line();

                foreach ($dependents as $table => $columns) {
                    $columnList = implode(', ', $columns);
                    $this->line("  • {$table} (column: {$columnList})");
                }

                $this->line();
                $this->error("TRUNCATE may fail due to foreign key constraints!");
                $this->info("Consider using 'table:empty' instead (respects foreign keys).");
                $this->line();
            }
        } catch (\Exception $e) {
            // If check fails, continue anyway
        }

        // Display warning about data loss and auto-increment reset
        $this->line();
        $this->error("WARNING: You are about to TRUNCATE table: {$tableName}");
        $this->error("         Current row count: {$rowCount}");
        $this->error("         Auto-increment will RESET to 1!");
        $this->error("         This action CANNOT be undone!");
        $this->line();
        $this->info("Note: Table structure will be preserved");
        $this->info("      This is FASTER than DELETE but resets auto-increment");
        if ($hasDependents) {
            $this->error("      Foreign key checks will be temporarily disabled!");
        }
        $this->line();

        // Request user confirmation before proceeding
        $confirmed = $this->confirm("Are you sure you want to reset this table?");

        if (!$confirmed) {
            // User cancelled
            $this->info("Table reset cancelled.");
            exit(0);
        }

        // Execute TRUNCATE TABLE statement
        try {
            $this->line();
            $this->info("Resetting table '{$tableName}'...");

            // Truncate table via MySQLSchema (resets auto-increment)
            $schema->truncateTable($tableName);

            // Table truncated successfully
            $this->line();
            $this->success("Table '{$tableName}' has been reset.");
            $this->info("All rows deleted. Auto-increment reset to 1.");
            $this->line();

        } catch (\Exception $e) {
            // Truncate failed (SQL execution, database connection, foreign key constraint, etc.)
            $this->line();
            $this->error("Failed to reset table!");
            $this->line();
            $this->error("Error: " . $e->getMessage());
            $this->line();

            // Provide helpful suggestion
            if (strpos($e->getMessage(), 'foreign key') !== false || $hasDependents) {
                $this->info("Suggestion: Use 'table:empty' instead (respects foreign keys)");
                $this->line();
            }

            exit(1);
        }
    }
}
