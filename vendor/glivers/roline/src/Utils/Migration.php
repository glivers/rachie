<?php namespace Roline\Utils;

use Rackage\Registry;
use Rackage\Model;

/**
 * Migration
 *
 * Manages migration tracking table and migration execution.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Roline
 * @package Roline\Utils
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */
class Migration
{
    /**
     * Migrations table name
     * @var string
     */
    private $table;

    /**
     * Constructor
     */
    public function __construct()
    {
        $dbConfig = Registry::dbConfig();
        $this->table = $dbConfig['migrations_table'] ?? 'migrations';
    }

    /**
     * Ensure migrations table exists
     *
     * @return void
     */
    public function ensureTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `version` VARCHAR(255) NOT NULL,
            `applied_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        Model::sql($sql);
    }

    /**
     * Get all ran migrations
     *
     * @return array Array of migration versions
     */
    public function getRanMigrations()
    {
        $this->ensureTableExists();

        $result = Model::sql("SELECT `version` FROM `{$this->table}` ORDER BY `id` ASC");

        $migrations = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $migrations[] = $row['version'];
            }
        }

        return $migrations;
    }

    /**
     * Get pending migration files
     *
     * @param string $migrationsPath Path to migrations directory
     * @return array Array of pending migration filenames
     */
    public function getPendingMigrations($migrationsPath)
    {
        $ranMigrations = $this->getRanMigrations();

        $allMigrations = [];
        if (is_dir($migrationsPath)) {
            $files = scandir($migrationsPath);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $allMigrations[] = $file;
                }
            }
        }

        sort($allMigrations);

        // Return migrations that haven't been run yet
        return array_diff($allMigrations, $ranMigrations);
    }

    /**
     * Mark migration as ran
     *
     * @param string $version Migration filename
     * @return void
     */
    public function markAsRan($version)
    {
        $this->ensureTableExists();

        $escapedVersion = addslashes($version);
        $sql = "INSERT INTO `{$this->table}` (`version`, `applied_at`) VALUES ('{$escapedVersion}', NOW())";
        Model::sql($sql);
    }

    /**
     * Remove migration from log (for rollback)
     *
     * @param string $version Migration filename
     * @return void
     */
    public function markAsNotRan($version)
    {
        $escapedVersion = addslashes($version);
        $sql = "DELETE FROM `{$this->table}` WHERE `version` = '{$escapedVersion}'";
        Model::sql($sql);
    }

    /**
     * Get last N ran migrations (for rollback)
     *
     * @param int $count Number of migrations to get
     * @return array Array of migration versions
     */
    public function getLastRanMigrations($count = 1)
    {
        $this->ensureTableExists();

        $sql = "SELECT `version` FROM `{$this->table}` ORDER BY `id` DESC LIMIT " . (int)$count;
        $result = Model::sql($sql);

        $migrations = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $migrations[] = $row['version'];
            }
        }

        return $migrations;
    }

    /**
     * Run a migration file
     *
     * @param string $migrationPath Full path to migration file
     * @return void
     * @throws \Exception If migration fails
     */
    public function runMigration($migrationPath)
    {
        if (!file_exists($migrationPath)) {
            throw new \Exception("Migration file not found: {$migrationPath}");
        }

        // Include the migration file
        require_once $migrationPath;

        // Call the up() function to apply migration
        if (function_exists('up')) {
            up();
        }
    }

    /**
     * Rollback a migration file
     *
     * @param string $migrationPath Full path to migration file
     * @return void
     * @throws \Exception If rollback fails
     */
    public function rollbackMigration($migrationPath)
    {
        if (!file_exists($migrationPath)) {
            throw new \Exception("Migration file not found: {$migrationPath}");
        }

        // Include the migration file
        require_once $migrationPath;

        // Call the down() function to rollback migration
        if (function_exists('down')) {
            down();
        }
    }
}
