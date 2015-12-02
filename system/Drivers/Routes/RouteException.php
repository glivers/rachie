<?php namespace Drivers\Routes;

/**
 *This class handles all exceptions thrown by Routing Class
 *
 * @author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 * @copyright 2015 - 2020 Geoffrey Oliver
 * @category Exceptions
 * @package Drivers\Routes\RouteException
 * @link https://github.com/gliver-mvc/gliver
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.1
 */

use Exceptions\BaseExceptionClass;

class RouteException extends BaseExceptionClass {

	/**
	*This method modifes the error message to be returned, displayed
	*
	*@param null
	*@return string The error message content
	*/
	public function getErrorMessage(){

		//define and return the error message to show
		$this->errorMessageContent = $this->getMessage();

		//return this object instance
		return $this;

	}

}