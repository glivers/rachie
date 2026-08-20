<?php namespace Rackage\Upload;

/**
 * File Uploader Implementation
 *
 * Handles the actual file upload logic including validation, security checks,
 * and file system operations. Used internally by the Upload facade.
 *
 * Features:
 *   - File type validation (extension and MIME type)
 *   - File size limits
 *   - Automatic unique file naming (SHA1 hash)
 *   - Directory creation with proper permissions
 *   - Path sanitization (prevents directory traversal)
 *   - Image dimension detection
 *   - Public URL generation
 *   - Comprehensive error handling
 *
 * Error Handling:
 *   - Validation errors (wrong type, too large) → Returned in response
 *   - System errors (directory issues) → Throws UploaderException
 *
 * Security:
 *   - MIME type validation (not just extension)
 *   - Path sanitization (removes .., double slashes)
 *   - Configurable allowed file types
 *   - File size limits
 *   - Safe directory permissions (0755)
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Uploader
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.3
 */

use Rackage\Path;
use Rackage\Registry;
use Rackage\Url;
use Rackage\Upload\UploaderException;
use Rackage\Upload\UploaderResponse;

class Uploader {

    /**
     * The form field name for the uploaded file
     *
     * @var string
     */
    private $fileName;

    /**
     * Generated unique file name for storage
     *
     * @var string
     */
    private $targetFileName;

    /**
     * Absolute path to target directory
     *
     * @var string
     */
    private $targetDir;

    /**
     * Full absolute path to target file
     *
     * @var string
     */
    private $targetFile;

    /**
     * File extension
     *
     * @var string
     */
    private $fileType;

    /**
     * MIME type detected from file
     *
     * @var string
     */
    private $mimeType;

    /**
     * File size in bytes
     *
     * @var int
     */
    private $fileSize;

    /**
     * Upload directory path relative to application root
     *
     * @var string
     */
    private $uploadPath;

    /**
     * Whether to hash filename with SHA1
     *
     * @var bool
     */
    private $hashName = false;

    /**
     * Whether to lowercase the filename
     *
     * @var bool
     */
    private $lowerCase = false;

    /**
     * Relative path from application root to uploaded file
     *
     * @var string
     */
    private $relativePath;

    /**
     * Allowed file extensions
     *
     * @var array|null
     */
    private $allowedTypes = null;

    /**
     * Maximum file size in bytes
     *
     * @var int|null
     */
    private $maxSize = null;

    /**
     * Validation error message
     *
     * If set, upload will fail with this message.
     * Used to collect validation errors without throwing exceptions.
     *
     * @var string|null
     */
    private $validationError = null;

    /**
     * Set upload directory path
     *
     * @param string|null $dirName Directory path relative to app root
     * @return $this
     */
    public function setUploadPath($dirName)
    {
        if ($dirName === null) {
            $this->uploadPath = $this->sanitizePath(Registry::settings()['upload_path']);
        } else {
            $this->uploadPath = $this->sanitizePath($dirName);
        }

        return $this;
    }

    /**
     * Set the form field name
     *
     * @param string $fileName The form field name
     * @return $this
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * Set whether to hash filename
     *
     * @param bool $hashName True to use SHA1 hash, false for original name
     * @return $this
     */
    public function setHashName($hashName)
    {
        $this->hashName = $hashName;
        return $this;
    }

    /**
     * Set whether to lowercase filename
     *
     * @param bool $lowerCase True to lowercase filename
     * @return $this
     */
    public function setLowerCase($lowerCase)
    {
        $this->lowerCase = $lowerCase;
        return $this;
    }

    /**
     * Set and create target directory
     *
     * @return $this
     * @throws UploaderException If directory creation fails (system error)
     */
    public function setTargetDir()
    {
        $this->targetDir = Path::base() . $this->uploadPath;

        if (!file_exists($this->targetDir)) {
            if (!mkdir($this->targetDir, 0755, true)) {
                throw new UploaderException("Failed to create upload directory: {$this->uploadPath}");
            }
        }

        if (!is_writable($this->targetDir)) {
            throw new UploaderException("Upload directory is not writable: {$this->uploadPath}");
        }

        return $this;
    }

    /**
     * Detect and set file type
     *
     * @return $this
     */
    public function setFileType()
    {
        // Check if file field exists
        if (!isset($_FILES[$this->fileName])) {
            $this->validationError = "No file uploaded with field name: {$this->fileName}";
            return $this;
        }

        // Extract extension
        $this->fileType = strtolower(pathinfo($_FILES[$this->fileName]['name'], PATHINFO_EXTENSION));

        return $this;
    }

    /**
     * Detect and set MIME type
     *
     * @return $this
     */
    public function setMimeType()
    {
        // Skip if already have validation error
        if ($this->validationError !== null) {
            return $this;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $this->mimeType = finfo_file($finfo, $_FILES[$this->fileName]['tmp_name']);
        finfo_close($finfo);

        return $this;
    }

    /**
     * Generate unique target file name
     *
     * @return $this
     */
    public function setTargetFileName()
    {
        // Skip if already have validation error
        if ($this->validationError !== null) {
            return $this;
        }

        if ($this->hashName) {
            // Use SHA1 hash (guaranteed unique, no collision risk)
            $hash = sha1_file($_FILES[$this->fileName]['tmp_name']);
            $this->targetFileName = sprintf('%s.%s', $hash, $this->fileType);
        } else {
            // Use sanitized original filename (SEO-friendly)
            $originalName = $_FILES[$this->fileName]['name'];
            $basename = pathinfo($originalName, PATHINFO_FILENAME);

            // Apply lowercase if flag is set
            if ($this->lowerCase) {
                $basename = strtolower($basename);
            }

            // Sanitize: keep alphanumeric, hyphens, underscores only
            $sanitized = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $basename);
            // Replace multiple hyphens with single hyphen
            $sanitized = preg_replace('/-+/', '-', $sanitized);
            // Trim hyphens from start/end
            $sanitized = trim($sanitized, '-');

            // If empty after sanitization, use timestamp
            if (empty($sanitized)) {
                $sanitized = 'upload-' . time();
            }

            // Check for duplicates and add numeric suffix
            $extension = $this->fileType;
            $counter = 0;
            $testName = $sanitized . '.' . $extension;

            while (file_exists($this->targetDir . DIRECTORY_SEPARATOR . $testName)) {
                $counter++;
                $testName = $sanitized . '-' . $counter . '.' . $extension;
            }

            $this->targetFileName = $testName;
        }

        return $this;
    }

