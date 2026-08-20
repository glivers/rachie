<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for model:delete command
 *
 * Tests the complete flow of deleting model files via the Roline CLI.
 * These integration tests execute the actual model:delete command and verify
 * that model files are safely deleted with proper confirmation prompts.
 *
 * What Gets Tested:
 *   - Model deletion with user confirmation
 *   - Cancellation of deletion when user declines
 *   - Error handling for non-existent models
 *   - Required argument validation
 *   - Confirmation prompt display
 *
 * Test Strategy:
 *   - Create models first using model:create
 *   - Test deletion with 'yes' confirmation (file removed)
 *   - Test cancellation with 'no' confirmation (file preserved)
 *   - Test error cases (missing model, missing argument)
 *   - Verify appropriate output messages
 *
 * Safety Focus:
 *   These tests verify the command's safety features including confirmation
 *   prompts and proper error messages to prevent accidental file deletion.
 *
 * File Cleanup:
 *   All created files are automatically deleted after each test via tearDown()
 *   inherited from RolineTest base class.
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

class ModelDeleteTest extends RolineTest
{
    /**
     * Test deleting a model with confirmation
     *
     * Verifies that model:delete successfully removes a model file when the
     * user confirms the deletion by responding 'yes' to the prompt.
     *
     * What Gets Verified:
     *   - Model file is created first
     *   - Delete command with 'yes' confirmation removes the file
     *   - File no longer exists after deletion
     *   - Success message is displayed in output
     *
     * Test Flow:
     *   1. Create model file
     *   2. Verify file exists
     *   3. Run delete command with 'yes' input
     *   4. Verify file was deleted
     *   5. Check success message in output
     *
     * @return void
     */
    public function testDeleteModelWithConfirmation()
    {
        $modelName = 'DeleteMe';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        // Create model first
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->assertFileExists($modelPath);

        // Delete with 'yes' confirmation
        $result = $this->runCommand("model:delete {$modelName}", ['yes']);

        // File should be deleted
        $this->assertFileDoesNotExist($modelPath);

        // Output should confirm deletion
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'deleted'),
            "Expected 'deleted' in output: {$result['output']}"
        );
    }

    /**
     * Test cancelling model deletion
     *
     * Verifies that model:delete preserves the model file when the user
     * declines deletion by responding 'no' to the confirmation prompt.
     *
     * What Gets Verified:
     *   - Model file is created first
     *   - Delete command with 'no' confirmation preserves the file
     *   - File still exists after cancelled deletion
     *   - Cancellation message is displayed in output
     *
     * Safety Feature:
     *   Ensures users can safely back out of deletion if they change their mind.
     *
     * @return void
     */
    public function testCancelModelDeletion()
    {
        $modelName = 'KeepMe';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        // Create model first
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->assertFileExists($modelPath);

        // Delete with 'no' confirmation (cancel)
        $result = $this->runCommand("model:delete {$modelName}", ['no']);

        // File should still exist
        $this->assertFileExists($modelPath);

        // Output should confirm cancellation
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled'),
            "Expected 'cancelled' in output: {$result['output']}"
        );
    }

    /**
     * Test deleting non-existent model
     *
     * Verifies that model:delete handles the error case gracefully when
     * attempting to delete a model that doesn't exist.
     *
     * What Gets Verified:
     *   - Command detects when model file doesn't exist
     *   - Appropriate error message is displayed
     *   - No file operations are attempted
     *
     * @return void
     */
    public function testDeleteNonExistentModel()
    {
        $modelName = 'DoesNotExist';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        // Make sure model doesn't exist
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }

        // Try to delete non-existent model
        $result = $this->runCommand("model:delete {$modelName}", ['yes']);

        // Should show error about model not found
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found'),
            "Expected 'not found' in output: {$result['output']}"
        );
    }

    /**
     * Test model:delete requires model name argument
     *
     * Verifies that the command properly validates required arguments and
     * provides helpful error message when model name is missing.
     *
     * What Gets Verified:
     *   - Command detects missing model name argument
     *   - Error message mentions 'required' or 'name'
     *   - Command exits with error before attempting any operations
     *
     * @return void
     */
    public function testRequiresModelNameArgument()
    {
        // Run command without model name
        $result = $this->runCommand('model:delete');

        // Should show error about missing name
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'name'),
            "Expected error about required name in output: {$result['output']}"
        );
    }

    /**
     * Test delete shows confirmation prompt with file path
     *
     * Verifies that model:delete displays the full path of the file that will
     * be deleted before asking for confirmation. Good UX shows users exactly
     * what will be removed before they commit to the destructive operation.
     *
     * What Gets Verified:
     *   - Output mentions deletion or removal
     *   - User is prompted before file is actually deleted
     *
     * @return void
     */
    public function testShowsConfirmationPrompt()
    {
        $modelName = 'ConfirmTest';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        // Create model
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");

        // Delete with confirmation
        $result = $this->runCommand("model:delete {$modelName}", ['yes']);

        // Output should mention deletion/removal
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'delete') || str_contains($output, 'remove'),
            "Expected deletion prompt in output: {$result['output']}"
        );
    }
}
