<?php namespace Roline\Commands\Migration;

/**
 * MigrationCommand - Base Class for Database Migration Operations
 *
 * Abstract base class providing shared functionality for all migration-related CLI
 * commands in Roline. Handles migration directory management, file discovery, and
 * timestamp-based filename generation for maintaining migration order.
 *
 * Architecture:
 *   - Template Method Pattern - Subclasses implement execute() for specific operations
 *   - File-Based Migrations - Migrations stored as PHP files in application/database/migrations/
 *   - Timestamp Ordering - Filenames prefixed with YYYY_MM_DD_HHmmss for sequential execution
 *   - Convention Over Configuration - Standard directory structure expected
 *
 * Migration File Naming:
 *   Format: YYYY_MM_DD_HHmmss_migration_name.php
 *   Example: 2025_01_15_143022_create_users_table.php
 *
 * Helper Methods Provided:
 *   - getMigrationsDir()     - Returns path to migrations directory
 *   - ensureMigrationsDir()  - Creates migrations directory if doesn't exist
 *   - getMigrationFiles()    - Lists all migration files in sorted order
 *   - generateFilename()     - Creates timestamped filename for new migration
 *
 * Common Workflows:
 *   1. migration:make   - Create new migration file
 *   2. migration:run    - Execute pending migrations
 *   3. migration:rollback - Revert last batch of migrations
 *   4. migration:status - View migration execution status
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Migration
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Command;
use Rackage\File;

abstract class MigrationCommand extends Command
{
    /**
     * Get migrations directory path
     *
     * Returns the standard path where all migration files are stored. This path
     * is relative to the project root directory.
     *
     * @return string Migrations directory path
     */
    protected function getMigrationsDir()
    {
        return 'application/database/migrations';
    }

    /**
     * Ensure migrations directory exists
     *
     * Creates the migrations directory if it doesn't exist. Safe to call multiple
     * times - only creates if needed. Uses File::ensureDir() for cross-platform
     * directory creation.
     *
     * @return void
     */
    protected function ensureMigrationsDir()
    {
        File::ensureDir($this->getMigrationsDir());
    }

    /**
     * Get all migration files
     *
     * Scans the migrations directory and returns all PHP files sorted alphabetically
     * (which maintains chronological order due to timestamp prefixes). Returns empty
     * array if directory doesn't exist.
     *
     * @return array List of migration filenames (sorted chronologically)
     */
    protected function getMigrationFiles()
    {
        // Get migrations directory path
        $dir = $this->getMigrationsDir();

        // Return empty array if directory doesn't exist yet
        if (!File::exists($dir)->exists)
        {
            return [];
        }

        // Scan directory for files
        $files = scandir($dir);
        $migrations = [];

        // Filter for PHP files only
        foreach ($files as $file)
        {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php')
            {
                $migrations[] = $file;
            }
        }

        // Sort alphabetically (timestamp prefix ensures chronological order)
        sort($migrations);

        return $migrations;
    }

    /**
     * Generate migration filename with timestamp
     *
     * Creates a timestamped filename in the format YYYY_MM_DD_HHmmss_name.php.
     * The timestamp prefix ensures migrations execute in chronological order and
     * prevents filename conflicts.
     *
     * @param string $name Migration name (e.g., "create_users_table")
     * @return string Complete filename with timestamp prefix
     */
    protected function generateFilename($name)
    {
        // Generate timestamp prefix (YYYY_MM_DD_HHmmss format)
        $timestamp = date('Y_m_d_His');

        // Build filename: timestamp_name.php
        return "{$timestamp}_{$name}.php";
    }
}
