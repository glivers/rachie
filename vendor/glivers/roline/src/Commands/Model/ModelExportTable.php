<?php namespace Roline\Commands\Model;

/**
 * ModelExportTable Command
 *
 * Exports complete table data to SQL or CSV format. Queries all rows from the database
 * table associated with the model and generates formatted output files suitable for
 * backups, data migration, or analysis in external tools (Excel, database tools, etc.).
 *
 * Supported Export Formats:
 *   - SQL Format (.sql):
 *     * Complete INSERT statements for each row
 *     * Includes column names and properly escaped values
 *     * NULL handling with SQL NULL keyword
 *     * File header with table name, timestamp, row count
 *     * Ready for direct import into MySQL
 *
 *   - CSV Format (.csv):
 *     * Comma-separated values
 *     * Column headers in first row
 *     * Standard CSV escaping for quotes and commas
 *     * Compatible with Excel, Google Sheets, etc.
 *     * Smaller file size than SQL format
 *
 * File Generation:
 *   - Auto-generates filename with timestamp if not specified
 *   - Format: {tablename}_{YYYY-MM-DD_HHmmss}.sql
 *   - Saves to application/storage/exports/ directory
 *   - Creates exports directory if doesn't exist
 *   - Checks for existing files and prompts before overwrite
 *
 * Use Cases:
 *   - Database backups before destructive operations
 *   - Data migration between environments
 *   - Exporting data for analysis in Excel/spreadsheets
 *   - Sharing sample data with team members
 *   - Creating test fixtures from production data
 *
 * Typical Workflow:
 *   1. Run command with model name and optional filename
 *   2. Command validates model and table exist
 *   3. Determines export format from file extension
 *   4. Creates exports directory if needed
 *   5. Queries all table data
 *   6. Generates formatted output
 *   7. Writes to file in exports directory
 *
 * Examples:
 *   php roline model:export-table User
 *   - Exports to users_2025-01-15_143022.sql
 *
 *   php roline model:export-table Post posts_backup.sql
 *   - Exports as SQL to posts_backup.sql
 *
 *   php roline model:export-table Product products.csv
 *   - Exports as CSV to products.csv
 *
 * Usage:
 *   php roline model:export-table <Model> [filename]
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Model
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\Model;
use Roline\Output;
use Roline\Schema\MySQLSchema;
use Roline\Utils\SchemaReader;

class ModelExportTable extends ModelCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Export table data to SQL or CSV';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing required model and optional filename
     */
    public function usage()
    {
        return '<Model|required> <file|optional>';
    }

    /**
     * Display detailed help information
     *
     * Shows arguments, export format options (SQL vs CSV), auto-generated filenames,
     * output location, and usage examples for different scenarios.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <Model|required>  Model class name (without "Model" suffix)');
        Output::line('  <file|optional>   Output filename (auto-generates if not provided)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline model:export-table User');
        Output::line('  php roline model:export-table Post posts_backup.sql');
        Output::line('  php roline model:export-table Product products.csv');
        Output::line();

        Output::info('Output Formats:');
        Output::line('  .sql  - SQL INSERT statements');
        Output::line('  .csv  - Comma-separated values');
        Output::line('  Auto-detects format from file extension (defaults to .sql)');
        Output::line();

        Output::info('Output Location:');
        Output::line('  Files are saved to: application/storage/exports/');
        Output::line();
    }

    /**
     * Execute table export to SQL or CSV
     *
     * Exports all table data to specified or auto-generated filename. Validates model
     * and table exist, determines format from file extension, creates exports directory
     * if needed, queries all rows, and writes formatted output. Prompts before overwriting
     * existing files.
     *
     * @param array $arguments Command arguments (model at index 0, filename at index 1 optional)
     * @return void Exits with status 0 on cancel, 1 on failure
     */
    public function execute($arguments)
    {
        // Validate model name argument is provided and normalize (ucfirst, remove 'Model' suffix)
        if (empty($arguments[0])) {
            $this->error('Model name is required!');
            $this->line();
            $this->info('Usage: php roline model:export-table <Model> [file]');
            $this->line();
            $this->info('Example: php roline model:export-table User');
            exit(1);
        }

        // Normalize model name (ucfirst + remove 'Model' suffix)
        $modelName = $this->validateName($arguments[0]);
        $modelClass = "Models\\{$modelName}Model";

        // Validate model class exists
        if (!class_exists($modelClass)) {
            $this->error("Model class not found: {$modelClass}");
            $this->line();
            $this->info('Create the model first: php roline model:create ' . $modelName);
            exit(1);
        }

        // Extract table name from model's protected static $table property
        try {
            // Use reflection to access protected static property
            $reflection = new \ReflectionClass($modelClass);
            $tableProperty = $reflection->getProperty('table');
            $tableProperty->setAccessible(true);
            $tableName = $tableProperty->getValue();

            // Validate table name is defined
            if (empty($tableName)) {
                $this->error('Model does not have a table name defined!');
                exit(1);
            }
        } catch (\Exception $e) {
            // Reflection failed
            $this->error('Error reading model: ' . $e->getMessage());
            exit(1);
        }

        // Validate table exists in database
        $schema = new MySQLSchema();
        if (!$schema->tableExists($tableName)) {
            $this->error("Table '{$tableName}' does not exist!");
            $this->line();
            $this->info("Create it first: php roline model:create-table {$modelName}");
            exit(1);
        }

        // Determine output filename (auto-generate if not provided)
        $filename = $arguments[1] ?? null;

        if ($filename === null) {
            // Generate filename with timestamp: tablename_YYYY-MM-DD_HHmmss.sql
            $timestamp = date('Y-m-d_His');
            $filename = "{$tableName}_{$timestamp}.sql";
        }

        // Detect export format from file extension (.csv or .sql)
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $format = strtolower($extension) === 'csv' ? 'csv' : 'sql';

        // Ensure exports directory exists
        $exportsDir = getcwd() . '/application/storage/exports';
        if (!is_dir($exportsDir)) {
            mkdir($exportsDir, 0755, true);
        }

        // Build full output file path
        $filepath = $exportsDir . '/' . $filename;

        // Check if file already exists and prompt for overwrite
        if (file_exists($filepath)) {
            $overwrite = $this->confirm("File '{$filename}' already exists. Overwrite?");
            if (!$overwrite) {
                // User declined overwrite
                $this->info("Export cancelled.");
                exit(0);
            }
        }

        // Check for --data-only flag
        $dataOnly = in_array('--data-only', $arguments);

        // Execute export based on detected format
        try {
            $this->line();
            $this->info("Exporting table '{$tableName}'...");
            if ($dataOnly) {
                $this->info("Mode: Data only (no schema)");
            }
            $this->line();

            // Delegate to format-specific export method
            if ($format === 'csv') {
                $this->exportToCSV($tableName, $filepath);
            } else {
                $this->exportToSQL($tableName, $filepath, $dataOnly);
            }

            // Export successful - display summary
            $this->line();
            $this->success("Export complete!");
            $this->line();
            $this->info("Format: " . strtoupper($format));
            $this->info("Location: {$filepath}");
            $this->line();

        } catch (\Exception $e) {
            // Export failed (query error, file write error, etc.)
            $this->line();
            $this->error("Export failed!");
            $this->line();
            $this->error("Error: " . $e->getMessage());
            $this->line();
            exit(1);
        }
    }

    /**
     * Export table to SQL format with streaming and batching
     *
     * @param string $tableName Table name to export
     * @param string $filepath Output file path
     * @param bool $dataOnly If true, skip CREATE TABLE (data only)
     * @return void
     * @throws \Exception If query or file write fails
     */
    private function exportToSQL($tableName, $filepath, $dataOnly = false)
    {
        // Batch size: rows per INSERT (same as db:export)
        $batchSize = 1000;
        $progressInterval = 10000;

        // Use unbuffered query for memory-efficient export
        $sql = "SELECT * FROM `{$tableName}`";
        $result = Model::stream()->sql($sql);

        if (!$result) {
            file_put_contents($filepath, "-- No data in table '{$tableName}'\n");
            return;
        }

        $columns = [];
        $fields = $result->fetch_fields();
        foreach ($fields as $field) {
            $columns[] = $field->name;
        }

        // Open file handle for streaming (avoids memory issues)
        $fileHandle = fopen($filepath, 'w');
        if (!$fileHandle) {
            throw new \Exception("Failed to open file for writing");
        }

        // Write header (row count shown at end after counting)
        fwrite($fileHandle, "-- Table export: {$tableName}\n");
        fwrite($fileHandle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");

        // Disable foreign key checks for single-table import
        fwrite($fileHandle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        // Include CREATE TABLE unless --data-only flag is set
        if (!$dataOnly) {
            fwrite($fileHandle, "-- Table structure for {$tableName}\n\n");
            fwrite($fileHandle, "DROP TABLE IF EXISTS `{$tableName}`;\n\n");

            // Get table schema and generate CREATE TABLE
            $schemaReader = new SchemaReader();
            $schema = $schemaReader->getTableSchema($tableName);
            $createTableSQL = $this->generateCreateTableSQL($tableName, $schema);
            fwrite($fileHandle, $createTableSQL);
            fwrite($fileHandle, "\n\n");
            fwrite($fileHandle, "-- Data for table {$tableName}\n\n");
        }

        // Pre-build column list (same for all batches)
        $columnList = '`' . implode('`, `', $columns) . '`';

        // Batch processing
        $batch = [];
        $rowCount = 0;
        $lastProgressUpdate = 0;

        // Show initial progress
        echo "  → Exporting {$tableName}...";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();

        while ($row = $result->fetch_assoc()) {
            $values = [];

            foreach ($columns as $column) {
                $value = $row[$column];
                // Model::quote() handles NULL, escaping, and quoting
                $values[] = Model::quote($value);
            }

            // Add row to batch
            $batch[] = '(' . implode(', ', $values) . ')';
            $rowCount++;

            // Write batch when full
            if ($rowCount % $batchSize === 0) {
                $sql = "INSERT INTO `{$tableName}` ({$columnList}) VALUES\n";
                $sql .= implode(",\n", $batch);
                $sql .= ";\n\n";

                fwrite($fileHandle, $sql);
                $batch = [];
            }

            // Show progress every progressInterval rows
            if ($rowCount % $progressInterval === 0 && $rowCount !== $lastProgressUpdate) {
                echo "\r\033[K  → Exporting {$tableName}... \033[37m(" . number_format($rowCount) . " rows)\033[0m";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                $lastProgressUpdate = $rowCount;
            }
        }

        // Write remaining rows
        if (!empty($batch)) {
            $sql = "INSERT INTO `{$tableName}` ({$columnList}) VALUES\n";
            $sql .= implode(",\n", $batch);
            $sql .= ";\n\n";

            fwrite($fileHandle, $sql);
        }

        // Re-enable foreign key checks
        fwrite($fileHandle, "\nSET FOREIGN_KEY_CHECKS=1;\n");

        fclose($fileHandle);

        // Final progress
        echo "\r\033[K  → Exporting {$tableName}... \033[37m(" . number_format($rowCount) . " rows)\033[0m\n";
    }

    /**
     * Export table to CSV format
     *
     * Generates CSV file with column headers in first row and data in subsequent rows.
     * Uses PHP's native fputcsv() for proper CSV escaping of quotes and commas.
     * Compatible with Excel, Google Sheets, and other spreadsheet tools.
     *
     * @param string $tableName Table name to export
     * @param string $filepath Output file path
     * @return void
     * @throws \Exception If query or file write fails
     */
    private function exportToCSV($tableName, $filepath)
    {
        // Use unbuffered query for memory-efficient export
        $sql = "SELECT * FROM `{$tableName}`";
        $result = Model::stream()->sql($sql);

        // Validate query succeeded
        if (!$result) {
            throw new \Exception("Failed to query table");
        }

        // Open file for writing
        $fp = fopen($filepath, 'w');
        if (!$fp) {
            throw new \Exception("Failed to open file for writing");
        }

        // Extract column names from result metadata
        $columns = [];
        $fields = $result->fetch_fields();
        foreach ($fields as $field) {
            $columns[] = $field->name;
        }

        // Write column headers as first row
        fputcsv($fp, $columns);

        // Write each data row (fputcsv handles escaping)
        while ($row = $result->fetch_assoc()) {
            fputcsv($fp, array_values($row));
        }

        // Close file handle
        fclose($fp);
    }

    /**
     * Generate CREATE TABLE SQL statement from schema definition
     *
     * @param string $tableName Table name
     * @param array $schema Schema definition from SchemaReader
     * @return string CREATE TABLE SQL statement
     */
    private function generateCreateTableSQL($tableName, $schema)
    {
        $sql = "CREATE TABLE `{$tableName}` (\n";

        $columnDefinitions = [];

        // Generate column definitions
        foreach ($schema['columns'] as $columnName => $columnDef) {
            $def = "  `{$columnName}` {$columnDef['type']}";

            // Add NULL/NOT NULL
            if ($columnDef['nullable']) {
                $def .= " NULL";
            } else {
                $def .= " NOT NULL";
            }

            // Add DEFAULT value if set
            if ($columnDef['default'] !== null) {
                $default = $columnDef['default'];

                // Skip DEFAULT NULL if column is NOT NULL (contradictory)
                if (!$columnDef['nullable'] && strtoupper($default) === 'NULL') {
                    // Skip - can't have NOT NULL DEFAULT NULL
                }
                // Special keywords (CURRENT_TIMESTAMP, NULL) don't get quoted
                elseif (strtoupper($default) === 'CURRENT_TIMESTAMP' || strtoupper($default) === 'NULL') {
                    $def .= " DEFAULT {$default}";
                }
                // Check if already quoted (ENUM/SET types return quoted values from MySQL)
                elseif (strlen($default) >= 2 && $default[0] === "'" && substr($default, -1) === "'") {
                    // Already quoted - use as-is
                    $def .= " DEFAULT {$default}";
                } else {
                    // Regular defaults need quoting
                    $def .= " DEFAULT '{$default}'";
                }
            }

            // Add extra attributes (auto_increment, on update, etc.)
            if (!empty($columnDef['extra'])) {
                $extra = trim($columnDef['extra']);

                // Filter out NULL values that shouldn't be in extra field
                if (strtoupper($extra) !== 'NULL' && strtoupper($extra) !== 'NOT NULL') {
                    $def .= " {$extra}";
                }
            }

            // Add column comment if exists
            if (!empty($columnDef['comment'])) {
                $def .= " COMMENT '" . addslashes($columnDef['comment']) . "'";
            }

            $columnDefinitions[] = $def;
        }

        $sql .= implode(",\n", $columnDefinitions);

        // Add primary key
        if (!empty($schema['primary_key'])) {
            $primaryKeyColumns = is_array($schema['primary_key'])
                ? $schema['primary_key']
                : [$schema['primary_key']];
            $pkColumns = '`' . implode('`, `', $primaryKeyColumns) . '`';
            $sql .= ",\n  PRIMARY KEY ({$pkColumns})";
        }

        // Add indexes
        if (!empty($schema['indexes'])) {
            foreach ($schema['indexes'] as $indexName => $indexDef) {
                // Skip primary key (already added)
                if ($indexName === 'PRIMARY') {
                    continue;
                }

                $indexColumns = '`' . implode('`, `', $indexDef['columns']) . '`';
                $indexType = strtoupper($indexDef['type'] ?? 'BTREE');

                // Add appropriate index type
                if ($indexType === 'FULLTEXT') {
                    $sql .= ",\n  FULLTEXT KEY `{$indexName}` ({$indexColumns})";
                }
                elseif ($indexDef['unique'] ?? false) {
                    $sql .= ",\n  UNIQUE KEY `{$indexName}` ({$indexColumns})";
                }
                else {
                    $sql .= ",\n  KEY `{$indexName}` ({$indexColumns})";
                }
            }
        }

        // Add foreign keys
        if (!empty($schema['foreign_keys'])) {
            foreach ($schema['foreign_keys'] as $fkName => $fkDef) {
                // SchemaReader returns 'column' (singular string), not 'columns' (array)
                $fkColumn = $fkDef['column'];
                $refColumn = $fkDef['referenced_column'];

                $sql .= ",\n  CONSTRAINT `{$fkName}` FOREIGN KEY (`{$fkColumn}`) " .
                        "REFERENCES `{$fkDef['referenced_table']}` (`{$refColumn}`)";

                if (!empty($fkDef['on_delete'])) {
                    $sql .= " ON DELETE {$fkDef['on_delete']}";
                }

                if (!empty($fkDef['on_update'])) {
                    $sql .= " ON UPDATE {$fkDef['on_update']}";
                }
            }
        }

        // Add CHECK constraints if exist (MySQL 8.0.16+)
        if (!empty($schema['check_constraints'])) {
            foreach ($schema['check_constraints'] as $constraintName => $checkClause) {
                $sql .= ",\n  CONSTRAINT `{$constraintName}` CHECK ({$checkClause})";
            }
        }

        $sql .= "\n)";

        // Add table options
        if (!empty($schema['engine'])) {
            $sql .= " ENGINE={$schema['engine']}";
        }

        if (!empty($schema['charset'])) {
            $sql .= " DEFAULT CHARSET={$schema['charset']}";
        }

        if (!empty($schema['collation'])) {
            $sql .= " COLLATE={$schema['collation']}";
        }

        // Add table comment if exists
        if (!empty($schema['table_comment'])) {
            $sql .= " COMMENT='" . addslashes($schema['table_comment']) . "'";
        }

        // Add partitioning if exists
        if (!empty($schema['partition'])) {
            $partition = $schema['partition'];
            $type = strtoupper($partition['type']);
            $column = $partition['column'];
            $count = $partition['count'];
            $sql .= "\nPARTITION BY {$type}(`{$column}`)\nPARTITIONS {$count}";
        }

        $sql .= ";\n";

        return $sql;
    }
}
