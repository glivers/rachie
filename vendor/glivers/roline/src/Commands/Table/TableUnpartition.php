<?php namespace Roline\Commands\Table;

/**
 * TableUnpartition Command
 *
 * Removes partitioning from a table using the safe copy-swap approach.
 * Designed for huge tables where ALTER TABLE REMOVE PARTITIONING would lock too long.
 *
 * Process:
 *   1. Create new non-partitioned table (with _new suffix)
 *   2. Copy data in batches (original table stays live)
 *   3. Brief lock -> rename swap (original to _old, new to original)
 *   4. Drop old table
 *
 * Usage:
 *   php roline table:unpartition <table>
 *   php roline table:unpartition links
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

class TableUnpartition extends TableCommand
{
    /**
     * Batch size for copying rows
     */
    private $batchSize = 10000;

    public function description()
    {
        return 'Remove partitioning from table (copy-swap method)';
    }

    public function usage()
    {
        return '<table|required>';
    }

    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <table>  Table name to unpartition');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline table:unpartition links');
        Output::line('  php roline table:unpartition users');
        Output::line();

        Output::info('Process:');
        Output::line('  1. Creates new non-partitioned table');
        Output::line('  2. Copies data in batches (original stays live)');
        Output::line('  3. Brief lock to swap tables');
        Output::line('  4. Drops old partitioned table');
        Output::line();

        Output::info('Note:');
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
            $this->info('Usage: php roline table:unpartition <table>');
            $this->info('Example: php roline table:unpartition links');
            exit(1);
        }

        $tableName = $arguments[0];

        // Validate table exists
        $schema = new MySQLSchema();
        if (!$schema->tableExists($tableName)) {
            $this->error("Table '{$tableName}' does not exist!");
            exit(1);
        }

        // Check if partitioned
        $existingPartition = $schema->getExistingPartition($tableName);
        if (!$existingPartition) {
            $this->error("Table '{$tableName}' is not partitioned!");
            exit(1);
        }

        // Get table schema
        $schemaReader = new SchemaReader();
        $tableSchema = $schemaReader->getTableSchema($tableName);

        // Get row count and table size for estimates
        $rowCount = $schema->getRowCountEstimate($tableName);
        $tableSize = $schema->getTableSize($tableName);
        $tableSizeFormatted = $this->formatBytes($tableSize);

        // Show plan and ask for confirmation
        $this->line();
        $this->info("=== TABLE UNPARTITION PLAN ===");
        $this->line();
        $this->info("Table: {$tableName}");
        $this->info("Rows: " . number_format($rowCount));
        $this->info("Size: {$tableSizeFormatted}");
        $this->line();
        $this->info("Current: PARTITION BY " . strtoupper($existingPartition['type']) .
                   "({$existingPartition['column']}) PARTITIONS {$existingPartition['count']}");
        $this->line();
        $this->warning("Temporary space required: ~{$tableSizeFormatted}");

        if ($rowCount > 1000000) {
            $estimatedMinutes = ceil($rowCount / 500000);
            $this->warning("Estimated time: {$estimatedMinutes}-" . ($estimatedMinutes * 2) . " minutes");
        }
        $this->line();

        // Confirm
        $confirm = $this->confirm("Proceed with removing partitioning?");
        if (!$confirm) {
            $this->info("Cancelled.");
            exit(0);
        }

        // Execute unpartition
        try {
            $this->line();
            $this->unpartitionTable($tableName, $tableSchema);

            $this->line();
            $this->success("Partitioning removed from '{$tableName}'!");
            $this->line();

        } catch (\Exception $e) {
            $this->line();
            $this->error("Unpartition failed: " . $e->getMessage());
            $this->line();
            $this->info("Attempting cleanup...");
            $this->cleanup($tableName);
            exit(1);
        }
    }

    /**
     * Execute the copy-swap unpartition process
     */
    private function unpartitionTable($tableName, $tableSchema)
    {
        $newTable = "{$tableName}_new";
        $oldTable = "{$tableName}_old";

        // Step 1: Create new non-partitioned table
        $this->info("[1/4] Creating non-partitioned table structure...");

        $createSQL = $this->generateCreateTableSQL($tableName, $newTable);

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
     * Generate CREATE TABLE SQL without partitioning
     */
    private function generateCreateTableSQL($originalTable, $newTable)
    {
        // Get the original CREATE TABLE statement
        $result = Model::sql("SHOW CREATE TABLE `{$originalTable}`");
        $row = $result->fetch_assoc();
        $createSQL = $row['Create Table'];

        // Replace table name
        $createSQL = preg_replace("/CREATE TABLE `{$originalTable}`/", "CREATE TABLE `{$newTable}`", $createSQL);

        // Remove partition clause (everything after the closing paren and engine options)
        // Pattern: remove PARTITION BY ... to end, but keep ENGINE, CHARSET, etc.
        $createSQL = preg_replace('/\s*\/\*.*PARTITION BY.*\*\//', '', $createSQL);
        $createSQL = preg_replace('/\s*PARTITION BY\s+(HASH|KEY|RANGE|LIST)\s*\([^)]+\)\s*(PARTITIONS\s+\d+)?/i', '', $createSQL);

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
