<?php namespace Roline\Commands\Table;

/**
 * TableCreate Command
 *
 * Simple standalone command to create a database table directly using SQL DDL.
 * Works with table names only - no model classes required. Provides interactive
 * column definition or accepts SQL file input.
 *
 * Features:
 *   - Interactive column-by-column definition
 *   - Primary key auto-detection
 *   - Common column types (varchar, int, text, datetime, etc.)
 *   - NULL/NOT NULL specification
 *   - Default values
 *   - Or import from SQL file
 *
 * Use Cases:
 *   - Quick table creation without models
 *   - Database prototyping
 *   - Creating lookup/reference tables
 *   - Legacy database integration
 *
 * Note:
 *   For model-based table creation with @column annotations, use:
 *   php roline model:create-table <Model>
 *
 * Usage:
 *   php roline table:create <tablename>
 *   php roline table:create <tablename> --sql=create.sql
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

class TableCreate extends TableCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Create a database table directly';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing required table name
     */
    public function usage()
    {
        return '<tablename|required> [--sql=file]';
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
        Output::line('  <tablename|required>  Database table name');
        Output::line('  --sql=file            SQL file with CREATE TABLE statement');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline table:create users');
        Output::line('  php roline table:create categories --sql=schema.sql');
        Output::line();

        Output::info('Note:');
        Output::line('  For model-based tables with @column annotations, use:');
        Output::line('  php roline model:create-table <Model>');
        Output::line();
    }

    /**
     * Execute table creation
     *
     * @param array $arguments Command arguments
     * @return void
     */
    public function execute($arguments)
    {
        if (empty($arguments[0])) {
            $this->error('Table name is required!');
            $this->line();
            $this->info('Usage: php roline table:create <tablename>');
            exit(1);
        }

        $tableName = $arguments[0];

        // Check if table already exists
        $schema = new MySQLSchema();
        if ($schema->tableExists($tableName)) {
            $this->error("Table '{$tableName}' already exists!");
            $this->line();
            $this->info("Use table:update to modify it, or table:delete to drop it.");
            exit(1);
        }

        // Check for --sql flag
        $sqlFile = null;
        foreach ($arguments as $arg) {
            if (strpos($arg, '--sql=') === 0) {
                $sqlFile = substr($arg, 6);
                break;
            }
        }

        if ($sqlFile) {
            $this->createFromSQL($tableName, $sqlFile, $schema);
        } else {
            $this->createInteractive($tableName, $schema);
        }
    }

    /**
     * Create table from SQL file
     *
     * @param string $tableName Table name
     * @param string $sqlFile SQL file path
     * @param MySQLSchema $schema Schema instance
     * @return void
     */
    private function createFromSQL($tableName, $sqlFile, $schema)
    {
        if (!file_exists($sqlFile)) {
            $this->error("SQL file not found: {$sqlFile}");
            exit(1);
        }

        $sql = file_get_contents($sqlFile);

        $this->line();
        $this->info("Creating table '{$tableName}' from SQL file...");

        try {
            $schema->rawQuery($sql);
            $this->line();
            $this->success("Table '{$tableName}' created successfully!");
            $this->line();
        } catch (\Exception $e) {
            $this->line();
            $this->error("Failed to create table: " . $e->getMessage());
            exit(1);
        }
    }

    /**
     * Create table interactively
     *
     * @param string $tableName Table name
     * @param MySQLSchema $schema Schema instance
     * @return void
     */
    private function createInteractive($tableName, $schema)
    {
        $this->line();
        $this->info("Creating table: {$tableName}");
        $this->line();
        $this->info("Define columns (press Enter with empty name to finish):");
        $this->line();

        $columns = [];
        $primaryKey = null;

        while (true) {
            $this->line("Column name (or press Enter to finish): ", false);
            $columnName = trim(fgets(STDIN));

            if (empty($columnName)) {
                break;
            }

            // Get column type
            $this->line("Column type [VARCHAR(255)]: ", false);
            $columnType = trim(fgets(STDIN));
            if (empty($columnType)) {
                $columnType = 'VARCHAR(255)';
            }

            // Get NULL/NOT NULL
            $nullable = $this->confirm("Allow NULL?", false);
            $nullConstraint = $nullable ? 'NULL' : 'NOT NULL';

            // Get default value
            $this->line("Default value (or press Enter for none): ", false);
            $defaultValue = trim(fgets(STDIN));
            $defaultConstraint = '';
            if (!empty($defaultValue)) {
                $defaultConstraint = "DEFAULT '{$defaultValue}'";
            }

            // Check if primary key
            $isPrimary = $this->confirm("Is this the primary key?", false);
            if ($isPrimary) {
                $primaryKey = $columnName;
                $nullConstraint = 'NOT NULL';

                // Check if auto increment
                $autoIncrement = $this->confirm("Auto increment?", true);
                if ($autoIncrement) {
                    $columnType .= ' AUTO_INCREMENT';
                }
            }

            $columnDef = "`{$columnName}` {$columnType} {$nullConstraint} {$defaultConstraint}";
            $columns[] = trim($columnDef);

            $this->success("  Added: {$columnName} ({$columnType})");
        }

        if (empty($columns)) {
            $this->line();
            $this->info("No columns defined. Table creation cancelled.");
            exit(0);
        }

        // Build CREATE TABLE SQL
        $sql = "CREATE TABLE `{$tableName}` (\n  ";
        $sql .= implode(",\n  ", $columns);

        if ($primaryKey) {
            $sql .= ",\n  PRIMARY KEY (`{$primaryKey}`)";
        }

        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        // Show SQL preview
        $this->line();
        $this->info("SQL Preview:");
        $this->line($sql);
        $this->line();

        // Confirm creation
        $confirmed = $this->confirm("Create this table?");

        if (!$confirmed) {
            $this->info("Table creation cancelled.");
            exit(0);
        }

        // Execute creation
        try {
            $this->line();
            $this->info("Creating table...");
            $schema->rawQuery($sql);

            $this->line();
            $this->success("Table '{$tableName}' created successfully!");
            $this->line();
        } catch (\Exception $e) {
            $this->line();
            $this->error("Failed to create table: " . $e->getMessage());
            exit(1);
        }
    }
}
