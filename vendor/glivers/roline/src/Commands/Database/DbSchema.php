<?php namespace Roline\Commands\Database;

/**
 * DbSchema Command
 *
 * Displays comprehensive schema overview for entire database including all tables and
 * their complete structure. Provides formatted output showing column definitions, data
 * types, constraints, primary keys, and indexes for every table in the database.
 *
 * What Gets Displayed:
 *   - Database summary with total table count
 *   - For each table:
 *     * Table name with separator border
 *     * All column definitions with:
 *       - Column name
 *       - Data type (VARCHAR, INT, TEXT, etc.)
 *       - NULL/NOT NULL constraints
 *       - Default values
 *       - Extra attributes (AUTO_INCREMENT, etc.)
 *     * Primary key columns
 *     * Indexes with column names and unique status
 *
 * Output Format:
 *   - Bordered header with title
 *   - Table count summary
 *   - Each table separated with border lines
 *   - Hierarchical indentation for readability
 *   - Empty state message for databases with no tables
 *
 * Distinction from table:schema:
 *   - db:schema     - Shows ALL tables in database (database-wide)
 *   - table:schema  - Shows single table specified by Model (table-specific)
 *
 * Use Cases:
 *   - Get overview of entire database structure
 *   - Document database schema for team/stakeholders
 *   - Compare development vs production schemas
 *   - Audit database after migrations
 *   - Understand inherited/legacy database structure
 *   - Generate schema documentation
 *
 * Typical Workflow:
 *   1. Developer runs: php roline db:schema
 *   2. Command connects to database
 *   3. Queries all table names
 *   4. Iterates through each table querying structure
 *   5. Displays formatted output for each table
 *   6. Shows summary at end
 *
 * Important Notes:
 *   - Read-only command (makes no database changes)
 *   - Safe to run anytime without side effects
 *   - Output can be lengthy for large databases
 *   - Uses SchemaReader utility for schema extraction
 *
 * Example Output:
 *   =================================================
 *              DATABASE SCHEMA
 *   =================================================
 *
 *   Total Tables: 3
 *
 *   TABLE: users
 *   --------------------------------------------------
 *     id INT NOT NULL AUTO_INCREMENT
 *     username VARCHAR(50) NOT NULL
 *     email VARCHAR(100) NOT NULL
 *     password VARCHAR(255) NOT NULL
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 *
 *     PRIMARY KEY: id
 *
 *     INDEXES:
 *       idx_username: username UNIQUE
 *       idx_email: email UNIQUE
 *
 *   TABLE: posts
 *   --------------------------------------------------
 *     id INT NOT NULL AUTO_INCREMENT
 *     user_id INT NOT NULL
 *     title VARCHAR(200) NOT NULL
 *     body TEXT
 *     published_at TIMESTAMP
 *
 *     PRIMARY KEY: id
 *
 *     INDEXES:
 *       idx_user_id: user_id
 *
 *   =================================================
 *
 * Usage:
 *   php roline db:schema
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Database
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Output;
use Roline\Utils\SchemaReader;

class DbSchema extends DatabaseCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Show full database schema';
    }

    /**
     * Get command usage syntax
     *
     * @return string Empty string (no arguments required)
     */
    public function usage()
    {
        return '';
    }

    /**
     * Display detailed help information
     *
     * Shows what information gets displayed (tables, columns, keys, indexes),
     * provides usage examples, and explains output format.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Description:');
        Output::line('  Displays all tables in the database with their column information.');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline db:schema');
        Output::line();

        Output::info('Output:');
        Output::line('  - List of all tables');
        Output::line('  - Column names and types for each table');
        Output::line('  - Primary keys and indexes');
        Output::line();
    }

    /**
     * Execute database schema display
     *
     * Queries database for all table names, retrieves complete schema information for
     * each table via SchemaReader, and displays formatted output showing table structures,
     * columns, primary keys, and indexes for the entire database.
     *
     * @param array $arguments Command arguments (none required)
     * @return void Exits with status 0 on success, 1 on failure
     */
    public function execute($arguments)
    {
        try {
            // Display progress message
            $this->line();
            $this->info('Reading database schema...');
            $this->line();

            // Create SchemaReader utility instance
            $schemaReader = new SchemaReader();

            // Get list of all tables in database
            $tables = $schemaReader->getTables();

            // Check if database has any tables
            if (empty($tables)) {
                $this->info('No tables found in database.');
                $this->line();
                exit(0);
            }

            // Display header with border
            $this->line('=================================================');
            $this->line('           DATABASE SCHEMA');
            $this->line('=================================================');
            $this->line();

            // Display total table count
            $this->success('Total Tables: ' . count($tables));
            $this->line();

            // Display schema for each table
            foreach ($tables as $tableName) {
                $this->displayTableSchema($tableName, $schemaReader);
                $this->line();
            }

            // Display closing border
            $this->line('=================================================');
            $this->line();

        } catch (\Exception $e) {
            // Schema read failed (database connection, query error, etc.)
            $this->line();
            $this->error('Failed to read database schema!');
            $this->line();
            $this->error('Error: ' . $e->getMessage());
            $this->line();
            exit(1);
        }
    }

    /**
     * Display schema for a single table
     *
     * Outputs formatted table structure including column definitions with data types,
     * constraints, defaults, primary key information, foreign key relationships,
     * and index details. Provides hierarchical indentation for readability.
     *
     * @param string $tableName Table name to display
     * @param SchemaReader $schemaReader Schema reader instance for querying
     * @return void
     */
    private function displayTableSchema($tableName, $schemaReader)
    {
        // Display table header with border separator
        $this->line("TABLE: {$tableName}");
        $this->line(str_repeat('-', 50));

        // Get complete schema definition for table
        $schema = $schemaReader->getTableSchema($tableName);
        $columns = $schema['columns'] ?? [];

        // Check if table has any columns
        if (empty($columns)) {
            $this->line('  (No columns)');
            return;
        }

        // Display each column definition
        foreach ($columns as $columnName => $columnDef) {
            // Build column definition parts array
            $parts = [];
            $parts[] = $columnName;
            $parts[] = $columnDef['type'];

            // Add NOT NULL constraint if applicable
            if (!$columnDef['nullable']) {
                $parts[] = 'NOT NULL';
            }

            // Add DEFAULT value if set
            if ($columnDef['default'] !== null) {
                $parts[] = "DEFAULT {$columnDef['default']}";
            }

            // Add extra attributes (AUTO_INCREMENT, etc.)
            if (!empty($columnDef['extra'])) {
                $parts[] = strtoupper($columnDef['extra']);
            }

            // Add column comment if exists
            if (!empty($columnDef['comment'])) {
                $parts[] = "-- {$columnDef['comment']}";
            }

            // Display complete column definition with indentation
            $this->line('  ' . implode(' ', $parts));
        }

        // Display table comment if exists
        if (!empty($schema['table_comment'])) {
            $this->line();
            $this->line("  COMMENT: {$schema['table_comment']}");
        }

        // Display primary key information if exists
        if (!empty($schema['primary_key'])) {
            $this->line();

            // Join multiple primary key columns with comma
            $pkColumns = implode(', ', $schema['primary_key']);
            $this->line("  PRIMARY KEY: {$pkColumns}");
        }

        // Display foreign key information if exists
        if (!empty($schema['foreign_keys'])) {
            $this->line();
            $this->line('  FOREIGN KEYS:');

            // Display each foreign key with referenced table and actions
            foreach ($schema['foreign_keys'] as $constraintName => $fk) {
                $fkDef = "{$fk['column']} → {$fk['referenced_table']}({$fk['referenced_column']})";

                // Add ON DELETE and ON UPDATE actions
                $actions = [];
                if (!empty($fk['on_delete']) && $fk['on_delete'] !== 'RESTRICT') {
                    $actions[] = "ON DELETE {$fk['on_delete']}";
                }
                if (!empty($fk['on_update']) && $fk['on_update'] !== 'RESTRICT') {
                    $actions[] = "ON UPDATE {$fk['on_update']}";
                }

                if (!empty($actions)) {
                    $fkDef .= ' [' . implode(', ', $actions) . ']';
                }

                // Display foreign key definition with extra indentation
                $this->line("    {$constraintName}: {$fkDef}");
            }
        }

        // Display CHECK constraints if exists
        if (!empty($schema['check_constraints'])) {
            $this->line();
            $this->line('  CHECK CONSTRAINTS:');

            // Display each check constraint with its clause
            foreach ($schema['check_constraints'] as $constraintName => $checkClause) {
                $this->line("    {$constraintName}: {$checkClause}");
            }
        }

        // Display index information if exists
        if (!empty($schema['indexes'])) {
            $this->line();
            $this->line('  INDEXES:');

            // Display each index with columns and type/unique status
            foreach ($schema['indexes'] as $indexName => $indexDef) {
                // Join index columns with comma
                $columns = implode(', ', $indexDef['columns']);

                // Determine index type label
                $indexType = strtoupper($indexDef['type'] ?? 'BTREE');
                $typeLabel = '';

                if ($indexType === 'FULLTEXT') {
                    $typeLabel = 'FULLTEXT';
                }
                elseif ($indexDef['unique'] ?? false) {
                    $typeLabel = 'UNIQUE';
                }

                // Display index definition with extra indentation
                $this->line("    {$indexName}: {$columns} {$typeLabel}");
            }
        }

        // Display partition information if exists
        if (!empty($schema['partition'])) {
            $this->line();
            $partition = $schema['partition'];
            $type = strtoupper($partition['type']);
            $this->line("  PARTITION BY {$type}({$partition['column']}) PARTITIONS {$partition['count']}");
        }
    }
}
