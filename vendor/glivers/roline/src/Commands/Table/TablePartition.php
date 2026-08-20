<?php namespace Roline\Commands\Table;

/**
 * TablePartition Command
 *
 * Adds partitioning to an existing table using the safe copy-swap approach.
 * Designed for huge tables (100M+ rows) where ALTER TABLE would lock too long.
 *
 * Process:
 *   1. Create new partitioned table (with _new suffix)
 *   2. Copy data in batches (original table stays live)
 *   3. Brief lock -> rename swap (original to _old, new to original)
 *   4. Drop old table
 *
 * Usage:
 *   php roline table:partition <table> <type(column)> <count>
 *   php roline table:partition links hash(source) 32
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

class TablePartition extends TableCommand
{
    /**
     * Batch size for copying rows
     */
    private $batchSize = 10000;

    public function description()
    {
        return 'Add partitioning to table (copy-swap method)';
    }

    public function usage()
    {
        return '<table|required> <type(column)|required> <count|required>';
    }

    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <table>        Table name to partition');
        Output::line('  <type(column)> Partition type and column: hash(column) or key(column)');
        Output::line('  <count>        Number of partitions (e.g., 32)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline table:partition links hash(source) 32');
        Output::line('  php roline table:partition users key(user_id) 16');
        Output::line();

        Output::info('Process:');
        Output::line('  1. Creates new partitioned table');
        Output::line('  2. Copies data in batches (original stays live)');
        Output::line('  3. Brief lock to swap tables');
        Output::line('  4. Drops old table');
        Output::line();

        Output::info('Note:');
        Output::line('  - Partition column must be part of PRIMARY KEY');
        Output::line('  - Requires temporary disk space for table copy');
        Output::line('  - Safe for production - minimal lock time');
        Output::line();
    }

    public function execute($arguments)
    {
        // Parse arguments
        if (empty($arguments[0])) {
            $this->error('Table name is required!');
            $this->line();
            $this->info('Usage: php roline table:partition <table> <type(column)> <count>');
            $this->info('Example: php roline table:partition links hash(source) 32');
            exit(1);
        }

        $tableName = $arguments[0];

        // Parse partition specification: hash(column) or key(column)
        if (empty($arguments[1])) {
            $this->error('Partition type and column required!');
            $this->line();
            $this->info('Usage: php roline table:partition links hash(source) 32');
            exit(1);
        }

        $partitionSpec = $arguments[1];
        if (!preg_match('/^(hash|key)\((\w+)\)$/i', $partitionSpec, $matches)) {
            $this->error("Invalid partition format: {$partitionSpec}");
            $this->line();
            $this->info('Format: hash(column) or key(column)');
            $this->info('Example: hash(source)');
            exit(1);
        }

        $partitionType = strtoupper($matches[1]);
        $partitionColumn = $matches[2];

        // Parse count
        if (empty($arguments[2]) || !is_numeric($arguments[2])) {
            $this->error('Partition count required!');
            $this->line();
            $this->info('Usage: php roline table:partition links hash(source) 32');
            exit(1);
        }

        $partitionCount = (int) $arguments[2];

        // Validate table exists
        $schema = new MySQLSchema();
        if (!$schema->tableExists($tableName)) {
            $this->error("Table '{$tableName}' does not exist!");
            exit(1);
        }

        // Check if already partitioned
        $existingPartition = $schema->getExistingPartition($tableName);
        if ($existingPartition) {
            $this->error("Table '{$tableName}' is already partitioned!");
            $this->line();
            $this->info("Current: PARTITION BY " . strtoupper($existingPartition['type']) .
                       "({$existingPartition['column']}) PARTITIONS {$existingPartition['count']}");
            $this->line();
            $this->info("To change partitioning, first run: php roline table:unpartition {$tableName}");
            exit(1);
        }

        // Get table schema
        $schemaReader = new SchemaReader();
        $tableSchema = $schemaReader->getTableSchema($tableName);

        // Validate partition column exists
        if (!isset($tableSchema['columns'][$partitionColumn])) {
            $this->error("Column '{$partitionColumn}' does not exist in table '{$tableName}'!");
            exit(1);
        }

        // Validate partition column is in PRIMARY KEY
        $primaryKey = $tableSchema['primary_key'] ?? [];
        if (!in_array($partitionColumn, $primaryKey)) {
            $this->error("Column '{$partitionColumn}' must be part of PRIMARY KEY!");
            $this->line();
            $this->info("Current PRIMARY KEY: " . (empty($primaryKey) ? '(none)' : implode(', ', $primaryKey)));
            $this->line();
            $this->info("MySQL requires partition column to be in every unique key.");
            $this->info("Add '{$partitionColumn}' to PRIMARY KEY first, then partition.");
            exit(1);
        }

        // Get row count and table size for estimates
        $rowCount = $schema->getRowCountEstimate($tableName);
        $tableSize = $schema->getTableSize($tableName);
        $tableSizeFormatted = $this->formatBytes($tableSize);

        // Show plan and ask for confirmation
        $this->line();
        $this->info("=== TABLE PARTITION PLAN ===");
        $this->line();
        $this->info("Table: {$tableName}");
        $this->info("Rows: " . number_format($rowCount));
        $this->info("Size: {$tableSizeFormatted}");
        $this->line();
        $this->info("Partition: {$partitionType}({$partitionColumn}) PARTITIONS {$partitionCount}");
        $this->line();
        $this->warning("Temporary space required: ~{$tableSizeFormatted}");

        if ($rowCount > 1000000) {
            $estimatedMinutes = ceil($rowCount / 500000);
            $this->warning("Estimated time: {$estimatedMinutes}-" . ($estimatedMinutes * 2) . " minutes");
        }
        $this->line();

        // Confirm
        $confirm = $this->confirm("Proceed with partitioning?");
        if (!$confirm) {
            $this->info("Cancelled.");
            exit(0);
        }

        // Execute partition
        try {
            $this->line();
            $this->partitionTable($tableName, $tableSchema, $partitionType, $partitionColumn, $partitionCount);

            $this->line();
            $this->success("Table '{$tableName}' partitioned successfully!");
            $this->line();
            $this->info("PARTITION BY {$partitionType}({$partitionColumn}) PARTITIONS {$partitionCount}");
            $this->line();

        } catch (\Exception $e) {
            $this->line();
            $this->error("Partition failed: " . $e->getMessage());
            $this->line();
            $this->info("Attempting cleanup...");
            $this->cleanup($tableName);
            exit(1);
        }
    }

    /**
     * Execute the copy-swap partition process
     */
    private function partitionTable($tableName, $tableSchema, $partitionType, $partitionColumn, $partitionCount)
    {
        $newTable = "{$tableName}_new";
        $oldTable = "{$tableName}_old";

        // Step 1: Create new partitioned table
        $this->info("[1/4] Creating partitioned table structure...");

        $createSQL = $this->generateCreateTableSQL($tableName, $newTable, $tableSchema, $partitionType, $partitionColumn, $partitionCount);

        // Drop if exists from failed previous attempt
        Model::sql("DROP TABLE IF EXISTS `{$newTable}`");

        Model::sql($createSQL);
        $this->success("  Created {$newTable}");

        // Step 2: Copy data in batches
        $this->line();
        $this->info("[2/4] Copying data...");

        $this->copyData($tableName, $newTable, $tableSchema);

        // Step 3: Swap tables
        $this->line();
        $this->info("[3/4] Swapping tables (brief lock)...");

        // Drop old backup if exists
        Model::sql("DROP TABLE IF EXISTS `{$oldTable}`");

        // Atomic rename swap
        $renameSQL = "RENAME TABLE `{$tableName}` TO `{$oldTable}`, `{$newTable}` TO `{$tableName}`";
        Model::sql($renameSQL);
        $this->success("  Tables swapped");

        // Step 4: Drop old table
        $this->line();
        $this->info("[4/4] Dropping old table...");

        Model::sql("DROP TABLE IF EXISTS `{$oldTable}`");
        $this->success("  Cleanup complete");
    }

    /**
     * Generate CREATE TABLE SQL with partitioning
     */
    private function generateCreateTableSQL($originalTable, $newTable, $schema, $partitionType, $partitionColumn, $partitionCount)
    {
        // Get the original CREATE TABLE statement
        $result = Model::sql("SHOW CREATE TABLE `{$originalTable}`");
        $row = $result->fetch_assoc();
        $createSQL = $row['Create Table'];

        // Replace table name
        $createSQL = preg_replace("/CREATE TABLE `{$originalTable}`/", "CREATE TABLE `{$newTable}`", $createSQL);

        // Add partition clause
        $createSQL .= "\nPARTITION BY {$partitionType}(`{$partitionColumn}`)\nPARTITIONS {$partitionCount}";

        return $createSQL;
    }

    /**
     * Copy data from original to new table in batches
     */
    private function copyData($sourceTable, $destTable, $schema)
    {
        // Get primary key for ordered batching
        $primaryKey = $schema['primary_key'] ?? ['id'];
        $pkColumn = $primaryKey[0]; // Use first PK column for ordering

        // Get total count
        $countResult = Model::sql("SELECT COUNT(*) as cnt FROM `{$sourceTable}`");
        $countRow = $countResult->fetch_assoc();
        $totalRows = (int) $countRow['cnt'];

        if ($totalRows === 0) {
            $this->info("  No data to copy");
            return;
        }

        $copied = 0;
        $lastId = 0;
        $startTime = microtime(true);

        while ($copied < $totalRows) {
            // Copy batch using INSERT ... SELECT with ordered pagination
            $sql = "INSERT INTO `{$destTable}` SELECT * FROM `{$sourceTable}`
                    WHERE `{$pkColumn}` > {$lastId}
                    ORDER BY `{$pkColumn}`
                    LIMIT {$this->batchSize}";

            Model::sql($sql);

            // Get affected rows count
            $affectedResult = Model::sql("SELECT ROW_COUNT() as affected");
            $affectedRow = $affectedResult->fetch_assoc();
            $affected = (int) $affectedRow['affected'];

            if ($affected === 0) {
                break; // No more rows
            }

            $copied += $affected;

            // Get last ID for next batch
            $lastIdResult = Model::sql(
                "SELECT MAX(`{$pkColumn}`) as last_id FROM `{$destTable}`"
            );
            $lastIdRow = $lastIdResult->fetch_assoc();
            $lastId = $lastIdRow['last_id'];

            // Progress update
            $percent = round(($copied / $totalRows) * 100);
            $elapsed = microtime(true) - $startTime;
            $rowsPerSec = $elapsed > 0 ? round($copied / $elapsed) : 0;

            echo "\r  Copied " . number_format($copied) . " / " . number_format($totalRows) .
                 " rows ({$percent}%) - {$rowsPerSec} rows/sec";

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }

        echo "\n";
        $this->success("  Copied " . number_format($copied) . " rows");
    }

    /**
     * Cleanup on failure
     */
    private function cleanup($tableName)
    {
        $newTable = "{$tableName}_new";

        try {
            Model::sql("DROP TABLE IF EXISTS `{$newTable}`");
            $this->info("Cleaned up temporary table.");
        } catch (\Exception $e) {
            $this->warning("Cleanup failed - manually drop: {$newTable}");
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
