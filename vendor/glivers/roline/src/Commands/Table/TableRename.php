<?php namespace Roline\Commands\Table;

/**
 * TableRename Command
 *
 * Simple standalone command to rename a database table.
 * Works with table names only - no model classes required.
 *
 * Usage:
 *   php roline table:rename <old_tablename> <new_tablename>
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

class TableRename extends TableCommand
{
    public function description()
    {
        return 'Rename a database table';
    }

    public function usage()
    {
        return '<old_tablename|required> <new_tablename|required>';
    }

    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <old_tablename|required>  Current table name');
        Output::line('  <new_tablename|required>  New table name');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline table:rename old_users new_users');
        Output::line('  php roline table:rename temp_data archive_data');
        Output::line();

        Output::info('Note:');
        Output::line('  For model-based table renaming, use:');
        Output::line('  php roline model:rename-table <Model> <new_tablename>');
        Output::line();
    }

    public function execute($arguments)
    {
        if (empty($arguments[0]) || empty($arguments[1])) {
            $this->error('Both old and new table names are required!');
            $this->line();
            $this->info('Usage: php roline table:rename <old_tablename> <new_tablename>');
            exit(1);
        }

        $oldTableName = $arguments[0];
        $newTableName = $arguments[1];

        $schema = new MySQLSchema();

        // Validate old table exists
        if (!$schema->tableExists($oldTableName)) {
            $this->error("Table '{$oldTableName}' does not exist!");
            exit(1);
        }

        // Validate new name doesn't exist
        if ($schema->tableExists($newTableName)) {
            $this->error("Table '{$newTableName}' already exists!");
            exit(1);
        }

        // Show rename preview
        $this->line();
        $this->info("Renaming table:");
        $this->line("  From: {$oldTableName}");
        $this->line("  To:   {$newTableName}");
        $this->line();

        $confirmed = $this->confirm("Proceed with rename?");

        if (!$confirmed) {
            $this->info("Table rename cancelled.");
            exit(0);
        }

        // Execute rename
        try {
            $this->line();
            $this->info("Renaming table...");

            $sql = "RENAME TABLE `{$oldTableName}` TO `{$newTableName}`";
            $schema->rawQuery($sql);

            $this->line();
            $this->success("Table renamed successfully!");
            $this->line();
        } catch (\Exception $e) {
            $this->line();
            $this->error("Failed to rename table: " . $e->getMessage());
            exit(1);
        }
    }
}