    /**
     * Set target file path
     *
     * @return $this
     */
    public function setTargetFile()
    {
        // Skip if already have validation error
        if ($this->validationError !== null) {
            return $this;
        }

        $this->relativePath = $this->uploadPath . $this->targetFileName;
        $this->targetFile = $this->targetDir . $this->targetFileName;

        return $this;
    }

    /**
     * Validate file type (returns error in response, doesn't throw)
     *
     * @param array|null $allowedTypes Array of allowed extensions
     * @return $this
     */
    public function checkFileType($allowedTypes = null)
    {
        // Skip if already have validation error
        if ($this->validationError !== null) {
            return $this;
        }

        $this->allowedTypes = $allowedTypes;

        if ($allowedTypes === null || empty($allowedTypes)) {
            return $this;
        }

        $allowedTypes = array_map('strtolower', (array)$allowedTypes);

        // Check extension
        if (!in_array($this->fileType, $allowedTypes)) {
            $this->validationError = "Invalid file type '{$this->fileType}'. Allowed types: " . implode(', ', $allowedTypes);
            return $this;
        }

        // MIME type validation
        $mimeMap = [
            'jpg'  => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls'  => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'txt'  => ['text/plain'],
            'csv'  => ['text/csv', 'text/plain'],
            'zip'  => ['application/zip', 'application/x-zip-compressed'],
        ];

        if (isset($mimeMap[$this->fileType])) {
            if (!in_array($this->mimeType, $mimeMap[$this->fileType])) {
                $this->validationError = "File content does not match extension. Expected MIME: " .
                    implode(' or ', $mimeMap[$this->fileType]) . ", got: {$this->mimeType}";
                return $this;
            }
        }

        return $this;
    }

    /**
     * Set file size
     *
     * @return $this
     */
    public function setFileSize()
    {
        // Skip if already have validation error
        if ($this->validationError !== null) {
            return $this;
        }

        $this->fileSize = $_FILES[$this->fileName]["size"];
        return $this;
    }

    /**
     * Validate file size (returns error in response, doesn't throw)
     *
     * @param int|null $maxSize Maximum size in bytes
     * @return $this
     */
    public function checkFileSize($maxSize = null)
    {
        // Skip if already have validation error
        if ($this->validationError !== null) {
            return $this;
        }

        $this->maxSize = $maxSize;

        if ($maxSize === null) {
            return $this;
        }

        if ($this->fileSize > $maxSize) {
            $maxMB = round($maxSize / 1048576, 2);
            $actualMB = round($this->fileSize / 1048576, 2);
            $this->validationError = "File size ({$actualMB}MB) exceeds maximum allowed ({$maxMB}MB)";
            return $this;
        }

        return $this;
    }

    /**
     * Perform the file upload
     *
     * @return UploaderResponse Upload result with file details or error
     */
    public function upload()
    {
        $response = new UploaderResponse();

        // Check if validation failed earlier
        if ($this->validationError !== null) {
            $response->error = true;
            $response->errorMessage = $this->validationError;
            return $response;
        }

        // Check for PHP upload errors
        if ($_FILES[$this->fileName]['error'] !== UPLOAD_ERR_OK) {
            $response->error = true;
            $response->errorMessage = $this->getUploadErrorMessage($_FILES[$this->fileName]['error']);
            return $response;
        }

        // Move uploaded file
        if (move_uploaded_file($_FILES[$this->fileName]["tmp_name"], $this->targetFile)) {
            // Success - populate response
            $response->success = true;
            $response->fileName = $_FILES[$this->fileName]["name"];
            $response->savedFileName = $this->targetFileName;
            $response->fileSize = $this->fileSize;
            $response->fileType = $this->fileType;
            $response->mimeType = $this->mimeType;
            $response->fullPath = $this->targetFile;
            $response->relativePath = $this->relativePath;

            // Generate public URL if file is in public directory
            if (strpos($this->uploadPath, 'public/') === 0) {
                $publicPath = str_replace('public/', '', $this->relativePath);
                $response->publicUrl = Url::base() . $publicPath;
            }

            // Get image dimensions if it's an image
            if (in_array($this->fileType, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                $imageInfo = getimagesize($this->targetFile);
                if ($imageInfo !== false) {
                    $response->width = $imageInfo[0];
                    $response->height = $imageInfo[1];
                }
            }

            return $response;
        } else {
            // Move failed
            $response->error = true;
            $response->errorMessage = "Failed to move uploaded file to: {$this->targetFile}";
            return $response;
        }
    }

    /**
     * Sanitize directory path
     *
     * @param string $path Path to sanitize
     * @return string Sanitized path
     */
    private function sanitizePath($path)
    {
        $path = str_replace(['..', '\\'], ['', '/'], $path);
        $path = preg_replace('#/+#', '/', $path);
        return trim($path, '/') . '/';
    }

    /**
     * Get human-readable upload error message
     *
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage($errorCode)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload directory',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload',
        ];

        return $errors[$errorCode] ?? 'Unknown upload error';
    }
}
