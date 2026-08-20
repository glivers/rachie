<?php namespace Rackage;

use Rackage\Registry;

/**
 * Security Helper
 *
 * Comprehensive security utilities for password hashing, token generation,
 * input sanitization, and HTTP security headers.
 *
 * Static Design:
 *   All methods are static - no instance creation required.
 *
 * Access Patterns:
 *
 *   In Controllers:
 *     use Rackage\Security;
 *
 *     class AuthController extends Controller {
 *         public function postLogin() {
 *             $hash = Security::hash($password);
 *             $valid = Security::verify($password, $hash);
 *         }
 *     }
 *
 *   In Views:
 *     Security is automatically available (configured in view_helpers).
 *     No 'use' statement needed.
 *
 *     {{ Security::escape($userInput) }}
 *
 * Usage Categories:
 *
 *   1. PASSWORD MANAGEMENT
 *      - hash()          Hash passwords with bcrypt
 *      - verify()        Verify password against hash
 *      - needsRehash()   Check if hash needs updating
 *
 *   2. TOKEN GENERATION
 *      - randomBytes()   Generate cryptographically secure random bytes
 *      - randomToken()   Generate random hex token
 *      - randomString()  Generate random alphanumeric string
 *
 *   3. INPUT SANITIZATION
 *      - escape()        Escape HTML entities (XSS prevention)
 *      - clean()         Remove HTML/PHP tags from input
 *      - sanitize()      Comprehensive input cleaning
 *
 *   4. CRYPTOGRAPHIC UTILITIES
 *      - compare()       Timing-safe string comparison
 *      - hmac()          Generate HMAC signature
 *      - verifyHmac()    Verify HMAC signature
 *
 *   5. HTTP SECURITY HEADERS
 *      - headers()       Set common security headers
 *      - csp()           Set Content Security Policy
 *      - cors()          Set CORS headers
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Security
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */
class Security {

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
    // PASSWORD MANAGEMENT
    // =========================================================================

    /**
     * Hash password using bcrypt
     *
     * Uses PHP's password_hash() with bcrypt algorithm (secure default).
     * Cost parameter determines computational expense (higher = more secure but slower).
     *
     * Examples:
     *   $hash = Security::hash('password123');
     *   $hash = Security::hash('password123', ['cost' => 12]);
     *
     * Usage in registration:
     *   $user = [
     *       'email' => Input::get('email'),
     *       'password' => Security::hash(Input::get('password'))
     *   ];
     *   Users::save($user);
     *
     * @param string $password Plain text password
     * @param array $options Options array (e.g., ['cost' => 12])
     * @return string Hashed password
     */
    public static function hash($password, $options = [])
    {
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    /**
     * Verify password against hash
     *
     * Uses timing-safe comparison to prevent timing attacks.
     *
     * Examples:
     *   if (Security::verify($password, $hash)) {
     *       // Password is correct
     *   }
     *
     * Usage in login:
     *   $user = Users::where('email', Input::get('email'))->first();
     *   if ($user && Security::verify(Input::get('password'), $user->password)) {
     *       Session::set('user_id', $user->id);
     *       redirect('dashboard');
     *   }
     *
     * @param string $password Plain text password
     * @param string $hash Hashed password from database
     * @return bool True if password matches hash
     */
    public static function verify($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if password hash needs rehashing
     *
     * Returns true if hash was created with old algorithm or different cost.
     * Use this to upgrade password hashes when user logs in.
     *
     * Examples:
     *   if (Security::needsRehash($user->password)) {
     *       $newHash = Security::hash($password);
     *       Users::where('id', $user->id)->save(['password' => $newHash]);
     *   }
     *
     * @param string $hash Existing password hash
     * @param array $options Options to check against
     * @return bool True if hash needs updating
     */
    public static function needsRehash($hash, $options = [])
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, $options);
    }

    // =========================================================================
    // TOKEN GENERATION
    // =========================================================================

    /**
     * Generate cryptographically secure random bytes
     *
     * Returns raw binary data. Use bin2hex() or base64_encode() to convert
     * to string format.
     *
     * Examples:
     *   $bytes = Security::randomBytes(32);
     *   $hex = bin2hex($bytes);  // 64 character hex string
     *
     * @param int $length Number of bytes to generate
     * @return string Random bytes
     * @throws \Exception If secure random generation fails
     */
    public static function randomBytes($length = 32)
    {
        return random_bytes($length);
    }

