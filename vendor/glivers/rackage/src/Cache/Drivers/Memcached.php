<?php namespace Rackage\Cache\Drivers;

/**
 * Memcached Cache Driver
 *
 * Fast in-memory caching using Memcached server.
 * Provides high-performance key-value storage with automatic expiration.
 *
 * Features:
 *   - Very fast (data stored in RAM)
 *   - Automatic expiration handling
 *   - Distributed caching support
 *   - LRU eviction when memory full
 *
 * Requirements:
 *   - Memcached server installed and running
 *   - PHP memcached extension (libmemcached)
 *
 * Best for:
 *   - High-traffic production applications
 *   - Session storage
 *   - Frequently-accessed data
 *   - Multi-server environments
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Cache
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

class Memcached {

    /**
     * Memcached connection instance
     *
     * @var \Memcached
     */
    protected $service;

    /**
     * Memcached server hostname or IP
     *
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * Memcached server port
     *
     * @var int
     */
    protected $port = 11211;

    /**
     * Server weight for load balancing
     *
     * Used when connecting to multiple Memcached servers.
     * Higher weight = more requests sent to this server.
     *
     * @var int
     */
    protected $weight = 100;

    /**
     * Connection status flag
     *
     * @var bool
     */
    protected $connected = false;

    /**
     * Initialize Memcached cache driver
     *
     * Stores connection parameters from configuration.
     *
     * @param array $options Memcached connection configuration
     */
    public function __construct(array $options)
    {
        // Set connection parameters
        $this->host = $options['host'] ?? '127.0.0.1';
        $this->port = $options['port'] ?? 11211;
        $this->weight = $options['weight'] ?? 100;
    }

    /**
     * Validate Memcached connection status
     *
     * Checks if service instance is valid and connected.
     *
     * @return bool True if connected and valid
     */
    protected function validService()
    {
        // Check service instance exists
        $empty = empty($this->service);

        // Check if instance of Memcached class
        $instance = $this->service instanceof \Memcached;

        // Return true if connected, is Memcached instance, and not empty
        if ($this->connected && $instance && !$empty)
        {
            return true;
        }

        return false;
    }

    /**
     * Connect to Memcached server
     *
     * Establishes connection to Memcached server and adds server to pool.
     *
     * @return self Chainable
     * @throws CacheException If connection fails
     */
    public function connect()
    {
        try
        {
            // Create Memcached instance
            $this->service = new \Memcached();

            // Add server to connection pool
            $added = $this->service->addServer($this->host, $this->port, $this->weight);

            // Check if server was added successfully
            if (!$added)
            {
                throw new CacheException(
                    "Unable to connect to Memcached server at {$this->host}:{$this->port}"
                );
            }

            // Mark as connected
            $this->connected = true;

        }
        catch (\Exception $e)
        {
            throw new CacheException("Memcached connection error: " . $e->getMessage());
        }

        return $this;
    }

    /**
     * Disconnect from Memcached server
     *
     * Closes the Memcached connection gracefully.
     *
     * @return self Chainable
     */
    public function disconnect()
    {
        if ($this->validService())
        {
            // Close connection
            $this->service->quit();

            // Mark as disconnected
            $this->connected = false;
        }

        return $this;
    }

    /**
     * Get cached value
     *
     * Retrieves value from Memcached by key.
     * Returns false if key doesn't exist or has expired.
     *
     * @param string $key Cache key
     * @return mixed Cached value or false if not found
     * @throws CacheException If not connected
     */
    public function get($key)
    {
        try
        {
            // Require valid connection
            if (!$this->validService())
            {
                throw new CacheException("Not connected to a valid Memcached service");
            }

            // Get value from Memcached
            $value = $this->service->get($key);

            // Memcached returns false for non-existent keys
            if ($value === false && $this->service->getResultCode() == \Memcached::RES_NOTFOUND)
            {
                return false;
            }

            // Unserialize value
            return unserialize($value, ['allowed_classes' => false]);

        }
        catch (CacheException $exception)
        {
            $exception->errorShow();
            return false;
        }
    }

    /**
     * Store value in cache
     *
     * Sets key-value pair in Memcached with expiration.
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $minutes Cache duration in minutes
     * @return bool True on success
     * @throws CacheException If not connected
     */
    public function set($key, $value, $minutes = 60)
    {
        try
        {
            // Require valid connection
            if (!$this->validService())
            {
                throw new CacheException("Not connected to a valid Memcached service");
            }

            // Serialize value
            $serialized = serialize($value);

            // Calculate expiration in seconds
            $seconds = $minutes * 60;

            // Set value with expiration
            return $this->service->set($key, $serialized, $seconds);

        }
        catch (CacheException $exception)
        {
            $exception->errorShow();
            return false;
        }
    }

    /**
     * Check if cache key exists
     *
     * @param string $key Cache key
     * @return bool True if exists
     * @throws CacheException If not connected
     */
    public function has($key)
    {
        return $this->get($key) !== false;
    }

    /**
     * Delete cached value
     *
     * Removes key from Memcached.
     *
     * @param string $key Cache key
     * @return bool True if deleted, false if key didn't exist
     * @throws CacheException If not connected
     */
    public function delete($key)
    {
        try
        {
            // Require valid connection
            if (!$this->validService())
            {
                throw new CacheException("Not connected to a valid Memcached service");
            }

            // Delete key
            return $this->service->delete($key);

        }
        catch (CacheException $exception)
        {
            $exception->errorShow();
            return false;
        }
    }

    /**
     * Clear all cached values
     *
     * Flushes entire Memcached storage.
     * WARNING: This clears ALL keys in Memcached.
     *
     * @return bool True on success
     * @throws CacheException If not connected
     */
    public function flush()
    {
        try
        {
            // Require valid connection
            if (!$this->validService())
            {
                throw new CacheException("Not connected to a valid Memcached service");
            }

            // Flush all keys
            return $this->service->flush();

        }
        catch (CacheException $exception)
        {
            $exception->errorShow();
            return false;
        }
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
     * Store value permanently
     *
     * Sets value with maximum expiration (30 days - Memcached limit).
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @return bool True on success
     */
    public function forever($key, $value)
    {
        // Memcached max expiration is 30 days (2592000 seconds)
        return $this->set($key, $value, 43200); // 30 days in minutes
    }

    /**
     * Get Memcached server statistics
     *
     * Returns information about Memcached server and memory usage.
     *
     * @return array Server statistics
     */
    public function getStats()
    {
        if ($this->validService())
        {
            return $this->service->getStats();
        }

        return [];
    }
}
