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

	//check if bootstrap.php file exists
	if ( ! file_exists( __DIR__ . '/../system/bootstrap.php')) {
		
		throw new Exception("The boostrap.php file could not be found! Restore if you deleted this file...");
		
	}

	//load the bootstrap file
	require_once __DIR__ . '/../system/bootstrap.php';

}
catch(Exception $e){

$error =<<<ERROR
	<!DOCTYPE html>
	<html>
	<head>
	    <meta charset="utf-8">
	    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1">
	    <meta name="description" content="">
	    <meta name="author" content="">

		<title>Gliver MCV Application Launch Error </title>

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

	</style>	

	<div class="container">
		
		<p>{$e->getMessage()} This is an important configuration file!</p>

	</div>
	</body>
	</html>
ERROR;

//ouput the error
echo $error;

}


