<?php namespace Drivers\Utilities;

/**
 *This class resolves a Url string to get the controller, method and query parameters
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers
 *@link core.gliver.io
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class UrlParser  {

	/**
	 *@var string $url The url string to be parsed
	 */
	protected $url;

	/**
	 *@var array $parameters Parsed url string parameters
	 */
	protected $parameters = null;

	/**
	 *This constructor initializes the $url string variable
	 *@param string $url The url request string
	 *@return void
	 */
	public function __construct($url)
	{
		//assign this value to the $url internal variable
		$this->url = StringUtility::removeTags($url);

	}

	/**
	 *This method returns the controller from this url
	 *
	 *@param null
	 *@return string The controller name
	 */
	public function getController() 
	{
		//break url string into array of substrings
		$array = explode("/", $this->url);

		//procede if parameters were found
		if (sizeof($array) > 0)
		{

			//remove empty elements
			$array = ArrayUtility::clean($array);

			//remove whitespace from array elements
			$array = ArrayUtility::trim($array);

			//check if array still contains elements
			if (sizeof($array) > 0) 
			{
				//set the parameters array value
				$this->parameters = $array;

				//return the controller
				return $array[0];

			}
			//there are no paramters after filtering, return null
			else
			{
				//return null
				return null;
				
			}


		}

		//there are no url parameters
		else
		{
			//return null for controller
			return null;

		}
	
	}

	/**
	 *This method gets and returns the action for this url
	 *
	 *@param null
	 *@return string Action name
	 */
	public function getMethod()
	{
		//procede if parameters were found
		if (sizeof($this->parameters) > 1)
		{
			//return the action
			return $this->parameters[1];

		}

		//there are no url parameters
		else
		{
			//return null for action
			return null;

		}

	}

	/**
	 *This method returns the query parameters for this url
	 *
	 *@param null
	 *@return array Query parameters for this request
	 */
	public function getParameters()
	{
		if (sizeof($this->parameters) > 2)
		{
			//return the controller
			return array_slice($this->parameters, 2);

		}

		//there are no url parameters
		else
		{
			//return null for parameters
			return null;

		}

	}

}

