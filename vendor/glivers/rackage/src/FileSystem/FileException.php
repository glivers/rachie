<?php namespace Rackage\FileSystem;

/**
 * File Exception
 *
 * Exception thrown when file system operations encounter critical errors.
 * Extends ExceptionClass to integrate with Rachie's exception handling system.
 *
 * Common scenarios (system errors only):
 *   - Directory creation failure (permissions)
 *   - Disk full
 *   - Invalid arguments to file methods
 *   - Unexpected system errors
 *
 * Note: Normal file operation failures (file not found, can't read) should
 * return error in FileResponse, not throw exceptions.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Exceptions
 * @package Rackage\File\FileException
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

use Exceptions\ExceptionClass;

class FileException extends ExceptionClass {}