    /**
     * Generate random hex token
     *
     * Perfect for API tokens, password reset tokens, session IDs.
     * Returns twice as many characters as bytes (32 bytes = 64 chars).
     *
     * Examples:
     *   $token = Security::randomToken();        // 64 char hex token
     *   $token = Security::randomToken(16);      // 32 char hex token
     *
     * Usage for password reset:
     *   $token = Security::randomToken();
     *   PasswordResets::save([
     *       'email' => $email,
     *       'token' => $token,
     *       'expires' => time() + 3600
     *   ]);
     *
     * @param int $bytes Number of random bytes (output length = bytes * 2)
     * @return string Hex token
     * @throws \Exception If secure random generation fails
     */
    public static function randomToken($bytes = 32)
    {
        return bin2hex(self::randomBytes($bytes));
    }

    /**
     * Generate random alphanumeric string
     *
     * Uses only letters and numbers (no special characters).
     * Good for user-facing tokens or verification codes.
     *
     * Examples:
     *   $code = Security::randomString(6);   // "A7k9Bx"
     *   $code = Security::randomString(8);   // "mK4pQ2z7"
     *
     * Usage for verification code:
     *   $code = Security::randomString(6);
     *   Session::set('verification_code', $code);
     *   // Email $code to user
     *
     * @param int $length Length of string to generate
     * @return string Random alphanumeric string
     * @throws \Exception If secure random generation fails
     */
    public static function randomString($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charCount = strlen($chars);
        $result = '';

        $bytes = self::randomBytes($length);
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[ord($bytes[$i]) % $charCount];
        }

