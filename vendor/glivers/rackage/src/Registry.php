<?php namespace Rackage;

/**
 * Registry - Service Locator and Configuration Container
 *
 * This class implements the Singleton pattern to manage application-wide
 * resources and configuration. It stores object instances, configuration
 * settings, and provides lazy-loading of framework resources.
 *
 * Purpose:
 *   - Store singleton instances (database, cache, template)
 *   - Manage application configuration from config files
 *   - Provide centralized access to framework resources
 *   - Prevent duplicate object instantiation
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo 
 * @category Rackage
 * @package Rachie\Rackage
 */

use Rackage\Database\Database;
use Rackage\Cache\CacheHandler;
use Rackage\Templates\Template;

class Registry {

	/**
	 * Application start time in microseconds
	 * Used for performance profiling and request execution time tracking
	 * 
	 * @var float
	 */
	public static $rachie_app_start;

	/**
	 * Stored object instances (singleton registry)
	 * Stores instantiated objects to avoid duplicate creation
	 * 
	 * Format: ['database' => DbInstance, 'cache' => CacheInstance, ...]
	 * 
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Application settings (from settings.php)
	 * Contains general application configuration
	 * 
	 * @var array
	 */
	private static $settings = array();

	/**
	 * Application security configuration (from security.php)
	 * Contains specific header settings and ip blacklists
	 * 
	 * @var array
	 */
	private static $security;

	/**
	 * Database configuration (from database.php)
	 * Contains database connection settings for all drivers
	 * 
	 * @var array
	 */
	private static $database = array();

	/**
	 * Cache configuration (from cache.php)
	 * Contains cache driver settings
	 *
	 * @var array
	 */
	private static $cache = array();

	/**
	 * Flag indicating if current page should be cached
	 * Set by Router after caching decision, read by View
	 *
	 * @var bool
	 */
	private static $shouldCache = false;

	/**
	 * Mail configuration (from mail.php)
	 * Contains email/SMTP settings
	 *
	 * @var array
	 */
	private static $mail = array();

	/**
	 * Current request URL string
	 * Stored for routing and URL generation throughout the request lifecycle
	 * 
	 * @var string
	 */
	private static $url = '';

	/**
	 * Registered framework resources
	 * List of resources that can be lazy-loaded via Registry::get()
	 *
	 * @var array
	 */
	private static $resources = array(
		'database-sync',
		'database-async',
		'database-stream',
		'database-fresh',
		'database-server',
		'cache',
		'template',
	);

	/**
	 * Private constructor to prevent direct instantiation
	 * Enforces singleton pattern
	 *
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Private clone method to prevent object cloning
	 * Enforces singleton pattern
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Get a resource instance (lazy-loaded singleton)
	 *
	 * Retrieves or creates an instance of a framework resource.
	 * If the resource already exists, returns the cached instance.
	 * If not, creates it, caches it, then returns it.
	 *
	 * Usage:
	 *   $db = Registry::get('database-sync');
	 *   $cache = Registry::get('cache');
	 *   $template = Registry::get('template');
	 *   $freshDb = Registry::get('database-fresh', 'fresh');  // Bypass cache
	 *
	 * @param string $key Resource name ('database-sync', 'cache', 'template')
	 * @param string $mode Optional mode flag ('fresh' to bypass caching)
	 * @return object The resource instance
	 * @throws \InvalidArgumentException If no resource name provided or resource not registered
	 * @throws \RuntimeException If resource creation fails
	 */
	public static function get($key, $mode = null)
	{
		// Validate that a resource name was provided
		if (func_num_args() == 0) {
			throw new \InvalidArgumentException('Registry::get() requires a resource name');
		}

		// Get any additional arguments passed (beyond $key and $mode)
		$args = array_slice(func_get_args(), 2);

		// Fresh mode bypasses cache - always create new instance
		if ($mode === 'fresh') {
			// Check if this is a registered resource
			if (!in_array($key, static::$resources)) {
				throw new \InvalidArgumentException("Resource '{$key}' is not registered in Registry");
			}

			// Create and return new instance without caching
			$instance = static::getInstance($key, $args);

			if (!$instance) {
				throw new \RuntimeException("Failed to create instance of resource '{$key}'");
			}

			return $instance;
		}

		// Return cached instance if it exists
		if (isset(static::$instances[$key])) {
			return static::$instances[$key];
		}

		// Check if this is a registered resource
		if (!in_array($key, static::$resources)) {
			throw new \InvalidArgumentException("Resource '{$key}' is not registered in Registry");
		}

		// Create the resource instance
		$instance = static::getInstance($key, $args);

		if (!$instance) {
			throw new \RuntimeException("Failed to create instance of resource '{$key}'");
		}

		// Cache and return the instance
		static::$instances[$key] = $instance;
		return static::$instances[$key];
	}

