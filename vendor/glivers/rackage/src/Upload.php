<?php namespace Rackage;

/**
 * Upload - File Upload Helper
 *
 * Simple, secure file upload handling with validation and error reporting.
 * Uses static chaining pattern for clean, readable API.
 *
 * Features:
 *   - Chainable static methods
 *   - File type validation (extension + MIME type)
 *   - File size limits
 *   - Automatic unique file naming
 *   - Security (path sanitization, MIME validation)
 *   - Image dimension detection
 *   - Comprehensive error messages in response
 *
 * Basic Usage:
 *   $result = Upload::file('avatar')->save();
 *
 *   if ($result->success) {
 *       echo "File uploaded: " . $result->relativePath;
 *   } else {
 *       echo "Error: " . $result->errorMessage;
 *   }
 *
 * With Validation:
 *   $result = Upload::file('document')
 *       ->path('application/storage/documents')
 *       ->allowedTypes(['pdf', 'doc', 'docx'])
 *       ->maxSize(5 * 1024 * 1024)  // 5MB
 *       ->save();
 *
 * Image Upload Example:
 *   $result = Upload::file('profile_photo')
 *       ->allowedTypes(['jpg', 'png', 'gif'])
 *       ->maxSize(2 * 1024 * 1024)  // 2MB
 *       ->save();
 *
 *   if ($result->success) {
 *       echo "Image: {$result->width}x{$result->height}";
 *       echo "URL: {$result->publicUrl}";
 *   }
 *
 * Response Properties:
 *   $result->success         - Boolean: upload succeeded
 *   $result->error           - Boolean: upload failed
 *   $result->errorMessage    - String: error description
 *   $result->fileName        - String: original file name
 *   $result->savedFileName   - String: unique name on disk
 *   $result->fileSize        - Int: size in bytes
 *   $result->fileType        - String: extension (jpg, pdf, etc.)
 *   $result->mimeType        - String: MIME type
 *   $result->fullPath        - String: absolute server path
 *   $result->relativePath    - String: path from app root
 *   $result->publicUrl       - String: public URL (if applicable)
 *   $result->width           - Int: image width (images only)
 *   $result->height          - Int: image height (images only)
 *
 * Configuration:
 *   Default upload path is set in config/settings.php:
 *   'upload_path' => 'public/uploads/'
 *
 * Error Handling:
 *   - Validation errors (wrong file type, too large) → Returned in response object
 *   - System errors (directory not writable) → Throws UploaderException
 *
 * Security Notes:
 *   - MIME type is validated against file extension
 *   - Directory traversal is prevented (.., // removed)
 *   - Files are renamed with SHA1 hash (prevents name collisions)
 *   - Upload directory is auto-created with safe permissions (0755)
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Upload
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.3
 */

use Rackage\Upload\Uploader;
use Rackage\Upload\UploaderResponse;

class Upload {

    /**
     * Form field name for the uploaded file
     *
     * @var string
     */
    protected static $fileName;

    /**
     * Custom upload directory path
     *
     * @var string|null
     */
    protected static $path;

    /**
     * Allowed file extensions
     *
     * @var array|null
     */
    protected static $allowedTypes;

    /**
     * Maximum file size in bytes
     *
     * @var int|null
     */
    protected static $maxSize;

    /**
     * Whether to hash filename (SHA1) or use original name
     *
     * @var bool
     */
    protected static $hashName;

    /**
     * Whether to lowercase the filename
     *
     * @var bool
     */
    protected static $lowerCase;

    /**
     * Private constructor - prevent instantiation
     *
     * @return void
     */
    private function __construct() {}

    /**
     * Private clone - prevent cloning
     *
     * @return void
     */
    private function __clone() {}

    /**
     * Initialize file upload
     *
     * Sets the form field name and resets all options for new upload.
     *
     * Examples:
     *   Upload::file('avatar')
     *   Upload::file('document')
     *   Upload::file('profile_photo')
     *
     * @param string $fileName The form field name from <input name="...">
     * @return static For method chaining
     */
    public static function file($fileName)
    {
        // Reset state for new upload
        static::$fileName = $fileName;
        static::$path = null;
        static::$allowedTypes = null;
        static::$maxSize = null;
        static::$hashName = false;
        static::$lowerCase = false;

        return new static;
    }

    /**
     * Set custom upload directory
     *
     * Overrides the default upload path from config.
     * Path is relative to application root.
     *
     * Examples:
     *   ->path('public/uploads/avatars')
     *   ->path('application/storage/documents')
     *   ->path('public/uploads/reports')
     *
     * @param string $directory Directory path relative to app root
     * @return static For method chaining
     */
    public static function path($directory)
    {
        static::$path = $directory;
        return new static;
    }

