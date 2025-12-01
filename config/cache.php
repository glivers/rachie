<?php
/**
 * Cache Configuration
 * 
 * Configure caching for improved application performance.
 * Caching stores frequently-accessed data to reduce database queries
 * and speed up page loads.
 * 
 * Supported drivers:
 *   - file:      Simple file-based caching (no setup required)
 *   - memcached: Fast in-memory caching (requires Memcached server)
 *   - redis:     Advanced in-memory caching (requires Redis server)
 * 
 * Quick Start:
 *   Set 'enabled' => true and use 'file' driver (works out of the box)
 * 
 * @category Configuration
 * @package  Rachie
 */

return array(

	// ===========================================================================
	// CACHE SETTINGS
	// ===========================================================================
	
	/**
	 * Enable/Disable Caching
	 * 
	 * Controls whether caching is active for your application.
	 * 
	 * When TRUE:
	 *   - Cache queries, views, and data
	 *   - Faster page loads
	 *   - Reduced database load
	 * 
	 * When FALSE:
	 *   - No caching (useful during development)
	 *   - Always fetches fresh data
	 * 
	 * Recommended: TRUE in production, FALSE during active development
	 */
	'enabled' => false,

	/**
	 * Default Cache Driver
	 * 
	 * The caching system to use by default.
	 * 
	 * Options:
	 *   'file'      - File-based cache (no setup, works immediately)
	 *   'memcached' - Fast, requires Memcached server
	 *   'redis'     - Advanced, requires Redis server
	 * 
	 * Recommendation:
	 *   Development:  'file'
	 *   Production:   'redis' or 'memcached' (if available)
	 */
	'default' => 'file',

	/**
	 * Cache Lifetime (seconds)
	 *
	 * Default duration to keep cached items before they expire.
	 * Individual cache calls can override this.
	 *
	 * Common values:
	 *   60      - 1 minute
	 *   300     - 5 minutes
	 *   3600    - 1 hour
	 *   86400   - 24 hours
	 */
	'lifetime' => 3600,

	/**
	 * HTTP Methods to Cache
	 *
	 * Only cache responses for these HTTP request methods.
	 *
	 * GET and HEAD are safe methods that don't modify data,
	 * making them ideal for caching.
	 *
	 * Never cache:
	 *   - POST (creates data)
	 *   - PUT (updates data)
	 *   - DELETE (removes data)
	 *   - PATCH (modifies data)
	 *
	 * Default: ['GET', 'HEAD']
	 */
	'methods' => ['GET', 'HEAD'],

	/**
	 * Excluded URLs (Do Not Cache)
	 *
	 * URL patterns that should never be cached.
	 * Supports exact matches and wildcards.
	 *
	 * Common exclusions:
	 *   - Admin areas (dynamic, user-specific)
	 *   - User dashboards (personalized content)
	 *   - API endpoints (often need fresh data)
	 *   - Authentication pages (security)
	 *   - Cart/checkout (transactional)
	 *
	 * Patterns:
	 *   '/admin'      - Exact match only
	 *   '/admin/*'    - Admin and all subpages
	 *   '/user/*'     - All user pages
	 *   '/api/*'      - All API endpoints
	 *
	 * Note: Matching happens against the full URL path
	 */
	'exclude_urls' => [
		'/admin',
		'/admin/*',
		'/user',
		'/user/*',
		'/api',
		'/api/*',
	],

	// ===========================================================================
	// DRIVER CONFIGURATIONS
	// ===========================================================================
	
	/**
	 * Individual Cache Driver Settings
	 * 
	 * Configure connection details for each caching driver.
	 * Only configure the driver(s) you plan to use.
	 */
	'drivers' => array(

		/**
		 * File-Based Cache (Default)
		 * 
		 * Stores cache data as files on disk.
		 * 
		 * Pros:
		 *   - No additional software required
		 *   - Works immediately out of the box
		 *   - Simple and reliable
		 * 
		 * Cons:
		 *   - Slower than memory-based caching
		 *   - Can fill up disk space if not managed
		 * 
		 * Best for: Development, small apps, shared hosting
		 * 
		 * Ensure vault/cache directory:
		 *   - Exists
		 *   - Is writable (permissions: 755)
		 *   - Is outside public directory
		 */
		'file' => array(
			'path' => 'vault/cache',         // Cache storage directory
		),

		/**
		 * Memcached Cache
		 * 
		 * Fast in-memory caching using Memcached server.
		 * 
		 * Pros:
		 *   - Very fast (data stored in RAM)
		 *   - Scales well
		 *   - Automatic expiration
		 * 
		 * Cons:
		 *   - Requires Memcached server
		 *   - Data lost on restart
		 *   - Additional setup
		 * 
		 * Best for: Production apps with high traffic
		 * 
		 * Requirements:
		 *   - Memcached server installed
		 *   - PHP memcached extension
		 * 
		 * Default Memcached port: 11211
		 */
		'memcached' => array(
			'host'   => '127.0.0.1',         // Memcached server address
			'port'   => 11211,               // Memcached port (default: 11211)
			'weight' => 100,                 // Server weight (for multiple servers)
		),

		/**
		 * Redis Cache
		 * 
		 * Advanced in-memory caching with persistence options.
		 * 
		 * Pros:
		 *   - Extremely fast (in-memory)
		 *   - Rich data structures
		 *   - Optional persistence
		 *   - Advanced features (pub/sub, transactions)
		 * 
		 * Cons:
		 *   - Requires Redis server
		 *   - More complex than file cache
		 *   - Additional setup
		 * 
		 * Best for: Large production apps, real-time features
		 * 
		 * Requirements:
		 *   - Redis server installed
		 *   - PHP redis extension or Predis library
		 * 
		 * Default Redis port: 6379
		 * 
		 * Socket vs TCP:
		 *   Use 'socket' for local Redis (faster)
		 *   Use 'host'+'port' for remote Redis
		 */
		'redis' => array(
			'host'     => '127.0.0.1',       // Redis server address
			'port'     => 6379,              // Redis port (default: 6379)
			'password' => '',                // Redis password (if auth enabled)
			'database' => 0,                 // Redis database number (0-15)
			'socket'   => '',                // Unix socket path (alternative to host+port)
			                                 // Example: '/var/run/redis/redis.sock'
		),

	),

);

