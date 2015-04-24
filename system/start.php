<?php

return	function(){

	/**
	 *@global array This array contains the basic configuration information for this application
	 *
	 */
	global $config;

	/**
	 *@global string This string contains the uri segments for this particulat request
	 *
	 */
	global $url;

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

	/**
	 *Load the defined routes array
	 */
	try{

		if ( ! $routes = @include __DIR__ . '/../application/routes.php')

			throw new Core\Exceptions\FileNotFoundException("The routes.php file cannot be found!");
			
	}
	catch(Core\Exceptions\FileNotFoundException $error){

		$error->show();

		return;
	}

	$routeObj = new Core\Drivers\Router();

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
		$urlObj = new Core\Drivers\UrlParser($url);

		//get the controller name
		($controller = $urlObj->getController()) || ($controller = 'Home');

		//get the action name
		($action = $urlObj->getMethod()) || ($action = 'Index');

		//get parameters
		$parameters = $urlObj->getParameters();

	}


	//get the namespaced controller class
	$controller 	= 'Controllers\\' . ucwords($controller) . 'Controller';
	$dispatch 		= new $controller();

	if((int)method_exists($controller, $action))
	{
		call_user_func_array(array($dispatch, $action), $parameters);

	} 

	else
	{
		/** Error generation code here **/

	}

};	
