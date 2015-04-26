<?php

return	function() use($config, $url){

	//set the development environment
	if($config['development'] === true)
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

	
	//Load the defined routes file into array
	try{

		if ( ! $routes = @include __DIR__ . '/../application/routes.php')

			throw new Core\Exceptions\FileNotFoundException("The routes.php file cannot be found!");
			
	}
	catch(Core\Exceptions\FileNotFoundException $error){

		$error->show();

		return;
	}

	$routeObj = new Core\Drivers\Routes\Implementation();

	//there is a defined route
	if ( $routeObj->dispatch($url, $routes) ) 
	{
		//get the controller name
		($controller = $routeObj->getController()) || ($controller = 'Home');

		//get the action name
		($action = $routeObj->getMethod()) || ($action = 'Index');

		//get parameters
		$parameters = $routeObj->getParameters();

	}
	//there is no defined route
	else
	{
		//create an instance of the url parser
		$urlObj = new Core\Drivers\Utilities\UrlParser($url);

		//get the controller name
		($controller = $urlObj->getController()) || ($controller = 'Home');

		//get the action name
		($action = $urlObj->getMethod()) || ($action = 'Index');

		//get parameters
		$parameters = $urlObj->getParameters();

	}

	//check if this class and method exists
	try {

		//get the namespaced controller class
		$controller 	= 'Controllers\\' . ucwords($controller) . 'Controller';

		if( ! class_exists($controller) ) throw new Core\Exceptions\ClassNotFoundException("The class " . $controller . ' is undefined');
		
		if( ! (int)method_exists($controller, $action) )
		{
			//create instance of this object
			$dispatch = new $controller;

			//throw exception if no method can be found
			if( ! $dispatch->$action() ) throw new Core\Exceptions\MethodNotFoundException("Access to undefined method " . $controller . '->' . $action);
			
			//fire up application
			$dispatch->$action();


		}

		//method exists, go ahead and dispatch
		else $dispatch = new $controller; call_user_func_array(array($dispatch, $action), $parameters = array());

	}
	catch(Core\Exceptions\ClassNotFoundException $error){

		$error->show();

		return;

	}
	catch(Core\Exceptions\MethodNotFoundException $error){

		$error->show();

		return;

	}

};	
