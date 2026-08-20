<?php namespace Roline\Commands\Controller;

/**
 * ControllerCommand - Base class for all controller commands
 *
 * Provides shared functionality for controller-related CLI commands including
 * name validation, path resolution, existence checking, and directory setup.
 * All controller commands (create, append, delete, complete) extend this class
 * to avoid code duplication.
 *
 * Shared Functionality:
 *   - Name normalization (removes 'Controller' suffix if provided)
 *   - Path resolution to application/controllers/
 *   - Existence validation
 *   - Directory creation
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Controller
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Command;
use Rackage\File;

abstract class ControllerCommand extends Command
{
    /**
     * Validate and normalize controller name
     *
     * Ensures a controller name is provided, capitalizes first letter, and removes
     * the 'Controller' suffix if the user included it. This allows 'posts', 'Posts',
     * and 'PostsController' as valid input, all normalized to 'Posts'.
     *
     * @param string|null $name Controller name from user input
     * @return string Normalized name without 'Controller' suffix, first letter capitalized
     */
    protected function validateName($name)
    {
        if (!$name)
        {
            $this->error('Controller name is required');
            exit(1);
        }

        // Capitalize first letter (allows lowercase input like 'posts')
        $name = ucfirst($name);

        // Remove 'Controller' suffix if user provided it
        // Example: 'PostsController' becomes 'Posts'
        return str_replace('Controller', '', $name);
    }

    /**
     * Build full path to controller file
     *
     * @param string $name Normalized controller name (without 'Controller' suffix)
     * @return string Full relative path (e.g., 'application/controllers/PostsController.php')
     */
    protected function getControllerPath($name)
    {
        return "application/controllers/{$name}Controller.php";
    }

    /**
     * Check if controller file already exists
     *
     * @param string $name Normalized controller name
     * @return bool True if controller file exists, false otherwise
     */
    protected function controllerExists($name)
    {
        $path = $this->getControllerPath($name);
        return File::exists($path)->exists;
    }

    /**
     * Ensure the controllers directory exists
     *
     * Creates application/controllers/ directory if it doesn't exist.
     * Called before writing new controller files.
     *
     * @return void
     */
    protected function ensureControllersDir()
    {
        File::ensureDir('application/controllers');
    }
}
