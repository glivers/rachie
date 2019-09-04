<?php namespace Drivers\Routes;

/**
 *This Route class maps a request to the appropriate controller and action.
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Drivers
 *@package Drivers\Routes\RouteParser
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Registry;
use Drivers\Utilities\UrlParser;
use Drivers\Routes\RouteException;
use Helpers\ArrayHelper\ArrayHelper;
use Helpers\Input\Input;

class RouteParser extends BaseRouteClass {

	/**
	 *@var string $url The input url string to be parsed for controllers, methods and parameters
	 */
	private $urlString;

	/**
	 *@var array $routes The array of all defined routes
	 */
	protected $definedRoutesArray = array();

	/**
	*@var Object \UrlParser $UrlParserObjectInstance containing evaluated params
	*/
	protected $UrlParserObjectInstance;

	/**
	 *@var array $keys An array of keys to map request params
	 *
	 */
	protected $requestParamKeys = array();

	/**
	 *@var string $name The route name  to search
	 */
	protected $routeName;

	/**
	*This method gets the default parameters for this instance of the RouteParser
	*
	*@param string $urlString The Url request string to parser
	*@param array $definedRoutesArray The defined routes in an array
	*@param Object \UrlParser The instance of the UrlParser class
	*@return Object \RouteParser
	*/
	public function __construct($urlString, array $definedRoutesArray, UrlParser $UrlParserObjectInstance){

		//set the value of the $urlString 
		$this->urlString = $urlString;

		//set the value of the routes property
		$this->definedRoutesArray = $definedRoutesArray;

		//set the value of the UrlParserObjectInstance
		$this->UrlParserObjectInstance = $UrlParserObjectInstance;

		//return this object instance
		return $this;

	}

	/**
	 *This method populates the $routes array with all the defined routes
	 *
	 *@param array $definedRoutes Array of all the defined routes
	 *@return Object \RouteParser
	 */
	private function setRoutes(array $definedRoutes)
	{
		//assign the value of the input $definedRoutes to the $routes property
		$this->routes = $definedRoutes;

		//return this obeject instance
		return $this;

	}

	/**
	 *This method returns the contents of the routes array
	 *
	 *@param null
	 *@return array All defined routes in array
	 */
	private function getRoutes()
	{
		//get and return the routes array
		return $this->routes;

	}

	/**
	 *This methods launches the routing functionality of this class
	 *
	 *@param null
	 *
	 */
	public function matchRoute()
	{

		//check if this route key exists
		$RouteMatch = (ArrayHelper::KeyExists($this->UrlParserObjectInstance->getController(), $this->definedRoutesArray)->get()) ? true : false;

		//a matching route was found, set params and return true
		if($RouteMatch)
		{
			//set the value of the routeName
			$this->routeName = $this->UrlParserObjectInstance->getController();

			//get metaData for this route
			$this->routeMetaData = $this->definedRoutesArray[$this->UrlParserObjectInstance->getController()];

			//return true
			return true;

		}

		//not matching route was found, return fasle
		else{

			//return false
			return false;

		}

	}

	/**
	*This method sets the controller value for this url instance
	*
	*@param string $controllerToLookUp The controller to try and map
	*@return Object \RouteParser
	*/
	public function setController(){
		
		//explode the metaData into controller and method
		$routeMetaDataArray = ArrayHelper::parts($this->pattern, $this->routeMetaData)->clean()->trim()->get();

		//check if  a controller is defined for this route
		try{

			if ( ! (int)ArrayHelper::KeyExists(0, $routeMetaDataArray)->get() || empty($routeMetaDataArray[0])) {

				throw new RouteException(get_class(new RouteException) ." : There is no controller associated with this route! -> " . $this->routeName);
				
			}

			//set the controller for this route
			$this->controller = $routeMetaDataArray[0];

			//set the method meta data
			$this->methodMetaData = (count($routeMetaDataArray) > 1) ? $routeMetaDataArray[1] : null;

			//return this object instance
			return $this;

		}
		catch(RouteException $RouteExceptionObjectInstance){

			//display the error message
			$RouteExceptionObjectInstance->errorShow();

		}

	}


	/**
	 *This method returns the controller name
	 *
	 *@param null
	 *@return string Controller name
	 */
	public function getController()
	{
		//return the contoller name
		return $this->controller;

	}

	/**
	 *This method sets the value of the method name
	 *
	 *@param null
	 *@return Object \RouteParser
	 */
	public function setMethod()
	{
		//check if method metadata is null
		if(is_null($this->methodMetaData)){

			//if there was no method metaData check if there are names url param keys  in the controller name
			$requestParamKeys = ArrayHelper::parts($this->urlParameterSeparator, $this->controller)->clean()->trim()->get();

			//check if there are parameter found
			if(count($requestParamKeys) > 1){

				//set the new value of the controller
				$this->controller = $requestParamKeys[0];

				//set a value for the methodMetaData
				$this->methodMetaDataArray = $requestParamKeys;

			}

			//there is no method frm the routes, get the route from the UrlParser instance
			$this->method = $this->UrlParserObjectInstance->getMethod();

			return $this;

		}

		//there is method metadata
		else{

			//get the methodMetaDataArray
			$methodMetaDataArray = ArrayHelper::parts($this->urlParameterSeparator, $this->methodMetaData)->clean()->trim()->get();
			
			//put this in a try...catch block to enhance error handlign
			try{

				if(count($methodMetaDataArray) > 0){
				 	
				 	//set the value of the method property
					$this->method = $methodMetaDataArray[0];
					
					//check if the value of method from url parse is not null
					if($this->UrlParserObjectInstance->getMethod() !== null){

						//if the method name can be got from the methodMetaData, then lets prepend the value of method to the urlParser parameters
						$this->UrlParserObjectInstance->setParameters($this->UrlParserObjectInstance->getMethod(), false);

					}

					//set the methodMetaDataArray property
					$this->methodMetaDataArray = $methodMetaDataArray;
					
					//returnt this class instance
					return $this;

				}

				//there was not method data found after parsing metaData, this is wrong, throw an error
				else{

					//throw an exception
					throw new RouteException(get_class(new RouteException) ." : The method name specified after this named route " . $this->routeName . " => " . $this->controller . "@" . $this->methodMetaData . " is invalid format", 1);
					
				}

			}

			catch(RouteException $RouteExceptionObjectInstance){

				//display the error message
				$RouteExceptionObjectInstance->errorShow();

			}

		}

	}

	/**
	 *This method returns the method name
	 *
	 *@param null
	 *@return string Method name
	 */
	public function getMethod()
	{
		//return the Method name
		return $this->method;

	}

	/**
	 *This method sets the value of the request parameter
	 *
	 *@param null
	 *@return Object \RouteParser
	 */
	public function setParameters()
	{		
		//set the url parameter from the UrlParserObjectInstance
		$this->parameters = $this->UrlParserObjectInstance->getParameters();

		//populate the Input Class data
		Input::setGet()->setPost();
		
		//return this object instance
		return $this;

	}

	/**
	 *This method returns the parameters array
	 *
	 *@param null
	 *@return string Method name
	 */
	public function getParameters()
	{
		//return the Method name
		return $this->parameters;

	}

}
