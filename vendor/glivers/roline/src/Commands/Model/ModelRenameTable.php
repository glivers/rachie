<?php namespace Roline\Commands\Model;

/**
 * ModelRenameTable Command
 *
 * Renames a database table and updates the associated model's $table property.
 * Second argument is TABLE name (not model name). Executes RENAME TABLE statement
 * in database and automatically updates model's $table property to match.
 *
 * What Gets Renamed:
 *   - Database table name (via RENAME TABLE statement)
 *   - Model $table property (automatic, not optional)
 *
 * Important Notes:
 *   - Second argument is TABLE name (e.g., 'people' not 'Person')
 *   - Does NOT rename model file or class name
 *   - Does NOT update foreign key references automatically
 *   - Does NOT update code references to old table name
 *   - User must manually update any foreign key constraints
 *
 * How It Works:
 *   1. Reads current table name from model's $table property
 *   2. Validates current table exists in database
 *   3. Validates new table name doesn't conflict
 *   4. Executes RENAME TABLE in database
 *   5. Automatically updates model's $table property
 *
 * Typical Workflow:
 *   1. User runs command: model:rename-table User people
 *   2. Command reads current table from UsersModel.php ($table = 'users')
 *   3. User confirms database table rename
 *   4. RENAME TABLE `users` TO `people` executed
 *   5. Model $table property updated to 'people' automatically
 *
 * Example:
 *   php roline model:rename-table User people
 *   - Reads current table name from UserModel.php
 *   - Renames table in database
 *   - Updates $table property to 'people'
 *
 * Usage:
 *   php roline model:rename-table User people
 *   php roline model:rename-table Post articles
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

class ModelRenameTable extends ModelCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Rename table and update model $table';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing current model and new table name
     */
    public function usage()
    {
        return '<Model|required> <new_table_name|required>';
    }

    /**
     * Display detailed help information
     *
     * Shows arguments, examples, workflow steps, and important notes about
     * what does and doesn't get updated automatically during rename.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <Model|required>             Current model name (without "Model" suffix)');
        Output::line('  <new_table_name|required>   New TABLE name (not model name)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline model:rename-table User people');
        Output::line('  php roline model:rename-table Post articles');
        Output::line();

        Output::info('What it does:');
        Output::line('  1. Reads current table name from model');
        Output::line('  2. Renames the database table');
        Output::line('  3. Updates $table property in model (mandatory)');
        Output::line();

        Output::info('Note:');
        Output::line('  - Second argument is TABLE name (e.g., "people" not "Person")');
        Output::line('  - This does NOT rename the model file or class');
        Output::line('  - This does NOT update foreign key references');
        Output::line('  - Update foreign keys manually if needed');
        Output::line();
    }

    /**
     * Execute table rename with mandatory model update
     *
     * Renames database table via RENAME TABLE statement and automatically updates
     * model's $table property. Validates both old and new names, shows preview,
     * requires confirmation, and executes database change followed by mandatory
     * model file update. User cannot decline model update.
     *
     * @param array $arguments Command arguments (current model at index 0, new table name at index 1)
     * @return void Exits with status 0 on cancel, 1 on failure
     */
    public function execute($arguments)
    {
        // Validate both current model name and new table name provided
        if (empty($arguments[0]) || empty($arguments[1])) {
            $this->error('Both current model name and new table name are required!');
            $this->line();
            $this->info('Usage: php roline model:rename-table <Model> <new_table_name>');
            $this->line();
            $this->info('Example: php roline model:rename-table User people');
            $this->line();
            $this->info('Note: Second argument is TABLE name (e.g., "people" not "Person")');
            exit(1);
        }

        // Extract arguments (normalize model name, keep table name as provided)
        $modelName = $this->validateName($arguments[0]);  // ucfirst + remove 'Model'
        $newTableName = $arguments[1];  // TABLE name (not model name)
        $modelClass = "Models\\{$modelName}Model";

        // Validate model class exists
        if (!class_exists($modelClass)) {
            $this->error("Model class not found: {$modelClass}");
            $this->line();
            $this->info('Check the model name and try again.');
            exit(1);
        }

        // Extract current table name from model's $table property
        try {
            // Use reflection to access protected static property
            $reflection = new \ReflectionClass($modelClass);
            $tableProperty = $reflection->getProperty('table');
            $tableProperty->setAccessible(true);
            $currentTableName = $tableProperty->getValue();

            // Validate table name is defined
            if (empty($currentTableName)) {
                $this->error('Model does not have a table name defined!');
                exit(1);
            }
        } catch (\Exception $e) {
            // Reflection failed
            $this->error('Error reading model: ' . $e->getMessage());
            exit(1);
        }

        // Validate current table exists in database
        $schema = new MySQLSchema();
        if (!$schema->tableExists($currentTableName)) {
            $this->error("Table '{$currentTableName}' does not exist!");
            $this->line();
            $this->info("Create it first: php roline model:create-table {$modelName}");
            exit(1);
        }

        // Check new table name doesn't conflict with existing tables
        if ($schema->tableExists($newTableName)) {
            $this->error("Table '{$newTableName}' already exists!");
            $this->line();
            $this->info('Choose a different name.');
            exit(1);
        }

        // Display rename preview (before/after)
        $this->line();
        $this->info("Renaming table:");
        $this->line("  From: {$currentTableName}");
        $this->line("  To:   {$newTableName}");
        $this->line();
        $this->info("Model \$table property will be updated automatically.");
        $this->line();

        // Request user confirmation before database change
        $confirmed = $this->confirm("Are you sure you want to rename this table?");

        if (!$confirmed) {
            // User cancelled
            $this->info("Table rename cancelled.");
            exit(0);
        }

        // Execute RENAME TABLE statement in database
        try {
            $this->line();
            $this->info("Renaming table in database...");

            // Execute RENAME TABLE via MySQLSchema
            $schema->renameTable($currentTableName, $newTableName);

            // Table renamed successfully
            $this->success("Table renamed successfully!");
            $this->line();

        } catch (\Exception $e) {
            // Rename failed (SQL execution, database connection, etc.)
            $this->line();
            $this->error("Failed to rename table!");
            $this->line();
            $this->error("Error: " . $e->getMessage());
            $this->line();
            exit(1);
        }

        // Update model's $table property (mandatory - not optional)
        try {
            $this->info("Updating model \$table property...");

            // Update $table property via regex replacement
            $this->updateModelTableProperty($modelClass, $newTableName);

            $this->line();
            $this->success("Model \$table property updated!");
            $this->line();

        } catch (\Exception $e) {
            // Update failed - provide manual instructions
            $this->line();
            $this->error("Failed to update model file!");
            $this->line();
            $this->error("Error: " . $e->getMessage());
            $this->line();
            $this->info("IMPORTANT: Please update manually: protected static \$table = '{$newTableName}';");
            $this->line();
            exit(1);
        }

        // All operations complete
        $this->line();
        $this->success("Table rename complete!");
        $this->line();
    }

    /**
     * Update model's $table property
     *
     * Modifies the model file to update the protected static $table property
     * with the new table name. Uses regex to find and replace the property value
     * while preserving formatting and quote style.
     *
     * @param string $modelClass Fully-qualified model class name
     * @param string $newTableName New table name to set
     * @return void
     * @throws \Exception If model file not found or $table property not found
     */
    private function updateModelTableProperty($modelClass, $newTableName)
    {
        // Get model file path via reflection
        $reflection = new \ReflectionClass($modelClass);
        $filename = $reflection->getFileName();

        // Validate file exists
        if (!file_exists($filename)) {
            throw new \Exception("Model file not found: {$filename}");
        }

        // Read current file contents
        $content = file_get_contents($filename);

        // Build regex pattern to match $table property (preserves quote style)
        $pattern = '/(protected\s+static\s+\$table\s*=\s*[\'"])([^\'"]+)([\'"];)/';
        $replacement = '${1}' . $newTableName . '${3}';

        // Replace $table property value
        $newContent = preg_replace($pattern, $replacement, $content);

        // Validate replacement succeeded
        if ($newContent === null || $newContent === $content) {
            throw new \Exception("Could not find \$table property to update");
        }

        // Write modified content back to file
        file_put_contents($filename, $newContent);
    }
}
