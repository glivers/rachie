<?php namespace Rackage;

/**
 * Session Helper
 *
 * Provides session management with support for flash messages,
 * data persistence, and security features.
 *
 * Static Design:
 *   All methods are static - no instance creation required.
 *   Session is automatically started by the framework.
 *
 * Access Patterns:
 *
 *   In Controllers:
 *     use Rackage\Session;
 *
 *     class UserController extends Controller {
 *         public function login() {
 *             Session::set('user_id', 123);
 *         }
 *     }
 *
 *   In Views:
 *     Session is automatically available (configured in view_helpers).
 *     No 'use' statement needed.
 *
 *     @if(Session::has('user_id'))
 *         <p>Welcome back!</p>
 *     @endif
 *
 * Usage Patterns:
 *
 *   // Basic operations
 *   Session::set('user_id', 123);
 *   Session::get('user_id');           // 123
 *   Session::has('user_id');           // true
 *   Session::remove('user_id');
 *
 *   // Flash messages (one-time data)
 *   Session::flash('success', 'User created!');
 *   Session::get('success');           // "User created!" (then removed)
 *
 *   // Pull (get and remove)
 *   $message = Session::pull('temp_data');
 *
 *   // Get all session data
 *   $all = Session::all();
 *
 *   // Security
 *   Session::refresh();                // Regenerate session ID
 *   Session::flush();                  // Destroy session
 *
 * Flash Messages:
 *   Flash data persists for exactly one request - perfect for redirect messages:
 *
 *   // In controller after action
 *   Session::flash('success', 'Profile updated!');
 *   redirect('profile');
 *
 *   // In view on next request
 *   @if(Session::has('success'))
 *       <div class="alert">{{ Session::get('success') }}</div>
 *   @endif
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Session
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */
class Session {

    /**
     * Flash data key prefix
     */
    private static $flashKey = '_flash';

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
    // SESSION MANAGEMENT
    // =========================================================================

    /**
     * Start session if not already started
     *
     * Checks if session is active before starting to prevent warnings.
     * The framework automatically starts the session in bootstrap,
     * so you typically don't need to call this manually.
     *
     * Example:
     *   Session::start();
     *
     * @return bool True if session started or already active
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            return session_start();
        }
        return true;
    }

    /**
     * Check if session is active
     *
     * Example:
     *   if (Session::isActive()) {
     *       // Session is running
     *   }
     *
     * @return bool True if session is active
     */
    public static function isActive()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Get current session ID
     *
     * Example:
     *   $id = Session::getId();  // "a1b2c3d4e5f6..."
     *
     * @return string Session ID
     */
    public static function getId()
    {
        return session_id();
    }

    /**
     * Set session ID
     *
     * Must be called before session starts.
     *
     * Example:
     *   Session::setId('custom-session-id');
     *   Session::start();
     *
     * @param string $id Session ID
     * @return void
     */
    public static function setId($id)
    {
        session_id($id);
    }

    /**
     * Get session name
     *
     * Example:
     *   $name = Session::getName();  // "PHPSESSID"
     *
     * @return string Session name
     */
    public static function getName()
    {
        return session_name();
    }

    /**
     * Set session name
     *
     * Must be called before session starts.
     *
     * Example:
     *   Session::setName('MY_APP_SESSION');
     *   Session::start();
     *
     * @param string $name Session name
     * @return void
     */
    public static function setName($name)
    {
        session_name($name);
    }

    /**
     * Regenerate session ID to prevent session fixation attacks
     *
     * Call this after login or privilege changes for security.
     *
     * Examples:
     *   Session::refresh();           // Delete old session
     *   Session::refresh(false);      // Keep old session
     *
     * @param bool $deleteOld Delete old session data (default true)
     * @return bool True on success
     */
    public static function refresh($deleteOld = true)
    {
        return session_regenerate_id($deleteOld);
    }

    /**
     * Destroy session and all data
     *
     * Completely removes session data and destroys the session.
     * Use this for logout functionality.
     *
     * Example:
     *   Session::flush();
     *   redirect('login');
     *
     * @return void
     */
    public static function flush()
    {
        session_unset();
        session_destroy();
    }

    // =========================================================================
    // DATA OPERATIONS
    // =========================================================================

    /**
     * Set session value
     *
     * Stores data in the session. Data persists across requests
     * until explicitly removed or session is destroyed.
     *
     * Examples:
     *   Session::set('user_id', 123);
     *   Session::set('user', ['id' => 123, 'name' => 'John']);
     *   Session::set('cart', $cartArray);
     *
     * @param string $key Session key
     * @param mixed $data Data to store
     * @return void
     */
    public static function set($key, $data)
    {
        $_SESSION[$key] = $data;
    }

    /**
     * Get session value
     *
     * Retrieves value from session. Returns default if key doesn't exist.
     *
     * Examples:
     *   Session::get('user_id');              // 123
     *   Session::get('nonexistent', 'default'); // 'default'
     *   Session::get('user');                 // ['id' => 123, ...]
     *
     * @param string $key Session key
     * @param mixed $default Default value if key not found
     * @return mixed Session value or default
     */
    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     *
     * Returns true even if value is null or false.
     * Use this to check key existence, not value truthiness.
     *
     * Examples:
     *   Session::has('user_id');     // true
     *   Session::has('nonexistent'); // false
     *
     * @param string $key Session key
     * @return bool True if key exists
     */
    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Get all session data
     *
     * Returns entire $_SESSION array.
     *
     * Example:
     *   $allData = Session::all();
     *   // ['user_id' => 123, 'cart' => [...], ...]
     *
     * @return array All session data
     */
    public static function all()
    {
        return $_SESSION;
    }

