<?php namespace Roline\Commands\Migration;

/**
 * MigrationMake Command
 *
 * Creates new migration files by comparing current database schema with the last
 * migration state. Automatically detects schema changes and generates up/down SQL
 * statements for migration and rollback. Maintains schema snapshots to track changes
 * over time.
 *
 * How It Works:
 *   1. Reads current database schema (all tables, columns, indexes)
 *   2. Loads last schema snapshot from application/database/schemas/
 *   3. Compares current vs last schema to detect changes
 *   4. Generates UP SQL (applies changes) and DOWN SQL (reverts changes)
 *   5. Creates migration file with timestamped filename
 *   6. Saves new schema snapshot for next migration
 *
 * Schema Detection:
 *   - New tables added
 *   - Tables dropped
 *   - Columns added/removed/modified
 *   - Indexes added/removed
 *   - Foreign keys added/removed
 *
 * File Generation:
 *   - Migration: application/database/migrations/YYYY_MM_DD_HHmmss_name.php
 *   - Schema:    application/database/schemas/YYYY_MM_DD_HHmmss_name.json
 *   - Uses stub template from application/database/stubs/migration.stub (custom)
 *     or built-in default stub
 *
 * Migration File Structure:
 *   - up() method: Contains SQL to apply changes
 *   - down() method: Contains SQL to revert changes
 *   - Metadata: Description, timestamp, migration name
 *
 * Important Notes:
 *   - Requires database changes to exist BEFORE running (use table:create/update)
 *   - Will error if no schema changes detected
 *   - Timestamps prevent migration filename conflicts
 *   - Schema snapshots maintain complete history
 *
 * Typical Workflow:
 *   1. Make schema changes via table:create or table:update commands
 *   2. Run migration:make to capture changes
 *   3. Review generated migration file
 *   4. Run migration:run to apply to other environments
 *
 * Usage:
 *   php roline migration:make add_email_to_users
 *   php roline migration:make create_products_table
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Migration
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Utils\SchemaReader;
use Roline\Utils\SchemaDiffer;

class MigrationMake extends MigrationCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Create a new migration file';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing required migration name
     */
    public function usage()
    {
        return '<name|required>';
    }

    /**
     * Execute migration file generation
     *
     * Compares current database schema with last schema snapshot, generates SQL
     * for up/down migrations, creates timestamped migration file and schema snapshot.
     * Errors if no schema changes detected or migration already exists.
     *
     * @param array $arguments Command arguments (migration name at index 0)
     * @return void Exits with status 1 on failure
     */
    public function execute($arguments)
    {
        // Validate migration name argument is provided
        if (empty($arguments[0])) {
            $this->error('Migration name is required!');
            $this->line();
            $this->info('Usage: php roline migration:make <name>');
            $this->line();
            $this->info('Example: php roline migration:make add_email_to_users');
            exit(1);
        }

        // Extract migration name
        $name = $arguments[0];

        // Build directory paths
        $migrationsDir = getcwd() . '/application/database/migrations';
        $schemasDir = getcwd() . '/application/database/schemas';

        // Ensure both directories exist
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
        }
        if (!is_dir($schemasDir)) {
            mkdir($schemasDir, 0755, true);
        }

        // Read current database schema
        $this->line();
        $this->info('Reading current database schema...');

        $schemaReader = new SchemaReader();
        $currentSchema = $schemaReader->getFullSchema();

        // Load last schema snapshot for comparison
        $lastSchema = $this->getLastSchema($schemasDir);

        // Compare schemas to detect changes
        $this->info('Comparing with previous state...');

        $differ = new SchemaDiffer();
        $diff = $differ->diff($lastSchema, $currentSchema);

        // Check if any schema changes were detected
        if (empty(trim($diff['up'])) && empty(trim($diff['down']))) {
            $this->line();
            $this->error('No schema changes detected!');
            $this->line();
            $this->info('Tip: Make changes to your database using table:create or table:update first.');
            $this->line();
            exit(1);
        }

        // Generate timestamped filename (prevents conflicts)
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}";
        $migrationFile = $migrationsDir . '/' . $filename . '.php';
        $schemaFile = $schemasDir . '/' . $filename . '.json';

        // Check if migration file already exists
        if (file_exists($migrationFile)) {
            $this->error("Migration already exists: {$filename}.php");
            exit(1);
        }

        // Load migration stub template (custom location first, then default)
        $customStubPath = getcwd() . '/application/database/stubs/migration.stub';
        $defaultStubPath = dirname(dirname(dirname(__DIR__))) . '/stubs/migration.stub';

        if (file_exists($customStubPath)) {
            $stubPath = $customStubPath;
        } else {
            $stubPath = $defaultStubPath;
        }

        // Validate stub file exists
        if (!file_exists($stubPath)) {
            $this->error('Migration stub file not found!');
            exit(1);
        }

        // Read stub template
        $stub = file_get_contents($stubPath);

        // Format SQL for insertion into stub template
        $upSql = $this->formatSqlForStub($diff['up']);
        $downSql = $this->formatSqlForStub($diff['down']);

        // Replace placeholders in stub template
        $migrationName = str_replace('_', ' ', ucwords($name, '_'));
        $stub = str_replace('{{MigrationName}}', $migrationName, $stub);
        $stub = str_replace('{{Description}}', $name, $stub);
        $stub = str_replace('{{Timestamp}}', date('Y-m-d H:i:s'), $stub);
        $stub = str_replace('{{UpSQL}}', $upSql, $stub);
        $stub = str_replace('{{DownSQL}}', $downSql, $stub);

        // Write migration file to disk
        file_put_contents($migrationFile, $stub);

        // Save schema snapshot for next migration comparison
        file_put_contents($schemaFile, json_encode($currentSchema, JSON_PRETTY_PRINT));

        // Display success message with file locations
        $this->line();
        $this->success("Migration created successfully!");
        $this->line();
        $this->info("Migration: application/database/migrations/{$filename}.php");
        $this->info("Schema:    application/database/schemas/{$filename}.json");
        $this->line();
    }

    /**
     * Get last schema from schemas directory
     *
     * Finds the most recent schema snapshot by scanning the schemas directory
     * for JSON files and sorting by timestamp prefix. Returns empty array if
     * no previous snapshots exist.
     *
     * @param string $schemasDir Schemas directory path
     * @return array Last schema snapshot or empty array if none exist
     */
    private function getLastSchema($schemasDir)
    {
        // Return empty array if schemas directory doesn't exist yet
        if (!is_dir($schemasDir)) {
            return [];
        }

        // Scan directory for schema files
        $files = scandir($schemasDir);
        $schemaFiles = [];

        // Filter for JSON files only
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $schemaFiles[] = $file;
            }
        }

        // Return empty array if no schema snapshots found
        if (empty($schemaFiles)) {
            return [];
        }

        // Sort by filename (timestamp prefix) in reverse and get most recent
        rsort($schemaFiles);
        $lastSchemaFile = $schemasDir . '/' . $schemaFiles[0];

        // Read and decode JSON schema snapshot
        $json = file_get_contents($lastSchemaFile);
        return json_decode($json, true) ?: [];
    }

    /**
     * Format SQL for insertion into stub
     *
     * Formats raw SQL statements for inclusion in migration stub template.
     * Splits statements by semicolon, wraps each in Model::sql() call,
     * and adds proper indentation for code readability.
     *
     * @param string $sql Raw SQL statements
     * @return string Formatted SQL with Model::sql() calls and indentation
     */
    private function formatSqlForStub($sql)
    {
        // Handle empty SQL (no changes in this direction)
        if (empty(trim($sql))) {
            return '    // No changes';
        }

        // Split by semicolon to separate individual statements
        $statements = array_filter(explode(';', $sql));
        $formatted = [];

        foreach ($statements as $statement) {
            $statement = trim($statement);

            if (!empty($statement)) {
                // Add proper indentation to each line of SQL
                $lines = explode("\n", $statement);
                $indented = [];
                foreach ($lines as $line) {
                    $indented[] = '        ' . $line;
                }
                $indentedSql = implode("\n", $indented);

                // Wrap in Model::sql() call
                $formatted[] = "    Model::sql(\"\n{$indentedSql}\n    \");";
            }
        }

        // Join all statements with blank line separation
        return implode("\n\n", $formatted);
    }
}
