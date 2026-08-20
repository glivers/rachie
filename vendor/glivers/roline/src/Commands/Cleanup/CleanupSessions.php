<?php namespace Roline\Commands\Cleanup;

/**
 * CleanupSessions Command
 *
 * Clears all PHP session files from the session storage directory. This is a
 * destructive operation that logs out ALL active users by removing their session
 * data. Use with caution and typically only during maintenance windows.
 *
 * What Gets Cleared:
 *   - vault/sessions/ - All PHP session files (active and expired)
 *
 * Impact:
 *   - ALL users will be logged out immediately
 *   - Shopping carts, form data, and session state lost
 *   - Users must log in again to continue
 *
 * When to Use:
 *   - During scheduled maintenance windows
 *   - After security incidents requiring session invalidation
 *   - When session storage is consuming excessive disk space
 *   - Never during active business hours
 *
 * Safety Features:
 *   - Displays clear warning about logging out all users
 *   - Requires explicit user confirmation
 *   - Shows what will be cleared before execution
 *
 * Usage:
 *   php roline cleanup:sessions
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

class CleanupSessions extends CleanupCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Clear old session files';
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
     * Shows what gets cleared, why you might need this, usage examples,
     * and critical warnings about the impact of clearing sessions.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('What gets cleared:');
        Output::line('  - vault/sessions/    Old/expired session files');
        Output::line();

        Output::info('Why clear this:');
        Output::line('  - Session files accumulate over time');
        Output::line('  - Old sessions are no longer needed');
        Output::line('  - Can use significant disk space');
        Output::line();

        Output::info('Example:');
        Output::line('  php roline cleanup:sessions');
        Output::line();

        Output::info('Warning:');
        Output::line('  - This will log out ALL active users');
        Output::line('  - Only run this if you understand the impact');
        Output::line('  - Consider running during maintenance windows');
        Output::line();
    }

    /**
     * Execute session cleanup operation
     *
     * Clears all session files from the session storage directory after
     * displaying warnings and obtaining user confirmation. This logs out
     * all active users.
     *
     * @param array $arguments Command arguments (none expected)
     * @return void Exits with status 0 on success, 1 on failure
     */
    public function execute($arguments)
    {
        $directories = $this->getSessionDirectories();

        // Display what will be cleared to user
        $this->line();
        $this->info("What will be cleared:");

        foreach ($directories as $dir => $label)
        {
            $this->line("  - {$dir}/ ({$label})");
        }

        // Display critical warning about impact
        $this->line();
        $this->error("WARNING: This will log out ALL active users!");

        // Request user confirmation before proceeding
        $this->line();
        $confirmed = $this->confirm("Are you sure you want to clear all sessions?");

        if (!$confirmed)
        {
            $this->info("Cleanup cancelled.");
            exit(0);
        }

        // Execute session cleanup operation
        $this->line();
        $this->info('Clearing sessions...');

        $cleared = 0;
        $failed = 0;

        foreach ($directories as $dir => $label)
        {
            if ($this->clearDirectory($dir))
            {
                $this->success("Cleared: {$label}");
                $cleared++;
            }
            else
            {
                // Only report failure if directory exists but couldn't be cleared
                if (file_exists($dir))
                {
                    $this->error("Failed to clear: {$label}");
                    $failed++;
                }
            }
        }

        // Display final summary with user impact notice
        $this->line();

        if ($failed === 0)
        {
            $this->success("Sessions cleared successfully!");
            $this->info("All users have been logged out.");
        }
        else
        {
            $this->info("Cleanup partially completed. ({$cleared} cleared, {$failed} failed)");
        }
    }
}
