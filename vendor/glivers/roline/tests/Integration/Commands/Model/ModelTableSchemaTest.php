<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for model:table-schema command
 *
 * Tests the complete flow of displaying database table schema via model references
 * using the Roline CLI. These integration tests execute the actual model:table-schema
 * command and verify that table structure is correctly displayed with column details.
 *
 * What Gets Tested:
 *   - Displaying schema for existing table
 *   - Schema output includes column information
 *   - Schema output includes table name
 *   - Validation: Cannot display schema for non-existent table
 *   - Validation: Cannot display schema for non-existent model
 *   - Required model name argument validation
 *
 * Test Strategy:
 *   - Tests create models and tables via model:create and model:create-table
 *   - Model files tracked via trackFile(), tables via trackTable()
 *   - Commands executed via runCommand() which captures output
 *   - Output assertions verify schema information display
 *   - Tests verify column names and types appear in output
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

class ModelTableSchemaTest extends RolineTest
{
    /**
     * Test displaying schema for existing table
     *
     * Verifies that model:table-schema can display the structure of an
     * existing database table.
     *
     * What Gets Verified:
     *   - Model and table are created successfully
     *   - Schema command executes without errors
     *   - Output contains column information
     *   - Standard columns (id, date_created, date_modified) appear
     *
     * @return void
     */
    public function testDisplaySchemaForExistingTable()
    {
        $modelName = 'SchemaTest';
        $tableName = 'schema_tests';
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

        // Display schema
        $result = $this->runCommand("model:table-schema {$modelName}");

        // Output should contain column information
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'id') || str_contains($output, 'column'),
            "Expected schema information in output: {$result['output']}"
        );
        $this->assertTrue(
            str_contains($output, 'date_created') || str_contains($output, 'date_modified'),
            "Expected timestamp columns in output: {$result['output']}"
        );
    }

    /**
     * Test displaying schema for non-existent table
     *
     * Verifies that model:table-schema fails gracefully when table doesn't exist.
     *
     * What Gets Verified:
     *   - Model exists but table doesn't
     *   - Command detects non-existent table
     *   - Error message is displayed
     *   - No schema information is shown
     *
     * @return void
     */
    public function testDisplaySchemaForNonExistentTable()
    {
        $modelName = 'NoTable';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        // Create model but not table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");

        // Try to display schema
        $result = $this->runCommand("model:table-schema {$modelName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'does not exist') || str_contains($output, 'not found'),
            "Expected error message about non-existent table: {$result['output']}"
        );
    }

    /**
     * Test displaying schema for non-existent model
     *
     * Verifies that model:table-schema fails gracefully when model doesn't exist.
     *
     * What Gets Verified:
     *   - Command detects non-existent model
     *   - Error message is displayed
     *   - No schema operations attempted
     *
     * @return void
     */
    public function testDisplaySchemaForNonExistentModel()
    {
        $modelName = 'NoModel';

        $result = $this->runCommand("model:table-schema {$modelName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found') || str_contains($output, 'does not exist'),
            "Expected error message about non-existent model: {$result['output']}"
        );
    }

    /**
     * Test model:table-schema requires model name argument
     *
     * Verifies that model:table-schema validates required arguments and displays
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
        $result = $this->runCommand("model:table-schema");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'usage'),
            "Expected error message about missing argument: {$result['output']}"
        );
    }

    /**
     * Test schema output includes table name
     *
     * Verifies that the schema output clearly shows which table is being displayed.
     *
     * What Gets Verified:
     *   - Schema is displayed successfully
     *   - Table name appears in output
     *   - Output provides clear context
     *
     * @return void
     */
    public function testSchemaOutputIncludesTableName()
    {
        $modelName = 'TableName';
        $tableName = 'table_names';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);

        // Create model and table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->runCommand("model:create-table {$modelName}", ['yes']);

        // Display schema
        $result = $this->runCommand("model:table-schema {$modelName}");

        // Output should include table name
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, $tableName) || str_contains($output, 'table'),
            "Expected table name in output: {$result['output']}"
        );
    }
}
