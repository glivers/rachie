<?php namespace Rackage;

/**
 * Request Helper
 *
 * Provides comprehensive HTTP request information including method detection,
 * headers, client data, content negotiation, and URL analysis.
 *
 * Static Design:
 *   All methods are static - no instance creation required.
 *
 * Access Patterns:
 *
 *   In Controllers:
 *     use Rackage\Request;
 *
 *     class ApiController extends Controller {
 *         public function store() {
 *             if (Request::isPost() && Request::isJson()) {
 *                 // Handle JSON POST request
 *             }
 *         }
 *     }
 *
 *   In Views:
 *     Request is automatically available (configured in view_helpers).
 *     No 'use' statement needed.
 *
 *     @if(Request::isMobile())
 *         <div class="mobile-menu"></div>
 *     @endif
 *
 * Usage Categories:
 *
 *   1. HTTP METHOD DETECTION
 *      - method()       Get HTTP method (GET, POST, etc.)
 *      - isGet()        Check if GET request
 *      - isPost()       Check if POST request
 *      - isPut()        Check if PUT request
 *      - isDelete()     Check if DELETE request
 *      - isPatch()      Check if PATCH request
 *      - isMethod()     Check if specific method
 *
 *   2. REQUEST TYPE CHECKS
 *      - ajax()         Check if AJAX request
 *      - isAjax()       Alias for ajax()
 *      - secure()       Check if HTTPS
 *      - isSecure()     Alias for secure()
 *
 *   3. CONTENT NEGOTIATION
 *      - isJson()       Check if JSON request
 *      - expectsJson()  Check if client expects JSON response
 *      - wantsJson()    Check if client prefers JSON
 *      - accepts()      Check if client accepts content type
 *      - contentType()  Get Content-Type header
 *
 *   4. URL & PATH
 *      - path()         Get request path (without query string)
 *      - url()          Get full URL
 *      - fullUrl()      Get full URL with query string
 *      - fullUri()      Get request URI (path + query, no domain)
 *      - is()           Check if path matches pattern
 *      - segment()      Get URL segment by index
 *      - segments()     Get all URL segments
 *
 *   5. QUERY PARAMETERS
 *      - query()        Get query parameter
 *      - hasQuery()     Check if query parameter exists
 *      - queryString()  Get full query string
 *
 *   6. HEADERS
 *      - header()       Get request header
 *      - headers()      Get all headers
 *      - hasHeader()    Check if header exists
 *      - bearer()       Get Bearer token from Authorization header
 *
 *   7. CLIENT INFORMATION
 *      - ip()           Get client IP address
 *      - agent()        Get user agent string
 *      - referer()      Get HTTP referer
 *      - isMobile()     Check if mobile device
 *      - isBot()        Check if search bot
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Request
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */
class Request {

    /**
     * Cached headers array
     * @var array|null
     */
    private static $cachedHeaders = null;

    /**
     * Private constructor to prevent instantiation
     * @return void
     */
    private function __construct() {}

    /**
     * Prevent cloning
     * @return void
     */
    private function __clone() {}

    // =========================================================================
    // HTTP METHOD DETECTION
    // =========================================================================

    /**
     * Get the HTTP request method
     *
     * Returns the HTTP method used for this request (GET, POST, PUT, DELETE, etc.).
     *
     * Examples:
     *   $method = Request::method();  // "GET", "POST", "PUT", etc.
     *
     *   if (Request::method() === 'POST') {
     *       // Handle POST request
     *   }
     *
     * @return string The HTTP request method (uppercase)
     */
    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Check if request method is GET
     *
     * Examples:
     *   if (Request::isGet()) {
     *       // Display form
     *   }
     *
     * @return bool True if GET request
     */
    public static function isGet()
    {
        return self::method() === 'GET';
    }

    /**
     * Check if request method is POST
     *
     * Examples:
     *   if (Request::isPost()) {
     *       // Process form submission
     *   }
     *
     * Usage in controller:
     *   public function store() {
     *       if (Request::isPost()) {
     *           $data = Input::post();
     *           Users::save($data);
     *       }
     *   }
     *
     * @return bool True if POST request
     */
    public static function isPost()
    {
        return self::method() === 'POST';
    }

