<?php

return	function(){

	//specify the global variables required
	global $config;
	global $url;

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

};	
