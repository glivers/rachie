<?php

/**
 *This index.php file is  the single entry point to this application.
 *All Helper and Core classes are called from here to aid is system excecution
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Index
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

//check if the boostrap file exists and load
try{
	
	//start the php_engine_launch timer
	$gliver_app_start = microtime(true);

	/**
	 *This function defines the types of errors to be reported
	 *@param const The error type to report
	 *@return void
	 */
	error_reporting(E_ALL);
	/**
	 *This method  sets the value of a configuration option
	 *we set it to not display erros so that we can handle this with our custom shutdown
	 *@param sting The option to set
	 *@param string The value of the option set
	 *@return null
	 */
	ini_set('display_errors', 'Off');
	/**
	 *This method  sets the value of a configuration option
	 *we set it to write errors to a file so that we can use for debug
	 *@param sting The option to set
	 *@param string The value of the option set
	 *@return null
	 */
	ini_set('log_errors', 'On');
	/**
	 *This method  sets the value of a configuration option
	 *we set the file on which to write error so that we are better able to handle errors
	 *@param sting The option to set
	 *@param string The value of the option set
	 *@return null
	 */
	ini_set('error_log', dirname(dirname(__FILE__)) . '/bin/logs/error.log');
	
	//check if bootstrap.php file exists
	if ( ! file_exists( __DIR__ . '/../system/bootstrap.php')) {
		
		throw new Exception("The boostrap.php file could not be found! Restore if you deleted this file...");
		
	}

	//load the bootstrap file
	require_once __DIR__ . '/../system/bootstrap.php';

}
catch(Exception $e){

	//check for console request, and reduce verbose error message
	if ( defined('CONSOLE_INSTANCE' ) ) {
		
		//return the error message unformated
		echo $e->getMessage(); exit();

	} 

	//this is a web request, format message
	else {

		$showErrorMessage = $e->getMessage();

		//load the error page html 
		include dirname(dirname(__FILE__)) . "/system/Exceptions/ErrorPageHtml.php"; 

		//stop further script execution
		exit();

	}

}


