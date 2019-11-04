<?php

return	function() use($config){

	//Load the defined routes file into array
	try{

		//check of the routes configuration file exists
		if ( ! file_exists( __DIR__ . '/../application/routes.php'))

			throw new Gliver\Routes\RouteException("Gliver\Routes\RouteException : The defined routes.php file cannot be found! Please restore if you deleted");
		
		//get the defined routes
		$definedRoutes = include __DIR__ . '/../application/routes.php';

	}
	catch(Gliver\Routes\RouteException $ExceptionObjectInstance){

		//display the error message to the browser
		$ExceptionObjectInstance->errorShow();
 
	}

	//create an instance of the UrlParser Class, 
	$UrlParserObjectInstance = new Gliver\Utilities\UrlParser(Gliver\Registry\Registry::getUrl(), Gliver\Registry\Registry::getConfig()['url_component_separator']);

	//and set controller, method and request parameter
	$UrlParserObjectInstance->setController()->setMethod()->setParameters();

	//create an instance of the route parser class
	$RouteParserObject = new Gliver\Routes\RouteParser(Gliver\Registry\Registry::getUrl(), $definedRoutes, $UrlParserObjectInstance);

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

		if( ! class_exists($controller) ) throw new Gliver\Routes\RouteException("Gliver\Routes\RouteException : The class " . $controller . ' is undefined');
		
		if( ! (int)method_exists($controller, $action) )
		{
			//create instance of this object
			$dispatch = new $controller;

			//throw exception if no method can be found
			if( ! $dispatch->$action() ) throw new Gliver\Routes\RouteException("Gliver\Routes\RouteException : Access to undefined method " . $controller . '->' . $action);
			
			//get the method name
			$action = $dispatch->$action();

		} 

		//method exists, go ahead and dispatch
		else $dispatch = new $controller;

		//ensure the controller is an instance of the Controllers\BaseController class
		if( ! $dispatch instanceof Controllers\BaseController) throw new Gliver\Routes\RouteException("Gliver\Routes\RouteException : $controller class must extend Controllers\BaseController class!");
		
		//check if the controller class uses the Gliver\Controllers\BaseControllerTrait
		//if( ! array_key_exists('[Controllers\BaseController]', class_uses('Controllers\BaseController', true))) throw new Gliver\Routes\RouteException("Gliver\Routes\RouteException : Controllers\BaseController class must use Gliver\Controllers\BaseControllerTrait!");
		
		//set the controller defaults
		$dispatch->set_gliver_fr_controller_trait_properties();

		//get the Inspector class object
		$inspector = (new ReflectionClass($dispatch))->getMethod($action);

		//get the number of parameters from the reflector
		$method_params_count = count($inspector->getParameters());
		
		//get the method parameters passed
		$method_params_array = $RouteParserObject->getParameters();

		//check if the keys are more than then values
		if($method_params_count > count($method_params_array)){

			//padd the $method_params_array with null values
			$method_params_array = array_pad($method_params_array, $method_params_count, null);

		}

		//check if method filter are set, and process filter
		if ($dispatch->enable_method_filters === true) 
		{
			//get  method metadata
		    $filter_methods = Gliver\Utilities\Inspector::checkFilter($inspector->getdoccomment());

		    try {

		    	if($filter_methods === false){

					//launch the infered method for this request
					call_user_func_array(array($dispatch, $action), $method_params_array);

		    	}

		    	else {

		    		if(isset($filter_methods['before'])) {

		    			switch (count($filter_methods['before'])) 
		    			{
		    				case 1:
		    					//thow exception if the filter method does not exist.
					    		if( ! (int)method_exists($controller, $filter_methods['before'][0])) throw new Gliver\Routes\RouteException("Gliver\Routes\RouteException : The method {$filter_methods['before'][0]} specified as filter in $controller :: $action is undefined.", 1);
								
								//call the before filter
								$dispatch->$filter_methods['before'][0]();

		    					break;
		    				
		    				case 2:

		    					//check the filter class and method
		    					//thow exception if the filter method does not exist.
		    					if( ! class_exists($filter_methods['before'][0])) throw new Gliver\Routes\RouteException("Gliver\Routes\RouteException : The class {$filter_methods['before'][0]} specified as filter in $controller :: $action is undefined.", 1);
		    					
					    		if( ! (int)method_exists($filter_methods['before'][0], $filter_methods['before'][1])) throw new Gliver\Routes\RouteException("Gliver\Routes\RouteException : The method {$filter_methods['before'][1]} specified as filter in $controller :: $action is undefined.", 1);
								
								//call the before filter
								(new $filter_methods['before'][0]())->$filter_methods['before'][1]();

		    					break;
		    			}

		    		}


		    		if(isset($filter_methods['after'])){

		    			switch (count($filter_methods['after'])) 
		    			{
		    				case 1:
		    					//check if the method specified in teh after filter does not exists and throw error
					    		if( ! (int)method_exists($dispatch, $filter_methods['after'][0])) throw new Gliver\Routes\RouteException("Gliver\Routes\RouteException : The method {$filter_methods['after'][0]} specified as filter in $controller :: $action is undefined.", 1);
	    				
			    				//launch the controller class filter method
								call_user_func_array(array($dispatch, $action), $method_params_array);
				
			    				//call the after filter
								$dispatch->$filter_methods['after'][0]();

		    					break;
		    				
		    				case 2:
		    					//check if the class and method specified in the after filter does not exists and throw error
		    					if( ! class_exists($filter_methods['after'][0])) throw new Gliver\Routes\RouteException("Gliver\Routes\RouteException : The class {$filter_methods['after'][0]} specified as filter in $controller :: $action is undefined.", 1);
					    		if( ! (int)method_exists($filter_methods['after'][0], $filter_methods['after'][1])) throw new Gliver\Routes\RouteException("Gliver\Routes\RouteException : The method {$filter_methods['after'][1]} specified as filter in $controller :: $action is undefined.", 1);
	    				
			    				//launch the controller class filter method
								call_user_func_array(array($dispatch, $action), $method_params_array);
				
			    				//call the after filter
								(new $filter_methods['after'][0]())->$filter_methods['after'][1]();

		    					break;

		    			}

		    		}

		    		else{

						//launch the controller class filter method
						call_user_func_array(array($dispatch, $action), $method_params_array);
	    			
		    		}
					
		    	}
		    			    	
		    } 

		    catch (Gliver\Routes\RouteException $e) {

		    	//dislpay the error message
		    	$e->errorShow();
		    	
		    }

		}

		//no filters set, proceed to load controller class method
		else 
		{
			//launch the infered method for this request
			call_user_func_array(array($dispatch, $action), $method_params_array);

		}
		

	}
	catch(Gliver\Routes\RouteException $ExceptionObjectInstance){

		//display the error message to the browser
		$ExceptionObjectInstance->errorShow();

	}

};	
