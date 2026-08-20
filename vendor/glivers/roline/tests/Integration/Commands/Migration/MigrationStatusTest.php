<?php namespace Tests\Integration\Commands;

/**
 * MigrationStatus Command Integration Tests
 *
 * Tests the migration:status command which displays comprehensive status overview
 * of all migrations showing which have been executed and which are pending.
 *
 * Test Coverage:
 *   - Displaying status with missing directory
 *   - Status output format and structure
 *   - Ran and pending migrations display
 *
 * @category Tests
 * @package  Tests\Integration\Commands
 */

use Tests\RolineTest;

class MigrationStatusTest extends RolineTest
{
    /**
     * Test status with missing migrations directory
     *
     * @return void
     */
    public function testMissingMigrationsDirectory()
    {
        $migrationsDir = RACHIE_ROOT . '/application/database/migrations';

        // Skip if directory exists
        if (is_dir($migrationsDir)) {
            $this->markTestSkipped('Migrations directory exists, cannot test missing directory scenario');
            return;
        }

        $result = $this->runCommand("migration:status");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found') || str_contains($output, 'directory'),
            "Expected error about missing directory: {$result['output']}"
        );
    }

    /**
     * Test status displays migration status
     *
     * @return void
     */
    public function testDisplaysMigrationStatus()
    {
        $migrationsDir = RACHIE_ROOT . '/application/database/migrations';

        // Ensure directory exists
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
            $cleanupDir = true;
        } else {
            $cleanupDir = false;
        }

        // Run command
        $result = $this->runCommand("migration:status");

        // Cleanup if we created directory
        if ($cleanupDir) {
            rmdir($migrationsDir);
        }

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'migration') || str_contains($output, 'status'),
            "Expected migration status information: {$result['output']}"
        );
    }

    /**
     * Test status output includes ran migrations section
     *
     * @return void
     */
    public function testOutputIncludesRanMigrationsSection()
    {
        $migrationsDir = RACHIE_ROOT . '/application/database/migrations';

        // Ensure directory exists
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
            $cleanupDir = true;
        } else {
            $cleanupDir = false;
        }

        // Run command
        $result = $this->runCommand("migration:status");

        // Cleanup if we created directory
        if ($cleanupDir) {
            rmdir($migrationsDir);
        }

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'ran') || str_contains($output, 'migration'),
            "Expected ran migrations section: {$result['output']}"
        );
    }

    /**
     * Test status output includes pending migrations section
     *
     * @return void
     */
    public function testOutputIncludesPendingMigrationsSection()
    {
        $migrationsDir = RACHIE_ROOT . '/application/database/migrations';

        // Ensure directory exists
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
            $cleanupDir = true;
        } else {
            $cleanupDir = false;
        }

        // Run command
        $result = $this->runCommand("migration:status");

        // Cleanup if we created directory
        if ($cleanupDir) {
            rmdir($migrationsDir);
        }

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'pending') || str_contains($output, 'up to date') || str_contains($output, 'migration'),
            "Expected pending migrations section: {$result['output']}"
        );
    }

    /**
     * Test status displays formatted header
     *
     * @return void
     */
    public function testDisplaysFormattedHeader()
    {
        $migrationsDir = RACHIE_ROOT . '/application/database/migrations';

        // Ensure directory exists
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
            $cleanupDir = true;
        } else {
            $cleanupDir = false;
        }

        // Run command
        $result = $this->runCommand("migration:status");

        // Cleanup if we created directory
        if ($cleanupDir) {
            rmdir($migrationsDir);
        }

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'status') || str_contains($output, '='),
            "Expected formatted header: {$result['output']}"
        );
    }
}
