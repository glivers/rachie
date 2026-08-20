<?php namespace Roline\Commands\Model;

/**
 * ModelRename Command
 *
 * Renames a model file and updates the class name inside the file.
 * Handles case-insensitive input and automatically strips/adds "Model" suffix.
 *
 * What It Does:
 *   - Renames the model file (e.g., TodosModel.php → TodoModel.php)
 *   - Updates the class name inside the file
 *   - Validates that old model exists and new model doesn't exist
 *
 * Usage:
 *   php roline model:rename Todos Todo
 *   php roline model:rename TodosModel TodoModel
 *   php roline model:rename todosmodel todomodel
 *
 * Note:
 *   This command does NOT update references to the model in controllers or other files.
 *   You'll need to manually update any imports or usage of the old model name.
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
use Roline\Output;

class ModelRename extends ModelCommand
{
    /**
     * Get command description for listing
     *
     * Returns a brief one-line description of what this command does.
     * Displayed when running `php roline` to list all available commands.
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Rename a model file and class name';
    }

    /**
     * Get command usage syntax
     *
     * Defines the expected arguments for this command. The pipe-separated
     * format indicates argument types (e.g., <oldName|required>).
     *
     * @return string Usage syntax showing required arguments
     */
    public function usage()
    {
        return '<oldName|required> <newName|required>';
    }

    /**
     * Display detailed help information
     *
     * Shows comprehensive usage instructions including:
     * - Argument descriptions
     * - Usage examples
     * - Important notes about manual updates needed
     *
     * Called when running: php roline model:rename --help
     *
     * @return void Outputs help text to console
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <oldName|required>  Current model name (with or without "Model" suffix)');
        Output::line('  <newName|required>  New model name (with or without "Model" suffix)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline model:rename Todos Todo');
        Output::line('  php roline model:rename TodosModel TodoModel');
        Output::line('  php roline model:rename todosmodel todomodel');
        Output::line();

        Output::info('Note:');
        Output::line('  This command only renames the model file and class name.');
        Output::line('  You must manually update any references to the old model name');
        Output::line('  in controllers, views, or other files.');
        Output::line();
    }

    /**
     * Execute model rename operation
     *
     * Performs the following steps:
     * 1. Validates both old and new model names are provided
     * 2. Normalizes names (strips "Model" suffix, capitalizes)
     * 3. Checks old model file exists
     * 4. Checks new model file doesn't already exist
     * 5. Reads old file content
     * 6. Updates class name in content
     * 7. Writes new file
     * 8. Deletes old file
     * 9. Reports success and reminds user to update references
     *
     * @param array $arguments Command arguments [0 => oldName, 1 => newName]
     * @return void Exits with status 1 on failure, displays success message on completion
     */
    public function execute($arguments)
    {
        // Validate arguments
        if (empty($arguments[0]) || empty($arguments[1])) {
            $this->error('Both old and new model names are required!');
            $this->line();
            $this->info('Usage: php roline model:rename <oldName> <newName>');
            $this->line();
            $this->info('Example: php roline model:rename Todos Todo');
            exit(1);
        }

        // Normalize names (strip "Model", capitalize)
        $oldName = $this->normalizeName($arguments[0]);
        $newName = $this->normalizeName($arguments[1]);

        // Build file paths
        $oldPath = "application/models/{$oldName}Model.php";
        $newPath = "application/models/{$newName}Model.php";

        // Validate old model exists
        if (!file_exists($oldPath)) {
            $this->error("Model not found: {$oldPath}");
            exit(1);
        }

        // Validate new model doesn't exist
        if (file_exists($newPath)) {
            $this->error("Model already exists: {$newPath}");
            exit(1);
        }

        $this->line();
        $this->info("This will rename:");
        $this->info("  From: {$oldPath}");
        $this->info("  To:   {$newPath}");
        $this->line();
        $this->line("The class name inside the file will also be updated.");
        $this->line();

        // Ask for confirmation
        $confirmation = readline("Are you sure you want to rename this model? (yes/no): ");
        if (strtolower(trim($confirmation)) !== 'yes') {
            $this->line();
            $this->line('Operation cancelled.');
            exit(0);
        }

        $this->line();
        $this->info("Renaming model: {$oldName}Model → {$newName}Model");
        $this->line();

        // Read old file content
        $result = File::read($oldPath);
        if (!$result->success) {
            $this->error("Failed to read model file: {$result->errorMessage}");
            exit(1);
        }

        $content = $result->content;

        // Update class name
        $content = str_replace(
            "class {$oldName}Model",
            "class {$newName}Model",
            $content
        );

        // Write to new file
        $writeResult = File::write($newPath, $content);
        if (!$writeResult->success) {
            $this->error("Failed to write new model file: {$writeResult->errorMessage}");
            exit(1);
        }

        // Delete old file
        if (!unlink($oldPath)) {
            $this->error("Failed to delete old model file: {$oldPath}");
            // Rollback - delete new file
            unlink($newPath);
            exit(1);
        }

        $this->success("✓ Model renamed successfully!");
        $this->line();
        $this->info("  Old: {$oldPath}");
        $this->info("  New: {$newPath}");
        $this->line();
        $this->line("Don't forget to update references to {$oldName}Model in your code:");
        $this->line("  - Controller imports: use Models\\{$oldName}Model;");
        $this->line("  - Model calls: {$oldName}Model::all()");
        $this->line();
    }

    /**
     * Normalize model name for consistent formatting
     *
     * Strips the "Model" suffix (case-insensitive) and capitalizes the first letter
     * to ensure consistent naming regardless of user input format.
     *
     * Examples:
     * - "todos" → "Todos"
     * - "TodosModel" → "Todos"
     * - "todosmodel" → "Todos"
     * - "TODOS" → "Todos"
     *
     * @param string $name Raw model name from user input
     * @return string Normalized model name (capitalized, without "Model" suffix)
     */
    private function normalizeName($name)
    {
        // Remove "Model" suffix (case-insensitive)
        $name = preg_replace('/model$/i', '', $name);

        // Capitalize first letter
        return ucfirst($name);
    }
}
