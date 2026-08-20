<?php namespace Roline\Commands\Table;

/**
 * TableSchema Command
 *
 * Simple standalone command to display database table structure.
 * Works with table names only - no model classes required.
 *
 * Usage:
 *   php roline table:schema <tablename>
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

class TableSchema extends TableCommand
{
    public function description()
    {
        return 'Display table structure/schema';
    }

    public function usage()
    {
        return '<tablename|required>';
    }

    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <tablename|required>  Database table name');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline table:schema users');
        Output::line('  php roline table:schema posts');
        Output::line();

        Output::info('Note:');
        Output::line('  For model-based schema display, use:');
        Output::line('  php roline model:table-schema <Model>');
        Output::line();
    }

    public function execute($arguments)
    {
        if (empty($arguments[0])) {
            $this->error('Table name is required!');
            $this->line();
            $this->info('Usage: php roline table:schema <tablename>');
            exit(1);
        }

        $tableName = $arguments[0];

        $schema = new MySQLSchema();

        // Validate table exists
        if (!$schema->tableExists($tableName)) {
            $this->error("Table '{$tableName}' does not exist!");
            exit(1);
        }

        // Display schema
        try {
            $this->line();
            $this->info("Table: {$tableName}");
            $this->line();

            $schema->displayTableSchema($tableName);

            $this->line();
        } catch (\Exception $e) {
            $this->line();
            $this->error("Failed to display schema: " . $e->getMessage());
            exit(1);
        }
    }
}