	/**
	 * Manually set/store an object instance
	 *
	 * Allows storing arbitrary object instances in the registry.
	 * Useful for dependency injection or storing custom singletons.
	 *
	 * @param string $key Identifier for the instance
	 * @param object $instance The object to store
	 * @return void
	 */
	public static function set($key, $instance)
	{		
		static::$instances[$key] = $instance;
	}

	/**
	 * Set application settings (from settings.php)
	 * 
	 * If $settings is provided then set the configuration array, otherwise return
	 * the currently set $settings array to caller.
	 *
	 * @param array $settings Application configuration array
	 * @return self For method chaining
	 */
	public static function settings($settings  = null)
	{
		if($settings) {

			static::$settings = $settings;
			return new static;
		}
		else return static::$settings;
	}

	/**
	 * Set cache configuration (from cache.php)
	 * 
	 * If the $cache configuration is provided set it, otherwise return the currently
	 * set configuration for the appication cache.
	 *
	 * @param array $cache Cache configuration array
	 * @return self For method chaining
	 */
	public static function cacheConfig($cache = null)
	{
		if($cache) {

			static::$cache = $cache;
			return new static;
		}
		else return static::$cache;
	}

	/**
	 * Set mail configuration (from mail.php)
	 *
	 * @param array $mail Mail configuration array
	 * @return self For method chaining
	 */
	public static function mailConfig($mail = null)
	{
		if($mail) {

			static::$mail = $mail;
			return new static;
		}
		else return static::$mail;
	}

	/**
	 * Set security configuration (from security.php)
	 * 
	 * If $security is provided it sets the current security settings,
	 * otherwise returns the current security settings.
	 *
	 * @param array $security Security configuration array
	 * @return self For method chaining
	 */
	public static function securityConfig($security = null)
	{
		if($security) {

			static::$security = $security;
			return new static;
		}
		else return static::$security;
	}

	/**
	 * Set the current request URL
	 *
	 * @param string $url The URL string for this request
	 * @return self For method chaining
	 */
	public static function setUrl($url)
	{
		static::$url = $url;
		return new static;
	}

	/**
	 * Set whether current page should be cached
	 *
	 * @param bool $shouldCache True if page should be cached
	 * @return void
	 */
	public static function setShouldCache($shouldCache)
	{
		static::$shouldCache = $shouldCache;
	}
    
    /**
     * Shorthand setter and getter for database configuration.
	 * 
	 * Same method used for both operations. If the confugration is provided it sets it
	 * then returns the static class. Otherwise it returns what's inside $database.
     * 
	 * @param array $database Configuration of the database
     * @return array Database configuration if no param is supplied
     */
    public static function dbConfig($database = null)
    {
		if($database) {

			static::$database = $database;
			return new static;
		}
		else return static::$database;
    }
    
    /**
     * Shorthand getter for cache config
     * 
     * @return array Cache configuration
     */
    public static function cache()
    {
        return static::$cache;
    }    
    
    /**
     * Shorthand getter for URL
     *
     * @return string Current request URL
     */
    public static function url()
    {
        return static::$url;
    }

    /**
     * Shorthand getter for shouldCache flag
     *
     * @return bool Whether current page should be cached
     */
    public static function shouldCache()
    {
        return static::$shouldCache;
    }
    

	/**
	 * Create a resource instance (internal factory method)
	 *
	 * Dynamically calls the appropriate get{Resource} method
	 * to create the requested resource instance.
	 *
	 * @param string $key Resource name
	 * @param array $args Additional arguments
	 * @return object Resource instance
	 */
	private static function getInstance($key, $args)
	{
		// Convert hyphenated keys to camelCase method names
		// 'database-async' => 'DatabaseAsync'
		// 'database-stream' => 'DatabaseStream'
		$parts = explode('-', $key);
		$camelCase = implode('', array_map('ucfirst', $parts));

		// Build method name: 'database' => 'getDatabaseInstance'
		$method = 'get' . $camelCase . 'Instance';

		// Call the factory method
		return static::$method($args);
	}

	/**
	 * Create template engine instance
	 *
	 * @param array $args Additional arguments (unused)
	 * @return BaseTemplateClass
	 */
	private static function getTemplateInstance($args)
	{
		return new BaseTemplateClass();
	}

	/**
	 * Create sync database connection instance
	 *
	 * Creates the main database connection used for synchronous queries.
	 * This connection is cached as a singleton.
	 *
	 * @param array $args Additional arguments (unused)
	 * @return object Database connection instance
	 * @throws \RuntimeException If database config not loaded
	 */
	private static function getDatabaseSyncInstance($args)
	{
		// Get stored database configuration
		$config = static::$database;

		if (empty($config)) {
			throw new \RuntimeException('Database configuration not loaded. Call Registry::dbConfig() first.');
		}

		// Create and connect database instance
		$instance = new Database(
			$config['default'],
			$config[$config['default']]
		);

		return $instance->initialize()->connect();
	}

