<?php namespace Roline\Commands\Table;

/**
 * TableDelete Command
 *
 * Simple standalone command to drop (delete) a database table.
 * Works with table names only - no model classes required.
 *
 * WARNING: This is a DESTRUCTIVE operation! All table data will be permanently lost.
 *
 * Features:
 *   - Direct table deletion by name
 *   - Confirmation prompt
 *   - Validates table exists before deletion
 *
 * Note:
 *   For model-based table deletion, use:
 *   php roline model:drop-table <Model>
 *
 * Usage:
 *   php roline table:delete <tablename>
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Table
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Output;
use Roline\Schema\MySQLSchema;

class TableDelete extends TableCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Drop (delete) a database table';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing required table name
     */
    public function usage()
    {
        return '<tablename|required>';
    }

    /**
     * Display detailed help information
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <tablename|required>  Database table name to drop');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline table:delete old_users');
        Output::line('  php roline table:delete temp_data');
        Output::line();

        Output::info('Warning:');
        Output::line('  This permanently deletes the table and ALL its data!');
        Output::line();

        Output::info('Note:');
        Output::line('  For model-based table deletion, use:');
        Output::line('  php roline model:drop-table <Model>');
        Output::line();
    }

    /**
     * Execute table deletion
     *
     * @param array $arguments Command arguments
     * @return void
     */
    public function execute($arguments)
    {
        if (empty($arguments[0])) {
            $this->error('Table name is required!');
            $this->line();
            $this->info('Usage: php roline table:delete <tablename>');
            exit(1);
        }

        $tableName = $arguments[0];

        // Validate table exists
        $schema = new MySQLSchema();
        if (!$schema->tableExists($tableName)) {
            $this->error("Table '{$tableName}' does not exist!");
            exit(1);
        }

        // Show warning and request confirmation
        $this->line();
        $this->error("WARNING: This will permanently drop table '{$tableName}'!");
        $this->error("         All data will be lost!");
        $this->line();

        $confirmed = $this->confirm("Are you sure you want to drop this table?");

        if (!$confirmed) {
            $this->info("Table deletion cancelled.");
            exit(0);
        }

        // Execute deletion
        try {
            $this->line();
            $this->info("Dropping table '{$tableName}'...");

            $sql = "DROP TABLE `{$tableName}`";
            $schema->rawQuery($sql);

            $this->line();
            $this->success("Table '{$tableName}' dropped successfully!");
            $this->line();
        } catch (\Exception $e) {
            $this->line();
            $this->error("Failed to drop table: " . $e->getMessage());
            exit(1);
        }
    }
}
