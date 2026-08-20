<?php namespace Roline\Commands\Migration;

/**
 * MigrationStatus Command
 *
 * Displays comprehensive status overview of all migrations showing which have been
 * executed and which are pending. Provides visual distinction between ran and pending
 * migrations with formatted output similar to git status. Essential tool for understanding
 * database migration state before running migrations or rollbacks.
 *
 * What Gets Displayed:
 *   - Ran Migrations Section:
 *     * Count of executed migrations
 *     * List of migration filenames that have been run
 *     * Visual ✓ checkmark indicator for each
 *     * Ordered by execution time (oldest to newest)
 *
 *   - Pending Migrations Section:
 *     * Count of migrations not yet executed
 *     * List of migration filenames waiting to run
 *     * Visual ⏳ hourglass indicator for each
 *     * Ordered by timestamp (execution order if run)
 *
 * Output Format:
 *   - Bordered header with title
 *   - Clear section separators
 *   - Color-coded output (green for ran, yellow for pending)
 *   - Empty state messages when no migrations exist
 *   - "Database is up to date!" when no pending migrations
 *
 * Status Determination:
 *   - Queries migrations tracking table for executed migrations
 *   - Scans migrations directory for all migration files
 *   - Compares to determine pending migrations (not in tracking table)
 *   - Groups by execution status for display
 *
 * Use Cases:
 *   - Check migration state before running migration:run
 *   - Verify all migrations executed after deployment
 *   - Debug why migrations aren't running (check if already ran)
 *   - Review migration history
 *   - Confirm database is up to date
 *   - Identify which migrations will run next
 *
 * Typical Workflow:
 *   1. Developer pulls code with new migrations
 *   2. Runs: php roline migration:status
 *   3. Sees pending migrations listed
 *   4. Runs: php roline migration:run
 *   5. Runs: php roline migration:status (verify all ran)
 *
 * Important Notes:
 *   - Read-only command (makes no database changes)
 *   - Safe to run anytime without side effects
 *   - Requires migrations directory to exist
 *   - Empty directory shows "Database is up to date"
 *
 * Example Output (Mixed State):
 *   =================================================
 *                 MIGRATION STATUS
 *   =================================================
 *
 *   Ran Migrations (3):
 *
 *     ✓ 2025_01_15_120000_create_users.php
 *     ✓ 2025_01_15_130000_add_email.php
 *     ✓ 2025_01_15_135000_create_posts.php
 *
 *   Pending Migrations (2):
 *
 *     ⏳ 2025_01_15_140000_add_status.php
 *     ⏳ 2025_01_15_145000_create_comments.php
 *
 *   =================================================
 *
 * Example Output (Up to Date):
 *   =================================================
 *                 MIGRATION STATUS
 *   =================================================
 *
 *   Ran Migrations (5):
 *     ✓ 2025_01_15_120000_create_users.php
 *     ✓ 2025_01_15_130000_add_email.php
 *     (... more migrations ...)
 *
 *   Pending Migrations (0):
 *     Database is up to date!
 *
 *   =================================================
 *
 * Example Output (No Migrations):
 *   =================================================
 *                 MIGRATION STATUS
 *   =================================================
 *
 *   Ran Migrations (0):
 *     No migrations have been run yet.
 *
 *   Pending Migrations (0):
 *     Database is up to date!
 *
 *   =================================================
 *
 * Usage:
 *   php roline migration:status
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Migration
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Utils\Migration;

class MigrationStatus extends MigrationCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Show migration status (ran and pending)';
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
     * Execute migration status display
     *
     * Queries tracking table for executed migrations, scans directory for all migration
     * files, compares to determine pending migrations, and displays formatted output
     * showing both ran and pending migrations with counts and visual indicators.
     *
     * @param array $arguments Command arguments (none required)
     * @return void Exits with status 0 (always successful - read-only), 1 on directory error
     */
    public function execute($arguments)
    {
        // Build path to migrations directory
        $migrationsDir = getcwd() . '/application/database/migrations';

        // Validate migrations directory exists
        if (!is_dir($migrationsDir)) {
            $this->error('Migrations directory not found!');
            $this->line();
            $this->info('Location: application/database/migrations/');
            exit(1);
        }

        // Create Migration utility instance for querying
        $migration = new Migration();

        // Get list of migrations that have been executed
        $ranMigrations = $migration->getRanMigrations();

        // Get list of migrations waiting to be executed
        $pendingMigrations = $migration->getPendingMigrations($migrationsDir);

        // Display formatted header with border
        $this->line();
        $this->line('=================================================');
        $this->line('              MIGRATION STATUS');
        $this->line('=================================================');
        $this->line();

        // Display ran migrations section
        if (!empty($ranMigrations)) {
            // Show count in section header
            $this->success('Ran Migrations (' . count($ranMigrations) . '):');
            $this->line();

            // List each ran migration with checkmark indicator
            foreach ($ranMigrations as $file) {
                $this->line('  ✓ ' . $file);
            }
        } else {
            // No migrations have been executed yet
            $this->info('Ran Migrations (0):');
            $this->line('  No migrations have been run yet.');
        }

        // Blank line separator between sections
        $this->line();

        // Display pending migrations section
        if (!empty($pendingMigrations)) {
            // Show count in section header
            $this->info('Pending Migrations (' . count($pendingMigrations) . '):');
            $this->line();

            // List each pending migration with hourglass indicator
            foreach ($pendingMigrations as $file) {
                $this->line('  ⏳ ' . $file);
            }
        } else {
            // Database is up to date - no pending migrations
            $this->success('Pending Migrations (0):');
            $this->line('  Database is up to date!');
        }

        // Display closing border
        $this->line();
        $this->line('=================================================');
        $this->line();
    }
}
