<?php namespace Rackage;

/**
 * Base Seeder Class
 *
 * All database seeders extend this base class.
 * Provides utility methods for populating database tables with test/sample data.
 *
 * Usage:
 *
 *   In Application Seeders:
 *     <?php namespace Seeders;
 *
 *     use Rackage\Seeder;
 *     use Models\UserModel;
 *
 *     class UsersSeeder extends Seeder {
 *         public function run() {
 *             // Clear existing data
 *             $this->truncate('users');
 *
 *             // Insert sample users
 *             UserModel::save([
 *                 'username' => 'admin',
 *                 'email' => 'admin@example.com',
 *                 'password' => Security::hash('password123')
 *             ]);
 *         }
 *     }
 *
 * Orchestration Pattern:
 *   The call() method enables running multiple seeders in a specific order.
 *   This is useful when seeders have dependencies on each other.
 *
 *   DatabaseSeeder (Master Seeder):
 *     <?php namespace Seeders;
 *
 *     use Rackage\Seeder;
 *
 *     class DatabaseSeeder extends Seeder {
 *         public function run() {
 *             // Run seeders in dependency order
 *             $this->call(UsersSeeder::class);
 *             $this->call(CategoriesSeeder::class);
 *             $this->call(PostsSeeder::class);  // Depends on users + categories
 *             $this->call(CommentsSeeder::class);  // Depends on posts
 *         }
 *     }
 *
 * Transaction Pattern:
 *   Wrap seeding operations in a transaction to ensure data integrity.
 *   If any error occurs, all changes are rolled back automatically.
 *
 *   public function run() {
 *       $this->transaction(function() {
 *           UserModel::save(['name' => 'John']);
 *           PostModel::save(['title' => 'Hello']);
 *           // If any operation fails, both are rolled back
 *       });
 *   }
 *
 * Available Methods:
 *
 *   ORCHESTRATION:
 *   - call($seederClass)          Run another seeder class
 *
 *   TABLE OPERATIONS:
 *   - truncate($tables)           Clear table(s) before seeding
 *
 *   TRANSACTIONS:
 *   - transaction($callback)      Wrap operations in database transaction
 *
 *   EXECUTION:
 *   - run()                       Override this method in child seeders
 *
 * Best Practices:
 *   - Use truncate() to ensure idempotent seeding (can run multiple times)
 *   - Use transactions for related data to maintain referential integrity
 *   - Use call() in DatabaseSeeder to orchestrate dependencies
 *   - Keep seeders focused on one table or related group of tables
 *
 * Running Seeders:
 *   php roline db:seed              Run all seeders
 *   php roline db:seed Users        Run UsersSeeder only
 *   php roline db:seed Database     Run DatabaseSeeder only
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Seeder
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\Exceptions\HelperException;

class Seeder
{

    /**
     * Call another seeder class
     *
     * Instantiates and executes the specified seeder's run() method.
     * Useful for orchestrating multiple seeders in a specific order from
     * a master seeder (typically DatabaseSeeder).
     *
     * This allows you to manage dependencies between seeders - for example,
     * ensuring users are created before posts, categories before products, etc.
     *
     * Example:
     *   public function run() {
     *       $this->call(UsersSeeder::class);
     *       $this->call(CategoriesSeeder::class);
     *       $this->call(PostsSeeder::class);  // Depends on users + categories
     *   }
     *
     * @param string $seederClass Fully qualified seeder class name
     * @return void
     * @throws HelperException If seeder class doesn't exist or run() method missing
     */
    public function call($seederClass)
    {
        // Extract seeder name from fully qualified class name
        // Example: "Seeders\UsersSeeder" -> "UsersSeeder"
        $parts = explode('\\', $seederClass);
        $className = end($parts);

        // Build path to seeder file
        $seederFile = getcwd() . '/application/database/seeders/' . $className . '.php';

        // Load seeder file if not already loaded
        if (!class_exists($seederClass)) {
            if (!file_exists($seederFile)) {
                throw new HelperException("Seeder file not found: {$seederFile}");
            }
            require_once $seederFile;
        }

        // Validate seeder class exists after loading
        if (!class_exists($seederClass)) {
            throw new HelperException("Seeder class not found: {$seederClass}");
        }

        // Instantiate the seeder
        $seeder = new $seederClass();

        // Validate run() method exists
        if (!method_exists($seeder, 'run')) {
            throw new HelperException("Seeder {$seederClass} does not have a run() method");
        }

        // Execute the seeder
        $seeder->run();
    }

    /**
     * Truncate table(s)
     *
     * Removes all rows from the specified table(s) and resets auto-increment counters.
     * This is useful for ensuring idempotent seeding - you can run seeders multiple
     * times without creating duplicate data.
     *
     * TRUNCATE is faster than DELETE and resets auto-increment, but:
     * - Cannot be rolled back (even in transactions)
     * - Requires appropriate permissions
     * - Will fail if foreign key constraints exist (disable checks first if needed)
     *
     * Example:
     *   $this->truncate('users');              // Single table
     *   $this->truncate(['users', 'posts']);   // Multiple tables
     *
     * @param string|array $tables Single table name or array of table names
     * @return void
     */
    public function truncate($tables)
    {
        // Normalize to array for consistent processing
        $tables = is_array($tables) ? $tables : [$tables];

        // Truncate each table
        foreach ($tables as $table) {
            Model::sql("TRUNCATE TABLE {$table}");
        }
    }

    /**
     * Execute callback within database transaction
     *
     * Wraps the provided callback in a database transaction. If any operation
     * fails (throws an exception), all changes are rolled back automatically.
     * This ensures data integrity when seeding related tables.
     *
     * Note: TRUNCATE operations cannot be rolled back, even when used inside
     * a transaction. Use DELETE instead if you need rollback capability.
     *
     * Example:
     *   $this->transaction(function() {
     *       UserModel::save(['name' => 'John', 'email' => 'john@example.com']);
     *       PostModel::save(['user_id' => 1, 'title' => 'First Post']);
     *       // If post fails, user is also rolled back
     *   });
     *
     * @param callable $callback Function containing seeding operations
     * @return void
     * @throws HelperException Re-throws any exception from callback after rollback
     */
    public function transaction($callback)
    {
        // Begin transaction
        Model::begin();

        try {
            // Execute callback
            $callback();

            // Commit if successful
            Model::commit();
        } catch (\Exception $e) {
            // Rollback on error
            Model::rollback();

            // Re-throw as HelperException
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Run seeder
     *
     * Override this method in child seeder classes to define seeding logic.
     * This method is called by the db:seed command when executing seeders.
     *
     * Example:
     *   public function run() {
     *       $this->truncate('users');
     *       UserModel::save(['name' => 'Admin', 'email' => 'admin@example.com']);
     *       UserModel::save(['name' => 'User', 'email' => 'user@example.com']);
     *   }
     *
     * @return void
     */
    public function run()
    {
        // Override this method in child seeder classes
    }
}
