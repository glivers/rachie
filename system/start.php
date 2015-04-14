<?php

return	function(){

	/**
	 *global $config array variable
	 *This array contains the basic configuration information for this application
	 */
	global $config;

	/**
	 *This string contains the uri segments for this particulat request
	 */
	global $url;

	/**
	 *Define the query string array
	 */
	$queryString = array();

	/**
	 *Check for development environment to use in setting the manner in which errors are displayed
	 *
	 */
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

	/**
	 *Explode uri string into array to get the uri components
	 *
	 */
	$urlArray = array();
	$urlArray = explode("/", $url);

	//check is a controller was specified
	if( count($urlArray) > 1)
	{
		//extract controller from first array element
		$controller = $urlArray[0];

		//remove the controller after extracting it
		array_shift($urlArray);

		//check to see if an action has been specified
		if( count($urlArray) > 0)
		{
			//get the action as the second element
			$action = $urlArray[0];

			//remove the action from array after extracting it
			array_shift($urlArray);

			//get the uri segments if they exist
			$queryString = @$urlArray;

		}
		//there is no action specified, assign the default action
		else
		{
			//get the default action from the config array
			$action = $config['default']['action'];

		}


	}
	//no url specified, use the default controller
	else
	{
		//set the default controller		
		$controller = $config['default']['controller'];

		//set the default action
		$action = $config['default']['action'];

	}

	//get the namespaced controller class
	$controller 	= 'Controllers\\' . ucwords($controller) . 'Controller';
	$dispatch 		= new $controller();

	if((int)method_exists($controller, $action))
	{
		call_user_func_array(array($dispatch, $action), $queryString);

	} 

	else
	{
		/** Error generation code here **/

	}

};	
