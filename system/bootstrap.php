<?php 

/**
 *This boostrap.php file is  the excecution point of this application.
 *All Helper and Core classes are called from here to aid is system excecution
 * @author Geoffrey Okongo <code@gliver.org>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Core
 * @package Bootstrap
 * @link https://github.com/gliverphp/gliver
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

    //check for absence of console instance
    if( ! defined('CONSOLE_INSTANCE') ) {

		//this is a web request, launch the routing
		//get closure object instance to launch application
		$start = require_once __DIR__ . '/start.php';

		//lauch application instance
		$start();

    }
    
} 
catch(Exception $e) {

	//check for console request, and reduce verbose error message
	if ( defined('CONSOLE_INSTANCE') ) {
		
		//return the error message unformated
		echo $e->getMessage(); exit();

	} 

	//this is a web request, format message
	else {

		$showErrorMessage = $e->getMessage();


        //load the show error view file
        include dirname(__FILE__) . '/Exceptions/ErrorPageHtml.php';

        //stop script execution
		exit();

	}
	

}