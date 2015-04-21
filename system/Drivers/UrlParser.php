<?php namespace Core\Drivers;

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

use Core\Drivers\Array;

class UrlParser  {

	/**
	 *@var string $url The url string to be parsed
	 */
	protected $url;

	/**
	 *This constructor initializes the $url string variable
	 *@param string $url The url request string
	 *@return void
	 */
	public function __construct($url)
	{
		//assign this value to the $url internal variable
		$this->url = $url;

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
			$array = Array::clean($array);

			//remove whitespace from array elements
			$array = Array::trim($array);

			//check if array still contains elements
			if (sizeof($array) > 0) 
			{
				//return the controller
				return $array[0];

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
	public function getAction()
	{
		//break url string into array of substrings
		$array = explode("/", $this->url);

		//procede if parameters were found
		if (sizeof($array) > 1)
		{

			//remove empty elements
			$array = Array::clean($array);

			//remove whitespace from array elements
			$array = Array::trim($array);

			//check if array still contains elements
			if (sizeof($array) > 1) 
			{
				//return the action
				return $array[1];

			}


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
	public function getParam()
	{
		//break url string into array of substrings
		$array = explode("/", $this->url);

		//procede if parameters were found
		if (sizeof($array) > 2)
		{

			//remove empty elements
			$array = Array::clean($array);

			//remove whitespace from array elements
			$array = Array::trim($array);

			//check if array still contains elements
			if (sizeof($array) > 2) 
			{
				//return the controller
				return array_slice($array, 2);

			}


		}

		//there are no url parameters
		else
		{
			//return null for parameters
			return null;

		}

	}

}

