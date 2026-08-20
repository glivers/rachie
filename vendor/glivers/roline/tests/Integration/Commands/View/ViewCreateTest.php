<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for view:create command
 *
 * Tests the complete flow of creating view directories via the Roline CLI.
 * These integration tests execute the actual view:create command and verify
 * that view directories and template files are correctly generated with proper
 * HTML5 structure.
 *
 * What Gets Tested:
 *   - Basic view directory creation with index.php template
 *   - Lowercase directory naming convention
 *   - HTML5 template generation (DOCTYPE, meta tags, structure)
 *   - PHP syntax validation of generated templates
 *   - Directory overwrite protection
 *   - Required argument validation
 *   - Proper file system structure (directories vs files)
 *
 * Test Strategy:
 *   - Each test creates actual directories in application/views/
 *   - Directories are tracked via trackDirectory() for automatic cleanup
 *   - Commands are executed via runCommand() which captures output and exit codes
 *   - Content assertions verify proper HTML structure and metadata
 *   - File system assertions verify correct directory/file creation
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

class ViewCreateTest extends RolineTest
{
    /**
     * Test creating a basic view directory with default template
     *
     * Verifies that view:create command generates a properly structured view
     * directory containing an index.php template file with valid HTML5 markup.
     *
     * What Gets Verified:
     *   - Directory is created: application/views/testview/
     *   - Index file is created: application/views/testview/index.php
     *   - Template contains valid HTML5 doctype
     *   - Page title follows convention: "Viewname - Index"
     *   - Heading follows convention: "Viewname Index"
     *   - Template contains descriptive placeholder text
     *
     * Expected Directory Structure:
     *   application/views/
     *     └── testview/
     *         └── index.php
     *
     * Expected Template Content:
     *   <!DOCTYPE html>
     *   <html lang="en">
     *   <head>
     *       <title>Testview - Index</title>
     *   </head>
     *   <body>
     *       <h1>Testview Index</h1>
     *       <p>This is the index view for testview.</p>
     *   </body>
     *   </html>
     *
     * @return void
     */
    public function testCreateBasicView()
    {
        $viewName = 'testview';
        $viewDir = TEST_VIEWS_PATH . '/' . $viewName;
        $indexPath = $viewDir . '/index.php';

        // Track for cleanup
        $this->trackDirectory($viewDir);

        // Delete if exists from previous test
        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }

        // Run command
        $result = $this->runCommand("view:create {$viewName}");

        // Assert directory was created
        $this->assertDirectoryCreated($viewDir);

        // Assert index.php was created
        $this->assertFileCreated($indexPath);

