<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for view:delete command
 *
 * Tests the complete flow of deleting view directories via the Roline CLI.
 * These integration tests execute the actual view:delete command and verify
 * that view directories are safely deleted with proper confirmation prompts.
 *
 * What Gets Tested:
 *   - View directory deletion with user confirmation
 *   - Cancellation of deletion when user declines
 *   - Recursive deletion (directory with multiple files)
 *   - Error handling for non-existent directories
 *   - Required argument validation
 *   - Confirmation prompt display
 *
 * Test Strategy:
 *   - Create view directories first using view:create
 *   - Test deletion with 'yes' confirmation (directory removed)
 *   - Test cancellation with 'no' confirmation (directory preserved)
 *   - Test error cases (missing directory, missing argument)
 *   - Verify appropriate output messages
 *
 * Safety Focus:
 *   These tests verify the command's safety features including confirmation
 *   prompts and proper error messages to prevent accidental data loss.
 *
 * File Cleanup:
 *   All created directories and files are automatically deleted after each test
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

class ViewDeleteTest extends RolineTest
{
    /**
     * Test deleting a view directory with confirmation
     *
     * Verifies that view:delete successfully removes a view directory and all
     * its contents when the user confirms the deletion by responding 'yes' to
     * the confirmation prompt.
     *
     * What Gets Verified:
     *   - View directory is created first
     *   - Delete command with 'yes' confirmation removes the directory
     *   - Directory no longer exists after deletion
     *   - Success message is displayed in output
     *
     * Test Flow:
     *   1. Create view directory
     *   2. Verify directory exists
     *   3. Run delete command with 'yes' input
     *   4. Verify directory was deleted
     *   5. Check success message in output
     *
     * @return void
     */
    public function testDeleteViewWithConfirmation()
    {
        $viewName = 'deleteme';
        $viewDir = TEST_VIEWS_PATH . '/' . $viewName;

        $this->trackDirectory($viewDir);

        // Create view directory first
        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }
        $this->runCommand("view:create {$viewName}");
        $this->assertDirectoryExists($viewDir);

        // Delete with 'yes' confirmation
        $result = $this->runCommand("view:delete {$viewName}", ['yes']);

        // Directory should be deleted
        $this->assertDirectoryDoesNotExist($viewDir);

        // Output should confirm deletion
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'deleted'),
            "Expected 'deleted' in output: {$result['output']}"
        );
    }

    /**
     * Test cancelling view deletion
     *
     * Verifies that view:delete preserves the view directory when the user
     * declines deletion by responding 'no' to the confirmation prompt.
     *
     * What Gets Verified:
     *   - View directory is created first
     *   - Delete command with 'no' confirmation preserves the directory
     *   - Directory still exists after cancelled deletion
     *   - Cancellation message is displayed in output
     *
     * Safety Feature:
     *   Ensures users can safely back out of deletion if they change their mind.
     *
     * @return void
     */
    public function testCancelViewDeletion()
    {
        $viewName = 'keepme';
        $viewDir = TEST_VIEWS_PATH . '/' . $viewName;

        $this->trackDirectory($viewDir);

        // Create view directory first
        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }
        $this->runCommand("view:create {$viewName}");
        $this->assertDirectoryExists($viewDir);

        // Delete with 'no' confirmation (cancel)
        $result = $this->runCommand("view:delete {$viewName}", ['no']);

        // Directory should still exist
        $this->assertDirectoryExists($viewDir);

        // Output should confirm cancellation
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled'),
            "Expected 'cancelled' in output: {$result['output']}"
        );
    }

    /**
     * Test recursive deletion of directory with multiple files
     *
     * Verifies that view:delete properly deletes a view directory containing
     * multiple template files (recursive deletion).
     *
     * What Gets Verified:
     *   - Directory with 4 files (index, show, create, edit) is created
     *   - All files exist before deletion
     *   - Delete command with confirmation removes directory
     *   - Directory and all contained files are deleted
     *
     * Why This Matters:
     *   Tests that the command performs proper recursive deletion, not just
     *   empty directory removal.
     *
     * @return void
     */
    public function testDeleteViewWithMultipleFiles()
    {
        $viewName = 'multifile';
        $viewDir = TEST_VIEWS_PATH . '/' . $viewName;

        $this->trackDirectory($viewDir);

        // Create directory with multiple files
        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }
        $this->runCommand("view:create {$viewName}");
        $this->runCommand("view:add {$viewName} show");
        $this->runCommand("view:add {$viewName} create");
        $this->runCommand("view:add {$viewName} edit");

        // Verify all files exist
        $this->assertFileExists($viewDir . '/index.php');
        $this->assertFileExists($viewDir . '/show.php');
        $this->assertFileExists($viewDir . '/create.php');
        $this->assertFileExists($viewDir . '/edit.php');

        // Delete entire directory with confirmation
        $result = $this->runCommand("view:delete {$viewName}", ['yes']);

        // All files should be gone
        $this->assertDirectoryDoesNotExist($viewDir);
        $this->assertFileDoesNotExist($viewDir . '/index.php');
        $this->assertFileDoesNotExist($viewDir . '/show.php');
        $this->assertFileDoesNotExist($viewDir . '/create.php');
        $this->assertFileDoesNotExist($viewDir . '/edit.php');
    }

    /**
     * Test error handling for non-existent directory
     *
     * Verifies that view:delete gracefully handles the error case when attempting
     * to delete a view directory that doesn't exist.
     *
     * @return void
     */
    public function testDeleteNonExistentView()
    {
        $viewName = 'doesnotexist';
        $viewDir = TEST_VIEWS_PATH . '/' . $viewName;

        // Make sure directory doesn't exist
        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }

        // Try to delete non-existent directory
        $result = $this->runCommand("view:delete {$viewName}", ['yes']);

        // Should show error about directory not found
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found'),
            "Expected 'not found' in output: {$result['output']}"
        );
    }

    /**
     * Test view:delete requires view name argument
     *
     * Verifies that the command properly validates required arguments and provides
     * helpful error message when view name is missing.
     *
     * @return void
     */
    public function testRequiresViewNameArgument()
    {
        // Run command without view name
        $result = $this->runCommand('view:delete');

        // Should show error about missing name
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'name'),
            "Expected error about required name in output: {$result['output']}"
        );
    }

    /**
     * Test delete shows confirmation prompt with details
     *
     * Verifies that view:delete displays information about what will be deleted
     * before asking for confirmation. Good UX shows users exactly what will be
     * removed before they commit to the destructive operation.
     *
     * @return void
     */
    public function testShowsConfirmationPrompt()
    {
        $viewName = 'confirmtest';
        $viewDir = TEST_VIEWS_PATH . '/' . $viewName;

        $this->trackDirectory($viewDir);

        // Create view directory
        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }
        $this->runCommand("view:create {$viewName}");

        // Delete with confirmation
        $result = $this->runCommand("view:delete {$viewName}", ['yes']);

        // Output should mention what's being deleted
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'delete') || str_contains($output, 'remove'),
            "Expected deletion prompt in output: {$result['output']}"
        );
    }
}
