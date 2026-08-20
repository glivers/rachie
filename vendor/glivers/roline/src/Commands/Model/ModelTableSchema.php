<?php namespace Roline\Commands\Model;

/**
 * ModelTableSchema Command
 *
 * Displays the complete structure of a database table including columns, data types,
 * constraints, keys, and indexes. Provides formatted output similar to MySQL's DESCRIBE
 * statement but with enhanced readability and additional index information.
 *
 * What Gets Displayed:
 *   - Column Information:
 *     * Column names
 *     * Data types (VARCHAR, INT, TEXT, etc.)
 *     * NULL/NOT NULL constraints
 *     * Key types (PRI, UNI, MUL)
 *     * Default values
 *   - Index Information:
 *     * Index names
 *     * Indexed columns
 *     * Unique/non-unique status
 *     * Index types (BTREE, HASH, etc.)
 *
 * Output Format:
 *   - Formatted table with aligned columns
 *   - Clear section headers for columns and indexes
 *   - Border separators for visual clarity
 *   - Color-coded output via Output class methods
 *
 * Use Cases:
 *   - Verify table structure after creation/migration
 *   - Debug schema issues
 *   - Document database structure
 *   - Compare model annotations with actual database
 *   - Understand existing table structure
 *
 * Typical Workflow:
 *   1. Run command with model name
 *   2. Command extracts table name from model
 *   3. Query database for column information
 *   4. Query database for index information
 *   5. Display formatted output to terminal
 *
 * Usage:
 *   php roline model:table-schema User
 *   php roline model:table-schema Post
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Model
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Output;
use Roline\Schema\MySQLSchema;

class ModelTableSchema extends ModelCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Show table structure';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing required model name
     */
    public function usage()
    {
        return '<Model|required>';
    }

    /**
     * Display detailed help information
     *
     * Shows what information gets displayed (columns, types, constraints, indexes)
     * and provides usage examples.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <Model|required>  Model class name (without "Model" suffix)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline model:table-schema User');
        Output::line('  php roline model:table-schema Post');
        Output::line();

        Output::info('Output:');
        Output::line('  - Column names and types');
        Output::line('  - Null/Not Null constraints');
        Output::line('  - Default values');
        Output::line('  - Primary and unique keys');
        Output::line('  - Indexes');
        Output::line();
    }

    /**
     * Execute schema display
     *
     * Retrieves and displays complete table structure including columns and indexes.
     * Validates model and table exist, queries database for schema information via
     * MySQLSchema, and formats output as aligned table with clear section headers.
     *
     * @param array $arguments Command arguments (model name at index 0)
     * @return void Exits with status 0 on success, 1 on failure
     */
    public function execute($arguments)
    {
        // Validate model name argument is provided and normalize (ucfirst, remove 'Model' suffix)
        if (empty($arguments[0])) {
            $this->error('Model name is required!');
            $this->line();
            $this->info('Usage: php roline model:table-schema <Model>');
            $this->line();
            $this->info('Example: php roline model:table-schema User');
            exit(1);
        }

        // Normalize model name (ucfirst + remove 'Model' suffix)
        $modelName = $this->validateName($arguments[0]);
        $modelClass = "Models\\{$modelName}Model";

        // Validate model class exists
        if (!class_exists($modelClass)) {
            $this->error("Model class not found: {$modelClass}");
            $this->line();
            $this->info('Create the model first: php roline model:create ' . $modelName);
            exit(1);
        }

        // Extract table name from model's protected static $table property
        try {
            // Use reflection to access protected static property
            $reflection = new \ReflectionClass($modelClass);
            $tableProperty = $reflection->getProperty('table');
            $tableProperty->setAccessible(true);
            $tableName = $tableProperty->getValue();

            // Validate table name is defined
            if (empty($tableName)) {
                $this->error('Model does not have a table name defined!');
                exit(1);
            }
        } catch (\Exception $e) {
            // Reflection failed
            $this->error('Error reading model: ' . $e->getMessage());
            exit(1);
        }

        // Validate table exists in database
        $schema = new MySQLSchema();
        if (!$schema->tableExists($tableName)) {
            $this->error("Table '{$tableName}' does not exist!");
            $this->line();
            $this->info("Create it first: php roline model:create-table {$modelName}");
            exit(1);
        }

        // Retrieve and display table schema information
        try {
            // Display table header with border
            $this->line();
            $this->line('=================================================');
            $this->line("  TABLE: {$tableName}");
            $this->line('=================================================');
            $this->line();

            // Query database for column information
            $columns = $schema->getTableColumns($tableName);

            // Handle empty table (no columns)
            if (empty($columns)) {
                $this->info('Table has no columns.');
                $this->line();
                exit(0);
            }

            // Display columns section header
            $this->success('Columns:');
            $this->line();

            // Display column table header row
            $this->line(sprintf(
                '  %-20s %-25s %-10s %-15s %s',
                'COLUMN',
                'TYPE',
                'NULL',
                'KEY',
                'DEFAULT'
            ));
            $this->line('  ' . str_repeat('-', 85));

            // Display each column's information
            foreach ($columns as $column) {
                $this->line(sprintf(
                    '  %-20s %-25s %-10s %-15s %s',
                    $column['Field'],
                    $column['Type'],
                    $column['Null'],
                    $column['Key'],
                    $column['Default'] ?? 'NULL'
                ));
            }

            $this->line();

            // Query database for index information
            $indexes = $schema->getTableIndexes($tableName);

            // Display indexes section if any exist
            if (!empty($indexes)) {
                $this->success('Indexes:');
                $this->line();

                // Display index table header row
                $this->line(sprintf(
                    '  %-25s %-15s %-10s %s',
                    'INDEX NAME',
                    'COLUMN',
                    'UNIQUE',
                    'TYPE'
                ));
                $this->line('  ' . str_repeat('-', 75));

                // Display each index's information
                foreach ($indexes as $index) {
                    $this->line(sprintf(
                        '  %-25s %-15s %-10s %s',
                        $index['Key_name'],
                        $index['Column_name'],
                        $index['Non_unique'] == 0 ? 'YES' : 'NO',
                        $index['Index_type']
                    ));
                }

                $this->line();
            }

            // Query database for partition information
            $partition = $schema->getExistingPartition($tableName);

            // Display partition section if exists
            if ($partition) {
                $this->success('Partition:');
                $this->line();
                $type = strtoupper($partition['type']);
                $this->line("  PARTITION BY {$type}({$partition['column']}) PARTITIONS {$partition['count']}");
                $this->line();
            }

            // Display closing border
            $this->line('=================================================');
            $this->line();

        } catch (\Exception $e) {
            // Schema query failed (database connection, SQL error, etc.)
            $this->line();
            $this->error("Failed to read table schema!");
            $this->line();
            $this->error("Error: " . $e->getMessage());
            $this->line();
            exit(1);
        }
    }
}
