<?php namespace Roline\Commands\View;

/**
 * ViewCommand - Base class for all view commands
 *
 * Provides shared functionality for view-related CLI commands including
 * name validation, path resolution, existence checking, and directory setup.
 * All view commands (create, add, delete) extend this class to avoid code
 * duplication.
 *
 * View Structure:
 *   Views are organized in application/views/ directory with subdirectories
 *   for logical grouping (e.g., application/views/posts/index.php)
 *
 * Shared Functionality:
 *   - Name normalization (converts to lowercase)
 *   - Path resolution to application/views/
 *   - Directory existence checking
 *   - Directory creation
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\View
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Command;
use Rackage\File;

abstract class ViewCommand extends Command
{
    /**
     * Validate and normalize view name
     *
     * Ensures a view name is provided and converts it to lowercase
     * for consistent file naming conventions.
     *
     * @param string|null $name View name from user input
     * @return string Normalized lowercase view name
     */
    protected function validateName($name)
    {
        if (!$name)
        {
            $this->error('View name is required');
            exit(1);
        }

        return strtolower($name);
    }

    /**
     * Build path to view directory
     *
     * @param string $name View directory name (e.g., 'posts', 'admin/users')
     * @return string Full relative path (e.g., 'application/views/posts')
     */
    protected function getViewDir($name)
    {
        return "application/views/{$name}";
    }

    /**
     * Build full path to view file
     *
     * @param string $dir View directory path
     * @param string $file View filename without extension
     * @return string Full file path with .php extension
     */
    protected function getViewPath($dir, $file)
    {
        return "{$dir}/{$file}.php";
    }

    /**
     * Check if view directory already exists
     *
     * @param string $name View directory name
     * @return bool True if directory exists, false otherwise
     */
    protected function viewDirExists($name)
    {
        $path = $this->getViewDir($name);
        return File::exists($path)->exists;
    }

    /**
     * Ensure the views root directory exists
     *
     * Creates application/views/ directory if it doesn't exist.
     * Called before creating new view directories.
     *
     * @return void
     */
    protected function ensureViewsDir()
    {
        File::ensureDir('application/views');
    }
}
