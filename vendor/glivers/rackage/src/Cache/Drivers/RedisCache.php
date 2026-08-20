<?php namespace Rackage\Cache\Drivers;

/**
 * Redis Cache Driver
 *
 * Advanced in-memory caching using Redis server.
 * Provides fast key-value storage with rich data structures.
 *
 * Features:
 *   - Extremely fast (in-memory storage)
 *   - Optional persistence to disk
 *   - Rich data types (strings, lists, sets, hashes)
 *   - Atomic operations
 *   - Pub/sub support
 *
 * Requirements:
 *   - Redis server installed and running
 *   - PHP redis extension (phpredis)
 *
 * Best for:
 *   - High-traffic production applications
 *   - Real-time features
 *   - Session storage
 *   - Message queues
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Cache
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

class RedisCache {

    /**
     * Redis connection instance
     *
     * @var \Redis
     */
    protected $service;

    /**
     * Redis server hostname or IP
     *
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * Redis server port
     *
     * @var int
     */
    protected $port = 6379;

    /**
     * Redis authentication password
     *
     * @var string
     */
    protected $password = '';

    /**
     * Redis database number (0-15)
     *
     * @var int
     */
    protected $database = 0;

    /**
     * Unix socket path (alternative to host+port)
     *
     * @var string
     */
    protected $socket = '';

    /**
     * Connection status flag
     *
     * @var bool
     */
    protected $connected = false;

    /**
     * Initialize Redis cache driver
     *
     * Stores connection parameters from configuration.
     *
     * @param array $options Redis connection configuration
     */
    public function __construct(array $options)
    {
        // Set connection parameters
        $this->host = $options['host'] ?? '127.0.0.1';
        $this->port = $options['port'] ?? 6379;
        $this->password = $options['password'] ?? '';
        $this->database = $options['database'] ?? 0;
        $this->socket = $options['socket'] ?? '';
    }

    /**
     * Validate Redis connection status
     *
     * Checks if service instance is valid and connected.
     *
     * @return bool True if connected and valid
     */
    protected function validService()
    {
        // Check service instance exists
        $empty = empty($this->service);

        // Check if instance of Redis class
        $instance = $this->service instanceof \Redis;

        // Return true if connected, is Redis instance, and not empty
        if ($this->connected && $instance && !$empty) {
            return true;
        }

        return false;
    }

    /**
     * Connect to Redis server
     *
     * Establishes connection using either socket or TCP.
     * Automatically authenticates if password provided.
     * Selects database if specified.
     *
     * @return self Chainable
     * @throws CacheException If connection fails
     */
    public function connect()
    {
        try {
            // Create Redis instance
            $this->service = new \Redis();

            // Connect via Unix socket or TCP
            if (!empty($this->socket)) {
                // Unix socket connection (faster for local Redis)
                $connected = $this->service->connect($this->socket);
            } else {
                // TCP connection
                $connected = $this->service->connect($this->host, $this->port);
            }

            // Check connection status
            if (!$connected) {
                throw new CacheException(
                    "Unable to connect to Redis server at {$this->host}:{$this->port}"
                );
            }

            // Authenticate if password provided
            if (!empty($this->password)) {
                $auth = $this->service->auth($this->password);
                if (!$auth) {
                    throw new CacheException("Redis authentication failed");
                }
            }

            // Select database
            if ($this->database > 0) {
                $this->service->select($this->database);
            }

            // Mark as connected
            $this->connected = true;

        } catch (\RedisException $e) {
            throw new CacheException("Redis connection error: " . $e->getMessage());
        }

        return $this;
    }

    /**
     * Disconnect from Redis server
     *
     * Closes the Redis connection gracefully.
     *
     * @return self Chainable
     */
    public function disconnect()
    {
        if ($this->validService()) {
            // Close connection
            $this->service->close();

            // Mark as disconnected
            $this->connected = false;
        }

        return $this;
    }

    /**
     * Get cached value
     *
     * Retrieves value from Redis by key.
     * Returns false if key doesn't exist or has expired.
     *
     * @param string $key Cache key
     * @return mixed Cached value or false if not found
     * @throws CacheException If not connected
     */
    public function get($key)
    {
        try {
            // Require valid connection
            if (!$this->validService()) {
                throw new CacheException("Not connected to a valid Redis service");
            }

            // Get value from Redis
            $value = $this->service->get($key);

            // Redis returns false for non-existent keys
            if ($value === false) {
                return false;
            }

            // Unserialize value
            return unserialize($value, ['allowed_classes' => false]);

        } catch (CacheException $exception) {
            $exception->errorShow();
            return false;
        }
    }

    /**
     * Store value in cache
     *
     * Sets key-value pair in Redis with expiration.
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $minutes Cache duration in minutes
     * @return bool True on success
     * @throws CacheException If not connected
     */
    public function set($key, $value, $minutes = 60)
    {
        try {
            // Require valid connection
            if (!$this->validService()) {
                throw new CacheException("Not connected to a valid Redis service");
            }

            // Serialize value
            $serialized = serialize($value);

            // Calculate expiration in seconds
            $seconds = $minutes * 60;

            // Set value with expiration
            return $this->service->setex($key, $seconds, $serialized);

        } catch (CacheException $exception) {
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
        try {
            // Require valid connection
            if (!$this->validService()) {
                throw new CacheException("Not connected to a valid Redis service");
            }

            // Check if key exists
            return $this->service->exists($key) > 0;

        } catch (CacheException $exception) {
            $exception->errorShow();
            return false;
        }
    }

    /**
     * Delete cached value
     *
     * Removes key from Redis.
     *
     * @param string $key Cache key
     * @return bool True if deleted, false if key didn't exist
     * @throws CacheException If not connected
     */
    public function delete($key)
    {
        try {
            // Require valid connection
            if (!$this->validService()) {
                throw new CacheException("Not connected to a valid Redis service");
            }

            // Delete key (returns number of keys deleted)
            return $this->service->del($key) > 0;

        } catch (CacheException $exception) {
            $exception->errorShow();
            return false;
        }
    }

    /**
     * Clear all cached values
     *
     * Flushes entire Redis database.
     * WARNING: This clears ALL keys in the selected database.
     *
     * @return bool True on success
     * @throws CacheException If not connected
     */
    public function flush()
    {
        try {
            // Require valid connection
            if (!$this->validService()) {
                throw new CacheException("Not connected to a valid Redis service");
            }

            // Flush current database
            return $this->service->flushDB();

        } catch (CacheException $exception) {
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
        if ($value !== false) {
            return $value;
        }

        // Execute callback to get fresh value
        $value = $callback();

        // Store in cache
        $this->set($key, $value, $minutes);

        // Return fresh value
        return $value;
    }

    /**
     * Store value permanently
     *
     * Sets value without expiration.
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @return bool True on success
     */
    public function forever($key, $value)
    {
        try {
            // Require valid connection
            if (!$this->validService()) {
                throw new CacheException("Not connected to a valid Redis service");
            }

            // Serialize and set without expiration
            $serialized = serialize($value);
            return $this->service->set($key, $serialized);

        } catch (CacheException $exception) {
            $exception->errorShow();
            return false;
        }
    }

    /**
     * Get Redis server statistics
     *
     * Returns information about Redis server and memory usage.
     *
     * @return array Server statistics
     */
    public function getStats()
    {
        if ($this->validService()) {
            return $this->service->info();
        }

        return [];
    }
}
