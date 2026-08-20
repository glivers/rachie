<?php namespace Roline\Utils;

/**
 * SchemaDiffer
 *
 * Compares two database schemas and generates SQL for up() and down() migrations.
 * Detects added/removed/modified tables and columns.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Roline
 * @package Roline\Utils
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */
class SchemaDiffer
{
    /**
     * Generate migration SQL from schema diff
     *
     * @param array $oldSchema Previous schema (from last migration)
     * @param array $newSchema Current schema (from database)
     * @return array ['up' => sql, 'down' => sql]
     */
    public function diff($oldSchema, $newSchema)
    {
        $upSql = [];
        $downSql = [];

        // Find added tables
        foreach ($newSchema as $tableName => $tableSchema) {
            if (!isset($oldSchema[$tableName])) {
                $upSql[] = $this->generateCreateTable($tableName, $tableSchema);
                $downSql[] = $this->generateDropTable($tableName);
            }
        }

        // Find removed tables
        foreach ($oldSchema as $tableName => $tableSchema) {
            if (!isset($newSchema[$tableName])) {
                $upSql[] = $this->generateDropTable($tableName);
                $downSql[] = $this->generateCreateTable($tableName, $tableSchema);
            }
        }

        // Find modified tables
        foreach ($newSchema as $tableName => $newTableSchema) {
            if (isset($oldSchema[$tableName])) {
                $oldTableSchema = $oldSchema[$tableName];
                $tableDiff = $this->diffTable($tableName, $oldTableSchema, $newTableSchema);

                if (!empty($tableDiff['up'])) {
                    $upSql = array_merge($upSql, $tableDiff['up']);
                }
                if (!empty($tableDiff['down'])) {
                    $downSql = array_merge($downSql, $tableDiff['down']);
                }
            }
        }

        return [
            'up' => implode("\n\n", $upSql),
            'down' => implode("\n\n", array_reverse($downSql))
        ];
    }

