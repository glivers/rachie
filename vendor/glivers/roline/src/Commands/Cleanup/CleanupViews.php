<?php namespace Roline\Commands\Cleanup;

/**
 * CleanupViews Command
 *
 * Clears orphaned compiled view template files from the temporary storage directory.
 * Rachie compiles view templates to vault/tmp/ during rendering and normally deletes
 * them afterward. However, errors during rendering can leave orphaned files that
 * accumulate over time. This command safely removes them.
 *
 * What Gets Cleared:
 *   - vault/tmp/ - All orphaned compiled view templates
 *
 * What's Safe:
 *   - Your source views in application/views/ are NEVER touched
 *   - Only temporary compiled files are removed
 *   - Safe to run anytime without data loss
 *
 * When to Use:
 *   - After debugging view errors
 *   - When vault/tmp/ contains many old files
 *   - As part of regular maintenance
 *
 * Usage:
 *   php roline cleanup:views
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

class CleanupViews extends CleanupCommand
{
    public function description()
    {
        return 'Clear compiled view temporary files';
    }

    public function usage()
    {
        return '';
    }

    public function help()
    {
        parent::help();

        Output::info('What gets cleared:');
        Output::line('  - vault/tmp/    Orphaned compiled view templates');
        Output::line();

        Output::info('Why clear this:');
        Output::line('  - Views are compiled to vault/tmp/ when rendered');
        Output::line('  - Normally auto-deleted after rendering');
        Output::line('  - Errors can leave orphaned files');
        Output::line('  - Over time, orphaned files can accumulate');
        Output::line();

        Output::info('Example:');
        Output::line('  php roline cleanup:views');
        Output::line();

        Output::info('Note:');
        Output::line('  - This only clears compiled views, not actual view files');
        Output::line('  - Your source views in application/views/ are untouched');
        Output::line('  - Safe to run anytime');
        Output::line();
    }

    public function execute($arguments)
    {
        $directories = $this->getViewTempDirectories();

        // Display what will be cleared to user
        $this->line();
        $this->info("What will be cleared:");

        foreach ($directories as $dir => $label)
        {
            $this->line("  - {$dir}/ ({$label})");
        }

        $this->line();
        $this->info("Note: This only clears compiled views, not your source views.");

        // Request user confirmation before proceeding
        $this->line();
        $confirmed = $this->confirm("Are you sure you want to clear compiled views?");

        if (!$confirmed)
        {
            $this->info("Cleanup cancelled.");
            exit(0);
        }

        // Execute cleanup operation
        $this->line();
        $this->info('Clearing compiled views...');

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

        // Display final summary
        $this->line();

        if ($failed === 0)
        {
            $this->success("Compiled views cleared successfully!");
        }
        else
        {
            $this->info("Cleanup partially completed. ({$cleared} cleared, {$failed} failed)");
        }
    }
}
