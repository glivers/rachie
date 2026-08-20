<?php namespace Rackage;

/**
 * Cookie Helper
 *
 * Provides secure cookie management with modern security features.
 *
 * Static Design:
 *   All methods are static - no instance creation required.
 *
 * Access Patterns:
 *
 *   In Controllers:
 *     use Rackage\Cookie;
 *
 *     class AuthController extends Controller {
 *         public function postRememberMe() {
 *             Cookie::set('remember_token', $token, 43200); // 30 days
 *         }
 *     }
 *
 *   In Views:
 *     Cookie is automatically available (configured in view_helpers).
 *     No 'use' statement needed.
 *
 *     @if(Cookie::has('user_preferences'))
 *         {{ Cookie::get('user_preferences') }}
 *     @endif
 *
 * Usage Methods:
 *
 *   COOKIE OPERATIONS
 *   - set()      Set cookie with expiration
 *   - get()      Get cookie value with optional default
 *   - has()      Check if cookie exists
 *   - delete()   Delete cookie
 *   - forget()   Alias for delete()
 *
 *   HELPERS
 *   - forever()  Set long-lived cookie (5 years)
 *
 * Security Features:
 *   - HttpOnly default (true) - prevents JavaScript access
 *   - Secure flag support - HTTPS only
 *   - SameSite default (Lax) - CSRF protection
 *   - Domain scoping support
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Cookie
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */
class Cookie
{

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
    // COOKIE OPERATIONS
    // =========================================================================

    /**
     * Set a cookie
     *
     * Creates a cookie with modern security defaults.
     * Expiration is in minutes for convenience.
     *
     * Examples:
     *   // Session cookie (expires when browser closes)
     *   Cookie::set('temp_data', 'value');
     *
     *   // 30-day cookie
     *   Cookie::set('remember_token', $token, 43200);
     *
     *   // Secure cookie (HTTPS only)
     *   Cookie::set('auth_token', $token, 1440, '/', true);
     *
     *   // Allow JavaScript access (not recommended for sensitive data)
     *   Cookie::set('theme', 'dark', 525600, '/', false, false);
     *
     *   // Strict SameSite (best CSRF protection)
     *   Cookie::set('csrf_token', $token, 0, '/', true, true, 'Strict');
     *
     * Parameters:
     *   $minutes = 0     Session cookie (deleted when browser closes)
     *   $minutes = 1440  24 hours (1 day)
     *   $minutes = 10080 1 week
     *   $minutes = 43200 30 days
     *   $minutes = 525600 1 year
     *
     * Security defaults:
     *   - httpOnly: true (prevents XSS attacks)
     *   - sameSite: 'Lax' (prevents most CSRF attacks)
     *   - secure: false (change to true in production with HTTPS)
     *
     * SameSite options:
     *   - 'Strict': Cookie only sent to same site (best security, may break some flows)
     *   - 'Lax': Cookie sent on top-level navigation (recommended default)
     *   - 'None': Cookie sent everywhere (requires secure=true)
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int $minutes Expiration in minutes (0 = session cookie)
     * @param string $path Path where cookie is available (default: '/')
     * @param bool $secure Only send over HTTPS (default: false)
     * @param bool $httpOnly Accessible only through HTTP (default: true)
     * @param string $sameSite SameSite attribute (default: 'Lax')
     * @param string $domain Domain where cookie is available (default: current domain)
     * @return bool True on success
     */
    public static function set($name, $value, $minutes = 0, $path = '/', $secure = false, $httpOnly = true, $sameSite = 'Lax', $domain = '')
    {
        $expiry = $minutes === 0 ? 0 : time() + ($minutes * 60);

        // PHP 7.3+ supports options array
        if (PHP_VERSION_ID >= 70300) {
            $options = [
                'expires' => $expiry,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httpOnly,
                'samesite' => $sameSite
            ];
            return setcookie($name, $value, $options);
        } else {
            // Fallback for PHP < 7.3 (append SameSite to path)
            $pathWithSameSite = $path . '; SameSite=' . $sameSite;
            return setcookie($name, $value, $expiry, $pathWithSameSite, $domain, $secure, $httpOnly);
        }
    }

