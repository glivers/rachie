<?php namespace Drivers\Routes;

/**
 *This class performs url resolving. 
 *It inspects the input url and gets the appropriate controller and method to launch based on the 
 *defined or infered routes
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers
 *@link core.gliver.io
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\ArrayUtility;
use Drivers\StringUtility;

class Base {

	/**
	 *@var string $pattern The url string pattern to check for
	 *@readwrite 
	 */
	protected $pattern = '@';
	/**
	 *@var string $controller The controller name to launch
	 *@readwrite
	 */
	protected $controller = null;
	/**
	 *@var string $action The controller method to invoke
	 *@readwrite
	 */
	protected $method = null;
	/**
	 *@var array $parameters The url request parameters
	 *@readwrite
	 */
	protected $parameters = null;

	/**
	 *This exception class is  throw when the specified action cannot be found.
	 *@param string $method The method name that we attempted to excecute
	 *@return void
	 */
	public function MethodNotFoundException()
	{
		//throw the exception error message
		return new Exception\Implementation("{$method} method not implemented" );

	}



}
