<?php

//This class has been taken from 
//http://kuwamoto.org/2007/12/17/improved-pluralizing-in-php-actionscript-and-ror
//Type: MIT License
//Changes: A few changes to add custom irregularWords from config/inflection.php

class Inflection {

	static $plural = array(

			'/(quiz)$/i'				=> "$1zes",
			'/^(ox)$/i'					=> "$1en",
			'/([m|l])ouse$/i'			=> "$1ice",
			'/(matr|vert|ind)ix|ex$/i'	=> "$1ices",
			'/(x|ch|ss|sh)$/i'			=> "$1es"



	);

}

/** check for magic quotes and remove them **/

function stripSlashesDeep($value)
{
	$value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value);

	return $value;

}

function removeMagicQuotes()
{
	if(get_magic_quotes_gpc())
	{
		$_GET 		= stripSlashesDeep($_GET	);
		$_POST 		= stripSlashesDeep($_POST	);
		$_COOKIE 	= stripSlashesDeep($_COOKIE	);

	}

}

/** check register globals and remove them **/

function unregisterGlobals()
{
	if(ini_get('register_globals'))
	{
		$array = array('_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

		foreach ($array as $value) 
		{
			foreach ($GLOBALS[$value] as $key => $var) 
			{
				if($var === $GLOBALS[$key])
				{
					unset($GLOBALS[$key]);

				}

			}

		}

	}

}




function setReporting()
{
	//define the config global variable for access
	global $config;

	if($config['development'] == true)
	{
		error_reporting(E_ALL);
		ini_set('display_errors', 'On');

	}

	else
	{
		error_reporting(E_ALL);
		ini_set('display_errors', 'Off');
		ini_set('log_errors', 'On');
		ini_set('error_log', ROOT.DS.'tmp'.DS.'logs'.DS.'error.log');

	}

}


/** main call function **/

function callHook()
{
	global $url;

	$urlArray = array();
	$urlArray = explode("/", $url);

	$controller = $urlArray[0];
	array_shift($urlArray);
	$action = $urlArray[0];
	array_shift($urlArray);
	$queryString = $urlArray;

	$controllerName = $controller;
	$controller 	= ucwords($controller);
	$model 			= rtrim($controller, 's');
	$controller 	= 'Controllers\\' . $controller . 'Controller';
	$dispatch 		= new $controller($model, $controllerName, $action);

	if((int)method_exists($controller, $action))
	{
		call_user_func_array(array($dispatch, $action), $queryString);

	} 

	else
	{
		/** Error generation code here **/


	}

}

setReporting();
removeMagicQuotes();
unregisterGlobals();
callHook();
