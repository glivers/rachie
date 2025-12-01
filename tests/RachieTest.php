<?php namespace Tests;

/**
 * Base Test Class for Application Tests
 *
 * This is the parent class for all application tests in Rachie. It provides helpers
 * for testing your controllers, models, and features through the full framework stack.
 *
 * Architecture:
 *   - Tests run through the complete boot sequence (bootstrap.php → start.php)
 *   - Uses actual framework classes (Router, Input, Registry, Models)
 *   - No mocking - tests verify real integration behavior
 *   - Database access uses framework Models (Posts::save(), Users::where())
 *
 * Features:
 *   - HTTP request simulation with full routing and controller dispatch
 *   - Path helpers for creating test files (controllers, models, views)
 *   - Automatic file cleanup after each test
 *   - Custom assertions for responses, sessions, JSON
 *
 * Test Strategy:
 *   Integration tests create real files, make real requests, verify real output.
 *   Use trackFile() to auto-delete test files in tearDown(). Use framework Models
 *   directly for database operations instead of raw PDO.
 *
 * Usage:
 *   class MyFeatureTest extends RachieTest
 *   {
 *       public function testCreatePost()
 *       {
 *           // Create test controller
 *           $controllerPath = $this->controllerPath('Posts');
 *           $this->trackFile($controllerPath);
 *           file_put_contents($controllerPath, '<?php ...');
 *
 *           // Test it
 *           $response = $this->request('Posts/create', 'POST', ['title' => 'Test']);
 *           $this->assertResponseContains('created', $response);
 *       }
 *   }
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Tests
 * @package Rachie
 * @link https://github.com/glivers/rachie
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\Path;
use Rackage\Input;
use Rackage\Registry;
use Rackage\Router\Router;
use PHPUnit\Framework\TestCase;

abstract class RachieTest extends TestCase
{
    /**
     * Files and directories to delete after test completes
     *
     * Tracks all test files created during test execution. Populated by trackFile()
     * and processed by cleanupTrackedFiles() in tearDown(). Supports both files
     * and directories (directories are deleted recursively).
     *
     * @var array List of absolute file/directory paths
     */
    protected array $filesToCleanup = [];

    /**
     * Cached application base path for performance
     *
     * Set once in setUp() via Path::app() and reused by all path helper methods
     * (controllerPath, modelPath, viewPath). Avoids repeated Path::app() calls.
     *
     * @var string Absolute path to application/ directory
     */
    protected string $basePath;

    /**
     * Set up clean test environment before each test
     *
     * Resets PHP superglobals to ensure test isolation. Clears session data,
     * GET/POST parameters, and sets default REQUEST_METHOD to GET. Also caches
     * the application base path for use by path helper methods.
     *
     * Called automatically by PHPUnit before each test method executes.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset globals for clean test state
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Reset file cleanup tracking
        $this->filesToCleanup = [];

        // Cache base path for path helpers
        $this->basePath = Path::app();
    }

    /**
     * Clean up test files after each test
     *
     * Deletes all files and directories tracked via trackFile(). Automatically
     * called by PHPUnit after each test method completes. Ensures no test files
     * persist between test runs.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->cleanupTrackedFiles();
        parent::tearDown();
    }

    // =======================================================================
    // PATH HELPERS
    // =======================================================================

    /**
     * Get full path to a controller file
     *
     * Example:
     *   $path = $this->controllerPath('Posts');
     *   // Returns: /path/to/application/controllers/PostsController.php
     *
     * @param string $name Controller name (without 'Controller' suffix)
     * @return string Full path to controller file
     */
    protected function controllerPath(string $name)
    {
        return $this->basePath . '/controllers/' . $name . 'Controller.php';
    }

    /**
     * Get full path to a model file
     *
     * Example:
     *   $path = $this->modelPath('Post');
     *   // Returns: /path/to/application/models/PostModel.php
     *
     * @param string $name Model name (without 'Model' suffix)
     * @return string Full path to model file
     */
    protected function modelPath(string $name)
    {
        return $this->basePath . '/models/' . $name . 'Model.php';
    }

    /**
     * Get full path to a view file
     *
     * Example:
     *   $path = $this->viewPath('posts/show');
     *   // Returns: /path/to/application/views/posts/show.php
     *
     * @param string $path View path (without .php extension)
     * @return string Full path to view file
     */
    protected function viewPath(string $path)
    {
        return Path::view($path . '.php');
    }

    // =======================================================================
    // HTTP REQUEST SIMULATION
    // =======================================================================

    /**
     * Simulate HTTP request through full framework stack
     *
     * Replicates what system/start.php does for web requests: sets up server
     * environment, initializes Input, loads routes, creates Router, and dispatches
     * the request. Captures and returns controller output via output buffering.
     *
     * This is the primary method for integration testing - it runs the complete
     * request lifecycle including routing, controller instantiation, method
     * execution, and view rendering.
     *
     * Process:
     *   1. Configure $_SERVER superglobal (REQUEST_METHOD, REQUEST_URI, etc.)
     *   2. Set $_GET['_rachie_route'] (what .htaccess does)
     *   3. Set $_POST for POST/PUT/PATCH/DELETE requests
     *   4. Initialize Input and Registry (Input::setGet()->setPost())
     *   5. Load route definitions from config/routes.php
     *   6. Create Router instance with settings and routes
     *   7. Dispatch request and capture output
     *
     * Example:
     *   $response = $this->request('posts/create', 'POST', [
     *       'title' => 'My Post',
     *       'body' => 'Content here'
     *   ]);
     *   $this->assertResponseContains('Post created', $response);
     *
     * @param string $url URL to request (without leading slash, e.g., 'posts/create')
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH)
     * @param array $data POST/PUT/PATCH data (key-value pairs)
     * @return string Response output (captured via output buffering)
     */
    protected function request(string $url, string $method = 'GET', array $data = [])
    {
        // Set up server environment (CLI doesn't have these)
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/' . $url;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_GET['_rachie_route'] = $url;

        // Set POST data for POST/PUT/PATCH/DELETE requests
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $_POST = $data;
        }

        // Initialize Input (what system/start.php does)
        Input::setGet()->setPost();
        Registry::setUrl($url);

        // Load routes (what system/start.php does)
        $routes = require __DIR__ . '/../config/routes.php';

        // Create and dispatch router (what system/start.php does)
        $router = new Router(Registry::settings(), $routes);

        // Capture output using output buffering
        ob_start();
        $router->dispatch();
        return ob_get_clean();
    }

    // =======================================================================
    // FILE CLEANUP HELPERS
    // =======================================================================

    /**
     * Track file or directory for automatic cleanup
     *
     * Adds path to cleanup list. File/directory will be deleted automatically
     * in tearDown(). Useful for test files that need cleanup regardless of
     * test success or failure.
     *
     * Supports both individual files and directories (deleted recursively).
     *
     * Example:
     *   $controllerPath = $this->controllerPath('Test');
     *   $this->trackFile($controllerPath);
     *   file_put_contents($controllerPath, '<?php ...');
     *   // File auto-deleted after test completes
     *
     * @param string $filePath Absolute path to file or directory
     * @return void
     */
    protected function trackFile(string $filePath)
    {
        $this->filesToCleanup[] = $filePath;
    }

    /**
     * Delete all tracked files and directories
     *
     * Iterates through $filesToCleanup and removes each item. Directories are
     * deleted recursively (all contents removed first). Called automatically
     * by tearDown() - you typically don't need to call this manually.
     *
     * @return void
     */
    protected function cleanupTrackedFiles()
    {
        foreach ($this->filesToCleanup as $file) {
            if (file_exists($file)) {
                if (is_dir($file)) {
                    // Recursively delete directory
                    $items = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($file, \RecursiveDirectoryIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::CHILD_FIRST
                    );

                    foreach ($items as $item) {
                        $todo = ($item->isDir() ? 'rmdir' : 'unlink');
                        $todo($item->getRealPath());
                    }

                    rmdir($file);
                } else {
                    // Delete file
                    unlink($file);
                }
            }
        }
    }

    // =======================================================================
    // CUSTOM ASSERTIONS
    // =======================================================================

    /**
     * Assert response contains expected string
     *
     * Verifies that controller response output includes the specified substring.
     * Useful for checking if specific content, messages, or HTML elements appear
     * in the rendered output.
     *
     * @param string $needle String to search for in response
     * @param string $response Response output from request() method
     * @param string $message Optional custom failure message
     * @return void
     */
    protected function assertResponseContains(string $needle, string $response, string $message = '')
    {
        $this->assertStringContainsString(
            $needle,
            $response,
            $message ?: "Response should contain '{$needle}'"
        );
    }

    /**
     * Assert response does NOT contain string
     *
     * Verifies that controller response output does NOT include the specified
     * substring. Useful for ensuring error messages, sensitive data, or unwanted
     * content is absent from rendered output.
     *
     * @param string $needle String that should NOT appear in response
     * @param string $response Response output from request() method
     * @param string $message Optional custom failure message
     * @return void
     */
    protected function assertResponseNotContains(string $needle, string $response, string $message = '')
    {
        $this->assertStringNotContainsString(
            $needle,
            $response,
            $message ?: "Response should not contain '{$needle}'"
        );
    }

    /**
     * Assert view produced output
     *
     * Verifies that response is not empty, indicating the controller successfully
     * rendered a view or produced output. Useful for basic smoke tests.
     *
     * @param string $response Response output from request() method
     * @param string $message Optional custom failure message
     * @return void
     */
    protected function assertViewRendered(string $response, string $message = '')
    {
        $this->assertNotEmpty($response, $message ?: 'View should produce output');
    }

    /**
     * Assert session contains key
     *
     * Verifies that $_SESSION has the specified key. Useful for testing that
     * controllers set session data (user login, flash messages, etc.).
     *
     * @param string $key Session key to check for
     * @return void
     */
    protected function assertSessionHas(string $key)
    {
        $this->assertArrayHasKey($key, $_SESSION, "Session should have key '{$key}'");
    }

    /**
     * Assert session does NOT contain key
     *
     * Verifies that $_SESSION does NOT have the specified key. Useful for testing
     * logout functionality or ensuring sensitive data is cleared.
     *
     * @param string $key Session key that should NOT exist
     * @return void
     */
    protected function assertSessionMissing(string $key)
    {
        $this->assertArrayNotHasKey($key, $_SESSION, "Session should not have key '{$key}'");
    }

    /**
     * Assert response is valid JSON and return decoded data
     *
     * Verifies that response is valid JSON, then decodes and returns it as an
     * associative array. Useful for testing API endpoints or AJAX responses.
     *
     * Example:
     *   $response = $this->request('api/users/123');
     *   $data = $this->assertJsonResponse($response);
     *   $this->assertEquals('success', $data['status']);
     *   $this->assertEquals(123, $data['user']['id']);
     *
     * @param string $response Response output from request() method
     * @return array Decoded JSON as associative array
     */
    protected function assertJsonResponse(string $response)
    {
        $data = json_decode($response, true);
        $this->assertNotNull($data, 'Response should be valid JSON');
        return $data;
    }
}
