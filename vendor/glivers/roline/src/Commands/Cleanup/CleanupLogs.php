<?php namespace Roline\Commands\Cleanup;

/**
 * CleanupLogs Command
 *
 * Truncates (empties) error log files to zero bytes without deleting them.
 * Error logs can grow very large over time, making debugging difficult and
 * consuming disk space. This command safely clears log content while preserving
 * the log files themselves, so logging continues to work normally.
 *
 * What Gets Cleared:
 *   - vault/logs/error.log - Application error log (truncated to 0 bytes)
 *
 * Safety Features:
 *   - Shows current log file sizes before clearing
 *   - Files are truncated, not deleted (logging continues to work)
 *   - Requires user confirmation
 *   - Displays which files don't exist (safe to ignore)
 *
 * Important:
 *   - Consider backing up important logs before clearing
 *   - Truncation is permanent - log content cannot be recovered
 *   - Log files continue to work after clearing
 *
 * Usage:
 *   php roline cleanup:logs
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

class CleanupLogs extends CleanupCommand
{
    public function description()
    {
        return 'Clear error log files';
    }

    public function usage()
    {
        return '';
    }

    public function help()
    {
        parent::help();

        Output::info('What gets cleared:');
        Output::line('  - vault/logs/error.log    Application error log');
        Output::line();

        Output::info('Why clear this:');
        Output::line('  - Error logs can grow very large over time');
        Output::line('  - Large logs slow down error debugging');
        Output::line('  - Logs are truncated (emptied), not deleted');
        Output::line();

        Output::info('Example:');
        Output::line('  php roline cleanup:logs');
        Output::line();

        Output::info('Note:');
        Output::line('  - Truncates log files (sets content to empty)');
        Output::line('  - Consider backing up important logs first');
        Output::line('  - Log file will continue to work after clearing');
        Output::line();
    }

    public function execute($arguments)
    {
        $logFiles = $this->getLogFiles();

        // Display what will be cleared with current file sizes
        $this->line();
        $this->info("What will be cleared:");

        foreach ($logFiles as $file => $label)
        {
            $fileCheck = File::exists($file);

            if ($fileCheck->exists)
            {
                // Show file size to help user decide if clearing is needed
                $sizeResult = File::size($file);
                $sizeFormatted = $this->formatBytes($sizeResult->size);
                $this->line("  - {$file} ({$label}) - {$sizeFormatted}");
            }
            else
            {
                $this->line("  - {$file} ({$label}) - File does not exist");
            }
        }

        // Request user confirmation before proceeding
        $this->line();
        $confirmed = $this->confirm("Are you sure you want to clear log files?");

        if (!$confirmed)
        {
            $this->info("Cleanup cancelled.");
            exit(0);
        }

        // Execute log truncation operation
        $this->line();
        $this->info('Clearing log files...');

        $cleared = 0;
        $failed = 0;

        foreach ($logFiles as $file => $label)
        {
            if ($this->truncateFile($file))
            {
                $this->success("Cleared: {$label}");
                $cleared++;
            }
            else
            {
                // Only report failure if file exists but couldn't be truncated
                if (file_exists($file))
                {
                    $this->error("Failed to clear: {$label}");
                    $failed++;
                }
            }
        }

        // Display final summary
        $this->line();

        if ($failed === 0)
        {
            $this->success("Log files cleared successfully!");
        }
        else
        {
            $this->info("Cleanup partially completed. ({$cleared} cleared, {$failed} failed)");
        }
    }

    /**
     * Convert bytes to human-readable file size format
     *
     * Formats raw byte counts into appropriate units (bytes, KB, MB, GB)
     * with two decimal places for readability.
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