	/**
	 * Create async database connection instance
	 *
	 * Creates a separate database connection dedicated to async queries.
	 * This connection is cached as a singleton separate from the main connection.
	 *
	 * @param array $args Additional arguments (unused)
	 * @return object Database connection instance
	 * @throws \RuntimeException If database config not loaded
	 */
	private static function getDatabaseAsyncInstance($args)
	{
		// Get stored database configuration
		$config = static::$database;

		if (empty($config)) {
			throw new \RuntimeException('Database configuration not loaded. Call Registry::dbConfig() first.');
		}

		// Create and connect database instance
		$instance = new Database(
			$config['default'],
			$config[$config['default']]
		);

		return $instance->initialize()->connect();
	}

	/**
	 * Create streaming database connection instance
	 *
	 * Creates a separate database connection dedicated to unbuffered/streaming queries.
	 * This connection is cached as a singleton separate from the main connection.
	 *
	 * @param array $args Additional arguments (unused)
	 * @return object Database connection instance
	 * @throws \RuntimeException If database config not loaded
	 */
	private static function getDatabaseStreamInstance($args)
	{
		// Get stored database configuration
		$config = static::$database;

		if (empty($config)) {
			throw new \RuntimeException('Database configuration not loaded. Call Registry::dbConfig() first.');
		}

		// Create and connect database instance
		$instance = new Database(
			$config['default'],
			$config[$config['default']]
		);

		return $instance->initialize()->connect();
	}

	/**
	 * Create fresh database connection instance
	 *
	 * Creates a new database connection that is NOT cached. Each call returns
	 * a completely new connection instance, allowing unlimited concurrent operations.
	 *
	 * Note: Unlike other connection types, this bypasses the singleton caching
	 * in Registry::get() and returns a fresh connection every time.
	 *
	 * @param array $args Additional arguments (unused)
	 * @return object Database connection instance
	 * @throws \RuntimeException If database config not loaded
	 */
	private static function getDatabaseFreshInstance($args)
	{
		// Get stored database configuration
		$config = static::$database;

		if (empty($config)) {
			throw new \RuntimeException('Database configuration not loaded. Call Registry::dbConfig() first.');
		}

		// Create and connect database instance
		$instance = new Database(
			$config['default'],
			$config[$config['default']]
		);

		return $instance->initialize()->connect();
	}

	/**
	 * Create server-level database connection instance (no database selected)
	 *
	 * Creates a database connection to the MySQL server without selecting a database.
	 * Used for database-level operations: CREATE DATABASE, DROP DATABASE, SHOW DATABASES.
	 * This connection is cached as a singleton separate from other connections.
	 *
	 * @param array $args Additional arguments (unused)
	 * @return object Database connection instance
	 * @throws \RuntimeException If database config not loaded
	 */
	private static function getDatabaseServerInstance($args)
	{
		// Get stored database configuration
		$config = static::$database;

		if (empty($config)) {
			throw new \RuntimeException('Database configuration not loaded. Call Registry::dbConfig() first.');
		}

		// Clone config and remove database parameter
		$serverConfig = $config[$config['default']];
		unset($serverConfig['database']);

		// Create and connect database instance without selecting a database
		$instance = new Database(
			$config['default'],
			$serverConfig
		);

		return $instance->initialize()->connect();
	}

	/**
	 * Create cache instance
	 *
	 * Creates appropriate cache driver based on configuration.
	 * Similar to getDatabaseInstance() pattern.
	 *
	 * @param array $args Additional arguments (unused)
	 * @return object Cache driver instance (FileCache, Memcached, or RedisCache)
	 * @throws \RuntimeException If cache config not loaded
	 */
	private static function getCacheInstance($args)
	{
		// Get stored cache configuration
		$config = static::$cache;

		if (empty($config)) {
			throw new \RuntimeException('Cache configuration not loaded. Call Registry::cacheConfig() first.');
		}

		// Get default driver type
		$driver = $config['default'] ?? 'file';

		// Get driver-specific options
		$options = $config['drivers'][$driver] ?? [];

		// Create cache handler and initialize driver
		$instance = new CacheHandler($driver, $options);

		return $instance->initialize();
	}

	/**
	 * Remove an instance from the registry
	 *
	 * Useful for clearing cached instances or freeing memory.
	 *
	 * @param string $key The instance identifier to remove
	 * @return void
	 */
	public static function erase($key)
	{
		unset(static::$instances[$key]);
	}

}