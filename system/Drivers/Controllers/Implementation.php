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

trait  Implementation {

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

}