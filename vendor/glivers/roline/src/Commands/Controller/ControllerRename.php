<?php namespace Roline\Commands\Controller;

/**
 * ControllerRename Command
 *
 * Renames a controller file and updates the class name inside the file.
 * Handles case-insensitive input and automatically strips/adds "Controller" suffix.
 *
 * What It Does:
 *   - Renames the controller file (e.g., TodosController.php → TodoController.php)
 *   - Updates the class name inside the file
 *   - Validates that old controller exists and new controller doesn't exist
 *
 * Usage:
 *   php roline controller:rename Todos Todo
 *   php roline controller:rename TodosController TodoController
 *   php roline controller:rename todoscontroller todocontroller
 *
 * Note:
 *   This command does NOT update route definitions or references to the controller.
 *   You may need to manually update config/routes.php or other files.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Controller
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\File;
use Roline\Output;

class ControllerRename extends ControllerCommand
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
        return 'Rename a controller file and class name';
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
     * - Important notes about manual route updates needed
     *
     * Called when running: php roline controller:rename --help
     *
     * @return void Outputs help text to console
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <oldName|required>  Current controller name (with or without "Controller" suffix)');
        Output::line('  <newName|required>  New controller name (with or without "Controller" suffix)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline controller:rename Todos Todo');
        Output::line('  php roline controller:rename TodosController TodoController');
        Output::line('  php roline controller:rename todoscontroller todocontroller');
        Output::line();

        Output::info('Note:');
        Output::line('  This command only renames the controller file and class name.');
        Output::line('  You must manually update any route definitions or references');
        Output::line('  to the old controller name.');
        Output::line();
    }

    /**
     * Execute controller rename operation
     *
     * Performs the following steps:
     * 1. Validates both old and new controller names are provided
     * 2. Normalizes names (strips "Controller" suffix, capitalizes)
     * 3. Checks old controller file exists
     * 4. Checks new controller file doesn't already exist
     * 5. Reads old file content
     * 6. Updates class name in content
     * 7. Writes new file
     * 8. Deletes old file
     * 9. Reports success and reminds user to check routes
     *
     * @param array $arguments Command arguments [0 => oldName, 1 => newName]
     * @return void Exits with status 1 on failure, displays success message on completion
     */
    public function execute($arguments)
    {
        // Validate arguments
        if (empty($arguments[0]) || empty($arguments[1])) {
            $this->error('Both old and new controller names are required!');
            $this->line();
            $this->info('Usage: php roline controller:rename <oldName> <newName>');
            $this->line();
            $this->info('Example: php roline controller:rename Todos Todo');
            exit(1);
        }

        // Normalize names (strip "Controller", capitalize)
        $oldName = $this->normalizeName($arguments[0]);
        $newName = $this->normalizeName($arguments[1]);

        // Build file paths
        $oldPath = $this->getControllerPath($oldName);
        $newPath = $this->getControllerPath($newName);

        // Validate old controller exists
        if (!file_exists($oldPath)) {
            $this->error("Controller not found: {$oldPath}");
            exit(1);
        }

        // Validate new controller doesn't exist
        if (file_exists($newPath)) {
            $this->error("Controller already exists: {$newPath}");
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
        $confirmation = readline("Are you sure you want to rename this controller? (yes/no): ");
        if (strtolower(trim($confirmation)) !== 'yes') {
            $this->line();
            $this->line('Operation cancelled.');
            exit(0);
        }

        $this->line();
        $this->info("Renaming controller: {$oldName}Controller → {$newName}Controller");
        $this->line();

        // Read old file content
        $result = File::read($oldPath);
        if (!$result->success) {
            $this->error("Failed to read controller file: {$result->errorMessage}");
            exit(1);
        }

        $content = $result->content;

        // Update class name
        $content = str_replace(
            "class {$oldName}Controller",
            "class {$newName}Controller",
            $content
        );

        // Write to new file
        $writeResult = File::write($newPath, $content);
        if (!$writeResult->success) {
            $this->error("Failed to write new controller file: {$writeResult->errorMessage}");
            exit(1);
        }

        // Delete old file
        if (!unlink($oldPath)) {
            $this->error("Failed to delete old controller file: {$oldPath}");
            // Rollback - delete new file
            unlink($newPath);
            exit(1);
        }

        $this->success("✓ Controller renamed successfully!");
        $this->line();
        $this->info("  Old: {$oldPath}");
        $this->info("  New: {$newPath}");
        $this->line();
        $this->line("Don't forget to check:");
        $this->line("  - Route definitions in config/routes.php");
        $this->line("  - Any manual references to {$oldName}Controller");
        $this->line();
    }

    /**
     * Normalize controller name for consistent formatting
     *
     * Strips the "Controller" suffix (case-insensitive) and capitalizes the first letter
     * to ensure consistent naming regardless of user input format.
     *
     * Examples:
     * - "todos" → "Todos"
     * - "TodosController" → "Todos"
     * - "todoscontroller" → "Todos"
     * - "TODOS" → "Todos"
     *
     * @param string $name Raw controller name from user input
     * @return string Normalized controller name (capitalized, without "Controller" suffix)
     */
    private function normalizeName($name)
    {
        // Remove "Controller" suffix (case-insensitive)
        $name = preg_replace('/controller$/i', '', $name);

        // Capitalize first letter
        return ucfirst($name);
    }
}
