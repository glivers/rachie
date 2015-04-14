<?php 

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(dirname(__FILE__)));

$url = $_GET['url'];

/**
 *This loads the composer autoloader file
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
//require_once(ROOT . DS . 'config' . DS . 'routing.php');
//require_once(ROOT . DS . 'config' . DS . 'inflection.php');
require_once(ROOT . DS . 'system' . DS . 'shared.php');
