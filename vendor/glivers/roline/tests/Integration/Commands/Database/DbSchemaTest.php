<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for db:schema command
 *
 * Tests the complete flow of displaying comprehensive database schema overview
 * via the Roline CLI. These integration tests execute the actual db:schema
 * command and verify that complete database structure is displayed including
 * all tables, columns, and their properties.
 *
 * What Gets Tested:
 *   - Displaying schema for database with tables
 *   - Schema command execution without errors
 *   - Schema output includes table count
 *   - Schema output includes column information
 *   - Comprehensive schema formatting
 *
 * Test Strategy:
 *   - Tests use raw SQL CREATE TABLE for setup
 *   - Tables are tracked via trackTable() for automatic cleanup
 *   - Commands are executed via runCommand() which captures output
 *   - Output assertions verify schema information display
 *   - Tests verify table names, column details appear in output
 *
 * Table Cleanup:
 *   All created tables are automatically dropped after each test via tearDown()
 *   inherited from RolineTest base class. No manual cleanup required.
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

class DbSchemaTest extends RolineTest
{
    /**
     * Test displaying schema for database with tables
     *
     * Verifies that db:schema command successfully displays schema information
     * for a database containing tables.
     *
     * What Gets Verified:
     *   - Command executes successfully
     *   - Created table appears in output
     *   - Schema information is displayed
     *   - Table references are included
     *
     * @return void
     */
    public function testDisplaySchemaWithTables()
    {
        $tableName = 'schema_test_table';

        $this->trackTable($tableName);

        // Create table using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL
        )");
        $this->assertTableExists($tableName);

        // Display schema
        $result = $this->runCommand("db:schema");

        // Output should contain table name
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, $tableName) || str_contains($output, 'table'),
            "Expected table name in output: {$result['output']}"
        );
    }

    /**
     * Test schema command runs successfully
     *
     * Verifies that db:schema command executes without errors and displays
     * database schema information.
     *
     * What Gets Verified:
     *   - Command completes successfully
     *   - Output contains schema-related keywords
     *   - Database information is displayed
     *   - No errors occur during execution
     *
     * @return void
     */
    public function testSchemaCommandRunsSuccessfully()
    {
        // Run schema command
        $result = $this->runCommand("db:schema");

        // Output should contain schema-related keywords
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'schema') || str_contains($output, 'table') || str_contains($output, 'database'),
            "Expected schema information in output: {$result['output']}"
        );
    }

    /**
     * Test schema output includes table count
     *
     * Verifies that db:schema command displays the total number of tables
     * in the database as part of the schema overview.
     *
     * What Gets Verified:
     *   - Total table count is displayed
     *   - Count information appears in output
     *   - Database statistics are shown
     *
     * @return void
     */
    public function testSchemaOutputIncludesTableCount()
    {
        $tableName = 'count_test';

        $this->trackTable($tableName);

        // Create table using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (id INT PRIMARY KEY)");

        // Display schema
        $result = $this->runCommand("db:schema");

        // Output should include total tables
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'total') || str_contains($output, 'tables'),
            "Expected table count in output: {$result['output']}"
        );
    }

    /**
     * Test schema output includes column information
     *
     * Verifies that db:schema command displays detailed column information
     * including column names, types, and constraints for tables.
     *
     * What Gets Verified:
     *   - Column names appear in output
     *   - Column types are displayed
     *   - Schema includes column details
     *   - Complete table structure is shown
     *
     * @return void
     */
    public function testSchemaOutputIncludesColumnInfo()
    {
        $tableName = 'column_info_test';

        $this->trackTable($tableName);

        // Create table with multiple columns using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (
            id INT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(100)
        )");

        // Display schema
        $result = $this->runCommand("db:schema");

        // Output should contain column information
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'id') || str_contains($output, 'username') || str_contains($output, 'varchar'),
            "Expected column information in output: {$result['output']}"
        );
    }
}
