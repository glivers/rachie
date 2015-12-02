<?php namespace Exceptions;

/**
 *This class handles all exceptions thrown and is extended by other classes
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Exceptions\BaseException
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Registry;
use Exceptions\Debug\BaseExceptionInterface;

class BaseExceptionClass extends \Exception implements BaseExceptionInterface  {

	/**
	*@var string The site title
	*/
	public $siteTile;

	/**
	 *@var string The path to the error log file
	 */
	public $errorLogFilePath;

	/**
	*@var boolean true|false The value of the application environment, defaults to true
	*/
	public $devENV = true;

	/**
	*@var string The path to the error page HTML
	*/
	public $ErrorPageHtml;

	/**
	*@var string This property stores the error message for this exception
	*/
	public $errorMessageContent;

	/**
	 *This constructor method sets the default values of this class properties
	 *
	 *@param Object \Registry class This loads the user configuration array
	 *@return \BaseExceptionClass object instance
	 */
	public function __construct($errorMessage = null, $errorCode = 0, Exception $previous = null){

		//enable parent constructor
		parent::__construct($errorMessage, $errorCode, $previous);

		//set the value of siteTile property
		$this->siteTile = Registry::getConfig()['title'];

		//set the default error log file path
		$this->errorLogFilePath = Registry::getConfig()['root'] . '/' . Registry::getConfig()['error_log_file_path'];

		//set the value  of the development environment, as defined in the user configuration
		$this->devENV = Registry::getConfig()['dev'];

		//set the path to the error page html
		$this->ErrorPageHtml = Registry::getConfig()['root'] . "/system/Exceptions/ErrorPageHtml.php";

	}

	/**
	*This method logs an error message into the log file
	*
	*@param null
	*@return Object \BaseExceptionClass This instance of the object
	*/
	public function logErrorMessage(){

		//write the message content to file
		error_log($this->errorMessageContent . PHP_EOL, 3, $this->errorLogFilePath);

		//return this object instance
		return $this;


	}

	/**
	*This method gets and returns the full error message content
	*
	*@param null
	*@return string The error message content
	*/
	public function getErrorMessage(){

		//define and return the error message to show
		$this->errorMessageContent = $this->getMessage() . ' ' . $this->getFile() . ' ' . $this->getLine();

		//return this object instance
		return $this;

	}

	/**
	 *This method displays the error message passed from the thrown error
	 *
	 * @param null
	 * @return void
	 * @throws This method does not throw an error
	 *
	 */
	public function errorShow()
	{
		//define and log the error message
		$this->getErrorMessage()->logErrorMessage();

		//define the error message to show
		$showErrorMessage = $this->errorMessageContent;

		//check if this is not development environment
		if($this->devENV === false){

			//set the hideErrorMessage parameter
			$hideErrorMessage = true;

		}

		//get the site title
		$title = $this->siteTile;

		//load the error message page html file
		include $this->ErrorPageHtml;

		//stop futher script execution
		exit();

	}
	
}