        return $result;
    }

    // =========================================================================
    // INPUT SANITIZATION
    // =========================================================================

    /**
     * Escape HTML entities for safe output
     *
     * Prevents XSS attacks by converting < > & " ' to HTML entities.
     * Use this when outputting user input in HTML.
     *
     * Examples:
     *   echo Security::escape($userInput);
     *   echo Security::escape('<script>alert("XSS")</script>');
     *   // Outputs: &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;
     *
     * In views (template syntax does this automatically):
     *   {{ $userInput }}  // Auto-escaped via HTML::escape()
     *
     * @param string $string String to escape
     * @return string Escaped string safe for HTML output
     */
    public static function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Remove HTML and PHP tags from input
     *
     * Strips all HTML and PHP tags. Use for plain text fields
     * where no HTML should be allowed.
     *
     * Examples:
     *   $clean = Security::clean('<p>Hello</p>');  // "Hello"
     *   $clean = Security::clean('<script>alert("XSS")</script>');  // ""
     *
     * Allow specific tags:
     *   $clean = Security::clean($input, '<b><i>');  // Keep bold/italic
     *
     * @param string $string String to clean
     * @param string $allowedTags Tags to allow (e.g., '<b><i>')
     * @return string Cleaned string
     */
    public static function clean($string, $allowedTags = '')
    {
        return strip_tags($string, $allowedTags);
    }

    /**
     * Comprehensive input sanitization
     *
     * Removes HTML/PHP tags and trims whitespace.
     * Use for general user input cleaning.
     *
     * Examples:
     *   $clean = Security::sanitize('  <b>Hello</b>  ');  // "Hello"
     *   $username = Security::sanitize(Input::get('username'));
     *
     * @param string $string String to sanitize
     * @return string Sanitized string
     */
    public static function sanitize($string)
    {
        return trim(strip_tags($string));
    }

    // =========================================================================
    // CRYPTOGRAPHIC UTILITIES
    // =========================================================================

    /**
     * Timing-safe string comparison
     *
     * Prevents timing attacks by comparing strings in constant time.
     * Use this when comparing sensitive values like tokens or hashes.
     *
     * Examples:
     *   if (Security::compare($userToken, $dbToken)) {
     *       // Token is valid
     *   }
     *
     * Usage for password reset:
     *   $reset = PasswordResets::where('token', $token)->first();
     *   if ($reset && Security::compare($token, $reset->token)) {
     *       // Token is valid, allow password reset
     *   }
     *
     * @param string $known Known string (from database)
     * @param string $user User-provided string
     * @return bool True if strings match
     */
    public static function compare($known, $user)
    {
        return hash_equals($known, $user);
    }

    /**
     * Generate HMAC signature for data
     *
     * Creates a cryptographic hash for verifying data integrity.
     * Use for API signatures, webhook validation, or tamper detection.
     *
     * Examples:
     *   $signature = Security::hmac($data, $secret);
     *   $signature = Security::hmac($data, $secret, 'sha256');
     *
     * Usage for API signature:
     *   $data = json_encode(['user_id' => 123, 'action' => 'update']);
     *   $signature = Security::hmac($data, env('API_SECRET'));
     *   // Send $data and $signature to API
     *
     * @param string $data Data to sign
     * @param string $key Secret key
     * @param string $algo Hash algorithm (default: sha256)
     * @return string HMAC signature
     */
    public static function hmac($data, $key, $algo = 'sha256')
    {
        return hash_hmac($algo, $data, $key);
    }

    /**
     * Verify HMAC signature
     *
     * Validates that data hasn't been tampered with using timing-safe comparison.
     *
     * Examples:
     *   if (Security::verifyHmac($data, $signature, $secret)) {
     *       // Data is authentic
     *   }
     *
     * Usage in webhook verification:
     *   $payload = file_get_contents('php://input');
     *   $signature = $_SERVER['HTTP_X_SIGNATURE'];
     *   if (Security::verifyHmac($payload, $signature, env('WEBHOOK_SECRET'))) {
     *       // Process webhook
     *   }
     *
     * @param string $data Data to verify
     * @param string $signature Signature to check
     * @param string $key Secret key
     * @param string $algo Hash algorithm (default: sha256)
     * @return bool True if signature is valid
     */
    public static function verifyHmac($data, $signature, $key, $algo = 'sha256')
    {
        $expectedSignature = self::hmac($data, $key, $algo);
        return self::compare($expectedSignature, $signature);
    }

    // =========================================================================
    // HTTP SECURITY HEADERS
    // =========================================================================

    /**
     * Set common security headers
     *
     * Sets multiple security headers for basic protection:
     * - X-Frame-Options: Prevent clickjacking
     * - X-Content-Type-Options: Prevent MIME sniffing
     * - X-XSS-Protection: Enable browser XSS filter
     * - Referrer-Policy: Control referrer information
     * - Strict-Transport-Security: Force HTTPS (if already on HTTPS)
     *
     * Call this early in application bootstrap or in base controller constructor.
     *
     * Examples:
     *   // In system/bootstrap.php
     *   Security::headers();
     *
     *   // In base controller
     *   class BaseController extends Controller {
     *       public function __construct() {
     *           Security::headers();
     *           parent::__construct();
     *       }
     *   }
     *
     * @return void
     */
    public static function headers()
    {    
        // Pull specific security configuration from registry
        $security   = Registry::securityConfig();
    
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Referrer policy
        $referrer   = $security['referrer_policy'] ?? 'strict-origin-when-cross-origin';
        header("Referrer-Policy: $referrer");

        // Framing Policy 
        $framing    = $policy['framing_policy'] ?? 'sameorigin';
        $ancestors  = null;

        // Prevent clickjacking
        if ($framing === 'deny') {

            header("X-Frame-Options: DENY");
            $ancestors = "'none'";
        } 
        else if ($framing === 'sameorigin') {

            header("X-Frame-Options: SAMEORIGIN");
            $ancestors = "'self'";
        } 
        else {
            // "allow" - removes X-Frame-Options entirely so it doesn't block framing
            // frame-ancestors * allows all domains
            $ancestors = "*";
        }

        // Force HTTPS (only if already on HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Update framing policy
        $security['Content-Security-Policy']['directives']['frame-ancestors'] = $ancestors;

        static::csp($security['Content-Security-Policy']);
        static::cors(
            $security['Cross-Origin-Resource-Sharing']['origins'], 
            $security['Cross-Origin-Resource-Sharing']['options']
        );
    }

    /**
     * Set Content Security Policy headers
     *
     * CSP helps prevent XSS attacks by controlling which resources
     * the browser can load (scripts, styles, images, etc.).
     *
     * Default policy is restrictive - only allows resources from same origin.
     * Customize by passing policy directives as array.
     *
     * Examples:
     *   // Use default secure policy
     *   Security::csp();
     *
     *   // Allow external scripts and styles
     *   Security::csp([
     *       'script-src' => "'self' https://cdn.example.com",
     *       'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com"
     *   ]);
     *
     *   // Allow inline scripts (less secure, use sparingly)
     *   Security::csp([
     *       'script-src' => "'self' 'unsafe-inline'"
     *   ]);
     *
     * Common directives:
     *   - default-src: Fallback for all resource types
     *   - script-src: JavaScript sources
     *   - style-src: CSS sources
     *   - img-src: Image sources
     *   - connect-src: AJAX/WebSocket sources
     *   - font-src: Font sources
     *   - frame-ancestors: Who can embed this page in iframe
     *
     * @param array $policy CSP policy directives
     * @return void
     */
    public static function csp($policy = [])
    {
        if(!$policy['enabled']) return; // Ignore if policy has been turned off

        // Default secure policy
        $defaults = [
            'default-src'       => "'self'",
            'script-src'        => "'self'",
            'style-src'         => "'self' 'unsafe-inline'",
            'img-src'           => "'self' data: https:",
            'font-src'          => "'self'",
            'connect-src'       => "'self'",
            // 'frame-ancestors'   => "'none'",
            'base-uri'          => "'self'",
            'form-action'       => "'self'"
        ];

        // Merge with custom policy
        $directives = array_merge($defaults, $policy['directives']);

        // Build CSP header string
        $cspParts = [];
        foreach ($directives as $directive => $value) {
            $cspParts[] = $directive . ' ' . $value;
        }
        $cspHeader = implode('; ', $cspParts);

        // Send headers based on Mode (Block vs Report-Only)
        if ($policy['report_only'] ?? false) header("Content-Security-Policy-Report-Only: $cspHeader");
        else header("Content-Security-Policy: $cspHeader");
    }

    /**
     * Set CORS headers for cross-origin requests
     *
     * CORS (Cross-Origin Resource Sharing) allows controlled access
     * to resources from different domains.
     *
     * Examples:
     *   // Allow specific origins
     *   Security::cors(['https://app.example.com', 'https://mobile.example.com']);
     *
     *   // Allow all origins (use with caution!)
     *   Security::cors(['*']);
     *
     *   // Custom configuration
     *   Security::cors(
     *       ['https://app.example.com'],
     *       [
     *           'methods' => 'GET, POST, PUT',
     *           'headers' => 'Content-Type, Authorization',
     *           'credentials' => false,
     *           'max_age' => 3600
     *       ]
     *   );
     *
     * Usage in API controller:
     *   class ApiController extends Controller {
     *       public function __construct() {
     *           Security::cors(['https://app.example.com']);
     *           parent::__construct();
     *       }
     *   }
     *
     * @param array $allowedOrigins Array of allowed origin URLs
     * @param array $options Additional CORS options
     * @return void
     */
    public static function cors($allowedOrigins = [], $options = [])
    {
        // Get request origin
        $origin = $_SERVER['HTTP_ORIGIN']?? '';
        if(!$origin) return;

        // Default options
        $defaults = [
            'methods'       => 'GET, POST, PUT, DELETE, OPTIONS',
            'headers'       => 'Content-Type, Authorization, X-CSRF-TOKEN, X-Requested-With',
            'credentials'   => true,
            'max_age'       => 86400
        ];

        $options = array_merge($defaults, $options);

        // Check if origin is allowed
        if (in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins)) {

            // Set allowed origin (use specific origin, not * if credentials are true)
            if ($options['credentials'] && $origin) {

                header("Access-Control-Allow-Origin: $origin");
                header('Access-Control-Allow-Credentials: true');
            } 
            else {
                header('Access-Control-Allow-Origin: ' . (in_array('*', $allowedOrigins) ? '*' : $origin));
            }

            // Set allowed methods
            header('Access-Control-Allow-Methods: ' . $options['methods']);

            // Set allowed headers
            header('Access-Control-Allow-Headers: ' . $options['headers']);

            // Set max age for preflight cache
            header('Access-Control-Max-Age: ' . $options['max_age']);

            // Handle preflight OPTIONS request
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                
                http_response_code(200);
                exit(); // No need to wake up controller for pre flight
            }
        }
        else exit(); // Seems this origin is not allowed
    }
}
