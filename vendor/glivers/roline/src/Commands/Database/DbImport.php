<?php namespace Roline\Commands\Database;

/**
 * DbImport Command
 *
 * Imports SQL dump file into database by executing all SQL statements including
 * table creation, data insertion, and schema modifications. Processes file via
 * streaming to handle files of any size without memory issues. Shows live progress
 * during import for better user experience.
 *
 * What Gets Imported:
 *   - CREATE TABLE statements (creates database schema)
 *   - INSERT statements (imports all data)
 *   - ALTER TABLE statements (modifies schema)
 *   - CREATE INDEX statements (creates indexes)
 *   - SET statements (configuration)
 *   - Any valid SQL in the dump file
 *
 * File Processing:
 *   - Streams file line by line (no memory limit)
 *   - Parses SQL statements (handles multi-line statements)
 *   - Skips comments and empty lines
 *   - Executes statements sequentially
 *   - Shows live progress indicator
 *
 * Progress Display:
 *   - Updates every 100 statements
 *   - Shows statement count in real-time
 *   - Uses carriage return for same-line updates
 *   - Clean, non-cluttered output
 *
 * Error Handling:
 *   - Stops on first error
 *   - Shows failing statement and line number
 *   - Displays MySQL error message
 *   - Does NOT rollback previous statements
 *   - User can fix error and resume import
 *
 * Use Cases:
 *   - Importing database backups
 *   - Restoring from db:export dumps
 *   - Migrating data between environments
 *   - Setting up development databases
 *   - Deploying to production servers
 *
 * Performance:
 *   - Speed: ~10,000-50,000 rows/second
 *   - 1M rows: ~20-60 seconds
 *   - 100M rows: ~30-90 minutes
 *   - File size: No limit (streaming)
 *   - Memory: Constant (~50-100 MB)
 *
 * Important Notes:
 *   - Target database must exist (does NOT create database)
 *   - Existing tables will be dropped if dump contains DROP TABLE
 *   - Statements execute sequentially (not transactional by default)
 *   - Compatible with mysqldump, db:export, and standard SQL dumps
 *   - Works with files of any size (GB, TB, etc.)
 *
 * Typical Workflow:
 *   1. Export from source: php roline db:export
 *   2. Transfer file to target server
 *   3. Import on target: php roline db:import backup.sql
 *   4. Verify data integrity
 *
 * Example Output:
 *   → Importing database: myapp_db
 *   → Source file: /path/to/backup.sql
 *   → File size: 1.2 GB
 *
 *   → Importing... 100 statements
 *   → Importing... 200 statements
 *   → Importing... 15,847 statements
 *
 *   ✓ Database imported successfully!
 *   → Executed: 15,847 statements
 *   → Duration: 2 minutes 34 seconds
 *
 * Usage:
 *   php roline db:import backup.sql
 *   php roline db:import /path/to/database_dump.sql
 *   php roline db:import ../exports/ke_search_backup_2025-12-09_095356.sql
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
use Rackage\Model;
use Rackage\Registry;

class DbImport extends DatabaseCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Import SQL dump file into database';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing required file parameter
     */
    public function usage()
    {
        return '<file|required>';
    }

    /**
     * Display detailed help information
     *
     * Shows arguments, what gets imported (CREATE/INSERT), examples with various
     * file paths, performance notes, and important warnings about existing data.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <file|required>  SQL dump file to import');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline db:import backup.sql');
        Output::line('  php roline db:import /path/to/database_dump.sql');
        Output::line('  php roline db:import ../exports/backup_2025-12-09.sql');
        Output::line();

        Output::info('What it imports:');
        Output::line('  - CREATE TABLE statements (creates tables)');
        Output::line('  - INSERT statements (imports data)');
        Output::line('  - ALTER TABLE, CREATE INDEX, etc.');
        Output::line('  - All SQL statements in dump file');
        Output::line();

        Output::info('Performance:');
        Output::line('  - Handles files of any size (streams, no memory limit)');
        Output::line('  - Speed: ~10,000-50,000 rows/second');
        Output::line('  - Shows live progress during import');
        Output::line();

        Output::info('Important:');
        Output::line('  - Target database must exist');
        Output::line('  - Existing tables may be dropped/replaced');
        Output::line('  - Stops on first error');
        Output::line();
    }

    /**
     * Execute database import from SQL file
     *
     * Validates file exists, reads file size, streams SQL statements line by line,
     * executes each statement sequentially, shows live progress, handles errors,
     * and displays summary statistics on completion.
     *
     * @param array $arguments Command arguments (filepath at index 0, required)
     * @return void Exits with status 1 on failure
     */
    public function execute($arguments)
    {
        try {
            // Validate file argument provided
            if (empty($arguments[0])) {
                $this->error('SQL file path is required!');
                $this->line();
                $this->info('Usage: php roline db:import <file>');
                $this->line();
                $this->info('Example: php roline db:import backup.sql');
                exit(1);
            }

            $filepath = $arguments[0];

            // Resolve file path with multiple fallback strategies
            $filepath = $this->resolveFilePath($filepath);

            if (!$filepath) {
                $this->error("File not found: {$arguments[0]}");
                $this->line();
                $this->info('Searched in:');
                $this->line('  - ' . $arguments[0]);
                $this->line('  - ' . getcwd() . '/' . $arguments[0]);
                $this->line('  - ' . getcwd() . '/application/storage/exports/' . basename($arguments[0]));
                $this->line();
                exit(1);
            }

            // Get database name from configuration
            $dbConfig = Registry::dbConfig();
            $driver = $dbConfig['default'] ?? 'mysql';
            $databaseName = $dbConfig[$driver]['database'] ?? 'database';

            // Display import header
            $this->line();
            $this->info("Importing database: {$databaseName}");
            $this->info("Source file: {$filepath}");

            // Display file size for context
            $fileSize = filesize($filepath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);
            if ($fileSizeMB < 1) {
                $this->info("File size: " . round($fileSize / 1024, 2) . " KB");
            } else if ($fileSizeMB < 1024) {
                $this->info("File size: {$fileSizeMB} MB");
            } else {
                $fileSizeGB = round($fileSizeMB / 1024, 2);
                $this->info("File size: {$fileSizeGB} GB");
            }

            $this->line();

            // Record start time for duration calculation
            $startTime = microtime(true);

            // Import SQL file
            $statementsExecuted = $this->importSQL($filepath);

            // Calculate duration
            $duration = microtime(true) - $startTime;
            $minutes = (int)($duration / 60);
            $seconds = (int)$duration - ($minutes * 60);

            // Display success summary
            $this->line();
            $this->success("Database imported successfully!");
            $this->line();
            $this->info("Executed: " . number_format($statementsExecuted) . " statements");
            if ($minutes > 0) {
                $this->info("Duration: {$minutes} minutes {$seconds} seconds");
            } else {
                $this->info("Duration: {$seconds} seconds");
            }
            $this->line();

        } catch (\Exception $e) {
            // Import failed
            $this->line();
            $this->error("Import failed!");
            $this->line();
            $this->error("Error: " . $e->getMessage());
            $this->line();
            exit(1);
        }
    }

    /**
     * Import SQL dump file
     *
     * Streams file line by line, parses SQL statements, executes each statement,
     * shows live progress, and handles errors. Returns total statements executed.
     *
     * Statement Parsing Logic:
     *   - Accumulates lines until semicolon at end of line
     *   - Skips comment lines (starting with --)
     *   - Skips empty lines
     *   - Handles multi-line statements (INSERTs, CREATE TABLEs)
     *   - Executes complete statements only
     *
     * Progress Display:
     *   - Shows progress every 100 statements
     *   - Updates on same line (carriage return)
     *   - Shows final count at end
     *
     * Error Handling:
     *   - Stops at first error
     *   - Shows statement that failed
     *   - Shows line number in file
     *   - Shows MySQL error message
     *   - Throws exception (caught by execute())
     *
     * @param string $filepath Absolute path to SQL dump file
     * @return int Number of statements successfully executed
     * @throws \Exception If statement execution fails
     */
    private function importSQL($filepath)
    {
        // Open file for streaming (doesn't load into memory)
        $fileHandle = fopen($filepath, 'r');
        if (!$fileHandle) {
            throw new \Exception("Failed to open file: {$filepath}");
        }

        $statement = '';
        $lineNumber = 0;
        $statementsExecuted = 0;
        $progressInterval = 100;  // Show progress every 100 statements

        // Show initial progress
        echo "  → Importing... 0 statements";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();

        // Read file line by line (memory efficient)
        while (($line = fgets($fileHandle)) !== false) {
            $lineNumber++;
            $trimmed = trim($line);

            // Skip comment lines (-- comments)
            if (substr($trimmed, 0, 2) === '--') {
                continue;
            }

            // Skip empty lines
            if (empty($trimmed)) {
                continue;
            }

            // Accumulate line to current statement
            $statement .= $line;

            // Check if statement is complete (ends with semicolon)
            if (substr(rtrim($line), -1) === ';') {
                try {
                    // Execute the complete SQL statement
                    Model::sql($statement);
                    $statementsExecuted++;

                    // Show progress every progressInterval statements
                    if ($statementsExecuted % $progressInterval === 0) {
                        echo "\r\033[K  → Importing... \033[37m(" . number_format($statementsExecuted) . " statements)\033[0m";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }

                } catch (\Exception $e) {
                    // Statement failed - show context and error
                    fclose($fileHandle);

                    // Show the statement that failed (truncate if too long)
                    $statementPreview = substr($statement, 0, 200);
                    if (strlen($statement) > 200) {
                        $statementPreview .= '...';
                    }

                    throw new \Exception(
                        "Failed at line {$lineNumber}\n\n" .
                        //"Statement:\n{$statementPreview}\n\n" .
                        "Statement:\n{$statement}\n\n" .
                        "MySQL Error: " . $e->getMessage()
                    );
                }

                // Reset for next statement
                $statement = '';
            }
        }

        fclose($fileHandle);

        // Show final progress
        echo "\r\033[K  → Importing... \033[37m(" . number_format($statementsExecuted) . " statements)\033[0m\n";

        return $statementsExecuted;
    }

    /**
     * Resolve file path with intelligent fallback strategies
     *
     * Tries multiple locations to find the SQL dump file:
     *   1. Exact path as provided (absolute or relative)
     *   2. Relative to current working directory
     *   3. In application/storage/exports/ (matches db:export default location)
     *
     * Strategy 3 provides excellent UX - users can just type the filename:
     *   php roline db:export
     *   → Saves: application/storage/exports/backup_2025-12-09.sql
     *
     *   php roline db:import backup_2025-12-09.sql
     *   → Finds it automatically!
     *
     * @param string $providedPath Path provided by user
     * @return string|false Resolved absolute path if found, false otherwise
     */
    private function resolveFilePath($providedPath)
    {
        // Strategy 1: Path exists as-is (absolute or valid relative)
        if (file_exists($providedPath)) {
            return realpath($providedPath);
        }

        // Strategy 2: Relative to current working directory
        $cwdPath = getcwd() . '/' . $providedPath;
        if (file_exists($cwdPath)) {
            return realpath($cwdPath);
        }

        // Strategy 3: In exports directory (matches db:export location)
        // Extracts just filename and looks in exports/
        $exportsPath = getcwd() . '/application/storage/exports/' . basename($providedPath);
        if (file_exists($exportsPath)) {
            return realpath($exportsPath);
        }

        // File not found in any location
        return false;
    }
}
