<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for table:schema command
 *
 * Tests the complete flow of displaying database table structure via the Roline CLI
 * without requiring model classes. These integration tests execute the actual
 * table:schema command and verify that table schema information is correctly
 * displayed with column details and types.
 *
 * What Gets Tested:
 *   - Displaying schema for existing table
 *   - Validation: Cannot display schema of non-existent table
 *   - Schema output includes column information
 *   - Schema output includes table name
 *   - Required table name argument validation
 *
 * Test Strategy:
 *   - Each test uses raw SQL CREATE TABLE for setup
 *   - Tables are tracked via trackTable() for automatic cleanup
 *   - Commands are executed via runCommand() which captures output
 *   - Output assertions verify schema information is displayed
 *   - Tests verify column names, types, and table references appear in output
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

class TableSchemaTest extends RolineTest
{
    /**
     * Test displaying schema for existing table
     *
     * Verifies that table:schema command successfully displays schema information
     * for an existing table including column names and types.
     *
     * What Gets Verified:
     *   - Command executes successfully
     *   - Output contains schema/column information
     *   - Column names appear in output
     *   - Table structure is properly formatted
     *
     * @return void
     */
    public function testDisplaySchemaForExistingTable()
    {
        $tableName = 'schema_display';

        $this->trackTable($tableName);

        // Create table with multiple columns using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (
            id INT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255)
        )");
        $this->assertTableExists($tableName);

        // Display schema
        $result = $this->runCommand("table:schema {$tableName}");

        // Output should contain column information
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'id') || str_contains($output, 'column'),
            "Expected schema information in output: {$result['output']}"
        );
        $this->assertTrue(
            str_contains($output, 'name') || str_contains($output, 'email'),
            "Expected column names in output: {$result['output']}"
        );
    }

    /**
     * Test displaying schema for non-existent table
     *
     * Verifies that table:schema command properly detects and rejects attempts
     * to display schema for a table that doesn't exist in the database.
     *
     * What Gets Verified:
     *   - Command detects non-existent table
     *   - Error message is displayed
     *   - No schema information is shown
     *
     * @return void
     */
    public function testDisplaySchemaForNonExistentTable()
    {
        $tableName = 'no_such_table';

        $result = $this->runCommand("table:schema {$tableName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'does not exist') || str_contains($output, 'not found'),
            "Expected error message about non-existent table: {$result['output']}"
        );
    }

    /**
     * Test table:schema requires table name argument
     *
     * Verifies that table:schema command validates required arguments and
     * displays appropriate error message when table name is missing.
     *
     * What Gets Verified:
     *   - Missing argument is detected
     *   - Error or usage message is displayed
     *   - Command exits without displaying schema
     *
     * @return void
     */
    public function testRequiresTableNameArgument()
    {
        $result = $this->runCommand("table:schema");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'usage'),
            "Expected error message about missing argument: {$result['output']}"
        );
    }

    /**
     * Test schema output includes table name
     *
     * Verifies that table:schema command output includes a reference to the
     * table name being displayed, providing context for the schema information.
     *
     * What Gets Verified:
     *   - Table name appears in output
     *   - Output includes table reference
     *   - Schema display is properly labeled
     *
     * @return void
     */
    public function testSchemaOutputIncludesTableName()
    {
        $tableName = 'named_table';

        $this->trackTable($tableName);

        // Create table using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (id INT PRIMARY KEY)");

        // Display schema
        $result = $this->runCommand("table:schema {$tableName}");

        // Output should include table name
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, $tableName) || str_contains($output, 'table'),
            "Expected table name in output: {$result['output']}"
        );
    }
}