    /**
     * Diff a single table
     *
     * @param string $tableName Table name
     * @param array $oldSchema Old table schema
     * @param array $newSchema New table schema
     * @return array ['up' => [...], 'down' => [...]]
     */
    protected function diffTable($tableName, $oldSchema, $newSchema)
    {
        $upSql = [];
        $downSql = [];

        $oldColumns = $oldSchema['columns'] ?? [];
        $newColumns = $newSchema['columns'] ?? [];

        // Find added columns
        foreach ($newColumns as $columnName => $columnDef) {
            if (!isset($oldColumns[$columnName])) {
                $upSql[] = $this->generateAddColumn($tableName, $columnName, $columnDef);
                $downSql[] = $this->generateDropColumn($tableName, $columnName);
            }
        }

        // Find removed columns
        foreach ($oldColumns as $columnName => $columnDef) {
            if (!isset($newColumns[$columnName])) {
                $upSql[] = $this->generateDropColumn($tableName, $columnName);
                $downSql[] = $this->generateAddColumn($tableName, $columnName, $columnDef);
            }
        }

        // Find modified columns
        foreach ($newColumns as $columnName => $newDef) {
            if (isset($oldColumns[$columnName])) {
                $oldDef = $oldColumns[$columnName];
                if ($this->columnChanged($oldDef, $newDef)) {
                    $upSql[] = $this->generateModifyColumn($tableName, $columnName, $newDef);
                    $downSql[] = $this->generateModifyColumn($tableName, $columnName, $oldDef);
                }
            }
        }

        // Detect foreign key changes
        $oldForeignKeys = $oldSchema['foreign_keys'] ?? [];
        $newForeignKeys = $newSchema['foreign_keys'] ?? [];

        // Find added foreign keys
        foreach ($newForeignKeys as $constraintName => $fk) {
            if (!isset($oldForeignKeys[$constraintName])) {
                $upSql[] = $this->generateAddForeignKey($tableName, $constraintName, $fk);
                $downSql[] = $this->generateDropForeignKey($tableName, $constraintName);
            }
        }

        // Find removed foreign keys
        foreach ($oldForeignKeys as $constraintName => $fk) {
            if (!isset($newForeignKeys[$constraintName])) {
                $upSql[] = $this->generateDropForeignKey($tableName, $constraintName);
                $downSql[] = $this->generateAddForeignKey($tableName, $constraintName, $fk);
            }
        }

        // Find modified foreign keys (drop old, add new)
        foreach ($newForeignKeys as $constraintName => $newFk) {
            if (isset($oldForeignKeys[$constraintName])) {
                $oldFk = $oldForeignKeys[$constraintName];
                if ($this->foreignKeyChanged($oldFk, $newFk)) {
                    // Drop old constraint
                    $upSql[] = $this->generateDropForeignKey($tableName, $constraintName);
                    // Add new constraint
                    $upSql[] = $this->generateAddForeignKey($tableName, $constraintName, $newFk);

                    // Reverse for down migration
                    $downSql[] = $this->generateDropForeignKey($tableName, $constraintName);
                    $downSql[] = $this->generateAddForeignKey($tableName, $constraintName, $oldFk);
                }
            }
        }

        // Detect CHECK constraint changes (MySQL 8.0.16+)
        $oldCheckConstraints = $oldSchema['check_constraints'] ?? [];
        $newCheckConstraints = $newSchema['check_constraints'] ?? [];

        // Find added check constraints
        foreach ($newCheckConstraints as $constraintName => $checkClause) {
            if (!isset($oldCheckConstraints[$constraintName])) {
                $upSql[] = $this->generateAddCheckConstraint($tableName, $constraintName, $checkClause);
                $downSql[] = $this->generateDropCheckConstraint($tableName, $constraintName);
            }
        }

        // Find removed check constraints
        foreach ($oldCheckConstraints as $constraintName => $checkClause) {
            if (!isset($newCheckConstraints[$constraintName])) {
                $upSql[] = $this->generateDropCheckConstraint($tableName, $constraintName);
                $downSql[] = $this->generateAddCheckConstraint($tableName, $constraintName, $checkClause);
            }
        }

        // Find modified check constraints (drop old, add new)
        foreach ($newCheckConstraints as $constraintName => $newCheckClause) {
            if (isset($oldCheckConstraints[$constraintName])) {
                $oldCheckClause = $oldCheckConstraints[$constraintName];
                if ($oldCheckClause !== $newCheckClause) {
                    // Drop old constraint
                    $upSql[] = $this->generateDropCheckConstraint($tableName, $constraintName);
                    // Add new constraint
                    $upSql[] = $this->generateAddCheckConstraint($tableName, $constraintName, $newCheckClause);

                    // Reverse for down migration
                    $downSql[] = $this->generateDropCheckConstraint($tableName, $constraintName);
                    $downSql[] = $this->generateAddCheckConstraint($tableName, $constraintName, $oldCheckClause);
                }
            }
        }

        // Detect index changes (both simple and composite)
        $oldIndexes = $oldSchema['indexes'] ?? [];
        $newIndexes = $newSchema['indexes'] ?? [];

        // Find added indexes
        foreach ($newIndexes as $indexName => $newIdx) {
            if (!isset($oldIndexes[$indexName])) {
                $upSql[] = $this->generateAddIndex($tableName, $indexName, $newIdx);
                $downSql[] = $this->generateDropIndex($tableName, $indexName);
            }
        }

        // Find removed indexes
        foreach ($oldIndexes as $indexName => $oldIdx) {
            if (!isset($newIndexes[$indexName])) {
                $upSql[] = $this->generateDropIndex($tableName, $indexName);
                $downSql[] = $this->generateAddIndex($tableName, $indexName, $oldIdx);
            }
        }

        // Find modified indexes (drop old, add new)
        foreach ($newIndexes as $indexName => $newIdx) {
            if (isset($oldIndexes[$indexName])) {
                $oldIdx = $oldIndexes[$indexName];
                if ($this->indexChanged($oldIdx, $newIdx)) {
                    // Drop old index
                    $upSql[] = $this->generateDropIndex($tableName, $indexName);
                    // Add new index
                    $upSql[] = $this->generateAddIndex($tableName, $indexName, $newIdx);

                    // Reverse for down migration
                    $downSql[] = $this->generateDropIndex($tableName, $indexName);
                    $downSql[] = $this->generateAddIndex($tableName, $indexName, $oldIdx);
                }
            }
        }

        return ['up' => $upSql, 'down' => $downSql];
    }

    /**
     * Check if column definition changed
     *
     * @param array $oldDef Old column definition
     * @param array $newDef New column definition
     * @return bool True if changed
     */
    protected function columnChanged($oldDef, $newDef)
    {
        return $oldDef['type'] !== $newDef['type']
            || $oldDef['nullable'] !== $newDef['nullable']
            || $oldDef['default'] !== $newDef['default']
            || $oldDef['extra'] !== $newDef['extra'];
    }

