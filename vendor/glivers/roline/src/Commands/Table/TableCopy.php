<?php namespace Roline\Commands\Table;

/**
 * TableCopy Command
 *
 * Copies a table within the database (structure and optionally data).
 * Faster than export→import since it stays within MySQL.
 *
 * Usage:
 *   php roline table:copy <source> <destination>
 *   php roline table:copy <source> <destination> --empty
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

class TableCopy extends TableCommand
{
    public function description()
    {
        return 'Copy table (structure + data)';
    }

    public function usage()
    {
        return '<source|required> <destination|required> [--empty]';
    }

    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <source>       Source table name');
        Output::line('  <destination>  Destination table name');
        Output::line();

        Output::info('Options:');
        Output::line('  --empty        Copy structure only (no data)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline table:copy users users_backup');
        Output::line('  php roline table:copy links links_test --empty');
        Output::line();
    }

    public function execute($arguments)
    {
        // Parse arguments
        if (empty($arguments[0])) {
            $this->error('Source table name is required!');
            $this->line();
            $this->info('Usage: php roline table:copy <source> <destination>');
            exit(1);
        }

        if (empty($arguments[1])) {
            $this->error('Destination table name is required!');
            $this->line();
            $this->info('Usage: php roline table:copy <source> <destination>');
            exit(1);
        }

        $sourceTable = $arguments[0];
        $destTable = $arguments[1];
        $emptyOnly = in_array('--empty', $arguments);

        // Validate source table exists
        $schema = new MySQLSchema();
        if (!$schema->tableExists($sourceTable)) {
            $this->error("Source table '{$sourceTable}' does not exist!");
            exit(1);
        }

        // Check if destination exists
        if ($schema->tableExists($destTable)) {
            $overwrite = $this->confirm("Table '{$destTable}' already exists. Drop and recreate?");
            if (!$overwrite) {
                $this->info("Cancelled.");
                exit(0);
            }

            // Drop existing
            Model::sql("DROP TABLE `{$destTable}`");
        }

        // Get estimated row count (fast - uses INFORMATION_SCHEMA)
        $rowCount = $schema->getRowCountEstimate($sourceTable);

        // Show plan
        $this->line();
        $this->info("Copying table:");
        $this->info("  From: {$sourceTable}");
        $this->info("  To:   {$destTable}");
        if ($emptyOnly) {
            $this->info("  Mode: Structure only (no data)");
        } else {
            $this->info("  Rows: ~" . number_format($rowCount) . " (estimate)");
        }
        $this->line();

        try {
            // Step 1: Copy structure
            $this->info("Creating table structure...");
            $sql = "CREATE TABLE `{$destTable}` LIKE `{$sourceTable}`";
            Model::sql($sql);

            // Step 2: Copy data (unless --empty)
            if (!$emptyOnly && $rowCount > 0) {
                $this->info("Copying data...");

                $sql = "INSERT INTO `{$destTable}` SELECT * FROM `{$sourceTable}`";
                Model::sql($sql);

                // Get actual count of copied rows
                $result = Model::sql("SELECT COUNT(*) as count FROM `{$destTable}`");
                $row = $result->fetch_assoc();
                $copied = $row['count'];

                $this->success("  Copied " . number_format($copied) . " rows");
            }

            $this->line();
            $this->success("Table copied successfully!");
            $this->line();
            $this->info("New table: {$destTable}");
            $this->line();

        } catch (\Exception $e) {
            $this->line();
            $this->error("Copy failed: " . $e->getMessage());

            // Cleanup on failure
            Model::sql("DROP TABLE IF EXISTS `{$destTable}`");
            exit(1);
        }
    }
}
