<?php namespace Rackage\FileSystem;

/**
 * File Handler
 *
 * Handles file and directory operations including reading, writing, copying, deleting,
 * listing, and information retrieval. All methods are static and return FileResponse objects.
 *
 * Error Handling:
 *   - Normal failures (file not found, can't read) → Returned in FileResponse
 *   - System errors (invalid arguments, unexpected failures) → Throws FileException
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\File
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

use Rackage\FileSystem\FileResponse;
use Rackage\FileSystem\FileException;

class FileHandler {

    /**
     * Private constructor - prevent instantiation
     *
     * @return void
     */
    private function __construct() {}

    // =========================================================================
    // READING FILES
    // =========================================================================

    /**
     * Read entire file contents
     *
     * Examples:
     *   $result = FileHandler::read('config.json');
     *   if ($result->success) {
     *       $config = json_decode($result->content, true);
     *   }
     *
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function read($path)
    {
        if (!file_exists($path)) {
            return FileResponse::error("File not found: {$path}", $path);
        }

        if (!is_readable($path)) {
            return FileResponse::error("File not readable: {$path}", $path);
        }

        $content = @file_get_contents($path);

        if ($content === false) {
            return FileResponse::error("Failed to read file: {$path}", $path);
        }

        $response = FileResponse::success($content, $path);
        $response->size = strlen($content);
        return $response;
    }

    /**
     * Read file as array of lines
     *
     * Examples:
     *   $result = FileHandler::readLines('data.txt');
     *   if ($result->success) {
     *       foreach ($result->content as $line) {
     *           echo $line;
     *       }
     *   }
     *
     * @param string $path Path to file
     * @return FileResponse Response with content as array
     */
    public static function readLines($path)
    {
        if (!file_exists($path)) {
            return FileResponse::error("File not found: {$path}", $path);
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return FileResponse::error("Failed to read file: {$path}", $path);
        }

        return FileResponse::success($lines, $path);
    }

    /**
     * Read and decode JSON file
     *
     * Examples:
     *   $result = FileHandler::readJson('config.json');
     *   if ($result->success) {
     *       $config = $result->content;  // Already decoded
     *   }
     *
     * @param string $path Path to JSON file
     * @param bool $assoc Return associative array (default true)
     * @return FileResponse Response with decoded JSON
     */
    public static function readJson($path, $assoc = true)
    {
        $result = static::read($path);

        if (!$result->success) {
            return $result;
        }

        $decoded = json_decode($result->content, $assoc);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return FileResponse::error("Invalid JSON in file: " . json_last_error_msg(), $path);
        }

        return FileResponse::success($decoded, $path);
    }

    /**
     * Read CSV file as array
     *
     * Examples:
     *   $result = FileHandler::readCsv('users.csv');
     *   if ($result->success) {
     *       foreach ($result->content as $row) {
     *           echo $row[0];  // First column
     *       }
     *   }
     *
     * @param string $path Path to CSV file
     * @param string $delimiter Field delimiter (default comma)
     * @return FileResponse Response with CSV data as array
     */
    public static function readCsv($path, $delimiter = ',')
    {
        if (!file_exists($path)) {
            return FileResponse::error("File not found: {$path}", $path);
        }

        $handle = @fopen($path, 'r');

        if ($handle === false) {
            return FileResponse::error("Failed to open file: {$path}", $path);
        }

        $data = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $data[] = $row;
        }

        fclose($handle);

        return FileResponse::success($data, $path);
    }

    /**
     * Process large file line by line
     *
     * Memory efficient for large files - doesn't load entire file into memory.
     *
     * Examples:
     *   FileHandler::lines('huge.log', function($line, $number) {
     *       if (str_contains($line, 'ERROR')) {
     *           echo "Line {$number}: {$line}";
     *       }
     *   });
     *
     * @param string $path Path to file
     * @param callable $callback Function to call for each line (line, lineNumber)
     * @return FileResponse
     */
    public static function lines($path, callable $callback)
    {
        if (!file_exists($path)) {
            return FileResponse::error("File not found: {$path}", $path);
        }

        $handle = @fopen($path, 'r');

        if ($handle === false) {
            return FileResponse::error("Failed to open file: {$path}", $path);
        }

        $lineNumber = 0;
        while (($line = fgets($handle)) !== false) {
            $callback(rtrim($line, "\r\n"), $lineNumber++);
        }

        fclose($handle);

        return FileResponse::success('', $path);
    }

    // =========================================================================
    // WRITING FILES
    // =========================================================================

    /**
     * Write content to file (create or overwrite)
     *
     * Examples:
     *   $result = FileHandler::write('output.txt', 'Hello World');
     *   $result = FileHandler::write('data.json', json_encode($data));
     *
     * @param string $path Path to file
     * @param string $content Content to write
     * @return FileResponse
     */
    public static function write($path, $content)
    {
        $bytes = @file_put_contents($path, $content);

        if ($bytes === false) {
            return FileResponse::error("Failed to write file: {$path}", $path);
        }

        $response = FileResponse::success('', $path);
        $response->size = $bytes;
        return $response;
    }

    /**
     * Append content to end of file
     *
     * Examples:
     *   FileHandler::append('log.txt', "New log entry\n");
     *
     * @param string $path Path to file
     * @param string $content Content to append
     * @return FileResponse
     */
    public static function append($path, $content)
    {
        $bytes = @file_put_contents($path, $content, FILE_APPEND);

        if ($bytes === false) {
            return FileResponse::error("Failed to append to file: {$path}", $path);
        }

        return FileResponse::success('', $path);
    }

    /**
     * Prepend content to beginning of file
     *
     * Examples:
     *   FileHandler::prepend('output.txt', "Header\n");
     *
     * @param string $path Path to file
     * @param string $content Content to prepend
     * @return FileResponse
     */
    public static function prepend($path, $content)
    {
        if (file_exists($path)) {
            $existing = file_get_contents($path);
            $content = $content . $existing;
        }

        return static::write($path, $content);
    }

    /**
     * Write data to JSON file
     *
     * Examples:
     *   FileHandler::writeJson('config.json', $config, true);
     *
     * @param string $path Path to JSON file
     * @param mixed $data Data to encode
     * @param bool $pretty Pretty print JSON (default false)
     * @return FileResponse
     */
    public static function writeJson($path, $data, $pretty = false)
    {
        $flags = $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : 0;
        $json = json_encode($data, $flags);

        if ($json === false) {
            return FileResponse::error("Failed to encode JSON: " . json_last_error_msg(), $path);
        }

        return static::write($path, $json);
    }

    /**
     * Write array to CSV file
     *
     * Examples:
     *   $data = [
     *       ['Name', 'Email'],
     *       ['John', 'john@example.com']
     *   ];
     *   FileHandler::writeCsv('users.csv', $data);
     *
     * @param string $path Path to CSV file
     * @param array $data Array of rows
     * @param string $delimiter Field delimiter (default comma)
     * @return FileResponse
     */
    public static function writeCsv($path, array $data, $delimiter = ',')
    {
        $handle = @fopen($path, 'w');

        if ($handle === false) {
            return FileResponse::error("Failed to create file: {$path}", $path);
        }

        foreach ($data as $row) {
            fputcsv($handle, $row, $delimiter);
        }

        fclose($handle);

        return FileResponse::success('', $path);
    }

    // =========================================================================
    // FILE OPERATIONS
    // =========================================================================

    /**
     * Copy file to new location
     *
     * Examples:
     *   FileHandler::copy('source.txt', 'backup.txt');
     *   FileHandler::copy('config.json', 'config.backup.json');
     *
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @return FileResponse
     */
    public static function copy($source, $destination)
    {
        if (!file_exists($source)) {
            return FileResponse::error("Source file not found: {$source}", $source);
        }

        if (@copy($source, $destination)) {
            return FileResponse::success('', $destination);
        }

        return FileResponse::error("Failed to copy file from {$source} to {$destination}", $source);
    }

    /**
     * Move/rename file
     *
     * Examples:
     *   FileHandler::move('old.txt', 'new.txt');
     *   FileHandler::move('temp/file.txt', 'permanent/file.txt');
     *
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @return FileResponse
     */
    public static function move($source, $destination)
    {
        if (!file_exists($source)) {
            return FileResponse::error("Source file not found: {$source}", $source);
        }

        if (@rename($source, $destination)) {
            return FileResponse::success('', $destination);
        }

        return FileResponse::error("Failed to move file from {$source} to {$destination}", $source);
    }

    /**
     * Delete file
     *
     * Examples:
     *   FileHandler::delete('temp.txt');
     *   FileHandler::delete(['temp1.txt', 'temp2.txt']);
     *
     * @param string|array $paths File path or array of paths
     * @return FileResponse
     */
    public static function delete($paths)
    {
        $paths = is_array($paths) ? $paths : [$paths];
        $failed = [];

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;  // Already deleted, skip
            }

            if (!@unlink($path)) {
                $failed[] = $path;
            }
        }

        if (!empty($failed)) {
            return FileResponse::error("Failed to delete files: " . implode(', ', $failed));
        }

        return FileResponse::success();
    }

    // =========================================================================
    // FILE INFORMATION
    // =========================================================================

    /**
     * Check if file exists
     *
     * Examples:
     *   $result = FileHandler::exists('config.json');
     *   if ($result->exists) { ... }
     *
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function exists($path)
    {
        $response = FileResponse::success('', $path);
        $response->exists = file_exists($path);
        return $response;
    }

    /**
     * Check if file is missing
     *
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function missing($path)
    {
        $response = FileResponse::success('', $path);
        $response->exists = !file_exists($path);
        return $response;
    }

    /**
     * Check if path is a file
     *
     * @param string $path Path to check
     * @return FileResponse
     */
    public static function isFile($path)
    {
        $response = FileResponse::success('', $path);
        $response->isFile = is_file($path);
        return $response;
    }

    /**
     * Check if path is a directory
     *
     * @param string $path Path to check
     * @return FileResponse
     */
    public static function isDir($path)
    {
        $response = FileResponse::success('', $path);
        $response->isDir = is_dir($path);
        return $response;
    }

    /**
     * Check if file is readable
     *
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function isReadable($path)
    {
        $response = FileResponse::success('', $path);
        $response->success = is_readable($path);
        return $response;
    }

    /**
     * Check if file is writable
     *
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function isWritable($path)
    {
        $response = FileResponse::success('', $path);
        $response->success = is_writable($path);
        return $response;
    }

    /**
     * Get file size in bytes
     *
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function size($path)
    {
        if (!file_exists($path)) {
            return FileResponse::error("File not found: {$path}", $path);
        }

        $size = @filesize($path);

        if ($size === false) {
            return FileResponse::error("Failed to get file size: {$path}", $path);
        }

        $response = FileResponse::success('', $path);
        $response->size = $size;
        return $response;
    }

    /**
     * Get file extension
     *
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function extension($path)
    {
        $response = FileResponse::success('', $path);
        $response->extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $response;
    }

    /**
     * Get filename without extension
     *
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function name($path)
    {
        $response = FileResponse::success('', $path);
        $response->content = pathinfo($path, PATHINFO_FILENAME);
        return $response;
    }

    /**
     * Get filename with extension
     *
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function basename($path)
    {
        $response = FileResponse::success('', $path);
        $response->content = pathinfo($path, PATHINFO_BASENAME);
        return $response;
    }

    /**
     * Get file MIME type
     *
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function mimeType($path)
    {
        if (!file_exists($path)) {
            return FileResponse::error("File not found: {$path}", $path);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        $response = FileResponse::success('', $path);
        $response->mimeType = $mime;
        return $response;
    }

    /**
     * Get last modified timestamp
     *
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function lastModified($path)
    {
        if (!file_exists($path)) {
            return FileResponse::error("File not found: {$path}", $path);
        }

        $time = @filemtime($path);

        if ($time === false) {
            return FileResponse::error("Failed to get modification time: {$path}", $path);
        }

        $response = FileResponse::success('', $path);
        $response->lastModified = $time;
        return $response;
    }

    /**
     * Get file hash
     *
     * Examples:
     *   FileHandler::hash('file.txt', 'sha256');
     *   FileHandler::hash('file.txt', 'md5');
     *
     * @param string $path Path to file
     * @param string $algo Hash algorithm (default sha256)
     * @return FileResponse
     */
    public static function hash($path, $algo = 'sha256')
    {
        if (!file_exists($path)) {
            return FileResponse::error("File not found: {$path}", $path);
        }

        $hash = @hash_file($algo, $path);

        if ($hash === false) {
            return FileResponse::error("Failed to hash file: {$path}", $path);
        }

        return FileResponse::success($hash, $path);
    }

    // =========================================================================
    // ADVANCED OPERATIONS
    // =========================================================================

    /**
     * Change file permissions
     *
     * Examples:
     *   FileHandler::chmod('script.sh', 0755);
     *   FileHandler::chmod('config.php', 0644);
     *
     * @param string $path Path to file
     * @param int $permissions Octal permissions (e.g., 0644, 0755)
     * @return FileResponse
     */
    public static function chmod($path, $permissions)
    {
        if (!file_exists($path)) {
            return FileResponse::error("File not found: {$path}", $path);
        }

        if (@chmod($path, $permissions)) {
            return FileResponse::success('', $path);
        }

        return FileResponse::error("Failed to change permissions: {$path}", $path);
    }

    /**
     * Get file permissions
     *
     * @param string $path Path to file
     * @return FileResponse Response with permissions as integer
     */
    public static function getPermissions($path)
    {
        if (!file_exists($path)) {
            return FileResponse::error("File not found: {$path}", $path);
        }

        $perms = @fileperms($path);

        if ($perms === false) {
            return FileResponse::error("Failed to get permissions: {$path}", $path);
        }

        $response = FileResponse::success('', $path);
        $response->content = substr(sprintf('%o', $perms), -4);  // Last 4 digits
        return $response;
    }

    /**
     * Read file with shared lock
     *
     * Safe concurrent reading - multiple processes can read simultaneously.
     *
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function sharedGet($path)
    {
        if (!file_exists($path)) {
            return FileResponse::error("File not found: {$path}", $path);
        }

        $handle = @fopen($path, 'r');

        if ($handle === false) {
            return FileResponse::error("Failed to open file: {$path}", $path);
        }

        flock($handle, LOCK_SH);
        $content = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return FileResponse::success($content, $path);
    }

    /**
     * Write file with exclusive lock
     *
     * Safe concurrent writing - only one process can write at a time.
     *
     * @param string $path Path to file
     * @param string $content Content to write
     * @return FileResponse
     */
    public static function exclusivePut($path, $content)
    {
        $handle = @fopen($path, 'w');

        if ($handle === false) {
            return FileResponse::error("Failed to open file: {$path}", $path);
        }

        flock($handle, LOCK_EX);
        fwrite($handle, $content);
        flock($handle, LOCK_UN);
        fclose($handle);

        return FileResponse::success('', $path);
    }

    /**
     * Find and replace in file
     *
     * @param string $search Search string
     * @param string $replace Replacement string
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function replace($search, $replace, $path)
    {
        $result = static::read($path);

        if (!$result->success) {
            return $result;
        }

        $newContent = str_replace($search, $replace, $result->content);

        return static::write($path, $newContent);
    }

    /**
     * Find and replace using regex pattern
     *
     * @param string $pattern Regex pattern
     * @param string $replace Replacement string
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function replacePattern($pattern, $replace, $path)
    {
        $result = static::read($path);

        if (!$result->success) {
            return $result;
        }

        $newContent = preg_replace($pattern, $replace, $result->content);

        return static::write($path, $newContent);
    }

    /**
     * Require PHP file
     *
     * @param string $path Path to PHP file
     * @return mixed Result of require
     * @throws FileException If file not found
     */
    public static function requireFile($path)
    {
        if (!file_exists($path)) {
            throw new FileException("File not found: {$path}");
        }

        return require $path;
    }

    /**
     * Require PHP file once
     *
     * @param string $path Path to PHP file
     * @return mixed Result of require_once
     * @throws FileException If file not found
     */
    public static function requireOnce($path)
    {
        if (!file_exists($path)) {
            throw new FileException("File not found: {$path}");
        }

        return require_once $path;
    }

    // =========================================================================
    // DIRECTORY CREATION
    // =========================================================================

    /**
     * Create directory
     *
     * Creates a directory with specified permissions.
     * Recursive by default - creates parent directories if needed.
     *
     * Examples:
     *   FileHandler::makeDir('storage/uploads');
     *   FileHandler::makeDir('cache/views', 0755, true);
     *
     * @param string $path Directory path
     * @param int $permissions Directory permissions (default 0755)
     * @param bool $recursive Create parent directories (default true)
     * @return FileResponse
     */
    public static function makeDir($path, $permissions = 0755, $recursive = true)
    {
        if (is_dir($path)) {
            return FileResponse::success('', $path);
        }

        if (@mkdir($path, $permissions, $recursive)) {
            return FileResponse::success('', $path);
        }

        return FileResponse::error("Failed to create directory: {$path}", $path);
    }

    /**
     * Ensure directory exists
     *
     * Creates directory only if it doesn't exist.
     * Convenient wrapper for makeDir that doesn't fail if already exists.
     *
     * Examples:
     *   FileHandler::ensureDir('storage/cache');
     *   FileHandler::ensureDir('vault/logs', 0755);
     *
     * @param string $path Directory path
     * @param int $permissions Directory permissions (default 0755)
     * @return FileResponse
     */
    public static function ensureDir($path, $permissions = 0755)
    {
        return static::makeDir($path, $permissions, true);
    }

    // =========================================================================
    // DIRECTORY DELETION
    // =========================================================================

    /**
     * Delete directory and all contents
     *
     * Recursively deletes directory and everything inside it.
     * Use with caution!
     *
     * Examples:
     *   FileHandler::deleteDir('temp/cache');
     *   FileHandler::deleteDir('old_uploads');
     *
     * @param string $path Directory path
     * @return FileResponse
     */
    public static function deleteDir($path)
    {
        if (!is_dir($path)) {
            return FileResponse::error("Directory not found: {$path}", $path);
        }

        if (!static::cleanDir($path)->success) {
            return FileResponse::error("Failed to clean directory: {$path}", $path);
        }

        if (@rmdir($path)) {
            return FileResponse::success('', $path);
        }

        return FileResponse::error("Failed to delete directory: {$path}", $path);
    }

    /**
     * Clean directory contents
     *
     * Deletes all files and subdirectories but keeps the directory itself.
     *
     * Examples:
     *   FileHandler::cleanDir('cache');
     *   FileHandler::cleanDir('temp');
     *
     * @param string $path Directory path
     * @return FileResponse
     */
    public static function cleanDir($path)
    {
        if (!is_dir($path)) {
            return FileResponse::error("Directory not found: {$path}", $path);
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }

        return FileResponse::success('', $path);
    }

    // =========================================================================
    // DIRECTORY LISTING
    // =========================================================================

    /**
     * Get all files in directory (non-recursive)
     *
     * Returns array of file paths in the directory.
     * Does not include subdirectories or their contents.
     *
     * Examples:
     *   $result = FileHandler::files('storage/uploads');
     *   if ($result->success) {
     *       foreach ($result->files as $file) {
     *           echo $file;
     *       }
     *   }
     *
     * @param string $path Directory path
     * @return FileResponse Response with files array
     */
    public static function files($path)
    {
        if (!is_dir($path)) {
            return FileResponse::error("Directory not found: {$path}", $path);
        }

        $files = [];

        try {
            $iterator = new \DirectoryIterator($path);

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        } catch (\Exception $e) {
            return FileResponse::error("Failed to read directory: {$e->getMessage()}", $path);
        }

        $response = FileResponse::success('', $path);
        $response->files = $files;
        return $response;
    }

    /**
     * Get all files in directory (recursive)
     *
     * Returns array of file paths including all subdirectories.
     *
     * Examples:
     *   $result = FileHandler::allFiles('application');
     *   // Returns all PHP files, templates, etc. recursively
     *
     * @param string $path Directory path
     * @return FileResponse Response with files array
     */
    public static function allFiles($path)
    {
        if (!is_dir($path)) {
            return FileResponse::error("Directory not found: {$path}", $path);
        }

        $files = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        } catch (\Exception $e) {
            return FileResponse::error("Failed to read directory: {$e->getMessage()}", $path);
        }

        $response = FileResponse::success('', $path);
        $response->files = $files;
        return $response;
    }

    /**
     * Get subdirectories in directory
     *
     * Returns array of subdirectory paths (non-recursive).
     *
     * Examples:
     *   $result = FileHandler::dirs('application');
     *   // Returns: ['application/controllers', 'application/models', ...]
     *
     * @param string $path Directory path
     * @return FileResponse Response with directories array
     */
    public static function dirs($path)
    {
        if (!is_dir($path)) {
            return FileResponse::error("Directory not found: {$path}", $path);
        }

        $directories = [];

        try {
            $iterator = new \DirectoryIterator($path);

            foreach ($iterator as $dir) {
                if ($dir->isDir() && !$dir->isDot()) {
                    $directories[] = $dir->getPathname();
                }
            }
        } catch (\Exception $e) {
            return FileResponse::error("Failed to read directory: {$e->getMessage()}", $path);
        }

        $response = FileResponse::success('', $path);
        $response->files = $directories;
        return $response;
    }

    // =========================================================================
    // PATTERN MATCHING
    // =========================================================================

    /**
     * Find files by glob pattern
     *
     * Supports wildcards and brace expansion.
     *
     * Examples:
     *   FileHandler::glob('*.php')                    // All PHP files
     *   FileHandler::glob('src/** /*.php')             // PHP files recursively
     *   FileHandler::glob('config/*.{php,json}')      // PHP or JSON in config
     *   FileHandler::glob('logs/error-*.log')         // Error logs
     *
     * Pattern syntax:
     *   *      - Matches any characters
     *   ?      - Matches single character
     *   [abc]  - Matches a, b, or c
     *   {a,b}  - Matches a or b
     *   **     - Recursive (if supported by system)
     *
     * @param string $pattern Glob pattern
     * @param int $flags Glob flags (default 0)
     * @return FileResponse Response with matching files
     */
    public static function glob($pattern, $flags = 0)
    {
        $matches = glob($pattern, $flags);

        if ($matches === false) {
            return FileResponse::error("Glob pattern failed: {$pattern}", $pattern);
        }

        $response = FileResponse::success('', $pattern);
        $response->files = $matches;
        return $response;
    }

    // =========================================================================
    // PATH UTILITIES
    // =========================================================================

    /**
     * Join path components safely
     *
     * Joins multiple path segments with proper directory separator.
     * Handles trailing/leading slashes automatically.
     *
     * Examples:
     *   FileHandler::join('application', 'controllers')
     *   // Returns: 'application/controllers'
     *
     *   FileHandler::join('path/', '/to/', '/file.txt')
     *   // Returns: 'path/to/file.txt'
     *
     * @param string ...$paths Path components to join
     * @return string Joined path
     */
    public static function join(...$paths)
    {
        $result = [];

        foreach ($paths as $path) {
            $path = trim($path);

            if ($path === '') {
                continue;
            }

            // Remove leading/trailing slashes except for first segment
            if (empty($result)) {
                $result[] = rtrim($path, '/\\');
            } else {
                $result[] = trim($path, '/\\');
            }
        }

        return implode('/', $result);
    }

    /**
     * Normalize path
     *
     * Cleans path by:
     * - Converting backslashes to forward slashes
     * - Removing double slashes
     * - Removing . and .. references where safe
     *
     * Examples:
     *   FileHandler::normalize('path//to/../file.txt')
     *   // Returns: 'path/file.txt'
     *
     *   FileHandler::normalize('path\\to\\file')
     *   // Returns: 'path/to/file'
     *
     * @param string $path Path to normalize
     * @return string Normalized path
     */
    public static function normalize($path)
    {
        // Convert backslashes to forward slashes
        $path = str_replace('\\', '/', $path);

        // Remove double slashes
        $path = preg_replace('#/+#', '/', $path);

        // Handle . and .. (simple implementation)
        $parts = explode('/', $path);
        $result = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                if (!empty($result)) {
                    array_pop($result);
                }
            } else {
                $result[] = $part;
            }
        }

        $normalized = implode('/', $result);

        // Preserve leading slash if original had one
        if (strpos($path, '/') === 0) {
            $normalized = '/' . $normalized;
        }

        return $normalized;
    }

    /**
     * Get real absolute path
     *
     * Resolves symbolic links and relative references.
     * Returns false if path doesn't exist.
     *
     * Examples:
     *   FileHandler::realpath('../config/database.php')
     *   // Returns: '/var/www/app/config/database.php'
     *
     * @param string $path Path to resolve
     * @return string|false Absolute path or false
     */
    public static function realpath($path)
    {
        return realpath($path);
    }

    /**
     * Get relative path from base to target
     *
     * Calculates the relative path needed to get from base to target.
     *
     * Examples:
     *   FileHandler::relativePath('application', 'application/controllers/Home.php')
     *   // Returns: 'controllers/Home.php'
     *
     * @param string $from Base path
     * @param string $to Target path
     * @return string Relative path
     */
    public static function relativePath($from, $to)
    {
        $from = static::normalize($from);
        $to = static::normalize($to);

        // Simple case: target contains base path
        if (strpos($to, $from) === 0) {
            return ltrim(substr($to, strlen($from)), '/');
        }

        // Complex case: calculate relative path
        $fromParts = explode('/', trim($from, '/'));
        $toParts = explode('/', trim($to, '/'));

        // Find common base
        $common = 0;
        $total = min(count($fromParts), count($toParts));

        for ($i = 0; $i < $total; $i++) {
            if ($fromParts[$i] !== $toParts[$i]) {
                break;
            }
            $common++;
        }

        // Build relative path
        $up = str_repeat('../', count($fromParts) - $common);
        $down = implode('/', array_slice($toParts, $common));

        return $up . $down;
    }

    // =========================================================================
    // DIRECTORY INFORMATION
    // =========================================================================

    /**
     * Check if directory is empty
     *
     * @param string $path Directory path
     * @return FileResponse Response with success=true if empty
     */
    public static function isEmpty($path)
    {
        if (!is_dir($path)) {
            return FileResponse::error("Directory not found: {$path}", $path);
        }

        $iterator = new \FilesystemIterator($path);
        $isEmpty = !$iterator->valid();

        $response = FileResponse::success('', $path);
        $response->success = $isEmpty;
        return $response;
    }

    /**
     * Get directory size (total bytes)
     *
     * Calculates total size of all files in directory recursively.
     *
     * Examples:
     *   $result = FileHandler::dirSize('storage/uploads');
     *   echo "Size: {$result->size} bytes";
     *
     * @param string $path Directory path
     * @return FileResponse Response with size property
     */
    public static function dirSize($path)
    {
        if (!is_dir($path)) {
            return FileResponse::error("Directory not found: {$path}", $path);
        }

        $totalSize = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $totalSize += $file->getSize();
                }
            }
        } catch (\Exception $e) {
            return FileResponse::error("Failed to calculate size: {$e->getMessage()}", $path);
        }

        $response = FileResponse::success('', $path);
        $response->size = $totalSize;
        return $response;
    }

    /**
     * Copy entire directory
     *
     * Recursively copies directory and all contents to new location.
     *
     * Examples:
     *   FileHandler::copyDir('source', 'backup');
     *
     * @param string $source Source directory
     * @param string $destination Destination directory
     * @return FileResponse
     */
    public static function copyDir($source, $destination)
    {
        if (!is_dir($source)) {
            return FileResponse::error("Source directory not found: {$source}", $source);
        }

        // Create destination directory
        if (!static::makeDir($destination)->success) {
            return FileResponse::error("Failed to create destination: {$destination}", $destination);
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $target = $destination . '/' . $iterator->getSubPathName();

                if ($item->isDir()) {
                    @mkdir($target);
                } else {
                    @copy($item, $target);
                }
            }
        } catch (\Exception $e) {
            return FileResponse::error("Failed to copy directory: {$e->getMessage()}", $source);
        }

        return FileResponse::success('', $destination);
    }

    /**
     * Move/rename directory
     *
     * @param string $source Source directory
     * @param string $destination Destination directory
     * @return FileResponse
     */
    public static function moveDir($source, $destination)
    {
        if (!is_dir($source)) {
            return FileResponse::error("Source directory not found: {$source}", $source);
        }

        if (@rename($source, $destination)) {
            return FileResponse::success('', $destination);
        }

        return FileResponse::error("Failed to move directory from {$source} to {$destination}", $source);
    }
}
