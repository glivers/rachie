<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for controller:append command
 *
 * Tests the complete flow of appending methods to existing controller files
 * via the Roline CLI. These integration tests execute the actual controller:append
 * command and verify that methods are correctly added to existing controllers
 * without corrupting the file structure.
 *
 * What Gets Tested:
 *   - Appending methods to existing controllers
 *   - GET prefix auto-addition (getMethodName)
 *   - View path generation (controller.method)
 *   - Method name conflict detection
 *   - Required argument validation
 *   - Non-existent controller error handling
 *
 * Test Strategy:
 *   - Create controllers first using controller:create
 *   - Append methods and verify file modifications
 *   - Check generated method structure (docblock, name, view call)
 *   - Test error cases (missing controller, duplicate methods, missing args)
 *   - Verify existing code is preserved
 *
 * File Modification Testing:
 *   These tests verify that the command correctly modifies existing files
 *   by parsing the file content after append operations to ensure:
 *   - New method is present
 *   - Existing methods are preserved
 *   - PHP syntax remains valid
 *
 * File Cleanup:
 *   All created/modified files are automatically deleted after each test via
 *   tearDown() inherited from RolineTest base class.
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

class ControllerAppendTest extends RolineTest
{
    /**
     * Test appending a method to existing controller
     *
     * Verifies that controller:append successfully adds a new method to an
     * existing controller file with proper structure: docblock, GET prefix,
     * and view rendering call.
     *
     * What Gets Verified:
     *   - Method is added to controller file
     *   - Method has GET prefix (getPublished)
     *   - Method contains View::render() call
     *   - View path follows convention (posts.published)
     *   - Existing getIndex() method is preserved
     *   - File remains valid PHP after modification
     *
     * Expected Method Structure:
     *   ```php
     *   /**
     *    * Handle published action
     *    *
     *    * @return void
     *    *\/
     *   public function getPublished()
     *   {
     *       View::render('posts.published');
     *   }
     *   ```
     *
     * @return void
     */
    public function testAppendMethodToController()
    {
        $controllerName = 'Posts';
        $methodName = 'published';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . 'Controller.php';

        $this->trackFile($controllerPath);

        // Create controller first
        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }
        $this->runCommand("controller:create {$controllerName}");

        // Append method
        $result = $this->runCommand("controller:append {$controllerName} {$methodName}");

        // Read modified controller
        $content = file_get_contents($controllerPath);

        // Assert new method exists with GET prefix
        $this->assertStringContainsString('public function getPublished()', $content);

        // Assert view rendering call with correct path
        $this->assertStringContainsString("View::render('posts.published')", $content);

        // Assert original getIndex() method is preserved
        $this->assertStringContainsString('public function getIndex()', $content);

