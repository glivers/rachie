<?php namespace Rackage\Cache;

/**
 * Cache Exception Handler
 *
 * Handles all exceptions thrown in the context of cache operations.
 * Extends the main ExceptionClass to ensure unified error handling with
 * logging, dev/prod modes, and custom error pages.
 *
 * Features:
 *   - Inherits error logging from ExceptionClass
 *   - Inherits dev/prod mode handling from ExceptionClass
 *   - Inherits stack trace formatting from ExceptionClass
 *   - Provides cache-specific exception type for catch blocks
 *
 * Usage:
 *   throw new CacheException("Connection failed");
 *   throw new CacheException("Invalid cache driver: xyz");
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Cache
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

use Exceptions\ExceptionClass;

class CacheException extends ExceptionClass {}
