<?php namespace Helpers\ArrayHelper;

/**
 *This class handles all array related functionality
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Helpers
 *@package Helpers\Array\ArrayHelperClass
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class ArrayHelperClass {

	/**
	*@var array The method arguments/parameters passed with function calls
	*/
	private $methodArgs = array();

	/**
	*@var array The input element to be manipulated
	*/
	private  $inputElement;

	/**
	*@var mixed The output element after manipulation
	*/
	private  $outputElement;

	/**
	 *This is the constructor class.
	 *
	 *@param null
	 *@return void
	 */
	public function __construct() {}

	/**
	*this method sets the method args
	*
	*@param string $argName The name of the argument to set
	*@param string $argValue The value fo the argument to set
	*@return boolean true|false Depending on the outcome of the exectuion
	*/
	public function setMethodArgs($argName, $argValue){

		//get the array that contains the methodArgs, and set this value
		$this->methodArgs[$argName] = $argValue;

		//return this instance
		return $this;

	}

	/**
	*This method gets and returns the value of the arguments requested
	*
	*@param string $argName The name of the argument to look up
	*@return mixed The value of the argument or a Boolean False on failure
	*/
	public function getMethodArgs($argName){

		//check if the argument is already set
		if( array_key_exists($argName, $this->methodArgs)){

			//return the value of this argument
			return $this->methodArgs[$argName];

		}

		//this argument is not yet set, so return null
		else{

			//return a null
			return null;

		}

	}

	/**
	*This method sets the value of the outputElement
	*
	*@param mixed $outputElementValue The value of the output element
	*@return boolean true|false Depending on the success|failure of this execution
	*/
	public function setOutputElement($outputElementValue){

		//set the value
		if( $this->outputElement = $outputElementValue){

			//return true on success
			return true;

		}

		//there was a problem, return false
		else return "Unable to set outputElement!";

	}

	/**
	*This method get the value of the outputElement
	*
	*@param null
	*@return mixed
	*/
	public function getOutputElement(){

		//return the contents of the outputElement
		return $this->outputElement;

	}

	/**
	 *This method explodes a string into an array depending on the separator provided
	 *
	 *@param string $key The character(s) to use as a separator for exploding the string
	 *@param string $string The input string which is to be exploded into an array
	 *@param int $limit The value limits the number of elements to return
	 *@return Object \ArrayHelperClass
	 */
	public function parts($delimiter, $string, $limitSet = false, $limit = null)
	{
		//set the parameters
		$this->setMethodArgs('delimiter', $delimiter)->setMethodArgs('limit', $limit);

		//set the inputElement property
		$this->inputElement = $string;

		//check if limit is set
		if($limitSet === true){

			//explode this string into an array
			$arrayComponents = explode($delimiter, $string, $limit);

		}

		//limit is not set
		else{

			//explode this string into an array
			$arrayComponents = explode($delimiter, $string);

		}

		//check $arrayComponents and set $outputElement
		$this->outputElement = (count($arrayComponents) > 0) ? $arrayComponents : null;

		//return this class instance
		return $this;

	}

	/**
	 *This method joins an array into a string based on the joining parameter provided
	 *
	 *@param string $glue The string to use to join the array elements into string
	 *@param array $array The string which is to be exploded into an array
	 *@return Object \ArrayHelper
	 *
	 */
	public  function join($glue, array $array)
	{
		//set the method argument values
		$this->setMethodArgs('glue', $glue);

		//set the inputElement property
		$this->inputElement = $array;

		//explode this string and set the value of $outputElement
		$this->outputElement = implode($glue, $array);

		//return the self same class
		return $this;

	}

	/**
	 *This method loops through the items of an array removing elements with empty values
	 *
	 *@param array $array The array whose values are to be cleaned
	 *@return Object \ArrayHelper
	 */
	public function clean($array)
	{
		//set the $inputElement
		$this->inputElement = $array;

		//loop throught the input array removing empty elements and return resultant array
		$this->outputElement = array_values(array_filter($array, function($item){

				return ! empty($item);

		}));

		//return this self same class
		return $this;

	}

	/**
	 *This method loops through array elements removing whitespaces from begining and ending of string element values
	 *@param array $array The input array to be trimmed of whitespace
	 *@return Object \ArrayHelper
	 */
	public function trim($array)
	{
		//set the $inputElement
		$this->inputElement = $array;

		//loop the through the array elements removing the whitespaces
		$this->outputElement = array_map(function($item){

			return trim($item);

		}, $array);

		//return this self same class
		return $this;

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
		$this->outputElement = $return;

		//return this self same class
		return $this;

	}

	/**
	 *This method returns the first element in an array
	 *
	 *@param array $array The array whose first element is to be returned
	 *@return Object \ArrayHelper
	 */
	public function first($array)
	{
		//set the $inputElement
		$this->inputElement = $array;

		//set the value of $outputElement
		$this->outputElement = array_slice($array, 0, 1);

		//return this self same class
		return $this;

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
	public function slice($inputArray, $offset = null, $length = null, $preserveKeys = false){

		//set the methodArgs values
		$this->setMethodArgs('offset', $offset)->setMethodArgs('length', $length)->setMethodArgs('preserveKeys', $preserveKeys);

		//set the value of the input element
		$this->inputElement = $inputArray;

		//set the value of the output element
		$this->outputElement = array_slice($inputArray, $offset, $length, $preserveKeys);

		//return this self same class
		return $this;

	}

	/**
	*This method checks if an array key exists
	*
	*@param array $inputArrayToSearch The array to check against
	*@param string $key The key to search for inthe input array
 	*@return Object \ArrayHelper;
	*/
	public function KeyExists($key, array $inputArrayToSearch){

		//set the methodArgs values
		$this->setMethodArgs('key', $key);

		//set the value of the $inputElement
		$this->inputElement = $inputArrayToSearch;

		//set the value of the out put element
		$this->outputElement = array_key_exists($key, $inputArrayToSearch);

		//return this self same class
		return $this;

	}

	/**
	*This method merges the elements of an array
	*
	*@param array $array1
	*@param array $array2
 	*@return Object \ArrayHelperClass;
	*/
	public function Merge(array $array1, array $array2){

		//set the value of the out put element
		$this->outputElement = array_merge($array1, $array2);

		//return this self same class
		return $this;

	}

	/**
	*This method returns the output array after method manipulation
	*
	*@param null
	*@return array The output after array manipulation
	*/
	public function get(){

		//call the method to get the value of the outputElement
		return $this->getOutputElement();

	}

}