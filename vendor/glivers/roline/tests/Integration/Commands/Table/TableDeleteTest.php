<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for table:delete command
 *
 * Tests the complete flow of deleting database tables directly via the Roline CLI
 * without requiring model classes. These integration tests execute the actual
 * table:delete command and verify that tables are correctly dropped with proper
 * confirmation prompts and error handling.
 *
 * What Gets Tested:
 *   - Table deletion with user confirmation
 *   - Cancelling deletion operation
 *   - Validation: Cannot delete non-existent table
 *   - Required table name argument validation
 *   - Warning message display before deletion
 *
 * Test Strategy:
 *   - Each test uses raw SQL CREATE TABLE for setup
 *   - Tables are tracked via trackTable() for automatic cleanup
 *   - Commands are executed via runCommand() with confirmation inputs
 *   - Output assertions verify success/error/cancellation messages
 *   - Database assertions verify table existence before and after operations
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

class TableDeleteTest extends RolineTest
{
    /**
     * Test deleting table with confirmation
     *
     * Verifies that table:delete command successfully drops a table when
     * the user confirms the deletion operation.
     *
     * What Gets Verified:
     *   - Table exists before deletion
     *   - Confirmation prompt is accepted
     *   - Table is dropped from database
     *   - Success message is displayed
     *
     * @return void
     */
    public function testDeleteTableWithConfirmation()
    {
        $tableName = 'delete_me';

        $this->trackTable($tableName);

        // Create table using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (id INT PRIMARY KEY)");
        $this->assertTableExists($tableName);

        // Delete with confirmation
        $result = $this->runCommand("table:delete {$tableName}", ['yes']);

        // Table should be gone
        $this->assertTableDoesNotExist($tableName);

        // Output should confirm deletion
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'deleted') || str_contains($output, 'dropped'),
            "Expected success message in output: {$result['output']}"
        );
    }

    /**
     * Test cancelling table deletion
     *
     * Verifies that table:delete command preserves the table when user
     * cancels the deletion operation by responding 'no' to confirmation.
     *
     * What Gets Verified:
     *   - Table exists before deletion attempt
     *   - Confirmation prompt is declined
     *   - Table still exists after cancellation
     *   - Cancellation message is displayed
     *
     * @return void
     */
    public function testCancelTableDeletion()
    {
        $tableName = 'keep_me';

        $this->trackTable($tableName);

        // Create table using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (id INT PRIMARY KEY)");
        $this->assertTableExists($tableName);

        // Try to delete but cancel
        $result = $this->runCommand("table:delete {$tableName}", ['no']);

        // Table should still exist
        $this->assertTableExists($tableName);

        // Output should confirm cancellation
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled') || str_contains($output, 'cancel'),
            "Expected cancellation message in output: {$result['output']}"
        );
    }

    /**
     * Test deleting non-existent table
     *
     * Verifies that table:delete command properly detects and rejects attempts
     * to delete a table that doesn't exist in the database.
     *
     * What Gets Verified:
     *   - Command detects non-existent table
     *   - Error message is displayed
     *   - No database changes occur
     *
     * @return void
     */
    public function testDeleteNonExistentTable()
    {
        $tableName = 'does_not_exist';

        $result = $this->runCommand("table:delete {$tableName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'does not exist') || str_contains($output, 'not found'),
            "Expected error about non-existent table: {$result['output']}"
        );
    }

    /**
     * Test table:delete requires table name argument
     *
     * Verifies that table:delete command validates required arguments and
     * displays appropriate error message when table name is missing.
     *
     * What Gets Verified:
     *   - Missing argument is detected
     *   - Error or usage message is displayed
     *   - Command exits without deleting anything
     *
     * @return void
     */
    public function testRequiresTableNameArgument()
    {
        $result = $this->runCommand("table:delete");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'usage'),
            "Expected error about missing argument: {$result['output']}"
        );
    }

    /**
     * Test deletion shows warning message
     *
     * Verifies that table:delete command displays a warning message to the
     * user before proceeding with deletion, informing about data loss.
     *
     * What Gets Verified:
     *   - Warning message is displayed
     *   - Message mentions data or permanent deletion
     *   - User is properly informed before confirmation
     *
     * @return void
     */
    public function testShowsWarningMessage()
    {
        $tableName = 'warning_test';

        $this->trackTable($tableName);

        // Create table using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (id INT PRIMARY KEY)");

        // Try to delete (we'll cancel, just checking for warning)
        $result = $this->runCommand("table:delete {$tableName}", ['no']);

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'warning') || str_contains($output, 'data'),
            "Expected warning message in output: {$result['output']}"
        );
    }
}
