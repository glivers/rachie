<?php namespace Roline\Utils;

use Rackage\Registry;
use Rackage\Model;

/**
 * SchemaReader
 *
 * Reads current database schema structure by querying INFORMATION_SCHEMA.
 * Used for auto-generating migrations based on database state.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Roline
 * @package Roline\Utils
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */
class SchemaReader
{
    /**
     * Database name
     * @var string
     */
    private $database;

    /**
     * Constructor
     */
    public function __construct()
    {
        $dbConfig = Registry::dbConfig();
        $driver = $dbConfig['default'] ?? 'mysql';
        $this->database = $dbConfig[$driver]['database'] ?? '';
    }

    /**
     * Get all tables in the database
     *
     * @return array Array of table names
     */
    public function getTables()
    {
        $sql = "SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '{$this->database}'
                AND TABLE_TYPE = 'BASE TABLE'
                AND TABLE_NAME != 'migrations'
                ORDER BY TABLE_NAME";

        $result = Model::sql($sql);

        $tables = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tables[] = $row['TABLE_NAME'];
            }
        }

        return $tables;
    }

    /**
     * Get complete schema for all tables
     *
     * @return array Schema structure with tables and columns
     */
    public function getFullSchema()
    {
        $tables = $this->getTables();
        $schema = [];

        foreach ($tables as $table) {
            $schema[$table] = $this->getTableSchema($table);
        }

        return $schema;
    }

    /**
     * Get schema for a specific table
     *
     * @param string $table Table name
     * @return array Table schema with columns, indexes, etc.
     */
    public function getTableSchema($table)
    {
        return [
            'columns' => $this->getColumns($table),
            'indexes' => $this->getIndexes($table),
            'primary_key' => $this->getPrimaryKey($table),
            'foreign_keys' => $this->getForeignKeys($table),
            'check_constraints' => $this->getCheckConstraints($table),
            'partition' => $this->getPartition($table),
            'engine' => $this->getEngine($table),
            'charset' => $this->getCharset($table),
            'table_comment' => $this->getTableComment($table),
        ];
    }

    /**
     * Get table comment
     *
     * Retrieves the table-level comment from INFORMATION_SCHEMA.TABLES.
     *
     * @param string $table Table name
     * @return string|null Table comment or null if not set
     */
    public function getTableComment($table)
    {
        $sql = "SELECT TABLE_COMMENT
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '{$this->database}'
                AND TABLE_NAME = '{$table}'";

        $result = Model::sql($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();

            return $row['TABLE_COMMENT'] ?: null;
        }

        return null;
    }

    /**
     * Get columns for a table
     *
     * @param string $table Table name
     * @return array Array of column definitions
     */
    public function getColumns($table)
    {
        $sql = "SELECT
                    COLUMN_NAME,
                    COLUMN_TYPE,
                    IS_NULLABLE,
                    COLUMN_DEFAULT,
                    EXTRA,
                    CHARACTER_SET_NAME,
                    COLLATION_NAME,
                    COLUMN_COMMENT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = '{$this->database}'
                AND TABLE_NAME = '{$table}'
                ORDER BY ORDINAL_POSITION";

        $result = Model::sql($sql);

        $columns = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $columns[$row['COLUMN_NAME']] = [
                    'type' => $row['COLUMN_TYPE'],
                    'nullable' => $row['IS_NULLABLE'] === 'YES',
                    'default' => $row['COLUMN_DEFAULT'],
                    'extra' => $row['EXTRA'],
                    'charset' => $row['CHARACTER_SET_NAME'],
                    'collation' => $row['COLLATION_NAME'],
                    'comment' => $row['COLUMN_COMMENT'] ?: null,
                ];
            }
        }

        return $columns;
    }

    /**
     * Get indexes for a table
     *
     * @param string $table Table name
     * @return array Array of index definitions
     */
    public function getIndexes($table)
    {
        $sql = "SELECT
                    INDEX_NAME,
                    COLUMN_NAME,
                    NON_UNIQUE,
                    INDEX_TYPE
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = '{$this->database}'
                AND TABLE_NAME = '{$table}'
                AND INDEX_NAME != 'PRIMARY'
                ORDER BY INDEX_NAME, SEQ_IN_INDEX";

        $result = Model::sql($sql);

        $indexes = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $indexName = $row['INDEX_NAME'];
                if (!isset($indexes[$indexName])) {
                    $indexes[$indexName] = [
                        'unique' => $row['NON_UNIQUE'] == 0,
                        'type' => $row['INDEX_TYPE'],
                        'columns' => [],
                    ];
                }
                $indexes[$indexName]['columns'][] = $row['COLUMN_NAME'];
            }
        }

        return $indexes;
    }

    /**
     * Get primary key for a table
     *
     * @param string $table Table name
     * @return array|null Primary key column(s) or null
     */
    public function getPrimaryKey($table)
    {
        $sql = "SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = '{$this->database}'
                AND TABLE_NAME = '{$table}'
                AND INDEX_NAME = 'PRIMARY'
                ORDER BY SEQ_IN_INDEX";

        $result = Model::sql($sql);

        $columns = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['COLUMN_NAME'];
            }
        }

        return empty($columns) ? null : $columns;
    }

    /**
     * Get table engine
     *
     * @param string $table Table name
     * @return string Engine name (InnoDB, MyISAM, etc.)
     */
    public function getEngine($table)
    {
        $sql = "SELECT ENGINE
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '{$this->database}'
                AND TABLE_NAME = '{$table}'";

        $result = Model::sql($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['ENGINE'];
        }

        return 'InnoDB';
    }

    /**
     * Get table charset
     *
     * @param string $table Table name
     * @return string Charset name
     */
    public function getCharset($table)
    {
        $sql = "SELECT CCSA.CHARACTER_SET_NAME
                FROM INFORMATION_SCHEMA.TABLES T
                JOIN INFORMATION_SCHEMA.COLLATION_CHARACTER_SET_APPLICABILITY CCSA
                ON T.TABLE_COLLATION = CCSA.COLLATION_NAME
                WHERE T.TABLE_SCHEMA = '{$this->database}'
                AND T.TABLE_NAME = '{$table}'";

        $result = Model::sql($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['CHARACTER_SET_NAME'];
        }

        return 'utf8mb4';
    }

    /**
     * Get foreign keys for a table
     *
     * @param string $table Table name
     * @return array Array of foreign key definitions
     */
    public function getForeignKeys($table)
    {
        $sql = "SELECT
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = '{$this->database}'
                AND TABLE_NAME = '{$table}'
                AND REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY CONSTRAINT_NAME";

        $result = Model::sql($sql);

        $foreignKeys = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $constraintName = $row['CONSTRAINT_NAME'];

                // Get ON DELETE and ON UPDATE actions
                $refSql = "SELECT
                            DELETE_RULE,
                            UPDATE_RULE
                          FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                          WHERE CONSTRAINT_SCHEMA = '{$this->database}'
                          AND CONSTRAINT_NAME = '{$constraintName}'";

                $refResult = Model::sql($refSql);
                $refRow = $refResult->fetch_assoc(); 

                $foreignKeys[$constraintName] = [
                    'column' => $row['COLUMN_NAME'],
                    'referenced_table' => $row['REFERENCED_TABLE_NAME'],
                    'referenced_column' => $row['REFERENCED_COLUMN_NAME'],
                    'on_delete' => $refRow['DELETE_RULE'] ?? 'RESTRICT',
                    'on_update' => $refRow['UPDATE_RULE'] ?? 'RESTRICT',
                ];
            }
        }

        return $foreignKeys;
    }

    /**
     * Get CHECK constraints for a table
     *
     * Retrieves CHECK constraints from INFORMATION_SCHEMA.CHECK_CONSTRAINTS.
     * Available in MySQL 8.0.16 and later.
     *
     * Process:
     *   1. Query CHECK_CONSTRAINTS table for table's constraints
     *   2. Return associative array of constraint_name => check_clause
     *
     * Note: Column-level CHECK constraints use auto-generated names
     * in format: table_name_chk_N (where N is sequential number)
     *
     * @param string $table Table name
     * @return array Array of check constraint definitions
     */
    public function getCheckConstraints($table)
    {
        $sql = "SELECT
                    CONSTRAINT_NAME,
                    CHECK_CLAUSE
                FROM INFORMATION_SCHEMA.CHECK_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = '{$this->database}'
                AND TABLE_NAME = '{$table}'
                ORDER BY CONSTRAINT_NAME";

        $result = Model::sql($sql);

        $checkConstraints = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $checkConstraints[$row['CONSTRAINT_NAME']] = $row['CHECK_CLAUSE'];
            }
        }

        return $checkConstraints;
    }

    /**
     * Get partition info for a table
     *
     * Retrieves partition configuration from INFORMATION_SCHEMA.PARTITIONS.
     * Returns null if table is not partitioned.
     *
     * @param string $table Table name
     * @return array|null Partition config ['type', 'column', 'count'] or null
     */
    public function getPartition($table)
    {
        $sql = "SELECT PARTITION_METHOD, PARTITION_EXPRESSION, COUNT(*) as partition_count
                FROM INFORMATION_SCHEMA.PARTITIONS
                WHERE TABLE_SCHEMA = '{$this->database}'
                AND TABLE_NAME = '{$table}'
                AND PARTITION_NAME IS NOT NULL
                GROUP BY PARTITION_METHOD, PARTITION_EXPRESSION";

        $result = Model::sql($sql);

        if (!$result || $result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();

        // Parse partition method (HASH, KEY, RANGE, LIST)
        $type = strtolower($row['PARTITION_METHOD']);

        // Parse partition expression (column name)
        // Expression is like `source` or source - strip backticks
        $column = trim($row['PARTITION_EXPRESSION'], '`');

        return [
            'type' => $type,
            'column' => $column,
            'count' => (int) $row['partition_count']
        ];
    }
}
