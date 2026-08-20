<?php namespace Rackage;

/**
 * Cache Facade
 *
 * Static facade providing convenient access to the cache driver.
 * Delegates all method calls to the underlying cache driver instance
 * (FileCache, Memcached, or RedisCache) managed by Registry.
 *
 * Supported Drivers:
 *   - file:      File-based caching (default)
 *   - memcached: Fast in-memory caching
 *   - redis:     Advanced in-memory caching
 *
 * Configuration:
 *   Driver is configured in config/cache.php
 *
 * Usage:
 *   Cache::set('user_123', $user, 60);
 *   $user = Cache::get('user_123');
 *   Cache::delete('user_123');
 *   Cache::flush();
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Cache
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

class Cache {

    /**
     * Cache driver instance (singleton)
     *
     * @var object
     */
    private static $instance = null;

    /**
     * Prevent instantiation
     */
    private function __construct() {}

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Get cache driver instance
     *
     * Lazy-loads cache driver from Registry on first access.
     * Driver instance is cached for subsequent calls.
     *
     * @return object Cache driver instance
     */
    private static function getInstance() {
        if (self::$instance === null) {
            self::$instance = Registry::get('cache');
        }

        return self::$instance;
    }

    /**
     * Get cached value
     *
     * @param string $key Cache key
     * @return mixed Cached value or false if not found
     */
    public static function get($key) {
        return self::getInstance()->get($key);
    }

    /**
     * Store value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $minutes Cache duration in minutes (default: 60)
     * @return bool True on success
     */
    public static function set($key, $value, $minutes = 60) {
        return self::getInstance()->set($key, $value, $minutes);
    }

    /**
     * Check if cache key exists
     *
     * @param string $key Cache key
     * @return bool True if exists and not expired
     */
    public static function has($key) {
        return self::getInstance()->has($key);
    }

    /**
     * Delete cached value
     *
     * @param string $key Cache key
     * @return bool True on success
     */
    public static function delete($key) {
        return self::getInstance()->delete($key);
    }

    /**
     * Clear all cached values
     *
     * @return bool True on success
     */
    public static function flush() {
        return self::getInstance()->flush();
    }

    /**
     * Get or set cached value using callback
     *
     * Retrieves cached value if exists, otherwise executes callback,
     * caches the result, and returns it.
     *
     * @param string $key Cache key
     * @param int $minutes Cache duration in minutes
     * @param callable $callback Function to execute if cache misses
     * @return mixed Cached or fresh value
     */
    public static function remember($key, $minutes, $callback) {
        return self::getInstance()->remember($key, $minutes, $callback);
    }

    /**
     * Store value permanently
     *
     * Caches value without expiration (or very long expiration).
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @return bool True on success
     */
    public static function forever($key, $value) {
        return self::getInstance()->forever($key, $value);
    }
}