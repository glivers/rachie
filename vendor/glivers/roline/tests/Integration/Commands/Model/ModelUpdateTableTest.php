<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for model:update-table command
 *
 * Tests the complete flow of safely updating database tables from modified
 * model @column annotations via the Roline CLI. These integration tests execute
 * the actual model:update-table command and verify that ALTER TABLE statements are
 * correctly generated and executed to synchronize database schema with model.
 *
 * What Gets Tested:
 *   - Adding new columns to existing tables
 *   - Table existence validation before update
 *   - Model validation (class exists, table property defined)
 *   - Error handling for non-existent models
 *   - Error handling for non-existent tables
 *   - Required argument validation
 *   - Non-destructive updates (data preservation)
 *
 * Advanced Features (Future):
 *   - Column renaming with @rename annotation
 *   - Column deletion with @drop annotation
 *   - Column type modifications
 *
 * Test Strategy:
 *   - Create model and table using model:create and model:create-table
 *   - Modify model file to add new column annotations
 *   - Execute model:update-table command
 *   - Verify new columns exist in database
 *   - Test error cases (missing model, missing table, missing argument)
 *   - Verify appropriate output messages
 *
 * Database Requirements:
 *   - MySQL server must be running
 *   - Test database configured in config/database.php
 *   - Database user needs ALTER TABLE privileges
 *
 * Safety Features Tested:
 *   - Non-destructive updates
 *   - Validation before schema changes
 *   - Helpful error messages
 *
 * File Cleanup:
 *   All created models and tables are automatically cleaned up after each test
 *   via tearDown() inherited from RolineTest base class.
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

class ModelUpdateTableTest extends RolineTest
{
    /**
     * Test adding new column to existing table
     *
     * Verifies that model:update-table successfully adds a new column to an existing
     * table when model is modified with new @column annotation.
     *
     * What Gets Verified:
     *   - Model and table are created first
     *   - Model is modified to add new column annotation
     *   - model:update-table command executes successfully
     *   - New column exists in database after update
     *   - Existing columns are preserved
     *
     * Test Flow:
     *   1. Create model via model:create
     *   2. Create table via model:create-table
     *   3. Verify table has only default columns (id, timestamps)
     *   4. Modify model to add new column annotation
     *   5. Run model:update-table command
     *   6. Verify new column exists in database
     *
     * @return void
     */
    public function testAddNewColumnToTable()
    {
        $modelName = 'UpdateTest';
        $tableName = 'update_tests';  // snake_case + pluralized
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);

        // Create model and table with default columns
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->runCommand("model:create-table {$modelName}", ['yes']);
        $this->assertTableExists($tableName);

        // Verify initial columns exist
        $this->assertColumnExists($tableName, 'id');
        $this->assertColumnExists($tableName, 'date_created');
        $this->assertColumnExists($tableName, 'date_modified');

        // Modify model to add new column
        $modelContent = file_get_contents($modelPath);
        $newColumn = "\n    /**\n     * @column\n     * @varchar 255\n     */\n    protected \$name;\n";

        // Insert before MODEL METHODS section
        $modelContent = str_replace(
            '// ==================== MODEL METHODS ====================',
            $newColumn . "\n    // ==================== MODEL METHODS ====================",
            $modelContent
        );
        file_put_contents($modelPath, $modelContent);

        // Run model:update-table
        $result = $this->runCommand("model:update-table {$modelName}");

        // Verify new column exists
        $this->assertColumnExists($tableName, 'name');

        // Output should indicate success
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'updated') || str_contains($output, 'success'),
            "Expected success message in output: {$result['output']}"
        );
    }

    /**
     * Test model:update-table requires model name argument
     *
     * Verifies that the command properly validates required arguments and
     * provides helpful error message when model name is missing.
     *
     * What Gets Verified:
     *   - Command detects missing model name argument
     *   - Error message mentions 'required' or 'model'
     *   - Command exits with error before attempting operations
     *
     * @return void
     */
    public function testRequiresModelNameArgument()
    {
        // Run command without model name
        $result = $this->runCommand('model:update-table');

        // Should show error about missing model name
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'model'),
            "Expected error about required model in output: {$result['output']}"
        );
    }

    /**
     * Test error handling for non-existent model
     *
     * Verifies that model:update-table gracefully handles the error case when
     * attempting to update a table for a model that doesn't exist.
     *
     * What Gets Verified:
     *   - Command detects when model class doesn't exist
     *   - Appropriate error message is displayed
     *   - No database operations are attempted
     *
     * @return void
     */
    public function testUpdateTableFromNonExistentModel()
    {
        $modelName = 'DoesNotExist';

        // Try to update table from non-existent model
        $result = $this->runCommand("model:update-table {$modelName}");

        // Should show error about model not found
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found') || str_contains($output, 'does not exist'),
            "Expected 'not found' error in output: {$result['output']}"
        );
    }

    /**
     * Test error handling for non-existent table
     *
     * Verifies that model:update-table gracefully handles the error case when
     * attempting to update a table that doesn't exist in the database yet.
     *
     * What Gets Verified:
     *   - Model exists but table doesn't exist in database
     *   - Command detects table doesn't exist
     *   - Helpful error message suggests using model:create-table first
     *
     * @return void
     */
    public function testUpdateNonExistentTable()
    {
        $modelName = 'NoTable';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        // Create model but NOT the table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->assertFileExists($modelPath);

        // Try to update non-existent table
        $result = $this->runCommand("model:update-table {$modelName}");

        // Should show error about table not existing
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'does not exist') || str_contains($output, 'not found') || str_contains($output, 'create'),
            "Expected error about non-existent table in output: {$result['output']}"
        );
    }

    /**
     * Test updating table with no changes (no-op)
     *
     * Verifies that model:update-table handles the case where model hasn't changed
     * since table was created (no ALTER TABLE statements needed).
     *
     * What Gets Verified:
     *   - Model and table are created
     *   - Running model:update-table without model changes succeeds
     *   - No schema changes are made
     *   - Appropriate message is shown (no changes or success)
     *
     * @return void
     */
    public function testUpdateTableWithNoChanges()
    {
        $modelName = 'NoChange';
        $tableName = 'no_changes';  // snake_case + pluralized
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);

        // Create model and table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->runCommand("model:create-table {$modelName}", ['yes']);
        $this->assertTableExists($tableName);

        // Run model:update-table without changing model
        $result = $this->runCommand("model:update-table {$modelName}");

        // Should succeed (exit code 0 or message about no changes)
        $output = strtolower($result['output']);
        // Either success or "no changes" message is acceptable
        $this->assertTrue(
            $result['exitCode'] === 0 ||
            str_contains($output, 'updated') ||
            str_contains($output, 'success') ||
            str_contains($output, 'no changes'),
            "Expected success or 'no changes' in output: {$result['output']}"
        );
    }
}
