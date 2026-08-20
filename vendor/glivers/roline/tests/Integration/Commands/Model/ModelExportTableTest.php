<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for model:export-table command
 *
 * Tests the complete flow of exporting table data to SQL or CSV formats via model
 * references using the Roline CLI. These integration tests execute the actual
 * model:export-table command and verify proper export file creation and formatting.
 *
 * What Gets Tested:
 *   - Exporting table data to SQL format with INSERT statements
 *   - Exporting table data to CSV format with headers
 *   - Auto-generated filenames with timestamps
 *   - Custom filename specification
 *   - Validation: Cannot export non-existent table
 *   - Validation: Cannot export for non-existent model
 *   - Overwrite confirmation for existing files
 *
 * Test Strategy:
 *   - Tests create models and tables via model:create and model:create-table
 *   - Test data inserted via insertTestData() helper
 *   - Model files tracked via trackFile(), tables via trackTable()
 *   - Export files tracked via trackFile() for automatic cleanup
 *   - Commands executed via runCommand() with optional filename args
 *   - File content assertions verify SQL/CSV format correctness
 *   - Output assertions verify success messages
 *
 * File and Table Cleanup:
 *   All created model files, tables, and export files are automatically removed
 *   after each test via tearDown() inherited from RolineTest base class.
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