    /**
     * Generate CREATE TABLE SQL
     *
     * @param string $tableName Table name
     * @param array $schema Table schema
     * @return string SQL statement
     */
    protected function generateCreateTable($tableName, $schema)
    {
        $columns = $schema['columns'] ?? [];
        $primaryKey = $schema['primary_key'] ?? null;
        $indexes = $schema['indexes'] ?? [];
        $foreignKeys = $schema['foreign_keys'] ?? [];
        $engine = $schema['engine'] ?? 'InnoDB';
        $charset = $schema['charset'] ?? 'utf8mb4';

        $columnDefs = [];
        foreach ($columns as $columnName => $columnDef) {
            $columnDefs[] = $this->buildColumnDefinition($columnName, $columnDef);
        }

        $sql = "CREATE TABLE `{$tableName}` (\n";
        $sql .= "    " . implode(",\n    ", $columnDefs);

        if ($primaryKey) {
            $pkColumns = implode('`, `', $primaryKey);
            $sql .= ",\n    PRIMARY KEY (`{$pkColumns}`)";
        }

        // Add indexes (both simple and composite)
        if (!empty($indexes)) {
            foreach ($indexes as $indexName => $indexDef) {
                // Skip primary key (already added above)
                if ($indexName === 'PRIMARY') {
                    continue;
                }

                // Build column list for index
                $indexColumns = '`' . implode('`, `', $indexDef['columns']) . '`';
                $indexType = strtoupper($indexDef['type'] ?? 'BTREE');

                // Add appropriate index type
                if ($indexType === 'FULLTEXT') {
                    $sql .= ",\n    FULLTEXT KEY `{$indexName}` ({$indexColumns})";
                }
                elseif ($indexDef['unique'] ?? false) {
                    $sql .= ",\n    UNIQUE KEY `{$indexName}` ({$indexColumns})";
                }
                else {
                    $sql .= ",\n    KEY `{$indexName}` ({$indexColumns})";
                }
            }
        }

        // Add foreign key constraints
        if (!empty($foreignKeys)) {
            foreach ($foreignKeys as $constraintName => $fk) {
                $sql .= ",\n    CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$fk['column']}`) ";
                $sql .= "REFERENCES `{$fk['referenced_table']}` (`{$fk['referenced_column']}`)";

                if (!empty($fk['on_delete'])) {
                    $sql .= " ON DELETE {$fk['on_delete']}";
                }

                if (!empty($fk['on_update'])) {
                    $sql .= " ON UPDATE {$fk['on_update']}";
                }
            }
        }

        $sql .= "\n) ENGINE={$engine} DEFAULT CHARSET={$charset};";

        return $sql;
    }

    /**
     * Generate DROP TABLE SQL
     *
     * @param string $tableName Table name
     * @return string SQL statement
     */
    protected function generateDropTable($tableName)
    {
        return "DROP TABLE IF EXISTS `{$tableName}`;";
    }

    /**
     * Generate ADD COLUMN SQL
     *
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param array $columnDef Column definition
     * @return string SQL statement
     */
    protected function generateAddColumn($tableName, $columnName, $columnDef)
    {
        $definition = $this->buildColumnDefinition($columnName, $columnDef);
        return "ALTER TABLE `{$tableName}` ADD COLUMN {$definition};";
    }

    /**
     * Generate DROP COLUMN SQL
     *
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @return string SQL statement
     */
    protected function generateDropColumn($tableName, $columnName)
    {
        return "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`;";
    }

    /**
     * Generate MODIFY COLUMN SQL
     *
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param array $columnDef Column definition
     * @return string SQL statement
     */
    protected function generateModifyColumn($tableName, $columnName, $columnDef)
    {
        $definition = $this->buildColumnDefinition($columnName, $columnDef);
        return "ALTER TABLE `{$tableName}` MODIFY COLUMN {$definition};";
    }

    /**
     * Build column definition string
     *
     * @param string $columnName Column name
     * @param array $columnDef Column definition
     * @return string Column definition SQL
     */
    protected function buildColumnDefinition($columnName, $columnDef)
    {
        $sql = "`{$columnName}` {$columnDef['type']}";

        if (!$columnDef['nullable']) {
            $sql .= " NOT NULL";
        }

        if ($columnDef['default'] !== null) {
            // INFORMATION_SCHEMA.COLUMN_DEFAULT returns exactly what's needed
            // - String defaults include quotes: 'pending'
            // - Numeric defaults have no quotes: 0
            // - Keywords have no quotes: CURRENT_TIMESTAMP, NULL
            $sql .= " DEFAULT {$columnDef['default']}";
        }

        if (!empty($columnDef['extra'])) {
            $sql .= " {$columnDef['extra']}";
        }

        return $sql;
    }

