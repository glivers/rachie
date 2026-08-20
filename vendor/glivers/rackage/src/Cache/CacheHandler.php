<?php namespace Rackage\Cache;

/**
 * Cache Handler
 *
 * Gateway class that initializes and returns the appropriate cache driver
 * based on configuration settings.
 *
 * Supported Drivers:
 *   - file:      File-based caching (no setup required)
 *   - memcached: Fast in-memory caching (requires Memcached server)
 *   - redis:     Advanced in-memory caching (requires Redis server)
 *
 * Architecture:
 *   Similar to Database class - reads config, creates driver instance.
 *   Stored in Registry as singleton for application-wide access.
 *
 * Usage (via Registry):
 *   $cache = Registry::get('cache');
 *   $cache->set('key', 'value', 60);
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Cache
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

use Rackage\Cache\Drivers\FileCache;
use Rackage\Cache\Drivers\Memcached;
use Rackage\Cache\Drivers\RedisCache;
use Rackage\Cache\CacheException;

class CacheHandler {

    /**
     * Cache driver type (file, memcached, redis)
     *
     * @var string
     */
    protected $type;

    /**
     * Cache driver configuration options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Cache driver instance
     *
     * @var object
     */
    protected $driver = null;

    /**
     * Initialize cache handler
     *
     * Stores cache type and configuration for later initialization.
     *
     * @param string $type Cache driver type (file, memcached, redis)
     * @param array $options Driver configuration options
     */
    public function __construct($type, array $options = [])
    {
        $this->type = $type;
        $this->options = $options;
    }

    /**
     * Initialize and return cache driver instance
     *
     * Creates the appropriate cache driver based on configuration.
     * Called by Registry when cache is first requested.
     *
     * Flow:
     *   1. Validate cache type is set
     *   2. Switch on cache type
     *   3. Create driver instance with config options
     *   4. For memcached/redis: connect to server
     *   5. Return driver instance
     *
     * @return FileCache|Memcached|RedisCache Cache driver instance
     * @throws CacheException If invalid cache type
     */
    public function initialize()
    {
        // Validate cache type is set
        if (!$this->type)
        {
            throw new CacheException("Invalid cache driver type supplied");
        }

        // Create appropriate driver based on type
        switch ($this->type)
        {
            case 'file':
                // File cache - no connection needed
                $this->driver = new FileCache($this->options);
                return $this->driver;

            case 'memcached':
                // Memcached - requires connection
                $this->driver = new Memcached($this->options);
                return $this->driver->connect();

            case 'redis':
                // Redis - requires connection
                $this->driver = new RedisCache($this->options);
                return $this->driver->connect();

            default:
                throw new CacheException("Unsupported cache driver type: {$this->type}");
        }
    }

    /**
     * Get cache driver instance
     *
     * Returns initialized driver, or initializes if not yet done.
     *
     * @return object Cache driver instance
     */
    public function getDriver()
    {
        if ($this->driver === null)
        {
            $this->initialize();
        }

        return $this->driver;
    }
}
