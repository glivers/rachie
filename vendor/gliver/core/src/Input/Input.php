<?php namespace Helpers\Input;

/**
 *This class handles post and get input data
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Helpers
 *@package Helpers\Input\Input
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Helpers\Input\InputClass;
use Helpers\Exceptions\HelperException;

class Input {

	/**
	*@var Object \InputClass The implementation class of the inputHelperClass
	*/
	private static $InputClassInstance;

	/**
	*This method sets the InputClassInstance 
	*
	*@param null
	*@return void
	*/
	public static function setInputClassInstance(){

		//check if instance is null and set
		if(is_null(self::$InputClassInstance)){

			//set the class instance
			self::$InputClassInstance = new InputClass();

		}

	}

	/**
	*This method checks if input data parameters have been set, used if a user accesses a get method before a set methos
	*
	*@param null
	*@return void
	*/
	public static function setInputData(){

		//first, call the setInputClassInstance() method to set $InputClassInstance 
		self::setInputClassInstance();

		//set the input data
		self::setGet()->setPost();

		//return the static instance
		return new static;
	
	}
	
	/**
	*this method sets the URL parameter request data
	*
	*@param array $urlParamArray The values from url parameter to add to the Input Class
	*@return Object \Input
	*/
	public static function setUrl(array $params = array()){

		//begin by setting the input class instance
		self::setInputClassInstance();

		//call implementation method to set url data
		self::$InputClassInstance->setUrl($params);

		//return the static instance
		return new static;
	
	}

	/**
	*this method gets the URL parameter request data
	*
	*@param string $name The paramter name whose value we want to retrieve
	*@return mixed bool false if paramter not set or the value of the parameter, whole url param array if null supplied
	*/
	public static function getUrl($name = null){

		//call implementation class to get url paramters
		return self::$InputClassInstance->getUrl($name);

	}

	/**
	*this method checks if a particular name paramter exixts in the url params
	*
	*@param string $name The name of the key to check for
	*@return bool true|false True of the key exists and false if key not found
	*/
	public static function urlHas($name){

		//call the implementation method to check for this key in url params
		return self::$InputClassInstance->urlHas($name);

	}

	/**
	*this method sets the $_GET parameter
	*
	*@param null
	*@return Object \Input
	*/
	public static function setGet(){
		
		//begin by setting the input class instance
		self::setInputClassInstance();

		//call the implementation method to set get data
		self::$InputClassInstance->setGet();

		//return the static instance
		return new static;

	}

	/**
	*this method gets the $_GET parameter
	*
	*@param string $name The name of the key to search for
	*@return mixed True if key exists, whole $_GET array if no param supplied or false if key does not exist
	*/
	public static function getGet($name = null){

		//call implementation method to get GET data
		return self::$InputClassInstance->getGet($name);

	}

	/**
	*this method checks if the $_GET parameter exists
	*
	*@param string $name The name of the key to check
	*@return bool true|false True if key exists and False if Key not exists
	*/
	public static function getHas($name){

		//call the implementation method to check for the existance of key
		return self::$InputClassInstance->getHas($name);

	}

	/**
	*this method sets the $_POST parameter
	*
	*@param null
	*@return Object \Input
	*/
	public static function setPost(){
		
		//begin by setting the input class instance
		self::setInputClassInstance();

		//call implementation method to set post data
		self::$InputClassInstance->setPost();

		//return the static instance
		return new static;

	}

	/**
	*this method gets the $_POST parameter
	*
	*@param string $name The name of the key to get
	*@return mixed False if key not exists, Value of key if key exists, whole POST array if null supplied
	*/
	public static function getPost($name = null){

		//call the implementation method to get post paramter
		return self::$InputClassInstance->getPost($name);

	}

	/**
	*this method checks if specified key exists in the $_POST array
	*
	*@param string $name The key to check for in the POST array
	*@return bool True|False True of the key exists, False if the key not exists
	*/
	public static function postHas($name){

		//call the miplementaion method to check for this key in the post
		return self::$InputClassInstance->postHas($name);

	}


	/**
	 *This method gets all the InputData
	 *
	 *@param string $key The name with which to access post variable
	 *@return mixed False if key not exists, Value of key of the key exists, Whole Input array if null param supplied
	 */	
	public static function get($name = null)
	{
		//call the implementation method to get the input data
		return self::$InputClassInstance->get($name);

	}

	/**
	 *This method checks if there is a value with a particular name in the input data
	 *
	 *@param string $variableKey The name with which to access post variable 
	 *@return bool True|False True if the key exists, False if the key nto exists
	 */	
	public static function has($name)
	{
		//call the moethod to check for the key
		return self::$InputClassInstance->has($name);

	}

}
