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
	 *@param $string The string which is to be exploded into an array
	 *@param $key The string to use as a separator for exploding the string
	 *@param $limit The value limits the number of elements to return
	 *@return void
	 *
	 */
	public static function parts($key, $string, $limit = null)
	{
		//explode this string and return
		return explode($key, $string, (! is_null($limit) ) ? $limit );
	}
	
}