    /**
     * Set allowed file types
     *
     * Restricts uploads to specific file extensions.
     * Both extension and MIME type will be validated.
     *
     * Examples:
     *   ->allowedTypes(['jpg', 'png', 'gif'])
     *   ->allowedTypes(['pdf'])
     *   ->allowedTypes(['doc', 'docx', 'pdf'])
     *   ->allowedTypes(['csv', 'xlsx'])
     *
     * Common types:
     *   Images: jpg, jpeg, png, gif, webp, svg
     *   Documents: pdf, doc, docx, txt
     *   Spreadsheets: xls, xlsx, csv
     *   Archives: zip, rar, tar, gz
     *
     * @param array $types Array of allowed file extensions (lowercase)
     * @return static For method chaining
     */
    public static function allowedTypes(array $types)
    {
        static::$allowedTypes = $types;
        return new static;
    }

    /**
     * Set maximum file size
     *
     * Restricts uploads to files smaller than specified size.
     *
     * Examples:
     *   ->maxSize(1024 * 1024)          // 1MB
     *   ->maxSize(2 * 1024 * 1024)      // 2MB
     *   ->maxSize(5 * 1024 * 1024)      // 5MB
     *   ->maxSize(10485760)             // 10MB in bytes
     *
     * Common sizes:
     *   1MB   = 1048576 bytes      = 1024 * 1024
     *   2MB   = 2097152 bytes      = 2 * 1024 * 1024
     *   5MB   = 5242880 bytes      = 5 * 1024 * 1024
     *   10MB  = 10485760 bytes     = 10 * 1024 * 1024
     *   50MB  = 52428800 bytes     = 50 * 1024 * 1024
     *   100MB = 104857600 bytes    = 100 * 1024 * 1024
     *
     * @param int $bytes Maximum size in bytes
     * @return static For method chaining
     */
    public static function maxSize($bytes)
    {
        static::$maxSize = $bytes;
        return new static;
    }

    /**
     * Use SHA1 hash for filename
     *
     * When enabled, files are saved with SHA1 hash instead of original name.
     * Guarantees unique filenames and prevents collisions.
     *
     * Example:
     *   ->hashName()->save()  // a3f5d8c9e2b1f4a6...jpg
     *
     * @return static For method chaining
     */
    public static function hashName()
    {
        static::$hashName = true;
        return new static;
    }

    /**
     * Lowercase the filename
     *
     * Converts filename to lowercase for consistency and SEO.
     *
     * Example:
     *   ->lowerCase()->save()  // my-photo.jpg
     *
     * @return static For method chaining
     */
    public static function lowerCase()
    {
        static::$lowerCase = true;
        return new static;
    }

    /**
     * Execute the file upload
     *
     * Performs validation and uploads the file.
     * Returns response object with success/error status.
     *
     * Validation errors (wrong type, too large) are returned in response.
     * System errors (directory issues) throw UploaderException.
     *
     * Examples:
     *   // Original filename (default)
     *   $result = Upload::file('avatar')
     *       ->allowedTypes(['jpg', 'png'])
     *       ->maxSize(2 * 1024 * 1024)
     *       ->save();  // My-Photo.jpg
     *
     *   // Lowercase filename
     *   ->lowerCase()->save();  // my-photo.jpg
     *
     *   // SHA1 hash
     *   ->hashName()->save();  // a3f5d8c9e2b1...jpg
     *
     *   if ($result->success) {
     *       echo $result->relativePath;
     *       echo $result->publicUrl;
     *   } else {
     *       echo $result->errorMessage;
     *   }
     *
     * @return UploaderResponse Upload result with file details or error
     * @throws \Rackage\Uploader\UploaderException On system errors (directory issues)
     */
    public static function save()
    {
        // Create uploader instance and configure
        $uploader = new Uploader();

        // Execute upload with configured options
        return $uploader
            ->setUploadPath(static::$path)
            ->setFileName(static::$fileName)
            ->setHashName(static::$hashName)
            ->setLowerCase(static::$lowerCase)
            ->setTargetDir()
            ->setFileType()
            ->setMimeType()
            ->checkFileType(static::$allowedTypes)
            ->setFileSize()
            ->checkFileSize(static::$maxSize)
            ->setTargetFileName()
            ->setTargetFile()
            ->upload();
    }
}
