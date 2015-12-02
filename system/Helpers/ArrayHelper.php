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
	*@var array The input element to be manipulated
	*/
	private static $inputElement = null;

	/**
	*@var array The output element after manipulation
	*/
	private static $outputElement = null;

	/**
	 *This is the constructor class. We make this private to avoid creating instances of
	 *this class
	 *
	 *@param null
	 *@return void
	 */
	private function __construct() {}

	/**
	 *This method stops creation of a copy/clone of this class by making it private
	 *
	 *@param null
	 *@return void
	 *
	 */
	private function __clone(){}

	/**
	 *This method explodes a string into an array depending on the separator provided
	 *
	 *@param string $key The character(s) to use as a separator for exploding the string
	 *@param string $string The input string which is to be exploded into an array
	 *@param int $limit The value limits the number of elements to return
	 *@return Object \ArrayHelper
	 */
	public static function parts($key, $string, $limit = null)
	{
		//set the inputElement property
		self::$inputElement = $key;

		//explode this string into an array
		$arrayComponents = explode($key, $string, ( ! is_null($limit) ) ? $limit: null );

		//check $arrayComponents and set $outputElement
		self::$outputElement = (count($arrayComponents) > 0) ? $arrayComponents : null;

		//return this class instance
		return self;

	}

	/**
	 *This method joins an array into a string based on the joining parameter provided
	 *
	 *@param string $glue The string to use to join the array elements into string
	 *@param array $array The string which is to be exploded into an array
	 *@return Object \ArrayHelper
	 *
	 */
	public static function join($glue, array $array)
	{
		//set the inputElement property
		self::$inputElement = $array;

		//explode this string and set the value of $outputElement
		self::$outputElement = implode($glue, $array);

		//return the self same class
		return self;

	}

	/**
	 *This method loops through the items of an array removing elements with empty values
	 *
	 *@param array $array The array whose values are to be cleaned
	 *@return Object \ArrayHelper
	 */
	public static function clean($array)
	{
		//set the $inputElement
		self::$inputElement = $array;

		//loop throught the input array removing empty elements and return resultant array
		self::$outputElement = array_values(array_filter($array, function($item){

				return ! empty($item);

		}));

		//return this self same class
		return self;

	}

	/**
	 *This method loops through array elements removing whitespaces from begining and ending of string element values
	 *@param array $array The input array to be trimmed of whitespace
	 *@return Object \ArrayHelper
	 */
	public static function trim($array)
	{
		//set the $inputElement
		self::$inputElement = $array;

		//loop the through the array elements removing the whitespaces
		self::$outputElement = array_map(function($item){

			return trim($item);

		}, $array);

		//return this self same class
		return self;

	}

	/**
	 *This method converts a multi-dimensional array into a uni-dimensional array
	 *
	 *@param array $array The array to flatten
	 *@param array $return The return array
	 *@return Object \ArrayHelper
	 */
	public function flatten($array, $return = array())
	{
		//loop through the multi-dimensional array flattening the array value
		foreach ($array as $key => $value) 
		{
			//flatten is array element is a value or element
			if( is_array($value) || is_object($value))
			{
				//return 
				$return = self::flatten($value, $return);

			}
			else
			{
				$return[] = $value;

			}

		}

		//set the value of the $outputElement
		self::$outputElement = $return;

		//return this self same class
		return self;

	}

	/**
	 *This method returns the first element in an array
	 *
	 *@param array $array The array whose first element is to be returned
	 *@return Object \ArrayHelper
	 */
	public static function first($array)
	{
		//set the $inputElement
		self::$inputElement = $array;

		//set the value of $outputElement
		self::$outputElement = array_slice($array, 0, 1);

		//return this self same class
		return self;

	}

	/**
	*This method splits an array and returns the specified section.part
	*
	*@param array $inputArray The input array that is to be split and part return
	*@param int $offset The int to specify where to start truncating from
	*@param int $length The int to specify the number of elements to return
	*@param boolean true|false Set to true to preserver numberic keys, otherwise would be reindexed
	*@return Object \ArrayHelper
	*/
	public static function slice($inputArray, $offset = null, $length = null, $preserveKeys = false){

		//set the value of the input element
		self::$inputElement = $inputArray;

		//set the value of the output element
		self::$outputElement = array_slice($inputArray, $offset, $length, $preserveKeys);

		//return this self same class
		return self;

	}

	/**
	*This method checks if an array key exists
	*
	*@param array $inputArrayToSearch The array to check against
	*@param string $key The key to search for inthe input array
 	*@return Object \ArrayHelper;
	*/
	public static function KeyExists($key, array $inputArrayToSearch){

		//set the value of the $inputElement
		self::$inputElement = $inputArrayToSearch;

		//set the value of the out put element
		self::$outputElement = array_key_exists($key, $inputArrayToSearch);

		//return this self same class
		return self;

	}

	/**
	*This method merges the elements of an array
	*
	*@param array $array1
	*@param array $array2
 	*@return Object \ArrayHelper;
	*/
	public static function Merge(array $array1, array $array2){

		//set the value of the out put element
		self::$outputElement = array_merge($array1, $array2);

		//return this self same class
		return self;

	}

	/**
	*This method returns the output array after method manipulation
	*
	*@param null
	*@return array The output after array manipulation
	*/
	public static function get(){

		//get and return the value of the array property
		return self::$outputElement;

	}

}