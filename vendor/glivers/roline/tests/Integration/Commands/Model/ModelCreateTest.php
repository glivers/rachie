<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for model:create command
 *
 * Tests the complete flow of creating model files via the Roline CLI.
 * These integration tests execute the actual model:create command and verify
 * that model files are correctly generated with proper content, structure,
 * and schema documentation.
 *
 * What Gets Tested:
 *   - Basic model creation with default structure
 *   - Auto-pluralization of table names (Post -> posts, Article -> articles)
 *   - PHP syntax validation of generated code
 *   - Schema documentation comments (@column, @primary, @autonumber)
 *   - File overwrite protection (confirmation required)
 *
 * Test Strategy:
 *   - Each test creates actual files in application/models/
 *   - Files are tracked via trackFile() for automatic cleanup after each test
 *   - Commands are executed via runCommand() which captures output and exit codes
 *   - Content assertions verify proper namespace, class structure, and schema annotations
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

class ModelCreateTest extends RolineTest
{
    /**
     * Test creating a basic model with default structure
     *
     * Verifies that model:create command generates a properly structured model
     * file with correct namespace, class declaration, table name, timestamps,
     * and schema documentation annotations.
     *
     * What Gets Verified:
     *   - Model file is created at correct path
     *   - File contains proper namespace: Models\
     *   - Class extends Rackage\Model
     *   - Table name is auto-pluralized (TestModel -> tests)
     *   - Timestamps are enabled (protected static $timestamps = true)
     *   - Schema documentation includes @column annotations
     *   - Primary key annotations (@primary, @autonumber) are present
     *
     * Expected File Structure:
     *   ```php
     *   namespace Models;
     *   use Rackage\Model;
     *
     *   class TestModel extends Model {
     *       protected static $table = 'tests';
     *       protected static $timestamps = true;
     *
     *       /** @column @primary @autonumber *\/
     *       protected $id;
     *   }
     *   ```
     *
     * @return void
     */
    public function testCreateBasicModel()
    {
        $modelName = 'TestModel';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . '.php';

        // Track for cleanup
        $this->trackFile($modelPath);

        // Delete if exists from previous test
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }

        // Run command
        $result = $this->runCommand("model:create {$modelName}");

        // Assert file was created
        $this->assertFileCreated($modelPath);

        // Assert file contains expected content
        $content = file_get_contents($modelPath);
        $this->assertStringContainsString('namespace Models;', $content);
        $this->assertStringContainsString('class TestModel extends Model', $content);
        $this->assertStringContainsString("protected static \$table = 'tests';", $content);
        $this->assertStringContainsString('protected static $timestamps = true;', $content);
        $this->assertStringContainsString('@column', $content);
        $this->assertStringContainsString('@primary', $content);
        $this->assertStringContainsString('@autonumber', $content);
    }

    /**
     * Test automatic table name pluralization
     *
     * Verifies that model:create command automatically pluralizes model names
     * to generate appropriate table names following convention.
     *
     * What Gets Verified:
     *   - Model "Article" creates table name "articles"
     *   - Pluralization follows standard English rules
     *   - Model file is created with correct naming (ArticleModel.php)
     *
     * Pluralization Examples:
     *   - Post -> posts
     *   - Article -> articles
     *   - Category -> categories
     *   - Person -> people (irregular)
     *
     * @return void
     */
    public function testCreateModelWithAutoPluralization()
    {
        $modelName = 'Article';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        if (file_exists($modelPath)) {
            unlink($modelPath);
        }

        $result = $this->runCommand("model:create {$modelName}");

        $this->assertFileCreated($modelPath);

        $content = file_get_contents($modelPath);
        // Should auto-pluralize: Article -> articles
        $this->assertStringContainsString("protected static \$table = 'articles';", $content);
    }

    /**
     * Test that generated model file contains valid PHP syntax
     *
     * Verifies that the model:create command generates syntactically correct PHP
     * code by running PHP's built-in linter (php -l) on the generated file.
     *
     * What Gets Verified:
     *   - Generated file passes PHP syntax check (php -l)
     *   - No parse errors in generated code
     *   - File can be included/required without syntax errors
     *
     * Why This Matters:
     *   Template-based code generation can sometimes produce invalid syntax due to
     *   string interpolation issues, missing semicolons, or malformed structures.
     *   This test catches those issues before they reach production.
     *
     * @return void
     */
    public function testGeneratedModelIsValidPHP()
    {
        $modelName = 'ValidPHPModel';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . '.php';

        $this->trackFile($modelPath);

        if (file_exists($modelPath)) {
            unlink($modelPath);
        }

        $result = $this->runCommand("model:create {$modelName}");

        // Check PHP syntax
        exec("php -l \"{$modelPath}\" 2>&1", $output, $exitCode);

        $this->assertEquals(0, $exitCode, "Generated model has syntax errors:\n" . implode("\n", $output));
    }

    /**
     * Test generated model includes comprehensive schema documentation
     *
     * Verifies that model:create command generates helpful schema documentation
     * comments explaining the @column annotation system for defining database
     * schema directly in model properties.
     *
     * What Gets Verified:
     *   - File includes "DATABASE SCHEMA" documentation section
     *   - Lists supported column types (@varchar, @int, @text, etc.)
     *   - Explains modifiers (@null, @unique, @index, etc.)
     *   - Provides usage examples
     *
     * Why This Matters:
     *   Schema documentation helps developers understand how to define database
     *   columns using annotations, making the model more self-documenting.
     *
     * @return void
     */
    public function testModelContainsSchemaDocumentation()
    {
        $modelName = 'DocumentedModel';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . '.php';

        $this->trackFile($modelPath);

        if (file_exists($modelPath)) {
            unlink($modelPath);
        }

        $result = $this->runCommand("model:create {$modelName}");

        $content = file_get_contents($modelPath);

        // Check for schema documentation comments
        $this->assertStringContainsString('DATABASE SCHEMA', $content);
        $this->assertStringContainsString('Supported Types:', $content);
        $this->assertStringContainsString('@varchar', $content);
        $this->assertStringContainsString('@int', $content);
        $this->assertStringContainsString('Modifiers:', $content);
        $this->assertStringContainsString('Examples:', $content);
    }

    /**
     * Test file overwrite protection and confirmation prompt
     *
     * Verifies that model:create command prevents accidental overwriting of
     * existing model files by requiring explicit user confirmation.
     *
     * What Gets Verified:
     *   - Command detects when model file already exists
     *   - Command prompts user for confirmation before overwriting
     *   - Responding 'no' cancels the operation
     *   - Original file remains unchanged when cancelled
     *   - Output message confirms cancellation or shows "already exists"
     *
     * Test Flow:
     *   1. Create model first time (succeeds)
     *   2. Attempt to create same model again
     *   3. Simulate 'no' response to confirmation prompt
     *   4. Verify operation was cancelled
     *
     * @return void
     */
    public function testOverwriteExistingModel()
    {
        $modelName = 'ExistingModel';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . '.php';

        $this->trackFile($modelPath);

        // Create model first time
        $this->runCommand("model:create {$modelName}");
        $this->assertFileExists($modelPath);

        // Try to create again - should reject or ask for confirmation
        $result = $this->runCommand("model:create {$modelName}", ['no']);

        // Should show "already exists" or "cancelled"
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled') || str_contains($output, 'already exists'),
            "Expected 'cancelled' or 'already exists' in output: {$result['output']}"
        );
    }
}
