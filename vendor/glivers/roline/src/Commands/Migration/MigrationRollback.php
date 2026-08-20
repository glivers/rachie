<?php namespace Roline\Commands\Migration;

/**
 * MigrationRollback Command
 *
 * Reverts previously executed migrations by running their down() methods. Allows
 * rolling back single or multiple batches of migrations for undoing database changes.
 * Executes rollbacks in reverse chronological order (newest first) to maintain
 * referential integrity and dependency order.
 *
 * How It Works:
 *   1. Queries migrations tracking table for last-run migrations
 *   2. Optionally accepts steps parameter to rollback multiple batches
 *   3. Determines which migrations to rollback (most recent batch(es))
 *   4. Executes each migration's down() method in reverse order
 *   5. Removes successfully rolled-back migrations from tracking table
 *   6. Stops immediately if any rollback fails
 *
 * Batch System:
 *   - Each migration:run groups migrations into a batch
 *   - Batch number auto-increments with each run
 *   - Rollback targets most recent batch by default
 *   - Optional steps parameter rolls back multiple batches
 *
 * Rollback Order:
 *   - Executes in REVERSE chronological order (newest migration first)
 *   - Example: If migrations ran in order A, B, C
 *              Rollback executes C down(), B down(), A down()
 *   - Ensures foreign keys and dependencies are handled correctly
 *
 * Steps Parameter:
 *   - No argument: Rollback last batch only (default)
 *   - steps=1: Same as no argument (last batch)
 *   - steps=2: Rollback last 2 batches
 *   - steps=N: Rollback last N batches
 *
 * Error Handling:
 *   - Stops at first rollback failure (prevents partial state)
 *   - Does NOT re-apply successful rollbacks from current run
 *   - Successfully rolled-back migrations are NOT in tracking table
 *   - Failed rollback leaves migration marked as run (can retry after fix)
 *   - Error messages show which migration failed and SQL error details
 *
 * Safety Features:
 *   - Migration removed from tracking ONLY after successful rollback
 *   - Validates migration files still exist before rollback
 *   - Displays count of migrations before execution
 *   - Skips missing migration files with warning (continues to next)
 *
 * Important Notes:
 *   - down() method must properly reverse up() method changes
 *   - Cannot rollback migrations whose files have been deleted
 *   - Rollback does NOT restore deleted data (only schema changes)
 *   - Test rollbacks on development database first
 *   - Backup database before rolling back on production
 *
 * Typical Workflow:
 *   1. Developer runs migrations: php roline migration:run
 *   2. Developer discovers issue with migration
 *   3. Developer rolls back: php roline migration:rollback
 *   4. Developer fixes migration file
 *   5. Developer re-runs: php roline migration:run
 *
 * Use Cases:
 *   - Undoing problematic migrations in development
 *   - Rolling back failed deployments
 *   - Testing migration down() methods
 *   - Reverting schema changes temporarily
 *   - Cleaning up development database state
 *
 * Example Output (Single Batch):
 *   Rolling back 2 migration(s)...
 *
 *   → 2025_01_15_140000_create_posts.php
 *   ✓ 2025_01_15_140000_create_posts.php
 *   → 2025_01_15_130000_add_email.php
 *   ✓ 2025_01_15_130000_add_email.php
 *
 *   Rolled back 2 migration(s) successfully!
 *
 * Example Output (Multiple Batches):
 *   php roline migration:rollback 2
 *   Rolling back 5 migration(s)...
 *   (Rolls back last 2 batches)
 *
 * Usage:
 *   php roline migration:rollback       (rollback last batch)
 *   php roline migration:rollback 1     (rollback last batch)
 *   php roline migration:rollback 3     (rollback last 3 batches)
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

class MigrationRollback extends MigrationCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Rollback last migration batch';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing optional steps parameter
     */
    public function usage()
    {
        return '<steps|optional>';
    }

    /**
     * Execute migration rollback
     *
     * Retrieves last-run migrations from tracking table, executes their down()
     * methods in reverse order, and removes them from tracking upon success.
     * Optional steps parameter controls how many batches to rollback.
     *
     * @param array $arguments Command arguments (steps at index 0, optional)
     * @return void Exits with status 0 on success/nothing to rollback, 1 on failure
     */
    public function execute($arguments)
    {
        // Parse steps parameter (default to 1 batch if not provided)
        $steps = isset($arguments[0]) && is_numeric($arguments[0]) ? (int)$arguments[0] : 1;

        // Create Migration utility instance for tracking/execution
        $migration = new Migration();

        // Get migrations to rollback based on steps parameter
        $toRollback = $migration->getLastRanMigrations($steps);

        // Check if there are any migrations to rollback
        if (empty($toRollback)) {
            $this->line();
            $this->info('No migrations to rollback.');
            $this->line();
            exit(0);
        }

        // Build path to migrations directory
        $migrationsDir = getcwd() . '/application/database/migrations';

        // Display count of migrations about to rollback
        $this->line();
        $this->info('Rolling back ' . count($toRollback) . ' migration(s)...');
        $this->line();

        // Track successfully rolled-back migrations count
        $rollbackCount = 0;

        // Execute each migration's down() method in reverse order
        foreach ($toRollback as $file) {
            try {
                // Display migration filename being processed
                $this->info("  → {$file}");

                // Build full file path
                $filepath = $migrationsDir . '/' . $file;

                // Validate migration file still exists
                if (!file_exists($filepath)) {
                    $this->error("  ✗ Migration file not found: {$file}");
                    continue;
                }

                // Execute migration's down() method (reverts changes)
                $migration->rollbackMigration($filepath);

                // Remove migration from tracking table
                $migration->markAsNotRan($file);

                // Display success indicator
                $this->success("  ✓ {$file}");

                // Increment successful rollbacks counter
                $rollbackCount++;

            } catch (\Exception $e) {
                // Rollback failed - display error and stop
                $this->line();
                $this->error("  ✗ Rollback failed: {$file}");
                $this->line();
                $this->error("Error: " . $e->getMessage());
                $this->line();
                exit(1);
            }
        }

        // All rollbacks successful - display summary
        $this->line();
        $this->success("Rolled back {$rollbackCount} migration(s) successfully!");
        $this->line();
    }
}
