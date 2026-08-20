<?php namespace Roline\Commands\Database;

/**
 * DbSeed Command
 *
 * Runs database seeder classes to populate tables with test/sample data. Supports
 * running all seeders or specific individual seeders. Follows convention-based file
 * discovery from application/database/seeders/ directory with DatabaseSeeder running
 * first when executing all seeders.
 *
 * How It Works:
 *   1. Validates seeders directory exists (application/database/seeders/)
 *   2. If seeder name provided: runs only that specific seeder
 *   3. If no name provided: discovers and runs all seeders in directory
 *   4. For all seeders: DatabaseSeeder runs first (master seeder convention)
 *   5. Remaining seeders run in alphabetical order
 *   6. Each seeder's run() method is called to insert data
 *   7. Displays success/failure summary with counts
 *
 * Seeder File Structure:
 *   - Location: application/database/seeders/
 *   - Naming: {Name}Seeder.php (e.g., UsersSeeder.php, PostsSeeder.php)
 *   - Namespace: Seeders\
 *   - Extends: Rackage\Seeder (base class)
 *   - Required method: public function run()
 *
 * Seeder Execution Order:
 *   - DatabaseSeeder ALWAYS runs first (if exists)
 *   - Other seeders run alphabetically
 *   - Ensures master seeder can orchestrate order if needed
 *   - Individual seeder execution bypasses ordering
 *
 * Use Cases:
 *   - Populating fresh database with test data
 *   - Setting up development environment with sample content
 *   - Creating demo data for presentations/screenshots
 *   - Resetting database to known state for testing
 *   - Running specific seeder after schema changes
 *
 * Typical Workflow (Fresh Database):
 *   1. Developer runs: php roline db:drop (clear old data)
 *   2. Runs: php roline migration:run (create schema)
 *   3. Runs: php roline db:seed (populate data)
 *   4. Database now has structure + sample data
 *
 * Typical Workflow (Specific Seeder):
 *   1. Developer adds new table via migration
 *   2. Creates seeder: application/database/seeders/NewTableSeeder.php
 *   3. Runs: php roline db:seed NewTable
 *   4. Only new table gets populated with data
 *
 * Seeder Class Example:
 *   ```php
 *   <?php namespace Seeders;
 *
 *   use Rackage\Seeder;
 *   use Models\Users;
 *
 *   class UsersSeeder extends Seeder {
 *       public function run() {
 *           Users::save([
 *               'username' => 'admin',
 *               'email' => 'admin@example.com',
 *               'password' => Security::hash('password123')
 *           ]);
 *
 *           // Add more users...
 *       }
 *   }
 *   ```
 *
 * DatabaseSeeder (Master Seeder) Pattern:
 *   ```php
 *   <?php namespace Seeders;
 *
 *   use Rackage\Seeder;
 *
 *   class DatabaseSeeder extends Seeder {
 *       public function run() {
 *           // Call other seeders in specific order
 *           $this->call(UsersSeeder::class);
 *           $this->call(CategoriesSeeder::class);
 *           $this->call(PostsSeeder::class); // Depends on users + categories
 *           $this->call(CommentsSeeder::class); // Depends on posts
 *       }
 *   }
 *   ```
 *
 * Error Handling:
 *   - Validates seeders directory exists
 *   - Checks seeder file exists before loading
 *   - Validates seeder class exists after loading
 *   - Confirms run() method exists on seeder class
 *   - Continues through errors when running all seeders (reports count)
 *   - Stops immediately on error when running specific seeder
 *
 * Important Notes:
 *   - Seeders can be run multiple times (handle duplicates appropriately)
 *   - Use Model::save() or raw SQL in run() method
 *   - Seeders are for development/testing (not production data migration)
 *   - Can truncate tables in run() before inserting for idempotency
 *   - DatabaseSeeder naming is convention (not enforced but recommended)
 *
 * Example Output (All Seeders):
 *   Running all seeders...
 *
 *   Running seeder: DatabaseSeeder...
 *   ✓ DatabaseSeeder completed
 *
 *   Running seeder: UsersSeeder...
 *   ✓ UsersSeeder completed
 *
 *   Running seeder: PostsSeeder...
 *   ✓ PostsSeeder completed
 *
 *   All seeders completed successfully! (3 seeders)
 *
 * Example Output (Specific Seeder):
 *   Running seeder: UsersSeeder...
 *   ✓ UsersSeeder completed
 *
 * Usage:
 *   php roline db:seed              (run all seeders)
 *   php roline db:seed Database     (run DatabaseSeeder only)
 *   php roline db:seed Users        (run UsersSeeder only)
 *   php roline db:seed Posts        (run PostsSeeder only)
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Database
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Output;

class DbSeed extends DatabaseCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Run database seeders';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing optional seeder parameter
     */
    public function usage()
    {
        return '<seeder|optional>';
    }

    /**
     * Display detailed help information
     *
     * Shows arguments, seeder location, examples of running all vs specific seeders,
     * and complete seeder class format with namespace and run() method.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <seeder|optional>  Specific seeder class to run (without "Seeder" suffix)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline db:seed              # Run all seeders');
        Output::line('  php roline db:seed Database     # Run DatabaseSeeder only');
        Output::line('  php roline db:seed Users        # Run UsersSeeder only');
        Output::line();

        Output::info('Seeder Location:');
        Output::line('  Seeders should be in: application/database/seeders/');
        Output::line();

        Output::info('Seeder Format:');
        Output::line('  <?php namespace Seeders;');
        Output::line('');
        Output::line('  use Rackage\\Seeder;');
        Output::line('');
        Output::line('  class UsersSeeder extends Seeder {');
        Output::line('      public function run() {');
        Output::line('          // Insert data here');
        Output::line('      }');
        Output::line('  }');
        Output::line();
    }

    /**
     * Execute database seeding
     *
     * Runs seeder classes to populate database with test/sample data. If seeder name
     * provided, runs only that specific seeder. If no name provided, discovers and runs
     * all seeders with DatabaseSeeder executing first. Displays success/failure summary.
     *
     * @param array $arguments Command arguments (seeder name at index 0, optional)
     * @return void Exits with status 0 on success/no seeders, 1 on failure
     */
    public function execute($arguments)
    {
        try {
            // Build path to seeders directory
            $seedersDir = getcwd() . '/application/database/seeders';

            // Check if seeders directory exists
            if (!is_dir($seedersDir)) {
                $this->line();
                $this->error('Seeders directory not found!');
                $this->line();
                $this->info('Expected location: application/database/seeders/');
                $this->line();
                $this->info('Create it first: mkdir -p application/database/seeders');
                $this->line();
                exit(1);
            }

            $this->line();

            // Determine which seeders to run
            if (!empty($arguments[0])) {
                // Run specific seeder only
                $seederName = $arguments[0];
                $this->runSeeder($seederName, $seedersDir);
            } else {
                // Run all seeders in directory
                $this->runAllSeeders($seedersDir);
            }

        } catch (\Exception $e) {
            // Seeding failed (file not found, class error, run() error, etc.)
            $this->line();
            $this->error('Seeding failed!');
            $this->line();
            $this->error('Error: ' . $e->getMessage());
            $this->line();
            exit(1);
        }
    }

    /**
     * Run a specific seeder
     *
     * Loads seeder file, validates class and run() method exist, creates instance,
     * and executes run() method to insert data. Throws exception on any validation
     * failure or execution error.
     *
     * @param string $seederName Seeder name (without "Seeder" suffix)
     * @param string $seedersDir Seeders directory path
     * @return void
     * @throws \Exception If seeder file, class, or run() method not found
     */
    private function runSeeder($seederName, $seedersDir)
    {
        // Build fully-qualified seeder class name
        $seederClass = "Seeders\\{$seederName}Seeder";
        $seederFile = $seedersDir . "/{$seederName}Seeder.php";

        // Check if seeder file exists
        if (!file_exists($seederFile)) {
            throw new \Exception("Seeder file not found: {$seederFile}");
        }

        // Load seeder file
        require_once $seederFile;

        // Check if class exists after loading
        if (!class_exists($seederClass)) {
            throw new \Exception("Seeder class not found: {$seederClass}");
        }

        // Check if class has run() method
        if (!method_exists($seederClass, 'run')) {
            throw new \Exception("Seeder {$seederClass} does not have a run() method");
        }

        // Display seeder execution message
        $this->info("Running seeder: {$seederName}Seeder...");

        // Create seeder instance and execute run() method
        $seeder = new $seederClass();
        $seeder->run();

        // Seeder executed successfully
        $this->success("✓ {$seederName}Seeder completed");
        $this->line();
    }

    /**
     * Run all seeders in the directory
     *
     * Discovers all seeder files in directory, sorts with DatabaseSeeder first,
     * executes each seeder's run() method, and displays success/failure summary.
     * Continues through errors to run as many seeders as possible.
     *
     * @param string $seedersDir Seeders directory path
     * @return void
     * @throws \Exception If no seeders found (not an error, just informative exit)
     */
    private function runAllSeeders($seedersDir)
    {
        // Get all seeder files matching naming convention
        $seederFiles = glob($seedersDir . '/*Seeder.php');

        // Check if any seeders exist
        if (empty($seederFiles)) {
            $this->info('No seeders found in: ' . $seedersDir);
            $this->line();
            $this->info('Create a seeder first.');
            $this->line();
            exit(0);
        }

        // Display count of seeders about to run
        $this->info('Running all seeders...');
        $this->line();

        // Track success and failure counts
        $successCount = 0;
        $failCount = 0;

        // Sort seeders - DatabaseSeeder runs first if exists
        usort($seederFiles, function($a, $b) {
            // Check if either seeder is DatabaseSeeder
            $aIsDatabaseSeeder = strpos($a, 'DatabaseSeeder.php') !== false;
            $bIsDatabaseSeeder = strpos($b, 'DatabaseSeeder.php') !== false;

            // DatabaseSeeder comes before all others
            if ($aIsDatabaseSeeder && !$bIsDatabaseSeeder) {
                return -1;
            }
            if (!$aIsDatabaseSeeder && $bIsDatabaseSeeder) {
                return 1;
            }

            // Both are DatabaseSeeder or neither - sort alphabetically
            return strcmp($a, $b);
        });

        // Execute each seeder sequentially
        foreach ($seederFiles as $seederFile) {
            // Extract seeder name from filename
            $fileName = basename($seederFile, '.php');
            $seederName = str_replace('Seeder', '', $fileName);

            try {
                // Run this seeder
                $this->runSeeder($seederName, $seedersDir);

                // Seeder succeeded
                $successCount++;
            } catch (\Exception $e) {
                // Seeder failed - display error but continue to next seeder
                $this->error("✗ {$fileName} failed: " . $e->getMessage());
                $this->line();

                // Increment failure count
                $failCount++;
            }
        }

        // All seeders processed - display summary
        $this->line();
        if ($failCount === 0) {
            // All seeders succeeded
            $this->success("All seeders completed successfully! ({$successCount} seeders)");
        } else {
            // Some seeders failed
            $this->info("Seeding completed with errors.");
            $this->info("  Success: {$successCount}");
            $this->error("  Failed: {$failCount}");
        }
        $this->line();
    }
}
