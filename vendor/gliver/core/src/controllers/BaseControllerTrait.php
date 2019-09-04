<?php namespace Drivers\Controllers;

/**
 *This trait with magic class provide getters and setters for the base controller class
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Controllers
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Registry\Registry;

trait  BaseControllerTrait {

	/**
	*@var float The request start time
	*/
	public $request_start_time;

	/**
	*@var string The site title as defined in the config
	*/
	public $site_title;

	/**
	 *This magic method determines the request method and composes a method based on the value.
	 *It checks to see if the method exists
	 *
	 *@param string $method The method name to compose
	 *@param array $param The parameters passed to this function
	 *@return sting||(bool)false Return named method or false in boolean failure
	 */

	public function __call($method, $param = null)
	{
		$prefix = ($_SERVER['REQUEST_METHOD'] == 'GET') ? 'get' : 'post';

		$method = $prefix . ucwords($method);
		
		//check of this method exists and return method name, else return false
		if (method_exists($this, $method)) return $method;
		else return false;

	}

	/**
	*this method sets the basic app properties in controller
	*
	*@param null
	*@return \Object $this instance of the controller for the purposes of chaining
	*/
	public function set_gliver_fr_controller_trait_properties()
	{
		//set the request_start_time
		$this->request_start_time = Registry::$gliver_app_start;

		//set the site title
		$this->site_title = Registry::getConfig()['title'];

		//return this object instance
		return $this;

	}

	/**
	*This method returns the current request execution time
	*
	*@param null
	*@return float The duration of the request up to the point where this method is called
	*/
	public function request_exec_time()
	{
		//return the time different
		return microtime(true) - $this->request_start_time;
		
	}

}