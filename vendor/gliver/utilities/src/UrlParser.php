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

use Helpers\StringHelper\StringHelper;
use Helpers\ArrayHelper\ArrayHelper;
 
class UrlParser  {

	/**
	 *@var string $url The input url string to be parsed for controllers, methods and parameters
	 */
	private $urlString;

	/**
	*@var string The delimited to use for spliting up an url string
	*/
	private $urlSeparator;

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
	private $parameters = array();

	/**
	 *This constructor initializes the $url string variable
	 *
	 *@param string $url The url request string
	 *@param char $urlSeparator The url component separator character
	 *@return Object \UrlParser
	 */
	public function __construct($url, $urlSeparator)
	{
		//assign the input string value to the $urlString property
		$this->urlString = StringHelper::removeTags($url);
		$this->$urlSeparator = $urlSeparator;

		//call the helper class to split the url string to array components and assign value to $urlComponentsArray property
		$this->urlComponentsArray = ArrayHelper::parts($this->$urlSeparator, $this->urlString)->clean()->trim()->get();

		//return this object instance
		return $this;

	}

	/**
	*This defines the infered controller from the url string
	*
	*@param null
	*@return Object \UrlParser 
	*/
	public function setController(){

		//check if the $urlComponentsArray has more than one value
		if(count($this->urlComponentsArray) > 0){

			//set the value of the controller
			$this->controller = $this->urlComponentsArray[0];

			//return this class instance
			return $this;

		}

		//there are no url params, set controller to null
		else{

			//there are no components in the url string set null for infered controller
			$this->controller = null;

			//return this class instance
			return $this;

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
	public function setMethod(){

		//check if the $urlComponentsArray has more than one component
		if(count($this->urlComponentsArray) > 1){

			//set the value of the $method property
			$this->method = $this->urlComponentsArray[1];

			//return this class instance
			return $this;

		}

		//there are no more than 1 url params, set method to null
		else{

			//there are no components in the url string set null for infered controller
			$this->method = null;

			//return this class instance
			return $this;

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
	*This method sets the url string request parameters for this input string
	*
	*@param sting $value The new value to add to the parameters array
	*@param bool True|False Flag to indicated whether the additional value is to be prepended or appended
	*@return Object \UrlParser
	*/
	public function setParameters($value = null, $appendParameter = true){
			
		//check if the $urlComponentsArray has more than two element
		$this->parameters = (count($this->urlComponentsArray) > 2) ? ArrayHelper::slice($this->urlComponentsArray, 2)->get() : array();

		//check if there is an additonal parameter to add to parameters array
		if($value !== null){

			//check if this parameter is to be prepended or appended
			if($appendParameter === true){

				//append
				$this->parameters[] = $value;

			}

			//prepend to the parameters array
			else{

				//prepend
				array_unshift($this->parameters, $value);

			}

		}

		//return this class instance
		return $this;

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

