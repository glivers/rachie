<?php namespace Roline\Commands\View;

/**
 * ViewDelete Command
 *
 * Deletes an entire view directory and all its contents from application/views/.
 * This is a destructive operation that permanently removes the directory and ALL
 * view files within it. User confirmation is required before deletion proceeds.
 *
 * What Gets Deleted:
 *   - Complete directory: application/views/{name}/
 *   - All view files inside: index.php, show.php, create.php, etc.
 *   - Cannot be undone - files are permanently removed
 *
 * Safety Features:
 *   - Validates view directory exists before attempting deletion
 *   - Displays full path of what will be deleted
 *   - Requires explicit user confirmation via interactive prompt
 *   - Shows warning about deleting ALL files inside
 *   - Allows cancellation at confirmation prompt
 *   - Reports success or detailed error message
 *
 * When to Use:
 *   - Removing obsolete view directories no longer needed
 *   - Cleaning up after feature removal
 *   - Restructuring view organization
 *   - Never use on production without backup!
 *
 * Typical Workflow:
 *   1. Developer runs command with view directory name
 *   2. Command shows what will be deleted (full path)
 *   3. User confirms deletion via y/n prompt
 *   4. Directory and all contents permanently removed
 *
 * Usage:
 *   php roline view:delete users
 *   php roline view:delete blog
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

class ViewDelete extends ViewCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Delete a view directory';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing required view directory name
     */
    public function usage()
    {
        return '<view|required>';
    }

    /**
     * Display detailed help information
     *
     * Shows required arguments, examples of deletion, and critical warnings
     * about the destructive nature of this operation. Emphasizes that user
     * confirmation will be required before proceeding with deletion.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <view|required>  Name of the view directory to delete');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline view:delete posts');
        Output::line('  php roline view:delete users');
        Output::line();

        Output::info('Warning:');
        Output::line('  This will delete the entire directory and ALL view files inside!');
        Output::line('  You will be asked to confirm before deletion.');
        Output::line();
    }

    /**
     * Execute view directory deletion
     *
     * Permanently deletes an entire view directory and all its contents from
     * application/views/. Validates directory exists, displays what will be
     * deleted, requires user confirmation, and performs deletion. This is a
     * destructive operation that cannot be undone.
     *
     * @param array $arguments Command arguments (view directory name at index 0)
     * @return void Exits with status 0 on cancel, 1 on failure
     */
    public function execute($arguments)
    {
        // Validate and extract view directory name from arguments
        $name = $this->validateName($arguments[0] ?? null);

        // Check if view directory exists before attempting deletion
        if (!$this->viewDirExists($name))
        {
            $this->error("View directory not found: application/views/{$name}");
            exit(1);
        }

        // Build full path to view directory
        $viewDir = $this->getViewDir($name);

        // Display deletion warning and request user confirmation
        $this->line();
        $this->info("You are about to delete: {$viewDir}");
        $this->info("This will remove the directory and ALL files inside.");
        $confirmed = $this->confirm("Are you sure you want to delete this view directory?");

        if (!$confirmed)
        {
            // User cancelled - exit without deleting anything
            $this->info("Deletion cancelled.");
            exit(0);
        }

        // Permanently delete directory and all contents (cannot be undone)
        $result = File::deleteDir($viewDir);

        if ($result->success) {
            // Deletion successful - report to user
            $this->success("View directory deleted: {$viewDir}");

            // Check if associated CSS file exists
            $cssFile = "public/css/{$name}.css";

            if (file_exists($cssFile)) {
                $this->line();
                $cssConfirmed = $this->confirm("Also delete {$cssFile}?");

                if ($cssConfirmed) {
                    $cssResult = File::delete($cssFile);

                    if ($cssResult->success) {
                        $this->success("CSS file deleted: {$cssFile}");
                    }
                    else {
                        $this->error("Failed to delete CSS file: {$cssResult->errorMessage}");
                    }
                }
                else {
                    $this->info("CSS file kept: {$cssFile}");
                }
            }
        }
        else {
            // Deletion failed - show error details
            $this->error("Failed to delete view directory: {$result->errorMessage}");
            exit(1);
        }
    }
}