    /**
     * Check if request method is PUT
     *
     * Examples:
     *   if (Request::isPut()) {
     *       // Update resource
     *   }
     *
     * @return bool True if PUT request
     */
    public static function isPut()
    {
        return self::method() === 'PUT';
    }

    /**
     * Check if request method is DELETE
     *
     * Examples:
     *   if (Request::isDelete()) {
     *       // Delete resource
     *   }
     *
     * @return bool True if DELETE request
     */
    public static function isDelete()
    {
        return self::method() === 'DELETE';
    }

    /**
     * Check if request method is PATCH
     *
     * Examples:
     *   if (Request::isPatch()) {
     *       // Partially update resource
     *   }
     *
     * @return bool True if PATCH request
     */
    public static function isPatch()
    {
        return self::method() === 'PATCH';
    }

    /**
     * Check if request method matches given method
     *
     * Case-insensitive comparison.
     *
     * Examples:
     *   if (Request::isMethod('post')) {
     *       // Handle POST
     *   }
     *
     *   if (Request::isMethod('PUT')) {
     *       // Handle PUT
     *   }
     *
     * @param string $method Method to check (case-insensitive)
     * @return bool True if method matches
     */
    public static function isMethod($method)
    {
        return strtoupper($method) === self::method();
    }

    // =========================================================================
    // REQUEST TYPE CHECKS
    // =========================================================================

    /**
     * Check if the request is AJAX
     *
     * Checks for X-Requested-With: XMLHttpRequest header
     * (set by jQuery, Axios, and most AJAX libraries).
     *
     * Examples:
     *   if (Request::ajax()) {
     *       return View::json($data);
     *   } else {
     *       return View::render('page', $data);
     *   }
     *
     * Usage in API endpoints:
     *   public function getData() {
     *       if (!Request::ajax()) {
     *           http_response_code(403);
     *           die('AJAX only');
     *       }
     *       return View::json($results);
     *   }
     *
     * @return bool True if AJAX request
     */
    public static function ajax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Alias for ajax() - Check if AJAX request
     *
     * @return bool True if AJAX request
     */
    public static function isAjax()
    {
        return self::ajax();
    }

