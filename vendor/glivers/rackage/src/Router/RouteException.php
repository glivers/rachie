<?php namespace Rackage\Router;

/**
 * Route Exception Handler
 *
 * Handles all exceptions thrown in the context of routing operations.
 * Extends the main ExceptionClass to ensure unified error handling with
 * logging, dev/prod modes, and custom error pages.
 *
 * Features:
 *   - Inherits error logging from ExceptionClass
 *   - Inherits dev/prod mode handling from ExceptionClass
 *   - Inherits stack trace formatting from ExceptionClass
 *   - Provides routing-specific exception type for catch blocks
 *
 * Usage:
 *   throw new RouteException("Controller class 'HomeController' is not defined");
 *   throw new RouteException("Method 'show' does not exist on controller");
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Routes
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */

use Exceptions\ExceptionClass;

class RouteException extends ExceptionClass {}