    /**
     * Remove session key
     *
     * Deletes a single key from session.
     *
     * Examples:
     *   Session::remove('temp_data');
     *   Session::remove('user_id');
     *
     * @param string $key Session key to remove
     * @return void
     */
    public static function remove($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Alias for remove()
     *
     * Example:
     *   Session::forget('temp_data');
     *
     * @param string $key Session key to remove
     * @return void
     */
    public static function forget($key)
    {
        self::remove($key);
    }

    /**
     * Get value and remove it from session
     *
     * Retrieves value then immediately removes it.
     * Useful for one-time data.
     *
     * Examples:
     *   $message = Session::pull('temp_message');
     *   // Returns value and removes from session
     *
     *   $data = Session::pull('nonexistent', 'default');
     *   // Returns 'default'
     *
     * @param string $key Session key
     * @param mixed $default Default value if key not found
     * @return mixed Session value or default
     */
    public static function pull($key, $default = null)
    {
        $value = self::get($key, $default);
        self::remove($key);
        return $value;
    }

    // =========================================================================
    // FLASH MESSAGES
    // =========================================================================

    /**
     * Set flash data for next request
     *
     * Flash data persists for exactly one request - perfect for
     * redirect messages. Data is automatically removed after being accessed.
     *
     * Examples:
     *   // In controller after action
     *   Session::flash('success', 'User created!');
     *   redirect('users');
     *
     *   // In view on next request
     *   @if(Session::has('success'))
     *       <div class="alert">{{ Session::get('success') }}</div>
     *   @endif
     *
     *   // Flash is automatically removed after display
     *
     * When called with 2 arguments, sets flash data.
     * When called with 1 argument, gets flash data.
     *
     * Examples:
     *   Session::flash('error', 'Invalid credentials');  // set
     *   Session::flash('error');                         // get
     *
     * @param string $key Flash key
     * @param mixed $value Flash value (omit to get)
     * @return mixed|void Flash value when getting, void when setting
     */
    public static function flash($key, $value = null)
    {
        // Get mode: 1 argument
        if (func_num_args() === 1) {
            return self::getFlash($key);
        }

        // Set mode: 2 arguments
        $_SESSION[self::$flashKey][$key] = $value;
    }

    /**
     * Keep flash data for one more request
     *
     * Prevents flash data from being removed on this request.
     * Useful when you need to keep a message for another redirect.
     *
     * Examples:
     *   Session::keep('success');              // Keep one key
     *   Session::keep(['success', 'error']);   // Keep multiple keys
     *
     * @param string|array $keys Key or array of keys to keep
     * @return void
     */
    public static function keep($keys)
    {
        $keys = is_array($keys) ? $keys : [$keys];

        foreach ($keys as $key) {
            if (isset($_SESSION[self::$flashKey . '_old'][$key])) {
                $_SESSION[self::$flashKey][$key] = $_SESSION[self::$flashKey . '_old'][$key];
            }
        }
    }

    /**
     * Keep all flash data for one more request
     *
     * Useful when redirecting multiple times.
     *
     * Example:
     *   Session::reflash();
     *
     * @return void
     */
    public static function reflash()
    {
        if (isset($_SESSION[self::$flashKey . '_old'])) {
            $_SESSION[self::$flashKey] = $_SESSION[self::$flashKey . '_old'];
        }
    }

    /**
     * Age flash data
     *
     * Moves current flash to old flash and clears old flash.
     * Called automatically by the framework at the end of each request.
     *
     * Internal use only - don't call this manually.
     *
     * @return void
     */
    public static function ageFlashData()
    {
        // Remove old flash
        unset($_SESSION[self::$flashKey . '_old']);

        // Age current flash
        if (isset($_SESSION[self::$flashKey])) {
            $_SESSION[self::$flashKey . '_old'] = $_SESSION[self::$flashKey];
            unset($_SESSION[self::$flashKey]);
        }
    }

    /**
     * Get flash value
     *
     * Retrieves flash data. Flash is automatically removed after current request.
     *
     * Example:
     *   $message = Session::getFlash('success');
     *
     * @param string $key Flash key
     * @param mixed $default Default value
     * @return mixed Flash value or default
     */
    public static function getFlash($key, $default = null)
    {
        // Check new flash first
        if (isset($_SESSION[self::$flashKey][$key])) {
            return $_SESSION[self::$flashKey][$key];
        }

        // Check old flash
        if (isset($_SESSION[self::$flashKey . '_old'][$key])) {
            return $_SESSION[self::$flashKey . '_old'][$key];
        }

        return $default;
    }

    /**
     * Check if flash key exists
     *
     * Example:
     *   if (Session::hasFlash('success')) {
     *       echo Session::getFlash('success');
     *   }
     *
     * @param string $key Flash key
     * @return bool True if flash key exists
     */
    public static function hasFlash($key)
    {
        return isset($_SESSION[self::$flashKey][$key]) ||
               isset($_SESSION[self::$flashKey . '_old'][$key]);
    }
}
