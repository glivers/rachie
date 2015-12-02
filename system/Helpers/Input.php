<?php namespace Helpers;

/**
 *This class handles post and get input data
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Helpers\Input
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Helpers\Url;
use Helpers\ArrayHelper;
use Helpers\Exceptions\HelperException;

class Input {

	/**
	*@var array The array containing all the INPUT helper class values
	*/
	private static $inputData = array();

	/**
	 *This is the constructor class. We make this private to avoid creating instances of
	 *this object
	 *
	 *@param null
	 *@return void
	 */
	private function __construct() {}

	/**
	 *This method stops creation of a copy of this object by making it private
	 *
	 *@param null
	 *@return void
	 *
	 */
	private function __clone(){}

	/**
	*this method sets the $_GET parameter
	*
	*@param null
	*@return Object \Input
	*/
	public static function setGetData(){

		//populate the get array
		$getArray = (isset($_GET)) ? $_GET : array();

		//merge the two array
		self::$inputData = ArrayHelper::Merge(self::$inputData, $getArray);

		//return this self same class instance
		return self;

	}

	/**
	*this method sets the $_POST parameter
	*
	*@param null
	*@return Object \Input
	*/
	public static function setPostData(){

		//populate the get array
		$postArray = (isset($_GET)) ? $_POST : array();

		//merge the two array
		self::$inputData = ArrayHelper::Merge(self::$inputData, $postArray);

		//return this self same class instance
		return self;

	}

	/**
	*this method sets the URL parameter request data
	*
	*@param array $urlParamArray The values from url parameter to add to the Input Class
	*@return Object \Input
	*/
	public static function setUrlParamRequestData($urlParamArray = array()){

		//merge the two array
		self::$inputData = ArrayHelper::Merge(self::$inputData, $urlParamArray);

		//return this self same class instance
		return self;

	}

	/**
	 *This method return current $_POST or $_GET data
	 *
	 *@param string $key The name with which to access post variable
	 *@return mixed 
	 */	
	public static function get($variableKey = null)
	{
		//check if key to check was passed
		if( ! is_null($variableKey)){

			//return false if this variableKey doesnt exist
			if( ! ArrayHelper::KeyExists($variableKey, self::$inputData)) return false;

			//return the value of this key
			else return self::$inputData[$variableKey];

		}

		//return the whole content of the input data
		else
		{
			//check if this array is not empty
			if(count(self::$inputData) >0){

				//return the whole content of the array
				return self::$inputData;

			}

			//not data in input array, return false
			return false;

		}

	}

	/**
	 *This method checks if there is a value with a particular name in the input post/get
	 *
	 *@param string $variableKey The name with which to access post variable 
	 */	
	public static function has($variableKey)
	{
		//return false if this variableKey doesnt exist
		if( ! ArrayHelper::KeyExists($variableKey, self::$inputData)) return false;

		//return true, variableKey exists
		else return true;


	}

}