    /**
     * Check if the request is over HTTPS
     *
     * Checks both HTTPS server variable and port 443.
     *
     * Examples:
     *   if (Request::secure()) {
     *       // Connection is encrypted
     *   }
     *
     *   if (!Request::secure()) {
     *       redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
     *   }
     *
     * @return bool True if HTTPS
     */
    public static function secure()
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    }

    /**
     * Alias for secure() - Check if HTTPS
     *
     * @return bool True if HTTPS
     */
    public static function isSecure()
    {
        return self::secure();
    }

    // =========================================================================
    // CONTENT NEGOTIATION
    // =========================================================================

    /**
     * Check if request has JSON content type
     *
     * Checks if Content-Type header is application/json.
     * Use this to detect JSON request payloads.
     *
     * Examples:
     *   if (Request::isJson()) {
     *       $data = json_decode(file_get_contents('php://input'), true);
     *   }
     *
     * Usage in API controller:
     *   public function store() {
     *       if (Request::isJson()) {
     *           $input = json_decode(file_get_contents('php://input'), true);
     *           Users::save($input);
     *       }
     *   }
     *
     * @return bool True if JSON content type
     */
    public static function isJson()
    {
        $contentType = self::contentType();
        return $contentType && stripos($contentType, 'application/json') !== false;
    }

    /**
     * Check if client expects JSON response
     *
     * Checks Accept header for application/json.
     *
     * Examples:
     *   if (Request::expectsJson()) {
     *       return View::json($data);
     *   }
     *
     * @return bool True if client expects JSON
     */
    public static function expectsJson()
    {
        return self::accepts('application/json');
    }

    /**
     * Check if client wants JSON response
     *
     * Returns true if:
     * - Request is AJAX, OR
     * - Client accepts JSON
     *
     * Use this for automatic response format detection.
     *
     * Examples:
     *   if (Request::wantsJson()) {
     *       return View::json($data);
     *   } else {
     *       return View::render('page', $data);
     *   }
     *
     * @return bool True if client prefers JSON
     */
    public static function wantsJson()
    {
        return self::ajax() || self::expectsJson();
    }

    /**
     * Check if client accepts specific content type
     *
     * Checks Accept header for given content type.
     *
     * Examples:
     *   if (Request::accepts('application/json')) {
     *       return View::json($data);
     *   }
     *
     *   if (Request::accepts('text/html')) {
     *       return View::render('page');
     *   }
     *
     * @param string $type Content type to check (e.g., 'application/json')
     * @return bool True if client accepts content type
     */
    public static function accepts($type)
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return stripos($accept, $type) !== false || stripos($accept, '*/*') !== false;
    }

    /**
     * Get Content-Type header value
     *
     * Returns the Content-Type of the request (for POST/PUT payloads).
     *
     * Examples:
     *   $type = Request::contentType();  // "application/json"
     *
     *   if (Request::contentType() === 'application/xml') {
     *       // Parse XML
     *   }
     *
     * @return string|null Content-Type header or null
     */
    public static function contentType()
    {
        return $_SERVER['CONTENT_TYPE'] ?? null;
    }

    // =========================================================================
    // URL & PATH
    // =========================================================================

    /**
     * Get the request path without query string
     *
     * Returns the URL path portion without domain or query parameters.
     *
     * Examples:
     *   // URL: https://example.com/blog/post/123?page=2
     *   $path = Request::path();  // "/blog/post/123"
     *
     *   if (Request::path() === '/admin') {
     *       // Admin section
     *   }
     *
     * @return string The request path
     */
    public static function path()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        return $path;
    }

    /**
     * Get the full URL with protocol, host, and path
     *
     * Respects framework protocol setting from Registry.
     *
     * Examples:
     *   $url = Request::url();  // "https://example.com/blog/post/123"
     *
     * @return string The full URL (without query string)
     */
    public static function url()
    {
        $settings = Registry::settings();
        $protocol = $settings['protocol'] ?? 'auto';

        // Determine protocol
        if ($protocol === 'auto') {
            $protocol = self::secure() ? 'https' : 'http';
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = self::path();

        return $protocol . '://' . $host . $path;
    }

    /**
     * Get the full URL with query string
     *
     * Examples:
     *   $url = Request::fullUrl();
     *   // "https://example.com/blog/post/123?page=2&sort=date"
     *
     * @return string The full URL with query string
     */
    public static function fullUrl()
    {
        $settings = Registry::settings();
        $protocol = $settings['protocol'] ?? 'auto';

        // Determine protocol
        if ($protocol === 'auto') {
            $protocol = self::secure() ? 'https' : 'http';
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return $protocol . '://' . $host . $uri;
    }

    /**
     * Get the full request URI (path + query string)
     *
     * Returns the request URI without protocol or host.
     * Includes query string if present.
     *
     * Examples:
     *   Request::fullUri();  // "/search?q=kenya&page=2"
     *   Request::fullUri();  // "/blog/post/123"
     *   Request::fullUri();  // "/"
     *
     * @return string The request URI with query string
     */
    public static function fullUri()
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Check if request path matches a pattern
     *
     * Supports wildcard (*) matching.
     *
     * Examples:
     *   if (Request::is('admin/*')) {
     *       // Any admin route
     *   }
     *
     *   if (Request::is('api/* /users')) {
     *       // Matches: /api/v1/users, /api/v2/users
     *   }
     *
     *   if (Request::is('blog/post/*')) {
     *       // Any blog post
     *   }
     *
     * @param string $pattern Pattern to match (supports * wildcard)
     * @return bool True if path matches pattern
     */
    public static function is($pattern)
    {
        $path = self::path();
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = '#^' . $pattern . '$#';

        return (bool) preg_match($pattern, $path);
    }

    /**
     * Get URL segment by index
     *
     * Segments are 1-indexed (first segment is 1, not 0).
     *
     * Examples:
     *   // URL: /blog/post/123
     *   Request::segment(1);  // "blog"
     *   Request::segment(2);  // "post"
     *   Request::segment(3);  // "123"
     *   Request::segment(4);  // null
     *
     *   // With default value
     *   Request::segment(4, 'default');  // "default"
     *
     * @param int $index Segment index (1-indexed)
     * @param mixed $default Default value if segment doesn't exist
     * @return string|null Segment value or default
     */
    public static function segment($index, $default = null)
    {
        $segments = self::segments();
        return $segments[$index - 1] ?? $default;
    }

    /**
     * Get all URL segments
     *
     * Returns array of path segments (empty segments removed).
     *
     * Examples:
     *   // URL: /blog/post/123
     *   $segments = Request::segments();  // ["blog", "post", "123"]
     *
     *   foreach (Request::segments() as $segment) {
     *       echo $segment;
     *   }
     *
     * @return array Array of URL segments
     */
    public static function segments()
    {
        $path = trim(self::path(), '/');
        return $path === '' ? [] : explode('/', $path);
    }

    // =========================================================================
    // QUERY PARAMETERS
    // =========================================================================

    /**
     * Get query parameter value
     *
     * Alternative to Input::get() for specifically accessing query string.
     *
     * Examples:
     *   // URL: /search?q=test&page=2
     *   $query = Request::query('q');      // "test"
     *   $page = Request::query('page');    // "2"
     *   $sort = Request::query('sort', 'date');  // "date" (default)
     *
     * @param string $key Query parameter name
     * @param mixed $default Default value if parameter doesn't exist
     * @return mixed Query parameter value or default
     */
    public static function query($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Check if query parameter exists
     *
     * Examples:
     *   if (Request::hasQuery('page')) {
     *       $page = Request::query('page');
     *   }
     *
     * @param string $key Query parameter name
     * @return bool True if parameter exists
     */
    public static function hasQuery($key)
    {
        return isset($_GET[$key]);
    }

    /**
     * Get full query string
     *
     * Examples:
     *   // URL: /search?q=test&page=2
     *   $qs = Request::queryString();  // "q=test&page=2"
     *
     * @return string|null Query string or null
     */
    public static function queryString()
    {
        return $_SERVER['QUERY_STRING'] ?? null;
    }

    // =========================================================================
    // HEADERS
    // =========================================================================

    /**
     * Get request header value
     *
     * Header name is case-insensitive.
     *
     * Examples:
     *   $auth = Request::header('Authorization');
     *   $type = Request::header('Content-Type');
     *   $token = Request::header('X-CSRF-Token');
     *
     *   // With default value
     *   $lang = Request::header('Accept-Language', 'en');
     *
     * @param string $name Header name (case-insensitive)
     * @param mixed $default Default value if header not found
     * @return string|null Header value or default
     */
    public static function header($name, $default = null)
    {
        // Use headers() method for consistency (handles getallheaders() properly)
        $headers = self::headers();

        // Case-insensitive search
        foreach ($headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get all request headers
     *
     * Returns associative array of all headers.
     *
     * Examples:
     *   $headers = Request::headers();
     *   foreach ($headers as $name => $value) {
     *       echo "$name: $value";
     *   }
     *
     * @return array All request headers
     */
    public static function headers()
    {
        // Cache headers for performance
        if (self::$cachedHeaders !== null) {
            return self::$cachedHeaders;
        }

        $headers = [];

        // Use getallheaders() if available (Apache, nginx with PHP-FPM)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // Fallback: parse $_SERVER
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $headerName = str_replace('_', '-', substr($key, 5));
                    $headers[$headerName] = $value;
                } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                    $headerName = str_replace('_', '-', $key);
                    $headers[$headerName] = $value;
                }
            }
        }

        self::$cachedHeaders = $headers;
        return $headers;
    }

    /**
     * Check if request header exists
     *
     * Examples:
     *   if (Request::hasHeader('Authorization')) {
     *       $token = Request::header('Authorization');
     *   }
     *
     * @param string $name Header name (case-insensitive)
     * @return bool True if header exists
     */
    public static function hasHeader($name)
    {
        return self::header($name) !== null;
    }

    /**
     * Get Bearer token from Authorization header
     *
     * Extracts token from "Bearer {token}" authorization header.
     *
     * Examples:
     *   $token = Request::bearer();
     *
     *   if ($token = Request::bearer()) {
     *       // Validate token
     *       $user = Users::where('api_token', $token)->first();
     *   }
     *
     * @return string|null Bearer token or null
     */
    public static function bearer()
    {
        $authorization = self::header('Authorization');

        if ($authorization && stripos($authorization, 'Bearer ') === 0) {
            return trim(substr($authorization, 7));
        }

        return null;
    }

    // =========================================================================
    // CLIENT INFORMATION
    // =========================================================================

    /**
     * Get client IP address
     *
     * Checks multiple sources for IP address, prioritizing most reliable.
     * Handles proxy headers (X-Forwarded-For, X-Real-IP) but takes first IP only
     * to prevent spoofing.
     *
     * Examples:
     *   $ip = Request::ip();  // "192.168.1.100"
     *
     *   // Log IP for security
     *   ErrorLog::write('Login attempt from: ' . Request::ip());
     *
     * SECURITY NOTE:
     *   X-Forwarded-For can be spoofed. For high-security applications,
     *   only trust REMOTE_ADDR or configure trusted proxy IPs.
     *
     * @return string Client IP address
     */
    public static function ip()
    {
        // Check for IP from proxy (X-Forwarded-For)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);  // Take first IP only
        }

        // Check for IP from proxy (X-Real-IP)
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        // Check for IP from client (can be spoofed)
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        // Direct connection IP (most reliable)
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user agent string
     *
     * Examples:
     *   $agent = Request::agent();
     *   // "Mozilla/5.0 (Windows NT 10.0; Win64; x64)..."
     *
     *   if (stripos(Request::agent(), 'mobile') !== false) {
     *       // Mobile device
     *   }
     *
     * @return string User agent string
     */
    public static function agent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Get HTTP referer
     *
     * The URL the user came from.
     *
     * Examples:
     *   $referer = Request::referer();
     *
     *   if ($referer = Request::referer()) {
     *       redirect($referer);  // Go back to previous page
     *   }
     *
     * @return string|null Referer URL or null
     */
    public static function referer()
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Check if request is from mobile device
     *
     * Basic mobile detection using user agent.
     * For advanced detection, consider using a dedicated library.
     *
     * Examples:
     *   if (Request::isMobile()) {
     *       return View::render('mobile/index');
     *   }
     *
     *   @if(Request::isMobile())
     *       <div class="mobile-nav"></div>
     *   @endif
     *
     * @return bool True if mobile device
     */
    public static function isMobile()
    {
        $agent = self::agent();

        $mobileKeywords = [
            'mobile', 'android', 'iphone', 'ipad', 'ipod',
            'blackberry', 'windows phone', 'opera mini',
            'webos', 'palm'
        ];

        foreach ($mobileKeywords as $keyword) {
            if (stripos($agent, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if request is from search bot
     *
     * Detects common search engine crawlers.
     *
     * Examples:
     *   if (Request::isBot()) {
     *       // Skip analytics tracking
     *   }
     *
     *   if (!Request::isBot()) {
     *       // Increment page view counter
     *   }
     *
     * @return bool True if search bot
     */
    public static function isBot()
    {
        $agent = self::agent();

        $botKeywords = [
            'bot', 'crawl', 'spider', 'slurp',
            'googlebot', 'bingbot', 'yandex', 'baiduspider',
            'facebookexternalhit', 'twitterbot', 'linkedinbot'
        ];

        foreach ($botKeywords as $keyword) {
            if (stripos($agent, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
