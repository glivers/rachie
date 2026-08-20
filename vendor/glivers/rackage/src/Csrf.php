<?php namespace Rackage;

/**
 * Csrf Protection Helper
 *
 * Provides Cross-Site Request Forgery (Csrf) protection through secure
 * token generation and validation.
 *
 * Static Design:
 *   All methods are static - no instance creation required.
 *   Tokens are stored in session and validated on form submissions.
 *
 * Access Patterns:
 *
 *   In Controllers:
 *     use Rackage\Csrf;
 *
 *     class UserController extends Controller {
 *         public function postUpdate() {
 *             if (!Csrf::verify()) {
 *                 die('Invalid Csrf token');
 *             }
 *             // Process form...
 *         }
 *     }
 *
 *   In Views:
 *     Csrf is automatically available (configured in view_helpers).
 *     No 'use' statement needed.
 *
 *     <form method="POST" action="/user/update">
 *         {{ Csrf::field() }}
 *         <input type="text" name="username">
 *         <button>Update</button>
 *     </form>
 *
 *   In AJAX:
 *     {{ Csrf::meta() }}
 *
 *     <script>
 *     fetch('/api/update', {
 *         method: 'POST',
 *         headers: {
 *             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
 *         }
 *     });
 *     </script>
 *
 * Usage Methods:
 *
 *   TOKEN GENERATION
 *   - token()       Get Csrf token string
 *   - field()       Get HTML hidden input field
 *   - meta()        Get HTML meta tag (for AJAX)
 *
 *   VALIDATION
 *   - verify()      Auto-validate from POST/headers
 *   - valid($token) Manually validate specific token
 *
 *   MANAGEMENT
 *   - regenerate()  Create new token (after login, privilege changes)
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Csrf
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

use Rackage\Session;
use Rackage\Input;

class Csrf
{

    /**
     * Session key for storing Csrf token
     */
    private static $tokenKey = 'csrf_token';

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
    // TOKEN GENERATION
    // =========================================================================

    /**
     * Generate and retrieve Csrf token
     *
     * Creates a cryptographically secure token if one doesn't exist.
     * Returns existing token if already generated.
     *
     * Examples:
     *   $token = Csrf::token();  // "a1b2c3d4..."
     *   <input type="hidden" value="{{ Csrf::token() }}">
     *
     * Security:
     *   - 64-character hex token (32 random bytes)
     *   - Session-specific (different per user)
     *   - Timing-safe validation
     *
     * @return string Csrf token (64 hex characters)
     */
    public static function token()
    {
        if (!Session::has(self::$tokenKey)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::$tokenKey, $token);
        }

        return Session::get(self::$tokenKey);
    }

    /**
     * Get Csrf token as hidden form field
     *
     * Returns complete HTML hidden input with token.
     * Recommended way to add Csrf protection to forms.
     *
     * Examples:
     *   <form method="POST">
     *       {{ Csrf::field() }}
     *       <input type="text" name="username">
     *   </form>
     *
     * Output:
     *   <input type="hidden" name="csrf_token" value="a1b2...">
     *
     * Validation in controller:
     *   if (!Csrf::verify()) {
     *       die('Invalid token');
     *   }
     *
     * @return string HTML hidden input field
     */
    public static function field()
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    /**
     * Get Csrf token as meta tag
     *
     * Returns HTML meta tag for AJAX/SPA/frontend frameworks.
     *
     * Examples:
     *   <!-- In layout head -->
     *   {{ Csrf::meta() }}
     *
     *   <!-- JavaScript -->
     *   <script>
     *   const token = document.querySelector('meta[name="csrf-token"]').content;
     *   fetch('/api', {
     *       headers: { 'X-CSRF-TOKEN': token }
     *   });
     *   </script>
     *
     * jQuery setup:
     *   $.ajaxSetup({
     *       headers: {
     *           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
     *       }
     *   });
     *
     * Output:
     *   <meta name="csrf-token" content="a1b2...">
     *
     * @return string HTML meta tag
     */
    public static function meta()
    {
        return '<meta name="csrf-token" content="' . self::token() . '">';
    }

    // =========================================================================
    // VALIDATION
    // =========================================================================

    /**
     * Validate Csrf token
     *
     * Compares provided token with session token using timing-safe comparison.
     *
     * Examples:
     *   $userToken = Input::post('csrf_token');
     *   if (!Csrf::valid($userToken)) {
     *       die('Invalid token');
     *   }
     *
     *   // From HTTP header (AJAX)
     *   $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
     *   if (!Csrf::valid($token)) {
     *       http_response_code(403);
     *       exit;
     *   }
     *
     * Security:
     *   - Uses hash_equals() to prevent timing attacks
     *   - Returns false if session token doesn't exist
     *   - Null/empty tokens always fail
     *
     * @param string $token Token to validate
     * @return bool True if valid
     */
    public static function valid($token)
    {
        $sessionToken = Session::get(self::$tokenKey);

        $token  = (string) $token;
        if(!$token) return false;
        
        return $sessionToken && hash_equals($sessionToken, $token);
    }

    /**
     * Verify Csrf token from request
     *
     * Auto-retrieves and validates token from POST or HTTP headers.
     * Easiest validation method.
     *
     * Checks in order:
     *   1. X-CSRF-TOKEN HTTP header (AJAX)
     *   2. csrf_token POST field (forms)
     *
     * Examples:
     *   public function postUpdate() {
     *       if (!Csrf::verify()) {
     *           die('Invalid token');
     *       }
     *       // Process form...
     *   }
     *
     *   // JSON API response
     *   if (!Csrf::verify()) {
     *       return View::json(['error' => 'Invalid token'], 403);
     *   }
     *
     * Best practice:
     *   - Validate on POST/PUT/DELETE
     *   - Never validate GET (breaks caching)
     *   - Return 403 on failure
     *
     * @return bool True if valid
     */
    public static function verify()
    {
        // Try HTTP header (AJAX)
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        // Fall back to POST (forms)
        if (!$token) {
            $token = Input::post('csrf_token');
        }

        return self::valid($token);
    }

    // =========================================================================
    // TOKEN MANAGEMENT
    // =========================================================================

    /**
     * Regenerate Csrf token
     *
     * Destroys current token and generates new one.
     * Important for security after privilege changes.
     *
     * When to regenerate:
     *   - After login
     *   - After privilege escalation
     *   - After password change
     *   - After sensitive operations
     *
     * Examples:
     *   // After login
     *   Session::set('user_id', $user->id);
     *   Session::refresh();   // Regenerate session ID
     *   Csrf::regenerate();   // Regenerate Csrf token
     *
     *   // After privilege change
     *   Users::where('id', $id)->save(['role' => 'admin']);
     *   Csrf::regenerate();
     *
     * Note:
     *   Old tokens become invalid. Open forms in other tabs will fail.
     *   This is expected security behavior.
     *
     * @return string New Csrf token
     */
    public static function regenerate()
    {
        Session::remove(self::$tokenKey);
        return self::token();
    }
}
