<?php 

/**
 *This boostrap.php file is  the excecution point of this application.
 *All Helper and Core classes are called from here to aid is system excecution
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Bootstrap
 *@link core.gliver.io
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

//initilize native session
session_start();

//get the get data from url request
$url = isset($_GET['url']) ? $_GET['url'] : '';


/**
 *load the composer autoloader file
 */
require_once __DIR__ . '/../vendor/autoload.php';

/**
 *Define the configuration global variable
 */
$config = require_once __DIR__ . '/../config/config.php';

$start = require_once __DIR__ . '/start.php';

/**
 *Launch this application instance
 */
$start();