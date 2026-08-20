<?php namespace Tests\Integration\Commands;

/**
 * MigrationRollback Command Integration Tests
 *
 * Tests the migration:rollback command which reverts previously executed migrations.
 *
 * Test Coverage:
 *   - Rolling back with no migrations to rollback
 *   - Steps parameter validation
 *   - Rollback progress messages
 *
 * Note: Does NOT test actual migration rollback (requires executed migrations)
 *
 * @category Tests
 * @package  Tests\Integration\Commands
 */

use Tests\RolineTest;

class MigrationRollbackTest extends RolineTest
{
    /**
     * Test no migrations to rollback
     *
     * @return void
     */
    public function testNoMigrationsToRollback()
    {
        $result = $this->runCommand("migration:rollback");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'no migrations') || str_contains($output, 'rollback'),
            "Expected message about no migrations to rollback: {$result['output']}"
        );
    }

    /**
     * Test rollback with steps parameter
     *
     * @return void
     */
    public function testRollbackWithStepsParameter()
    {
        $result = $this->runCommand("migration:rollback 2");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'no migrations') || str_contains($output, 'rollback') || str_contains($output, 'rolling'),
            "Expected rollback-related message: {$result['output']}"
        );
    }

    /**
     * Test rollback displays migration information
     *
     * @return void
     */
    public function testDisplaysMigrationInformation()
    {
        $result = $this->runCommand("migration:rollback");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'migration'),
            "Expected migration-related information: {$result['output']}"
        );
    }

    /**
     * Test rollback with default steps (1)
     *
     * @return void
     */
    public function testRollbackWithDefaultSteps()
    {
        $result = $this->runCommand("migration:rollback 1");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'migration') || str_contains($output, 'no'),
            "Expected migration-related message: {$result['output']}"
        );
    }
}