    /**
     * Check if foreign key definition changed
     *
     * @param array $oldFk Old foreign key definition
     * @param array $newFk New foreign key definition
     * @return bool True if changed
     */
    protected function foreignKeyChanged($oldFk, $newFk)
    {
        return $oldFk['column'] !== $newFk['column']
            || $oldFk['referenced_table'] !== $newFk['referenced_table']
            || $oldFk['referenced_column'] !== $newFk['referenced_column']
            || $oldFk['on_delete'] !== $newFk['on_delete']
            || $oldFk['on_update'] !== $newFk['on_update'];
    }

    /**
     * Generate ADD CONSTRAINT (foreign key) SQL
     *
     * @param string $tableName Table name
     * @param string $constraintName Constraint name
     * @param array $fk Foreign key definition
     * @return string SQL statement
     */
    protected function generateAddForeignKey($tableName, $constraintName, $fk)
    {
        $sql = "ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$constraintName}` ";
        $sql .= "FOREIGN KEY (`{$fk['column']}`) ";
        $sql .= "REFERENCES `{$fk['referenced_table']}` (`{$fk['referenced_column']}`)";

        if (!empty($fk['on_delete'])) {
            $sql .= " ON DELETE {$fk['on_delete']}";
        }

        if (!empty($fk['on_update'])) {
            $sql .= " ON UPDATE {$fk['on_update']}";
        }

        $sql .= ";";

        return $sql;
    }

    /**
     * Generate DROP CONSTRAINT (foreign key) SQL
     *
     * @param string $tableName Table name
     * @param string $constraintName Constraint name
     * @return string SQL statement
     */
    protected function generateDropForeignKey($tableName, $constraintName)
    {
        return "ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`;";
    }

    /**
     * Check if index definition changed
     *
     * @param array $oldIdx Old index definition
     * @param array $newIdx New index definition
     * @return bool True if changed
     */
    protected function indexChanged($oldIdx, $newIdx)
    {
        // Check if columns changed
        if ($oldIdx['columns'] !== $newIdx['columns']) {
            return true;
        }

        // Check if unique status changed
        if (($oldIdx['unique'] ?? false) !== ($newIdx['unique'] ?? false)) {
            return true;
        }

        return false;
    }

    /**
     * Generate ADD INDEX SQL
     *
     * @param string $tableName Table name
     * @param string $indexName Index name
     * @param array $indexDef Index definition
     * @return string SQL statement
     */
    protected function generateAddIndex($tableName, $indexName, $indexDef)
    {
        $columnList = '`' . implode('`, `', $indexDef['columns']) . '`';
        $indexType = strtoupper($indexDef['type'] ?? 'BTREE');

        if ($indexType === 'FULLTEXT') {
            return "ALTER TABLE `{$tableName}` ADD FULLTEXT INDEX `{$indexName}` ({$columnList});";
        }
        elseif ($indexDef['unique'] ?? false) {
            return "ALTER TABLE `{$tableName}` ADD UNIQUE INDEX `{$indexName}` ({$columnList});";
        }
        else {
            return "ALTER TABLE `{$tableName}` ADD INDEX `{$indexName}` ({$columnList});";
        }
    }

    /**
     * Generate DROP INDEX SQL
     *
     * @param string $tableName Table name
     * @param string $indexName Index name
     * @return string SQL statement
     */
    protected function generateDropIndex($tableName, $indexName)
    {
        return "ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`;";
    }

    /**
     * Generate ADD CHECK CONSTRAINT SQL (MySQL 8.0.16+)
     *
     * @param string $tableName Table name
     * @param string $constraintName Constraint name
     * @param string $checkClause CHECK clause expression
     * @return string SQL statement
     */
    protected function generateAddCheckConstraint($tableName, $constraintName, $checkClause)
    {
        return "ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$constraintName}` CHECK ({$checkClause});";
    }

    /**
     * Generate DROP CHECK CONSTRAINT SQL (MySQL 8.0.16+)
     *
     * @param string $tableName Table name
     * @param string $constraintName Constraint name
     * @return string SQL statement
     */
    protected function generateDropCheckConstraint($tableName, $constraintName)
    {
        return "ALTER TABLE `{$tableName}` DROP CHECK `{$constraintName}`;";
    }
}
