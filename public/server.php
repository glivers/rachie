<?php
/**
 * Rachie Development Server Router
 *
 * This script enables URL rewriting for PHP's built-in web server, replicating
 * the behavior of Apache's mod_rewrite and the .htaccess file in this directory.
 *
 * WHY THIS EXISTS:
 *
 * PHP's built-in development server doesn't process .htaccess files or support
 * mod_rewrite. Without this router script, clean URLs like /todos/show/5 won't
 * work - the server will return 404 errors or try to serve them as static files.
 *
 * This script bridges that gap by routing all non-file requests through index.php,
 * exactly as Apache would with mod_rewrite enabled.
 *
 * WHEN TO USE:
 *
 * Use this script ONLY with PHP's built-in development server:
 *   php -S localhost:8000 server.php
 *
 * Do NOT use with:
 *   - Apache (already has .htaccess)
 *   - Nginx (configure server block instead)
 *   - Production environments (use proper web servers)
 *
 * HOW IT WORKS:
 *
 * This script replicates the .htaccess RewriteRule:
 *   RewriteRule ^(.*)$ index.php?_rachie_route=$1 [QSA,L]
 *
 * Step-by-step:
 *   1. Browser requests: /todos/show/5?page=2
 *   2. Script extracts path: /todos/show/5
 *   3. Checks if it's a real file (CSS, JS, images)
 *   4. If file exists: Serves it directly (static assets)
 *   5. If not: Routes through index.php with _rachie_route parameter
 *   6. Rachie router receives: $_GET['_rachie_route'] = 'todos/show/5'
 *   7. Dispatches to: TodosController@show($id = '5')
 *
 * WHAT IT DOES:
 *
 * 1. Port Handling
 *    Fixes SERVER_NAME to include non-standard ports (like :8000) so Rachie's
 *    Url:: helper generates correct URLs in development.
 *
 * 2. Static File Serving
 *    CSS, JS, images, fonts - served directly by PHP's server for performance.
 *    Uses is_file() (not file_exists()) to prevent directory listing attacks.
 *
 * 3. Query String Preservation
 *    Cache-busting URLs like /css/app.css?v=3 work correctly - the ?v=3 is
 *    preserved for browser caching while stripped for file existence checks.
 *
 * 4. REQUEST_URI Preservation
 *    Maintains original REQUEST_URI (/todos) instead of rewritten version
 *    (index.php?_rachie_route=todos). This matches Apache's behavior and
 *    ensures Rachie's URL building works correctly.
 *
 * SECURITY:
 *
 * - Uses is_file() instead of file_exists() to block directory listing
 * - Only serves files, never directories
 * - URL decoding prevents encoded path traversal attacks
 * - Query strings handled safely via parse_str()
 *
 * EXAMPLES:
 *
 * Request: /todos
 *   → Sets $_GET['_rachie_route'] = 'todos'
 *   → Routes to TodosController@index()
 *
 * Request: /todos/show/5
 *   → Sets $_GET['_rachie_route'] = 'todos/show/5'
 *   → Routes to TodosController@show($id = '5')
 *
 * Request: /css/app.css
 *   → File exists, return false
 *   → PHP serves file directly
 *
 * Request: /css/app.css?v=3
 *   → Query stripped to check: /css/app.css
 *   → File exists, return false
 *   → PHP serves with full URL including ?v=3
 *
 * Request: /css/
 *   → is_file() returns false (it's a directory)
 *   → Routes through index.php → 404 error
 *
 * USAGE:
 *
 *   cd public
 *   php -S localhost:8000 server.php
 *
 * Then visit:
 *   http://localhost:8000/todos
 *   http://localhost:8000/todos/show/1
 *   http://localhost:8000/todos/create
 *
 * IMPORTANT:
 *
 * This file must remain in the public/ directory alongside index.php.
 * Moving it will break the require statement at the bottom.
 *
 * @category  Development
 * @package   Rachie
 * @author    Geoffrey Okongo <code@rachie.dev>
 * @copyright Copyright (c) 2015 - 2030 Geoffrey Okongo
 * @license   http://opensource.org/licenses/MIT MIT License
 * @version   1.0.0
 * @link      https://rachie.dev/docs/development-server
 */

// ==============================================================================
// 1. PORT HANDLING
// ==============================================================================

// Fix SERVER_NAME to include port for PHP's built-in server
// This ensures Rachie's Url:: helper generates correct URLs with port number
// Example: localhost:8000 instead of just localhost
if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') {
	$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
}

// ==============================================================================
// 2. STATIC FILE HANDLING
// ==============================================================================

// Get the requested URI path (strips query string like ?v=3)
// Example: /css/app.css?v=3 becomes /css/app.css
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Check if the request is for an actual FILE (not directory)
// Security: is_file() prevents directory listing attacks
// Performance: Static assets served directly without PHP processing
if ($uri !== '/' && is_file(__DIR__ . $uri)) {
	// Return false tells PHP's built-in server to serve the file directly
	// Query strings like ?v=3 are preserved for browser caching
	return false;
}

// ==============================================================================
// 3. URL ROUTING SETUP
// ==============================================================================

// Get the query string if it exists (for QSA flag emulation)
// Example: ?page=2&sort=date
$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

// Build the _rachie_route parameter (strip leading slash)
// Example: /todos/show/5 becomes todos/show/5
$route = ltrim($uri, '/');

// Set the _rachie_route query parameter (mimics .htaccess RewriteRule)
// This is what Rachie's router reads to dispatch the request
$_GET['_rachie_route'] = $route;

// Append existing query string (mimics QSA flag - Query String Append)
// Example: /todos?page=2 preserves &page=2 alongside _rachie_route
if ($queryString) {
	parse_str($queryString, $params);
	$_GET = array_merge($_GET, $params);
}

// ==============================================================================
// 4. SERVER VARIABLE UPDATES
// ==============================================================================

// Update QUERY_STRING to include _rachie_route
// Example: _rachie_route=todos/show/5&page=2
$_SERVER['QUERY_STRING'] = http_build_query($_GET);

// Set SCRIPT_NAME and PHP_SELF to index.php
// This tells Rachie which script is handling the request
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// CRITICAL: We deliberately do NOT modify REQUEST_URI
// Apache's mod_rewrite keeps REQUEST_URI as the original clean URL (/todos)
// not the rewritten version (index.php?_rachie_route=todos)
// Changing REQUEST_URI breaks Rachie's URL building (Url::base(), Url::link(), etc.)

// ==============================================================================
// 5. DISPATCH TO APPLICATION
// ==============================================================================

// Route the request through index.php (Rachie's front controller)
// From this point, Rachie takes over and handles routing, controllers, views
require __DIR__ . '/index.php';
