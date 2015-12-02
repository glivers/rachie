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
use Drivers\Inspector;
use Helpers\ArrayHelper;
use Helpers\Input;
use Drivers\Utilities\UrlParser;
use Drivers\Routes\RouteException;

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

		//check if this route index exists
		$RouteMatch = (ArrayHelper::KeyExists($this->UrlParserObjectInstance->getController(), $this->definedRoutesArray)) ? true : false;

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
	protected function setController($controllerToLookUp){
		
		//explode the metaData into controller and method
		$routeMetaDataArray = ArrayHelper::parts($this->pattern, $this->routeMetaData)->clean()->trim()->get();


		//check if  a controller is defined for this route
		try{

			if ( ! (int)ArrayHelper::KeyExists(0, $routeMetaDataArray) || empty($routeMetaDataArray[0])) {

				throw new RouteException("There is no controller associated with this route! -> " . $this->routeName);
				
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

		//set the method
		$this->method = isset($routeMetaArray[1]) ? $routeMetaArray[1] : $method;
		
		//set the parameters array
		$this->parameters = @array_slice($routeMetaArray, 2);

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
		//check if method metadata is not null
		if(is_null($this->methodMetaData)){

			//set method to null and return this object instace without parsing anything
			$this->method = null;

			return $this;

		}

		//there is method metadata
		else{

			//get the methodMetaDataArray
			$methodMetaDataArray = ArrayHelper::parts($urlParameterSeparator, $methodMetaData)->clean()->trim()->get();
			
			//set the value of the method property
			$this->method = (count($methodMetaDataArray) > 0) ? $methodMetaDataArray[0] : null;

			//set the methodMetaDataArray property
			$this->methodMetaDataArray = $methodMetaDataArray;

			//returnt this class instance
			return $this;

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
		//set the requestParamKeys
		$requestParamKeys = (count($methodMetaDataArray) > 1) ? ArrayHelper::slice($methodMetaDataArray, 2)->get() : null;
		
		//set the requestParamKeys
		$this->requestParamKeys = $requestParamKeys;

		//check if the requestParamKeys contain values
		if(count($this->requestParamKeys) > 0){

			//get the url parameter from the UrlParserObjectInstance
			$requestParamValues = $this->UrlParserObjectInstance->getParameters();

			//get the number of keys
			$requestParamKeysLen = count($requestParamKeys);

			//check if the keys are more than then values
			if($requestParamKeysLen >= count($requestParamValues)){

				//padd the $requestParamValues with null values
				$requestParamValues = array_pad($requestParamValues, $requestParamKeysLen, null);

				//combine the two arrays into one
				$requestParams =  array_combine($requestParamKeys, $requestParamValues);

				//populate the Input Class data
				Input::setGetData()->setPostData()->setUrlParamRequestData($requestParams);
				
				//return this object instance
				return $this;

			}

			//the values are more than the keys
			else{

				//split the array to only remain with the numeber defined inthe keys
				$requestParamValues = ArrayHelper::slice($requestParamValues, 1, $requestParamKeysLen)->get();
				
				//combine the two arrays into one
				$requestParams =  array_combine($requestParamKeys, $requestParamValues);

				//populate the Input Class data
				Input::setGetData()->setPostData()->setUrlParamRequestData($requestParams);
				
				//return this object instance
				return $this;

			}

		}

		//no predefined keys, so we assign all values to Input Class
		else{

			//populate the Input Class data
			Input::setGetData()->setPostData()->setUrlParamRequestData($this->UrlParserObjectInstance->getParameters());
			
			//return this object instance
			return $this;

		} 

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
