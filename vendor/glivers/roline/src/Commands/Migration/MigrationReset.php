<?php namespace Roline\Commands\Migration;

/**
 * MigrationReset Command
 *
 * Completely resets the migration system by removing all migration files,
 * schema snapshots, and database migration records. Use when you want to
 * start fresh with migrations.
 *
 * What Gets Deleted:
 *   - All files in application/database/migrations/
 *   - All files in application/database/schemas/
 *   - All records in migrations table (TRUNCATE)
 *
 * Use Cases:
 *   - Development: Reset and rebuild from scratch
 *   - Starting over after major schema changes
 *   - Cleaning up test migrations
 *
 * IMPORTANT:
 *   - This does NOT drop your database tables
 *   - This only removes migration tracking
 *   - You'll need to manually drop/recreate tables if needed
 *   - Requires confirmation before executing
 *
 * Usage:
 *   php roline migration:reset
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Migration
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\File;
use Rackage\Model;
use Roline\Output;

class MigrationReset extends MigrationCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Reset migrations (delete all files and records)';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax
     */
    public function usage()
    {
        return '';
    }

    /**
     * Display detailed help information
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Description:');
        Output::line('  Resets the migration system by deleting all migration files,');
        Output::line('  schema snapshots, and migration records from database.');
        Output::line();

        Output::info('What gets deleted:');
        Output::line('  - All files in application/database/migrations/');
        Output::line('  - All files in application/database/schemas/');
        Output::line('  - All records in migrations table');
        Output::line();

        Output::info('What does NOT get deleted:');
        Output::line('  - Your database tables (you must drop manually if needed)');
        Output::line('  - Your data (only migration tracking is removed)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline migration:reset');
        Output::line();
    }

    /**
     * Execute migration reset
     *
     * @param array $arguments Command arguments (none expected)
     * @return void Exits with status 0 on cancel, 1 on failure
     */
    public function execute($arguments)
    {
        try {
            // Count what will be deleted
            $migrationsDir = 'application/database/migrations';
            $schemasDir = 'application/database/schemas';

            // Count migration files
            $migrationCount = 0;
            if (File::exists($migrationsDir)->exists) {
                $migrationFiles = File::files($migrationsDir)->files;
                $migrationCount = count($migrationFiles);
            }

            // Count schema files
            $schemaCount = 0;
            if (File::exists($schemasDir)->exists) {
                $schemaFiles = File::files($schemasDir)->files;
                $schemaCount = count($schemaFiles);
            }

            // Count migration records
            $recordCount = 0;
            try {
                $result = Model::sql("SELECT COUNT(*) as count FROM migrations");
                $row = $result->fetch_assoc();
                $recordCount = (int) $row['count'];
            } catch (\Exception $e) {
                // Table doesn't exist yet
            }

            // Display what will be deleted
            $this->line();
            $this->warning('This will DELETE:');
            $this->line("  - {$migrationCount} migration files");
            $this->line("  - {$schemaCount} schema snapshot files");
            $this->line("  - {$recordCount} migration records from database");
            $this->line();
            $this->warning('This will NOT delete your database tables or data.');
            $this->line();

            // Confirm
            $confirmed = $this->confirm('Are you sure you want to reset all migrations?');
            if (!$confirmed) {
                $this->info('Reset cancelled.');
                exit(0);
            }

            $this->line();

            // Delete migration files
            if ($migrationCount > 0) {
                $this->info('Deleting migration files...');
                File::cleanDir($migrationsDir);
                $this->success("  Deleted {$migrationCount} files");
            }

            // Delete schema files
            if ($schemaCount > 0) {
                $this->info('Deleting schema snapshots...');
                File::cleanDir($schemasDir);
                $this->success("  Deleted {$schemaCount} files");
            }

            // Truncate migrations table
            if ($recordCount > 0) {
                $this->info('Truncating migrations table...');
                Model::sql("TRUNCATE TABLE migrations");
                $this->success("  Deleted {$recordCount} records");
            }

            $this->line();
            $this->success('Migration system reset successfully!');
            $this->line();

        } catch (\Exception $e) {
            $this->line();
            $this->error('Reset failed!');
            $this->line();
            $this->error('Error: ' . $e->getMessage());
            $this->line();
            exit(1);
        }
    }
}
