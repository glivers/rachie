<?php namespace Helpers;

/**
 *This class handles all array related functionality
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Helpers\ArrayHelper
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class ArrayHelper {

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
	 *This method explodes a string into an array depending on the separator provided
	 *
	 *@param string $key The string to use as a separator for exploding the string
	 *@param string $string The string which is to be exploded into an array
	 *@param int $limit The value limits the number of elements to return
	 *@return void
	 *
	 */
	public static function parts($key, $string, $limit = null)
	{
		//explode this string and return
		return explode($key, $string, (! is_null($limit) ) ? $limit: null );
	}

	/**
	 *This method joins an array into a string based on the joining parameter provided
	 *
	 *@param string $glue The string to use to join the array elements into string
	 *@param aray $array The string which is to be exploded into an array
	 *@return void
	 *
	 */
	public static function join($glue,array $array)
	{
		//explode this string and return
		return implode($glue, $array);
	}
	
}