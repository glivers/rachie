<?php namespace Roline\Commands\Cleanup;

/**
 * CleanupCommand - Base class for all cleanup commands
 *
 * Provides shared functionality for maintenance and cleanup operations across
 * various Rachie system directories. All cleanup commands (cache, views, logs,
 * sessions, all) extend this class to access common cleanup utilities.
 *
 * Cleanup Targets:
 *   - vault/cache/      - Application cache files
 *   - vault/tmp/        - Compiled view templates
 *   - vault/logs/       - Error and application logs
 *   - vault/sessions/   - PHP session files
 *
 * Shared Functionality:
 *   - Directory path configuration
 *   - Directory clearing operations
 *   - File truncation operations
 *   - Existence validation
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Cleanup
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Command;
use Rackage\File;

abstract class CleanupCommand extends Command
{
    /**
     * Get cache directory paths
     *
     * Returns array of cache directories that should be cleaned
     * when running cache cleanup operations.
     *
     * @return array Associative array ['path' => 'description']
     */
    protected function getCacheDirectories()
    {
        return [
            'vault/cache' => 'Cache directory',
        ];
    }

    /**
     * Get view template cache directories
     *
     * Returns array of directories containing compiled view templates
     * that should be cleaned when clearing view cache.
     *
     * @return array Associative array ['path' => 'description']
     */
    protected function getViewTempDirectories()
    {
        return [
            'vault/tmp' => 'Compiled view templates',
        ];
    }

    /**
     * Get log file paths
     *
     * Returns array of log files that should be truncated
     * when running log cleanup operations.
     *
     * @return array Associative array ['path' => 'description']
     */
    protected function getLogFiles()
    {
        return [
            'vault/logs/error.log' => 'Error log',
        ];
    }

    /**
     * Get session directory paths
     *
     * Returns array of session storage directories that should be cleaned
     * when clearing session files.
     *
     * @return array Associative array ['path' => 'description']
     */
    protected function getSessionDirectories()
    {
        return [
            'vault/sessions' => 'Session files',
        ];
    }

    /**
     * Clear all files in a directory
     *
     * Removes all files and subdirectories within the specified directory
     * while preserving the directory itself. Used for cache and temp cleanup.
     *
     * @param string $dir Directory path to clean
     * @return bool True if successful, false if directory doesn't exist
     */
    protected function clearDirectory($dir)
    {
        if (!File::exists($dir)->exists)
        {
            return false;
        }

        $result = File::cleanDir($dir);
        return $result->success;
    }

    /**
     * Truncate a log file to zero bytes
     *
     * Empties the file content while preserving the file itself. Used for
     * log file cleanup without deleting the log file.
     *
     * @param string $file File path to truncate
     * @return bool True if successful, false if file doesn't exist
     */
    protected function truncateFile($file)
    {
        if (!File::exists($file)->exists)
        {
            return false;
        }

        $result = File::write($file, '');
        return $result->success;
    }
}
