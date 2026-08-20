<?php namespace Rackage\Database;

/**
 * Database Exception Handler
 *
 * Handles all exceptions thrown in the context of database operations.
 * Extends the main ExceptionClass to ensure unified error handling with
 * logging, dev/prod modes, and custom error pages.
 *
 * Features:
 *   - Inherits error logging from ExceptionClass
 *   - Inherits dev/prod mode handling from ExceptionClass
 *   - Inherits stack trace formatting from ExceptionClass
 *   - Provides database-specific exception type for catch blocks
 *
 * Usage:
 *   throw new DatabaseException("Connection failed");
 *   throw new DatabaseException("Invalid database type: postgresql");
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Database
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

use Exceptions\ExceptionClass;

class DatabaseException extends ExceptionClass {}
