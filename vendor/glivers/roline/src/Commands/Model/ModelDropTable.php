<?php namespace Roline\Commands\Model;

/**
 * ModelDropTable Command
 *
 * Permanently drops a database table and ALL its data. This is an extremely
 * destructive operation that cannot be undone. Multiple confirmations required
 * before execution to prevent accidental data loss.
 *
 * What Gets Deleted:
 *   - Complete database table
 *   - ALL rows and data within the table
 *   - Table structure and schema definition
 *   - Indexes, constraints, and triggers
 *
 * Safety Features:
 *   - Displays dramatic "DANGER ZONE" warning banner
 *   - Requires double confirmation before proceeding
 *   - Shows table name prominently in warnings
 *   - Explicit messaging that action cannot be undone
 *   - Validates model exists before attempting drop
 *
 * When to Use:
 *   - Removing obsolete tables no longer needed
 *   - Cleaning up development/testing databases
 *   - Restructuring database schema
 *   - NEVER on production without backup!
 *
 * Typical Workflow:
 *   1. User runs command with model name
 *   2. Command displays DANGER ZONE warning
 *   3. First confirmation prompt
 *   4. Second "absolutely sure" confirmation
 *   5. Table permanently dropped from database
 *
 * Important Warnings:
 *   - Cannot be undone after execution
 *   - All data permanently lost
 *   - Backup before running on production
 *   - Consider model:update-table for schema changes instead
 *
 * Usage:
 *   php roline model:drop-table User
 *   php roline model:drop-table Post
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

class ModelDropTable extends ModelCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Drop a database table';
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
     * Shows usage examples and critical warnings about the permanent and
     * irreversible nature of this destructive operation.
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
        Output::line('  php roline model:drop-table User');
        Output::line('  php roline model:drop-table Post');
        Output::line();

        Output::info('Warning:');
        Output::line('  This PERMANENTLY deletes the table and ALL data!');
        Output::line('  This action CANNOT be undone!');
        Output::line();
    }

    /**
     * Execute table deletion with double confirmation
     *
     * Permanently drops a database table after extracting table name from model
     * and requiring TWO explicit confirmations from user. Displays dramatic
     * warning banner to emphasize destructive nature. This cannot be undone.
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
            $this->info('Usage: php roline model:drop-table <Model>');
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

        // Display dramatic DANGER ZONE warning banner
        $this->line();
        $this->error("╔═══════════════════════════════════════════════════════════╗");
        $this->error("║                     DANGER ZONE                           ║");
        $this->error("╚═══════════════════════════════════════════════════════════╝");
        $this->line();
        $this->error("You are about to PERMANENTLY DELETE table: {$tableName}");
        $this->error("ALL DATA in this table will be LOST FOREVER!");
        $this->error("This action CANNOT be undone!");
        $this->line();

        // First confirmation - user must acknowledge table name
        $confirmed1 = $this->confirm("Type the table name to confirm deletion: {$tableName}");

        if (!$confirmed1) {
            // User cancelled at first prompt
            $this->info("Table deletion cancelled.");
            exit(0);
        }

        // Second confirmation - "absolutely sure" double-check
        $this->line();
        $confirmed2 = $this->confirm("Are you ABSOLUTELY SURE you want to delete '{$tableName}'?");

        if (!$confirmed2) {
            // User cancelled at second prompt
            $this->info("Table deletion cancelled.");
            exit(0);
        }

        // Create schema instance for database operations
        $schema = new MySQLSchema();

        // Check if other tables reference this one via foreign keys
        try {
            $dependents = $schema->getTablesReferencingTable($tableName);

            if (!empty($dependents)) {
                $this->line();
                $this->error("Cannot drop table '{$tableName}'!");
                $this->line();
                $this->error("The following tables have foreign key references to it:");
                $this->line();

                foreach ($dependents as $table => $columns) {
                    $columnList = implode(', ', $columns);
                    $this->line("  • {$table} (column: {$columnList})");
                }

                $this->line();
                $this->info("You must either:");
                $this->line("  1. Drop those tables first (in correct order), OR");
                $this->line("  2. Remove the foreign key constraints from those tables");
                $this->line();
                exit(1);
            }
        } catch (\Exception $e) {
            // If check fails, continue anyway (better to try and get MySQL error)
        }

        // Execute table drop via MySQLSchema
        try {
            $this->line();
            $this->info("Dropping table '{$tableName}'...");

            $schema->dropTable($tableName);

            // Table dropped successfully
            $this->line();
            $this->success("Table '{$tableName}' has been dropped.");
            $this->line();

        } catch (\Exception $e) {
            // Drop failed (SQL execution, database connection, etc.)
            $this->line();
            $this->error("Failed to drop table!");
            $this->error("Error: " . $e->getMessage());
            exit(1);
        }
    }
}