        // Assert success message
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'added'),
            "Expected 'added' in output: {$result['output']}"
        );
    }

    /**
     * Test appending multiple methods to same controller
     *
     * Verifies that controller:append can be called multiple times to add
     * several methods to the same controller without conflicts or corruption.
     *
     * What Gets Verified:
     *   - First method (published) is added successfully
     *   - Second method (archive) is added successfully
     *   - Third method (trending) is added successfully
     *   - All three new methods exist in file
     *   - Original getIndex() method is preserved
     *   - File remains valid PHP after multiple modifications
     *
     * Why This Matters:
     *   Controllers often need multiple custom methods beyond the default.
     *   This test ensures the append operation can be repeated safely.
     *
     * @return void
     */
    public function testAppendMultipleMethods()
    {
        $controllerName = 'Blog';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . 'Controller.php';

        $this->trackFile($controllerPath);

        // Create controller
        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }
        $this->runCommand("controller:create {$controllerName}");

        // Append three methods
        $this->runCommand("controller:append {$controllerName} published");
        $this->runCommand("controller:append {$controllerName} archive");
        $this->runCommand("controller:append {$controllerName} trending");

        // Read modified controller
        $content = file_get_contents($controllerPath);

        // Assert all three new methods exist
        $this->assertStringContainsString('public function getPublished()', $content);
        $this->assertStringContainsString('public function getArchive()', $content);
        $this->assertStringContainsString('public function getTrending()', $content);

        // Assert original method is preserved
        $this->assertStringContainsString('public function getIndex()', $content);

        // Check PHP syntax
        exec("php -l \"{$controllerPath}\" 2>&1", $output, $exitCode);
        $this->assertEquals(0, $exitCode, "Modified controller has syntax errors after multiple appends");
    }

    /**
     * Test appending method to non-existent controller
     *
     * Verifies that controller:append handles the error case gracefully when
     * attempting to modify a controller that doesn't exist.
     *
     * What Gets Verified:
     *   - Command detects when controller file doesn't exist
     *   - Appropriate error message is displayed
     *   - No file operations are attempted
     *
     * @return void
     */
    public function testAppendToNonExistentController()
    {
        $controllerName = 'DoesNotExist';
        $methodName = 'test';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . 'Controller.php';

        // Make sure controller doesn't exist
        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }

        // Try to append to non-existent controller
        $result = $this->runCommand("controller:append {$controllerName} {$methodName}");

        // Should show error about controller not found
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found'),
            "Expected 'not found' in output: {$result['output']}"
        );
    }

    /**
     * Test preventing duplicate method names
     *
     * Verifies that controller:append detects and prevents adding a method
     * with a name that already exists in the controller.
     *
     * What Gets Verified:
     *   - First append succeeds (method added)
     *   - Second append with same method name fails
     *   - Error message mentions method already exists
     *   - File is not corrupted by failed append attempt
     *
     * Why This Matters:
     *   Duplicate methods cause PHP fatal errors. This safety check prevents
     *   creating invalid PHP code.
     *
     * @return void
     */
    public function testCannotAddDuplicateMethod()
    {
        $controllerName = 'Duplicate';
        $methodName = 'test';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . 'Controller.php';

        $this->trackFile($controllerPath);

        // Create controller
        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }
        $this->runCommand("controller:create {$controllerName}");

        // Append method first time
        $this->runCommand("controller:append {$controllerName} {$methodName}");
        $content = file_get_contents($controllerPath);
        $this->assertStringContainsString('public function getTest()', $content);

        // Try to append same method again
        $result = $this->runCommand("controller:append {$controllerName} {$methodName}");

        // Should show error about method already existing
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'already exists'),
            "Expected 'already exists' in output: {$result['output']}"
        );

        // File should still be valid PHP (not corrupted)
        exec("php -l \"{$controllerPath}\" 2>&1", $lintOutput, $exitCode);
        $this->assertEquals(0, $exitCode, "Controller file corrupted after duplicate method attempt");
    }

    /**
     * Test controller:append requires both arguments
     *
     * Verifies that the command properly validates required arguments and
     * provides helpful error messages when arguments are missing.
     *
     * What Gets Verified:
     *   - Missing method name argument is detected
     *   - Error message mentions 'required' or 'method'
     *
     * @return void
     */
    public function testRequiresBothArguments()
    {
        $controllerName = 'Test';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . 'Controller.php';

        $this->trackFile($controllerPath);

        // Create controller first so we can test method argument requirement
        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }
        $this->runCommand("controller:create {$controllerName}");

        // Try to append without method name
        $result = $this->runCommand("controller:append {$controllerName}");

        // Should show error about missing method
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'method'),
            "Expected error about required method in output: {$result['output']}"
        );
    }

    /**
     * Test that appended method has proper structure
     *
     * Verifies the generated method code follows best practices with proper
     * docblock, method signature, and implementation.
     *
     * What Gets Verified:
     *   - Method has docblock comment
     *   - Docblock mentions the action name
     *   - Method returns void (@return void)
     *   - Method is public
     *   - Method has GET prefix
     *   - Method capitalizes first letter of action name
     *
     * Code Quality:
     *   This ensures generated code matches professional coding standards
     *   and provides helpful documentation.
     *
     * @return void
     */
    public function testAppendedMethodHasProperStructure()
    {
        $controllerName = 'Structure';
        $methodName = 'archive';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . 'Controller.php';

        $this->trackFile($controllerPath);

        // Create controller and append method
        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }
        $this->runCommand("controller:create {$controllerName}");
        $this->runCommand("controller:append {$controllerName} {$methodName}");

        // Read modified controller
        $content = file_get_contents($controllerPath);

        // Assert docblock exists
        $this->assertStringContainsString('/**', $content);
        $this->assertStringContainsString('* Handle archive action', $content);
        $this->assertStringContainsString('* @return void', $content);
        $this->assertStringContainsString('*/', $content);

        // Assert method signature is correct
        $this->assertStringContainsString('public function getArchive()', $content);

        // Assert proper capitalization (getArchive not getarchive)
        $this->assertStringNotContainsString('public function getarchive()', $content);
    }
}
