<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for model:append command
 *
 * Tests the complete flow of adding properties to existing model files via the
 * Roline CLI using interactive prompts. These integration tests execute the actual
 * model:append command and verify that properties are correctly added with proper
 * @column annotations and inserted in the correct location.
 *
 * What Gets Tested:
 *   - Adding single property to existing model
 *   - Adding multiple properties in one operation
 *   - Validation: Cannot append to non-existent model
 *   - Generated @column annotations with proper types
 *   - Properties inserted before MODEL METHODS section
 *   - Default type handling (varchar(255))
 *   - Required model name argument validation
 *
 * Test Strategy:
 *   - Tests use model:create to set up model files
 *   - Model files are tracked via trackFile() for automatic cleanup
 *   - Commands are executed via runCommand() with interactive inputs
 *   - File content assertions verify property insertion and annotations
 *   - Output assertions verify success messages
 *   - Position verification ensures proper code insertion location
 *
 * File Cleanup:
 *   All created model files are automatically deleted after each test via
 *   tearDown() inherited from RolineTest base class. No manual cleanup required.
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

class ModelAppendTest extends RolineTest
{
    /**
     * Test appending a single property to existing model
     *
     * Verifies that model:append can add a new property with @column annotation
     * to an existing model file and that the property is properly formatted.
     *
     * What Gets Verified:
     *   - Property is added to model file
     *   - @column annotation is present
     *   - Type annotation matches specified type
     *   - Success message is displayed
     *
     * @return void
     */
    public function testAppendSingleProperty()
    {
        $modelName = 'AppendTest';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        // Create model first
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->assertFileExists($modelPath);

        // Append property: name (varchar 255)
        $result = $this->runCommand("model:append {$modelName}", [
            'name',          // property name
            'varchar(100)',  // property type
            ''               // empty to finish
        ]);

        // Read model file
        $content = file_get_contents($modelPath);

        // Verify property was added
        $this->assertStringContainsString('protected $name;', $content);
        $this->assertStringContainsString('@column', $content);
        $this->assertStringContainsString('@varchar(100)', $content);

        // Output should confirm addition
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'added') || str_contains($output, 'success'),
            "Expected success message in output: {$result['output']}"
        );
    }

    /**
     * Test appending multiple properties to existing model
     *
     * Verifies that model:append can add multiple properties in a single
     * command execution with different types.
     *
     * What Gets Verified:
     *   - All properties are added to model file
     *   - Each property has correct type annotation
     *   - Output confirms number of properties added
     *   - Multiple types are handled correctly
     *
     * @return void
     */
    public function testAppendMultipleProperties()
    {
        $modelName = 'MultiAppend';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        // Create model first
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");

        // Append multiple properties
        $result = $this->runCommand("model:append {$modelName}", [
            'title',         // property 1
            'varchar(255)',
            'status',        // property 2
            'varchar(50)',
            'views',         // property 3
            'int',
            ''               // finish
        ]);

        // Read model file
        $content = file_get_contents($modelPath);

        // Verify all properties were added
        $this->assertStringContainsString('protected $title;', $content);
        $this->assertStringContainsString('@varchar(255)', $content);
        $this->assertStringContainsString('protected $status;', $content);
        $this->assertStringContainsString('@varchar(50)', $content);
        $this->assertStringContainsString('protected $views;', $content);
        $this->assertStringContainsString('@int', $content);

        // Output should confirm number added
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, '3') && str_contains($output, 'properties'),
            "Expected message about 3 properties in output: {$result['output']}"
        );
    }

    /**
     * Test appending to non-existent model
     *
     * Verifies that model:append fails gracefully when target model doesn't exist.
     *
     * What Gets Verified:
     *   - Command detects non-existent model
     *   - Error message is displayed
     *   - No properties are added
     *
     * @return void
     */
    public function testAppendToNonExistentModel()
    {
        $modelName = 'DoesNotExist';

        // Command should fail before asking for input
        $result = $this->runCommand("model:append {$modelName}");

        // Should show error about model not found
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'does not exist') || str_contains($output, 'not found'),
            "Expected error message about non-existent model: {$result['output']}"
        );
    }

    /**
     * Test appending properties with default type
     *
     * Verifies that model:append uses varchar(255) as default when no type provided.
     *
     * What Gets Verified:
     *   - Empty type input uses default varchar(255)
     *   - Property is added with default type
     *   - Default type annotation is correct
     *
     * @return void
     */
    public function testAppendWithDefaultType()
    {
        $modelName = 'DefaultType';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        // Create model first
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");

        // Append property with empty type (should default to varchar(255))
        $result = $this->runCommand("model:append {$modelName}", [
            'email',  // property name
            '',       // empty type (use default)
            ''        // finish
        ]);

        // Read model file
        $content = file_get_contents($modelPath);

        // Verify default type was used
        $this->assertStringContainsString('protected $email;', $content);
        $this->assertStringContainsString('@varchar(255)', $content);
    }

    /**
     * Test model:append requires model name argument
     *
     * Verifies that model:append validates required arguments and displays
     * appropriate error message when model name is missing.
     *
     * What Gets Verified:
     *   - Missing argument is detected
     *   - Error or usage message is displayed
     *   - Command exits without prompting
     *
     * @return void
     */
    public function testRequiresModelNameArgument()
    {
        // Command should fail before asking for input
        $result = $this->runCommand("model:append");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'usage'),
            "Expected error message about missing argument: {$result['output']}"
        );
    }

    /**
     * Test properties inserted before MODEL METHODS section
     *
     * Verifies that new properties are inserted in the correct location
     * (before the MODEL METHODS comment).
     *
     * What Gets Verified:
     *   - Property exists in model file
     *   - MODEL METHODS section exists
     *   - Property position is before MODEL METHODS
     *   - Code structure is maintained
     *
     * @return void
     */
    public function testPropertiesInsertedBeforeModelMethods()
    {
        $modelName = 'InsertLocation';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        // Create model first
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");

        // Append property
        $this->runCommand("model:append {$modelName}", [
            'description',
            'text',
            ''
        ]);

        // Read model file
        $content = file_get_contents($modelPath);

        // Find positions
        $propertyPos = strpos($content, 'protected $description;');
        $methodsPos = strpos($content, 'MODEL METHODS');

        // Verify property comes before MODEL METHODS section
        $this->assertNotFalse($propertyPos, "Property not found in model");
        $this->assertNotFalse($methodsPos, "MODEL METHODS section not found");
        $this->assertLessThan($methodsPos, $propertyPos,
            "Property should be inserted before MODEL METHODS section");
    }
}
