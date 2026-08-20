<?php
/**
 * Rackage Framework Test Bootstrap
 *
 * This file bootstraps the test environment for Rackage framework tests.
 * Unlike application tests, framework tests verify Router, Model, View, Input,
 * Registry, and other core framework classes.
 *
 * Boot Sequence (Normal Web Request):
 *   1. public/index.php
 *   2. system/bootstrap.php  (validates, loads config, sets up Registry)
 *   3. system/start.php      (loads routes, creates Router, dispatches)
 *
 * Boot Sequence (Framework Test Environment):
 *   1. vendor/glivers/rackage/tests/start.php (this file)
 *   2. ../../../../public/index.php (Rachie root)
 *   3. system/bootstrap.php  (validates, loads config, sets up Registry)
 *   4. system/start.php      (SKIPPED - because ROLINE_INSTANCE is defined)
 *
 * Architecture:
 *   Rackage tests MUST run against a Rachie installation because the framework
 *   needs an application context to test against (routes, controllers, models, views).
 *   These tests run FROM Rachie root using: vendor/glivers/rackage/phpunit.xml
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Tests
 * @package Rackage
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

// ===========================================================================
// MARK AS TEST ENVIRONMENT
// ===========================================================================

// This constant tells system/bootstrap.php we're in test mode
// When defined, system/start.php is skipped (see system/bootstrap.php:195)
define('ROLINE_INSTANCE', 'testing');

// ===========================================================================
// BOOT THE APPLICATION
// ===========================================================================

// Load the framework from Rachie root (4 levels up: tests/ -> rackage/ -> glivers/ -> vendor/ -> rachie/)
// This executes:
//   - public/index.php (sets $rachie_app_start, error reporting)
//   - system/bootstrap.php (validates files, loads config, sets up Registry)
// But NOT system/start.php (because ROLINE_INSTANCE is defined above)
require_once __DIR__ . '/../../../../public/index.php';

// ===========================================================================
// LOAD BASE TEST CLASS
// ===========================================================================

// RackageTest provides all test helpers for framework testing:
//   - request() method (simulates HTTP requests through full stack)
//   - Path helpers (controllerPath, modelPath, viewPath)
//   - File cleanup (trackFile, cleanupTrackedFiles)
//   - Custom assertions (assertResponseContains, assertSessionHas, etc.)
require_once __DIR__ . '/RackageTest.php';
