<?php namespace Drivers\Templates;

/**
 *This class handles all exceptions thrown by Template Class
 *
 * @author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 * @copyright 2015 - 2020 Geoffrey Oliver
 * @category Exceptions
 * @package Drivers\Templates\TemplateException
 * @link https://github.com/gliver-mvc/gliver
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.1
 */

use Exceptions\BaseExceptionClass;

class TemplateException extends BaseExceptionClass {

	/**
	*This method modifes the error message to be returned, displayed
	*
	*@param boolean true|false $included This paramter shows if this missing vie file was an included file
	*@return Object \TemplateException
	*/
	public function getErrorMessage(){

		//get the argument passed from the thrown exception
		$args = func_get_args();

		$included = (count($args) > 0) ? $args[0] : null;

		//compose the error message
		if($included === true) $message = 'The included view file '; 
		else $message = 'The  view file ';

		//define and return the error message to show
		$this->errorMessageContent = $message . $this->getMessage();

		//return this object instance
		return $this;

	}

}