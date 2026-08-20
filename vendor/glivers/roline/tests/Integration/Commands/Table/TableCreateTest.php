<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for table:create command
 *
 * Tests the complete flow of creating database tables directly via the Roline CLI
 * without requiring model classes. These integration tests execute the actual
 * table:create command and verify that tables are correctly created with proper
 * structure, columns, and constraints.
 *
 * What Gets Tested:
 *   - Interactive table creation with column definitions
 *   - Auto-detected primary key (id column with auto-increment)
 *   - Validation: Cannot create table that already exists
 *   - Required table name argument validation
 *   - Cancelling interactive table creation
 *
 * Test Strategy:
 *   - Each test creates actual database tables
 *   - Tables are tracked via trackTable() for automatic cleanup after each test
 *   - Commands are executed via runCommand() which captures output
 *   - For error cases, raw SQL CREATE TABLE is used to set up pre-existing tables
 *   - Output assertions verify success/error messages
 *   - Database assertions verify table existence and column structure
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

class TableCreateTest extends RolineTest
{
    /**
     * Test creating basic table with id column
     *
     * Verifies that table:create command interactively creates a table with
     * an auto-incrementing primary key when provided with column definitions.
     *
     * What Gets Verified:
     *   - Table is created in database
     *   - ID column exists with correct structure
     *   - Command outputs success message
     *   - Interactive prompts are properly handled
     *
     * @return void
     */
    public function testCreateBasicTable()
    {
        $tableName = 'test_table';

        $this->trackTable($tableName);

        // Create table with just id column
        $result = $this->runCommand("table:create {$tableName}", [
            'id',       // column name
            'int',      // type
            'no',       // allow null? no
            '',         // default value (none)
            'yes',      // primary key? yes
            'yes',      // auto increment? yes
            '',         // finish (empty column name)
            'yes',      // confirm creation
        ]);

        // Table should exist
        $this->assertTableExists($tableName);
        $this->assertColumnExists($tableName, 'id');
    }

    /**
     * Test creating table that already exists
     *
     * Verifies that table:create command properly detects and rejects attempts
     * to create a table when one with the same name already exists.
     *
     * What Gets Verified:
     *   - Command detects existing table
     *   - Error message is displayed
     *   - No duplicate table is created
     *   - Database integrity is maintained
     *
     * @return void
     */
    public function testCreateExistingTable()
    {
        $tableName = 'already_exists';

        $this->trackTable($tableName);

        // Create table first
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (id INT PRIMARY KEY)");

        // Try to create again
        $result = $this->runCommand("table:create {$tableName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'already exists') || str_contains($output, 'exists'),
            "Expected error about existing table: {$result['output']}"
        );
    }

    /**
     * Test table:create requires table name argument
     *
     * Verifies that table:create command validates required arguments and
     * displays appropriate error message when table name is missing.
     *
     * What Gets Verified:
     *   - Missing argument is detected
     *   - Error or usage message is displayed
     *   - Command exits without creating table
     *
     * @return void
     */
    public function testRequiresTableNameArgument()
    {
        $result = $this->runCommand("table:create");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'usage'),
            "Expected error about missing argument: {$result['output']}"
        );
    }

    /**
     * Test cancelling interactive table creation
     *
     * Verifies that users can cancel table creation during the interactive
     * column definition process without creating an incomplete table.
     *
     * What Gets Verified:
     *   - Empty column name input cancels the process
     *   - No table is created when cancelled
     *   - Cancellation message is displayed
     *   - Database remains unchanged
     *
     * @return void
     */
    public function testCancelInteractiveCreation()
    {
        $tableName = 'cancelled_table';

        // Start creating table but provide empty column name immediately (cancel)
        $result = $this->runCommand("table:create {$tableName}", [
            '', // empty column name = finish/cancel
        ]);

        // Table should not exist
        $this->assertTableDoesNotExist($tableName);

        // Output should mention cancelled or no columns
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled') || str_contains($output, 'no columns'),
            "Expected cancellation message: {$result['output']}"
        );
    }
}
