<?php namespace Roline\Commands\Table;

/**
 * TableExport Command
 *
 * Simple standalone command to export table data to SQL or CSV.
 * Works with table names only - no model classes required.
 *
 * Usage:
 *   php roline table:export <tablename> [filename]
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Table
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\Model;
use Roline\Output;
use Roline\Schema\MySQLSchema;
use Roline\Utils\SchemaReader;

class TableExport extends TableCommand
{
    public function description()
    {
        return 'Export table data to SQL or CSV';
    }

    public function usage()
    {
        return '<tablename|required> <file|optional>';
    }

    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <tablename|required>  Database table name');
        Output::line('  <file|optional>       Output filename (auto-generates if not provided)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline table:export users');
        Output::line('  php roline table:export posts posts_backup.sql');
        Output::line('  php roline table:export products products.csv');
        Output::line();

        Output::info('Output Formats:');
        Output::line('  .sql  - SQL INSERT statements');
        Output::line('  .csv  - Comma-separated values');
        Output::line('  Auto-detects format from file extension (defaults to .sql)');
        Output::line();

        Output::info('Note:');
        Output::line('  For model-based table export, use:');
        Output::line('  php roline model:export-table <Model>');
        Output::line();
    }

    public function execute($arguments)
    {
        if (empty($arguments[0])) {
            $this->error('Table name is required!');
            $this->line();
            $this->info('Usage: php roline table:export <tablename> [file]');
            exit(1);
        }

        $tableName = $arguments[0];

        $schema = new MySQLSchema();

        // Validate table exists
        if (!$schema->tableExists($tableName)) {
            $this->error("Table '{$tableName}' does not exist!");
            exit(1);
        }

        // Determine output filename
        $filename = $arguments[1] ?? null;

        if ($filename === null) {
            $timestamp = date('Y-m-d_His');
            $filename = "{$tableName}_{$timestamp}.sql";
        }

        // Detect format
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $format = strtolower($extension) === 'csv' ? 'csv' : 'sql';

        // Ensure exports directory exists
        $exportsDir = getcwd() . '/application/storage/exports';
        if (!is_dir($exportsDir)) {
            mkdir($exportsDir, 0755, true);
        }

        $filepath = $exportsDir . '/' . $filename;

        // Check if file exists
        if (file_exists($filepath)) {
            $overwrite = $this->confirm("File '{$filename}' already exists. Overwrite?");
            if (!$overwrite) {
                $this->info("Export cancelled.");
                exit(0);
            }
        }

        // Check for --data-only flag
        $dataOnly = in_array('--data-only', $arguments);

        // Execute export
        try {
            $this->line();
            $this->info("Exporting table '{$tableName}'...");
            if ($dataOnly) {
                $this->info("Mode: Data only (no schema)");
            }
            $this->line();

            if ($format === 'csv') {
                $this->exportToCSV($tableName, $filepath);
            } else {
                $this->exportToSQL($tableName, $filepath, $dataOnly);
            }

            $this->line();
            $this->success("Export complete!");
            $this->line();
            $this->info("Format: " . strtoupper($format));
            $this->info("Location: {$filepath}");
            $this->line();

        } catch (\Exception $e) {
            $this->line();
            $this->error("Export failed: " . $e->getMessage());
            exit(1);
        }
    }

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

    private function exportToCSV($tableName, $filepath)
    {
        // Use unbuffered query for memory-efficient export
        $sql = "SELECT * FROM `{$tableName}`";
        $result = Model::stream()->sql($sql);

        if (!$result) {
            throw new \Exception("Failed to query table");
        }

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

        // Write each data row
        while ($row = $result->fetch_assoc()) {
            fputcsv($fp, array_values($row));
        }

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

            // Add unsigned if applicable
            if (!empty($columnDef['unsigned']) && $columnDef['unsigned']) {
                $def .= " unsigned";
            }

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
