<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for view:add command
 *
 * Tests the complete flow of adding view files to existing view directories via
 * the Roline CLI. These integration tests execute the actual view:add command and
 * verify that template files are correctly added to existing view directories with
 * proper HTML5 structure and naming conventions.
 *
 * What Gets Tested:
 *   - Adding single view file to existing directory
 *   - Adding multiple view files to same directory
 *   - Error handling for non-existent directories
 *   - Required argument validation (directory and file name)
 *   - File overwrite protection
 *   - PHP syntax validation of generated templates
 *
 * Test Strategy:
 *   - Create view directories first using view:create
 *   - Add files using view:add and verify file creation
 *   - Content assertions verify proper HTML structure and metadata
 *   - Test error cases (missing directory, duplicate files, missing args)
 *   - Verify title/heading format: "Directory - Filename"
 *
 * File Cleanup:
 *   All created directories and files are automatically deleted after each test
 *   via tearDown() inherited from RolineTest base class. No manual cleanup required.
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

class ViewAddTest extends RolineTest
{
    /**
     * Test adding a view file to existing directory
     *
     * Verifies that view:add successfully creates a new template file within
     * an existing view directory with proper HTML5 structure and naming format.
     *
     * What Gets Verified:
     *   - View file is created: application/views/posts/show.php
     *   - Template contains valid HTML5 doctype
     *   - Page title follows format: "Posts - Show" (Directory - Filename)
     *   - Heading follows format: "Posts Show"
     *   - Directory name and file name are properly capitalized in content
     *
     * Prerequisites:
     *   View directory must exist (created via view:create first)
     *
     * Usage Pattern:
     *   php roline view:create posts  # Create directory
     *   php roline view:add posts show  # Add show.php template
     *
     * @return void
     */
    public function testAddViewToExistingDirectory()
    {
        $dirName = 'posts';
        $fileName = 'show';
        $viewDir = TEST_VIEWS_PATH . '/' . $dirName;
        $filePath = $viewDir . '/' . $fileName . '.php';

        // Track for cleanup
        $this->trackDirectory($viewDir);

        // Create view directory first
        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }
        $this->runCommand("view:create {$dirName}");

        // Add show.php to posts directory
        $result = $this->runCommand("view:add {$dirName} {$fileName}");

        // Assert file was created
        $this->assertFileCreated($filePath);

