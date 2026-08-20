<?php namespace Roline\Commands\Database;

/**
 * DbExport Command
 *
 * Exports entire database to SQL file including both schema (CREATE TABLE statements)
 * and data (INSERT statements) for all tables. Generates complete database dump suitable
 * for backups, migration, version control, or restoration on different environments.
 *
 * What Gets Exported:
 *   - File Header:
 *     * Database name
 *     * Export timestamp
 *     * Total table count
 *     * Foreign key check disabling for safe import
 *
 *   - For Each Table:
 *     * DROP TABLE IF EXISTS statement (safe re-import)
 *     * CREATE TABLE statement with:
 *       - All column definitions
 *       - Data types and constraints
 *       - Primary keys
 *       - Engine and charset specifications
 *     * INSERT statements for all rows
 *     * Row count comment
 *
 * File Generation:
 *   - Auto-generates filename with timestamp if not specified
 *   - Format: {database_name}_backup_{YYYY-MM-DD_HHmmss}.sql
 *   - Saves to application/storage/exports/ directory
 *   - Creates exports directory if doesn't exist
 *   - Prompts before overwriting existing files
 *
 * SQL Format Features:
 *   - SET FOREIGN_KEY_CHECKS=0 at start (allows safe table recreation)
 *   - DROP TABLE IF EXISTS for each table (idempotent import)
 *   - Properly escaped values with mysqli::real_escape_string()
 *   - NULL handling with SQL NULL keyword
 *   - CURRENT_TIMESTAMP preservation for default values
 *   - SET FOREIGN_KEY_CHECKS=1 at end (restore safety)
 *
 * Use Cases:
 *   - Database backups before major changes
 *   - Migrating data between environments (dev → staging → production)
 *   - Version controlling database state
 *   - Sharing database snapshots with team
 *   - Creating test fixtures from real data
 *   - Disaster recovery preparation
 *
 * Distinction from table:export:
 *   - db:export     - Exports ALL tables in database (complete dump)
 *   - table:export  - Exports single table specified by Model (table-specific)
 *
 * Important Notes:
 *   - Export includes ALL data (can be large for populated databases)
 *   - Does NOT include database creation statement
 *   - Import requires database to exist already
 *   - Exports are plain SQL files (human-readable and editable)
 *   - Can be imported with: mysql database_name < export_file.sql
 *
 * Typical Workflow:
 *   1. Developer runs: php roline db:export
 *   2. Command auto-generates filename with timestamp
 *   3. Exports all tables with structure and data
 *   4. Saves to application/storage/exports/
 *   5. Developer can version control or transfer file
 *   6. Import on other environment with standard mysql command
 *
 * Example Output:
 *   Exporting database: myapp_db
 *
 *   Tables to export: 5
 *
 *     → Exporting users...
 *     → Exporting posts...
 *     → Exporting comments...
 *     → Exporting categories...
 *     → Exporting tags...
 *
 *   Database exported successfully!
 *
 *   Location: /path/to/application/storage/exports/myapp_db_backup_2025-01-27_143022.sql
 *
 * Usage:
 *   php roline db:export                    (auto-generate filename)
 *   php roline db:export mybackup.sql       (custom filename)
 *   php roline db:export prod_snapshot.sql  (descriptive name)
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Database
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\Model;
use Rackage\Registry;
use Roline\Output;
use Roline\Utils\SchemaReader;

class DbExport extends DatabaseCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Export entire database';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing optional filename parameter
     */
    public function usage()
    {
        return '<file|optional> [--schema]';
    }

    /**
     * Display detailed help information
     *
     * Shows arguments, export format details (CREATE TABLE + INSERT), examples with
     * auto-generated vs custom filenames, and output location.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <file|optional>  Output filename (auto-generates if not provided)');
        Output::line('  --schema         Export schema only (no data)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline db:export');
        Output::line('  php roline db:export database_backup.sql');
        Output::line('  php roline db:export --schema');
        Output::line('  php roline db:export schema.sql --schema');
        Output::line();

        Output::info('What it exports:');
        Output::line('  - CREATE TABLE statements for all tables');
        Output::line('  - INSERT statements for all data (unless --schema used)');
        Output::line('  - Excludes migrations table by default');
        Output::line();

        Output::info('Output Location:');
        Output::line('  Files are saved to: application/storage/exports/');
        Output::line();
    }

    /**
     * Execute database export to SQL file
     *
     * Exports all tables with structure (CREATE TABLE) and data (INSERT) to SQL file.
     * Auto-generates timestamped filename if not provided, creates exports directory if
     * needed, prompts before overwriting existing files, and generates complete SQL dump
     * with foreign key handling for safe import.
     *
     * @param array $arguments Command arguments (filename at index 0, optional)
     * @return void Exits with status 0 on cancel, 1 on failure
     */
    public function execute($arguments)
    {
        try {
            // Check for --schema flag
            $schemaOnly = in_array('--schema', $arguments);

            // Remove --schema flag from arguments to process filename
            $arguments = array_values(array_filter($arguments, function($arg) {
                return $arg !== '--schema';
            }));

            // Get database name from configuration
            $dbConfig = Registry::dbConfig();
            $driver = $dbConfig['default'] ?? 'mysql';
            $databaseName = $dbConfig[$driver]['database'] ?? 'database';

            // Determine output filename (auto-generate if not provided)
            $filename = $arguments[0] ?? null;

            if ($filename === null) {
                // Generate filename with timestamp: database_backup_YYYY-MM-DD_HHmmss.sql
                $timestamp = date('Y-m-d_His');
                $filename = "{$databaseName}_backup_{$timestamp}.sql";
            }

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

            // Display export progress header
            $this->line();
            $this->info("Exporting database: {$databaseName}");
            $this->line();

            // Get all tables from database
            $schemaReader = new SchemaReader();
            $tables = $schemaReader->getTables();

            // Check if database has any tables
            if (empty($tables)) {
                $this->info('No tables found in database.');
                $this->line();
                exit(0);
            }

            // Display table count
            $this->info('Tables to export: ' . count($tables));
            $this->line();

            // Open file handle for streaming (avoids memory exhaustion)
            $fileHandle = fopen($filepath, 'w');
            if (!$fileHandle) {
                throw new \Exception("Failed to open file for writing: {$filepath}");
            }

            // Write SQL file header with metadata
            fwrite($fileHandle, "-- Database Export\n");
            fwrite($fileHandle, "-- Database: {$databaseName}\n");
            fwrite($fileHandle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fileHandle, "-- Tables: " . count($tables) . "\n\n");

            // Disable foreign key checks for safe table recreation
            fwrite($fileHandle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            // Export each table (structure and data)
            foreach ($tables as $tableName) {
                $this->info("  → Exporting {$tableName}...");

                // Stream table SQL directly to file (no memory accumulation)
                $this->exportTable($tableName, $schemaReader, $fileHandle, $schemaOnly);
                fwrite($fileHandle, "\n");
            }

            // Re-enable foreign key checks
            fwrite($fileHandle, "SET FOREIGN_KEY_CHECKS=1;\n");

            // Close file handle
            fclose($fileHandle);

            // Export successful - display summary
            $this->line();
            $this->success("Database exported successfully!");
            $this->line();
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
     * Export a single table (structure and data)
     *
     * Streams complete SQL for one table directly to file including DROP TABLE, CREATE TABLE
     * with full schema definition, and INSERT statements for all rows. Writes directly to file
     * handle to avoid memory accumulation.
     *
     * @param string $tableName Table name to export
     * @param SchemaReader $schemaReader Schema reader instance for querying
     * @param resource $fileHandle File handle to write to
     * @param bool $schemaOnly If true, skip INSERT statements (structure only)
     * @return void
     */
    private function exportTable($tableName, $schemaReader, $fileHandle, $schemaOnly = false)
    {
        // Write table header comment
        fwrite($fileHandle, "--\n");
        fwrite($fileHandle, "-- Table: {$tableName}\n");
        fwrite($fileHandle, "--\n\n");

        // Get table schema from SchemaReader
        $schema = $schemaReader->getTableSchema($tableName);

        // Write DROP TABLE statement for idempotent import
        fwrite($fileHandle, "DROP TABLE IF EXISTS `{$tableName}`;\n\n");

        // Write CREATE TABLE statement with full schema
        fwrite($fileHandle, $this->generateCreateTableSQL($tableName, $schema));
        fwrite($fileHandle, "\n\n");

        // Stream INSERT statements for all table data directly to file (with progress) (unless schema-only mode)
        if (!$schemaOnly) {
            $this->generateInsertStatementsSQL($tableName, $fileHandle);
        } else {
            // Schema-only mode - write comment instead of data
            fwrite($fileHandle, "-- Data export skipped (--schema mode)\n\n");
        }
    }

    /**
     * Generate CREATE TABLE SQL
     *
     * Builds complete CREATE TABLE statement from schema definition including all
     * column definitions with types, constraints, defaults, primary key, foreign keys,
     * engine, and charset specifications.
     *
     * @param string $tableName Table name
     * @param array $schema Table schema array from SchemaReader
     * @return string CREATE TABLE SQL statement
     */
    private function generateCreateTableSQL($tableName, $schema)
    {
        // Extract schema components
        $columns = $schema['columns'] ?? [];
        $primaryKey = $schema['primary_key'] ?? null;
        $indexes = $schema['indexes'] ?? [];
        $foreignKeys = $schema['foreign_keys'] ?? [];
        $engine = $schema['engine'] ?? 'InnoDB';
        $charset = $schema['charset'] ?? 'utf8mb4';

        // Start CREATE TABLE statement
        $sql = "CREATE TABLE `{$tableName}` (\n";

        // Build column definitions array
        $columnDefs = [];
        foreach ($columns as $columnName => $columnDef) {
            // Start with column name and type
            $def = "  `{$columnName}` {$columnDef['type']}";

            // Add NOT NULL constraint if applicable
            if (!$columnDef['nullable']) {
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

            // Add extra attributes (AUTO_INCREMENT, etc.)
            if (!empty($columnDef['extra'])) {
                $extra = trim($columnDef['extra']);

                // Skip if extra is just "NULL" or "NOT NULL" (these are handled above)
                if (strtoupper($extra) !== 'NULL' && strtoupper($extra) !== 'NOT NULL') {
                    $def .= " {$extra}";
                }
            }

            // Add column comment if exists
            if (!empty($columnDef['comment'])) {
                $def .= " COMMENT '" . addslashes($columnDef['comment']) . "'";
            }

            $columnDefs[] = $def;
        }

        // Join column definitions with commas
        $sql .= implode(",\n", $columnDefs);

        // Add PRIMARY KEY constraint if exists
        if ($primaryKey) {
            // Backtick-quote each primary key column
            $pkColumns = '`' . implode('`, `', $primaryKey) . '`';
            $sql .= ",\n  PRIMARY KEY ({$pkColumns})";
        }

        // Add indexes (both simple and composite)
        if (!empty($indexes)) {
            foreach ($indexes as $indexName => $indexDef) {
                // Skip primary key (already added above)
                if ($indexName === 'PRIMARY') {
                    continue;
                }

                // Build column list for index
                $indexColumns = '`' . implode('`, `', $indexDef['columns']) . '`';
                $indexType = strtoupper($indexDef['type'] ?? 'BTREE');

                // Add appropriate index type
                if ($indexType === 'FULLTEXT') {
                    $sql .= ",\n  FULLTEXT KEY `{$indexName}` ({$indexColumns})";
                }
                elseif ($indexDef['unique']) {
                    $sql .= ",\n  UNIQUE KEY `{$indexName}` ({$indexColumns})";
                }
                else {
                    $sql .= ",\n  KEY `{$indexName}` ({$indexColumns})";
                }
            }
        }

        // Add FOREIGN KEY constraints if exist
        if (!empty($foreignKeys)) {
            foreach ($foreignKeys as $constraintName => $fk) {
                $sql .= ",\n  CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$fk['column']}`) ";
                $sql .= "REFERENCES `{$fk['referenced_table']}` (`{$fk['referenced_column']}`)";

                if (!empty($fk['on_delete'])) {
                    $sql .= " ON DELETE {$fk['on_delete']}";
                }

                if (!empty($fk['on_update'])) {
                    $sql .= " ON UPDATE {$fk['on_update']}";
                }
            }
        }

        // Add CHECK constraints if exist (MySQL 8.0.16+)
        $checkConstraints = $schema['check_constraints'] ?? [];
        if (!empty($checkConstraints)) {
            foreach ($checkConstraints as $constraintName => $checkClause) {
                $sql .= ",\n  CONSTRAINT `{$constraintName}` CHECK ({$checkClause})";
            }
        }

        // Close CREATE TABLE with engine and charset
        $sql .= "\n) ENGINE={$engine} DEFAULT CHARSET={$charset}";

        // Add table comment if exists
        $tableComment = $schema['table_comment'] ?? null;
        if (!empty($tableComment)) {
            $sql .= " COMMENT='" . addslashes($tableComment) . "'";
        }

        // Add partitioning if exists
        $partition = $schema['partition'] ?? null;
        if (!empty($partition)) {
            $type = strtoupper($partition['type']);
            $column = $partition['column'];
            $count = $partition['count'];
            $sql .= "\nPARTITION BY {$type}(`{$column}`)\nPARTITIONS {$count}";
        }

        $sql .= ";";

        return $sql;
    }

    /**
     * Generate INSERT statements for table data
     *
     * Queries all rows from table and streams batched multi-row INSERT statements directly
     * to file with properly escaped values and NULL handling. Uses extended INSERT syntax
     * (mysqldump-style) with configurable batch size for optimal performance.
     *
     * Benefits of batching:
     * - 10-100x faster imports (fewer SQL statements)
     * - Fewer disk writes (1000 rows = 1 write vs 1000 writes)
     * - Smaller file size (no repeated INSERT INTO overhead)
     * - Memory efficient (only current batch in memory)
     *
     * @param string $tableName Table name
     * @param resource $fileHandle File handle to write to
     * @return void
     */
    private function generateInsertStatementsSQL($tableName, $fileHandle)
    {
        // Batch size: rows per INSERT statement (keep at 1000 for safety)
        // 1000 rows = proven safe even for tables with large TEXT/BLOB columns
        $batchSize = 1000;

        // Progress interval: show progress every N rows (reduces console spam)
        // Update every 10k rows gives good feedback without cluttering output
        $progressInterval = 10000;

        // Query all rows from table (unbuffered - fetches one row at a time)
        $sql = "SELECT * FROM `{$tableName}`";
        $result = Model::stream()->sql($sql);

        // Show initial message (no newline yet - we'll update on same line)
        echo "  → Exporting {$tableName}...";

        // Force flush to show message immediately
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();

        // Handle query failure
        if (!$result) {
            fwrite($fileHandle, "-- No data in table\n");
            echo " \033[37m(0 rows)\033[0m\n";  // White text in parentheses
            return;
        }

        // Extract column names from result metadata
        $columns = [];
        $fields = $result->fetch_fields();
        foreach ($fields as $field) {
            $columns[] = $field->name;
        }

        // Write data header comment (row count shown at end after processing)
        fwrite($fileHandle, "-- Data for table {$tableName}\n\n");

        // Pre-build column list (same for all rows)
        $columnList = '`' . implode('`, `', $columns) . '`';

        // Batch processing: accumulate rows, write in chunks
        $batch = [];
        $rowCount = 0;
        $lastProgressUpdate = 0;

        while ($row = $result->fetch_assoc()) {
            $values = [];

            // Process each column value
            foreach ($columns as $column) {
                $value = $row[$column];
                // Model::quote() handles NULL, escaping, and quoting
                $values[] = Model::quote($value);
            }

            // Add row to batch as value list: (val1, val2, val3)
            $batch[] = '(' . implode(', ', $values) . ')';
            $rowCount++;

            // When batch is full, write multi-row INSERT statement
            if ($rowCount % $batchSize === 0) {
                // Extended INSERT: INSERT INTO table (cols) VALUES (row1), (row2), ..., (rowN);
                $sql = "INSERT INTO `{$tableName}` ({$columnList}) VALUES\n";
                $sql .= implode(",\n", $batch);
                $sql .= ";\n\n";

                fwrite($fileHandle, $sql);

                // Reset batch for next chunk
                $batch = [];
            }

            // Update progress every progressInterval rows (e.g., every 10k)
            if ($rowCount % $progressInterval === 0 && $rowCount !== $lastProgressUpdate) {
                // \r returns to start of line, \033[K clears rest of line
                echo "\r\033[K  → Exporting {$tableName}... \033[37m(" . number_format($rowCount) . " rows)\033[0m";

                // Force flush output immediately (bypass PHP's output buffering)
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                $lastProgressUpdate = $rowCount;
            }
        }

        // Write remaining rows (last batch if not perfectly divisible)
        if (!empty($batch)) {
            $sql = "INSERT INTO `{$tableName}` ({$columnList}) VALUES\n";
            $sql .= implode(",\n", $batch);
            $sql .= ";\n\n";

            fwrite($fileHandle, $sql);
        }

        // Final progress with newline to move to next line
        // \r\033[K clears the line first, then show final count
        echo "\r\033[K  → Exporting {$tableName}... \033[37m(" . number_format($rowCount) . " rows)\033[0m\n";
    }
}