/**
 * PERFORMANCE TIPS:
 * 
 * 1. Start with file caching
 *    - Easy to set up, no dependencies
 *    - Good enough for most small-to-medium apps
 * 
 * 2. Upgrade to Redis/Memcached when:
 *    - App gets high traffic
 *    - File cache becomes a bottleneck
 *    - You need advanced caching features
 * 
 * 3. What to cache:
 *    - Database query results
 *    - Compiled templates
 *    - API responses
 *    - Complex calculations
 *    - Session data
 * 
 * 4. What NOT to cache:
 *    - User-specific data (unless per-user caching)
 *    - Frequently-changing data
 *    - Large binary files
 * 
 * 5. Cache invalidation:
 *    - Clear cache when data changes
 *    - Use appropriate expiration times
 *    - Consider cache tags/groups for batch clearing
 */

/**
 * SECURITY NOTES:
 * 
 * 1. File cache directory (vault/cache):
 *    - Must be outside public directory
 *    - Set proper permissions (755)
 *    - Add to .gitignore
 * 
 * 2. Redis/Memcached:
 *    - Use passwords in production
 *    - Bind to localhost unless needed externally
 *    - Use firewall rules to restrict access
 *    - Never expose to public internet without authentication
 * 
 * 3. Sensitive data:
 *    - Don't cache passwords or tokens
 *    - Be careful with personal information
 *    - Consider encrypting cached data if sensitive
 */