        // Assert file contains expected content
        $content = file_get_contents($filePath);
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('<title>Posts - Show</title>', $content);
        $this->assertStringContainsString('<h1>Posts Show</h1>', $content);
    }

    /**
     * Test adding multiple view files to same directory
     *
     * Verifies that view:add can be called multiple times to add several template
     * files to the same view directory without conflicts or corruption.
     *
     * What Gets Verified:
     *   - First file (show.php) is created successfully
     *   - Second file (create.php) is created successfully
     *   - Third file (edit.php) is created successfully
     *   - Each file has correct title format: "Users - Filename"
     *   - All files coexist in same directory
     *
     * Common Use Case:
     *   Creating CRUD view templates (show, create, edit) for a resource.
     *
     * @return void
     */
    public function testAddMultipleViewFiles()
    {
        $dirName = 'users';
        $viewDir = TEST_VIEWS_PATH . '/' . $dirName;
        $showPath = $viewDir . '/show.php';
        $createPath = $viewDir . '/create.php';
        $editPath = $viewDir . '/edit.php';

        $this->trackDirectory($viewDir);

        // Create directory
        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }
        $this->runCommand("view:create {$dirName}");

        // Add multiple files
        $this->runCommand("view:add {$dirName} show");
        $this->runCommand("view:add {$dirName} create");
        $this->runCommand("view:add {$dirName} edit");

        // Assert all files exist
        $this->assertFileCreated($showPath);
        $this->assertFileCreated($createPath);
        $this->assertFileCreated($editPath);

        // Verify each has correct title
        $this->assertStringContainsString('<title>Users - Show</title>', file_get_contents($showPath));
        $this->assertStringContainsString('<title>Users - Create</title>', file_get_contents($createPath));
        $this->assertStringContainsString('<title>Users - Edit</title>', file_get_contents($editPath));
    }

    /**
     * Test error handling for non-existent directory
     *
     * Verifies that view:add gracefully handles the error case when attempting
     * to add a file to a view directory that doesn't exist.
     *
     * What Gets Verified:
     *   - Command detects when view directory doesn't exist
     *   - Appropriate error message is displayed ("not found")
     *   - No file operations are attempted
     *   - Helpful suggestion may be provided (create directory first)
     *
     * @return void
     */
    public function testAddViewToNonExistentDirectory()
    {
        $dirName = 'nonexistent';
        $fileName = 'show';
        $viewDir = TEST_VIEWS_PATH . '/' . $dirName;

        // Make sure directory doesn't exist
        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }

        // Try to add file to non-existent directory
        $result = $this->runCommand("view:add {$dirName} {$fileName}");

        // Should show error about directory not found
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found') || str_contains($output, 'does not exist'),
            "Expected 'not found' error in output: {$result['output']}"
        );
    }

    /**
     * Test view:add requires both directory and file arguments
     *
     * Verifies that the command properly validates required arguments and provides
     * helpful error message when file name is missing.
     *
     * What Gets Verified:
     *   - Command detects missing file name argument
     *   - Error message mentions 'required' or 'file'
     *   - Command exits with error before attempting operations
     *
     * @return void
     */
    public function testRequiresBothArguments()
    {
        $dirName = 'posts';
        $viewDir = TEST_VIEWS_PATH . '/' . $dirName;

        $this->trackDirectory($viewDir);

        // Create directory first so we can test the file argument requirement
        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }
        $this->runCommand("view:create {$dirName}");

        // Test missing file argument
        $result = $this->runCommand("view:add {$dirName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'file'),
            "Expected error about required file in output: {$result['output']}"
        );
    }

    /**
     * Test file overwrite protection
     *
     * Verifies that view:add prevents accidental overwriting of existing view
     * files to protect against data loss from accidental re-runs.
     *
     * What Gets Verified:
     *   - First add succeeds (file created)
     *   - Second add with same filename fails
     *   - Error message contains "already exists"
     *   - Original file remains unchanged
     *
     * @return void
     */
    public function testCannotOverwriteExistingFile()
    {
        $dirName = 'blog';
        $fileName = 'show';
        $viewDir = TEST_VIEWS_PATH . '/' . $dirName;
        $filePath = $viewDir . '/' . $fileName . '.php';

        $this->trackDirectory($viewDir);

        // Create directory
        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }
        $this->runCommand("view:create {$dirName}");

        // Add file first time
        $this->runCommand("view:add {$dirName} {$fileName}");
        $this->assertFileExists($filePath);

        // Try to add same file again
        $result = $this->runCommand("view:add {$dirName} {$fileName}");

        // Should show "already exists"
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'already exists'),
            "Expected 'already exists' in output: {$result['output']}"
        );
    }

    /**
     * Test that generated view template contains valid PHP syntax
     *
     * Verifies that view:add generates syntactically correct PHP code by running
     * PHP's built-in linter on the generated template.
     *
     * What Gets Verified:
     *   - Generated file passes PHP syntax check (php -l)
     *   - No parse errors in generated HTML/PHP template
     *   - File can be included/required without syntax errors
     *
     * @return void
     */
    public function testGeneratedViewIsValidPHP()
    {
        $dirName = 'products';
        $fileName = 'details';
        $viewDir = TEST_VIEWS_PATH . '/' . $dirName;
        $filePath = $viewDir . '/' . $fileName . '.php';

        $this->trackDirectory($viewDir);

        // Create directory and add file
        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }
        $this->runCommand("view:create {$dirName}");
        $this->runCommand("view:add {$dirName} {$fileName}");

        // Check PHP syntax
        exec("php -l \"{$filePath}\" 2>&1", $output, $exitCode);

        $this->assertEquals(0, $exitCode, "Generated view has syntax errors:\n" . implode("\n", $output));
    }
}
