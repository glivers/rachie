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
use Drivers\Utilities\UrlParser;

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
	 *@var array $keys An array of key/value pairs
	 *
	 */
	protected $keys = array();

	/**
	 *@var string $name The route name  to search
	 */
	protected $name;

	/**
	*This method gets the default parameters for this instance of the RouteParser
	*
	*@param string $urlString The Url request string to parser
	*@param array $definedRoutesArray The defined routes in an array
	*@return Object \RouteParser
	*/
	public function __construct($urlString, array $definedRoutesArray){

		//set the value of the $urlString
		$this->urlString = $urlString;

		//set the value of the routes property
		$this->definedRoutesArray = $definedRoutesArray;

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
	public function dispatch()
	{

		//create instance of the UrlParser object
		$UrlParserObjectInstance = new UrlParser($url);

		//check for defined route
		return $this->match($UrlParserObjectInstance->getController(), $UrlParserObjectInstance->getMethod()) ? true : false;

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

	/**
	*This method sets the controller value for this url instance
	*
	*@param string $controllerToLookUp The controller to try and map
	*@return Object \RouteParser
	*/
	protected function setController($controllerToLookUp){


	}

	/**
	 *This method checks for the existance of a defined route
	 *
	 *@param string $controller The name of the controller to match
	 *@param string $method The name of the method to match
	 *@return (bool) true|false True if route exists and false if not
	 */
	private function match($inferedController, $inferedMethod)
	{
		//check if this route index exists
		$match = ArrayHelper::KeyExists($inferedController, $this->routes) ? true : false;

		//if this match is true, set the controller and method
		if($match)
		{
			//get metaData for this route
			$routeMeta = $this->routes[$name];

			//explode the metaData into controller and method
			$routeMetaArray = explode('@', $routeMeta);

			//procede if array has elements
			if( sizeof($routeMetaArray) > 0 )
			{
				//sanitize routeMetaArray
				$routeMetaArray = ArrayUtility::trim(ArrayUtility::clean($routeMetaArray));

			}

			//check if  a controller is defined for this route
			try{

				if ( ! (int)array_key_exists(0, $routeMetaArray) || empty($routeMetaArray[0])) {

					throw new RouteControllerNotDefinedException("There is no controller associated with this route! -> " . $name);
					
				}

				//set the controller for this route
				$this->controller = $routeMetaArray[0];

			}
			catch(RouteControllerNotDefinedException $error){

				$error->show();

				exit();

			}

			//set the method
			$this->method = isset($routeMetaArray[1]) ? $routeMetaArray[1] : $method;
			
			//set the parameters array
			$this->parameters = @array_slice($routeMetaArray, 2);

			return true;

		}

		return false;

	}

}
