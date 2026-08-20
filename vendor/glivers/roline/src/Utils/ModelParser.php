<?php namespace Roline\Utils;

use Roline\Exceptions\Exceptions;

/**
 * ModelParser - Parse Model Classes for Schema Generation
 *
 * Parses Model class properties with @column annotations to extract
 * database schema definitions and generate SQL statements.
 *
 * Supported Type Annotations:
 *   Numeric: @int, @bigint, @decimal, @float, @double, @tinyint, @smallint, @mediumint
 *   String: @varchar, @char, @text, @mediumtext, @longtext
 *   Date/Time: @datetime, @date, @time, @timestamp, @year
 *   Special: @enum, @set, @boolean, @bool, @json, @autonumber, @uuid
 *   Binary: @blob, @mediumblob, @longblob
 *   Spatial: @point, @geometry, @linestring, @polygon
 *
 * Modifiers:
 *   @primary - Primary key
 *   @unique - Unique constraint
 *   @nullable - Allow NULL (default is NOT NULL)
 *   @unsigned - Unsigned numeric (only for numeric types)
 *   @default value - Default value
 *   @index - Add index on this column
 *   @fulltext - Add FULLTEXT index (for TEXT/VARCHAR columns)
 *   @check expression - CHECK constraint (MySQL 8.0+)
 *   @comment "text" - Column comment
 *   @after column_name - Position after specified column (ALTER TABLE only)
 *   @first - Position as first column in table (ALTER TABLE only)
 *   @drop - Mark for deletion (table:update)
 *   @rename old_name - Rename from old_name (table:update)
 *
 * Foreign Keys:
 *   @foreign table(column) - Create foreign key constraint
 *   @ondelete ACTION - ON DELETE action (CASCADE, RESTRICT, SET NULL, NO ACTION)
 *   @onupdate ACTION - ON UPDATE action (CASCADE, RESTRICT, SET NULL, NO ACTION)
 *
 * Table-Level Annotations (class docblock):
 *   @partition hash(column) count - HASH partitioning (e.g., @partition hash(source) 32)
 *   @partition key(column) count  - KEY partitioning (e.g., @partition key(user_id) 16)
 *   @tablecomment "text"         - Table comment
 *   @compositeindex name(cols)   - Composite index
 *   @compositeunique name(cols)  - Composite unique index
 *
 * Usage:
 *   $parser = new ModelParser();
 *   $schema = $parser->parseModelClass('Models\\User');
 *   // Returns driver-agnostic schema array
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Roline
 * @package Roline\Utils
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

class ModelParser
{
    /**
     * MySQL type mappings for docblock annotations
     */
    private const TYPE_MAPPINGS = [
        // Numeric types
        'int'        => 'INT',
        'integer'    => 'INT',
        'bigint'     => 'BIGINT',
        'tinyint'    => 'TINYINT',
        'smallint'   => 'SMALLINT',
        'mediumint'  => 'MEDIUMINT',
        'decimal'    => 'DECIMAL',
        'float'      => 'FLOAT',
        'double'     => 'DOUBLE',

        // String types
        'varchar'    => 'VARCHAR',
        'char'       => 'CHAR',
        'text'       => 'TEXT',
        'mediumtext' => 'MEDIUMTEXT',
        'longtext'   => 'LONGTEXT',

        // Date/Time types
        'datetime'   => 'DATETIME',
        'date'       => 'DATE',
        'time'       => 'TIME',
        'timestamp'  => 'TIMESTAMP',
        'year'       => 'YEAR',

        // Special types
        'enum'       => 'ENUM',
        'set'        => 'SET',
        'boolean'    => 'TINYINT',
        'bool'       => 'TINYINT',
        'json'       => 'JSON',

        // Binary types
        'blob'       => 'BLOB',
        'mediumblob' => 'MEDIUMBLOB',
        'longblob'   => 'LONGBLOB',

        // Spatial types
        'point'      => 'POINT',
        'geometry'   => 'GEOMETRY',
        'linestring' => 'LINESTRING',
        'polygon'    => 'POLYGON',

        // Convenience types
        'autonumber' => 'INT',
        'uuid'       => 'CHAR',
    ];

    /**
     * Types that support length specification
     */
    private const LENGTH_TYPES = [
        'VARCHAR', 'CHAR', 'INT', 'BIGINT', 'TINYINT',
        'SMALLINT', 'MEDIUMINT', 'DECIMAL'
    ];

    /**
     * Types that support UNSIGNED modifier
     */
    private const UNSIGNED_TYPES = [
        'INT', 'BIGINT', 'TINYINT', 'SMALLINT', 'MEDIUMINT',
        'DECIMAL', 'FLOAT', 'DOUBLE'
    ];

    /**
     * Parse a Model class and extract schema information
     *
     * @param string $modelClass Fully qualified model class name
     * @return array Schema definition
     * @throws \ReflectionException
     */
    public function parseModelClass($modelClass)
    {
        if (!class_exists($modelClass)) {
            throw new \Exception("Model class {$modelClass} not found");
        }

        $reflection = new \ReflectionClass($modelClass);

        // Get table name from static property
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $tableName = $tableProperty->getValue();

        // Get timestamps setting
        $timestampsEnabled = false;
        if ($reflection->hasProperty('timestamps')) {
            $timestampsProperty = $reflection->getProperty('timestamps');
            $timestampsProperty->setAccessible(true);
            $timestampsEnabled = $timestampsProperty->getValue();
        }

        // Parse all properties
        $columns = [];
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);

        foreach ($properties as $property) {
            // Skip static properties (like $table, $timestamps)
            if ($property->isStatic()) {
                continue;
            }

            $docComment = $property->getDocComment();
            if (!$docComment) {
                continue;
            }

            // Check if this property has @column annotation
            if (!$this->hasAnnotation($docComment, 'column')) {
                continue;
            }

            $columnDef = $this->parsePropertyDocblock($property->getName(), $docComment);
            if ($columnDef) {
                $columns[] = $columnDef;
            }
        }

        $schema = [
            'table' => $tableName,
            'columns' => $columns,
            'timestamps' => $timestampsEnabled,
            'class' => $modelClass,
            'composite_indexes' => $this->parseCompositeIndices($reflection),
            'composite_unique_indexes' => $this->parseCompositeUniqueIndices($reflection),
            'simple_indexes' => $this->parseSimpleIndices($columns),
            'table_comment' => $this->parseTableComment($reflection),
            'partition' => $this->parsePartition($reflection),
        ];

        // Validate schema before returning
        $this->validateSchema($schema);

        return $schema;
    }

    /**
     * Validate parsed schema for common issues
     *
     * @param array $schema Parsed schema
     * @return void
     * @throws \Exception If validation fails
     */
    private function validateSchema($schema)
    {
        // Check if table has at least one column
        if (empty($schema['columns'])) {
            throw new \Exception(
                "Model '{$schema['class']}' has no valid @column definitions.\n\n" .
                "What to do:\n" .
                "  1. Add properties with @column annotation\n" .
                "  2. Each @column needs a type like @varchar, @int, @text, etc.\n\n" .
                "Example:\n" .
                "  /**\n" .
                "   * @column\n" .
                "   * @varchar 255\n" .
                "   */\n" .
                "  protected \$username;\n\n" .
                "See application/models/TestUserModel.php for a complete example."
            );
        }

        // Check for primary key
        $hasPrimaryKey = false;
        foreach ($schema['columns'] as $column) {
            if ($column['primary']) {
                $hasPrimaryKey = true;
                break;
            }
        }

        if (!$hasPrimaryKey) {
            throw new Exceptions(
                "Table '{$schema['table']}' has no primary key defined.\n\n" .
                "What to do:\n" .
                "  Add a primary key column (usually 'id') with @primary annotation.\n\n" .
                "Example:\n" .
                "  /**\n" .
                "   * @column\n" .
                "   * @primary\n" .
                "   * @autonumber\n" .
                "   */\n" .
                "  protected \$id;\n\n" .
                "Why: Every table needs a primary key to uniquely identify records.",
                'missing_primary_key',
                true, // Auto-fixable
                'Add primary key property'
            );
        }

        // Check for timestamp columns if timestamps are enabled
        if ($schema['timestamps']) {
            $hasCreatedAt = false;
            $hasUpdatedAt = false;

            foreach ($schema['columns'] as $column) {
                if ($column['name'] === 'created_at') $hasCreatedAt = true;
                if ($column['name'] === 'updated_at') $hasUpdatedAt = true;
            }

            if (!$hasCreatedAt || !$hasUpdatedAt) {
                $missing = [];
                if (!$hasCreatedAt) $missing[] = 'created_at';
                if (!$hasUpdatedAt) $missing[] = 'updated_at';

                throw new Exceptions(
                    "Model has \$timestamps = true but missing: " . implode(', ', $missing) . "\n\n" .
                    "What to do:\n" .
                    "  Either add the timestamp columns OR set \$timestamps = false\n\n" .
                    "Add timestamp columns:\n" .
                    "  /**\n" .
                    "   * Timestamp when the record was first created\n" .
                    "   * @column\n" .
                    "   * @datetime\n" .
                    "   */\n" .
                    "  protected \$created_at;\n\n" .
                    "  /**\n" .
                    "   * Timestamp when the record was last modified\n" .
                    "   * @column\n" .
                    "   * @datetime\n" .
                    "   */\n" .
                    "  protected \$updated_at;\n\n" .
                    "Or disable timestamps:\n" .
                    "  protected static \$timestamps = false;",
                    'missing_timestamps',
                    true, // Auto-fixable
                    'Add timestamp properties'
                );
            }
        }

        // Validate individual columns
        foreach ($schema['columns'] as $column) {
            // Skip dropped columns
            if ($column['drop']) {
                continue;
            }

            // Validate column name
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column['name'])) {
                throw new \Exception(
                    "Invalid column name '\${$column['name']}'.\n\n" .
                    "What to do:\n" .
                    "  Column names must start with a letter or underscore,\n" .
                    "  and contain only letters, numbers, and underscores.\n\n" .
                    "Valid: \$user_name, \$email, \$created_at\n" .
                    "Invalid: \$user-name, \$123start, \$user.name"
                );
            }

            // Validate ENUM has values
            if ($column['type'] === 'ENUM' && empty($column['values'])) {
                throw new \Exception(
                    "Column '\${$column['name']}' is @enum but has no values.\n\n" .
                    "What to do:\n" .
                    "  @enum needs comma-separated values.\n\n" .
                    "Example:\n" .
                    "  /**\n" .
                    "   * @column\n" .
                    "   * @enum active,inactive,banned\n" .
                    "   * @default active\n" .
                    "   */\n" .
                    "  protected \$status;"
                );
            }

            // Validate SET has values
            if ($column['type'] === 'SET' && empty($column['values'])) {
                throw new \Exception(
                    "Column '\${$column['name']}' is @set but has no values.\n\n" .
                    "What to do:\n" .
                    "  @set needs comma-separated values.\n\n" .
                    "Example:\n" .
                    "  /**\n" .
                    "   * @column\n" .
                    "   * @set read,write,delete,admin\n" .
                    "   */\n" .
                    "  protected \$permissions;"
                );
            }

            // Validate DECIMAL length format
            if ($column['type'] === 'DECIMAL' && $column['length']) {
                if (!preg_match('/^\d+,\d+$/', $column['length'])) {
                    throw new \Exception(
                        "Column '\${$column['name']}' has invalid @decimal length '{$column['length']}'.\n\n" .
                        "What to do:\n" .
                        "  @decimal needs precision,scale format.\n\n" .
                        "Example:\n" .
                        "  @decimal 10,2  (10 total digits, 2 after decimal)\n" .
                        "  Good for money: \$price DECIMAL(10,2) can store up to 99999999.99"
                    );
                }
            }

            // Warn about unsigned on non-numeric types
            if ($column['unsigned'] && !in_array($column['type'], self::UNSIGNED_TYPES)) {
                throw new \Exception(
                    "Column '\${$column['name']}' has @unsigned but type '{$column['type']}' cannot be unsigned.\n\n" .
                    "What to do:\n" .
                    "  Remove @unsigned annotation.\n\n" .
                    "Why: Only numeric types (INT, BIGINT, TINYINT, etc.) can be unsigned."
                );
            }
        }
    }

    /**
     * Parse a single property's docblock
     *
     * @param string $propertyName Property name (becomes column name)
     * @param string $docComment Docblock comment
     * @return array|null Column definition or null if not a column
     */
    private function parsePropertyDocblock($propertyName, $docComment)
    {
        $column = [
            'name' => $propertyName,
            'type' => null,
            'length' => null,
            'values' => null,      // For ENUM/SET
            'primary' => false,
            'unique' => false,
            'nullable' => false,
            'unsigned' => false,
            'autoincrement' => false,
            'default' => null,
            'index' => false,
            'fulltext' => false,
            'check' => null,
            'comment' => null,
            'after' => null,
            'first' => false,
            'drop' => false,
            'rename' => null,
        ];

        // Check for @drop flag
        if ($this->hasAnnotation($docComment, 'drop')) {
            $column['drop'] = true;
            return $column;
        }

        // Check for @rename
        $rename = $this->getAnnotationValue($docComment, 'rename');
        if ($rename) {
            $column['rename'] = $rename;
        }

        // Detect type from docblock
        $typeInfo = $this->detectType($docComment);
        if (!$typeInfo) {
            throw new \Exception(
                "Property '\${$propertyName}' has @column but no valid type annotation.\n" .
                "       Add a type like: @varchar 255, @int, @text, @datetime, etc.\n" .
                "       See model.stub for examples of valid types."
            );
        }

        $column['type'] = $typeInfo['type'];
        $column['length'] = $typeInfo['length'];
        $column['values'] = $typeInfo['values'];

        // Validate required fields based on type
        if (in_array($column['type'], ['VARCHAR', 'CHAR']) && !$column['length']) {
            throw new \Exception(
                "Property '\${$propertyName}' has @{$annotation} but no length specified.\n" .
                "       Use: @{$annotation} 255 (or any length)\n" .
                "       Default will be used: @{$annotation} defaults to length 255"
            );
        }

        if ($column['type'] === 'ENUM' && empty($column['values'])) {
            throw new \Exception(
                "Property '\${$propertyName}' has @enum but no values specified.\n" .
                "       Use: @enum value1,value2,value3"
            );
        }

        if ($column['type'] === 'SET' && empty($column['values'])) {
            throw new \Exception(
                "Property '\${$propertyName}' has @set but no values specified.\n" .
                "       Use: @set value1,value2,value3"
            );
        }

        // Special handling for @autonumber
        if ($this->hasAnnotation($docComment, 'autonumber')) {
            $column['type'] = 'INT';
            $column['length'] = 11;
            $column['unsigned'] = true;
            $column['autoincrement'] = true;
            $column['primary'] = true;
            return $column;
        }

        // Special handling for @uuid
        if ($this->hasAnnotation($docComment, 'uuid')) {
            $column['type'] = 'CHAR';
            $column['length'] = 36;
            $column['primary'] = true;
            return $column;
        }

        // Special handling for @boolean/@bool
        if ($this->hasAnnotation($docComment, 'boolean') || $this->hasAnnotation($docComment, 'bool')) {
            $column['type'] = 'TINYINT';
            $column['length'] = 1;
            $column['default'] = $column['default'] ?? 0;
        }

        // Check for modifiers
        if ($this->hasAnnotation($docComment, 'primary')) {
            $column['primary'] = true;
        }

        if ($this->hasAnnotation($docComment, 'unique')) {
            $column['unique'] = true;
        }

        if ($this->hasAnnotation($docComment, 'nullable')) {
            $column['nullable'] = true;
        }

        if ($this->hasAnnotation($docComment, 'unsigned')) {
            if (in_array($column['type'], self::UNSIGNED_TYPES)) {
                $column['unsigned'] = true;
            }
        }

        if ($this->hasAnnotation($docComment, 'index')) {
            $column['index'] = true;
        }

        // Check for fulltext index (TEXT/VARCHAR columns only)
        if ($this->hasAnnotation($docComment, 'fulltext')) {
            $column['fulltext'] = true;
        }

        // Check for CHECK constraint (MySQL 8.0+)
        $check = $this->getAnnotationValue($docComment, 'check');
        if ($check) {
            $column['check'] = $check;
        }

        // Check for column comment
        $comment = $this->getAnnotationValue($docComment, 'comment');
        if ($comment) {
            // Remove surrounding quotes if present
            $column['comment'] = trim($comment, '"\'');
        }

        // Check for column positioning (ALTER TABLE only)
        $after = $this->getAnnotationValue($docComment, 'after');
        if ($after) {
            $column['after'] = $after;
        }

        // Check for @first positioning (column at beginning of table)
        if ($this->hasAnnotation($docComment, 'first')) {
            $column['first'] = true;
        }

        // Parse foreign key constraint
        if ($this->hasAnnotation($docComment, 'foreign')) {
            $foreign = $this->getAnnotationValue($docComment, 'foreign');
            if ($foreign) {
                $column['foreign'] = $foreign; // Format: "table(column)"
            }
        }

        // Parse ON DELETE action
        if ($this->hasAnnotation($docComment, 'ondelete')) {
            $onDelete = strtoupper($this->getAnnotationValue($docComment, 'ondelete'));
            $column['on_delete'] = $onDelete; // CASCADE, SET NULL, RESTRICT, NO ACTION
        }

        // Parse ON UPDATE action
        if ($this->hasAnnotation($docComment, 'onupdate')) {
            $onUpdate = strtoupper($this->getAnnotationValue($docComment, 'onupdate'));
            $column['on_update'] = $onUpdate; // CASCADE, SET NULL, RESTRICT, NO ACTION
        }

        // Get default value
        $default = $this->getAnnotationValue($docComment, 'default');
        if ($default !== null) {
            $column['default'] = $default;
        }

        return $column;
    }

    /**
     * Detect column type from docblock
     *
     * @param string $docComment Docblock comment
     * @return array|null Type info [type, length, values] or null
     */
    private function detectType($docComment)
    {
        // Try each known type
        foreach (self::TYPE_MAPPINGS as $annotation => $sqlType) {
            if ($this->hasAnnotation($docComment, $annotation)) {
                $value = $this->getAnnotationValue($docComment, $annotation);

                // Handle ENUM and SET (have comma-separated values)
                if ($annotation === 'enum' || $annotation === 'set') {
                    $values = array_map('trim', explode(',', $value));
                    return [
                        'type' => strtoupper($annotation),
                        'length' => null,
                        'values' => $values,
                    ];
                }

                // Handle DECIMAL (precision,scale)
                if ($annotation === 'decimal') {
                    return [
                        'type' => 'DECIMAL',
                        'length' => $value ?: '10,2', // Default precision
                        'values' => null,
                    ];
                }

                // Handle types with length
                if (in_array($sqlType, self::LENGTH_TYPES)) {
                    return [
                        'type' => $sqlType,
                        'length' => $value ?: $this->getDefaultLength($sqlType),
                        'values' => null,
                    ];
                }

                // Type without length
                return [
                    'type' => $sqlType,
                    'length' => null,
                    'values' => null,
                ];
            }
        }

        return null;
    }

    /**
     * Get default length for a type
     *
     * @param string $type SQL type
     * @return int|null Default length
     */
    private function getDefaultLength($type)
    {
        $defaults = [
            'VARCHAR' => 255,
            'CHAR' => 255,
            'INT' => 11,
            'BIGINT' => 20,
            'TINYINT' => 4,
            'SMALLINT' => 6,
            'MEDIUMINT' => 9,
        ];

        return $defaults[$type] ?? null;
    }

    /**
     * Check if annotation exists in docblock
     *
     * @param string $docComment Docblock comment
     * @param string $annotation Annotation name (without @)
     * @return bool
     */
    private function hasAnnotation($docComment, $annotation)
    {
        return (bool) preg_match('/@' . preg_quote($annotation, '/') . '\b/', $docComment);
    }

    /**
     * Get annotation value from docblock
     *
     * @param string $docComment Docblock comment
     * @param string $annotation Annotation name (without @)
     * @return string|null Value or null if not found
     */
    private function getAnnotationValue($docComment, $annotation)
    {
        // Match: @annotation value (stops at newline or next @)
        if (preg_match('/@' . preg_quote($annotation, '/') . '\s+([^\s@\*\n][^\n\*]*?)(?=\s*(?:\n|@|\*\/))/', $docComment, $matches)) {
            return trim($matches[1]);
        }

        // For multi-word values on same line (like enum values with commas)
        if (preg_match('/@' . preg_quote($annotation, '/') . '\s+([^\n\*@]+?)(?=\s*(?:\n|@|\*\/))/', $docComment, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Parse class docblock for composite indices
     *
     * Supports two formats:
     *   @composite (col1, col2, col3)                 - Auto-generated name
     *   @composite idx_custom_name (col1, col2, col3) - Custom name
     *
     * Auto-generated names use pattern: idx_{col1}_{col2}_{col3}
     *
     * @param \ReflectionClass $reflection Class reflection
     * @return array Composite indices ['index_name' => ['col1', 'col2', 'col3']]
     */
    private function parseCompositeIndices($reflection)
    {
        $docComment = $reflection->getDocComment();
        if (!$docComment) {
            return [];
        }

        $composites = [];

        // Match: @composite optional_name (col1, col2, col3)
        // Group 1: optional index name (if provided)
        // Group 2: column list inside parentheses
        $pattern = '/@composite\s+(?:(\w+)\s+)?\(([^)]+)\)/';

        if (preg_match_all($pattern, $docComment, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $indexName = trim($match[1]) ?: null;  // null if not provided
                $columnList = trim($match[2]);
                $columns = array_map('trim', explode(',', $columnList));

                // Auto-generate index name if not provided
                if (!$indexName) {
                    $indexName = 'idx_' . implode('_', $columns);
                }

                $composites[$indexName] = $columns;
            }
        }

        return $composites;
    }

    /**
     * Parse class docblock for composite unique indices
     *
     * Supports two formats:
     *   @compositeUnique (col1, col2, col3)                 - Auto-generated name
     *   @compositeUnique unq_custom_name (col1, col2, col3) - Custom name
     *
     * Auto-generated names use pattern: unq_{col1}_{col2}_{col3}
     *
     * @param \ReflectionClass $reflection Class reflection
     * @return array Composite unique indices ['index_name' => ['col1', 'col2', 'col3']]
     */
    private function parseCompositeUniqueIndices($reflection)
    {
        $docComment = $reflection->getDocComment();
        if (!$docComment) {
            return [];
        }

        $composites = [];

        // Match: @compositeUnique optional_name (col1, col2, col3)
        // Group 1: optional index name (if provided)
        // Group 2: column list inside parentheses
        $pattern = '/@compositeUnique\s+(?:(\w+)\s+)?\(([^)]+)\)/';

        if (preg_match_all($pattern, $docComment, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $indexName = trim($match[1]) ?: null;  // null if not provided
                $columnList = trim($match[2]);
                $columns = array_map('trim', explode(',', $columnList));

                // Auto-generate index name if not provided
                if (!$indexName) {
                    $indexName = 'unq_' . implode('_', $columns);
                }

                $composites[$indexName] = $columns;
            }
        }

        return $composites;
    }

    /**
     * Parse class docblock for table comment
     *
     * Extracts table-level comment from class docblock using @tablecomment annotation.
     * Comment is stored in database schema for documentation purposes.
     *
     * Syntax:
     *   @tablecomment "User accounts and authentication data"
     *   @tablecomment 'Product catalog entries'
     *
     * Process:
     *   1. Get class docblock
     *   2. Match @tablecomment followed by quoted or unquoted text
     *   3. Strip surrounding quotes if present
     *   4. Return trimmed comment or null
     *
     * @param \ReflectionClass $reflection Class reflection
     * @return string|null Table comment or null if not defined
     */
    private function parseTableComment($reflection)
    {
        $docComment = $reflection->getDocComment();
        if (!$docComment) {
            return null;
        }

        // Match: @tablecomment "text" or @tablecomment 'text' or @tablecomment text
        if (preg_match('/@tablecomment\s+["\']?([^"\'*\n]+)["\']?/', $docComment, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Parse @index annotations from property docblocks
     *
     * Extracts simple single-column indexes defined with @index annotation.
     *
     * Supports two formats:
     *   @index                    - Auto-generated name (column_name_index)
     *   @index index_custom_name  - Custom index name
     *
     * Also detects @unique which creates a unique index.
     *
     * @param array $columns Parsed column definitions
     * @return array Simple indexes ['index_name' => ['column' => 'col_name', 'unique' => bool]]
     */
    private function parseSimpleIndices($columns)
    {
        $indexes = [];

        // Note: $columns is a numeric array, not keyed by column name
        foreach ($columns as $columnDef) {
            $hasIndex = isset($columnDef['index']) && $columnDef['index'];
            $hasUnique = isset($columnDef['unique']) && $columnDef['unique'];

            // Skip if no @index or @unique annotation
            if (!$hasIndex && !$hasUnique) {
                continue;
            }

            $columnName = $columnDef['name'];

            // @unique creates unique index with _unique suffix
            if ($hasUnique) {
                $indexes[$columnName . '_unique'] = [
                    'column' => $columnName,
                    'unique' => true,
                ];
            }

            // @index creates regular index with _index suffix
            // A column can have both @unique and @index (creates two indexes)
            if ($hasIndex) {
                $indexes[$columnName . '_index'] = [
                    'column' => $columnName,
                    'unique' => false,
                ];
            }
        }

        return $indexes;
    }

    /**
     * Parse @partition annotation from class docblock
     *
     * Extracts table partitioning configuration from class-level annotation.
     * Partitioning splits large tables into smaller physical chunks for
     * improved query performance and faster inserts on massive tables.
     *
     * Supported formats:
     *   @partition hash(column) count    - HASH partitioning
     *   @partition key(column) count     - KEY partitioning
     *   @partition range(column)         - RANGE partitioning (not yet supported)
     *   @partition list(column)          - LIST partitioning (not yet supported)
     *
     * Examples:
     *   @partition hash(source) 32       - PARTITION BY HASH(source) PARTITIONS 32
     *   @partition key(user_id) 16       - PARTITION BY KEY(user_id) PARTITIONS 16
     *
     * Process:
     *   1. Get class docblock
     *   2. Match @partition type(column) count pattern
     *   3. Return partition config array or null
     *
     * Note: Partition column must be part of PRIMARY KEY for HASH/KEY partitioning.
     *
     * @param \ReflectionClass $reflection Reflection of model class
     * @return array|null Partition config ['type' => 'hash', 'column' => 'source', 'count' => 32] or null
     */
    private function parsePartition($reflection)
    {
        $docComment = $reflection->getDocComment();
        if (!$docComment) {
            return null;
        }

        // Match: @partition type(column) count
        // Example: @partition hash(source) 32
        // Group 1: partition type (hash, key, range, list)
        // Group 2: column name (inside parentheses)
        // Group 3: optional partition count (for hash/key)
        if (preg_match('/@partition\s+(hash|key|range|list)\((\w+)\)(?:\s+(\d+))?/i', $docComment, $matches)) {
            $type = strtolower($matches[1]);
            $column = $matches[2];
            $count = isset($matches[3]) ? (int) $matches[3] : null;

            // Validate count for hash/key (required)
            if (in_array($type, ['hash', 'key']) && !$count) {
                throw new \Exception(
                    "Partition count required for {$type} partitioning.\n" .
                    "Usage: @partition {$type}({$column}) 32"
                );
            }

            return [
                'type' => $type,
                'column' => $column,
                'count' => $count,
            ];
        }

        return null;
    }
}
