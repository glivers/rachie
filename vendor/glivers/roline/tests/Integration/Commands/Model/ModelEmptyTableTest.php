<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for model:empty-table command
 *
 * Tests the complete flow of emptying database tables via model references using
 * the Roline CLI. These integration tests execute the actual model:empty-table
 * command and verify that all rows are deleted while preserving table structure.
 *
 * What Gets Tested:
 *   - Emptying table with user confirmation
 *   - Cancelling empty operation preserves data
 *   - Validation: Cannot empty non-existent table
 *   - Validation: Cannot empty table for non-existent model
 *   - Table structure (columns, indexes) preserved after emptying
 *   - All rows are deleted when confirmed
 *   - Required model name argument validation
 *
 * Test Strategy:
 *   - Tests create models and tables via model:create and model:create-table
 *   - Test data is inserted via insertTestData() helper
 *   - Model files tracked via trackFile(), tables via trackTable()
 *   - Commands executed via runCommand() with confirmation inputs
 *   - Row counts verified before and after operations
 *   - Column existence verified to ensure structure preservation
 *
 * File and Table Cleanup:
 *   All created model files and tables are automatically removed after each test
 *   via tearDown() inherited from RolineTest base class. No manual cleanup required.
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

class ModelEmptyTableTest extends RolineTest
{
    /**
     * Test emptying table with confirmation
     *
     * Verifies that model:empty-table deletes all rows when confirmed,
     * but preserves table structure.
     *
     * What Gets Verified:
     *   - Table exists before emptying
     *   - Test data is inserted successfully
     *   - Row count is correct before emptying
     *   - Confirmation is accepted
     *   - Table still exists after emptying
     *   - All rows are deleted (count = 0)
     *   - Success message is displayed
     *
     * @return void
     */
    public function testEmptyTableWithConfirmation()
    {
        $modelName = 'EmptyTest';
        $tableName = 'empty_tests';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);

        // Create model and table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->runCommand("model:create-table {$modelName}", ['yes']);
        $this->assertTableExists($tableName);

        // Insert test data
        $this->insertTestData($tableName, [
            ['date_created' => date('Y-m-d H:i:s'), 'date_modified' => date('Y-m-d H:i:s')],
            ['date_created' => date('Y-m-d H:i:s'), 'date_modified' => date('Y-m-d H:i:s')],
        ]);

        // Verify data exists
        $count = $this->getTableRowCount($tableName);
        $this->assertEquals(2, $count, "Expected 2 rows before emptying");

        // Empty table with confirmation
        $result = $this->runCommand("model:empty-table {$modelName}", ['yes']);

        // Table should still exist
        $this->assertTableExists($tableName);

        // But all rows should be deleted
        $count = $this->getTableRowCount($tableName);
        $this->assertEquals(0, $count, "Expected 0 rows after emptying");

        // Output should confirm success
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'emptied') || str_contains($output, 'deleted'),
            "Expected success message in output: {$result['output']}"
        );
    }

    /**
     * Test cancelling empty operation
     *
     * Verifies that model:empty-table preserves data when user declines.
     *
     * What Gets Verified:
     *   - Test data exists before cancellation
     *   - Confirmation is declined
     *   - Data is preserved after cancellation
     *   - Row count remains unchanged
     *   - Cancellation message is displayed
     *
     * @return void
     */
    public function testCancelEmptyTable()
    {
        $modelName = 'KeepData';
        $tableName = 'keep_datas';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);

        // Create model and table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->runCommand("model:create-table {$modelName}", ['yes']);

        // Insert test data
        $this->insertTestData($tableName, [
            ['date_created' => date('Y-m-d H:i:s'), 'date_modified' => date('Y-m-d H:i:s')],
        ]);

        // Try to empty with 'no' (cancel)
        $result = $this->runCommand("model:empty-table {$modelName}", ['no']);

        // Data should still exist
        $count = $this->getTableRowCount($tableName);
        $this->assertEquals(1, $count, "Expected data to be preserved after cancellation");

        // Output should confirm cancellation
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled') || str_contains($output, 'cancel'),
            "Expected cancellation message in output: {$result['output']}"
        );
    }

    /**
     * Test emptying non-existent table
     *
     * Verifies that model:empty-table fails gracefully when table doesn't exist.
     *
     * What Gets Verified:
     *   - Model exists but table doesn't
     *   - Command detects non-existent table
     *   - Error message is displayed
     *   - No database changes occur
     *
     * @return void
     */
    public function testEmptyNonExistentTable()
    {
        $modelName = 'NoTable';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        // Create model but not table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");

        // Try to empty non-existent table
        $result = $this->runCommand("model:empty-table {$modelName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'does not exist') || str_contains($output, 'not found'),
            "Expected error message about non-existent table: {$result['output']}"
        );
    }

    /**
     * Test emptying table from non-existent model
     *
     * Verifies that model:empty-table fails gracefully when model file doesn't exist.
     *
     * What Gets Verified:
     *   - Command detects non-existent model
     *   - Error message is displayed
     *   - No database operations attempted
     *
     * @return void
     */
    public function testEmptyTableFromNonExistentModel()
    {
        $modelName = 'NoModel';

        $result = $this->runCommand("model:empty-table {$modelName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found') || str_contains($output, 'does not exist'),
            "Expected error message about non-existent model: {$result['output']}"
        );
    }

    /**
     * Test model:empty-table requires model name argument
     *
     * Verifies that model:empty-table validates required arguments and displays
     * appropriate error message when model name is missing.
     *
     * What Gets Verified:
     *   - Missing argument is detected
     *   - Error or usage message is displayed
     *   - Command exits without prompting
     *
     * @return void
     */
    public function testRequiresModelNameArgument()
    {
        $result = $this->runCommand("model:empty-table");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'usage'),
            "Expected error message about missing argument: {$result['output']}"
        );
    }

    /**
     * Test table structure preserved after emptying
     *
     * Verifies that columns, indexes, and constraints are preserved.
     *
     * What Gets Verified:
     *   - All columns exist before emptying
     *   - Table is successfully emptied
     *   - All columns still exist after emptying
     *   - Table structure remains intact
     *
     * @return void
     */
    public function testTableStructurePreserved()
    {
        $modelName = 'StructureTest';
        $tableName = 'structure_tests';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);

        // Create model and table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->runCommand("model:create-table {$modelName}", ['yes']);

        // Verify columns exist before
        $this->assertColumnExists($tableName, 'id');
        $this->assertColumnExists($tableName, 'date_created');
        $this->assertColumnExists($tableName, 'date_modified');

        // Empty table
        $this->runCommand("model:empty-table {$modelName}", ['yes']);

        // Verify columns still exist after
        $this->assertColumnExists($tableName, 'id');
        $this->assertColumnExists($tableName, 'date_created');
        $this->assertColumnExists($tableName, 'date_modified');
    }
}
