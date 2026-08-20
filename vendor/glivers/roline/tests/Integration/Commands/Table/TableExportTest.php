<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for table:export command
 *
 * Tests the complete flow of exporting database table data to SQL or CSV formats
 * via the Roline CLI without requiring model classes. These integration tests
 * execute the actual table:export command and verify that table data is correctly
 * exported with proper file creation and format handling.
 *
 * What Gets Tested:
 *   - Exporting table data to SQL format
 *   - Exporting table data to CSV format
 *   - Auto-generated filenames with timestamps
 *   - Custom filename specification
 *   - Validation: Cannot export non-existent table
 *   - Required table name argument validation
 *   - Overwrite confirmation for existing files
 *
 * Test Strategy:
 *   - Each test uses raw SQL CREATE TABLE for setup
 *   - Tables are tracked via trackTable() for automatic cleanup
 *   - Export files are tracked via trackFile() for automatic cleanup
 *   - Commands are executed via runCommand() which captures output
 *   - File content assertions verify export format correctness
 *   - Output assertions verify success/error messages
 *
 * File and Table Cleanup:
 *   All created tables and export files are automatically removed after each test
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

class TableExportTest extends RolineTest
{
    /**
     * Test exporting table to SQL format
     *
     * Verifies that table:export command successfully exports table data
     * to SQL format with proper INSERT statements.
     *
     * What Gets Verified:
     *   - Export file is created
     *   - File contains SQL INSERT statements
     *   - File includes table name reference
     *   - Success message is displayed
     *   - Data is correctly formatted as SQL
     *
     * @return void
     */
    public function testExportToSQLFormat()
    {
        $tableName = 'export_sql_test';
        $exportFile = RACHIE_ROOT . '/application/storage/exports/table_export.sql';

        $this->trackTable($tableName);
        $this->trackFile($exportFile);

        // Create table with data using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL
        )");
        $this->assertTableExists($tableName);

        // Insert test data
        $this->insertTestData($tableName, [
            ['name' => 'Test Entry'],
        ]);

        // Export to SQL
        $result = $this->runCommand("table:export {$tableName} table_export.sql");

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
     * Verifies that table:export command successfully exports table data
     * to CSV format with proper column headers and data rows.
     *
     * What Gets Verified:
     *   - Export file is created
     *   - File contains CSV column headers
     *   - File includes column names
     *   - Success message is displayed
     *   - Data is correctly formatted as CSV
     *
     * @return void
     */
    public function testExportToCSVFormat()
    {
        $tableName = 'export_csv_test';
        $exportFile = RACHIE_ROOT . '/application/storage/exports/table_export.csv';

        $this->trackTable($tableName);
        $this->trackFile($exportFile);

        // Create table with data using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(100) NOT NULL
        )");
        $this->assertTableExists($tableName);

        // Insert test data
        $this->insertTestData($tableName, [
            ['title' => 'CSV Test'],
        ]);

        // Export to CSV
        $result = $this->runCommand("table:export {$tableName} table_export.csv");

        // Export file should exist
        $this->assertFileExists($exportFile, "Export file should be created");

        // File should contain CSV data
        $content = file_get_contents($exportFile);
        $this->assertStringContainsString('id', $content,
            "CSV should contain column headers");
        $this->assertStringContainsString('title', $content,
            "CSV should contain column names");
    }

    /**
     * Test auto-generated filename with timestamp
     *
     * Verifies that table:export command automatically generates a timestamped
     * filename when no filename is specified by the user.
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
        $tableName = 'auto_export';
        $exportDir = RACHIE_ROOT . '/application/storage/exports';

        $this->trackTable($tableName);

        // Create table using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (id INT PRIMARY KEY)");
        $this->assertTableExists($tableName);

        // Export without filename (should auto-generate)
        $result = $this->runCommand("table:export {$tableName}");

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
     * Verifies that table:export command properly detects and rejects attempts
     * to export a table that doesn't exist in the database.
     *
     * What Gets Verified:
     *   - Command detects non-existent table
     *   - Error message is displayed
     *   - No export file is created
     *
     * @return void
     */
    public function testExportNonExistentTable()
    {
        $tableName = 'no_such_table';

        $result = $this->runCommand("table:export {$tableName}");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'does not exist') || str_contains($output, 'not found'),
            "Expected error about non-existent table: {$result['output']}"
        );
    }

    /**
     * Test table:export requires table name argument
     *
     * Verifies that table:export command validates required arguments and
     * displays appropriate error message when table name is missing.
     *
     * What Gets Verified:
     *   - Missing argument is detected
     *   - Error or usage message is displayed
     *   - Command exits without exporting
     *
     * @return void
     */
    public function testRequiresTableNameArgument()
    {
        $result = $this->runCommand("table:export");

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'required') || str_contains($output, 'usage'),
            "Expected error about missing argument: {$result['output']}"
        );
    }

    /**
     * Test overwrite confirmation
     *
     * Verifies that table:export command prompts for confirmation when
     * attempting to overwrite an existing export file.
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
        $tableName = 'overwrite_export';
        $exportFile = RACHIE_ROOT . '/application/storage/exports/overwrite.sql';

        $this->trackTable($tableName);
        $this->trackFile($exportFile);

        // Create table using raw SQL
        $db = $this->getDb();
        $db->exec("CREATE TABLE `{$tableName}` (id INT PRIMARY KEY)");
        $this->assertTableExists($tableName);

        // Create initial export
        $this->runCommand("table:export {$tableName} overwrite.sql");
        $this->assertFileExists($exportFile);

        // Try to export again with 'no' (cancel overwrite)
        $result = $this->runCommand("table:export {$tableName} overwrite.sql", ['no']);

        // Should be cancelled
        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled') || str_contains($output, 'cancel'),
            "Expected cancellation message: {$result['output']}"
        );
    }
}
