<?php namespace Roline\Commands\Model;

/**
 * ModelAppend Command
 *
 * Adds new properties with @column annotations to an existing model file.
 * This command allows developers to extend model schemas without manually
 * editing files, with interactive prompts for property names and types.
 *
 * Features:
 *   - Interactive property input (name and type)
 *   - Automatic @column annotation generation
 *   - Inserts properties before MODEL METHODS section
 *   - Validates model exists before modification
 *   - Preserves existing code structure
 *
 * Property Type Examples:
 *   - varchar(255) - Variable length string
 *   - int - Integer number
 *   - text - Long text content
 *   - datetime - Date and time
 *   - decimal(10,2) - Decimal with precision
 *   - boolean - True/false value
 *
 * Workflow:
 *   1. Validates model exists
 *   2. Prompts for property names and types
 *   3. Generates @column annotations
 *   4. Inserts before MODEL METHODS section
 *   5. Updates model file
 *
 * Usage:
 *   php roline model:append User
 *   php roline model:append Post
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

class ModelAppend extends ModelCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Add properties to existing model';
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
     * Shows usage examples and explains property type formats.
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
        Output::line('  php roline model:append User');
        Output::line('  php roline model:append Post');
        Output::line();

        Output::info('Common property types:');
        Output::line('  varchar(255)    - Variable length string (max 255 chars)');
        Output::line('  int             - Integer number');
        Output::line('  text            - Long text content');
        Output::line('  datetime        - Date and time');
        Output::line('  decimal(10,2)   - Decimal with precision');
        Output::line('  boolean         - True/false value');
        Output::line();

        Output::info('Interactive mode:');
        Output::line('  The command will prompt you for:');
        Output::line('  1. Property name (e.g., "email", "age", "created_at")');
        Output::line('  2. Property type (e.g., "varchar(255)", "int", "datetime")');
        Output::line('  3. Press Enter with empty name to finish');
        Output::line();
    }

    /**
     * Execute model property appending
     *
     * Validates model exists, then interactively prompts for properties
     * to add. Generates @column annotations and inserts them into the
     * model file before the MODEL METHODS section.
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
            $this->info('Usage: php roline model:append <Model>');
            $this->line();
            $this->info('Example: php roline model:append User');
            exit(1);
        }

        // Normalize model name (ucfirst + remove 'Model' suffix)
        $modelName = $this->validateName($arguments[0]);
        $modelPath = $this->getModelPath($modelName);

        // Check if model exists
        if (!$this->modelExists($modelName)) {
            $this->error("Model does not exist: {$modelPath}");
            $this->line();
            $this->info('Create it first: php roline model:create ' . $modelName);
            exit(1);
        }

        // Interactive property collection
        $this->line();
        $this->info("Adding properties to {$modelName}Model");
        $this->line();
        $this->info("Enter property details (press Enter with empty name to finish):");
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

        // Check if any properties were added
        if (empty($properties))
        {
            $this->line();
            $this->info("No properties added.");
            exit(0);
        }

        // Read current model content
        try {
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
            if (strpos($content, '// ==================== MODEL METHODS ====================') !== false)
            {
                $content = str_replace(
                    '// ==================== MODEL METHODS ====================',
                    $propertiesCode . '    // ==================== MODEL METHODS ====================',
                    $content
                );
            }
            else
            {
                // Fallback: insert before last closing brace
                $content = preg_replace('/}\s*$/', $propertiesCode . "}\n", $content);
            }

            // Write back to file
            file_put_contents($modelPath, $content);

            $this->line();
            $this->success("Added " . count($properties) . " properties to {$modelName}Model");
            $this->line();
            $this->info("Next steps:");
            $this->info("  1. Review the model file: {$modelPath}");
            $this->info("  2. Update the table: php roline model:update-table {$modelName}");
            $this->line();

        } catch (\Exception $e) {
            $this->line();
            $this->error("Failed to update model file!");
            $this->line();
            $this->error("Error: " . $e->getMessage());
            $this->line();
            exit(1);
        }
    }
}
