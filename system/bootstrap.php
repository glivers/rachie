<?php 

session_start();

$url = isset($_GET['url']) ? $_GET['url'] : '';

//$url = $_GET['url'];

/**
 *This loads the composer autoloader file
 */
require_once __DIR__ . '/../vendor/autoload.php';

/**
 *Define the configuration global variable
 */
$config = require_once __DIR__ . '/../config/config.php';

$start = require_once __DIR__ . '/start.php';

/**
 *Launch this application
 */
$start();