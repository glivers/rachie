<?php namespace Drivers\Routes;

/**
 *This class routes a request to the appropriate controller and action.
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers
 *@link core.gliver.io
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Utilities\ArrayUtility; 
use Drivers\Registry;
use Drivers\Inspector;
use Drivers\Utilities\UrlParser;

class Implementation extends Base {

	/**
	 *@var array $keys An array of key/value pairs
	 *
	 */
	protected $keys = array();

	/**
	 *@var string $name The route name string to search
	 */
	protected $name;

	/**
	 *@var array $routes The array if all defined routes
	 */
	protected $routes = array();

	/**
	 *This method populates the $routes array with all the defined routes
	 *
	 *@param array $definedRoutes Array of all the defined routes
	 *@return void
	 */
	private function addRoute(array $definedRoutes)
	{
		//loop through the input array adding each element to the $routes array
		foreach ($definedRoutes as $routeName => $routeController) 
		{
			//add route
			$this->routes[$routeName] = $routeController;

		}

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
	 *@param string $url The url string for which to perform match
	 *
	 */
	public function dispatch($url, $routes)
	{
		//populate the routes array
		if ( sizeof($routes) > 0)
		{
			//call the add route method
			$this->addRoute($routes);

		}

		//create instance of the UrlParser object
		$urlObj = new UrlParser($url);

		//check for defined route
		return $this->match($urlObj->getController(), $urlObj->getMethod()) ? true : false;

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
	 *This method returns the action name
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
	 *This method returns the action name
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
	 *This method checks for the existance of a defined route
	 *
	 *@param string $name The name of the defined route to match
	 *@return (bool) true|false True if route exists and false if not
	 */
	private function match($name, $method)
	{
		//check if this route index exists
		$match = array_key_exists($name, $this->routes) ? true : false;

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
