<?php namespace Roline\Commands\Model;

/**
 * ModelUpdateTable Command
 *
 * Safely updates existing database tables by comparing model @column annotations
 * with current database structure and generating appropriate ALTER TABLE statements.
 * This is the SAFE alternative to model:create-table for modifying existing tables
 * without data loss.
 *
 * What Gets Updated:
 *   - New columns added to database from model
 *   - Columns marked with @drop removed from database
 *   - Columns marked with @rename renamed in database
 *   - Column types/constraints modified to match annotations
 *
 * Special Annotations for Safe Migrations:
 *   - @drop - Marks column for deletion (prevents accidental data loss)
 *   - @rename old_name - Renames column from old_name to property name
 *
 * Safety Features:
 *   - Non-destructive - Only adds/modifies, never drops without @drop annotation
 *   - Validates table exists before attempting update
 *   - Compares model schema with database schema before changes
 *   - Preserves existing data during column modifications
 *   - Generates minimal ALTER TABLE statements
 *
 * Common Use Cases:
 *   - Adding new columns to existing tables
 *   - Modifying column types or constraints
 *   - Safely renaming columns with @rename annotation
 *   - Removing obsolete columns with @drop annotation
 *
 * Example Model Annotations:
 *
 * Add new column:
 *   @column @varchar(100)
 *   protected $new_field;
 *
 * Rename column:
 *   @column @rename old_email @varchar(255)
 *   protected $email;
 *
 * Drop column:
 *   @column @drop
 *   protected $obsolete_field;
 *
 * Workflow:
 *   1. Modify model @column annotations
 *   2. Run model:update-table command
 *   3. Schema compares model vs database
 *   4. ALTER TABLE statements executed
 *   5. Table structure updated, data preserved
 *
 * Usage:
 *   php roline model:update-table User
 *   php roline model:update-table Post
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

class ModelUpdateTable extends ModelCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Update table from Model @column changes';
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
     * Shows how to use the command, what operations it performs, and examples
     * of special annotations (@drop, @rename) for safe schema migrations.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <Model|required>  Model class name (without "Model" suffix)');
        Output::line();

        Output::info('What this does:');
        Output::line('  - Compares Model @column annotations with database');
        Output::line('  - Generates ALTER TABLE statements for changes');
        Output::line('  - Handles: new columns, dropped columns (@drop), renamed columns (@rename)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline model:update-table User');
        Output::line('  php roline model:update-table Post');
        Output::line();

        Output::info('Safe Migrations:');
        Output::line('  Use @drop to mark columns for deletion:');
        Output::line('    /**');
        Output::line('     * @column');
        Output::line('     * @drop');
        Output::line('     */');
        Output::line('    protected $old_column;');
        Output::line();
        Output::line('  Use @rename to rename columns:');
        Output::line('    /**');
        Output::line('     * @column');
        Output::line('     * @rename old_name');
        Output::line('     * @varchar 255');
        Output::line('     */');
        Output::line('    protected $new_name;');
        Output::line();
    }

    /**
     * Execute table update from model annotations
     *
     * Compares model @column annotations with current database structure and
     * generates ALTER TABLE statements to synchronize. Validates model and table
     * exist, then delegates to MySQLSchema for schema comparison and DDL execution.
     * This is non-destructive - data is preserved during updates.
     *
     * @param array $arguments Command arguments (model name at index 0)
     * @return void Exits with status 1 on failure
     */
    public function execute($arguments)
    {
        // Validate model name argument is provided and normalize (ucfirst, remove 'Model' suffix)
        if (empty($arguments[0])) {
            $this->error('Model name is required!');
            $this->line();
            $this->info('Usage: php roline model:update-table <Model>');
            $this->line();
            $this->info('Example: php roline model:update-table User');
            exit(1);
        }

        // Normalize model name (ucfirst + remove 'Model' suffix)
        $modelName = $this->validateName($arguments[0]);
        $modelClass = "Models\\{$modelName}Model";

        // Check if model class exists via autoloader
        if (!class_exists($modelClass)) {
            $this->error("Model class not found: {$modelClass}");
            $this->line();
            $this->info('Create the model first: php roline model:create ' . $modelName);
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

        // Execute table update via MySQLSchema
        try {
            // Create schema instance for database operations
            $schema = new MySQLSchema();

            // Validate table exists in database before attempting update
            if (!$schema->tableExists($tableName)) {
                $this->error("Table '{$tableName}' does not exist!");
                $this->line();
                $this->info("Create it first: php roline model:create-table {$modelName}");
                exit(1);
            }

            // Display update summary
            $this->line();
            $this->info("Updating table '{$tableName}' from Model: {$modelClass}");
            $this->line();

            // Define confirmation callback for column drops and renames
            $confirmationCallback = function($dropColumns, $renameColumns) {
                // If nothing to confirm, proceed
                if (empty($dropColumns) && empty($renameColumns)) {
                    return true;
                }

                // Show pending changes
                $this->line();
                $this->info("⚠ PENDING CHANGES:");
                $this->line();

                // Show renames (data preserved)
                if (!empty($renameColumns)) {
                    $this->info("RENAMES (data preserved):");
                    foreach ($renameColumns as $rename) {
                        $this->line("  - {$rename['old_name']} → {$rename['new_name']}");
                    }
                    $this->line();
                }

                // Show drops (data lost)
                if (!empty($dropColumns)) {
                    $this->error("DROPS (data will be lost):");
                    foreach ($dropColumns as $col) {
                        $this->error("  - {$col['name']} ({$col['reason']})");
                    }
                    $this->line();
                }

                // Prompt for confirmation
                echo "Apply these changes? (y/n): ";
                $handle = fopen("php://stdin", "r");
                $input = trim(fgets($handle));
                fclose($handle);

                return strtolower($input) === 'y';
            };

            // Compare model schema with database schema and execute ALTER TABLE statements
            $result = $schema->updateTableFromModel($modelClass, $confirmationCallback);

            // Check if aborted
            if ($result === false) {
                // User aborted - don't show success message
                $this->line();
                exit(0);
            }

            // Update successful
            $this->line();
            $this->success("Table '{$tableName}' updated successfully!");
            $this->line();

        } catch (\Exception $e) {
            // Update failed (schema comparison, SQL execution, database connection, etc.)
            $this->line();
            $this->error("Failed to update table!");
            $this->line();
            $this->error("Error: " . $e->getMessage());
            $this->line();
            exit(1);
        }
    }
}