    /**
     * Get a cookie value
     *
     * Retrieves cookie value with optional default.
     *
     * Examples:
     *   $token = Cookie::get('auth_token');
     *   $theme = Cookie::get('theme', 'light');  // Default to 'light'
     *   $lang = Cookie::get('language', 'en');
     *
     * Usage in controllers:
     *   if (Cookie::get('remember_me')) {
     *       // Auto-login user
     *   }
     *
     * Usage in views:
     *   <body class="theme-{{ Cookie::get('theme', 'light') }}">
     *
     * @param string $name Cookie name
     * @param mixed $default Default value if cookie doesn't exist
     * @return mixed Cookie value or default
     */
    public static function get($name, $default = null)
    {
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * Check if cookie exists
     *
     * Returns true if cookie is set, regardless of value.
     *
     * Examples:
     *   if (Cookie::has('auth_token')) {
     *       // User might be logged in
     *   }
     *
     *   @if(Cookie::has('user_preferences'))
     *       // Show custom preferences
     *   @endif
     *
     * @param string $name Cookie name
     * @return bool True if cookie exists
     */
    public static function has($name)
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Delete a cookie
     *
     * Removes cookie by setting expiration to the past.
     * Must match path and domain used when setting the cookie.
     *
     * Examples:
     *   Cookie::delete('auth_token');
     *   Cookie::delete('session_id', '/admin');
     *   Cookie::delete('user_pref', '/', 'subdomain.example.com');
     *
     * Usage in logout:
     *   Cookie::delete('remember_token');
     *   Cookie::delete('session_id');
     *   Session::flush();
     *
     * @param string $name Cookie name
     * @param string $path Path where cookie is available (default: '/')
     * @param string $domain Domain where cookie is available (default: current domain)
     * @return bool True on success
     */
    public static function delete($name, $path = '/', $domain = '')
    {
        if (self::has($name)) {
            unset($_COOKIE[$name]);

            // PHP 7.3+ supports options array
            if (PHP_VERSION_ID >= 70300) {
                return setcookie($name, '', [
                    'expires' => time() - 3600,
                    'path' => $path,
                    'domain' => $domain
                ]);
            } else {
                return setcookie($name, '', time() - 3600, $path, $domain);
            }
        }
        return false;
    }

    /**
     * Alias for delete()
     *
     * Provides a more expressive name for cookie deletion.
     *
     * Examples:
     *   Cookie::forget('auth_token');
     *   Cookie::forget('temp_data', '/admin');
     *
     * @param string $name Cookie name
     * @param string $path Path where cookie is available (default: '/')
     * @param string $domain Domain where cookie is available (default: current domain)
     * @return bool True on success
     */
    public static function forget($name, $path = '/', $domain = '')
    {
        return self::delete($name, $path, $domain);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Set a long-lived cookie (5 years)
     *
     * Creates a cookie that lasts 5 years (2,628,000 minutes).
     * Useful for "remember me" tokens and persistent preferences.
     *
     * Examples:
     *   // Remember me token
     *   Cookie::forever('remember_token', $token);
     *
     *   // User preferences
     *   Cookie::forever('theme', 'dark');
     *   Cookie::forever('language', 'en');
     *
     * Security note:
     *   Long-lived cookies should use secure=true in production.
     *   Consider using httpOnly=true for sensitive data.
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param string $path Path where cookie is available (default: '/')
     * @param bool $secure Only send over HTTPS (default: false)
     * @param bool $httpOnly Accessible only through HTTP (default: true)
     * @param string $sameSite SameSite attribute (default: 'Lax')
     * @param string $domain Domain where cookie is available (default: current domain)
     * @return bool True on success
     */
    public static function forever($name, $value, $path = '/', $secure = false, $httpOnly = true, $sameSite = 'Lax', $domain = '')
    {
        // 5 years in minutes (5 * 365 * 24 * 60)
        return self::set($name, $value, 2628000, $path, $secure, $httpOnly, $sameSite, $domain);
    }
}
