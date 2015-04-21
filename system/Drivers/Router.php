<?php namespace Core\Drivers;

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

use Core\Drivers\Array;
use Core\Drivers\String;
use Core\Drivers\BaseRouter;
use Core\Drivers\Registry;
use Core\Drivers\Inspector;

class Router extends BaseRouter {

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
	public function addRoute(array $definedRoutes)
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
	public function getRoutes()
	{
		//get and return the routes array
		return $this->routes;

	}

	/**
	 *This methods attempts to find a route pattern match using regular expressions
	 *
	 *@param string $url The url string for which to perform match
	 *
	 */
	public function match($name)
	{
		//do something

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

}
