<?php namespace Roline\Schema;

use Roline\Utils\ModelParser;
use Rackage\Database\Database;
use Rackage\Registry;

/**
 * MySQLSchema - MySQL Schema Generation and Management
 *
 * Generates MySQL-specific CREATE TABLE and ALTER TABLE statements
 * from driver-agnostic schema definitions.
 *
 * Usage:
 *   $schema = new MySQLSchema();
 *   $schema->createTableFromModel('Models\\User');
 *   $schema->updateTableFromModel('Models\\Post');
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Roline
 * @package Roline\Schema
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

class MySQLSchema
{
    /**
     * Model parser instance
     * @var ModelParser
     */
    private $parser;

    /**
     * Database connection
     * @var object
     */
    private $connection;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->parser = new ModelParser();
    }

    /**
     * Create table from Model class
     *
     * @param string $modelClass Fully qualified model class name (e.g., 'Models\\User')
     * @return bool True on success
     * @throws \Exception
     */
    public function createTableFromModel($modelClass)
    {
        // Parse model class
        $schema = $this->parser->parseModelClass($modelClass);

        // Validate schema has columns
        if (empty($schema['columns'])) {
            throw new \Exception(
                "Model has no valid @column definitions.\n" .
                "       Add properties with @column and type annotations.\n" .
                "       Example:\n" .
                "       /**\n" .
                "        * @column\n" .
                "        * @varchar 255\n" .
                "        */\n" .
                "       protected \$username;"
            );
        }

        // Validate foreign key constraints before creating table
        $this->validateForeignKeys($schema);

        // Validate partition configuration
        $this->validatePartition($schema);

        // Generate CREATE TABLE SQL
        $sql = $this->generateCreateTableSQL($schema);

        // Get database connection
        $this->ensureConnection();

        // Drop existing table first
        $dropSQL = "DROP TABLE IF EXISTS `{$schema['table']}`;";
        $this->connection->execute($dropSQL);

        // Create table
        $result = $this->connection->execute($sql);

        if (!$result) {
            $error = $this->connection->lastError();
            throw new \Exception(
                "Failed to execute SQL.\n\n" .
                "SQL:\n{$sql}\n\n" .
                "Error: {$error}"
            );
        }

        return true;
    }

    /**
     * Update table from Model class
     *
     * @param string $modelClass Fully qualified model class name
     * @return bool True on success
     * @throws \Exception
     */
    public function updateTableFromModel($modelClass, $confirmationCallback = null)
    {
        // Parse model class
        $schema = $this->parser->parseModelClass($modelClass);

        // Get existing table columns
        $existingColumns = $this->getExistingColumns($schema['table']);

        // Generate ALTER TABLE statements with drop and rename info
        $result = $this->generateAlterTableSQL($schema, $existingColumns);
        $statements = $result['statements'];
        $dropColumns = $result['drop_columns'];
        $renameColumns = $result['rename_columns'];

        if (empty($statements)) {
            echo "\n";
            echo "✓ No changes needed - table is up to date!\n";
            return true; // No changes needed
        }

        // Preview changes before executing
        echo "\n";
        echo "Planned changes:\n";
        foreach ($statements as $i => $stmt) {
            echo "  " . ($i + 1) . ". " . substr($stmt, 0, 100);
            if (strlen($stmt) > 100) {
                echo "...";
            }
            echo "\n";
        }
        echo "\n";

        // Check for slow operations and warn user
        $rowCount = $this->getRowCountEstimate($schema['table']);
        $hasSlowOps = false;

        foreach ($statements as $stmt) {
            // Warn about adding indexes on large tables
            if (stripos($stmt, 'ADD INDEX') !== false && $rowCount > 100000) {
                $hasSlowOps = true;
                $estimatedSeconds = ceil($rowCount / 50000); // ~50K rows per second
                $estimatedMinutes = ceil($estimatedSeconds / 60);

                echo "⚠ WARNING: Adding index to large table (" . number_format($rowCount) . " rows)\n";
                echo "           This may take " . $estimatedMinutes . "-" . ($estimatedMinutes * 3) . " minutes.\n";
                echo "           DO NOT INTERRUPT - let it complete!\n\n";
                break; // Only show warning once
            }

            // Warn about modifying columns on large tables
            if (stripos($stmt, 'MODIFY COLUMN') !== false && $rowCount > 100000) {
                $hasSlowOps = true;
                echo "⚠ WARNING: Modifying column on large table (" . number_format($rowCount) . " rows)\n";
                echo "           This may take several minutes.\n\n";
                break; // Only show warning once
            }

            // Warn about dropping indexes on large tables (can require table rebuild)
            if (stripos($stmt, 'DROP INDEX') !== false && $rowCount > 100000) {
                $hasSlowOps = true;
                $estimatedSeconds = ceil($rowCount / 50000); // ~50K rows per second
                $estimatedMinutes = ceil($estimatedSeconds / 60);

                echo "⚠ WARNING: Dropping index on large table (" . number_format($rowCount) . " rows)\n";
                echo "           This may take " . $estimatedMinutes . "-" . ($estimatedMinutes * 3) . " minutes.\n";
                echo "           DO NOT INTERRUPT - let it complete!\n\n";
                break; // Only show warning once
            }

            // Warn about partition changes on large tables
            if (stripos($stmt, 'PARTITION BY') !== false && $rowCount > 100000) {
                $hasSlowOps = true;
                $tableSize = $this->getTableSize($schema['table']);
                $tableSizeFormatted = $this->formatBytes($tableSize);
                $estimatedMinutes = ceil($rowCount / 500000); // ~500K rows per minute for partitioning

                echo "⚠ WARNING: Adding partitioning to large table (" . number_format($rowCount) . " rows)\n";
                echo "           Current table size: {$tableSizeFormatted}\n";
                echo "           Temporary space required: ~{$tableSizeFormatted} (rebuilds table)\n";
                echo "           Estimated time: {$estimatedMinutes}-" . ($estimatedMinutes * 2) . " minutes.\n";
                echo "           DO NOT INTERRUPT - let it complete!\n\n";
                break;
            }

            // Warn about removing partitioning
            if (stripos($stmt, 'REMOVE PARTITIONING') !== false && $rowCount > 100000) {
                $hasSlowOps = true;
                $tableSize = $this->getTableSize($schema['table']);
                $tableSizeFormatted = $this->formatBytes($tableSize);

                echo "⚠ WARNING: Removing partitioning from large table (" . number_format($rowCount) . " rows)\n";
                echo "           This requires a full table rebuild.\n";
                echo "           Temporary space required: ~{$tableSizeFormatted}\n";
                echo "           DO NOT INTERRUPT - let it complete!\n\n";
                break;
            }
        }

        // If there are columns to drop or rename, ask for confirmation
        if ((!empty($dropColumns) || !empty($renameColumns)) && is_callable($confirmationCallback)) {
            $confirmed = $confirmationCallback($dropColumns, $renameColumns);
            if (!$confirmed) {
                // User said no - abort all changes
                echo "\n";
                echo "✗ Aborted - no changes made.\n";
                return false; // Return false to indicate abort
            }
        }

        // Get database connection
        $this->ensureConnection();

        // Execute each ALTER statement with progress display
        $total = count($statements);
        echo "Executing " . $total . " statement" . ($total > 1 ? 's' : '') . "...\n\n";

        foreach ($statements as $i => $sql) {
            $num = $i + 1;

            // Show what's being executed
            echo "[$num/$total] " . substr($sql, 0, 80);
            if (strlen($sql) > 80) {
                echo "...";
            }
            echo "\n";

            // Execute and time it
            $start = microtime(true);
            $result = $this->connection->execute($sql);
            $elapsed = microtime(true) - $start;

            if (!$result) {
                throw new \Exception("Failed to update table '{$schema['table']}': " . $this->connection->lastError());
            }

            // Show completion time
            if ($elapsed > 1) {
                echo "        ✓ Completed in " . round($elapsed, 2) . "s";
                if ($elapsed > 60) {
                    echo " (" . round($elapsed / 60, 1) . " min)";
                }
                echo "\n";
            } else {
                echo "        ✓ Completed in " . round($elapsed * 1000, 0) . "ms\n";
            }

            echo "\n";
        }

        return true;
    }

    /**
     * Generate CREATE TABLE SQL statement
     *
     * @param array $schema Schema definition from ModelParser
     * @return string CREATE TABLE SQL
     */
    public function generateCreateTableSQL($schema)
    {
        $tableName = $schema['table'];
        $columns = $schema['columns'];

        $columnDefinitions = [];
        $primaryKeys = [];
        $uniqueKeys = [];
        $indexes = [];
        $fulltextIndexes = [];
        $foreignKeys = [];

        foreach ($columns as $column) {
            // Skip dropped columns
            if ($column['drop']) {
                continue;
            }

            $def = "`{$column['name']}` ";

            // Type and length
            if ($column['values']) {
                // ENUM or SET type
                $values = array_map(function($v) {
                    return "'" . addslashes($v) . "'";
                }, $column['values']);
                $def .= "{$column['type']}(" . implode(', ', $values) . ")";
            } elseif ($column['length']) {
                $def .= "{$column['type']}({$column['length']})";
            } else {
                $def .= $column['type'];
            }

            // UNSIGNED
            if ($column['unsigned']) {
                $def .= " UNSIGNED";
            }

            // NULL/NOT NULL
            $def .= $column['nullable'] ? " NULL" : " NOT NULL";

            // AUTO_INCREMENT
            if ($column['autoincrement']) {
                $def .= " AUTO_INCREMENT";
            }

            // DEFAULT
            if ($column['default'] !== null) {
                if (in_array(strtoupper($column['default']), ['CURRENT_TIMESTAMP', 'NULL'])) {
                    $def .= " DEFAULT {$column['default']}";
                }
                else {
                    $def .= " DEFAULT '" . addslashes($column['default']) . "'";
                }
            }

            // COMMENT
            if (!empty($column['comment'])) {
                $def .= " COMMENT '" . addslashes($column['comment']) . "'";
            }

            // CHECK constraint (MySQL 8.0+)
            if (!empty($column['check'])) {
                $def .= " CHECK (" . $column['check'] . ")";
            }

            $columnDefinitions[] = $def;

            // Track keys and indexes
            if ($column['primary']) {
                $primaryKeys[] = $column['name'];
            }
            if ($column['unique']) {
                $uniqueKeys[] = $column['name'];
            }
            if ($column['index']) {
                $indexes[] = $column['name'];
            }
            if (!empty($column['fulltext'])) {
                $fulltextIndexes[] = $column['name'];
            }

            // Track foreign keys
            if (!empty($column['foreign'])) {
                $foreignKeys[] = [
                    'column' => $column['name'],
                    'references' => $column['foreign'],
                    'on_delete' => $column['on_delete'] ?? 'RESTRICT',
                    'on_update' => $column['on_update'] ?? 'RESTRICT',
                ];
            }
        }

        // Add primary key
        if (!empty($primaryKeys)) {
            $columnDefinitions[] = "PRIMARY KEY (`" . implode('`, `', $primaryKeys) . "`)";
        }

        // Add unique keys
        foreach ($uniqueKeys as $key) {
            $columnDefinitions[] = "UNIQUE KEY `{$key}_unique` (`{$key}`)";
        }

        // Add indexes
        foreach ($indexes as $key) {
            $columnDefinitions[] = "KEY `{$key}_index` (`{$key}`)";
        }

        // Add composite indexes
        if (!empty($schema['composite_indexes'])) {
            foreach ($schema['composite_indexes'] as $indexName => $columns) {
                $columnList = '`' . implode('`, `', $columns) . '`';
                $columnDefinitions[] = "KEY `{$indexName}` ({$columnList})";
            }
        }

        // Add composite unique indexes
        if (!empty($schema['composite_unique_indexes'])) {
            foreach ($schema['composite_unique_indexes'] as $indexName => $columns) {
                $columnList = '`' . implode('`, `', $columns) . '`';
                $columnDefinitions[] = "UNIQUE KEY `{$indexName}` ({$columnList})";
            }
        }

        // Add fulltext indexes
        foreach ($fulltextIndexes as $column) {
            $columnDefinitions[] = "FULLTEXT KEY `{$column}_fulltext` (`{$column}`)";
        }

        // Add foreign keys
        foreach ($foreignKeys as $fk) {
            // Parse foreign key reference: "table(column)"
            if (preg_match('/^(\w+)\((\w+)\)$/', $fk['references'], $matches)) {
                $refTable = $matches[1];
                $refColumn = $matches[2];

                $constraintName = "fk_{$tableName}_{$fk['column']}";
                $fkDef = "CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$fk['column']}`) ";
                $fkDef .= "REFERENCES `{$refTable}` (`{$refColumn}`)";

                if (!empty($fk['on_delete'])) {
                    $fkDef .= " ON DELETE {$fk['on_delete']}";
                }

                if (!empty($fk['on_update'])) {
                    $fkDef .= " ON UPDATE {$fk['on_update']}";
                }

                $columnDefinitions[] = $fkDef;
            }
        }

        $sql = "CREATE TABLE `{$tableName}` (\n  ";
        $sql .= implode(",\n  ", $columnDefinitions);
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        // Add table comment if defined
        if (!empty($schema['table_comment'])) {
            $sql .= " COMMENT='" . addslashes($schema['table_comment']) . "'";
        }

        // Add partitioning if defined
        if (!empty($schema['partition'])) {
            $sql .= $this->generatePartitionClause($schema['partition']);
        }

        $sql .= ";";

        return $sql;
    }

    /**
     * Generate ALTER TABLE SQL statements
     *
     * @param array $schema New schema definition
     * @param array $existingColumns Existing columns from database
     * @return array Array with 'statements' and 'drop_columns' keys
     */
    public function generateAlterTableSQL($schema, $existingColumns)
    {
        $tableName = $schema['table'];
        $statements = [];
        $dropColumns = [];
        $renameColumns = [];

        $newColumns = [];
        foreach ($schema['columns'] as $column) {
            $newColumns[$column['name']] = $column;
        }

        // Handle drops - columns with @drop annotation
        foreach ($schema['columns'] as $column) {
            if ($column['drop']) {
                $dropColumns[] = [
                    'name' => $column['name'],
                    'reason' => '@drop annotation'
                ];
                $statements[] = "ALTER TABLE `{$tableName}` DROP COLUMN `{$column['name']}`;";
            }
        }

        // Handle orphaned columns - exist in DB but not in model
        foreach ($existingColumns as $columnName => $columnInfo) {
            if (!isset($newColumns[$columnName])) {
                // Check if already marked for drop via @drop annotation
                $alreadyMarked = false;
                foreach ($dropColumns as $dc) {
                    if ($dc['name'] === $columnName) {
                        $alreadyMarked = true;
                        break;
                    }
                }

                // Check if this column is being renamed (not orphaned)
                $isBeingRenamed = false;
                foreach ($schema['columns'] as $column) {
                    if (!empty($column['rename']) && $column['rename'] === $columnName) {
                        $isBeingRenamed = true;
                        break;
                    }
                }

                if (!$alreadyMarked && !$isBeingRenamed) {
                    $dropColumns[] = [
                        'name' => $columnName,
                        'reason' => 'not in model (orphaned)'
                    ];
                    $statements[] = "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`;";
                }
            }
        }

        // Handle renames
        foreach ($schema['columns'] as $column) {
            if ($column['rename']) {
                $oldName = $column['rename'];
                $newName = $column['name'];
                $renameColumns[] = [
                    'old_name' => $oldName,
                    'new_name' => $newName
                ];
                $columnDef = $this->getColumnDefinition($column);
                $statements[] = "ALTER TABLE `{$tableName}` CHANGE `{$oldName}` `{$newName}` {$columnDef};";
            }
        }

        // Handle new columns and modifications
        foreach ($newColumns as $name => $column) {
            if ($column['drop'] || $column['rename']) {
                continue;
            }

            if (!isset($existingColumns[$name])) {
                // New column
                $columnDef = $this->getColumnDefinition($column);
                $positionClause = $this->getPositionClause($column);
                $statements[] = "ALTER TABLE `{$tableName}` ADD COLUMN `{$name}` {$columnDef}{$positionClause};";

                // Add fulltext index if specified
                if (!empty($column['fulltext'])) {
                    $statements[] = "ALTER TABLE `{$tableName}` ADD FULLTEXT INDEX `{$name}_fulltext` (`{$name}`);";
                }
            }
            else {
                // Column exists - check if ACTUALLY modified (smart diffing)
                if ($this->columnDefinitionChanged($existingColumns[$name], $column)) {
                    $columnDef = $this->getColumnDefinition($column);
                    $positionClause = $this->getPositionClause($column);
                    $statements[] = "ALTER TABLE `{$tableName}` MODIFY COLUMN `{$name}` {$columnDef}{$positionClause};";
                }
                // Otherwise skip - no change needed!
            }
        }

        // Handle foreign keys
        $existingForeignKeys = $this->getExistingForeignKeys($tableName);

        // Collect model foreign keys
        $modelForeignKeys = [];
        foreach ($schema['columns'] as $column) {
            if (!empty($column['foreign']) && !$column['drop']) {
                $constraintName = "fk_{$tableName}_{$column['name']}";
                $modelForeignKeys[$constraintName] = [
                    'column' => $column['name'],
                    'references' => $column['foreign'],
                    'on_delete' => $column['on_delete'] ?? 'RESTRICT',
                    'on_update' => $column['on_update'] ?? 'RESTRICT',
                ];
            }
        }

        // Drop foreign keys that exist in DB but not in model, OR have changed
        foreach ($existingForeignKeys as $constraintName => $fkInfo) {
            $shouldDrop = false;

            if (!isset($modelForeignKeys[$constraintName])) {
                // FK exists in DB but not in model - drop it
                $shouldDrop = true;
            } elseif ($fkInfo['references'] !== $modelForeignKeys[$constraintName]['references']) {
                // FK exists in both but references changed - drop and will re-add below
                $shouldDrop = true;
            }

            if ($shouldDrop) {
                $statements[] = "ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`;";
            }
        }

        // Add foreign keys that exist in model but not in DB, OR have changed
        foreach ($modelForeignKeys as $constraintName => $fk) {
            $shouldAdd = false;

            if (!isset($existingForeignKeys[$constraintName])) {
                // FK exists in model but not in DB - add it
                $shouldAdd = true;
            } elseif ($existingForeignKeys[$constraintName]['references'] !== $fk['references']) {
                // FK exists in both but references changed - re-add it (already dropped above)
                $shouldAdd = true;
            }

            if ($shouldAdd) {
                // Parse foreign key reference: "table(column)"
                if (preg_match('/^(\w+)\((\w+)\)$/', $fk['references'], $matches)) {
                    $refTable = $matches[1];
                    $refColumn = $matches[2];

                    $fkDef = "ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$constraintName}` ";
                    $fkDef .= "FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$refTable}` (`{$refColumn}`)";

                    if (!empty($fk['on_delete'])) {
                        $fkDef .= " ON DELETE {$fk['on_delete']}";
                    }

                    if (!empty($fk['on_update'])) {
                        $fkDef .= " ON UPDATE {$fk['on_update']}";
                    }

                    $statements[] = $fkDef . ";";
                }
            }
        }

        // Handle composite indexes
        $existingCompositeIndexes = $this->getExistingCompositeIndexes($tableName);
        $modelCompositeIndexes = $schema['composite_indexes'] ?? [];

        // Drop composite indexes that exist in DB but not in model, or have changed
        foreach ($existingCompositeIndexes as $indexName => $indexInfo) {
            $shouldDrop = false;

            if (!isset($modelCompositeIndexes[$indexName])) {
                // Index exists in DB but not in model - drop it
                $shouldDrop = true;
            } elseif ($indexInfo['columns'] !== $modelCompositeIndexes[$indexName]) {
                // Index exists but columns changed - drop and re-add
                $shouldDrop = true;
            }

            if ($shouldDrop) {
                $statements[] = "ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`;";
            }
        }

        // Add composite indexes that exist in model but not in DB, or have changed
        foreach ($modelCompositeIndexes as $indexName => $columns) {
            $shouldAdd = false;

            if (!isset($existingCompositeIndexes[$indexName])) {
                // Index exists in model but not in DB - add it
                $shouldAdd = true;
            } elseif ($existingCompositeIndexes[$indexName]['columns'] !== $columns) {
                // Index exists but columns changed - re-add it (already dropped above)
                $shouldAdd = true;
            }

            if ($shouldAdd) {
                $columnList = '`' . implode('`, `', $columns) . '`';
                $statements[] = "ALTER TABLE `{$tableName}` ADD INDEX `{$indexName}` ({$columnList});";
            }
        }

        // Handle composite unique indexes
        $existingCompositeUniqueIndexes = $this->getExistingCompositeUniqueIndexes($tableName);
        $modelCompositeUniqueIndexes = $schema['composite_unique_indexes'] ?? [];

        // Drop composite unique indexes that exist in DB but not in model, or have changed
        foreach ($existingCompositeUniqueIndexes as $indexName => $indexInfo) {
            $shouldDrop = false;

            if (!isset($modelCompositeUniqueIndexes[$indexName])) {
                // Index exists in DB but not in model - drop it
                $shouldDrop = true;
            } elseif ($indexInfo['columns'] !== $modelCompositeUniqueIndexes[$indexName]) {
                // Index exists but columns changed - drop and re-add
                $shouldDrop = true;
            }

            if ($shouldDrop) {
                $statements[] = "ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`;";
            }
        }

        // Add composite unique indexes that exist in model but not in DB, or have changed
        foreach ($modelCompositeUniqueIndexes as $indexName => $columns) {
            $shouldAdd = false;

            if (!isset($existingCompositeUniqueIndexes[$indexName])) {
                // Index exists in model but not in DB - add it
                $shouldAdd = true;
            } elseif ($existingCompositeUniqueIndexes[$indexName]['columns'] !== $columns) {
                // Index exists but columns changed - re-add it (already dropped above)
                $shouldAdd = true;
            }

            if ($shouldAdd) {
                $columnList = '`' . implode('`, `', $columns) . '`';
                $statements[] = "ALTER TABLE `{$tableName}` ADD UNIQUE INDEX `{$indexName}` ({$columnList});";
            }
        }

        // Handle simple (single-column) indexes
        $existingSimpleIndexes = $this->getExistingSimpleIndexes($tableName);
        $modelSimpleIndexes = $schema['simple_indexes'] ?? [];

        // Drop simple indexes that exist in DB but not in model, or have changed
        foreach ($existingSimpleIndexes as $indexName => $indexInfo) {
            $shouldDrop = false;

            if (!isset($modelSimpleIndexes[$indexName])) {
                // Index exists in DB but not in model - drop it
                $shouldDrop = true;
            } elseif ($indexInfo['column'] !== $modelSimpleIndexes[$indexName]['column']
                   || $indexInfo['unique'] !== $modelSimpleIndexes[$indexName]['unique']) {
                // Index exists but definition changed - drop and re-add
                $shouldDrop = true;
            }

            if ($shouldDrop) {
                $statements[] = "ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`;";
            }
        }

        // Add simple indexes that exist in model but not in DB, or have changed
        foreach ($modelSimpleIndexes as $indexName => $indexDef) {
            $shouldAdd = false;

            if (!isset($existingSimpleIndexes[$indexName])) {
                // Index exists in model but not in DB - add it
                $shouldAdd = true;
            } elseif ($existingSimpleIndexes[$indexName]['column'] !== $indexDef['column']
                   || $existingSimpleIndexes[$indexName]['unique'] !== $indexDef['unique']) {
                // Index exists but definition changed - re-add it (already dropped above)
                $shouldAdd = true;
            }

            if ($shouldAdd) {
                $column = '`' . $indexDef['column'] . '`';
                if ($indexDef['unique']) {
                    $statements[] = "ALTER TABLE `{$tableName}` ADD UNIQUE INDEX `{$indexName}` ({$column});";
                }
                else {
                    $statements[] = "ALTER TABLE `{$tableName}` ADD INDEX `{$indexName}` ({$column});";
                }
            }
        }

        // Handle fulltext indexes
        $existingFulltextIndexes = $this->getExistingFulltextIndexes($tableName);
        $modelFulltextIndexes = [];

        // Collect fulltext indexes from model columns
        foreach ($schema['columns'] as $column) {
            if (!empty($column['fulltext']) && !$column['drop']) {
                $indexName = $column['name'] . '_fulltext';
                $modelFulltextIndexes[$indexName] = $column['name'];
            }
        }

        // Drop fulltext indexes that exist in DB but not in model
        foreach ($existingFulltextIndexes as $indexName => $columnName) {
            if (!isset($modelFulltextIndexes[$indexName])) {
                $statements[] = "ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`;";
            }
        }

        // Add fulltext indexes that exist in model but not in DB
        foreach ($modelFulltextIndexes as $indexName => $columnName) {
            if (!isset($existingFulltextIndexes[$indexName])) {
                $statements[] = "ALTER TABLE `{$tableName}` ADD FULLTEXT INDEX `{$indexName}` (`{$columnName}`);";
            }
        }

        // Handle partitioning changes
        $existingPartition = $this->getExistingPartition($tableName);
        $modelPartition = $schema['partition'] ?? null;

        // Compare partition configurations
        $partitionChanged = false;
        if ($modelPartition && !$existingPartition) {
            // Adding partitioning to non-partitioned table
            $partitionChanged = true;
        } elseif (!$modelPartition && $existingPartition) {
            // Removing partitioning - generate REMOVE PARTITIONING
            $statements[] = "ALTER TABLE `{$tableName}` REMOVE PARTITIONING;";
        } elseif ($modelPartition && $existingPartition) {
            // Both have partitioning - check if different
            if ($modelPartition['type'] !== strtolower($existingPartition['type']) ||
                $modelPartition['column'] !== $existingPartition['column'] ||
                $modelPartition['count'] !== $existingPartition['count']) {
                $partitionChanged = true;
            }
        }

        if ($partitionChanged && $modelPartition) {
            // Validate partition before generating ALTER
            $this->validatePartition($schema);

            // Generate partition clause
            $partitionClause = $this->generatePartitionClause($modelPartition);
            $statements[] = "ALTER TABLE `{$tableName}` {$partitionClause};";
        }

        return [
            'statements' => $statements,
            'drop_columns' => $dropColumns,
            'rename_columns' => $renameColumns
        ];
    }

    /**
     * Check if column definition has changed between DB and model
     *
     * Compares existing database column with model annotation to determine
     * if a MODIFY COLUMN statement is needed. Normalizes both definitions
     * for accurate comparison.
     *
     * @param array $dbColumn Column info from SHOW COLUMNS (database)
     * @param array $modelColumn Column definition from model annotations
     * @return bool True if column needs to be modified
     */
    private function columnDefinitionChanged($dbColumn, $modelColumn)
    {
        // Build what the column definition SHOULD be from model
        $newDef = $this->getColumnDefinition($modelColumn);

        // Build what the column definition CURRENTLY is from database
        $currentType = strtoupper($dbColumn['Type']);
        $currentNull = $dbColumn['Null'] === 'YES' ? ' NULL' : ' NOT NULL';
        $currentDefault = '';
        $currentExtra = '';
        $currentComment = '';

        // Handle default values
        if ($dbColumn['Default'] !== null) {
            if (in_array(strtoupper($dbColumn['Default']), ['CURRENT_TIMESTAMP', 'NULL'])) {
                $currentDefault = " DEFAULT {$dbColumn['Default']}";
            }
            else {
                $currentDefault = " DEFAULT '" . addslashes($dbColumn['Default']) . "'";
            }
        }

        // Handle extra (AUTO_INCREMENT, etc)
        if (!empty($dbColumn['Extra'])) {
            $currentExtra = ' ' . strtoupper($dbColumn['Extra']);
        }

        // Handle comment (from SHOW FULL COLUMNS)
        if (!empty($dbColumn['Comment'])) {
            $currentComment = " COMMENT '" . addslashes($dbColumn['Comment']) . "'";
        }

        $currentDef = $currentType . $currentNull . $currentDefault . $currentExtra . $currentComment;

        // Normalize both for comparison (remove extra whitespace, make uppercase)
        $currentDef = preg_replace('/\s+/', ' ', strtoupper(trim($currentDef)));
        $newDef = preg_replace('/\s+/', ' ', strtoupper(trim($newDef)));

        // Remove spaces after commas in ENUM/SET (MySQL doesn't include them)
        $currentDef = preg_replace('/,\s+/', ',', $currentDef);
        $newDef = preg_replace('/,\s+/', ',', $newDef);

        // JSON type: MySQL reports just "JSON" but model generates "JSON NULL"
        // Only compare NULL-ability, skip if both are JSON with same NULL-ability
        if ($modelColumn['type'] === 'JSON') {
            $currentIsJson = strpos($currentType, 'JSON') === 0;
            if ($currentIsJson) {
                // Both are JSON - only check if NULL-ability changed
                $currentIsNullable = $dbColumn['Null'] === 'YES';
                $newIsNullable = $modelColumn['nullable'];

                // Also check comment change for JSON columns
                $currentCommentVal = $dbColumn['Comment'] ?? '';
                $newCommentVal = $modelColumn['comment'] ?? '';

                return $currentIsNullable !== $newIsNullable
                    || $currentCommentVal !== $newCommentVal;
            }
            // DB is not JSON but model is - needs modification
        }

        // Compare - if different, modification is needed
        return $currentDef !== $newDef;
    }

    /**
     * Get column definition string for ALTER statements
     *
     * @param array $column Column definition
     * @return string Column definition SQL
     */
    private function getColumnDefinition($column)
    {
        $def = "";

        // Type and length
        if ($column['values']) {
            $values = array_map(function($v) {
                return "'" . addslashes($v) . "'";
            }, $column['values']);
            $def .= "{$column['type']}(" . implode(', ', $values) . ")";
        } elseif ($column['length']) {
            $def .= "{$column['type']}({$column['length']})";
        } else {
            $def .= $column['type'];
        }

        // UNSIGNED
        if ($column['unsigned']) {
            $def .= " UNSIGNED";
        }

        // NULL/NOT NULL
        $def .= $column['nullable'] ? " NULL" : " NOT NULL";

        // AUTO_INCREMENT
        if ($column['autoincrement']) {
            $def .= " AUTO_INCREMENT";
        }

        // DEFAULT
        if ($column['default'] !== null) {
            if (in_array(strtoupper($column['default']), ['CURRENT_TIMESTAMP', 'NULL'])) {
                $def .= " DEFAULT {$column['default']}";
            }
            else {
                $def .= " DEFAULT '" . addslashes($column['default']) . "'";
            }
        }

        // COMMENT
        if (!empty($column['comment'])) {
            $def .= " COMMENT '" . addslashes($column['comment']) . "'";
        }

        // CHECK constraint (MySQL 8.0+)
        if (!empty($column['check'])) {
            $def .= " CHECK (" . $column['check'] . ")";
        }

        return $def;
    }

    /**
     * Get column position clause (FIRST or AFTER)
     *
     * @param array $column Column definition
     * @return string Position clause (e.g., " FIRST" or " AFTER `col`") or empty string
     */
    private function getPositionClause($column)
    {
        if (!empty($column['first'])) {
            return " FIRST";
        }

        if (!empty($column['after'])) {
            return " AFTER `{$column['after']}`";
        }

        return "";
    }

    /**
     * Get existing columns from database table
     *
     * @param string $tableName Table name
     * @return array Associative array of column names => column info
     */
    private function getExistingColumns($tableName)
    {
        $this->ensureConnection();

        // Use SHOW FULL COLUMNS to include Comment field
        $sql = "SHOW FULL COLUMNS FROM `{$tableName}`";
        $result = $this->connection->execute($sql);

        if (!$result) {
            // Table doesn't exist yet
            return [];
        }

        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = $row;
        }

        return $columns;
    }

    /**
     * Get existing foreign keys from database table
     *
     * @param string $tableName Table name
     * @return array Associative array of constraint names => FK info
     */
    private function getExistingForeignKeys($tableName)
    {
        $this->ensureConnection();

        $sql = "SELECT
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = '{$tableName}'
                    AND REFERENCED_TABLE_NAME IS NOT NULL";

        $result = $this->connection->execute($sql);

        if (!$result) {
            return [];
        }

        $foreignKeys = [];
        while ($row = $result->fetch_assoc()) {
            $foreignKeys[$row['CONSTRAINT_NAME']] = [
                'column' => $row['COLUMN_NAME'],
                'references' => $row['REFERENCED_TABLE_NAME'] . '(' . $row['REFERENCED_COLUMN_NAME'] . ')'
            ];
        }

        return $foreignKeys;
    }

    /**
     * Ensure database connection is established
     *
     * @return void
     */
    private function ensureConnection()
    {
        if ($this->connection) {
            return;
        }

        // Get database connection from Registry (already initialized and connected)
        try {
            $this->connection = Registry::get('database-sync');
        } catch (\Exception $e) {
            throw new \Exception("Failed to get database connection: " . $e->getMessage());
        }

        if (!$this->connection) {
            throw new \Exception("Database connection not available");
        }
    }

    /**
     * Get table schema as array
     *
     * @param string $tableName Table name
     * @return array Table schema information
     */
    public function getTableSchema($tableName)
    {
        $this->ensureConnection();

        $columns = $this->getExistingColumns($tableName);

        return [
            'table' => $tableName,
            'columns' => $columns,
            'exists' => !empty($columns)
        ];
    }

    /**
     * Check if table exists
     *
     * @param string $tableName Table name
     * @return bool True if table exists
     */
    public function tableExists($tableName)
    {
        $this->ensureConnection();

        $sql = "SHOW TABLES LIKE '{$tableName}'";
        $result = $this->connection->execute($sql);

        return $result && $result->num_rows > 0;
    }

    /**
     * Drop table
     *
     * @param string $tableName Table name
     * @return bool True on success
     */
    public function dropTable($tableName)
    {
        $this->ensureConnection();

        $sql = "DROP TABLE IF EXISTS `{$tableName}`;";
        return $this->connection->execute($sql);
    }

    /**
     * Rename a table
     *
     * @param string $oldName Current table name
     * @param string $newName New table name
     * @return mixed Query result
     */
    public function renameTable($oldName, $newName)
    {
        $this->ensureConnection();

        $sql = "RENAME TABLE `{$oldName}` TO `{$newName}`;";
        return $this->connection->execute($sql);
    }

    /**
     * Get table columns (for TableSchema command)
     *
     * @param string $tableName Table name
     * @return array Array of column information
     */
    public function getTableColumns($tableName)
    {
        $this->ensureConnection();

        $sql = "SHOW COLUMNS FROM `{$tableName}`";
        $result = $this->connection->execute($sql);

        if (!$result) {
            return [];
        }

        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row;
        }

        return $columns;
    }

    /**
     * Get table indexes (for TableSchema command)
     *
     * @param string $tableName Table name
     * @return array Array of index information
     */
    public function getTableIndexes($tableName)
    {
        $this->ensureConnection();

        $sql = "SHOW INDEX FROM `{$tableName}`";
        $result = $this->connection->execute($sql);

        if (!$result) {
            return [];
        }

        $indexes = [];
        while ($row = $result->fetch_assoc()) {
            $indexes[] = $row;
        }

        return $indexes;
    }

    /**
     * Get the number of rows in a table
     *
     * @param string $tableName Name of table to count rows in
     * @return int Number of rows in table
     */
    public function getRowCount($tableName)
    {
        $this->ensureConnection();

        $sql = "SELECT COUNT(*) as count FROM `{$tableName}`";
        $result = $this->connection->execute($sql);

        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int) $row['count'];
    }

    /**
     * Get estimated row count (fast, uses INFORMATION_SCHEMA)
     *
     * Returns approximate row count from table statistics.
     * Instant even on billion-row tables. Not 100% accurate but
     * good enough for progress display and estimates.
     *
     * @param string $tableName Table name
     * @return int Estimated row count
     */
    public function getRowCountEstimate($tableName)
    {
        $this->ensureConnection();

        $sql = "SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$tableName}'";

        $result = $this->connection->execute($sql);

        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int) ($row['TABLE_ROWS'] ?? 0);
    }

    /**
     * Get table size in bytes
     *
     * @param string $tableName Table name
     * @return int Size in bytes
     */
    public function getTableSize($tableName)
    {
        $this->ensureConnection();

        $sql = "SELECT (DATA_LENGTH + INDEX_LENGTH) as size
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$tableName}'";

        $result = $this->connection->execute($sql);

        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int) ($row['size'] ?? 0);
    }

    /**
     * Format bytes to human readable string
     *
     * @param int $bytes Size in bytes
     * @return string Formatted size (e.g., "1.5 GB")
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

    /**
     * Empty a table by deleting all rows
     *
     * Preserves table structure and indexes, but removes all data.
     * Uses DELETE instead of TRUNCATE to respect foreign keys and preserve auto-increment counter.
     *
     * @param string $tableName Name of table to empty
     * @return void
     */
    public function emptyTable($tableName)
    {
        $this->ensureConnection();

        $sql = "DELETE FROM `{$tableName}`";
        $this->connection->execute($sql);
    }

    /**
     * Truncate table (reset with auto-increment reset)
     *
     * Truncates all rows from a table and resets the auto-increment counter
     * to 1. This is much faster than DELETE for large tables. Temporarily
     * disables foreign key checks to allow truncation of tables with FK
     * references.
     *
     * @param string $tableName Name of table to truncate
     * @return void
     * @throws \Exception If truncate fails
     */
    public function truncateTable($tableName)
    {
        $this->ensureConnection();

        // Disable foreign key checks temporarily
        $this->connection->execute("SET FOREIGN_KEY_CHECKS=0");

        try {
            // Execute TRUNCATE
            $sql = "TRUNCATE TABLE `{$tableName}`";
            $result = $this->connection->execute($sql);

            if (!$result) {
                throw new \Exception($this->connection->lastError());
            }
        } finally {
            // Always re-enable foreign key checks
            $this->connection->execute("SET FOREIGN_KEY_CHECKS=1");
        }
    }

    /**
     * Display table schema in formatted output
     *
     * Shows column names, types, and constraints.
     *
     * @param string $tableName Name of table to display
     * @return void
     */
    public function displayTableSchema($tableName)
    {
        $this->ensureConnection();

        $sql = "SHOW FULL COLUMNS FROM `{$tableName}`";
        $result = $this->connection->execute($sql);

        if (!$result) {
            return;
        }

        echo "Columns:\n";
        while ($row = $result->fetch_assoc()) {
            $col = $row['Field'];
            $type = $row['Type'];
            $null = $row['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
            $key = $row['Key'] ? " ({$row['Key']})" : '';
            $extra = $row['Extra'] ? " {$row['Extra']}" : '';

            echo "  {$col}: {$type} {$null}{$key}{$extra}\n";
        }

        // Display partition info if exists
        $partition = $this->getExistingPartition($tableName);
        if ($partition) {
            echo "\nPartition:\n";
            $type = strtoupper($partition['type']);
            echo "  PARTITION BY {$type}({$partition['column']}) PARTITIONS {$partition['count']}\n";
        }
    }

    /**
     * Get tables that reference a given table via foreign keys
     *
     * Returns an array of tables that have foreign key constraints
     * referencing the specified table.
     *
     * @param string $tableName Table name to check
     * @return array Associative array [table_name => [columns]]
     */
    public function getTablesReferencingTable($tableName)
    {
        $this->ensureConnection();

        // Escape table name for safe SQL usage
        $escapedTable = $this->connection->escape($tableName);

        $sql = "
            SELECT
                TABLE_NAME,
                COLUMN_NAME,
                CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME = '{$escapedTable}'
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ";

        // Execute query using standard execute() method
        $result = $this->connection->execute($sql);

        $references = [];
        while ($row = $result->fetch_assoc()) {
            $table = $row['TABLE_NAME'];
            if (!isset($references[$table])) {
                $references[$table] = [];
            }
            $references[$table][] = $row['COLUMN_NAME'];
        }

        return $references;
    }

    /**
     * Validate foreign key constraints before table creation
     *
     * Checks that all foreign key references:
     * 1. Point to existing tables
     * 2. Point to existing columns
     * 3. Have matching data types (exact match required)
     *
     * Throws descriptive exception if validation fails.
     *
     * @param array $schema Parsed model schema
     * @return void
     * @throws \Exception If foreign key validation fails
     */
    private function validateForeignKeys($schema)
    {
        // Get database connection
        $this->ensureConnection();

        //print_r($schema); exit();

        // Collect all foreign keys from schema
        $foreignKeys = [];
        foreach ($schema['columns'] as $columnName => $columnDef) {
            if (!empty($columnDef['foreign'])) {
                // Build full column type string (same as in createTableFromModel)
                $fullType = $columnDef['type'];
                if ($columnDef['length']) {
                    $fullType .= "({$columnDef['length']})";
                }
                if ($columnDef['unsigned']) {
                    $fullType .= " UNSIGNED";
                }

                $foreignKeys[] = [
                    'column' => $columnDef['name'],
                    'column_type' => $fullType,
                    'references' => $columnDef['foreign'],
                    'on_delete' => $columnDef['on_delete'] ?? 'RESTRICT',
                    'on_update' => $columnDef['on_update'] ?? 'RESTRICT',
                ];
            }
        }

        // No foreign keys? Nothing to validate
        if (empty($foreignKeys)) {
            return;
        }

        // Validate each foreign key
        foreach ($foreignKeys as $fk) {
            // Parse foreign key reference: "table(column)"
            if (!preg_match('/^(\w+)\((\w+)\)$/', $fk['references'], $matches)) {
                throw new \Exception(
                    "Invalid foreign key format for column '{$fk['column']}'.\n" .
                    "       Expected: tablename(columnname)\n" .
                    "       Got: {$fk['references']}"
                );
            }

            $refTable = $matches[1];
            $refColumn = $matches[2];

            // 1. Check if referenced table exists
            $tableCheck = $this->connection->execute("SHOW TABLES LIKE '{$refTable}'");
            if ($tableCheck->num_rows === 0) {
                throw new \Exception(
                    "Foreign Key Validation Failed!\n\n" .
                    "  Column: {$fk['column']}\n" .
                    "  References: {$refTable}({$refColumn})\n" .
                    "  Problem: Table '{$refTable}' does not exist\n\n" .
                    "  Fix: Create the '{$refTable}' table first, then retry.\n" .
                    "       Foreign keys must reference existing tables."
                );
            }

            // 2. Check if referenced column exists
            $columnCheck = $this->connection->execute(
                "SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_KEY
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = '{$refTable}'
                 AND COLUMN_NAME = '{$refColumn}'"
            );

            if ($columnCheck->num_rows === 0) {
                throw new \Exception(
                    "Foreign Key Validation Failed!\n\n" .
                    "  Column: {$fk['column']}\n" .
                    "  References: {$refTable}({$refColumn})\n" .
                    "  Problem: Column '{$refColumn}' does not exist in table '{$refTable}'\n\n" .
                    "  Fix: The referenced column must exist in the target table.\n" .
                    "       Check your @foreign annotation."
                );
            }

            $refColumnData = $columnCheck->fetch_assoc();

            // 3. Check if referenced column is indexed (required for foreign keys)
            if (empty($refColumnData['COLUMN_KEY'])) {
                throw new \Exception(
                    "Foreign Key Validation Failed!\n\n" .
                    "  Column: {$fk['column']}\n" .
                    "  References: {$refTable}({$refColumn})\n" .
                    "  Problem: Column '{$refTable}.{$refColumn}' is not indexed\n\n" .
                    "  Fix: Foreign keys can only reference indexed columns.\n" .
                    "       Add @index, @unique, or @primary to {$refTable}.{$refColumn}"
                );
            }

            // 4. Check if column types match EXACTLY
            $sourceType = strtoupper($fk['column_type']);
            $targetType = strtoupper($refColumnData['COLUMN_TYPE']);

            if ($sourceType !== $targetType) {
                // Provide helpful error with type comparison
                throw new \Exception(
                    "Foreign Key Validation Failed!\n\n" .
                    "  Column: {$fk['column']}\n" .
                    "  References: {$refTable}({$refColumn})\n" .
                    "  Problem: Data type mismatch\n\n" .
                    "  Source column type:     {$sourceType}\n" .
                    "  Referenced column type: {$targetType}\n\n" .
                    "  Fix: Foreign key columns must have EXACTLY matching types.\n" .
                    "       Change {$schema['table']}.{$fk['column']} to match {$refTable}.{$refColumn},\n" .
                    "       or update {$refTable}.{$refColumn} to match {$schema['table']}.{$fk['column']}."
                );
            }
        }

        // All foreign keys validated successfully
    }

    /**
     * Validate partition configuration
     *
     * Ensures partition column exists and is part of PRIMARY KEY.
     * MySQL requires partition column to be in every unique key.
     *
     * @param array $schema Parsed model schema
     * @throws \Exception If partition is invalid
     */
    private function validatePartition($schema)
    {
        // No partition defined? Nothing to validate
        if (empty($schema['partition'])) {
            return;
        }

        $partition = $schema['partition'];
        $partitionColumn = $partition['column'];

        // Build column lookup by name (columns array is numerically indexed)
        $columnsByName = [];
        foreach ($schema['columns'] as $colDef) {
            $columnsByName[$colDef['name']] = $colDef;
        }

        // 1. Check partition column exists in schema
        if (!isset($columnsByName[$partitionColumn])) {
            throw new \Exception(
                "Partition Validation Failed!\n\n" .
                "  @partition {$partition['type']}({$partitionColumn}) {$partition['count']}\n\n" .
                "  Problem: Column '{$partitionColumn}' does not exist in model.\n\n" .
                "  Fix: The partition column must be defined as a @column in the model.\n" .
                "       Add a property with @column annotation for '{$partitionColumn}'."
            );
        }

        // 2. Check partition column is part of PRIMARY KEY
        // MySQL requirement: partition column must be in every unique key
        $primaryKeys = [];
        foreach ($columnsByName as $colName => $colDef) {
            if (!empty($colDef['primary'])) {
                $primaryKeys[] = $colName;
            }
        }

        if (!in_array($partitionColumn, $primaryKeys)) {
            $currentPK = empty($primaryKeys) ? '(none)' : implode(', ', $primaryKeys);
            throw new \Exception(
                "Partition Validation Failed!\n\n" .
                "  @partition {$partition['type']}({$partitionColumn}) {$partition['count']}\n\n" .
                "  Problem: Column '{$partitionColumn}' is not part of PRIMARY KEY.\n\n" .
                "  Current PRIMARY KEY: {$currentPK}\n\n" .
                "  Fix: MySQL requires partition column to be in PRIMARY KEY.\n" .
                "       Add @primary annotation to '{$partitionColumn}' property.\n\n" .
                "  Example for composite primary key:\n" .
                "    /** @column @int @primary @auto_increment */\n" .
                "    protected \$id;\n\n" .
                "    /** @column @int @primary */\n" .
                "    protected \${$partitionColumn};"
            );
        }
    }

    /**
     * Execute raw SQL query
     *
     * @param string $sql SQL query to execute
     * @return mixed Query result
     */
    public function rawQuery($sql)
    {
        $this->ensureConnection();
        return $this->connection->execute($sql);
    }

    /**
     * Get existing composite indexes from database
     *
     * Returns only non-unique indexes with multiple columns (composite).
     * Single-column indexes and unique indexes are filtered out.
     *
     * @param string $tableName Table name
     * @return array Composite indexes ['index_name' => ['columns' => ['col1', 'col2']]]
     */
    private function getExistingCompositeIndexes($tableName)
    {
        $this->ensureConnection();

        $sql = "SELECT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX, NON_UNIQUE
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$tableName}'
                AND INDEX_NAME != 'PRIMARY'
                ORDER BY INDEX_NAME, SEQ_IN_INDEX";

        $result = $this->connection->execute($sql);

        if (!$result) {
            return [];
        }

        $indexes = [];
        while ($row = $result->fetch_assoc()) {
            $indexName = $row['INDEX_NAME'];
            if (!isset($indexes[$indexName])) {
                $indexes[$indexName] = [
                    'columns' => [],
                    'unique' => $row['NON_UNIQUE'] == 0,
                ];
            }
            $indexes[$indexName]['columns'][] = $row['COLUMN_NAME'];
        }

        // Filter: keep only composite (multi-column) AND non-unique indexes
        return array_filter($indexes, function($idx) {
            return count($idx['columns']) > 1 && !$idx['unique'];
        });
    }

    /**
     * Get existing composite unique indexes from database
     *
     * Returns only unique indexes with multiple columns (composite unique).
     * Single-column indexes and non-unique indexes are filtered out.
     *
     * @param string $tableName Table name
     * @return array Composite unique indexes ['index_name' => ['columns' => ['col1', 'col2']]]
     */
    private function getExistingCompositeUniqueIndexes($tableName)
    {
        $this->ensureConnection();

        $sql = "SELECT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX, NON_UNIQUE
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$tableName}'
                AND INDEX_NAME != 'PRIMARY'
                ORDER BY INDEX_NAME, SEQ_IN_INDEX";

        $result = $this->connection->execute($sql);

        if (!$result) {
            return [];
        }

        $indexes = [];
        while ($row = $result->fetch_assoc()) {
            $indexName = $row['INDEX_NAME'];
            if (!isset($indexes[$indexName])) {
                $indexes[$indexName] = [
                    'columns' => [],
                    'unique' => $row['NON_UNIQUE'] == 0,
                ];
            }
            $indexes[$indexName]['columns'][] = $row['COLUMN_NAME'];
        }

        // Filter: keep only composite (multi-column) AND unique indexes
        return array_filter($indexes, function($idx) {
            return count($idx['columns']) > 1 && $idx['unique'];
        });
    }

    /**
     * Get existing simple (single-column) indexes from database
     *
     * Returns only indexes with one column (simple/single-column).
     * Composite indexes and PRIMARY key are filtered out.
     *
     * @param string $tableName Table name
     * @return array Simple indexes ['index_name' => ['column' => 'col_name', 'unique' => bool]]
     */
    private function getExistingSimpleIndexes($tableName)
    {
        $this->ensureConnection();

        $sql = "SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$tableName}'
                AND INDEX_NAME != 'PRIMARY'
                ORDER BY INDEX_NAME, SEQ_IN_INDEX";

        $result = $this->connection->execute($sql);

        if (!$result) {
            return [];
        }

        $allIndexes = [];
        while ($row = $result->fetch_assoc()) {
            $indexName = $row['INDEX_NAME'];
            if (!isset($allIndexes[$indexName])) {
                $allIndexes[$indexName] = [
                    'columns' => [],
                    'unique' => $row['NON_UNIQUE'] == 0,
                ];
            }
            $allIndexes[$indexName]['columns'][] = $row['COLUMN_NAME'];
        }

        // Filter to only single-column indexes (both unique and non-unique)
        $simpleIndexes = [];
        foreach ($allIndexes as $indexName => $indexInfo) {
            if (count($indexInfo['columns']) === 1) {
                $simpleIndexes[$indexName] = [
                    'column' => $indexInfo['columns'][0],
                    'unique' => $indexInfo['unique'],
                ];
            }
        }

        return $simpleIndexes;
    }

    /**
     * Get existing FULLTEXT indexes from database table
     *
     * Queries INFORMATION_SCHEMA.STATISTICS for FULLTEXT indexes.
     * Returns index names mapped to their column names.
     *
     * Process:
     *   1. Ensure database connection
     *   2. Query STATISTICS table for INDEX_TYPE = 'FULLTEXT'
     *   3. Return associative array of index_name => column_name
     *
     * @param string $tableName Table name
     * @return array Fulltext indexes ['index_name' => 'column_name']
     */
    private function getExistingFulltextIndexes($tableName)
    {
        $this->ensureConnection();

        $sql = "SELECT INDEX_NAME, COLUMN_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$tableName}'
                AND INDEX_TYPE = 'FULLTEXT'
                ORDER BY INDEX_NAME, SEQ_IN_INDEX";

        $result = $this->connection->execute($sql);

        if (!$result) {
            return [];
        }

        $indexes = [];
        while ($row = $result->fetch_assoc()) {
            $indexes[$row['INDEX_NAME']] = $row['COLUMN_NAME'];
        }

        return $indexes;
    }

    /**
     * Get existing partition info for a table
     *
     * Queries INFORMATION_SCHEMA to get current partition configuration.
     * Returns null if table is not partitioned.
     *
     * @param string $tableName Table name
     * @return array|null Partition config ['type', 'column', 'count'] or null
     */
    public function getExistingPartition($tableName)
    {
        $this->ensureConnection();

        // Get partition info from INFORMATION_SCHEMA
        $sql = "SELECT PARTITION_METHOD, PARTITION_EXPRESSION, COUNT(*) as partition_count
                FROM INFORMATION_SCHEMA.PARTITIONS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$tableName}'
                AND PARTITION_NAME IS NOT NULL
                GROUP BY PARTITION_METHOD, PARTITION_EXPRESSION";

        $result = $this->connection->execute($sql);

        if (!$result) {
            return null;
        }

        $row = $result->fetch_assoc();

        if (!$row) {
            return null;
        }

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

    /**
     * Generate PARTITION BY clause for CREATE TABLE
     *
     * Builds the PARTITION BY clause based on partition configuration.
     * Supports HASH and KEY partitioning with specified partition count.
     *
     * Examples:
     *   ['type' => 'hash', 'column' => 'source', 'count' => 32]
     *   → PARTITION BY HASH(source) PARTITIONS 32
     *
     *   ['type' => 'key', 'column' => 'user_id', 'count' => 16]
     *   → PARTITION BY KEY(user_id) PARTITIONS 16
     *
     * Note: RANGE and LIST partitioning require additional configuration
     * (partition definitions) which is not yet supported.
     *
     * @param array $partition Partition config from ModelParser
     * @return string PARTITION BY clause (with leading newline)
     */
    private function generatePartitionClause($partition)
    {
        $type = strtoupper($partition['type']);
        $column = $partition['column'];
        $count = $partition['count'];

        switch ($type) {
            case 'HASH':
                return "\nPARTITION BY HASH(`{$column}`)\nPARTITIONS {$count}";

            case 'KEY':
                return "\nPARTITION BY KEY(`{$column}`)\nPARTITIONS {$count}";

            case 'RANGE':
            case 'LIST':
                // RANGE and LIST require partition definitions, not just count
                // For now, return empty - would need extended annotation syntax
                throw new \Exception(
                    "RANGE and LIST partitioning not yet supported.\n" .
                    "Use HASH or KEY partitioning instead:\n" .
                    "  @partition hash({$column}) 32"
                );

            default:
                return '';
        }
    }
}
