<?php namespace Roline\Commands\Model;

/**
 * ModelCreateTable Command
 *
 * Creates database tables from model class @column docblock annotations. Reads
 * the model's schema definition from property annotations and generates appropriate
 * SQL DDL statements to create the table structure. This is a destructive operation
 * that DROPS existing tables before recreation.
 *
 * What Gets Created:
 *   - Database table matching model's $table property name
 *   - Columns defined by @column annotations on model properties
 *   - Primary keys, indexes, and constraints from annotations
 *
 * Schema Annotation Format:
 *   Each model property with @column becomes a database column:
 *
 *   Example:
 *     @column @primary @autonumber
 *     protected $id;
 *
 *     @column @string(255)
 *     protected $title;
 *
 *     @column @datetime
 *     protected $created_at;
 *
 * Auto-Fix Features:
 *   - Missing Timestamps - Offers to add created_at/updated_at properties
 *   - Missing Primary Key - Offers to add id property with @primary @autonumber
 *   - Automatically modifies model file with user confirmation
 *   - Preserves existing code structure and formatting
 *
 * Safety Features:
 *   - Validates model class exists before proceeding
 *   - Checks for $table property definition
 *   - Shows table name and warns about data loss
 *   - Requires explicit user confirmation
 *   - Detailed error messages with resolution hints
 *
 * Important Warnings:
 *   - DROPS EXISTING TABLE - All data permanently lost!
 *   - Use model:update-table for safe schema modifications
 *   - Never run on production without backup
 *   - Cannot be undone after confirmation
 *
 * Typical Workflow:
 *   1. Define model with @column annotations
 *   2. Run model:create-table command
 *   3. Review table name and warnings
 *   4. Confirm creation (or fix validation errors)
 *   5. Table created in database
 *
 * Usage:
 *   php roline model:create-table User
 *   php roline model:create-table Post
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
use Roline\Exceptions\Exceptions;

class ModelCreateTable extends ModelCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Create table from Model @column annotations';
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
     * Shows required arguments, examples of table creation, and critical warnings
     * about the destructive nature of this operation. Emphasizes that existing
     * tables will be dropped and suggests model:update-table for safe modifications.
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
        Output::line('  php roline model:create-table User');
        Output::line('  php roline model:create-table Post');
        Output::line();

        Output::info('Note:');
        Output::line('  - Drops existing table before creating (data loss!)');
        Output::line('  - Use model:update-table to modify existing tables safely');
        Output::line();
    }

    /**
     * Execute table creation from model annotations
     *
     * Creates a database table by reading @column annotations from the model class.
     * Validates model exists, extracts table name, shows warnings about data loss,
     * requires user confirmation, and executes DDL via MySQLSchema. Handles validation
     * errors with auto-fix offerings for common issues (missing timestamps, missing
     * primary key). This is a DESTRUCTIVE operation that drops existing tables.
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
            $this->info('Usage: php roline model:create-table <Model>');
            $this->line();
            $this->info('Example: php roline model:create-table User');
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

            // Validate table name is defined and not empty
            if (empty($tableName)) {
                $this->error('Model does not have a table name defined!');
                $this->line();
                $this->info('Add this to your model: protected static $table = \'table_name\';');
                exit(1);
            }
        } catch (\Exception $e) {
            // Reflection failed - property doesn't exist or other error
            $this->error('Error reading model: ' . $e->getMessage());
            exit(1);
        }

        // Display summary of what will be created and warnings about data loss
        $this->line();
        $this->info("Creating table from Model: {$modelClass}");
        $this->line("  Table name: {$tableName}");
        $this->line();
        $this->error("WARNING: This will DROP the existing '{$tableName}' table if it exists!");
        $this->error("         All data will be lost!");
        $this->line();

        // Request user confirmation before proceeding with destructive operation
        $confirmed = $this->confirm("Are you sure you want to create this table?");

        if (!$confirmed) {
            // User cancelled - exit without creating table
            $this->info("Table creation cancelled.");
            exit(0);
        }

        // Execute table creation via MySQLSchema
        try {
            $this->line();
            $this->info("Creating table '{$tableName}'...");

            // Create schema instance and execute DDL from model annotations
            $schema = new MySQLSchema();
            $schema->createTableFromModel($modelClass);

            // Table created successfully
            $this->line();
            $this->success("Table '{$tableName}' created successfully!");
            $this->line();

        } catch (Exceptions $e) {
            // Schema validation or auto-fixable error occurred
            $this->line();

            // Check if error can be automatically fixed
            if ($e->isAutoFixable()) {
                // Display error details
                $this->error(ucfirst(str_replace('_', ' ', $e->getErrorType())) . "!");
                $this->line();
                $this->error("Error: " . $e->getMessage());
                $this->line();

                // Offer auto-fix based on specific error type
                switch ($e->getErrorType()) {
                    case 'missing_timestamps':
                        // Offer to add created_at and updated_at properties
                        $addFix = $this->confirm("Would you like me to add the missing timestamp properties to your model?");

                        if ($addFix) {
                            try {
                                // Modify model file to add timestamp properties
                                $this->addTimestampProperties($modelClass);
                                $this->line();
                                $this->success("Timestamp properties added to model!");
                                $this->line();
                                $this->info("Please run the command again: php roline model:create-table {$modelName}");
                                $this->line();
                                exit(0);
                            } catch (\Exception $addError) {
                                // Auto-fix failed
                                $this->line();
                                $this->error("Failed to add timestamp properties: " . $addError->getMessage());
                                $this->line();
                                exit(1);
                            }
                        }
                        break;

                    case 'missing_primary_key':
                        // Offer to add id primary key property
                        $addFix = $this->confirm("Would you like me to add an 'id' primary key property to your model?");

                        if ($addFix) {
                            try {
                                // Modify model file to add primary key property
                                $this->addPrimaryKeyProperty($modelClass);
                                $this->line();
                                $this->success("Primary key property added to model!");
                                $this->line();
                                $this->info("Please run the command again: php roline model:create-table {$modelName}");
                                $this->line();
                                exit(0);
                            } catch (\Exception $addError) {
                                // Auto-fix failed
                                $this->line();
                                $this->error("Failed to add primary key property: " . $addError->getMessage());
                                $this->line();
                                exit(1);
                            }
                        }
                        break;
                }

                // User declined auto-fix or unknown error type
                exit(1);
            }

            // Non-auto-fixable validation error (manual fix required)
            $this->error("Schema validation failed!");
            $this->line();
            $this->error("Error: " . $e->getMessage());
            $this->line();

            exit(1);

        } catch (\Exception $e) {
            // Generic error (SQL execution failure, database connection, etc.)
            $this->line();
            $this->error("Failed to create table!");
            $this->line();
            $this->error("Error: " . $e->getMessage());
            $this->line();

            exit(1);
        }
    }

    /**
     * Add timestamp properties to model file
     *
     * Automatically modifies the model file to add created_at and updated_at
     * properties with appropriate @column and @datetime annotations. Uses regex to
     * find an appropriate insertion point (before MODEL METHODS comment or after
     * last protected property).
     *
     * @param string $modelClass Fully qualified model class name
     * @return void
     * @throws \Exception If model file not found or insertion point can't be determined
     */
    private function addTimestampProperties($modelClass)
    {
        // Get model file path via reflection
        $reflection = new \ReflectionClass($modelClass);
        $filename = $reflection->getFileName();

        // Validate file exists
        if (!$filename || !file_exists($filename)) {
            throw new \Exception("Could not find model file");
        }

        // Read current file contents
        $content = file_get_contents($filename);

        // Build timestamp property code with @column and @datetime annotations
        $timestampCode = "\n    /**\n" .
                        "     * Timestamp when the record was first created\n" .
                        "     * @column\n" .
                        "     * @datetime\n" .
                        "     */\n" .
                        "    protected \$created_at;\n\n" .
                        "    /**\n" .
                        "     * Timestamp when the record was last modified\n" .
                        "     * @column\n" .
                        "     * @datetime\n" .
                        "     */\n" .
                        "    protected \$updated_at;\n";

        // Try to find a good insertion point in model file
        if (preg_match('/(\n\s*\/\/ .*MODEL METHODS.*\n)/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            // Insert before MODEL METHODS comment (ideal location)
            $insertPos = $matches[1][1];
            $content = substr_replace($content, $timestampCode . "\n", $insertPos, 0);
        } else if (preg_match('/(\n\s*protected .*;\n)(?!.*\n\s*protected)/s', $content, $matches, PREG_OFFSET_CAPTURE)) {
            // Insert after last protected property (fallback)
            $insertPos = $matches[1][1] + strlen($matches[1][0]);
            $content = substr_replace($content, $timestampCode, $insertPos, 0);
        } else {
            // No suitable insertion point found
            throw new \Exception("Could not find suitable insertion point in model file");
        }

        // Write modified content back to file
        file_put_contents($filename, $content);
    }

    /**
     * Add primary key property to model file
     *
     * Automatically modifies the model file to add an 'id' primary key property
     * with @column, @primary, and @autonumber annotations. Primary keys should
     * appear first in the schema, so this method finds an appropriate insertion
     * point near the top of the property list (after DATABASE SCHEMA comment or
     * after $timestamps property).
     *
     * @param string $modelClass Fully qualified model class name
     * @return void
     * @throws \Exception If model file not found or insertion point can't be determined
     */
    private function addPrimaryKeyProperty($modelClass)
    {
        // Get model file path via reflection
        $reflection = new \ReflectionClass($modelClass);
        $filename = $reflection->getFileName();

        // Validate file exists
        if (!$filename || !file_exists($filename)) {
            throw new \Exception("Could not find model file");
        }

        // Read current file contents
        $content = file_get_contents($filename);

        // Build primary key property code with @primary and @autonumber annotations
        $primaryKeyCode = "\n    /**\n" .
                         "     * @column\n" .
                         "     * @primary\n" .
                         "     * @autonumber\n" .
                         "     */\n" .
                         "    protected \$id;\n";

        // Find appropriate insertion point (primary key should be first property)
        if (preg_match('/(\n\s*\/\/ .*DATABASE SCHEMA.*\n)/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            // Insert after DATABASE SCHEMA comment (ideal location)
            $insertPos = $matches[1][1] + strlen($matches[1][0]);
            $content = substr_replace($content, $primaryKeyCode, $insertPos, 0);
        } else if (preg_match('/(\n\s*protected static \$timestamps)/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            // Insert after $timestamps line (fallback)
            $insertPos = $matches[1][1] + strlen($matches[1][0]);

            // Skip to end of that line before inserting
            $nextNewline = strpos($content, "\n", $insertPos);
            $content = substr_replace($content, $primaryKeyCode, $nextNewline, 0);
        } else {
            // No suitable insertion point found
            throw new \Exception("Could not find suitable insertion point in model file");
        }

        // Write modified content back to file
        file_put_contents($filename, $content);
    }
}
