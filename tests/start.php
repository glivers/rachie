<?php
/**
 * Test Start
 *
 * This is the test environment's version of system/start.php
 *
 * Boot Sequence (Normal Web Request):
 *   1. public/index.php
 *   2. system/bootstrap.php  (validates, loads config, sets up Registry)
 *   3. system/start.php      (loads routes, creates Router, dispatches)
 *
 * Boot Sequence (Test Environment):
 *   1. tests/start.php (this file)
 *   2. public/index.php
 *   3. system/bootstrap.php  (validates, loads config, sets up Registry)
 *   4. system/start.php      (SKIPPED - because ROLINE_INSTANCE is defined)
 *
 * Since system/start.php never executes during tests, this file serves as
 * the test environment setup. All test helper logic lives in RachieTest.php
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Tests
 * @package Rachie
 * @link https://github.com/glivers/rachie
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

// Load the framework
// This executes:
//   - public/index.php (sets $rachie_app_start, error reporting)
//   - system/bootstrap.php (validates files, loads config, sets up Registry)
// But NOT system/start.php (because ROLINE_INSTANCE is defined above)
require_once __DIR__ . '/../public/index.php';

// ===========================================================================
// LOAD BASE TEST CLASS
// ===========================================================================

// RachieTest provides all test helpers including:
//   - request() method (simulates HTTP requests)
//   - Database helpers (executeQuery, insertTestData)
//   - File cleanup (trackFile, cleanupTrackedFiles)
//   - Custom assertions (assertResponseContains, assertSessionHas, etc.)
require_once __DIR__ . '/RachieTest.php';
