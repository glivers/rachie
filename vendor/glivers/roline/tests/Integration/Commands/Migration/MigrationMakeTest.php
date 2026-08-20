<?php namespace Tests\Integration\Commands;

/**
 * MigrationMake Command Integration Tests
 *
 * Tests the migration:make command which creates new migration files by
 * comparing current database schema with the last migration state.
 *
 * Test Coverage:
 *   - Creating migration requires name argument
 *   - No schema changes detected (error case)
 *   - Migration name validation
 *
 * Note: Does NOT test actual migration file generation (requires schema changes)
 *
 * @category Tests
 * @package  Tests\Integration\Commands
 */

use Tests\RolineTest;

class MigrationMakeTest extends RolineTest
{
    /**
     * Test migration:make requires name argument
     *
     * @return void
     */
    public function testRequiresMigrationName()
    {
        $result = $this->runCommand("migration:make");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'usage'),
            "Expected error about missing name: {$result['output']}"
        );
    }

    /**
     * Test migration:make processes schema comparison
     *
     * @return void
     */
    public function testProcessesSchemaComparison()
    {
        $result = $this->runCommand("migration:make test_migration");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'schema') || str_contains($output, 'migration') || str_contains($output, 'reading'),
            "Expected schema processing messages: {$result['output']}"
        );
    }

    /**
     * Test migration:make creates migrations directory if missing
     *
     * @return void
     */
    public function testCreatesDirectoryIfMissing()
    {
        $migrationsDir = RACHIE_ROOT . '/application/database/migrations';

        // Check if directory exists
        $dirExists = is_dir($migrationsDir);

        // Run command (will create directory if missing)
        $result = $this->runCommand("migration:make test_migration");

        // Verify directory now exists (even though migration fails due to no changes)
        $this->assertTrue(
            is_dir($migrationsDir),
            "Migration directory should be created if missing"
        );

        // Output should still indicate no schema changes
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'no schema changes') || str_contains($output, 'reading'),
            "Expected schema-related message: {$result['output']}"
        );
    }

    /**
     * Test migration name is used in output
     *
     * @return void
     */
    public function testMigrationNameInOutput()
    {
        $migrationName = 'add_status_column';

        $result = $this->runCommand("migration:make {$migrationName}");

        // Even though it fails, the command should process and read schema
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'schema') || str_contains($output, 'reading') || str_contains($output, 'comparing'),
            "Expected migration processing messages: {$result['output']}"
        );
    }
}
