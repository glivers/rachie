<?php namespace Rackage\Upload;

/**
 * Upload Response Object
 *
 * Returned by Upload operations to provide detailed information about
 * the upload result including success/failure status, file details,
 * and error messages.
 *
 * Properties:
 *   - success: Boolean indicating if upload succeeded
 *   - error: Boolean indicating if upload failed
 *   - errorMessage: Human-readable error description
 *   - fileName: Original uploaded file name
 *   - savedFileName: Generated unique file name on disk
 *   - fileSize: File size in bytes
 *   - fileType: File extension (jpg, pdf, etc.)
 *   - mimeType: MIME type (image/jpeg, application/pdf, etc.)
 *   - fullPath: Absolute server path to uploaded file
 *   - relativePath: Relative path from application root
 *   - publicUrl: Public URL to access the file (if in public directory)
 *   - width: Image width in pixels (images only)
 *   - height: Image height in pixels (images only)
 *
 * Usage:
 *   $result = Upload::file('avatar');
 *
 *   if ($result->success) {
 *       echo "Uploaded: " . $result->relativePath;
 *       echo "Size: " . $result->fileSize . " bytes";
 *   } else {
 *       echo "Error: " . $result->errorMessage;
 *   }
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Uploader
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */
class UploaderResponse {

    /**
     * Upload success status
     *
     * True if file was uploaded successfully, false otherwise.
     *
     * @var bool
     */
    public $success = false;

    /**
     * Upload error status
     *
     * True if upload failed, false otherwise.
     * Mutually exclusive with $success.
     *
     * @var bool
     */
    public $error = false;

    /**
     * Error message
     *
     * Human-readable description of what went wrong.
     * Empty string if upload succeeded.
     *
     * Examples:
     *   - "File size exceeds maximum allowed (2MB)"
     *   - "Invalid file type. Only JPG, PNG allowed"
     *   - "Failed to move uploaded file"
     *
     * @var string
     */
    public $errorMessage = '';

    /**
     * Original file name
     *
     * The name of the file as uploaded by the user.
     *
     * Example: "profile-photo.jpg"
     *
     * @var string
     */
    public $fileName = '';

    /**
     * Saved file name
     *
     * The unique generated file name stored on disk.
     * Uses SHA1 hash of file content to prevent collisions.
     *
     * Example: "a94a8fe5ccb19ba61c4c0873d391e987982fbbd3.jpg"
     *
     * @var string
     */
    public $savedFileName = '';

    /**
     * File size in bytes
     *
     * @var int
     */
    public $fileSize = 0;

    /**
     * File extension
     *
     * Lowercase file extension without dot.
     *
     * Examples: "jpg", "pdf", "png", "docx"
     *
     * @var string
     */
    public $fileType = '';

    /**
     * MIME type
     *
     * The actual MIME type detected from file content.
     * More reliable than file extension for validation.
     *
     * Examples:
     *   - "image/jpeg"
     *   - "application/pdf"
     *   - "text/plain"
     *
     * @var string
     */
    public $mimeType = '';

    /**
     * Full absolute path to uploaded file
     *
     * Complete server path to the file.
     *
     * Example: "C:/xampp/htdocs/myapp/public/uploads/abc123.jpg"
     *
     * Use for: Server-side file operations
     *
     * @var string
     */
    public $fullPath = '';

    /**
     * Relative path from application root
     *
     * Path relative to the application root directory.
     *
     * Example: "public/uploads/abc123.jpg"
     *
     * Use for: Storing in database, internal references
     *
     * @var string
     */
    public $relativePath = '';

    /**
     * Public URL to access the file
     *
     * Full URL to access the file via browser.
     * Only set if file is in a publicly accessible directory.
     *
     * Example: "https://example.com/public/uploads/abc123.jpg"
     *
     * Use for: Display in img tags, download links
     *
     * @var string
     */
    public $publicUrl = '';

    /**
     * Image width in pixels
     *
     * Only set for image files (JPG, PNG, GIF, etc.).
     * Zero for non-image files.
     *
     * @var int
     */
    public $width = 0;

    /**
     * Image height in pixels
     *
     * Only set for image files (JPG, PNG, GIF, etc.).
     * Zero for non-image files.
     *
     * @var int
     */
    public $height = 0;
}
