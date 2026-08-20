<?php namespace Roline\Commands\Model;

/**
 * ModelEmptyTable Command
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
 * Why This Instead of TRUNCATE:
 *   - DELETE respects foreign key constraints (TRUNCATE can fail)
 *   - Auto-increment counter preserved (useful for testing)
 *   - Can be rolled back if in transaction
 *   - Triggers are fired for each row
 *
 * Safety Features:
 *   - Displays warning about data loss
 *   - Requires explicit user confirmation
 *   - Shows table name and row count before deletion
 *   - Validates model and table exist first
 *
 * When to Use:
 *   - Clearing test data during development
 *   - Resetting staging databases
 *   - Removing all records before re-importing
 *   - Cleaning up development/testing tables
 *   - NEVER on production without backup!
 *
 * Typical Workflow:
 *   1. User runs command with model name
 *   2. Command displays warning and row count
 *   3. User confirms deletion
 *   4. DELETE FROM table executed
 *   5. All rows removed, table structure preserved
 *
 * Important Warnings:
 *   - Cannot be undone after execution (unless in transaction)
 *   - All data permanently lost
 *   - Backup before running on production
 *   - Auto-increment counter NOT reset
 *
 * Usage:
 *   php roline model:empty-table User
 *   php roline model:empty-table Post
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

class ModelEmptyTable extends ModelCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Empty table (delete all rows)';
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
     * operation. Explains difference from TRUNCATE and what gets preserved.
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
        Output::line('  php roline model:empty-table User');
        Output::line('  php roline model:empty-table Post');
        Output::line();

        Output::info('What this does:');
        Output::line('  - Deletes ALL rows from the table');
        Output::line('  - Preserves table structure and indexes');
        Output::line('  - Keeps auto-increment counter (unlike TRUNCATE)');
        Output::line('  - Respects foreign key constraints');
        Output::line();

        Output::info('Warning:');
        Output::line('  This deletes ALL data in the table!');
        Output::line('  This action cannot be undone!');
        Output::line();
    }

    /**
     * Execute table emptying with confirmation
     *
     * Deletes all rows from a database table after extracting table name from model
     * and requiring explicit confirmation from user. Displays warning and shows
     * current row count before deletion. Executes DELETE FROM statement.
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
            $this->info('Usage: php roline model:empty-table <Model>');
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
            $this->info("Create it first: php roline model:create-table {$modelName}");
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
