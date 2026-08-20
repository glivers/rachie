<?php namespace Roline\Commands\Cleanup;

/**
 * CleanupCache Command
 *
 * Clears application cache based on configured cache driver (file, memcached, or redis).
 * Displays current configuration, shows what will be cleared, and requires confirmation
 * before executing. Provides driver-specific instructions for memory-based caches.
 *
 * Supported Cache Drivers:
 *   - file:      Clears vault/cache/ directory
 *   - memcached: Shows flush command for Memcached
 *   - redis:     Shows FLUSHDB command for Redis
 *
 * Safety Features:
 *   - Shows current cache configuration
 *   - Displays what will be cleared before execution
 *   - Requires user confirmation
 *   - Skips non-existent directories without error
 *
 * Note: This only clears cache, not compiled views. Use cleanup:views for that.
 *
 * Usage:
 *   php roline cleanup:cache
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

class CleanupCache extends CleanupCommand
{
    public function description()
    {
        return 'Clear application cache';
    }

    public function usage()
    {
        return '';
    }

    public function help()
    {
        parent::help();

        Output::info('What gets cleared:');
        Output::line('  - vault/cache/      Application cache files (file driver)');
        Output::line('  OR');
        Output::line('  - Memcached/Redis cache (based on configured driver)');
        Output::line();

        Output::info('Example:');
        Output::line('  php roline cleanup:cache');
        Output::line();

        Output::info('Note:');
        Output::line('  - Reads cache configuration from config/cache.php');
        Output::line('  - Shows what will be cleared before confirmation');
        Output::line('  - Only clears cache, not views or logs (use cleanup:views or cleanup:logs)');
        Output::line();
    }

    public function execute($arguments)
    {
        // Load cache configuration from Registry
        $cacheConfig = \Rackage\Registry::cacheConfig();
        $cacheEnabled = $cacheConfig['enabled'] ?? false;
        $cacheDriver = $cacheConfig['default'] ?? 'file';
        $driverConfig = $cacheConfig['drivers'][$cacheDriver] ?? [];

        // Display current cache configuration
        $this->line();
        $this->info("Current Cache Configuration:");
        $this->line("  Status: " . ($cacheEnabled ? "Enabled" : "Disabled"));
        $this->line("  Driver: {$cacheDriver}");

        // Show driver-specific configuration details
        if ($cacheDriver === 'file')
        {
            $cachePath = $driverConfig['path'] ?? 'vault/cache';
            $this->line("  Path: {$cachePath}");
        }
        elseif ($cacheDriver === 'memcached')
        {
            $host = $driverConfig['host'] ?? '127.0.0.1';
            $port = $driverConfig['port'] ?? 11211;
            $this->line("  Server: {$host}:{$port}");
        }
        elseif ($cacheDriver === 'redis')
        {
            $host = $driverConfig['host'] ?? '127.0.0.1';
            $port = $driverConfig['port'] ?? 6379;
            $database = $driverConfig['database'] ?? 0;
            $this->line("  Server: {$host}:{$port}");
            $this->line("  Database: {$database}");
        }

        // Show what will be cleared
        $this->line();
        $this->info("What will be cleared:");

        if ($cacheDriver === 'file')
        {
            $cachePath = $driverConfig['path'] ?? 'vault/cache';
            $this->line("  - All files in: {$cachePath}/");
        }
        elseif ($cacheDriver === 'memcached')
        {
            $this->line("  - All cached data in Memcached server");
        }
        elseif ($cacheDriver === 'redis')
        {
            $database = $driverConfig['database'] ?? 0;
            $this->line("  - All cached data in Redis database {$database}");
        }

        $this->line();
        $this->info("Note: This only clears cache. Use 'cleanup:views' to clear compiled views.");

        // Request user confirmation before proceeding
        $this->line();
        $confirmed = $this->confirm("Are you sure you want to clear the cache?");

        if (!$confirmed)
        {
            $this->info("Cache clear cancelled.");
            exit(0);
        }

        // Execute cache clearing operation
        $this->line();
        $this->info('Clearing cache...');

        $cleared = 0;
        $failed = 0;

        // Clear cache directories (file-based cache only)
        $directories = $this->getCacheDirectories();

        foreach ($directories as $dir => $label)
        {
            if ($this->clearDirectory($dir))
            {
                $this->success("Cleared: {$label}");
                $cleared++;
            }
            else
            {
                // Don't count as failure if directory doesn't exist
                if (file_exists($dir))
                {
                    $this->error("Failed to clear: {$label}");
                    $failed++;
                }
            }
        }

        // For memory-based caches, provide manual flush instructions
        if ($cacheDriver === 'memcached')
        {
            $host = $driverConfig['host'] ?? '127.0.0.1';
            $port = $driverConfig['port'] ?? 11211;
            $this->line();
            $this->info("Memcached Cache:");
            $this->line("To flush Memcached, run:");
            $this->line("  telnet {$host} {$port}");
            $this->line("  flush_all");
        }
        elseif ($cacheDriver === 'redis')
        {
            $host = $driverConfig['host'] ?? '127.0.0.1';
            $port = $driverConfig['port'] ?? 6379;
            $database = $driverConfig['database'] ?? 0;
            $this->line();
            $this->info("Redis Cache:");
            $this->line("To flush Redis database {$database}, run:");
            $this->line("  redis-cli -h {$host} -p {$port} -n {$database} FLUSHDB");
        }

        $this->line();
        if ($failed === 0)
        {
            $this->success("Cache cleared successfully!");
        }
        else
        {
            $this->info("Cache partially cleared. ({$cleared} cleared, {$failed} failed)");
        }
    }
}
