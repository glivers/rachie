<?php namespace Roline\Commands\Model;

/**
 * ModelCreate Command
 *
 * Generates a new model class from a stub template with pre-configured table
 * name, namespace, and static method structure. The generated model extends
 * Rackage\Model and is ready for immediate database operations.
 *
 * Features:
 *   - Auto-adds 'Model' suffix if not provided
 *   - Auto-generates plural table name (or accepts custom name)
 *   - Checks for existing models to prevent overwriting
 *   - Supports custom stub templates
 *   - Creates models directory if needed
 *
 * Generated Model Structure:
 *   - Proper namespace (Models\)
 *   - Static table name property
 *   - Timestamp configuration
 *   - Extends Rackage\Model
 *
 * Usage:
 *   php roline model:create Post                  (auto: table 'posts')
 *   php roline model:create PostModel             (auto: table 'posts')
 *   php roline model:create Data datum            (custom table for edge cases)
 *   php roline model:create Sheep sheep           (singular = plural)
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Model
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\File;
use Rackage\Registry;

class ModelCreate extends ModelCommand
{
    public function description()
    {
        return 'Create a new model class';
    }

    public function usage()
    {
        return '<Model|required> [table|optional]';
    }

    public function execute($arguments)
    {
        $name = $this->validateName($arguments[0] ?? null);

        // Prevent overwriting existing models
        if ($this->modelExists($name))
        {
            $path = $this->getModelPath($name);
            $this->error("Model already exists: {$path}");
            exit(1);
        }

        // Use provided table name or auto-generate plural form
        // Example: 'Post' becomes 'posts', 'User' becomes 'users'
        // Custom: model:create Data datum (for edge cases)
        $tableName = $arguments[1] ?? $this->pluralize($name);

        // Load stub template - custom stubs take priority over defaults
        // This allows developers to customize generated model structure
        $customStubPath = getcwd() . '/application/database/stubs/model.stub';
        $defaultStubPath = __DIR__ . '/../../../stubs/model.stub';

        $stubPath = file_exists($customStubPath) ? $customStubPath : $defaultStubPath;

        $stub = File::read($stubPath);

        if (!$stub->success)
        {
            $this->error('Model stub file not found');
            exit(1);
        }

        // Replace template placeholders with actual values
        $content = str_replace('{{ModelName}}', $name, $stub->content);
        $content = str_replace('{{TableName}}', $tableName, $content);

        // Replace metadata placeholders from settings
        $settings = Registry::settings();
        $content = str_replace('{{author}}', $settings['author'] ?? 'Your Name', $content);
        $content = str_replace('{{copyright}}', $settings['copyright'] ?? 'Copyright (c) ' . date('Y'), $content);
        $content = str_replace('{{license}}', $settings['license'] ?? 'MIT License', $content);
        $content = str_replace('{{version}}', $settings['version'] ?? '1.0.0', $content);

        // Ensure target directory exists before writing
        $this->ensureModelsDir();

        // Write the generated model file
        $path = $this->getModelPath($name);
        $result = File::write($path, $content);

        if ($result->success)
        {
            $this->success("Model created: {$path}");
            $customTable = isset($arguments[1]);
            $tableSource = $customTable ? '(custom)' : '(auto-pluralized)';
            $this->info("Table name: {$tableName} {$tableSource}");
            $this->line();

            // Interactive prompt: ask if user wants to add properties
            $addProperties = $this->confirm("Would you like to add properties to this model now?");

            if ($addProperties)
            {
                $this->addPropertiesInteractively($path, $name);
            }
            else
            {
                $this->line();
                $this->info("You can add properties later using: php roline model:append {$name}");
                $this->line();
            }
        }
        else
        {
            $this->error("Failed to create model: {$result->errorMessage}");
            exit(1);
        }
    }

    /**
     * Interactively add properties to model
     *
     * Prompts user for property names and types, then inserts @column
     * annotations into the model file.
     *
     * @param string $modelPath Path to model file
     * @param string $modelName Model name (without Model suffix)
     * @return void
     */
    private function addPropertiesInteractively($modelPath, $modelName)
    {
        $this->line();
        $this->info("Add properties (press Enter with empty name to finish):");
        $this->line();

        $properties = [];

        while (true)
        {
            // Get property name
            $this->line("Property name (or press Enter to finish): ", false);
            $propertyName = trim(fgets(STDIN));

            if (empty($propertyName))
            {
                break;
            }

            // Get property type
            $this->line("Property type [varchar(255)]: ", false);
            $propertyType = trim(fgets(STDIN));

            if (empty($propertyType))
            {
                $propertyType = 'varchar(255)';
            }

            $properties[] = [
                'name' => $propertyName,
                'type' => $propertyType
            ];

            $this->success("  Added: \${$propertyName} ({$propertyType})");
        }

        if (!empty($properties))
        {
            // Read current model content
            $content = file_get_contents($modelPath);

            // Build properties code
            $propertiesCode = "\n";
            foreach ($properties as $prop)
            {
                $propertiesCode .= "    /**\n";
                $propertiesCode .= "     * @column\n";
                $propertiesCode .= "     * @{$prop['type']}\n";
                $propertiesCode .= "     */\n";
                $propertiesCode .= "    protected \${$prop['name']};\n\n";
            }

            // Insert before MODEL METHODS section
            $content = str_replace(
                '// ==================== MODEL METHODS ====================',
                $propertiesCode . '    // ==================== MODEL METHODS ====================',
                $content
            );

            // Write back to file
            file_put_contents($modelPath, $content);

            $this->line();
            $this->success("Added " . count($properties) . " properties to {$modelName}Model");
            $this->line();
            $this->info("Next steps:");
            $this->info("  1. Review the model file: {$modelPath}");
            $this->info("  2. Create the table: php roline model:create-table {$modelName}");
            $this->line();
        }
        else
        {
            $this->line();
            $this->info("No properties added.");
            $this->line();
        }
    }
}
