<?php namespace Roline\Commands\View;

/**
 * ViewRename Command
 *
 * Renames a view directory and all its contents.
 * Handles case-insensitive input and normalizes to lowercase.
 *
 * What It Does:
 *   - Renames the view directory (e.g., application/views/todoss/ → application/views/todos/)
 *   - Moves all view files and subdirectories
 *   - Validates that old view exists and new view doesn't exist
 *
 * Usage:
 *   php roline view:rename todoss todos
 *   php roline view:rename Todoss Todos
 *   php roline view:rename TODOSS todos
 *
 * Note:
 *   This command does NOT update View::render() calls in controllers.
 *   You'll need to manually update any references from the old view name to the new one.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\View
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\File;
use Roline\Output;

class ViewRename extends ViewCommand
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
        return 'Rename a view directory';
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
     * - Important notes about manual View::render() updates needed
     *
     * Called when running: php roline view:rename --help
     *
     * @return void Outputs help text to console
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <oldName|required>  Current view directory name');
        Output::line('  <newName|required>  New view directory name');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline view:rename todoss todos');
        Output::line('  php roline view:rename Todoss Todos');
        Output::line('  php roline view:rename products items');
        Output::line();

        Output::info('Note:');
        Output::line('  This command only renames the view directory.');
        Output::line('  You must manually update View::render() calls in controllers:');
        Output::line('  - View::render(\'oldname/index\') → View::render(\'newname/index\')');
        Output::line();
    }

    /**
     * Execute view directory rename operation
     *
     * Performs the following steps:
     * 1. Validates both old and new view names are provided
     * 2. Normalizes names to lowercase
     * 3. Checks old view directory exists
     * 4. Checks new view directory doesn't already exist
     * 5. Renames the directory (moves all files automatically)
     * 6. Reports success and reminds user to update View::render() calls
     *
     * @param array $arguments Command arguments [0 => oldName, 1 => newName]
     * @return void Exits with status 1 on failure, displays success message on completion
     */
    public function execute($arguments)
    {
        // Validate arguments
        if (empty($arguments[0]) || empty($arguments[1])) {
            $this->error('Both old and new view names are required!');
            $this->line();
            $this->info('Usage: php roline view:rename <oldName> <newName>');
            $this->line();
            $this->info('Example: php roline view:rename todoss todos');
            exit(1);
        }

        // Normalize names (lowercase)
        $oldName = strtolower(trim($arguments[0]));
        $newName = strtolower(trim($arguments[1]));

        // Build directory paths
        $oldPath = $this->getViewDir($oldName);
        $newPath = $this->getViewDir($newName);

        // Validate old view exists
        if (!$this->viewDirExists($oldName)) {
            $this->error("View directory not found: {$oldPath}");
            exit(1);
        }

        // Validate new view doesn't exist
        if ($this->viewDirExists($newName)) {
            $this->error("View directory already exists: {$newPath}");
            exit(1);
        }

        $this->line();
        $this->info("This will rename:");
        $this->info("  From: {$oldPath}");
        $this->info("  To:   {$newPath}");
        $this->line();
        $this->line("All files in the directory will be moved.");
        $this->line();

        // Ask for confirmation
        $confirmation = readline("Are you sure you want to rename this view directory? (yes/no): ");
        if (strtolower(trim($confirmation)) !== 'yes') {
            $this->line();
            $this->line('Operation cancelled.');
            exit(0);
        }

        $this->line();
        $this->info("Renaming view directory: {$oldName} → {$newName}");
        $this->line();

        // Rename directory
        if (!rename($oldPath, $newPath)) {
            $this->error("Failed to rename view directory!");
            exit(1);
        }

        $this->success("✓ View directory renamed successfully!");
        $this->line();
        $this->info("  Old: {$oldPath}");
        $this->info("  New: {$newPath}");
        $this->line();

        // Check if associated CSS file exists
        $oldCssFile = "public/css/{$oldName}.css";
        $newCssFile = "public/css/{$newName}.css";

        if (file_exists($oldCssFile)) {
            $cssConfirmed = $this->confirm("Also rename {$oldCssFile} to {$newCssFile}?");

            if ($cssConfirmed) {
                if (rename($oldCssFile, $newCssFile)) {
                    $this->success("✓ CSS file renamed successfully!");
                    $this->line();
                    $this->info("  Old: {$oldCssFile}");
                    $this->info("  New: {$newCssFile}");
                    $this->line();
                }
                else {
                    $this->error("Failed to rename CSS file!");
                }
            }
            else {
                $this->info("CSS file kept: {$oldCssFile}");
                $this->line();
            }
        }

        $this->line("Don't forget to update View::render() calls in your controllers:");
        $this->line("  - View::render('{$oldName}/index') → View::render('{$newName}/index')");
        $this->line("  - View::render('{$oldName}/show') → View::render('{$newName}/show')");
        $this->line();
    }
}
