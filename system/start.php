<?php

return	function() use($config){

	//Load the defined routes file into array
	try{

		//check of the routes configuration file exists
		if ( ! file_exists( __DIR__ . '/../application/routes.php'))

			throw new Rackage\Routes\RouteException("Rackage\Routes\RouteException : The defined routes.php file cannot be found! Please restore if you deleted");
		
		//get the defined routes
		$definedRoutes = include __DIR__ . '/../application/routes.php';

	}
	catch(Rackage\Routes\RouteException $ExceptionObjectInstance){

		//display the error message to the browser
		$ExceptionObjectInstance->errorShow();
 
	}

	//create an instance of the UrlParser Class, 
	$UrlParserObjectInstance = new Rackage\Utilities\UrlParser(Rackage\Registry\Registry::getUrl(), Rackage\Registry\Registry::getConfig()['url_component_separator']);

	//and set controller, method and request parameter
	$UrlParserObjectInstance->setController()->setMethod()->setParameters();

	//create an instance of the route parser class
	$RouteParserObject = new Rackage\Routes\RouteParser(Rackage\Registry\Registry::getUrl(), $definedRoutes, $UrlParserObjectInstance);

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

		if( ! class_exists($controller) ) throw new Rackage\Routes\RouteException("Rackage\Routes\RouteException : The class " . $controller . ' is undefined');
		
		if( ! (int)method_exists($controller, $action) )
		{
			//create instance of this object
			$dispatch = new $controller;

			//throw exception if no method can be found
			if( ! $dispatch->$action() ) throw new Rackage\Routes\RouteException("Rackage\Routes\RouteException : Access to undefined method " . $controller . '->' . $action);
			
			//get the method name
			$action = $dispatch->$action();

		} 

		//method exists, go ahead and dispatch
		else $dispatch = new $controller;

		//ensure the controller is an instance of the Controllers\BaseController class
		if( ! $dispatch instanceof Controllers\BaseController) throw new Rackage\Routes\RouteException("Rackage\Routes\RouteException : $controller class must extend Controllers\BaseController class!");
		
		//check if the controller class uses the Rackage\Controllers\BaseControllerTrait
		//if( ! array_key_exists('[Controllers\BaseController]', class_uses('Controllers\BaseController', true))) throw new Rackage\Routes\RouteException("Rackage\Routes\RouteException : Controllers\BaseController class must use Rackage\Controllers\BaseControllerTrait!");
		
		//set the controller defaults
		$dispatch->set_rachie_fr_controller_trait_properties();

		//get the Inspector class object
		$classpect =  new ReflectionClass($dispatch);
		$inspector = $classpect->getMethod($action);

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

		    // Get class-level filters
		    $class_filter_methods = Rackage\Utilities\Inspector::checkFilter($classpect->getDocComment());
		    
		    // Get method-level filters (existing code)
		    $method_filter_methods = Rackage\Utilities\Inspector::checkFilter($inspector->getDocComment());
		    
		    // Merge filters: class @before, then method @before
		    $combined_filters = [
		        'before' => [],
		        'after' => []
		    ];
		    
		    // Add class-level @before filters first
		    if ($class_filter_methods && isset($class_filter_methods['before'])) {
		        $combined_filters['before'] = array_merge(
		            $combined_filters['before'],
		            $class_filter_methods['before']
		        );
		    }
		    
		    // Add method-level @before filters
		    if ($method_filter_methods && isset($method_filter_methods['before'])) {
		        $combined_filters['before'] = array_merge(
		            $combined_filters['before'],
		            $method_filter_methods['before']
		        );
		    }
		    
		    // Add method-level @after filters first
		    if ($method_filter_methods && isset($method_filter_methods['after'])) {
		        $combined_filters['after'] = array_merge(
		            $combined_filters['after'],
		            $method_filter_methods['after']
		        );
		    }
		    
		    // Add class-level @after filters last
		    if ($class_filter_methods && isset($class_filter_methods['after'])) {
		        $combined_filters['after'] = array_merge(
		            $combined_filters['after'],
		            $class_filter_methods['after']
		        );
		    }
		    
		    // Now use $combined_filters instead of $filter_methods
		    $filter_methods = $combined_filters;

			try {

			    if($filter_methods === false){
			        //launch the infered method for this request
			        call_user_func_array(array($dispatch, $action), $method_params_array);
			    }
			    else {
			        // ========== EXECUTE ALL @BEFORE FILTERS ==========
			        if(isset($filter_methods['before'])) {
			            // Loop through each before filter
			            foreach($filter_methods['before'] as $filter) {
			                switch (count($filter)) 
			                {
			                    case 1:
			                        //throw exception if the filter method does not exist
			                        if( ! (int)method_exists($controller, $filter[0])) {
			                            throw new Rackage\Routes\RouteException(
			                                "Rackage\Routes\RouteException : The method {$filter[0]} specified as filter in $controller :: $action is undefined.", 
			                                1
			                            );
			                        }
			                        
			                        //call the before filter
			                        $dispatch->$filter[0]();
			                        break;
			                    
			                    case 2:
			                        //check the filter class and method
			                        //throw exception if the filter method does not exist
			                        if( ! class_exists($filter[0])) {
			                            throw new Rackage\Routes\RouteException(
			                                "Rackage\Routes\RouteException : The class {$filter[0]} specified as filter in $controller :: $action is undefined.", 
			                                1
			                            );
			                        }
			                        
			                        if( ! (int)method_exists($filter[0], $filter[1])) {
			                            throw new Rackage\Routes\RouteException(
			                                "Rackage\Routes\RouteException : The method {$filter[1]} specified as filter in $controller :: $action is undefined.", 
			                                1
			                            );
			                        }
			                        
			                        //call the before filter
			                        (new $filter[0]())->$filter[1]();
			                        break;
			                }
			            }
			        }
			        
			        // ========== EXECUTE CONTROLLER METHOD ==========
			        call_user_func_array(array($dispatch, $action), $method_params_array);
			        
			        // ========== EXECUTE ALL @AFTER FILTERS ==========
			        if(isset($filter_methods['after'])){
			            // Loop through each after filter
			            foreach($filter_methods['after'] as $filter) {
			                switch (count($filter)) 
			                {
			                    case 1:
			                        //check if the method specified in the after filter does not exist and throw error
			                        if( ! (int)method_exists($dispatch, $filter[0])) {
			                            throw new Rackage\Routes\RouteException(
			                                "Rackage\Routes\RouteException : The method {$filter[0]} specified as filter in $controller :: $action is undefined.", 
			                                1
			                            );
			                        }
			                        
			                        //call the after filter
			                        $dispatch->$filter[0]();
			                        break;
			                    
			                    case 2:
			                        //check if the class and method specified in the after filter does not exist and throw error
			                        if( ! class_exists($filter[0])) {
			                            throw new Rackage\Routes\RouteException(
			                                "Rackage\Routes\RouteException : The class {$filter[0]} specified as filter in $controller :: $action is undefined.", 
			                                1
			                            );
			                        }
			                        
			                        if( ! (int)method_exists($filter[0], $filter[1])) {
			                            throw new Rackage\Routes\RouteException(
			                                "Rackage\Routes\RouteException : The method {$filter[1]} specified as filter in $controller :: $action is undefined.", 
			                                1
			                            );
			                        }
			                        
			                        //call the after filter
			                        (new $filter[0]())->$filter[1]();
			                        break;
			                }
			            }
			        }
			        
			    }
			                        
			} 

			catch (Rackage\Routes\RouteException $e) {

			    //display the error message
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
	catch(Rackage\Routes\RouteException $ExceptionObjectInstance){

		//display the error message to the browser
		$ExceptionObjectInstance->errorShow();

	}

};	
