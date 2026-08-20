<?php namespace Roline\Commands\Model;

/**
 * ModelResetTable Command
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
 * Why This Instead of DELETE (model:table-empty):
 *   - TRUNCATE is much faster (especially on large tables)
 *   - Resets auto-increment to 1 (clean slate)
 *   - Minimal transaction log overhead
 *   - Reclaims storage space immediately
 *
 * Important Differences from model:table-empty:
 *   - Does NOT respect foreign keys (may require disabling FK checks)
 *   - Does NOT fire triggers
 *   - Cannot be rolled back
 *   - Auto-increment resets to 1 (not preserved)
 *
 * Safety Features:
 *   - Displays warning about data loss and auto-increment reset
 *   - Requires explicit user confirmation
 *   - Shows table name before truncation
 *   - Validates model and table exist first
 *   - Checks for foreign key references and warns
 *
 * When to Use:
 *   - Need to completely reset table with fresh IDs
 *   - Large tables where DELETE would be too slow
 *   - Tables with no foreign key dependencies
 *   - Development/testing clean slate scenarios
 *   - NEVER on production without backup!
 *
 * When NOT to Use:
 *   - Tables referenced by foreign keys (use model:table-empty)
 *   - Need to preserve auto-increment sequence
 *   - Need transactional safety (rollback capability)
 *   - Need triggers to fire on deletion
 *
 * Typical Workflow:
 *   1. User runs command with model name
 *   2. Command displays warning and row count
 *   3. Command checks for foreign key dependencies
 *   4. User confirms truncation
 *   5. TRUNCATE TABLE executed
 *   6. All rows removed, auto-increment reset to 1
 *
 * Important Warnings:
 *   - Cannot be undone (DDL operation, not DML)
 *   - All data permanently lost
 *   - Auto-increment counter RESET to 1
 *   - May fail if foreign keys reference this table
 *   - Backup before running on production
 *
 * Usage:
 *   php roline model:table-reset User
 *   php roline model:table-reset Post
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Model
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Output;
use Roline\Schema\MySQLSchema;

class ModelResetTable extends ModelCommand
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
     * @return string Usage syntax showing required model name
     */
    public function usage()
    {
        return '<Model|required>';
    }

    /**
     * Display detailed help information
     *
     * Shows usage examples and warnings about the destructive nature of this
     * operation. Explains difference from DELETE (model:table-empty) and what
     * gets reset vs preserved.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <Model|required>  Model class name (without "Model" suffix)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline model:table-reset User');
        Output::line('  php roline model:table-reset Post');
        Output::line();

        Output::info('What this does:');
        Output::line('  - TRUNCATES table (fast deletion)');
        Output::line('  - RESETS auto-increment counter to 1');
        Output::line('  - Preserves table structure and indexes');
        Output::line('  - Does NOT respect foreign keys (may fail if referenced)');
        Output::line('  - Does NOT fire triggers');
        Output::line();

        Output::info('Difference from model:table-empty:');
        Output::line('  model:table-empty  - Uses DELETE (slow, safe, preserves auto-increment)');
        Output::line('  model:table-reset  - Uses TRUNCATE (fast, resets auto-increment)');
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
     * after extracting table name from model and requiring explicit confirmation.
     * Displays warning, shows current row count, and checks for FK dependencies
     * before truncation. Executes TRUNCATE TABLE statement.
     *
     * @param array $arguments Command arguments (model name at index 0)
     * @return void Exits with status 0 on cancel, 1 on failure
     */
    public function execute($arguments)
    {
        // Validate model name argument is provided and normalize (ucfirst, remove 'Model' suffix)
        if (empty($arguments[0])) {
            $this->error('Model name is required!');
            $this->line();
            $this->info('Usage: php roline model:table-reset <Model>');
            exit(1);
        }

        // Normalize model name (ucfirst + remove 'Model' suffix)
        $modelName = $this->validateName($arguments[0]);
        $modelClass = "Models\\{$modelName}Model";

        // Check if model class exists via autoloader
        if (!class_exists($modelClass)) {
            $this->error("Model class not found: {$modelClass}");
            exit(1);
        }

        // Extract table name from model's protected static $table property
        try {
            // Use reflection to access protected static property
            $reflection = new \ReflectionClass($modelClass);
            $tableProperty = $reflection->getProperty('table');
            $tableProperty->setAccessible(true);
            $tableName = $tableProperty->getValue();

            // Validate table name is defined
            if (empty($tableName)) {
                $this->error('Model does not have a table name defined!');
                exit(1);
            }
        } catch (\Exception $e) {
            // Reflection failed
            $this->error('Error reading model: ' . $e->getMessage());
            exit(1);
        }

        // Create schema instance for database operations
        $schema = new MySQLSchema();

        // Validate table exists in database
        if (!$schema->tableExists($tableName)) {
            $this->error("Table '{$tableName}' does not exist!");
            $this->line();
            $this->info("Create it first: php roline model:table-create {$modelName}");
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
                $this->info("Consider using 'model:table-empty' instead (respects foreign keys).");
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
                $this->info("Suggestion: Use 'model:table-empty' instead (respects foreign keys)");
                $this->line();
            }

            exit(1);
        }
    }
}
