<?php namespace Rackage\FileSystem;

/**
 * File Operation Response Object
 *
 * Returned by File operations to provide detailed information about
 * the operation result including success/failure status, content,
 * and error messages.
 *
 * Properties:
 *   - success: Boolean indicating if operation succeeded
 *   - error: Boolean indicating if operation failed
 *   - errorMessage: Human-readable error description
 *   - content: File content (for read operations)
 *   - path: File/directory path
 *   - size: File size in bytes
 *   - exists: Boolean indicating if file/directory exists
 *   - files: Array of files (for directory listings)
 *   - isFile: Boolean indicating if path is a file
 *   - isDir: Boolean indicating if path is a directory
 *
 * Factory Methods:
 *   FileResponse::success($content, $path)
 *   FileResponse::error($message, $path)
 *
 * Usage:
 *   $result = File::read('config.json');
 *
 *   if ($result->success) {
 *       echo $result->content;
 *   } else {
 *       echo $result->errorMessage;
 *   }
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\File
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

class FileResponse {

    /**
     * Operation success status
     *
     * True if operation succeeded, false otherwise.
     *
     * @var bool
     */
    public $success = false;

    /**
     * Operation error status
     *
     * True if operation failed, false otherwise.
     * Mutually exclusive with $success.
     *
     * @var bool
     */
    public $error = false;

    /**
     * Error message
     *
     * Human-readable description of what went wrong.
     * Empty string if operation succeeded.
     *
     * Examples:
     *   - "File not found: config.json"
     *   - "Permission denied: /etc/hosts"
     *   - "Directory not empty: /tmp/cache"
     *
     * @var string
     */
    public $errorMessage = '';

    /**
     * File content
     *
     * The content read from the file.
     * Only populated for read operations.
     *
     * @var string
     */
    public $content = '';

    /**
     * File or directory path
     *
     * The path that was operated on.
     *
     * @var string
     */
    public $path = '';

    /**
     * File size in bytes
     *
     * Only populated for file info operations.
     *
     * @var int
     */
    public $size = 0;

    /**
     * File/directory existence status
     *
     * True if file or directory exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * List of files
     *
     * Array of file paths from directory listing operations.
     *
     * @var array
     */
    public $files = [];

    /**
     * Is file check
     *
     * True if path is a file.
     *
     * @var bool
     */
    public $isFile = false;

    /**
     * Is directory check
     *
     * True if path is a directory.
     *
     * @var bool
     */
    public $isDir = false;

    /**
     * File extension
     *
     * File extension without dot (e.g., 'php', 'json', 'txt')
     *
     * @var string
     */
    public $extension = '';

    /**
     * MIME type
     *
     * The MIME type of the file.
     *
     * @var string
     */
    public $mimeType = '';

    /**
     * Last modified timestamp
     *
     * Unix timestamp of last modification.
     *
     * @var int
     */
    public $lastModified = 0;

    /**
     * Create a success response
     *
     * Factory method for creating successful operation responses.
     *
     * Examples:
     *   FileResponse::success($content, 'config.json')
     *   FileResponse::success('', 'deleted.txt')
     *
     * @param mixed $content Response content (string, array, etc.)
     * @param string $path File/directory path
     * @return FileResponse
     */
    public static function success($content = '', $path = '')
    {
        $response = new self();
        $response->success = true;
        $response->content = $content;
        $response->path = $path;

        return $response;
    }

    /**
     * Create an error response
     *
     * Factory method for creating failed operation responses.
     *
     * Examples:
     *   FileResponse::error('File not found', 'missing.txt')
     *   FileResponse::error('Permission denied', '/etc/hosts')
     *
     * @param string $message Error message
     * @param string $path File/directory path
     * @return FileResponse
     */
    public static function error($message, $path = '')
    {
        $response = new self();
        $response->error = true;
        $response->errorMessage = $message;
        $response->path = $path;

        return $response;
    }
}
