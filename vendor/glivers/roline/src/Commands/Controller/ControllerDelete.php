<?php namespace Roline\Commands\Controller;

/**
 * ControllerDelete Command
 *
 * Safely deletes an existing controller class file with user confirmation.
 * This is a destructive operation that permanently removes the controller
 * file from the filesystem - use with caution.
 *
 * Safety Features:
 *   - Validates controller exists before attempting deletion
 *   - Requires explicit user confirmation
 *   - Provides clear feedback on success/failure
 *   - Shows full file path before deletion
 *
 * Note: This only deletes the controller file - it does NOT remove routes
 * or clean up related views. Manual cleanup may be required.
 *
 * Usage:
 *   php roline controller:delete Posts
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

class ControllerDelete extends ControllerCommand
{
    public function description()
    {
        return 'Delete an existing controller';
    }

    public function usage()
    {
        return '<Controller|required>';
    }

    public function execute($arguments)
    {
        $name = $this->validateName($arguments[0] ?? null);

        // Verify controller exists before attempting deletion
        if (!$this->controllerExists($name))
        {
            $this->error("Controller not found: {$name}Controller");
            exit(1);
        }

        $path = $this->getControllerPath($name);

        // Display warning and request confirmation
        $this->line();
        $this->info("You are about to delete: {$path}");
        $confirmed = $this->confirm("Are you sure you want to delete this controller?");

        if (!$confirmed)
        {
            $this->info("Deletion cancelled.");
            exit(0);
        }

        // Perform file deletion
        $result = File::delete($path);

        if ($result->success)
        {
            $this->success("Controller deleted: {$path}");
        }
        else
        {
            $this->error("Failed to delete controller: {$result->errorMessage}");
            exit(1);
        }
    }
}