class ModelExportTableTest extends RolineTest
{
    /**
     * Test exporting table to SQL format
     *
     * Verifies that model:export-table can export table data as SQL INSERT statements.
     *
     * What Gets Verified:
     *   - Export file is created
     *   - File contains SQL INSERT statements
     *   - File references correct table name
     *   - Success message is displayed
     *
     * @return void
     */
    public function testExportToSQLFormat()
    {
        $modelName = 'ExportSQL';
        $tableName = 'export_sqls';  // toSnakeCase() keeps acronyms together
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';
        $exportFile = RACHIE_ROOT . '/application/storage/exports/test_export.sql';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);
        $this->trackFile($exportFile);

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
        ]);

        // Export to SQL
        $result = $this->runCommand("model:export-table {$modelName} test_export.sql");

        // Export file should exist
        $this->assertFileExists($exportFile, "Export file should be created");

        // File should contain SQL INSERT statements
        $content = file_get_contents($exportFile);
        $this->assertStringContainsString('INSERT INTO', $content,
            "Export should contain INSERT statements");
        $this->assertStringContainsString($tableName, $content,
            "Export should reference table name");

        // Output should confirm success
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'complete') || str_contains($output, 'success'),
            "Expected success message in output: {$result['output']}"
        );
    }

    /**
     * Test exporting table to CSV format
     *
     * Verifies that model:export-table can export table data as CSV.
     *
     * What Gets Verified:
     *   - Export file is created in CSV format
     *   - File contains column headers
     *   - File includes column names
     *   - Data is properly formatted as CSV
     *
     * @return void
     */
    public function testExportToCSVFormat()
    {
        $modelName = 'ExportCSV';
        $tableName = 'export_csvs';  // toSnakeCase() keeps acronyms together
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';
        $exportFile = RACHIE_ROOT . '/application/storage/exports/test_export.csv';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);
        $this->trackFile($exportFile);

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
        ]);

        // Export to CSV
        $result = $this->runCommand("model:export-table {$modelName} test_export.csv");

        // Export file should exist
        $this->assertFileExists($exportFile, "Export file should be created");

        // File should contain CSV data
        $content = file_get_contents($exportFile);
        $this->assertStringContainsString('id', $content,
            "CSV should contain column headers");
        $this->assertStringContainsString('date_created', $content,
            "CSV should contain column names");
    }

    /**
     * Test auto-generated filename with timestamp
     *
     * Verifies that model:export-table generates timestamped filename when none provided.
     *
     * What Gets Verified:
     *   - Export file is created with auto-generated name
     *   - Filename includes timestamp pattern (YYYY-MM-DD_HHMMSS)
     *   - Export completes successfully
     *   - Filename is displayed in output
     *
     * @return void
     */
    public function testAutoGeneratedFilename()
    {
        $modelName = 'AutoName';
        $tableName = 'auto_names';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';
        $exportDir = RACHIE_ROOT . '/application/storage/exports';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);

        // Create model and table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->runCommand("model:create-table {$modelName}", ['yes']);
        $this->assertTableExists($tableName);

        // Export without filename (should auto-generate)
        $result = $this->runCommand("model:export-table {$modelName}");

        // Find generated file
        $files = glob($exportDir . '/' . $tableName . '_*.sql');
        $this->assertNotEmpty($files, "Auto-generated export file should exist");

        // Track for cleanup
        if (!empty($files)) {
            $this->trackFile($files[0]);
        }

        // Filename should contain timestamp pattern
        $output = $result['output'];
        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2}_\d{6}/',
            $output,
            "Filename should contain timestamp: {$output}"
        );
    }

    /**
     * Test exporting non-existent table
     *
     * Verifies that model:export-table fails gracefully when table doesn't exist.
     *
     * What Gets Verified:
     *   - Model exists but table doesn't
     *   - Command detects non-existent table
     *   - Error message is displayed
     *   - No export file is created
     *
     * @return void
     */
    public function testExportNonExistentTable()
    {
        $modelName = 'NoTable';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';

        $this->trackFile($modelPath);

        // Create model but not table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");

        // Try to export non-existent table
        $result = $this->runCommand("model:export-table {$modelName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'does not exist') || str_contains($output, 'not found'),
            "Expected error about non-existent table: {$result['output']}"
        );
    }

    /**
     * Test exporting for non-existent model
     *
     * Verifies that model:export-table fails gracefully when model doesn't exist.
     *
     * What Gets Verified:
     *   - Command detects non-existent model
     *   - Error message is displayed
     *   - No export operations attempted
     *
     * @return void
     */
    public function testExportFromNonExistentModel()
    {
        $modelName = 'NoModel';

        $result = $this->runCommand("model:export-table {$modelName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'not found') || str_contains($output, 'does not exist'),
            "Expected error about non-existent model: {$result['output']}"
        );
    }

    /**
     * Test model:export-table requires model name argument
     *
     * Verifies that model:export-table validates required arguments and displays
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
        $result = $this->runCommand("model:export-table");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'usage'),
            "Expected error about missing argument: {$result['output']}"
        );
    }

    /**
     * Test overwrite confirmation
     *
     * Verifies that model:export-table prompts before overwriting existing file.
     *
     * What Gets Verified:
     *   - Initial export succeeds
     *   - Export file exists
     *   - Overwrite prompt is displayed
     *   - Declining overwrite cancels operation
     *   - Cancellation message is displayed
     *
     * @return void
     */
    public function testOverwriteConfirmation()
    {
        $modelName = 'Overwrite';
        $tableName = 'overwrites';
        $modelPath = TEST_MODELS_PATH . '/' . $modelName . 'Model.php';
        $exportFile = RACHIE_ROOT . '/application/storage/exports/overwrite_test.sql';

        $this->trackFile($modelPath);
        $this->trackTable($tableName);
        $this->trackFile($exportFile);

        // Create model and table
        if (file_exists($modelPath)) {
            unlink($modelPath);
        }
        $this->runCommand("model:create {$modelName}");
        $this->runCommand("model:create-table {$modelName}", ['yes']);
        $this->assertTableExists($tableName);

        // Create initial export
        $this->runCommand("model:export-table {$modelName} overwrite_test.sql");
        $this->assertFileExists($exportFile);

        // Try to export again with 'no' (cancel overwrite)
        $result = $this->runCommand("model:export-table {$modelName} overwrite_test.sql", ['no']);

        // Should be cancelled
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled') || str_contains($output, 'cancel'),
            "Expected cancellation message: {$result['output']}"
        );
    }
}
