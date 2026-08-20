<?php namespace Roline\Commands\Cleanup;

/**
 * CleanupAll Command
 *
 * Executes all cleanup operations in sequence: cache, compiled views, logs, and
 * sessions. This provides a comprehensive cleanup of all temporary and cached data
 * across the entire Rachie application, useful for fresh starts, troubleshooting,
 * or reclaiming disk space.
 *
 * Cleanup Operations Performed:
 *   1. Cache       - vault/cache/ application cache files
 *   2. Views       - vault/tmp/ compiled view templates
 *   3. Logs        - vault/logs/error.log (truncated to 0 bytes)
 *   4. Sessions    - vault/sessions/ all session files
 *
 * Impact:
 *   - ALL users will be logged out (sessions cleared)
 *   - All cached data removed (may slow initial requests)
 *   - All error logs emptied (historical data lost)
 *   - Compiled views removed (will recompile on next request)
 *
 * When to Use:
 *   - During scheduled maintenance windows
 *   - After major updates or deployments
 *   - When troubleshooting caching issues
 *   - To reclaim disk space
 *   - Never during active business hours
 *
 * Safety Features:
 *   - Displays complete list of what will be cleared
 *   - Shows log file sizes before truncation
 *   - Requires explicit user confirmation
 *   - Reports progress for each cleanup step
 *   - Provides summary of successes/failures
 *
 * Usage:
 *   php roline cleanup:all
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Cleanup
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Output;
use Rackage\File;

class CleanupAll extends CleanupCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Run all cleanup operations (cache, views, logs, sessions)';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax (empty - no arguments needed)
     */
    public function usage()
    {
        return '';
    }

    /**
     * Display detailed help information
     *
     * Shows comprehensive list of all cleanup operations that will run,
     * reasons for using this command, and critical warnings about impact.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('What gets cleared:');
        Output::line('  - vault/cache/       Application cache files');
        Output::line('  - vault/tmp/         Compiled view templates');
        Output::line('  - vault/logs/        Error log files (truncated)');
        Output::line('  - vault/sessions/    Session files (logs out all users)');
        Output::line();

        Output::info('Why use this:');
        Output::line('  - Comprehensive cleanup of all temporary/cached data');
        Output::line('  - Useful for fresh starts or troubleshooting');
        Output::line('  - Can free significant disk space');
        Output::line();

        Output::info('Example:');
        Output::line('  php roline cleanup:all');
        Output::line();

        Output::info('Warning:');
        Output::line('  - This will log out ALL active users (sessions cleared)');
        Output::line('  - Consider running during maintenance windows');
        Output::line('  - You will be prompted for confirmation before proceeding');
        Output::line();
    }

    /**
     * Execute all cleanup operations in sequence
     *
     * Runs cache, views, logs, and sessions cleanup after displaying a
     * comprehensive summary and obtaining user confirmation. Reports progress
     * for each operation and provides final success/failure summary.
     *
     * @param array $arguments Command arguments (none expected)
     * @return void Exits with status 0 on success, 1 on failure
     */
    public function execute($arguments)
    {
        // Display header for comprehensive cleanup
        $this->line();
        $this->info("Cleanup All - Complete System Cleanup");
        $this->line();

        $this->info("The following will be cleared:");
        $this->line();

        // List cache directories that will be cleared
        $this->line("Cache:");
        $cacheDirectories = $this->getCacheDirectories();

        foreach ($cacheDirectories as $dir => $label)
        {
            $this->line("  - {$dir}/ ({$label})");
        }

        $this->line();

        // List view temp directories that will be cleared
        $this->line("Compiled Views:");
        $viewDirectories = $this->getViewTempDirectories();

        foreach ($viewDirectories as $dir => $label)
        {
            $this->line("  - {$dir}/ ({$label})");
        }

        $this->line();

        // List log files with current sizes
        $this->line("Log Files:");
        $logFiles = $this->getLogFiles();

        foreach ($logFiles as $file => $label)
        {
            $fileCheck = File::exists($file);

            if ($fileCheck->exists)
            {
                // Show current file size to help user decide
                $sizeResult = File::size($file);
                $sizeFormatted = $this->formatBytes($sizeResult->size);
                $this->line("  - {$file} ({$label}) - {$sizeFormatted}");
            }
            else
            {
                $this->line("  - {$file} ({$label}) - File does not exist");
            }
        }

        $this->line();

        // List session directories that will be cleared
        $this->line("Sessions:");
        $sessionDirectories = $this->getSessionDirectories();

        foreach ($sessionDirectories as $dir => $label)
        {
            $this->line("  - {$dir}/ ({$label})");
        }

        $this->line();

        // Display critical warning about user impact
        $this->error("WARNING: This will log out ALL active users!");

        // Request user confirmation before proceeding
        $this->line();
        $confirmed = $this->confirm("Are you sure you want to run ALL cleanup operations?");

        if (!$confirmed)
        {
            $this->info("Cleanup cancelled.");
            exit(0);
        }

        // Execute all cleanup operations in sequence
        $this->line();
        $this->info('Running all cleanup operations...');
        $this->line();

        $totalCleared = 0;
        $totalFailed = 0;

        // Step 1: Clear cache directories
        $this->info('1. Clearing cache...');

        foreach ($cacheDirectories as $dir => $label)
        {
            if ($this->clearDirectory($dir))
            {
                $this->success("  Cleared: {$label}");
                $totalCleared++;
            }
            else
            {
                // Only count as failure if directory exists but couldn't be cleared
                if (file_exists($dir))
                {
                    $this->error("  Failed to clear: {$label}");
                    $totalFailed++;
                }
            }
        }

        $this->line();

        // Step 2: Clear compiled view templates
        $this->info('2. Clearing compiled views...');

        foreach ($viewDirectories as $dir => $label)
        {
            if ($this->clearDirectory($dir))
            {
                $this->success("  Cleared: {$label}");
                $totalCleared++;
            }
            else
            {
                if (file_exists($dir))
                {
                    $this->error("  Failed to clear: {$label}");
                    $totalFailed++;
                }
            }
        }

        $this->line();

        // Step 3: Truncate log files
        $this->info('3. Clearing log files...');

        foreach ($logFiles as $file => $label)
        {
            if ($this->truncateFile($file))
            {
                $this->success("  Cleared: {$label}");
                $totalCleared++;
            }
            else
            {
                if (file_exists($file))
                {
                    $this->error("  Failed to clear: {$label}");
                    $totalFailed++;
                }
            }
        }

        $this->line();

        // Step 4: Clear session files (logs out all users)
        $this->info('4. Clearing sessions...');

        foreach ($sessionDirectories as $dir => $label)
        {
            if ($this->clearDirectory($dir))
            {
                $this->success("  Cleared: {$label}");
                $totalCleared++;
            }
            else
            {
                if (file_exists($dir))
                {
                    $this->error("  Failed to clear: {$label}");
                    $totalFailed++;
                }
            }
        }

        $this->line();

        // Display final summary with statistics
        $this->line();

        if ($totalFailed === 0)
        {
            $this->success("All cleanup operations completed successfully!");
            $this->info("Total items cleared: {$totalCleared}");
            $this->line();
            $this->info("All users have been logged out.");
        }
        else
        {
            $this->info("Cleanup partially completed.");
            $this->line("  Cleared: {$totalCleared}");
            $this->line("  Failed: {$totalFailed}");
        }
    }

    /**
     * Convert bytes to human-readable file size format
     *
     * Formats raw byte counts into appropriate units (bytes, KB, MB, GB)
     * with two decimal places for readability. Used to display log file
     * sizes before truncation.
     *
     * @param int $bytes Raw byte count
     * @return string Formatted size string (e.g., '1.50 MB', '250 bytes')
     */
    private function formatBytes($bytes)
    {
        if ($bytes >= 1073741824)
        {
            // 1 GB or larger
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            // 1 MB or larger
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            // 1 KB or larger
            return number_format($bytes / 1024, 2) . ' KB';
        }
        else
        {
            // Less than 1 KB
            return $bytes . ' bytes';
        }
    }
}
