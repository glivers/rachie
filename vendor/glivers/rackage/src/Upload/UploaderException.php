<?php namespace Rackage\Upload;

/**
 * Upload Exception
 *
 * Exception thrown when file upload operations fail.
 * Extends ExceptionClass to integrate with Rachie's exception handling system.
 *
 * Common scenarios:
 *   - Invalid file type uploaded
 *   - File size exceeds limit
 *   - Upload directory not writable
 *   - Failed to move uploaded file
 *   - Missing required upload field
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Exceptions
 * @package Rackage\Uploader\UploaderException
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

use Exceptions\ExceptionClass;

class UploaderException extends ExceptionClass {}
