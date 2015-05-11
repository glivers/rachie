<?php

return	function() use($config, $url){

	//set the application environment
	if($config['environment'] == 'development')
	{
		error_reporting(E_ALL);
		//ini_set('display_errors', 'On');

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

		//check of the routes configuration file exists
		if ( ! file_exists( __DIR__ . '/../application/routes.php'))

			throw new Exceptions\BaseException("The defined routes file cannot be found! Please restore if you deleted");
		
		//get the defined routes
		$routes = include __DIR__ . '/../application/routes.php';

	}
	catch(Exceptions\BaseException $error){

		$error->show();

		return;
	}

	$routeObj = new Drivers\Routes\Implementation();

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
		$urlObj = new Drivers\Utilities\UrlParser($url);

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

		if( ! class_exists($controller) ) throw new Exceptions\BaseException("The class " . $controller . ' is undefined');
		
		if( ! (int)method_exists($controller, $action) )
		{
			//create instance of this object
			$dispatch = new $controller;

			//throw exception if no method can be found
			if( ! $dispatch->$action() ) throw new Exceptions\BaseException("Access to undefined method " . $controller . '->' . $action);
			
			//get the method name
			$action = $dispatch->$action();

			//fire up application
			$dispatch->$action();

			//stop further script execution
			exit();


		}

		//method exists, go ahead and dispatch
		else $dispatch = new $controller; call_user_func_array(array($dispatch, $action), $parameters = array());

	}
	catch(Exceptions\BaseException $error){

		$error->show();

		return;

	}
	catch(Exceptions\BaseException $error){

		$error->show();

		return;

	}

};	
