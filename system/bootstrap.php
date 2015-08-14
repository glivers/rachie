<?php 

/**
 *This boostrap.php file is  the excecution point of this application.
 *All Helper and Core classes are called from here to aid is system excecution
 * @author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 * @copyright 2015 - 2020 Geoffrey Oliver
 * @category Core
 * @package Bootstrap
 * @link https://github.com/gliver-mvc/gliver
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.1
 */

//check for the existance of important system configuration files
try{

	//check if the composer/vendor/autoloader file exists
	if ( ! file_exists( __DIR__ . '/../vendor/autoload.php') ) {

		throw new Exception("The composer autoloader file is missing! Please run composer from your terminal to install. System Exit...");
		
	}

	//check if the configuration file is present
	if ( ! file_exists(__DIR__ . '/../config/config.php') ) {

		throw new Exception("The system configuration file is missing! Please restore if you deleted. System Exit...");
		
	}

 	//check if the database settings file is present
	if ( ! file_exists(__DIR__ . '/../config/database.php') ) {

		throw new Exception("The system database settings file is missing! Please restore if you deleted. System Exit...");
		
	}

	//check if the start.php file is present
	if ( ! file_exists(__DIR__ . '/start.php') ) {

		throw new Exception("The System Application Start() file not found! Please restore if you deleted. System Exit...");
		
	}

	//check if the constants.php file is present
	if ( ! file_exists(__DIR__ . '/../application/constants.php') ) {

		throw new Exception("The constants definition file not found! Please restore if you deleted. System Exit...");
		
	}

	//check if the  base shutdown debug file is present
	if ( ! file_exists(__DIR__ . '/Exceptions/Debug/BaseShutdown.php') ) {

		throw new Exception("The System Application Default ErrorHandler not found! Please restore if you deleted. System Exit...");
		
	}

	//load system configuration settings into array	 
	$config = require_once __DIR__ . '/../config/config.php';

	//define the development environment
	if ( isset($config) && $config['dev'] == true ) define('DEV', true);
	else define('DEV', false);

	//get the ErrorHandler
	require_once __DIR__ . '/Exceptions/Debug/BaseShutdown.php';
	
	//initilize native session
	session_start(); 

	//get the composer autoloader.php file
	require_once __DIR__ . '/../vendor/autoload.php';

	//load the defined constants
	require_once __DIR__ . '/../application/constants.php';
	
	//load system database settings into array	 
	$database = require_once __DIR__ . '/../config/database.php';

	//include the initializatoin file
	require_once __DIR__ . '/initialize.php';

	//set the class aliases, so they are available to our closure objects
    //loop through aliases autoloading
    foreach (Drivers\Registry::getConfig()['aliases'] as $aliasNamespace => $aliasName) 
    {
        //register and lazy autoload the aliases(class)
        class_alias($aliasNamespace, $aliasName);

    }

	//get closure object instance to launch application
	$start = require_once __DIR__ . '/start.php';

	//lauch application instance
	$start();

} 
catch(Exception $e) {

$error =<<<ERROR
	<!DOCTYPE html>
	<html>
	<head>
	    <meta charset="utf-8">
	    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1">
	    <meta name="description" content="">
	    <meta name="author" content="">

		<title>Gliver Error!</title>

	</head>
	<body>

	<style type="text/css">
		
		.container {

			width : 960px;
			min-height: 100px;
			background-color: rgba(0,0,0,0.08);
			font-size: 16px;
			margin: auto;
			color: rgba(0, 128, 0, 1);
		}

		span.lead {
			color : rgba(255,0,0,1);
		}

	</style>	

	<div class="container">
		
		<p> <span class="lead">0x Error : </span>{$e->getMessage()}</p>

	</div>
	</body>
	</html>
ERROR;

//ouput the error
echo $error;

exit();

}