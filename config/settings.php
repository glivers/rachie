<?php
/**
 * Rachie Application Configuration
 * 
 * This file contains all the core settings for your Rachie application.
 * Modify these values to customize your application's behavior.
 * 
 * @category Configuration
 * @package  Rachie
 * @author   Geoffrey Okongo <code@rachie.dev>
 * @license  http://opensource.org/licenses/MIT MIT License
 */

return array(

	// ============================================================================
	// TIMEZONE CONFIGURATION
	// ============================================================================
	
	/**
	 * Application Timezone
	 * 
	 * Sets the default timezone for all date/time operations in your application.
	 * This affects functions like date(), time(), and Date helper class.
	 * 
	 * See: https://www.php.net/manual/en/timezones.php for valid timezone values
	 * 
	 * Examples:
	 *   'America/New_York'    - Eastern Time (US)
	 *   'Europe/London'       - GMT/BST
	 *   'Africa/Nairobi'      - East Africa Time
	 *   'Asia/Tokyo'          - Japan Standard Time
	 *   'UTC'                 - Coordinated Universal Time
	 */
	'timezone' => 'America/New_York',

	// ============================================================================
	// APPLICATION METADATA
	// ============================================================================
	
	/**
	 * Application Author
	 * 
	 * Your name/organization as the author of this application.
	 * This appears in:
	 *   - Console-generated class docblocks
	 *   - Auto-generated code comments
	 *   - Application metadata
	 * 
	 * Format: 'Name <email@example.com>' or just 'Name'
	 */
	'author' => 'Geoffrey Okongo <code@rachie.dev>',

	/**
	 * Copyright Statement
	 * 
	 * Your copyright notice for this application.
	 * Used in generated files and documentation.
	 * 
	 * Format: 'Copyright (c) YEAR(s) Your Name/Organization'
	 */
	'copyright' => 'Copyright (c) 2015 - 2030 Geoffrey Okongo',

	/**
	 * Application License
	 * 
	 * The license under which your application is released.
	 * Provide a URL to the license text or the license name.
	 * 
	 * Common licenses:
	 *   - MIT: http://opensource.org/licenses/MIT
	 *   - GPL: http://www.gnu.org/licenses/gpl.html
	 *   - Apache: http://www.apache.org/licenses/LICENSE-2.0
	 *   - Proprietary: 'Proprietary - All Rights Reserved'
	 */
	'license' => 'http://opensource.org/licenses/MIT MIT License',

	/**
	 * Application Version
	 * 
	 * The current version number of your application.
	 * Follow semantic versioning: MAJOR.MINOR.PATCH
	 * 
	 * Examples:
	 *   '1.0.0'   - Initial release
	 *   '1.2.3'   - Version 1, update 2, patch 3
	 *   '2.0.0'   - Major version 2 (breaking changes)
	 */
	'version' => '0.0.1',

	// ============================================================================
	// ENVIRONMENT CONFIGURATION
	// ============================================================================
	
	/**
	 * Development Mode
	 * 
	 * Controls whether the application runs in development or production mode.
	 * 
	 * When TRUE (development):
	 *   - Detailed error messages are displayed
	 *   - Stack traces are shown
	 *   - Debug information is available
	 *   - Helpful for troubleshooting during development
	 * 
	 * When FALSE (production):
	 *   - Error messages are generic/hidden from users
	 *   - Errors are logged instead of displayed
	 *   - Better security and user experience
	 * 
	 * IMPORTANT: Always set to FALSE in production environments!
	 */
	'dev' => true,

	/**
	 * Error Log File Path
	 * 
	 * The location where application errors are logged.
	 * Uses a relative path from the application root directory.
	 * 
	 * The framework will write error details, stack traces, and
	 * debugging information to this file.
	 * 
	 * Default: 'vault/logs/error.log'
	 *
	 * Make sure the directory:
	 *   - Exists and is writable by the web server
	 *   - Is outside the public directory for security
	 *   - Has appropriate permissions (typically 755 for directory, 644 for file)
	 */
	'error_log' => 'vault/logs/error.log',

	// ============================================================================
	// APPLICATION SETTINGS
	// ============================================================================
	
	/**
	 * Application Title
	 * 
	 * The name/title of your application.
	 * Used in:
	 *   - Page titles (if not overridden)
	 *   - Email headers
	 *   - Application metadata
	 *   - Default branding
	 * 
	 * This can be overridden on a per-page basis in your views/controllers.
	 */
	'title' => 'Rachie Framework',

	/**
	 * Application Protocol
	 *
	 * The protocol used to access your application.
	 *
	 * Values:
	 *   'auto'  - Auto-detect from server (checks $_SERVER['HTTPS'])
	 *   'http'  - Force HTTP (unencrypted connection)
	 *   'https' - Force HTTPS (encrypted SSL/TLS connection - RECOMMENDED for production)
	 *
	 * Used for:
	 *   - Generating absolute URLs (Url::base(), Url::link(), etc.)
	 *   - Redirects
	 *   - Asset URLs
	 *
	 * Recommendations:
	 *   - Development: 'auto' or 'http'
	 *   - Production: 'https' (enforces secure connections)
	 *
	 * SECURITY: Use 'https' in production!
	 */
	'protocol' => 'auto',

	/**
	 * Server Name
	 * 
	 * The domain name or hostname where your application is hosted.
	 * Do NOT include protocol (http://) or trailing slashes.
	 * 
	 * Examples:
	 *   'localhost'           - Local development
	 *   'localhost:8000'      - Local with custom port
	 *   'example.com'         - Production domain
	 *   'www.example.com'     - Production with www
	 *   'api.example.com'     - Subdomain
	 * 
	 * Used for:
	 *   - Generating absolute URLs
	 *   - Cookie domains
	 *   - CORS configuration
	 */
	'servername' => 'localhost',

	/**
	 * URL Component Separator
	 * 
	 * The character used to separate components in URLs.
	 * 
	 * Common patterns:
	 *   '.' (dot)   - example.com/controller.action.param
	 *   '/' (slash) - example.com/controller/action/param (REST-style)
	 * 
	 * Default Rachie routing: example.com/Home.Index
	 * 
	 * Choose based on your preferred URL structure and routing style.
	 */
	'url_separator' => '/',

	/**
	 * Default Upload Path
	 * 
	 * The directory where uploaded files are stored.
	 * Relative to the application root.
	 * 
	 * Default: 'public/uploads/'
	 * 
	 * Security considerations:
	 *   - Should be writable by the web server
	 *   - Consider placing outside public directory if files are sensitive
	 *   - Implement proper access controls and validation
	 *   - Never execute uploaded files
	 * 
	 * Make sure to:
	 *   - Create this directory before uploading files
	 *   - Set appropriate permissions (755 for directory)
	 *   - Add .htaccess rules if needed
	 */
	'upload_path' => 'public/uploads/',

	/**
	 * Application Root Directory
	 * 
	 * The absolute path to the application root directory.
	 * Auto-detected using dirname() - usually doesn't need to be changed.
	 * 
	 * This is used internally by the framework for:
	 *   - File path resolution
	 *   - Loading classes and views
	 *   - Including files
	 * 
	 * DO NOT modify this unless you have a specific reason!
	 */
	'root' => dirname((dirname(__FILE__))),

	// ============================================================================
	// ROUTING DEFAULTS
	// ============================================================================
	
	/**
	 * Default Routing Configuration
	 * 
	 * Defines the default controller and action when none is specified in the URL.
	 * 
	 * Example: When user visits just 'example.com/', Rachie will route to:
	 *   Controller: HomeController
	 *   Action: IndexAction
	 * 
	 * This gives you: example.com/ → HomeController::Index()
	 */
	'default' => array(
		/**
		 * Default Controller
		 * 
		 * The controller to use when no controller is specified in the URL.
		 * 
		 * Format: Just the controller name (without 'Controller' suffix)
		 * Example: 'Home' maps to HomeController class
		 * 
		 * Common defaults:
		 *   'Home'  - Home page
		 *   'Index' - Index page
		 *   'Main'  - Main page
		 */
		'controller' => 'Home',

		/**
		 * Default Action
		 * 
		 * The action/method to call when no action is specified in the URL.
		 * 
		 * Format: Just the action name (without 'Action' suffix)
		 * Example: 'Index' maps to IndexAction() method
		 * 
		 * Common defaults:
		 *   'Index'   - Main/index page
		 *   'Show'    - Display default view
		 *   'List'    - List items
		 */
		'action' => 'Index'
	),

	// ============================================================================
	// TEMPLATE ENGINE CONFIGURATION
	// ============================================================================
	
	/**
	 * Enable Template Engine
	 * 
	 * Controls whether Rachie's template engine is active.
	 * 
	 * When TRUE:
	 *   - Template files (.template.php) are compiled
	 *   - Directives (@if, @foreach, etc.) are processed
	 *   - Echo tags ({{ }}, {{{ }}}) are compiled
	 *   - Template caching is enabled
	 * 
	 * When FALSE:
	 *   - Views are rendered as plain PHP
	 *   - No template compilation
	 *   - Slightly faster for simple views
	 * 
	 * RECOMMENDED: Keep as TRUE for modern template features
	 */
	'template_engine' => true,

	/**
	 * Template Echo Tags (Escaped Output)
	 * 
	 * Defines the delimiters for ESCAPED (safe) variable output.
	 * Output is automatically HTML-escaped to prevent XSS attacks.
	 * 
	 * Default: array('{{', '}}')
	 * Usage in templates: {{ $username }}
	 * Compiles to: <?php echo HTML::escape($username); ?>
	 * 
	 * Use for:
	 *   - Displaying user input
	 *   - Any untrusted content
	 *   - Variables that might contain HTML
	 * 
	 * Change if using frontend frameworks (Vue.js, Angular) that also use {{ }}
	 * Example for Vue.js compatibility: array('[[', ']]')
	 */
	'template_echo_tags' => array('{{', '}}'),

	/**
	 * Template Raw Tags (Unescaped Output)
	 * 
	 * Defines the delimiters for RAW (unescaped) HTML output.
	 * Output is rendered as-is WITHOUT any escaping.
	 * 
	 * Default: array('{{{', '}}}')
	 * Usage in templates: {{{ $htmlContent }}}
	 * Compiles to: <?php echo $htmlContent; ?>
	 * 
	 * DANGER: Can cause XSS vulnerabilities if used with user input!
	 * 
	 * Use ONLY for:
	 *   - Trusted HTML content from your database
	 *   - CMS content you control
	 *   - Pre-sanitized HTML
	 * 
	 * NEVER use for:
	 *   - User input
	 *   - Unvalidated content
	 *   - External data sources
	 * 
	 * Example for different syntax: array('{!!', '!!}')
	 */
	'template_raw_tags' => array('{{{', '}}}'),

	// ============================================================================
	// VIEW HELPERS
	// ============================================================================

	/**
	 * View Helper Classes
	 *
	 * List of Rackage helper classes that are automatically imported into
	 * compiled view templates via 'use' statements.
	 *
	 * These classes become available in your view files WITHOUT needing to
	 * specify the full namespace or add 'use' statements manually.
	 *
	 * Example - In your view templates you can use:
	 *   Url::to('home')
	 *   HTML::escape($userInput)
	 *   Date::format($timestamp)
	 *   Input::get('username')
	 *
	 * Instead of:
	 *   \Rackage\Url::to('home')
	 *   \Rackage\HTML::escape($userInput)
	 *
	 * IMPORTANT: These are only auto-imported in VIEWS, not controllers.
	 * In controllers, you still need to add 'use Rackage\ClassName;' statements.
	 *
	 * Add any additional Rackage helper classes you want available in views.
	 */
	'view_helpers' => array(
		'Rackage\Arr',
		'Rackage\Str',
		'Rackage\Url',
		'Rackage\Path',
		'Rackage\Html',
		'Rackage\File',
		'Rackage\CSRF',
		'Rackage\Date',
		'Rackage\Input',
		'Rackage\Cookie',
		'Rackage\Session',
		'Rackage\Request',
		'Rackage\Validate',
		'Rackage\Registry',
		'Rackage\Security',
		'Rackage\Redirect',
	),

	// ============================================================================
	// ADVANCED ROUTING
	// ============================================================================

	/**
	 * Catch-All Routing
	 *
	 * Enable catch-all routing for CMS-style applications where URLs don't map
	 * directly to controllers (e.g., blog posts, pages, products with dynamic slugs).
	 *
	 * When enabled, if no route or controller matches the URL, the framework will
	 * dispatch to the catch-all controller instead of showing a 404 error.
	 *
	 * Use Cases:
	 *   - CMS platforms (WordPress-style page/post URLs)
	 *   - E-commerce sites (product slug URLs like /nike-air-max-270)
	 *   - Documentation sites (nested page hierarchies)
	 *   - Portfolio sites (project slug URLs)
	 *
	 * How It Works:
	 *   1. Check exact route matches
	 *   2. Check pattern matches (blog/*, products/*)
	 *   3. Check URL-based routing (Controller/method)
	 *   4. If catch_all enabled → Dispatch to catch-all controller
	 *   5. Else → Show 404 error page
	 *
	 * Example:
	 *   URL: /my-awesome-blog-post
	 *   No route or controller matches
	 *   → PagesController::show('my-awesome-blog-post')
	 *   → Controller queries database for page with this slug
	 *
	 * IMPORTANT: Most MVC applications don't need this. Only enable for
	 * content-focused platforms with dynamic URLs.
	 */
	'routing' => array(
		/**
		 * Enable Catch-All Routing
		 *
		 * Set to true to enable catch-all routing for unmatched URLs.
		 * Default: false (disabled)
		 */
		'catch_all' => false,

		/**
		 * Catch-All Controller
		 *
		 * The controller to dispatch to when no route/controller matches.
		 * Default: 'Pages'
		 */
		'ca_controller' => 'Pages',

		/**
		 * Catch-All Method
		 *
		 * The method to call on the catch-all controller.
		 * The full URL will be passed as the first parameter.
		 *
		 * Default: 'show'
		 */
		'ca_method' => 'show',
	),

	// ============================================================================
	// ERROR PAGES
	// ============================================================================

	/**
	 * Custom Error Pages
	 *
	 * Define custom view templates for error pages in production.
	 *
	 * In development mode (dev: true):
	 *   Errors show detailed stack traces for debugging
	 *
	 * In production mode (dev: false):
	 *   Errors use these custom templates for better UX
	 *
	 * View Path Format:
	 *   'errors.404' → application/views/errors/404.php
	 *   'errors.500' → application/views/errors/500.php
	 *
	 * Create these view files in your application/views/errors/ directory.
	 */
	'error_pages' => array(
		/**
		 * 404 Not Found
		 *
		 * Shown when:
		 *   - Route not found
		 *   - Controller not found
		 *   - Catch-all controller returns 404
		 */
		'404' => 'errors/404',

		/**
		 * 500 Server Error
		 *
		 * Shown when:
		 *   - Uncaught exceptions
		 *   - Fatal errors
		 *   - Database errors
		 */
		'500' => 'errors/500',
	)
);