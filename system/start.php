<?php

return	function() use($config){

	//Load the defined routes file into array
	try{

		//check of the routes configuration file exists
		if ( ! file_exists( __DIR__ . '/../application/routes.php'))

			throw new Exceptions\Routes\RouteException("The defined routes file cannot be found! Please restore if you deleted");
		
		//get the defined routes
		$definedRoutes = include __DIR__ . '/../application/routes.php';

	}
	catch(Drivers\Routes\RouteException $ExceptionObjectInstance){

		//display the error message to the browser
		$ExceptionObjectInstance->errorShow();
 
	}

	//create an instance of the UrlParser Class, 
	$UrlParserObjectInstance = new Drivers\Utilities\UrlParser(Drivers\Registry::getUrl());

	//and set controller, method and request parameter
	$UrlParserObjectInstance->setController()->setMethod()->setParameters();

	//create an instance of the route parser class
	$RouteParserObject = new Drivers\Routes\RouteParser(Drivers\Registry::getUrl(), $definedRoutes, $UrlParserObjectInstance);

	//there is a defined route 
	if ( $RouteParserObject->dispatch() ) 
	{
		//get the controller name
		($controller = $RouteParserObject->getController()) || ($controller = $config['default']['controller']);

		//get the action name
		($action = $RouteParserObject->getMethod()) || ($action = $config['default']['action']);

		//get parameters
		$parameters = $RouteParserObject->getParameters();

	}
	//there is no defined route 
	else
	{
		//get the controller name
		($controller = $UrlParserObjectInstance->getController()) || ($controller = $config['default']['controller']);

		//get the action name
		($action = $UrlParserObjectInstance->getMethod()) || ($action = $config['default']['action']);

		//get parameters
		$parameters = $UrlParserObjectInstance->getParameters();

	}

	//check if this class and method exists
	try {

		//get the namespaced controller class
		$controller 	= 'Controllers\\' . ucwords($controller) . 'Controller';

		if( ! class_exists($controller) ) throw new Drivers\Routes\RouteException("The class " . $controller . ' is undefined');
		
		if( ! (int)method_exists($controller, $action) )
		{
			//create instance of this object
			$dispatch = new $controller;

			//throw exception if no method can be found
			if( ! $dispatch->$action() ) throw new Drivers\Routes\RouteException("Access to undefined method " . $controller . '->' . $action);
			
			//get the method name
			$action = $dispatch->$action();

			//fire up application
			$dispatch->$action();

		}

		//method exists, go ahead and dispatch
		else $dispatch = new $controller; call_user_func_array(array($dispatch, $action), $parameters = array());

	}
	catch(Drivers\Routes\RouteException $ExceptionObjectInstance){

		//display the error message to the browser
		$ExceptionObjectInstance->errorShow();

	}

};	
