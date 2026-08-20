<?php namespace Rackage;

/**
 * File - File System Helper
 *
 * Simple, unified API for file and directory operations.
 * Static facade that delegates to FileHandler.
 *
 * All operations return FileResponse objects with success/error status.
 * No exceptions for normal failures (file not found, etc.) - check response instead.
 *
 * Basic Usage:
 *   // Read file
 *   $result = File::read('config.json');
 *   if ($result->success) {
 *       echo $result->content;
 *   }
 *
 *   // Write file
 *   File::write('output.txt', 'Hello World');
 *
 *   // List files in directory
 *   $result = File::files('storage/uploads');
 *   foreach ($result->files as $file) {
 *       echo $file;
 *   }
 *
 *   // Create directory
 *   File::makeDir('storage/cache');
 *
 *   // Find files by pattern
 *   $result = File::glob('*.php');
 *
 * Response Object:
 *   $result->success        - Boolean: operation succeeded
 *   $result->error          - Boolean: operation failed
 *   $result->errorMessage   - String: error description
 *   $result->content        - Mixed: file content or result
 *   $result->path           - String: file/directory path
 *   $result->size           - Int: file/directory size
 *   $result->exists         - Boolean: exists check result
 *   $result->files          - Array: file listing
 *   $result->extension      - String: file extension
 *   $result->mimeType       - String: MIME type
 *   $result->lastModified   - Int: last modified timestamp
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\File
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

use Rackage\FileSystem\FileHandler;
use Rackage\FileSystem\FileResponse;

class File {

    /**
     * Private constructor - prevent instantiation
     * @return void
     */
    private function __construct() {}

    /**
     * Private clone - prevent cloning
     * @return void
     */
    private function __clone() {}

    // =========================================================================
    // FILE READING
    // =========================================================================

    /**
     * Read entire file contents
     *
     * Examples:
     *   $result = File::read('config.json');
     *   if ($result->success) {
     *       $config = json_decode($result->content, true);
     *   }
     *
     * @param string $path Path to file
     * @return FileResponse Response with file content
     */
    public static function read($path)
    {
        return FileHandler::read($path);
    }

    /**
     * Read file as array of lines
     *
     * Examples:
     *   $result = File::readLines('data.txt');
     *   foreach ($result->content as $line) {
     *       echo $line;
     *   }
     *
     * @param string $path Path to file
     * @return FileResponse Response with content as array of lines
     */
    public static function readLines($path)
    {
        return FileHandler::readLines($path);
    }

    /**
     * Read and decode JSON file
     *
     * Examples:
     *   $result = File::readJson('config.json');
     *   $config = $result->content;  // Already decoded
     *
     * @param string $path Path to JSON file
     * @param bool $assoc Return associative array (default true)
     * @return FileResponse Response with decoded JSON content
     */
    public static function readJson($path, $assoc = true)
    {
        return FileHandler::readJson($path, $assoc);
    }

    /**
     * Read CSV file as array
     *
     * Examples:
     *   $result = File::readCsv('users.csv');
     *   foreach ($result->content as $row) {
     *       echo $row[0];  // First column
     *   }
     *
     * @param string $path Path to CSV file
     * @param string $delimiter Field delimiter (default comma)
     * @return FileResponse Response with CSV data as array
     */
    public static function readCsv($path, $delimiter = ',')
    {
        return FileHandler::readCsv($path, $delimiter);
    }

    /**
     * Process large file line by line
     *
     * Memory efficient for large files - doesn't load entire file.
     *
     * Examples:
     *   File::lines('huge.log', function($line, $number) {
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
        return FileHandler::lines($path, $callback);
    }

    // =========================================================================
    // FILE WRITING
    // =========================================================================

    /**
     * Write content to file (create or overwrite)
     *
     * Examples:
     *   File::write('output.txt', 'Hello World');
     *   File::write('data.json', json_encode($data));
     *
     * @param string $path Path to file
     * @param string $content Content to write
     * @return FileResponse
     */
    public static function write($path, $content)
    {
        return FileHandler::write($path, $content);
    }

    /**
     * Append content to end of file
     *
     * Examples:
     *   File::append('log.txt', "New log entry\n");
     *
     * @param string $path Path to file
     * @param string $content Content to append
     * @return FileResponse
     */
    public static function append($path, $content)
    {
        return FileHandler::append($path, $content);
    }

    /**
     * Prepend content to beginning of file
     *
     * Examples:
     *   File::prepend('output.txt', "Header\n");
     *
     * @param string $path Path to file
     * @param string $content Content to prepend
     * @return FileResponse
     */
    public static function prepend($path, $content)
    {
        return FileHandler::prepend($path, $content);
    }

    /**
     * Write data to JSON file
     *
     * Examples:
     *   File::writeJson('config.json', $config, true);
     *
     * @param string $path Path to JSON file
     * @param mixed $data Data to encode
     * @param bool $pretty Pretty print JSON (default false)
     * @return FileResponse
     */
    public static function writeJson($path, $data, $pretty = false)
    {
        return FileHandler::writeJson($path, $data, $pretty);
    }

    /**
     * Write array to CSV file
     *
     * Examples:
     *   $data = [
     *       ['Name', 'Email'],
     *       ['John', 'john@example.com']
     *   ];
     *   File::writeCsv('users.csv', $data);
     *
     * @param string $path Path to CSV file
     * @param array $data Array of rows
     * @param string $delimiter Field delimiter (default comma)
     * @return FileResponse
     */
    public static function writeCsv($path, array $data, $delimiter = ',')
    {
        return FileHandler::writeCsv($path, $data, $delimiter);
    }

    // =========================================================================
    // FILE OPERATIONS
    // =========================================================================

    /**
     * Copy file to new location
     *
     * Examples:
     *   File::copy('source.txt', 'backup.txt');
     *   File::copy('config.json', 'config.backup.json');
     *
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @return FileResponse
     */
    public static function copy($source, $destination)
    {
        return FileHandler::copy($source, $destination);
    }

    /**
     * Copy directory recursively
     *
     * Copies entire directory including all subdirectories and files.
     *
     * Examples:
     *   File::copyDir('uploads/docs', 'backup/docs');
     *   File::copyDir('app/templates', 'app/templates-backup');
     *
     * @param string $source Source directory path
     * @param string $destination Destination directory path
     * @return FileResponse
     */
    public static function copyDir($source, $destination)
    {
        return FileHandler::copyDir($source, $destination);
    }

    /**
     * Move/rename file
     *
     * Examples:
     *   File::move('old.txt', 'new.txt');
     *   File::move('temp/file.txt', 'permanent/file.txt');
     *
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @return FileResponse
     */
    public static function move($source, $destination)
    {
        return FileHandler::move($source, $destination);
    }

    /**
     * Delete file(s)
     *
     * Examples:
     *   File::delete('temp.txt');
     *   File::delete(['temp1.txt', 'temp2.txt']);
     *
     * @param string|array $paths File path or array of paths
     * @return FileResponse
     */
    public static function delete($paths)
    {
        return FileHandler::delete($paths);
    }

    /**
     * Check if file exists
     *
     * Examples:
     *   $result = File::exists('config.json');
     *   if ($result->exists) { ... }
     *
     * @param string $path Path to file
     * @return FileResponse Response with exists property
     */
    public static function exists($path)
    {
        return FileHandler::exists($path);
    }

    /**
     * Check if file is missing
     *
     * Examples:
     *   $result = File::missing('config.json');
     *   if ($result->exists) { ... }
     *
     * @param string $path Path to file
     * @return FileResponse Response with exists property
     */
    public static function missing($path)
    {
        return FileHandler::missing($path);
    }

    // =========================================================================
    // FILE INFORMATION
    // =========================================================================

    /**
     * Check if path is a file
     *
     * @param string $path Path to check
     * @return FileResponse Response with isFile property
     */
    public static function isFile($path)
    {
        return FileHandler::isFile($path);
    }

    /**
     * Check if path is a directory
     *
     * @param string $path Path to check
     * @return FileResponse Response with isDir property
     */
    public static function isDir($path)
    {
        return FileHandler::isDir($path);
    }

    /**
     * Check if file is readable
     *
     * @param string $path Path to file
     * @return FileResponse Response with success property
     */
    public static function isReadable($path)
    {
        return FileHandler::isReadable($path);
    }

    /**
     * Check if file is writable
     *
     * @param string $path Path to file
     * @return FileResponse Response with success property
     */
    public static function isWritable($path)
    {
        return FileHandler::isWritable($path);
    }

    /**
     * Get file size in bytes
     *
     * Examples:
     *   $result = File::size('data.txt');
     *   echo "Size: {$result->size} bytes";
     *
     * @param string $path Path to file
     * @return FileResponse Response with size property
     */
    public static function size($path)
    {
        return FileHandler::size($path);
    }

    /**
     * Get file extension
     *
     * Examples:
     *   $result = File::extension('photo.jpg');
     *   echo $result->extension;  // 'jpg'
     *
     * @param string $path Path to file
     * @return FileResponse Response with extension property
     */
    public static function extension($path)
    {
        return FileHandler::extension($path);
    }

    /**
     * Get filename without extension
     *
     * Examples:
     *   $result = File::name('photo.jpg');
     *   echo $result->content;  // 'photo'
     *
     * @param string $path Path to file
     * @return FileResponse Response with filename in content
     */
    public static function name($path)
    {
        return FileHandler::name($path);
    }

    /**
     * Get filename with extension
     *
     * Examples:
     *   $result = File::basename('path/to/photo.jpg');
     *   echo $result->content;  // 'photo.jpg'
     *
     * @param string $path Path to file
     * @return FileResponse Response with basename in content
     */
    public static function basename($path)
    {
        return FileHandler::basename($path);
    }

    /**
     * Get file MIME type
     *
     * Examples:
     *   $result = File::mimeType('photo.jpg');
     *   echo $result->mimeType;  // 'image/jpeg'
     *
     * @param string $path Path to file
     * @return FileResponse Response with mimeType property
     */
    public static function mimeType($path)
    {
        return FileHandler::mimeType($path);
    }

    /**
     * Get last modified timestamp
     *
     * Examples:
     *   $result = File::lastModified('config.json');
     *   echo date('Y-m-d', $result->lastModified);
     *
     * @param string $path Path to file
     * @return FileResponse Response with lastModified property
     */
    public static function lastModified($path)
    {
        return FileHandler::lastModified($path);
    }

    /**
     * Get file hash
     *
     * Examples:
     *   $result = File::hash('file.txt', 'sha256');
     *   echo $result->content;  // Hash string
     *
     * @param string $path Path to file
     * @param string $algo Hash algorithm (default sha256)
     * @return FileResponse Response with hash in content
     */
    public static function hash($path, $algo = 'sha256')
    {
        return FileHandler::hash($path, $algo);
    }

    // =========================================================================
    // DIRECTORY OPERATIONS
    // =========================================================================

    /**
     * Create directory
     *
     * Creates directory with specified permissions.
     * Recursive by default - creates parent directories if needed.
     *
     * Examples:
     *   File::makeDir('storage/uploads');
     *   File::makeDir('cache/views', 0755, true);
     *
     * @param string $path Directory path
     * @param int $permissions Directory permissions (default 0755)
     * @param bool $recursive Create parent directories (default true)
     * @return FileResponse
     */
    public static function makeDir($path, $permissions = 0755, $recursive = true)
    {
        return FileHandler::makeDir($path, $permissions, $recursive);
    }

    /**
     * Ensure directory exists
     *
     * Creates directory only if it doesn't exist.
     * Convenient wrapper that doesn't fail if already exists.
     *
     * Examples:
     *   File::ensureDir('storage/cache');
     *   File::ensureDir('vault/logs', 0755);
     *
     * @param string $path Directory path
     * @param int $permissions Directory permissions (default 0755)
     * @return FileResponse
     */
    public static function ensureDir($path, $permissions = 0755)
    {
        return FileHandler::ensureDir($path, $permissions);
    }

    /**
     * Delete directory and all contents
     *
     * Recursively deletes directory and everything inside.
     * Use with caution!
     *
     * Examples:
     *   File::deleteDir('temp/cache');
     *   File::deleteDir('old_uploads');
     *
     * @param string $path Directory path
     * @return FileResponse
     */
    public static function deleteDir($path)
    {
        return FileHandler::deleteDir($path);
    }

    /**
     * Clean directory contents
     *
     * Deletes all files and subdirectories but keeps directory itself.
     *
     * Examples:
     *   File::cleanDir('cache');
     *   File::cleanDir('temp');
     *
     * @param string $path Directory path
     * @return FileResponse
     */
    public static function cleanDir($path)
    {
        return FileHandler::cleanDir($path);
    }

    /**
     * Get all files in directory (non-recursive)
     *
     * Returns array of file paths in the directory.
     * Does not include subdirectories or their contents.
     *
     * Examples:
     *   $result = File::files('storage/uploads');
     *   foreach ($result->files as $file) {
     *       echo $file;
     *   }
     *
     * @param string $path Directory path
     * @return FileResponse Response with files array
     */
    public static function files($path)
    {
        return FileHandler::files($path);
    }

    /**
     * Get all files in directory (recursive)
     *
     * Returns array of file paths including all subdirectories.
     *
     * Examples:
     *   $result = File::allFiles('application');
     *   // Returns all PHP files, templates, etc. recursively
     *
     * @param string $path Directory path
     * @return FileResponse Response with files array
     */
    public static function allFiles($path)
    {
        return FileHandler::allFiles($path);
    }

    /**
     * Get subdirectories in directory
     *
     * Returns array of subdirectory paths (non-recursive).
     *
     * Examples:
     *   $result = File::dirs('application');
     *   // Returns: ['application/controllers', 'application/models', ...]
     *
     * @param string $path Directory path
     * @return FileResponse Response with directories array
     */
    public static function dirs($path)
    {
        return FileHandler::dirs($path);
    }

    /**
     * Find files by glob pattern
     *
     * Supports wildcards and brace expansion.
     *
     * Examples:
     *   File::glob('*.php')                    // All PHP files
     *   File::glob('src/**\/*.php')            // PHP files recursively
     *   File::glob('config/*.{php,json}')      // PHP or JSON in config
     *   File::glob('logs/error-*.log')         // Error logs
     *
     * Pattern syntax:
     *   *      - Matches any characters
     *   ?      - Matches single character
     *   [abc]  - Matches a, b, or c
     *   {a,b}  - Matches a or b
     *
     * @param string $pattern Glob pattern
     * @param int $flags Glob flags (default 0)
     * @return FileResponse Response with matching files
     */
    public static function glob($pattern, $flags = 0)
    {
        return FileHandler::glob($pattern, $flags);
    }

    // =========================================================================
    // ADVANCED OPERATIONS
    // =========================================================================

    /**
     * Change file permissions
     *
     * Examples:
     *   File::chmod('script.sh', 0755);
     *   File::chmod('config.php', 0644);
     *
     * @param string $path Path to file
     * @param int $permissions Octal permissions (e.g., 0644, 0755)
     * @return FileResponse
     */
    public static function chmod($path, $permissions)
    {
        return FileHandler::chmod($path, $permissions);
    }

    /**
     * Get file permissions
     *
     * @param string $path Path to file
     * @return FileResponse Response with permissions in content
     */
    public static function getPermissions($path)
    {
        return FileHandler::getPermissions($path);
    }

    /**
     * Read file with shared lock
     *
     * Safe concurrent reading - multiple processes can read simultaneously.
     *
     * @param string $path Path to file
     * @return FileResponse Response with file content
     */
    public static function sharedGet($path)
    {
        return FileHandler::sharedGet($path);
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
        return FileHandler::exclusivePut($path, $content);
    }

    /**
     * Find and replace in file
     *
     * Examples:
     *   File::replace('old_text', 'new_text', 'config.php');
     *
     * @param string $search Search string
     * @param string $replace Replacement string
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function replace($search, $replace, $path)
    {
        return FileHandler::replace($search, $replace, $path);
    }

    /**
     * Find and replace using regex pattern
     *
     * Examples:
     *   File::replacePattern('/old_(\w+)/', 'new_$1', 'config.php');
     *
     * @param string $pattern Regex pattern
     * @param string $replace Replacement string
     * @param string $path Path to file
     * @return FileResponse
     */
    public static function replacePattern($pattern, $replace, $path)
    {
        return FileHandler::replacePattern($pattern, $replace, $path);
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
        return FileHandler::requireFile($path);
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
        return FileHandler::requireOnce($path);
    }

    // =========================================================================
    // PATH UTILITIES
    // =========================================================================

    /**
     * Join path components safely
     *
     * Joins multiple path segments with proper separator.
     * Handles trailing/leading slashes automatically.
     *
     * Examples:
     *   File::join('application', 'controllers')
     *   // Returns: 'application/controllers'
     *
     *   File::join('path/', '/to/', '/file.txt')
     *   // Returns: 'path/to/file.txt'
     *
     * @param string ...$paths Path components to join
     * @return string Joined path
     */
    public static function join(...$paths)
    {
        return FileHandler::join(...$paths);
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
     *   File::normalize('path//to/../file.txt')
     *   // Returns: 'path/file.txt'
     *
     *   File::normalize('path\\to\\file')
     *   // Returns: 'path/to/file'
     *
     * @param string $path Path to normalize
     * @return string Normalized path
     */
    public static function normalize($path)
    {
        return FileHandler::normalize($path);
    }

    /**
     * Get real absolute path
     *
     * Resolves symbolic links and relative references.
     * Returns false if path doesn't exist.
     *
     * Examples:
     *   File::realpath('../config/database.php')
     *   // Returns: '/var/www/app/config/database.php'
     *
     * @param string $path Path to resolve
     * @return string|false Absolute path or false
     */
    public static function realpath($path)
    {
        return FileHandler::realpath($path);
    }

    /**
     * Get relative path from base to target
     *
     * Calculates the relative path needed to get from base to target.
     *
     * Examples:
     *   File::relativePath('application', 'application/controllers/Home.php')
     *   // Returns: 'controllers/Home.php'
     *
     * @param string $from Base path
     * @param string $to Target path
     * @return string Relative path
     */
    public static function relativePath($from, $to)
    {
        return FileHandler::relativePath($from, $to);
    }
}
