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
use Helpers\Exceptions\HelperException;

class Input {

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
	 *This method return current $_POST or $_GET data
	 *
	 *@param string $key The name with which to access post variable 
	 */	
	public static function get()
	{
		//get the argumants passed to this function
		$args = func_get_args();

		//there were arguments passed
		if ( sizeof($args) > 0 ) 
		{
			//dedine array to contain get data
			$get = array();

			//define array to define post data
			$post = array();

			//check if there is get data and add
			if ( isset($_GET) ) 
			{
				//the get data is get, add this to array
				$get = $_GET;

			}

			//check if there is post data and add
			if ( isset($_POST) ) 
			{
				//check the data in post and add this to array
				$post = $_POST;

			}

			//merge the two arrays into one
			$input = array_merge($get, $post);

			//check if this index exists and return
			if ( array_key_exists($args[0], $input) ) 
			{
				//return the value of this index
				return $input[$args[0]];

			}
			//this value does not exist in array, return false
			else return false;
			
		}

		else
		{
			//dedine array to contain get data
			$get = array();

			//define array to define post data
			$post = array();

			//check if there is get data and add
			if ( isset($_GET) ) 
			{
				//the get data is get, add this to array
				$get = $_GET;

			}

			//check if there is post data and add
			if ( isset($_POST) ) 
			{
				//check the data in post and add this to array
				$post = $_POST;

			}

			//merge the two arrays into one
			$input = array_merge($get, $post);

			if ( empty($input) ) return false;
			else return $input;

		}

	}

	/**
	 *This method checks if there is a value with a particular name in the input post/get
	 *
	 *@param string $key The name with which to access post variable 
	 */	
	public static function has($key)
	{
		//execute the code in a catch block to enable error handling
		try{

			//throw error if user input an array
			if ( is_array($key) ) throw new HelperException('This should be a string, not an array');
			


		}
		catch(HelperException $error){

			//diplay the error message
			$error->showError();

		}

	}

}
