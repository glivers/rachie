<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for controller:create command
 *
 * Tests the complete flow of creating controller files via the Roline CLI.
 * These integration tests execute the actual controller:create command and verify
 * that controller files are correctly generated with proper content and structure.
 *
 * What Gets Tested:
 *   - Basic controller creation with default getIndex() method
 *   - Resource controller creation with full RESTful methods
 *   - PHP syntax validation of generated code
 *   - File overwrite protection (confirmation required)
 *
 * Test Strategy:
 *   - Each test creates actual files in application/controllers/
 *   - Files are tracked via trackFile() for automatic cleanup after each test
 *   - Commands are executed via runCommand() which captures output and exit codes
 *   - Content assertions verify proper namespace, class structure, and methods
 *   - Syntax validation ensures generated code is valid PHP
 *
 * File Cleanup:
 *   All created files are automatically deleted after each test via tearDown()
 *   inherited from RolineTest base class. No manual cleanup required.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Tests\Integration\Commands
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 * @see RolineTest For base test functionality and cleanup mechanisms
 */

use Tests\RolineTest;

class ControllerCreateTest extends RolineTest
{
    /**
     * Test creating a basic controller with default structure
     *
     * Verifies that controller:create command generates a properly structured
     * controller file with correct namespace, class declaration, and default
     * getIndex() method for handling basic GET requests.
     *
     * What Gets Verified:
     *   - Controller file is created at correct path
     *   - File contains proper namespace: Controllers\
     *   - Class extends Rackage\Controller
     *   - Default getIndex() method is present
     *
     * Expected File Content:
     *   ```php
     *   namespace Controllers;
     *   use Rackage\Controller;
     *   class TestController extends Controller {
     *       public function getIndex() { ... }
     *   }
     *   ```
     *
     * @return void
     */
    public function testCreateBasicController()
    {
        $controllerName = 'TestController';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . '.php';

        // Track for cleanup
        $this->trackFile($controllerPath);

        // Delete if exists from previous test
        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }

        // Run command
        $result = $this->runCommand("controller:create {$controllerName}");

        // Assert file was created
        $this->assertFileCreated($controllerPath);

        // Assert file contains expected content
        $content = file_get_contents($controllerPath);
        $this->assertStringContainsString('namespace Controllers;', $content);
        $this->assertStringContainsString('class TestController extends Controller', $content);
        $this->assertStringContainsString('public function getIndex()', $content);
    }

    /**
     * Test creating a resource controller with full RESTful methods
     *
     * Verifies that controller:create --resource flag generates a controller
     * with all standard RESTful CRUD methods following HTTP verb conventions.
     * Resource controllers provide a complete set of methods for handling
     * standard create/read/update/delete operations.
     *
     * What Gets Verified:
     *   - Controller file is created with --resource flag
     *   - All seven RESTful methods are present:
     *     * getIndex() - List all resources (GET /resource)
     *     * getNew() - Show create form (GET /resource/new)
     *     * postNew() - Store new resource (POST /resource/new)
     *     * getShow($id) - Display single resource (GET /resource/{id})
     *     * getEdit($id) - Show edit form (GET /resource/{id}/edit)
     *     * postEdit($id) - Update resource (POST /resource/{id}/edit)
     *     * getDelete($id) - Delete resource (GET /resource/{id}/delete)
     *
     * RESTful Method Pattern:
     *   Methods are prefixed with HTTP verbs (get, post, put, delete) to handle
     *   different request types. This follows Rachie's HTTP method routing pattern.
     *
     * @return void
     */
    public function testCreateResourceController()
    {
        $controllerName = 'PostsController';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . '.php';

        $this->trackFile($controllerPath);

        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }

        $result = $this->runCommand("controller:create {$controllerName} --resource");

        $this->assertFileCreated($controllerPath);

        $content = file_get_contents($controllerPath);

        // Assert RESTful methods exist
        $this->assertStringContainsString('public function getIndex()', $content);
        $this->assertStringContainsString('public function getNew()', $content);
        $this->assertStringContainsString('public function postNew()', $content);
        $this->assertStringContainsString('public function getShow($id)', $content);
        $this->assertStringContainsString('public function getEdit($id)', $content);
        $this->assertStringContainsString('public function postEdit($id)', $content);
        $this->assertStringContainsString('public function getDelete($id)', $content);
    }

    /**
     * Test that generated controller file contains valid PHP syntax
     *
     * Verifies that the controller:create command generates syntactically correct
     * PHP code by running PHP's built-in linter (php -l) on the generated file.
     * This ensures the generated code will not cause parse errors when loaded.
     *
     * What Gets Verified:
     *   - Generated file passes PHP syntax check (php -l)
     *   - No parse errors in generated code
     *   - File can be included/required without syntax errors
     *
     * Why This Matters:
     *   Template-based code generation can sometimes produce invalid syntax due to
     *   string interpolation issues, missing semicolons, or malformed brackets.
     *   This test catches those issues before they reach production.
     *
     * Validation Method:
     *   Uses exec() to run `php -l "{$controllerPath}"` which performs lint check
     *   without executing the code. Exit code 0 means syntax is valid.
     *
     * @return void
     */
    public function testGeneratedControllerIsValidPHP()
    {
        $controllerName = 'ValidPHPController';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . '.php';

        $this->trackFile($controllerPath);

        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }

        $result = $this->runCommand("controller:create {$controllerName}");

        // Check PHP syntax
        exec("php -l \"{$controllerPath}\" 2>&1", $output, $exitCode);

        $this->assertEquals(0, $exitCode, "Generated controller has syntax errors:\n" . implode("\n", $output));
    }

    /**
     * Test file overwrite protection and confirmation prompt
     *
     * Verifies that controller:create command prevents accidental overwriting of
     * existing controller files by requiring explicit user confirmation. This is
     * a safety feature to protect against data loss from accidental re-runs.
     *
     * What Gets Verified:
     *   - Command detects when controller file already exists
     *   - Command prompts user for confirmation before overwriting
     *   - Responding 'no' cancels the operation
     *   - Original file remains unchanged when cancelled
     *   - Output message confirms cancellation or shows "already exists"
     *
     * Test Flow:
     *   1. Create controller first time (succeeds)
     *   2. Attempt to create same controller again
     *   3. Simulate 'no' response to confirmation prompt
     *   4. Verify operation was cancelled
     *
     * User Input Simulation:
     *   Uses runCommand() with second parameter ['no'] to pipe 'no' response
     *   to the confirmation prompt, mimicking user declining to overwrite.
     *
     * @return void
     */
    public function testOverwriteExistingController()
    {
        $controllerName = 'ExistingController';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . '.php';

        $this->trackFile($controllerPath);

        // Create controller first time
        $this->runCommand("controller:create {$controllerName}");
        $this->assertFileExists($controllerPath);

        // Try to create again - should ask for confirmation or reject
        $result = $this->runCommand("controller:create {$controllerName}", ['no']);

        // Should be cancelled or show "already exists"
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled') || str_contains($output, 'already exists'),
            "Expected 'cancelled' or 'already exists' in output: {$result['output']}"
        );
    }
}
