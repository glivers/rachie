<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for db:seed command
 *
 * Tests the complete flow of running database seeder classes via the Roline CLI.
 * These integration tests execute the actual db:seed command and verify proper
 * error handling and messaging without actually running seeder files.
 *
 * What Gets Tested:
 *   - Error handling when seeders directory is missing
 *   - Error handling when no seeder files are present
 *   - Error handling for non-existent seeder classes
 *   - Seeder information display in output
 *   - Directory creation and validation
 *
 * Test Strategy:
 *   - Tests focus on error cases and validation
 *   - Temporarily manipulates seeders directory for testing
 *   - Commands are executed via runCommand() which captures output
 *   - Output assertions verify error messages and feedback
 *   - Does NOT test actual seeder execution (requires seeder files)
 *   - Seeder files are temporarily renamed during tests to isolate scenarios
 *
 * Directory Safety:
 *   Tests that create or manipulate the seeders directory automatically restore
 *   original state after completion. No permanent changes to seeders directory.
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

class DbSeedTest extends RolineTest
{
    /**
     * Test seeding with missing seeders directory
     *
     * Verifies that db:seed command properly handles scenarios where the
     * seeders directory doesn't exist and displays appropriate error.
     *
     * What Gets Verified:
     *   - Missing directory is detected
     *   - Error message is displayed
     *   - Message mentions directory or not found
     *   - Command exits gracefully
     *
     * @return void
     */
    public function testSeedWithMissingSeedersDirectory()
    {
        $seedersDir = RACHIE_ROOT . '/application/database/seeders';

        // Check if seeders directory exists
        if (is_dir($seedersDir)) {
            // Directory exists, skip test (can't test missing directory)
            $this->markTestSkipped('Seeders directory exists, cannot test missing directory scenario');
            return;
        }

        // Run seed command
        $result = $this->runCommand("db:seed");

        // Output should indicate missing directory
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found') || str_contains($output, 'directory'),
            "Expected error about missing directory: {$result['output']}"
        );
    }

    /**
     * Test seeding with no seeders present
     *
     * Verifies that db:seed command properly handles scenarios where the
     * seeders directory exists but contains no seeder files.
     *
     * What Gets Verified:
     *   - Empty directory is detected
     *   - Appropriate message is displayed
     *   - Message indicates no seeders found
     *   - Directory state is restored after test
     *
     * @return void
     */
    public function testSeedWithNoSeeders()
    {
        $seedersDir = RACHIE_ROOT . '/application/database/seeders';

        // Check if seeders directory exists
        if (!is_dir($seedersDir)) {
            // Directory doesn't exist, create it temporarily
            mkdir($seedersDir, 0755, true);
            $cleanupDir = true;
        } else {
            $cleanupDir = false;
        }

        // Get existing seeder files
        $existingSeeders = glob($seedersDir . '/*Seeder.php');

        // Temporarily rename existing seeders
        $renamedFiles = [];
        foreach ($existingSeeders as $seeder) {
            $tempName = $seeder . '.bak';
            rename($seeder, $tempName);
            $renamedFiles[$seeder] = $tempName;
        }

        // Run seed command with no seeders
        $result = $this->runCommand("db:seed");

        // Restore renamed seeders
        foreach ($renamedFiles as $original => $temp) {
            rename($temp, $original);
        }

        // Remove directory if we created it
        if ($cleanupDir) {
            rmdir($seedersDir);
        }

        // Output should indicate no seeders found
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'no seeders') || str_contains($output, 'not found'),
            "Expected message about no seeders: {$result['output']}"
        );
    }

    /**
     * Test running specific seeder that doesn't exist
     *
     * Verifies that db:seed command properly handles attempts to run a
     * seeder class that doesn't exist.
     *
     * What Gets Verified:
     *   - Non-existent seeder is detected
     *   - Error message is displayed
     *   - Message indicates seeder not found or failed
     *   - Command exits with appropriate feedback
     *
     * @return void
     */
    public function testRunNonExistentSeeder()
    {
        $result = $this->runCommand("db:seed NonExistentSeeder");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found') || str_contains($output, 'failed'),
            "Expected error about non-existent seeder: {$result['output']}"
        );
    }

    /**
     * Test seed command displays seeder information
     *
     * Verifies that db:seed command displays information about available
     * seeders and execution when seeders are present.
     *
     * What Gets Verified:
     *   - Seeder execution information is displayed
     *   - Output contains seeder-related keywords
     *   - Command provides feedback about running seeders
     *
     * @return void
     */
    public function testSeedDisplaysSeederInfo()
    {
        $seedersDir = RACHIE_ROOT . '/application/database/seeders';

        // Skip if no seeders directory
        if (!is_dir($seedersDir)) {
            $this->markTestSkipped('Seeders directory does not exist');
            return;
        }

        // Get existing seeder files
        $existingSeeders = glob($seedersDir . '/*Seeder.php');

        // Skip if no seeders exist
        if (empty($existingSeeders)) {
            $this->markTestSkipped('No seeders found in directory');
            return;
        }

        // Run seed command
        $result = $this->runCommand("db:seed");

        // Output should contain seeder-related keywords
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'seeder') || str_contains($output, 'running'),
            "Expected seeder execution information: {$result['output']}"
        );
    }
}
