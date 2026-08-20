<?php namespace Rackage\Exceptions;

/**
 * Helper Exception Handler
 *
 * Handles all exceptions thrown by helper classes (Input, Session, Cookie, etc.).
 * Extends the main ExceptionClass to ensure unified error handling with
 * logging, dev/prod modes, and custom error pages.
 *
 * Features:
 *   - Inherits error logging from ExceptionClass
 *   - Inherits dev/prod mode handling from ExceptionClass
 *   - Inherits stack trace formatting from ExceptionClass
 *   - Provides helper-specific exception type for catch blocks
 *
 * Usage:
 *   throw new HelperException("Invalid session configuration");
 *   throw new HelperException("Cookie value exceeds maximum size");
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Exceptions
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

use Exceptions\ExceptionClass;

class HelperException extends ExceptionClass {}