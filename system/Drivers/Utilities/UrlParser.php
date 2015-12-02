<?php namespace Drivers\Utilities;

/**
 *This class resolves a Url string to get the controller, method and query parameters
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Utilities
 *@package Utilities\UrlParserUtility
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Helpers\StringHelper;
use Helpers\ArrayHelper;

class UrlParser  {

	/**
	 *@var string $url The input url string to be parsed for controllers, methods and parameters
	 */
	private $urlString;

	/**
	*@var string The delimited to use for spliting up an url string
	*/
	private $urlSeparator = '/';

	/**
	*@var array The seprated url components put together into an array 
	*/
	private $urlComponentsArray = array();

	/**
	*@var string The controller inferred from the input url string
 	*/
	private $controller;

	/**
	*@var string The controller method infered from the input url string
	*/
	private $method;

	/**
	 *@var array $parameters Parsed url string parameters
	 */
	private $parameters = null;

	/**
	 *This constructor initializes the $url string variable
	 *
	 *@param string $url The url request string
	 *@return Object \UrlParser
	 */
	public function __construct($url)
	{
		//assign the input string value to the $urlString property
		$this->urlString = StringHelper::removeTags($url);

		//call the helper class to split the url string to array components and assign value to $urlComponentsArray property
		$this->urlComponentsArray = ArrayHelper::parts($this->urlSeparator, $this->urlString)->clean()->trim()->get();

		//return this object instance
		return $this;

	}

	/**
	*This defines the infered controller from the url string
	*
	*@param null
	*@return Object \UrlParser 
	*/
	private function setController(){

		//check if the $urlComponentsArray has more than one value
		if(count($this->urlComponentsArray) > 0){

			//set the value of the controller
			$this->controller = $this->urlComponentsArray[0];

		}

		//there are no url params, set controller to null
		else{

			//there are no components in the url string set null for infered controller
			$this->controller = null;

		}

	}

	/**
	 *This method returns the controller infered from this url string
	 *
	 *@param null
	 *@return string The controller name
	 */
	public function getController() 
	{
		//return the value of the controller property
		return $this->controller;

	}

	/**
	*This method sets the value of the method infered from the input string
	*
	*@param null
	*@return Object \UrlParser
	*/
	private function setMethod(){

		//check if the $urlComponentsArray has more than one component
		if(count($this->urlComponentsArray) > 1){

			//set the value of the $method property
			$this->method = $this->urlComponentsArray[1];

		}

		//there are no more than 1 url params, set method to null
		else{

			//there are no components in the url string set null for infered controller
			$this->method = null;

		}

	}

	/**
	 *This method  returns the method for this url
	 *
	 *@param null
	 *@return string Action name
	 */
	public function getMethod()
	{
		
		//return the value of the $method property
		return $this->method;

	}

	/**
	*This method sets the url string request parameterd for this input string
	*
	*@param null
	*@return Object \UrlParser
	*/
	private function setParameters(){

		//check if the $urlComponentsArray has more than two element
		if(count($this->urlComponentsArray) > 2){

			//slice the array and return the parts after the method
			$this->parameters = ArrayHelper::slice($this->urlComponentsArray, 2)->get();

		}

		//there are no request parameters, set a value of null
		else{

			//set the null value
			$this->parameters = null;

		}

	}
	/**
	 *This method returns the request parameters for this url
	 *
	 *@param null
	 *@return array Query parameters for this request
	 */
	public function getParameters()
	{

		//return the value of the $parameters property
		return $this->parameters;

	}

}

