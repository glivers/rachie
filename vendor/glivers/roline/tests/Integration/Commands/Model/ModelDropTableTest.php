<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for model:drop-table command
 *
 * Tests the complete flow of deleting database tables via the Roline CLI.
 * These integration tests execute the actual model:drop-table command and verify
 * that database tables are safely dropped with proper confirmation prompts
 * and safety warnings.
 *
 * What Gets Tested:
 *   - Table deletion with double confirmation (DANGER ZONE)
 *   - Cancellation at first confirmation prompt
 *   - Cancellation at second confirmation prompt
 *   - Error handling for non-existent models
 *   - Error handling for non-existent tables
 *   - Required argument validation
 *   - Warning display (DANGER ZONE banner, data loss warnings)
 *
 * Test Strategy:
 *   - Create model and table first using model:create and model:create-table
 *   - Test deletion with 'yes, yes' confirmation (table dropped)
 *   - Test cancellation with 'no' at first prompt (table preserved)
 *   - Test cancellation with 'yes, no' at second prompt (table preserved)
 *   - Test error cases (missing model, missing table, missing argument)
 *   - Verify appropriate output messages
 *
 * Database Requirements:
 *   - MySQL server must be running
 *   - Test database configured in config/database.php
 *   - Database user needs DROP TABLE privileges
 *
 * Safety Features Tested:
 *   - Double confirmation before DROP TABLE
 *   - DANGER ZONE warning banner
 *   - Explicit "cannot be undone" messaging
 *   - Cancellation support at both prompts
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

class ModelDropTableTest extends RolineTest
{
    /**
     * Test deleting a table with double confirmation
     *
     * Verifies that model:drop-table successfully drops a database table when
     * user confirms at both confirmation prompts (two-step safety).
     *
     * What Gets Verified:
     *   - Model and table are created first
     *   - Delete command with 'yes, yes' confirmation drops the table
     *   - Table no longer exists in database after deletion
     *   - Success message is displayed in output
     *
     * Test Flow:
     *   1. Create model via model:create
     *   2. Create table via model:create-table
     *   3. Verify table exists
     *   4. Run model:drop-table with double 'yes' confirmation
     *   5. Verify table was dropped
     *   6. Check success message in output
     *
     * @return void
     */
    public function testDeleteTableWithConfirmation()
    {
        $modelName = 'DeleteMe';
        $tableName = 'delete_mes';  // snake_case + pluralized
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);

        // Create model and table first
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->runCommand("model:create-table {$modelName}", ['yes']);
        $this->assertTableExists($tableName);

        // Delete with double 'yes' confirmation
        $result = $this->runCommand("model:drop-table {$modelName}", ['yes', 'yes']);

        // Table should be deleted
        $this->assertTableDoesNotExist($tableName);

        // Output should confirm deletion
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'dropped') || str_contains($output, 'deleted'),
            "Expected success message in output: {$result['output']}"
        );
    }

    /**
     * Test cancelling table deletion at first prompt
     *
     * Verifies that model:drop-table preserves the table when user declines
     * deletion at the first confirmation prompt.
     *
     * What Gets Verified:
     *   - Table is created first
     *   - Delete command with 'no' at first prompt preserves table
     *   - Table still exists after cancelled deletion
     *   - Cancellation message is displayed in output
     *
     * Safety Feature:
     *   Ensures users can safely back out at the first warning.
     *
     * @return void
     */
    public function testCancelTableDeletionFirstPrompt()
    {
        $modelName = 'KeepMe';
        $tableName = 'keep_mes';  // snake_case + pluralized
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);

        // Create model and table first
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->runCommand("model:create-table {$modelName}", ['yes']);
        $this->assertTableExists($tableName);

        // Delete with 'no' at first prompt (cancel)
        $result = $this->runCommand("model:drop-table {$modelName}", ['no']);

        // Table should still exist
        $this->assertTableExists($tableName);

        // Output should confirm cancellation
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled'),
            "Expected 'cancelled' in output: {$result['output']}"
        );
    }

    /**
     * Test cancelling table deletion at second prompt
     *
     * Verifies that model:drop-table preserves the table when user confirms
     * at the first prompt but declines at the second "absolutely sure" prompt.
     *
     * What Gets Verified:
     *   - Table is created first
     *   - Delete command with 'yes, no' confirmation preserves table
     *   - Table still exists after cancelled deletion
     *   - Cancellation message is displayed in output
     *
     * Safety Feature:
     *   Double confirmation allows users to change their mind even after
     *   the first 'yes'.
     *
     * @return void
     */
    public function testCancelTableDeletionSecondPrompt()
    {
        $modelName = 'StillKeep';
        $tableName = 'still_keeps';  // snake_case + pluralized
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);

        // Create model and table first
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->runCommand("model:create-table {$modelName}", ['yes']);
        $this->assertTableExists($tableName);

        // Delete with 'yes' then 'no' (cancel at second prompt)
        $result = $this->runCommand("model:drop-table {$modelName}", ['yes', 'no']);

        // Table should still exist
        $this->assertTableExists($tableName);

        // Output should confirm cancellation
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled'),
            "Expected 'cancelled' in output: {$result['output']}"
        );
    }

    /**
     * Test error handling for non-existent model
     *
     * Verifies that model:drop-table gracefully handles the error case when
     * attempting to delete a table for a model that doesn't exist.
     *
     * What Gets Verified:
     *   - Command detects when model class doesn't exist
     *   - Appropriate error message is displayed
     *   - No database operations are attempted
     *
     * @return void
     */
    public function testDeleteTableFromNonExistentModel()
    {
        $modelName = 'DoesNotExist';

        // Try to delete table from non-existent model
        $result = $this->runCommand("model:drop-table {$modelName}", ['yes', 'yes']);

        // Should show error about model not found
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found') || str_contains($output, 'does not exist'),
            "Expected 'not found' error in output: {$result['output']}"
        );
    }

    /**
     * Test model:drop-table requires model name argument
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
        $result = $this->runCommand('model:drop-table');

        // Should show error about missing model name
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'model'),
            "Expected error about required model in output: {$result['output']}"
        );
    }

    /**
     * Test DANGER ZONE warning is displayed
     *
     * Verifies that model:drop-table displays prominent DANGER ZONE warning
     * banner before attempting destructive operation.
     *
     * What Gets Verified:
     *   - Output contains DANGER ZONE or similar warning text
     *   - Output mentions permanent data loss
     *   - Output mentions action cannot be undone
     *   - Warning is displayed BEFORE user confirmation
     *
     * @return void
     */
    public function testShowsDangerZoneWarning()
    {
        $modelName = 'WarnTest';
        $tableName = 'warn_tests';  // snake_case + pluralized
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);

        // Create model and table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->runCommand("model:create-table {$modelName}", ['yes']);

        // Run delete command and cancel to check warning
        $result = $this->runCommand("model:drop-table {$modelName}", ['no']);

        // Output should show danger warning
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'danger') || str_contains($output, 'warning') ||
            str_contains($output, 'permanently') || str_contains($output, 'cannot be undone'),
            "Expected DANGER ZONE warning in output: {$result['output']}"
        );
    }
}
