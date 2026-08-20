<?php namespace Tests;

/**
 * Base Test Class for Rackage Framework Tests
 *
 * This is the parent class for all Rackage framework tests. It provides helpers
 * for testing framework internals (Router, Model, View, Input, Registry, etc.)
 * through the full stack.
 *
 * Architecture:
 *   - Tests run against a Rachie installation (framework needs app context)
 *   - Uses actual framework classes - no mocking
 *   - Tests verify real routing, controller dispatch, model queries, view rendering
 *   - Database access uses framework Models (not raw PDO)
 *
 * Features:
 *   - HTTP request simulation with full routing and controller dispatch
 *   - Path helpers for creating test files (controllers, models, views)
 *   - Automatic file cleanup after each test
 *   - Custom assertions for responses, sessions, JSON
 *
 * Test Strategy:
 *   Framework tests create temporary controllers/models/views, make requests
 *   through Router, and verify framework behavior. All test files auto-deleted
 *   in tearDown(). Use framework Models for database operations.
 *
 * Usage:
 *   class RouterTest extends RackageTest
 *   {
 *       public function testBasicRouting()
 *       {
 *           // Create test controller
 *           $controllerPath = $this->controllerPath('Test');
 *           $this->trackFile($controllerPath);
 *           file_put_contents($controllerPath, '<?php namespace Controllers; ...');
 *
 *           // Test routing
 *           $response = $this->request('Test/index');
 *           $this->assertResponseContains('expected output', $response);
 *       }
 *   }
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Tests
 * @package Rackage
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\Path;
use Rackage\File;
use Rackage\Input;
use Rackage\Registry;
use Rackage\Router\Router;
use PHPUnit\Framework\TestCase;

abstract class RackageTest extends TestCase
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
     * IMPORTANT: Use explicit controller names in tests. Prefix with "Test" to make
     * it clear these are test files that can be safely deleted.
     *
     * Example:
     *   $path = $this->controllerPath('TestPosts');
     *   // Returns: /path/to/application/controllers/TestPostsController.php
     *
     * @param string $name Controller name (without 'Controller' suffix)
     * @return string Full path to controller file
     */
    protected function controllerPath(string $name)
    {
        return File::join(Path::app(), 'controllers', $name . 'Controller.php');
    }

    /**
     * Get full path to a model file
     *
     * IMPORTANT: Use explicit model names in tests. Prefix with "Test" to make
     * it clear these are test files that can be safely deleted.
     *
     * Example:
     *   $path = $this->modelPath('TestPost');
     *   // Returns: /path/to/application/models/TestPostModel.php
     *
     * @param string $name Model name (without 'Model' suffix)
     * @return string Full path to model file
     */
    protected function modelPath(string $name)
    {
        return File::join(Path::app(), 'models', $name . 'Model.php');
    }

    /**
     * Get full path to a view file
     *
     * IMPORTANT: Place test views in 'test-views/' directory to make it clear
     * these are test files that can be safely deleted.
     *
     * Example:
     *   $path = $this->viewPath('test-views/posts/show');
     *   // Returns: /path/to/application/views/test-views/posts/show.php
     *
     * @param string $path View path (with .php extension)
     * @return string Full path to view file
     */
    protected function viewPath(string $path)
    {
        return File::join(Path::app(), 'views', $path);
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
     *   4. Override Registry settings if provided
     *   5. Initialize Input and Registry (Input::setGet()->setPost())
     *   6. Load route definitions (from config/routes.php OR custom array)
     *   7. Create Router instance with settings and routes
     *   8. Dispatch request and capture output
     *
     * IMPORTANT: You can inject custom routes AND override settings without
     * modifying any source files. This is the SAFE way to test routing behavior.
     *
     * Example (URL-based routing):
     *   $response = $this->request('posts/create', 'POST', [
     *       'title' => 'My Post',
     *       'body' => 'Content here'
     *   ]);
     *   $this->assertResponseContains('Post created', $response);
     *
     * Example (custom routes):
     *   $customRoutes = ['blog/*' => 'TestBlog@show/slug'];
     *   $response = $this->request('blog/my-post', 'GET', [], $customRoutes);
     *   $this->assertResponseContains('Blog Post: my-post', $response);
     *
     * Example (override default controller):
     *   $response = $this->request('', 'GET', [], null, [
     *       'default' => ['controller' => 'TestHome', 'action' => 'index']
     *   ]);
     *
     * @param string $url URL to request (without leading slash, e.g., 'posts/create')
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH)
     * @param array $data POST/PUT/PATCH data (key-value pairs)
     * @param array|null $customRoutes Custom route definitions (null = load from config/routes.php)
     * @param array $settingsOverride Override specific settings keys temporarily
     * @return string Response output (captured via output buffering)
     */
    protected function request(string $url, string $method = 'GET', array $data = [], ?array $customRoutes = null, array $settingsOverride = [])
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

        // Override settings temporarily if provided
        // Store original settings to restore later
        $originalSettings = Registry::settings();
        if (!empty($settingsOverride)) {
            $mergedSettings = array_merge($originalSettings, $settingsOverride);
            Registry::setSettings($mergedSettings);
        }

        // Initialize Input (what system/start.php does)
        Input::setGet()->setPost();
        Registry::setUrl($url);

        // Load routes: use custom routes if provided, otherwise load from config/routes.php
        // NEVER modify config/routes.php - only read from it or use custom test routes
        if ($customRoutes !== null) {
            $routes = $customRoutes;
        } else {
            $routes = require __DIR__ . '/../../../../config/routes.php';
        }

        // Create and dispatch router (what system/start.php does)
        $router = new Router(Registry::settings(), $routes);

        // Capture output using output buffering
        ob_start();
        $router->dispatch();
        $response = ob_get_clean();

        // Restore original settings
        if (!empty($settingsOverride)) {
            Registry::setSettings($originalSettings);
        }

        return $response;
    }

    // =======================================================================
    // FILE CLEANUP HELPERS
    // =======================================================================

    /**
     * Track file or directory for automatic cleanup
     *
     * CRITICAL SAFETY: Only tracks files that DON'T already exist. This prevents
     * tests from deleting OR overwriting existing application files.
     *
     * If a file already exists when trackFile() is called:
     *   - It will NOT be added to cleanup list (prevents deletion)
     *   - An exception will be thrown (prevents overwriting)
     *
     * This ensures tests never delete or modify user's actual code.
     *
     * Adds path to cleanup list. File/directory will be deleted automatically
     * in tearDown(). Useful for test files that need cleanup regardless of
     * test success or failure.
     *
     * Supports both individual files and directories (deleted recursively).
     *
     * Example:
     *   $controllerPath = $this->controllerPath('Test');
     *   $this->trackFile($controllerPath);  // Throws exception if file exists
     *   file_put_contents($controllerPath, '<?php ...');
     *   // File auto-deleted after test completes
     *
     * @param string $filePath Absolute path to file or directory
     * @return void
     * @throws \RuntimeException If file already exists
     */
    protected function trackFile(string $filePath)
    {
        // CRITICAL: Prevent overwriting existing files
        if (File::exists($filePath)->exists) {
            throw new \RuntimeException(
                "SAFETY ERROR: Cannot track file that already exists. " .
                "This prevents tests from overwriting existing application code. " .
                "File: {$filePath}"
            );
        }

        // Only track files that were created by the test
        $this->filesToCleanup[] = $filePath;
    }

    /**
     * Delete all tracked files and directories
     *
     * Iterates through $filesToCleanup and removes each item. Directories are
     * deleted recursively (all contents removed first). Called automatically
     * by tearDown() - you typically don't need to call this manually.
     *
     * Uses Rackage\File helper for clean, abstracted file operations.
     *
     * @return void
     */
    protected function cleanupTrackedFiles()
    {
        foreach ($this->filesToCleanup as $file) {
            if (File::exists($file)->exists) {
                if (File::isDir($file)->isDir) {
                    // Recursively delete directory
                    File::deleteDir($file);
                } else {
                    // Delete file
                    File::delete($file);
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
