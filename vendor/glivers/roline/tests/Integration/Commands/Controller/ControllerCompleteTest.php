<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for controller:complete command
 *
 * Tests the complete flow of creating full MVC resource scaffolding via the Roline CLI.
 * These integration tests execute the actual controller:complete command and verify that
 * controller, model, and all view files are correctly created with proper structure.
 *
 * What Gets Tested:
 *   - Creating complete MVC scaffold (controller, model, 4 views)
 *   - Controller file structure and namespace
 *   - Model file structure and properties
 *   - View files contain proper template directives
 *   - Validation: Cannot create if controller already exists
 *   - Validation: Cannot create if model already exists
 *   - Next steps information display
 *   - All four view files are created (index, show, create, edit)
 *   - Required resource name argument validation
 *
 * Test Strategy:
 *   - Tests use actual command execution via runCommand()
 *   - Files tracked via trackFile() for automatic cleanup
 *   - For error cases, pre-create files using actual commands
 *   - File content assertions verify code structure
 *   - Output assertions verify success/error messages
 *   - File count assertions verify complete scaffold creation
 *
 * File Cleanup:
 *   All created controller, model, and view files are automatically deleted after
 *   each test via tearDown() inherited from RolineTest base class.
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

class ControllerCompleteTest extends RolineTest
{
    /**
     * Test controller:complete requires resource name
     *
     * @return void
     */
    public function testRequiresResourceName()
    {
        $result = $this->runCommand("controller:complete");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'usage'),
            "Expected error about missing resource name: {$result['output']}"
        );
    }

    /**
     * Test creating complete MVC scaffold
     *
     * @return void
     */
    public function testCreateCompleteMVCScaffold()
    {
        $name = 'CompleteTest';
        $controllerPath = TEST_CONTROLLERS_PATH . "/{$name}Controller.php";
        $modelPath = TEST_MODELS_PATH . "/{$name}Model.php";
        $viewsDir = TEST_VIEWS_PATH . '/' . strtolower($name);
        $indexView = "{$viewsDir}/index.php";
        $showView = "{$viewsDir}/show.php";
        $createView = "{$viewsDir}/create.php";
        $editView = "{$viewsDir}/edit.php";

        $this->trackFile($controllerPath);
        $this->trackFile($modelPath);
        $this->trackFile($indexView);
        $this->trackFile($showView);
        $this->trackFile($createView);
        $this->trackFile($editView);

        // Run command
        $result = $this->runCommand("controller:complete {$name}");

        // Verify controller created
        $this->assertFileExists($controllerPath, "Controller should be created");

        // Verify model created
        $this->assertFileExists($modelPath, "Model should be created");

        // Verify all views created
        $this->assertFileExists($indexView, "Index view should be created");
        $this->assertFileExists($showView, "Show view should be created");
        $this->assertFileExists($createView, "Create view should be created");
        $this->assertFileExists($editView, "Edit view should be created");

        // Verify success message
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'success') || str_contains($output, 'created'),
            "Expected success message: {$result['output']}"
        );
    }

    /**
     * Test controller file has proper structure
     *
     * @return void
     */
    public function testControllerHasProperStructure()
    {
        $name = 'StructureTest';
        $controllerPath = TEST_CONTROLLERS_PATH . "/{$name}Controller.php";
        $modelPath = TEST_MODELS_PATH . "/{$name}Model.php";
        $viewsDir = TEST_VIEWS_PATH . '/' . strtolower($name);

        $this->trackFile($controllerPath);
        $this->trackFile($modelPath);
        $this->trackFile("{$viewsDir}/index.php");
        $this->trackFile("{$viewsDir}/show.php");
        $this->trackFile("{$viewsDir}/create.php");
        $this->trackFile("{$viewsDir}/edit.php");

        $result = $this->runCommand("controller:complete {$name}");

        // Read controller content
        $content = file_get_contents($controllerPath);

        // Should have namespace
        $this->assertStringContainsString('namespace Controllers;', $content);

        // Should have class definition
        $this->assertStringContainsString("class {$name}Controller", $content);

        // Should extend Controller
        $this->assertStringContainsString('extends Controller', $content);
    }

    /**
     * Test model file has proper structure
     *
     * @return void
     */
    public function testModelHasProperStructure()
    {
        $name = 'ModelStructureTest';
        $controllerPath = TEST_CONTROLLERS_PATH . "/{$name}Controller.php";
        $modelPath = TEST_MODELS_PATH . "/{$name}Model.php";
        $viewsDir = TEST_VIEWS_PATH . '/' . strtolower($name);

        $this->trackFile($controllerPath);
        $this->trackFile($modelPath);
        $this->trackFile("{$viewsDir}/index.php");
        $this->trackFile("{$viewsDir}/show.php");
        $this->trackFile("{$viewsDir}/create.php");
        $this->trackFile("{$viewsDir}/edit.php");

        $result = $this->runCommand("controller:complete {$name}");

        // Read model content
        $content = file_get_contents($modelPath);

        // Should have namespace
        $this->assertStringContainsString('namespace Models;', $content);

        // Should have class definition
        $this->assertStringContainsString("class {$name}Model", $content);

        // Should extend Model
        $this->assertStringContainsString('extends Model', $content);

        // Should have table name
        $this->assertStringContainsString('protected static $table', $content);
    }

    /**
     * Test view files contain proper content
     *
     * @return void
     */
    public function testViewFilesContainProperContent()
    {
        $name = 'ViewContentTest';
        $controllerPath = TEST_CONTROLLERS_PATH . "/{$name}Controller.php";
        $modelPath = TEST_MODELS_PATH . "/{$name}Model.php";
        $viewsDir = TEST_VIEWS_PATH . '/' . strtolower($name);
        $indexView = "{$viewsDir}/index.php";

        $this->trackFile($controllerPath);
        $this->trackFile($modelPath);
        $this->trackFile($indexView);
        $this->trackFile("{$viewsDir}/show.php");
        $this->trackFile("{$viewsDir}/create.php");
        $this->trackFile("{$viewsDir}/edit.php");

        $result = $this->runCommand("controller:complete {$name}");

        // Read index view content
        $content = file_get_contents($indexView);

        // Should contain resource name
        $this->assertStringContainsString($name, $content);

        // Should contain template directives
        $this->assertTrue(
            str_contains($content, '@loopelse') || str_contains($content, '@empty'),
            "View should contain template directives"
        );
    }

    /**
     * Test cannot create if controller already exists
     *
     * @return void
     */
    public function testCannotCreateIfControllerExists()
    {
        $name = 'ExistingController';
        $controllerPath = TEST_CONTROLLERS_PATH . "/{$name}.php";
        $modelPath = TEST_MODELS_PATH . "/{$name}Model.php";

        $this->trackFile($controllerPath);
        $this->trackFile($modelPath);

        // Create controller first using command (like ControllerCreateTest does)
        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }
        $this->runCommand("controller:create {$name}");
        $this->assertFileExists($controllerPath, "Controller should be created by controller:create");

        // Try to create complete scaffold
        $result = $this->runCommand("controller:complete {$name}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'already exists') || str_contains($output, 'failed'),
            "Expected error about existing controller: {$result['output']}"
        );

        // Model should NOT be created (rollback on error)
        $this->assertFileDoesNotExist($modelPath, "Model should not be created when controller already exists");
    }

    /**
     * Test cannot create if model already exists
     *
     * @return void
     */
    public function testCannotCreateIfModelExists()
    {
        $baseName = 'Product';  // Use base name without Model suffix
        $controllerPath = TEST_CONTROLLERS_PATH . "/{$baseName}Controller.php";
        $modelPath = TEST_MODELS_PATH . "/{$baseName}Model.php";
        $viewsDir = TEST_VIEWS_PATH . '/' . strtolower($baseName);

        $this->trackFile($controllerPath);
        $this->trackFile($modelPath);
        $this->trackFile("{$viewsDir}/index.php");

        // Create model first using command
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$baseName}");
        $this->assertFileExists($modelPath, "Model should be created by model:create");

        // Try to create complete scaffold
        $result = $this->runCommand("controller:complete {$baseName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'already exists') || str_contains($output, 'failed'),
            "Expected error about existing model: {$result['output']}"
        );

        // Controller should have been created but then rolled back
        // Views should NOT be created (rollback on error)
        $this->assertFileDoesNotExist("{$viewsDir}/index.php", "Views should not be created when model already exists");
    }

    /**
     * Test displays next steps after creation
     *
     * @return void
     */
    public function testDisplaysNextSteps()
    {
        $name = 'NextStepsTest';
        $controllerPath = TEST_CONTROLLERS_PATH . "/{$name}Controller.php";
        $modelPath = TEST_MODELS_PATH . "/{$name}Model.php";
        $viewsDir = TEST_VIEWS_PATH . '/' . strtolower($name);

        $this->trackFile($controllerPath);
        $this->trackFile($modelPath);
        $this->trackFile("{$viewsDir}/index.php");
        $this->trackFile("{$viewsDir}/show.php");
        $this->trackFile("{$viewsDir}/create.php");
        $this->trackFile("{$viewsDir}/edit.php");

        $result = $this->runCommand("controller:complete {$name}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'next steps') || str_contains($output, 'model:append'),
            "Expected next steps information: {$result['output']}"
        );
    }

    /**
     * Test creates all four view files
     *
     * @return void
     */
    public function testCreatesAllFourViewFiles()
    {
        $name = 'FourViews';
        $controllerPath = TEST_CONTROLLERS_PATH . "/{$name}Controller.php";
        $modelPath = TEST_MODELS_PATH . "/{$name}Model.php";
        $viewsDir = TEST_VIEWS_PATH . '/' . strtolower($name);

        $this->trackFile($controllerPath);
        $this->trackFile($modelPath);
        $this->trackFile("{$viewsDir}/index.php");
        $this->trackFile("{$viewsDir}/show.php");
        $this->trackFile("{$viewsDir}/create.php");
        $this->trackFile("{$viewsDir}/edit.php");

        $result = $this->runCommand("controller:complete {$name}");

        // Count files in views directory
        $files = glob("{$viewsDir}/*.php");

        $this->assertCount(4, $files, "Should create exactly 4 view files");

        // Verify specific view files exist
        $viewNames = array_map(function($file) {
            return basename($file, '.php');
        }, $files);

        $this->assertContains('index', $viewNames);
        $this->assertContains('show', $viewNames);
        $this->assertContains('create', $viewNames);
        $this->assertContains('edit', $viewNames);
    }
}
