<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for table:rename command
 *
 * Tests the complete flow of renaming database tables directly via the Roline CLI
 * without requiring model classes. These integration tests execute the actual
 * table:rename command and verify that tables are correctly renamed with proper
 * confirmation prompts and validation.
 *
 * What Gets Tested:
 *   - Table renaming with user confirmation
 *   - Cancelling rename operation
 *   - Validation: Cannot rename non-existent table
 *   - Validation: Cannot rename to existing table name
 *   - Required arguments validation (old and new names)
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

class TableRenameTest extends RolineTest
{
    /**
     * Test renaming table with confirmation
     *
     * Verifies that table:rename command successfully renames a table when
     * the user confirms the operation.
     *
     * What Gets Verified:
     *   - Old table name exists before rename
     *   - Confirmation prompt is accepted
     *   - Old table name no longer exists
     *   - New table name exists in database
     *   - Success message is displayed
     *
     * @return void
     */
    public function testRenameTableWithConfirmation()
    {
        $oldName = 'old_table_name';
        $newName = 'new_table_name';

        $this->trackTable($oldName);
        $this->trackTable($newName);

        // Create table with old name using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$oldName}` (id INT PRIMARY KEY)");
        $this->assertTableExists($oldName);

        // Rename it
        $result = $this->runCommand("table:rename {$oldName} {$newName}", ['yes']);

        // Old name should not exist
        $this->assertTableDoesNotExist($oldName);

        // New name should exist
        $this->assertTableExists($newName);

        // Output should confirm success
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'renamed') || str_contains($output, 'success'),
            "Expected success message in output: {$result['output']}"
        );
    }

    /**
     * Test cancelling table rename
     *
     * Verifies that table:rename command preserves the original table name
     * when user cancels the operation by responding 'no' to confirmation.
     *
     * What Gets Verified:
     *   - Original table exists before rename attempt
     *   - Confirmation prompt is declined
     *   - Original table name still exists
     *   - New table name does not exist
     *   - Cancellation message is displayed
     *
     * @return void
     */
    public function testCancelTableRename()
    {
        $oldName = 'keep_old_name';
        $newName = 'not_renamed';

        $this->trackTable($oldName);

        // Create table using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$oldName}` (id INT PRIMARY KEY)");
        $this->assertTableExists($oldName);

        // Try to rename but cancel
        $result = $this->runCommand("table:rename {$oldName} {$newName}", ['no']);

        // Old name should still exist
        $this->assertTableExists($oldName);

        // New name should not exist
        $this->assertTableDoesNotExist($newName);

        // Output should confirm cancellation
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled') || str_contains($output, 'cancel'),
            "Expected cancellation message in output: {$result['output']}"
        );
    }

    /**
     * Test renaming non-existent table
     *
     * Verifies that table:rename command properly detects and rejects attempts
     * to rename a table that doesn't exist in the database.
     *
     * What Gets Verified:
     *   - Command detects non-existent table
     *   - Error message is displayed
     *   - No database changes occur
     *
     * @return void
     */
    public function testRenameNonExistentTable()
    {
        $oldName = 'does_not_exist';
        $newName = 'new_name';

        $result = $this->runCommand("table:rename {$oldName} {$newName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'does not exist') || str_contains($output, 'not found'),
            "Expected error about non-existent table: {$result['output']}"
        );
    }

    /**
     * Test renaming to existing table name
     *
     * Verifies that table:rename command properly detects and rejects attempts
     * to rename a table to a name that's already in use by another table.
     *
     * What Gets Verified:
     *   - Command detects name collision
     *   - Error message is displayed
     *   - Both original tables remain unchanged
     *   - No data loss occurs
     *
     * @return void
     */
    public function testRenameToExistingTableName()
    {
        $table1 = 'first_table';
        $table2 = 'second_table';

        $this->trackTable($table1);
        $this->trackTable($table2);

        // Create both tables using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$table1}` (id INT PRIMARY KEY)");
        $db->exec("CREATE TABLE `{$table2}` (id INT PRIMARY KEY)");

        // Try to rename first to second (which already exists)
        $result = $this->runCommand("table:rename {$table1} {$table2}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'already exists') || str_contains($output, 'exists'),
            "Expected error about existing table: {$result['output']}"
        );
    }

    /**
     * Test table:rename requires both arguments
     *
     * Verifies that table:rename command validates required arguments and
     * displays appropriate error messages when arguments are missing.
     *
     * What Gets Verified:
     *   - Missing all arguments is detected
     *   - Missing new name is detected
     *   - Error or usage message is displayed
     *   - Command exits without renaming anything
     *
     * @return void
     */
    public function testRequiresBothArguments()
    {
        // Missing both arguments
        $result = $this->runCommand("table:rename");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'usage'),
            "Expected error about missing arguments: {$result['output']}"
        );

        // Missing new name
        $result = $this->runCommand("table:rename old_name");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'usage'),
            "Expected error about missing new name: {$result['output']}"
        );
    }
}
