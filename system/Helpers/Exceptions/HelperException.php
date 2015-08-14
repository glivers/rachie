<?php namespace Helpers\Exceptions;

/**
 *This class handles all exceptions thrown by Helper Classes
 *
 * @author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 * @copyright 2015 - 2020 Geoffrey Oliver
 * @category Core
 * @package Helpers\Exceptions\
 * @link https://github.com/gliver-mvc/gliver
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.1
 */

use Drivers\Registry;
use Exceptions\BaseException;

class HelperException extends BaseException {

	/**
	 *This method displays the error message passed from the thrown error
	 *
	 * @param string The error message to display
	 * @return void
	 * @throws This method does not throw an error
	 *
	 */
	public function showError($message = null)
	{
		//get the site title
		$title = Registry::getConfig()['title'];

		//define the error message
		$errorMessage  = ( $message == null ) ? $this->getCode() . ': Error on line '.$this->getLine().' in '.$this->getFile() .': <b>  "'.$this->getMessage().' " </b> ' : $message;
		
		//the variable to be populated with the error message
		//$errorMessage = $this->getCode() . ': Error on line '.$this->getLine().' in '.$this->getFile() .': <b>  "'.$this->getMessage().' " </b> ';

		//display errors if in development environment
		if (  DEV == TRUE ) 
		{
			//load the showError file
			include Registry::$errorFilePath . 'errorShow.php';

		}
		//production environment, hide error
		else
		{
			//load the hide error file
			include Registry::$errorFilePath . 'errorHide.php';

		}

	}

	
}