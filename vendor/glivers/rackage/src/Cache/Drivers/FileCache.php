<?php namespace Rackage\Cache\Drivers;

/**
 * File-Based Cache Driver
 *
 * Provides file-based caching with expiration support.
 * Stores cache data as serialized files on disk.
 *
 * Features:
 *   - Simple, no dependencies
 *   - Automatic expiration handling
 *   - MD5-hashed keys for filesystem safety
 *   - Auto-creates cache directory
 *
 * Best for:
 *   - Development environments
 *   - Small to medium applications
 *   - Shared hosting (no Redis/Memcached)
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Cache
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

use Rackage\Path;

class FileCache {

    /**
     * Cache directory path
     *
     * @var string
     */
    private $cacheDir = null;

    /**
     * Initialize file cache driver
     *
     * Sets up cache directory from config or uses default.
     *
     * @param array $options Configuration options with 'path' key
     * @return void
     */
    public function __construct(array $options = [])
    {
        // Use configured path or default to vault/cache
        if (isset($options['path']))
        {
            $this->cacheDir = rtrim($options['path'], '/\\') . DIRECTORY_SEPARATOR;
        }
        else
        {
            $this->cacheDir = Path::vault() . 'cache' . DIRECTORY_SEPARATOR;
        }

        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir))
        {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get cache directory path
     *
     * @return string Cache directory path
     */
    private function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * Get file path for cache key
     *
     * Converts cache key to MD5 hash for filesystem compatibility.
     *
     * @param string $key Cache key
     * @return string Full file path
     */
    private function getFilePath($key)
    {
        return $this->getCacheDir() . md5($key) . '.cache';
    }

    /**
     * Get cached value
     *
     * Retrieves value from cache if it exists and hasn't expired.
     * Automatically deletes expired cache entries.
     *
     * @param string $key Cache key
     * @return mixed Cached value or false if not found or expired
     */
    public function get($key)
    {
        $file = $this->getFilePath($key);

        // Check if cache file exists
        if (!file_exists($file))
        {
            return false;
        }

        // Read and unserialize cache file
        $content = file_get_contents($file);
        $data = unserialize($content, ['allowed_classes' => false]);

        // Check if data structure is valid
        if (!is_array($data) || !isset($data['expires']) || !isset($data['value']))
        {
            $this->delete($key);
            return false;
        }

        // Check if cache has expired
        if ($data['expires'] < time())
        {
            $this->delete($key);
            return false;
        }

        return $data['value'];
    }

    /**
     * Store value in cache
     *
     * Serializes and stores value with expiration timestamp.
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $minutes Cache duration in minutes (default: 60)
     * @return bool True on success, false on failure
     */
    public function set($key, $value, $minutes = 60)
    {
        $file = $this->getFilePath($key);

        // Prepare cache data with expiration
        $data = [
            'value' => $value,
            'expires' => time() + ($minutes * 60),
            'created' => time()
        ];

        // Write to cache file
        $result = file_put_contents($file, serialize($data));

        // Log error if write failed
        if ($result === false)
        {
            error_log("FileCache: Failed to write cache key: $key");
            return false;
        }

        return true;
    }

    /**
     * Check if cache key exists and is valid
     *
     * @param string $key Cache key
     * @return bool True if exists and not expired
     */
    public function has($key)
    {
        return $this->get($key) !== false;
    }

    /**
     * Delete cached value
     *
     * Removes cache file from disk.
     *
     * @param string $key Cache key
     * @return bool True on success, false if file doesn't exist
     */
    public function delete($key)
    {
        $file = $this->getFilePath($key);

        if (file_exists($file))
        {
            return unlink($file);
        }

        return false;
    }

    /**
     * Clear all cached values
     *
     * Removes all cache files from the cache directory.
     *
     * @return bool True on success
     */
    public function flush()
    {
        $cacheDir = $this->getCacheDir();
        $files = glob($cacheDir . '*.cache');

        // No files to delete
        if (empty($files))
        {
            return true;
        }

        // Delete each cache file
        foreach ($files as $file)
        {
            if (is_file($file))
            {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Get or set cached value using callback
     *
     * Retrieves cached value if exists, otherwise executes callback,
     * caches the result, and returns it.
     *
     * Example:
     *   $users = $cache->remember('all_users', 60, function() {
     *       return Users::all();
     *   });
     *
     * @param string $key Cache key
     * @param int $minutes Cache duration in minutes
     * @param callable $callback Function to execute if cache misses
     * @return mixed Cached or fresh value
     */
    public function remember($key, $minutes, $callback)
    {
        // Try to get from cache
        $value = $this->get($key);

        // Return cached value if found
        if ($value !== false)
        {
            return $value;
        }

        // Execute callback to get fresh value
        $value = $callback();

        // Store in cache
        $this->set($key, $value, $minutes);

        return $value;
    }

    /**
     * Store value in cache permanently
     *
     * Caches value for 1 year (effectively permanent).
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @return bool True on success
     */
    public function forever($key, $value)
    {
        return $this->set($key, $value, 525600); // 1 year in minutes
    }
}
