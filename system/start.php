<?php

return	function() use($config){

	//Load the defined routes file into array
	try{

		//check of the routes configuration file exists
		if ( ! file_exists( __DIR__ . '/../application/routes.php'))

			throw new Drivers\Routes\RouteException("The defined routes.php file cannot be found! Please restore if you deleted");
		
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

	//check if there is infered controller from url string
	if($UrlParserObjectInstance->getController() !== null){

		//check if there is a defined route matched
		if ( $RouteParserObject->matchRoute() ) 
		{
			//if there is a defined route, set the controller, method and parameter properties
			$RouteParserObject->setController()->setMethod()->setParameters();

			//set the value of the controller
			$controller = $RouteParserObject->getController();

			//set the value of the method
			($action = $RouteParserObject->getMethod()) || ($action = $config['default']['action']);

		}

		//there is no defined routes, infer controller and method from the url string
		else{

			//set the parameter properties
			$RouteParserObject->setParameters();

			//get the controller name
			$controller = $UrlParserObjectInstance->getController();

			//set the value of the method
			($action = $UrlParserObjectInstance->getMethod()) || ($action = $config['default']['action']);


		}

	}

	//there is no infered controller from url string, get the defaults 
	else
	{
		//set the parameter properties
		$RouteParserObject->setParameters();

		//get the default controller name
		$controller = $config['default']['controller'];

		//get the default action name
		$action = $config['default']['action'];

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
			$dispatch->set_gliver_fr_controller_trait_properties()->$action();

		}

		//method exists, go ahead and dispatch
		else $dispatch = new $controller; $dispatch->set_gliver_fr_controller_trait_properties()->$action();

	}
	catch(Drivers\Routes\RouteException $ExceptionObjectInstance){

		//display the error message to the browser
		$ExceptionObjectInstance->errorShow();

	}

};	