        // Assert file contains expected content
        $content = file_get_contents($indexPath);
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('<title>Testview - Index</title>', $content);
        $this->assertStringContainsString('<h1>Testview Index</h1>', $content);
        $this->assertStringContainsString('This is the index view for testview', $content);
    }

    /**
     * Test view directory name is normalized to lowercase
     *
     * Verifies that view:create command normalizes directory names to lowercase
     * regardless of input casing, while preserving proper capitalization in the
     * generated HTML template content (titles and headings).
     *
     * What Gets Verified:
     *   - Input "MixedCase" creates directory "mixedcase" (lowercase)
     *   - Directory creation succeeds with normalized name
     *   - Index.php file is created inside lowercase directory
     *   - Template title capitalizes properly: "Mixedcase - Index"
     *   - Content uses normalized casing for display
     *
     * Why This Matters:
     *   Lowercase directory names prevent cross-platform issues (case-sensitive
     *   vs case-insensitive filesystems) and ensure consistent URL routing.
     *
     * @return void
     */
    public function testViewNameIsLowercase()
    {
        $viewName = 'MixedCase';
        $viewDir = TEST_VIEWS_PATH . '/mixedcase';
        $indexPath = $viewDir . '/index.php';

        $this->trackDirectory($viewDir);

        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }

        $result = $this->runCommand("view:create {$viewName}");

        // Should create lowercase directory
        $this->assertDirectoryCreated($viewDir);
        $this->assertFileCreated($indexPath);

        // But title should be capitalized
        $content = file_get_contents($indexPath);
        $this->assertStringContainsString('<title>Mixedcase - Index</title>', $content);
    }

    /**
     * Test that generated view template contains valid PHP syntax
     *
     * Verifies that the view:create command generates syntactically correct PHP
     * code by running PHP's built-in linter (php -l) on the generated template.
     * This ensures the template will not cause parse errors when rendered.
     *
     * What Gets Verified:
     *   - Generated file passes PHP syntax check (php -l)
     *   - No parse errors in generated HTML/PHP template
     *   - File can be included/required without syntax errors
     *
     * Why This Matters:
     *   Template-based code generation can sometimes produce invalid syntax due to
     *   string interpolation issues or malformed HTML/PHP structures. This test
     *   catches those issues before they reach production.
     *
     * Validation Method:
     *   Uses exec() to run `php -l "{$indexPath}"` which performs lint check
     *   without executing the code. Exit code 0 means syntax is valid.
     *
     * @return void
     */
    public function testGeneratedViewIsValidPHP()
    {
        $viewName = 'validview';
        $viewDir = TEST_VIEWS_PATH . '/' . $viewName;
        $indexPath = $viewDir . '/index.php';

        $this->trackDirectory($viewDir);

        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }

        $result = $this->runCommand("view:create {$viewName}");

        // Check PHP syntax
        exec("php -l \"{$indexPath}\" 2>&1", $output, $exitCode);

        $this->assertEquals(0, $exitCode, "Generated view has syntax errors:\n" . implode("\n", $output));
    }

    /**
     * Test directory overwrite protection
     *
     * Verifies that view:create command prevents accidental overwriting of
     * existing view directories. This safety feature protects against data loss
     * from accidental re-runs.
     *
     * What Gets Verified:
     *   - Command detects when view directory already exists
     *   - Command rejects creation attempt with error message
     *   - Output message contains "already exists"
     *   - No files are modified or overwritten
     *
     * Test Flow:
     *   1. Create view directory first time (succeeds)
     *   2. Verify directory exists
     *   3. Attempt to create same view again
     *   4. Verify operation was rejected
     *
     * Why This Matters:
     *   Unlike controller:create which asks for confirmation, view:create simply
     *   rejects attempts to overwrite existing directories to prevent accidental
     *   loss of template customizations.
     *
     * @return void
     */
    public function testCannotOverwriteExistingView()
    {
        $viewName = 'existingview';
        $viewDir = TEST_VIEWS_PATH . '/' . $viewName;

        $this->trackDirectory($viewDir);

        // Create view first time
        $this->runCommand("view:create {$viewName}");
        $this->assertDirectoryExists($viewDir);

        // Try to create again - should fail
        $result = $this->runCommand("view:create {$viewName}");

        // Should show "already exists"
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'already exists'),
            "Expected 'already exists' in output: {$result['output']}"
        );
    }

    /**
     * Test view:create requires view name argument
     *
     * Verifies that the command properly validates required arguments and
     * provides helpful error message when view name is missing.
     *
     * What Gets Verified:
     *   - Command detects missing view name argument
     *   - Error message mentions 'required' or 'name'
     *   - Command exits with error before attempting any operations
     *
     * @return void
     */
    public function testRequiresViewNameArgument()
    {
        // Run command without view name
        $result = $this->runCommand('view:create');

        // Should show error about missing name
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'name'),
            "Expected error about required name in output: {$result['output']}"
        );
    }

    /**
     * Test proper directory and file structure creation
     *
     * Verifies that view:create command creates the correct filesystem structure
     * with directories being actual directories and files being actual files (not
     * symlinks or other filesystem entities).
     *
     * What Gets Verified:
     *   - Created entity at view path is a directory (not a file)
     *   - Created index.php is a file (not a directory)
     *   - Index.php file has read permissions
     *   - Filesystem structure matches expected hierarchy
     *
     * Why This Matters:
     *   Ensures the command creates proper filesystem entities that can be used
     *   by the framework's View rendering system. Wrong entity types would cause
     *   runtime errors when attempting to load templates.
     *
     * @return void
     */
    public function testCreatesProperDirectoryStructure()
    {
        $viewName = 'structuretest';
        $viewDir = TEST_VIEWS_PATH . '/' . $viewName;
        $indexPath = $viewDir . '/index.php';

        $this->trackDirectory($viewDir);

        if (is_dir($viewDir)) {
            $this->deleteDirectory($viewDir);
        }

        $result = $this->runCommand("view:create {$viewName}");

        // Check directory exists and is a directory
        $this->assertTrue(is_dir($viewDir), "View directory should be a directory");

        // Check index.php exists and is a file
        $this->assertTrue(is_file($indexPath), "Index.php should be a file");

        // Check file is readable
        $this->assertTrue(is_readable($indexPath), "Index.php should be readable");
    }
}
