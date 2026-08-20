<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for model:rename-table command
 *
 * Tests the complete flow of renaming database tables and optionally updating
 * associated model files via the Roline CLI. These integration tests execute
 * the actual model:rename-table command and verify that RENAME TABLE statements are
 * correctly executed and model files are optionally updated.
 *
 * What Gets Tested:
 *   - Basic table rename with confirmation
 *   - Cancellation of rename operation
 *   - Error handling for non-existent models
 *   - Error handling for non-existent tables
 *   - Error handling for conflicting target names
 *   - Required argument validation (both old and new names)
 *   - Model $table property updates (optional)
 *
 * Test Strategy:
 *   - Create model and table using model:create and model:create-table
 *   - Execute model:rename-table command with new name
 *   - Verify old table no longer exists in database
 *   - Verify new table exists in database
 *   - Test error cases (missing args, missing table, conflicts)
 *   - Verify appropriate output messages
 *
 * Database Requirements:
 *   - MySQL server must be running
 *   - Test database configured in config/database.php
 *   - Database user needs ALTER TABLE privileges
 *
 * Safety Features Tested:
 *   - Confirmation prompt before rename
 *   - Validation of source and target names
 *   - Conflict detection (target table already exists)
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

class ModelRenameTableTest extends RolineTest
{
    /**
     * Test renaming table with confirmation
     *
     * Verifies that model:rename-table successfully renames a database table when
     * user confirms the operation.
     *
     * What Gets Verified:
     *   - Model and table are created first
     *   - Rename command with 'yes' confirmation renames the table
     *   - Old table no longer exists in database
     *   - New table exists in database
     *   - Success message is displayed in output
     *
     * Test Flow:
     *   1. Create model via model:create
     *   2. Create table via model:create-table
     *   3. Verify old table exists
     *   4. Run model:rename-table with new name and 'yes' confirmation
     *   5. Verify old table doesn't exist
     *   6. Verify new table exists
     *   7. Check success message in output
     *
     * NOTE: This test is currently skipped because MySQLSchema::renameTable()
     * method is not yet implemented. The model:rename-table command exists but the
     * underlying schema method needs to be added.
     *
     * @return void
     */
    public function testRenameTableWithConfirmation()
    {
        $this->markTestSkipped('MySQLSchema::renameTable() method not yet implemented');

        $oldModelName = 'OldName';
        $oldTableName = 'oldnames';
        $newTableName = 'new_table';
        $modelPath = TEST_MODELS_PATH . '/' . $oldModelName . 'Model.php';

        $this->trackFile($modelPath);
        $this->trackTable($oldTableName);
        $this->trackTable($newTableName);

        // Create model and table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$oldModelName}");
        $this->runCommand("model:create-table {$oldModelName}", ['yes']);
        $this->assertTableExists($oldTableName);

        // Rename table
        $result = $this->runCommand("model:rename-table {$oldModelName} {$newTableName}", ['yes']);

        // Old table should not exist
        $this->assertTableDoesNotExist($oldTableName);

        // New table should exist
        $this->assertTableExists($newTableName);

        // Output should confirm success
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'renamed') || str_contains($output, 'success'),
            "Expected success message in output: {$result['output']}"
        );
    }

    /**
     * Test cancelling table rename
     *
     * Verifies that model:rename-table preserves the original table name when
     * user declines the rename operation.
     *
     * What Gets Verified:
     *   - Table is created first
     *   - Rename command with 'no' confirmation preserves table
     *   - Original table still exists
     *   - Cancellation message is displayed in output
     *
     * @return void
     */
    public function testCancelTableRename()
    {
        $modelName = 'KeepName';
        $tableName = 'keep_names';  // snake_case + pluralized
        $newTableName = 'should_not_exist';
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

        // Try to rename with 'no' (cancel)
        $result = $this->runCommand("model:rename-table {$modelName} {$newTableName}", ['no']);

        // Original table should still exist
        $this->assertTableExists($tableName);

        // New table should NOT exist
        $this->assertTableDoesNotExist($newTableName);

        // Output should confirm cancellation
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled'),
            "Expected 'cancelled' in output: {$result['output']}"
        );
    }

    /**
     * Test model:rename-table requires both arguments
     *
     * Verifies that the command properly validates that both current model name
     * and new table name are provided.
     *
     * What Gets Verified:
     *   - Command detects missing new name argument
     *   - Error message mentions required arguments
     *   - Command exits with error before attempting operations
     *
     * @return void
     */
    public function testRequiresBothArguments()
    {
        // Run command without new name
        $result = $this->runCommand('model:rename-table OldModel');

        // Should show error about missing arguments
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'name'),
            "Expected error about required arguments in output: {$result['output']}"
        );
    }

    /**
     * Test error handling for non-existent model
     *
     * Verifies that model:rename-table gracefully handles the error case when
     * attempting to rename a table for a model that doesn't exist.
     *
     * @return void
     */
    public function testRenameTableFromNonExistentModel()
    {
        $modelName = 'DoesNotExist';
        $newTableName = 'newtable';

        // Try to rename table from non-existent model
        $result = $this->runCommand("model:rename-table {$modelName} {$newTableName}", ['yes']);

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
     * Verifies that model:rename-table gracefully handles the error case when
     * attempting to rename a table that doesn't exist in the database.
     *
     * What Gets Verified:
     *   - Model exists but table doesn't exist in database
     *   - Command detects table doesn't exist
     *   - Helpful error message is displayed
     *
     * @return void
     */
    public function testRenameNonExistentTable()
    {
        $modelName = 'NoTable';
        $newTableName = 'newtable';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        // Create model but NOT the table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->assertFileExists($modelPath);

        // Try to rename non-existent table
        $result = $this->runCommand("model:rename-table {$modelName} {$newTableName}", ['yes']);

        // Should show error about table not existing
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'does not exist') || str_contains($output, 'not found'),
            "Expected error about non-existent table in output: {$result['output']}"
        );
    }

    /**
     * Test error handling for conflicting target name
     *
     * Verifies that model:rename-table detects and rejects rename operations where
     * the target table name already exists (prevents table overwriting).
     *
     * What Gets Verified:
     *   - Two tables are created with different names
     *   - Attempting to rename one to match the other fails
     *   - Error message mentions conflict/already exists
     *   - Original tables remain unchanged
     *
     * @return void
     */
    public function testRenameToExistingTableName()
    {
        $model1Name = 'First';
        $model2Name = 'Second';
        $table1Name = 'firsts';
        $table2Name = 'seconds';
        $model1Path = TEST_MODELS_PATH . '/' . $model1Name . 'Model.php';
        $model2Path = TEST_MODELS_PATH . '/' . $model2Name . 'Model.php';

        $this->trackFile($model1Path);
        $this->trackFile($model2Path);
        $this->trackTable($table1Name);
        $this->trackTable($table2Name);

        // Create two models and tables
        if (file_exists($model1Path)) {
            unlink($model1Path);
        }
        if (file_exists($model2Path)) {
            unlink($model2Path);
        }

        $this->runCommand("model:create {$model1Name}");
        $this->runCommand("model:create-table {$model1Name}", ['yes']);
        $this->assertTableExists($table1Name);

        $this->runCommand("model:create {$model2Name}");
        $this->runCommand("model:create-table {$model2Name}", ['yes']);
        $this->assertTableExists($table2Name);

        // Try to rename first table to second table's name
        $result = $this->runCommand("model:rename-table {$model1Name} {$table2Name}", ['yes']);

        // Should show error about table already existing
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'already exists') || str_contains($output, 'conflict'),
            "Expected conflict error in output: {$result['output']}"
        );

        // Both original tables should still exist
        $this->assertTableExists($table1Name);
        $this->assertTableExists($table2Name);
    }
}
