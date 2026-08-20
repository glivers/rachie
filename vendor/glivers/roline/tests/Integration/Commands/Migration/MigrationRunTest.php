<?php namespace Tests\Integration\Commands;

/**
 * MigrationRun Command Integration Tests
 *
 * Tests the migration:run command which executes all pending database migrations.
 *
 * Test Coverage:
 *   - Running migrations with missing directory
 *   - No pending migrations (up to date)
 *   - Migration tracking and execution
 *
 * Note: Does NOT test actual migration execution (requires migration files)
 *
 * @category Tests
 * @package  Tests\Integration\Commands
 */

use Tests\RolineTest;

class MigrationRunTest extends RolineTest
{
    /**
     * Test running migrations with missing directory
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

        $result = $this->runCommand("migration:run");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found') || str_contains($output, 'directory'),
            "Expected error about missing directory: {$result['output']}"
        );
    }

    /**
     * Test migration:run processes migrations directory
     *
     * @return void
     */
    public function testProcessesMigrationsDirectory()
    {
        $migrationsDir = RACHIE_ROOT . '/application/database/migrations';

        // Ensure directory exists
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
            $cleanupDir = true;
        } else {
            $cleanupDir = false;
        }

        // Run command (may have pending or no pending)
        $result = $this->runCommand("migration:run");

        // Cleanup if we created directory
        if ($cleanupDir) {
            rmdir($migrationsDir);
        }

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'migration') || str_contains($output, 'running') || str_contains($output, 'no pending'),
            "Expected migration processing messages: {$result['output']}"
        );
    }

    /**
     * Test migration:run displays progress messages
     *
     * @return void
     */
    public function testDisplaysProgressMessages()
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
        $result = $this->runCommand("migration:run");

        // Cleanup if we created directory
        if ($cleanupDir) {
            rmdir($migrationsDir);
        }

        // Should have migration-related output
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'migration') || str_contains($output, 'pending'),
            "Expected migration-related messages: {$result['output']}"
        );
    }
}
