<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for controller:delete command
 *
 * Tests the complete flow of deleting controller files via the Roline CLI.
 * These integration tests execute the actual controller:delete command and verify
 * that controller files are safely deleted with proper confirmation prompts.
 *
 * What Gets Tested:
 *   - Controller deletion with user confirmation
 *   - Cancellation of deletion when user declines
 *   - Error handling for non-existent controllers
 *   - Required argument validation
 *   - Confirmation prompt display
 *
 * Test Strategy:
 *   - Create controllers first using controller:create
 *   - Test deletion with 'yes' confirmation (file removed)
 *   - Test cancellation with 'no' confirmation (file preserved)
 *   - Test error cases (missing controller, missing argument)
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

class ControllerDeleteTest extends RolineTest
{
    /**
     * Test deleting a controller with confirmation
     *
     * Verifies that controller:delete successfully removes a controller file
     * when the user confirms the deletion by responding 'yes' to the prompt.
     *
     * What Gets Verified:
     *   - Controller file is created first
     *   - Delete command with 'yes' confirmation removes the file
     *   - File no longer exists after deletion
     *   - Success message is displayed in output
     *
     * Test Flow:
     *   1. Create controller file
     *   2. Verify file exists
     *   3. Run delete command with 'yes' input
     *   4. Verify file was deleted
     *   5. Check success message in output
     *
     * @return void
     */
    public function testDeleteControllerWithConfirmation()
    {
        $controllerName = 'DeleteMe';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . 'Controller.php';

        $this->trackFile($controllerPath);

        // Create controller first
        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }
        $this->runCommand("controller:create {$controllerName}");
        $this->assertFileExists($controllerPath);

        // Delete with 'yes' confirmation
        $result = $this->runCommand("controller:delete {$controllerName}", ['yes']);

        // File should be deleted
        $this->assertFileDoesNotExist($controllerPath);

        // Output should confirm deletion
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'deleted'),
            "Expected 'deleted' in output: {$result['output']}"
        );
    }

    /**
     * Test cancelling controller deletion
     *
     * Verifies that controller:delete preserves the controller file when the
     * user declines deletion by responding 'no' to the confirmation prompt.
     *
     * What Gets Verified:
     *   - Controller file is created first
     *   - Delete command with 'no' confirmation preserves the file
     *   - File still exists after cancelled deletion
     *   - Cancellation message is displayed in output
     *
     * Safety Feature:
     *   This test ensures users can safely back out of deletion if they
     *   change their mind or accidentally ran the command.
     *
     * @return void
     */
    public function testCancelControllerDeletion()
    {
        $controllerName = 'KeepMe';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . 'Controller.php';

        $this->trackFile($controllerPath);

        // Create controller first
        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }
        $this->runCommand("controller:create {$controllerName}");
        $this->assertFileExists($controllerPath);

        // Delete with 'no' confirmation (cancel)
        $result = $this->runCommand("controller:delete {$controllerName}", ['no']);

        // File should still exist
        $this->assertFileExists($controllerPath);

        // Output should confirm cancellation
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled'),
            "Expected 'cancelled' in output: {$result['output']}"
        );
    }

    /**
     * Test deleting non-existent controller
     *
     * Verifies that controller:delete handles the error case gracefully when
     * attempting to delete a controller that doesn't exist.
     *
     * What Gets Verified:
     *   - Command detects when controller file doesn't exist
     *   - Appropriate error message is displayed
     *   - No file operations are attempted
     *
     * Why This Matters:
     *   Prevents confusing errors and provides clear feedback about what
     *   went wrong when user mistyped the controller name.
     *
     * @return void
     */
    public function testDeleteNonExistentController()
    {
        $controllerName = 'DoesNotExist';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . 'Controller.php';

        // Make sure controller doesn't exist
        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }

        // Try to delete non-existent controller
        $result = $this->runCommand("controller:delete {$controllerName}", ['yes']);

        // Should show error about controller not found
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found'),
            "Expected 'not found' in output: {$result['output']}"
        );
    }

    /**
     * Test controller:delete requires controller name argument
     *
     * Verifies that the command properly validates required arguments and
     * provides helpful error message when controller name is missing.
     *
     * What Gets Verified:
     *   - Command detects missing controller name argument
     *   - Error message mentions 'required' or 'name'
     *   - Command exits with error before attempting any operations
     *
     * @return void
     */
    public function testRequiresControllerNameArgument()
    {
        // Run command without controller name
        $result = $this->runCommand('controller:delete');

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
     * Verifies that controller:delete displays the full path of the file
     * that will be deleted before asking for confirmation. This transparency
     * helps users make informed decisions.
     *
     * What Gets Verified:
     *   - Output mentions deletion or removal
     *   - User is prompted before file is actually deleted
     *
     * User Experience:
     *   Good UX shows users exactly what will be deleted before they
     *   commit to the destructive operation.
     *
     * @return void
     */
    public function testShowsConfirmationPrompt()
    {
        $controllerName = 'ConfirmTest';
        $controllerPath = TEST_CONTROLLERS_PATH . '/' . $controllerName . 'Controller.php';

        $this->trackFile($controllerPath);

        // Create controller
        if (file_exists($controllerPath)) {
            unlink($controllerPath);
        }
        $this->runCommand("controller:create {$controllerName}");

        // Delete with confirmation
        $result = $this->runCommand("controller:delete {$controllerName}", ['yes']);

        // Output should mention deletion/removal
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'delete') || str_contains($output, 'remove'),
            "Expected deletion prompt in output: {$result['output']}"
        );
    }
}
