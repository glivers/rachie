<?php
/**
 * PHPUnit Bootstrap for Roline Tests
 *
 * This file sets up the testing environment by loading Rachie framework
 * via public/index.php (proper way to boot Rachie).
 */

// Define test constants BEFORE loading Rachie
define('ROLINE_TEST_ROOT', __DIR__);
define('ROLINE_ROOT', realpath(__DIR__ . '/..'));

// Rachie root is 3 levels up from roline/tests/
// vendor/glivers/roline/tests -> ../../../ = rachie root
define('RACHIE_ROOT', realpath(__DIR__ . '/../../../..'));

// Set up test application paths
define('TEST_APP_PATH', RACHIE_ROOT . '/application');
define('TEST_STORAGE_PATH', RACHIE_ROOT . '/application/storage');
define('TEST_CONTROLLERS_PATH', TEST_APP_PATH . '/controllers');
define('TEST_MODELS_PATH', TEST_APP_PATH . '/models');
define('TEST_VIEWS_PATH', TEST_APP_PATH . '/views');

// Set ROLINE_INSTANCE to prevent web request dispatch
// (system/bootstrap.php checks for this at line 195)
define('ROLINE_INSTANCE', 'testing');

// Override error display for tests (index.php turns it off)
ini_set('display_errors', '1');

// Load Rachie via public/index.php
// This properly boots the framework with all dependencies
require_once RACHIE_ROOT . '/public/index.php';

// Load base RolineTest class for tests
require_once __DIR__ . '/RolineTest.php';
