<?php namespace Rackage;

/**
 * View Handler
 *
 * Handles rendering of view files with optional template compilation.
 * Provides methods for passing data to views, rendering with or without
 * template engine, and outputting JSON responses.
 *
 * Static Design:
 *   All methods are static - no instance creation required.
 *   Variables are stored in static properties and extracted into view scope.
 * 
 * Template Engine:
 *   When enabled (settings.php → template_engine: true), views are compiled:
 *   - Directives (@if, @foreach, @extends, etc.) are processed
 *   - Echo tags ({{ }}, {{{ }}}) are compiled
 *   - View helpers are auto-imported
 *
 * Usage Patterns:
 *
 *   // Simple rendering with data
 *   View::render('blog/show', ['post' => $post]);
 *
 *   // With HTTP status code
 *   View::render('maintenance', [], 503);
 *
 *   // Chaining multiple data sources
 *   View::with(['user' => $user])
 *       ->with(['posts' => $posts])
 *       ->render('dashboard');
 *
 *   // Raw rendering (no template compilation)
 *   View::renderRaw('legacy.php', ['data' => $data]);
 *
 *   // Error pages
 *   View::error(404);
 *   View::error(500, ['message' => 'Database error']);
 *
 *   // Get compiled template content without rendering
 *   $compiled = View::get('emails/welcome');
 *
 *   // JSON response
 *   View::json(['status' => 'success']);
 *   View::json(['error' => 'Not found'], 404);
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\View
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

use Rackage\Path;
use Rackage\Cache;
use Rackage\Request;
use Rackage\Registry;
use Rackage\Templates\Template;
use Rackage\Templates\TemplateStream;
use Rackage\Exceptions\HelperException;
use Rackage\Templates\TemplateException;

class View {

    /**
     * Template parser singleton instance
     * @var Template|null
     */
    private static $templateEngine = null;

    /**
     * Variables to inject into view files
     * Stored as single merged array (not nested arrays)
     * @var array
     */
    private static $variables = array();

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

    /**
     * Get template parser singleton instance
     *
     * Creates Template instance on first call,
     * returns cached instance on subsequent calls.
     *
     * @return Template Template parser instance
     */
    public static function getTemplateEngine()
    {
        if (is_null(self::$templateEngine)) {
            self::$templateEngine = new Template();
        }

        return self::$templateEngine;
    }

    /**
     * Generate view helper imports header
     *
     * Creates PHP 'use' statements for all classes listed in the 'view_helpers'
     * configuration setting. This allows helper classes to be used in views without
     * fully qualifying their namespaces.
     *
     * Example output:
     *   <?php
     *   use Rackage\Url;
     *   use Rackage\HTML;
     *   ?>
     *
     * @return string PHP opening tag with use statements
     */
    private static function getHeaderContent()
    {
        $view_helpers = Registry::settings()['view_helpers'];

        $use_statements = array();
        foreach ($view_helpers as $namespace) {
            $use_statements[] = "use $namespace;";
        }

        $use_string = "\n" . join("\n", $use_statements);

        return '<?php ' . $use_string . "\n?>\n";
    }

    /**
     * Set view variables to pass to template
     *
     * Accepts an associative array of variables to make available in the view.
     * Can be called multiple times - data is merged together.
     *
     * Examples:
     *   View::with(['user' => $user])->render('profile');
     *
     *   View::with(['title' => 'Dashboard'])
     *       ->with(['posts' => $posts])
     *       ->render('admin/dashboard');
     *
     * @param array $data Associative array of variables
     * @return View Returns new instance for method chaining
     */
    public static function with(array $data)
    {
        // Merge data into single array (not nested)
        self::$variables = array_merge(self::$variables, $data);

        return new static;
    }

    /**
     * Render view file with template compilation
     *
     * Renders a view file with template engine enabled (unless disabled globally).
     * Variables are extracted into local scope and the view is included.
     *
     * Examples:
     *   View::render('home');
     *   View::render('blog/show', ['post' => $post]);
     *   View::render('maintenance', [], 503);
     *   View::with(['user' => $user])->render('dashboard');
     *
     * @param string $fileName View file path
     * @param array $data Optional associative array of variables
     * @param int $status HTTP status code (default 200)
     * @return void Outputs rendered view directly
     */
    public static function render($fileName, array $data = [], $status = 200)
    {
        self::renderView($fileName, $data, $status, true);
    }

    /**
     * Render view file without template compilation
     *
     * Renders a view file as plain PHP without template engine processing.
     * Use when a specific view shouldn't go through the template compiler,
     * while other views in the app still use templating.
     *
     * Examples:
     *   View::plain('emails/receipt');
     *   View::plain('exports/csv', ['rows' => $data]);
     *   View::plain('errors/maintenance', [], 503);
     *
     * @param string $fileName View file path
     * @param array $data Optional associative array of variables
     * @param int $status HTTP status code (default 200)
     * @return void Outputs rendered view directly
     */
    public static function plain($fileName, array $data = [], $status = 200)
    {
        self::renderView($fileName, $data, $status, false);
    }

    /**
     * Render error page with HTTP status code
     *
     * Renders the error page configured in settings.php → error_pages.
     * Sets proper HTTP status code. Falls back to generic message if
     * no error view is configured.
     *
     * Examples:
     *   View::error(404);
     *   View::error(500, ['message' => 'Database connection failed']);
     *   View::error(403, ['reason' => 'Insufficient permissions']);
     *
     * @param int $code HTTP status code
     * @param array $data Optional data to pass to error view
     * @return void Outputs error page directly
     */
    public static function error($code, array $data = [])
    {
        http_response_code($code);

        $errorPages = Registry::settings()['error_pages'] ?? [];
        $errorView = $errorPages[$code] ?? null;

        if ($errorView) self::render($errorView, $data);
   
        else echo "<h1>Error $code</h1>";
    }

    /**
     * Internal render implementation
     *
     * Handles the actual rendering logic for both render() and renderRaw().
     * Extracts variables, compiles template, and executes it.
     *
     * Rendering modes:
     *   - Dev mode: File-based (better error paths for debugging)
     *   - Production mode: Stream-based (zero disk I/O, 26x faster)
     *
     * If page caching is enabled, captures output and stores in cache.
     *
     * Process:
     *   1. Set HTTP status code if not 200
     *   2. Merge and extract variables into local scope
     *   3. Compile template (if template engine enabled)
     *   4. Execute via stream (production) or temp file (dev)
     *   5. Handle page caching if enabled
     *   6. Clean up and clear variables
     *
     * @param string $fileName View file path
     * @param array $data Variables to pass to view
     * @param int $status HTTP status code
     * @param bool $parse Whether to use template compilation
     * @return void
     */
    private static function renderView($fileName, array $data, $status, $parse)
    {
        // Set HTTP status code
        if ($status !== 200) http_response_code($status);

        // Merge passed data with existing variables
        $allVariables = array_merge(self::$variables, $data);

        // Extract variables into local scope
        extract($allVariables);

        // Determine whether to parse template
        $shouldParse = $parse && (Registry::settings()['template_engine'] !== false);

        // Get view contents (compiled or raw)
        if ($shouldParse) $contents = self::getHeaderContent() . self::getContents($fileName, false);
        else $contents = self::getHeaderContent() . file_get_contents(Path::view($fileName));

        // Check if Router decided this page should be cached
        $shouldCache = self::shouldCache();

        // ALWAYS start output buffering (for error handling AND optional caching)
        ob_start();

        // =======================================================================
        // RENDER TEMPLATE
        // =======================================================================
        //
        // Two rendering modes based on environment:
        //
        // DEBUG MODE (file-based):
        //   - Writes compiled template to vault/tmp/
        //   - Better error paths in stack traces (real file paths)
        //   - On error, temp file preserved for inspection
        //   - On success, temp file deleted immediately
        //
        // PRODUCTION MODE (stream-based):
        //   - Serves template directly from memory via stream wrapper
        //   - Zero disk I/O (~26x faster than file-based)
        //   - Consistent performance under server load
        //   - No orphaned temp files, no disk wear
        //
        // =======================================================================

        // Encapsulate in try...catch block so we can write file on error
        try {
            
            TemplateStream::setContent($contents);

            include 'rachie-template://render';

            TemplateStream::clearContent();
        }             
        catch (\Throwable $exception) { 
            
            // Write error template for debugging
            $root   = Registry::settings()['root'];
            $tmpDir = $root . '/vault/tmp/';

            if(!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
            $file   = date("Ymd_His") . "_" . preg_replace('/[^a-zA-Z0-9\_]+/', '_', $fileName);
            $file   = trim($file, '_') . ".php";
            $path   = $root . '/vault/tmp/' . $file;
            
            // Write the contents of the template file to disc for debugging
            file_put_contents($path, $contents); 

            $line       = $exception->getLine();
            $message    = $exception->getMessage();
            $file       = substr($path, strpos($root, $path));

            // Compose error message for both display and logging
            $message    = sprintf("%s in %s on line (%s)", $message, $file, $line);
            throw new TemplateException($message);
        }

        // Handle buffered output
        if ($shouldCache) {
            // Caching enabled: capture output, store it, then send to browser
            $output = ob_get_contents();
            ob_end_flush();
            self::storeCache($output);
        }
        else {
            // No caching: just send buffered output to browser
            ob_end_flush();
        }

        // Clear variables after rendering
        self::$variables = [];
    }

    /**
     * Render template to string without sending to browser
     *
     * Fully renders a template file with template compilation and variable extraction,
     * captures the output via output buffering, and returns it as a string.
     * The rendered HTML is never sent to the browser.
     *
     * Use cases:
     *   - Email bodies: render a template, pass HTML to mailer
     *   - Plugin admin partials: render content area, return to controller wrapper
     *   - PDF generation: capture HTML before passing to PDF library
     *   - Any context where you need rendered HTML as a string
     *
     * Examples:
     *   $html = View::get('emails/welcome', ['user' => $user]);
     *   $partial = View::get('admin/vendors/index', ['vendors' => $vendors]);
     *
     * @param string $fileName View file path (resolved via view_paths)
     * @param array $data Variables to pass to the template
     * @return string Fully rendered HTML string
     */
    public static function get($fileName, array $data = [])
    {
        $allVariables = array_merge(self::$variables, $data);

        extract($allVariables);

        $shouldParse = Registry::settings()['template_engine'] !== false;

        if ($shouldParse) $contents = self::getHeaderContent() . self::getContents($fileName, false);
        else $contents = self::getHeaderContent() . file_get_contents(Path::view($fileName));

        ob_start();

        try {

            TemplateStream::setContent($contents);

            include 'rachie-template://render';

            TemplateStream::clearContent();
        }
        catch(\Throwable $exception) {

            // Write error template for debugging
            $root   = Registry::settings()['root'];
            $tmpDir = $root . '/vault/tmp/';

            if(!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
            $file   = date("Ymd_His") . "_" . preg_replace('/[^a-zA-Z0-9\_]+/', '_', $fileName);
            $file   = trim($file, '_') . ".php";
            $path   = $root . '/vault/tmp/' . $file;
            
            // Write the contents of the template file to disc for debugging
            file_put_contents($path, $contents); 

            $line       = $exception->getLine();
            $message    = $exception->getMessage();
            $file       = substr($path, strpos($root, $path));

            // Compose error message for both display and logging
            $message    = sprintf("%s in %s on line (%s)", $message, $file, $line);
            throw new TemplateException($message);
        }

        $output = ob_get_clean();

        self::$variables = [];

        return $output;
    }

    /**
     * Get compiled template code without rendering or executing
     *
     * Returns the compiled PHP code of a template file without executing it.
     * Transforms template directives (@if, @foreach, {{ }}, etc.) into raw PHP
     * but does not run the result. Useful for debugging template compilation
     * output or inspecting how directives are translated.
     *
     * Example:
     *   $phpCode = View::compile('emails/welcome');
     *   // Returns: <?php echo htmlentities($user['name']); ?> etc.
     *
     * @param string $fileName View file path
     * @return string Compiled PHP code string
     */
    public static function compile($fileName)
    {
        try {
            return self::getContents($fileName, false);
        }
        catch (TemplateException $exception) {
            throw $exception;
        }
    }

    /**
     * Compile template file to PHP code
     *
     * Processes a template file through the template engine, converting
     * directives and echo tags into valid PHP code.
     *
     * @param string $fileName View file name
     * @param bool $embedded Whether this is embedded in another view
     * @return string Compiled PHP code
     */
    public static function getContents($fileName, $embedded)
    {
        $filePath = Path::view($fileName);
        $template = self::getTemplateEngine();
        $contents = $template->compiled($filePath, $embedded, $fileName);

        return $contents;
    }

    /**
     * Output JSON response
     *
     * Sets JSON content-type header and outputs data as JSON.
     * Optionally sets HTTP status code.
     *
     * Examples:
     *   View::json(['status' => 'success']);
     *   View::json(['error' => 'Not found'], 404);
     *   View::json(['data' => $results], 201);
     *
     * @param array|null $data Data to encode as JSON
     * @param int $status HTTP status code (default 200)
     * @return void Outputs JSON directly
     */
    public static function json(array $data = null, $status = 200)
    {
        http_response_code($status);

        header('Content-Type: application/json');

        echo json_encode($data);
    }

    /**
     * Outputs a .csv file
     * 
     * Takes an array of values and prints out a downloadable .csv file to the 
     * browser for direction download. Assumes the first row is the header;
     * 
     * @param array $output The list of items to output as csv file
     * @param string $filename The name of the file, defaults to export.csv
     * @return void
     */
    public static function csv($rows, $filename = "export.csv")
    {
        if(ob_get_level()) ob_end_clean();
        $stream     = fopen('php://output', 'w');

        try {            

            header('Content-Type: text/csv; charset=utf8');
            header('Content-Disposition: attachment; filename="'. $filename. '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            foreach($rows as $row) fputcsv($stream, $row);

            fclose($stream);

        } 
        catch (\Throwable $exception) {

            // Remove the preset headers, close output stream and throw template error
            header_remove();            
            if(is_resource($stream)) fclose($stream);

            throw new HelperException($exception->getMessage());
        }
    }

    /**
     * Render XML content
     * 
     * Takes preformated XML text, sets XML headers and renders to the browser in raw format.
     * Greate for generating .xml sitemaps or returning any kind of XML data
     * 
     * @param string $xml text
     * @return void
     */
    public static function xml($xml = null)
    {
        header('Content-Type: application/xml; charset=utf-8');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>';

        echo $xml;
    }

    /**
     * Render output and halt execution
     *
     * Renders view/JSON/error page and immediately exits.
     * Useful in filters to stop execution while returning output to user.
     *
     * Type-based dispatch:
     *   - Array → JSON response
     *   - Int → Error page (from error_pages config)
     *   - String → Template render
     *
     * Examples:
     *   View::halt(404);                                       // Error 404
     *   View::halt(404, null, ['message' => 'Not found']);    // Error 404 with data
     *   View::halt('errors/rate-limit', 429);                  // Template with 429 status
     *   View::halt('errors/rate-limit', 429, $data);           // Template with status and data
     *   View::halt(['error' => 'Unauthorized'], 401);          // JSON with 401 status
     *
     * WARNING: Calls exit() - no code after this runs (including @after filters)
     *
     * @param array|int|string $mode What to render (array=JSON, int=error, string=template)
     * @param int|null $code HTTP status code (default: 200 for string/array, mode value for int)
     * @param array $data Data to pass to template
     * @return void (never returns - calls exit)
     */
    public static function halt($mode, $code = null, $data = [])
    {
        // Array → JSON response
        if (is_array($mode)) {
            self::json($mode, $code ?? 200);
            exit;
        }

        // Int → error page
        if (is_int($mode)) {
            self::error($mode, $data);
            exit;
        }

        // String → template render
        if (is_string($mode)) {
            self::render($mode, $data, $code ?? 200);
            exit;
        }

        // Fallback - just exit
        exit;
    }

    // ===========================================================================
    // CACHE HANDLING
    // ===========================================================================

    /**
     * Check if current page should be cached
     *
     * Reads decision made by Router and stored in Registry.
     * Extracted as separate method to allow future customization
     * without polluting renderView() method.
     *
     * @return bool True if page should be cached
     */
    private static function shouldCache()
    {
        return Registry::shouldCache();
    }

    /**
     * Store rendered page output in cache
     *
     * @param string $output Rendered HTML output
     * @return void
     */
    private static function storeCache($output)
    {
        $cacheConfig = Registry::cacheConfig();
        $requestUri = Request::fullUri();
        $cacheKey = 'page:' . md5($requestUri);
        $lifetime = $cacheConfig['lifetime'] / 60;

        Cache::set($cacheKey, $output, $lifetime);
    }

}
