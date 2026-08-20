<?php namespace Roline\Commands\Migration;

/**
 * MigrationRun Command
 *
 * Executes all pending database migrations by running their up() methods. Tracks
 * migration execution in a database table to prevent running the same migration
 * twice and to enable rollback functionality. Migrations run sequentially in
 * timestamp order (oldest first).
 *
 * How It Works:
 *   1. Scans application/database/migrations/ for migration files
 *   2. Queries database for list of already-ran migrations
 *   3. Determines which migrations are pending (not yet run)
 *   4. Executes each pending migration's up() method sequentially
 *   5. Records successful migrations in migrations tracking table
 *   6. Stops immediately if any migration fails (prevents partial state)
 *
 * Migration Tracking:
 *   - Uses dedicated table to track which migrations have been executed
 *   - Each migration file name stored with run timestamp
 *   - Batch numbering for grouping migrations (useful for rollbacks)
 *   - Prevents re-running migrations that have already been applied
 *
 * Migration Execution Order:
 *   - Migrations run in filename order (timestamp prefix ensures chronological)
 *   - Example: 2025_01_15_120000_create_users.php runs before
 *              2025_01_15_130000_add_email_to_users.php
 *   - Sequential execution ensures dependencies are met
 *
 * Error Handling:
 *   - Stops at first failure (all-or-nothing per run)
 *   - Does NOT rollback successful migrations from current batch
 *   - User must fix error and re-run (already-successful migrations skipped)
 *   - Error messages show which migration failed and SQL error details
 *
 * Safety Features:
 *   - Each migration marked as run ONLY after successful execution
 *   - No double-execution possible (tracking table prevents)
 *   - Failed migrations NOT marked as run (can be fixed and re-run)
 *   - Displays count of migrations before execution
 *
 * Typical Workflow:
 *   1. Developer creates migration: php roline migration:make add_status
 *   2. Developer reviews generated migration file
 *   3. Developer runs: php roline migration:run
 *   4. Command executes all pending migrations
 *   5. Database is now up to date
 *
 * Use Cases:
 *   - Deploying schema changes to production
 *   - Setting up development databases
 *   - Applying teammate's migrations after git pull
 *   - Running migrations in CI/CD pipelines
 *   - Updating staging/test environments
 *
 * Important Notes:
 *   - Migrations should be idempotent where possible
 *   - Test migrations on development database first
 *   - Backup database before running on production
 *   - Check migration:status before running to see what will execute
 *
 * Example Output:
 *   Running 3 migration(s)...
 *
 *   → 2025_01_15_120000_create_users.php
 *   ✓ 2025_01_15_120000_create_users.php
 *   → 2025_01_15_130000_add_email.php
 *   ✓ 2025_01_15_130000_add_email.php
 *   → 2025_01_15_140000_create_posts.php
 *   ✓ 2025_01_15_140000_create_posts.php
 *
 *   Ran 3 migration(s) successfully!
 *
 * Usage:
 *   php roline migration:run
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

class MigrationRun extends MigrationCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Run pending migrations';
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
     * Execute pending migrations
     *
     * Scans migrations directory, determines which migrations haven't been run yet,
     * executes their up() methods sequentially, and marks each as run upon success.
     * Stops immediately if any migration fails to prevent partial database state.
     *
     * @param array $arguments Command arguments (none required)
     * @return void Exits with status 0 on success/no pending, 1 on failure
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

        // Create Migration utility instance for tracking/execution
        $migration = new Migration();

        // Get list of pending migrations (files not yet run)
        $pending = $migration->getPendingMigrations($migrationsDir);

        // Check if there are any pending migrations
        if (empty($pending)) {
            $this->line();
            $this->info('No pending migrations.');
            $this->line();
            exit(0);
        }

        // Display count of migrations about to run
        $this->line();
        $this->info('Running ' . count($pending) . ' migration(s)...');
        $this->line();

        // Track successfully run migrations count
        $ranCount = 0;

        // Execute each pending migration sequentially
        foreach ($pending as $file) {
            try {
                // Display migration filename being processed
                $this->info("  → {$file}");

                // Build full file path
                $filepath = $migrationsDir . '/' . $file;

                // Execute migration's up() method (applies changes)
                $migration->runMigration($filepath);

                // Mark migration as run in tracking table
                $migration->markAsRan($file);

                // Display success indicator
                $this->success("  ✓ {$file}");

                // Increment successful migrations counter
                $ranCount++;

            } catch (\Exception $e) {
                // Migration failed - display error and stop
                $this->line();
                $this->error("  ✗ Migration failed: {$file}");
                $this->line();
                $this->error("Error: " . $e->getMessage());
                $this->line();
                exit(1);
            }
        }

        // All migrations ran successfully - display summary
        $this->line();
        $this->success("Ran {$ranCount} migration(s) successfully!");
        $this->line();
    }
}
