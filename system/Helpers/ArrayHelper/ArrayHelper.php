<?php namespace Helpers\ArrayHelper;

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

use Helpers\ArrayHelper\ArrayHelperClass;
use Helpers\ArrayHelper\ArrayHelperException;

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
	*@var Object \ArrayHelperClass
	*/
	private static $ArrayHelperClassInstance = null;

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
	*This method gets and sets the instance of the array implementation class
	*
	*@param null
	*@return boolean true|false depending on the outcome of the operation
	*/
	public static function setImplmentationClass(){

		//check if the implementation class already exists
		if(is_null(self::$ArrayHelperClassInstance)){

			//create the implementation instance
			self::$ArrayHelperClassInstance = new ArrayHelperClass();
			
		}

		//the ArrayHelperClassInstance is already set, return true
		else{

			//return boolean true
			return  true;

		}

	}

	/**
	 *This method explodes a string into an array depending on the separator provided
	 *
	 *@param string $string The input string which is to be exploded into an array
	 *@param string $key The character(s) to use as a separator for exploding the string
	 *@param int $limit The value limits the number of elements to return
	 *@return Object \ArrayHelper
	 */
	public static function parts($delimiter = null, $string = null, $limit = null)
	{
		//check for the existance of the implementation class
		self::setImplmentationClass();

		//test for the paramters in a try...catch block for better error handling
		try{

			//set the value of the delimiter methodArg
			$delimiter = ( ! is_null($delimiter)) ? $delimiter : self::$ArrayHelperClassInstance->getMethodArgs('delimiter');

			//set the value of the string argument
			$string = ( ! is_null($string)) ? $string : self::$ArrayHelperClassInstance->getOutputElement();
			
			//set the value of the limit parameter/argument
			$limit = (!is_null($limit)) ? $limit : self::$ArrayHelperClassInstance->getMethodArgs('limit');

			//check if the delimiter methodArg value has been set
			if( $delimiter === null ) throw new ArrayHelperException("NULL \$delimiter value passed as the delimiter for splitting string into array to ArrayHelper::parts() method. A string value is expected!", 1);
			
			//check if the string value has been set
			if( $string === null) throw new ArrayHelperException("NULL \$string value has been passed as the string to be split into an array by the ArrayHelper::parts() method. A string value is expected!", 1);
			

			//check if the value of the limit parameter is set
			if($limit === null){
				
				//call the implmentation method to split string into array
				self::$ArrayHelperClassInstance->parts($delimiter, $string);

			}

			//limit value is set, pass the value of limit
			else{

				//call the implmentation method to split string into array
				self::$ArrayHelperClassInstance->parts($delimiter, $string, true, $limit);

			}

			//return the static instance
			return new static;

		}

		//catch the errors throw here
		catch(ArrayHelperException $ArrayHelperExceptionInstance){

			//display the error message
			$ArrayHelperExceptionInstance->errorShow();

		}

	}

	/**
	 *This method joins an array into a string based on the joining parameter provided
	 *
	 *@param string $glue The string to use to join the array elements into string
	 *@param array $array The string which is to be exploded into an array
	 *@return Object \ArrayHelper
	 *
	 */
	public static function join($glue = null, array $inputArray = null)
	{

		//check for the existance of the implementation class
		self::setImplmentationClass();

		//create the aprameter in a try...catch block for better error handling
		try{

			//set the value of the glue methodArg
			$glue = ( $glue !== null) ? $glue : self::$ArrayHelperClassInstance->getMethodArgs('glue');

			//set the value of the inputArray
			$inputArray = ( ! is_null($inputArray)) ? $inputArray : self::$ArrayHelperClassInstance->getOutputElement();

			//chekc if the value of the glue is null and throw exception
			if($glue === null) throw new ArrayHelperException("NULL  \$glue value has been passed for joining \$inputArray into String to ArrayHelper::join() method. A string value is expectd!",1);

			//check if null was passed for the input array
			if($inputArray === null) throw new ArrayHelperException("NULL \$inputArray value has been passed for splitting into a string to the ArrayHelper::join() method. A valid array value is expected!", 1);
			
			//call the implementation method to join this string
			self::$ArrayHelperClassInstance->join($glue, $inputArray);

			//return the static instance
			return new static;

		}

		//catch the thrown error here
		catch(ArrayHelperException $ArrayHelperExceptionInstance){

			//diplay the error message
			$ArrayHelperExceptionInstance->errorShow();

		}

	}

	/**
	 *This method loops through the items of an array removing elements with empty values
	 *
	 *@param array $array The array whose values are to be cleaned
	 *@return Object \ArrayHelper
	 */
	public static function clean($array = null)
	{
		//check for the existance of the implementation class
		self::setImplmentationClass();

		//set the input parameters in a try...catch block for better error handling
		try {

			//set the value fo the $inputArray parameter
			$inputArray = ( ! is_null($array)) ? $array : self::$ArrayHelperClassInstance->getOutputElement();

			//check if input element was a null
			if($inputArray === null) throw new ArrayHelperException("NULL \$array value passed for removal of empty elements values to ArrayHelper::clean() method. A valid array is expected!", 1);

			//call the implementation class to clean the input array
			self::$ArrayHelperClassInstance->clean($inputArray);

			//return the static instance
			return new static;

		} 

		//catch the error messages here
		catch (ArrayHelperException $ArrayHelperExceptionInstance) {

			//diplay the error message
			$ArrayHelperExceptionInstance->errorShow();
			
		}

	}

	/**
	 *This method loops through array elements removing whitespaces from begining and ending of string element values
	 *@param array $array The input array to be trimmed of whitespace
	 *@return Object \ArrayHelper
	 */
	public static function trim($array = null)
	{
		//check for the existance of the implementation class
		self::setImplmentationClass();

		//set the input parameters in a try...catch block for better error handling
		try {

			//set the value fo the $inputArray parameter
			$inputArray = ( ! is_null($array)) ? $array : self::$ArrayHelperClassInstance->getOutputElement();

			//check if input element was a null
			if($inputArray === null) throw new ArrayHelperException("NULL \$array value passed for removal of whitespaces from begining and ending of string element values to ArrayHelper::trim() method. A valid array is expected!", 1);

			//call the implementation class to trim the input array
			self::$ArrayHelperClassInstance->trim($inputArray);

			//return the static instance
			return new static;

		} 

		//catch the error messages here
		catch (ArrayHelperException $ArrayHelperExceptionInstance) {

			//diplay the error message
			$ArrayHelperExceptionInstance->errorShow();
			
		}

	}

	/**
	 *This method converts a multi-dimensional array into a uni-dimensional array
	 *
	 *@param array $array The array to flatten
	 *@param array $return The return array
	 *@return Object \ArrayHelper
	 */
	public static function flatten($array = null, $return = array())
	{
		//check for the existance of the implementation class
		self::setImplmentationClass();

		//set the input parameters in a try...catch block for better error handling
		try {

			//set the value fo the $inputArray parameter
			$inputArray = ( ! is_null($array)) ? $array : self::$ArrayHelperClassInstance->getOutputElement();

			//check if input element was a null
			if($inputArray === null) throw new ArrayHelperException("NULL \$array value passed to ArrayHelper::flatten() method for converting a multi-dimensional array into a uni-dimensional array. A valid array is expected!", 1);

			//call the implementation class to flatten the input array
			self::$ArrayHelperClassInstance->flatten($inputArray, $return);

			//return the static instance
			return new static;

		} 

		//catch the error messages here
		catch (ArrayHelperException $ArrayHelperExceptionInstance) {

			//diplay the error message
			$ArrayHelperExceptionInstance->errorShow();
			
		}

	}

	/**
	 *This method returns the first element in an array
	 *
	 *@param array $array The array whose first element is to be returned
	 *@return Object \ArrayHelper
	 */
	public static function first($array = null)
	{
		//check for the existance of the implementation class
		self::setImplmentationClass();

		//set the input parameters in a try...catch block for better error handling
		try {

			//set the value fo the $inputArray parameter
			$inputArray = ( ! is_null($array)) ? $array : self::$ArrayHelperClassInstance->getOutputElement();

			//check if input element was a null
			if($inputArray === null) throw new ArrayHelperException("NULL \$array value passed to ArrayHelper::first() method for returning the first element in an array. A valid array is expected!", 1);

			//call the implementation class to get first element of the input array
			self::$ArrayHelperClassInstance->first($inputArray);

			//return the static instance
			return new static;

		} 

		//catch the error messages here
		catch (ArrayHelperException $ArrayHelperExceptionInstance) {

			//diplay the error message
			$ArrayHelperExceptionInstance->errorShow();
			
		}

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
	public static function slice($inputArray = null, $offset = null, $length = null, $preserveKeys = false){

		//check for the existance of the implementation class
		self::setImplmentationClass();

		//set the input parameters in a try...catch block for better error handling
		try {

			//set the value fo the $inputArray parameter
			$inputArray = ( ! is_null($inputArray)) ? $inputArray : self::$ArrayHelperClassInstance->getOutputElement();

			//set the value of the offset parameter
			$offset = ( ! is_null($offset)) ? $offset : self::$ArrayHelperClassInstance->getMethodArgs('offset');

			//set the value of the length argument
			$length = ( ! is_null($length)) ? $length : self::$ArrayHelperClassInstance->getMethodArgs('length');

			//set the value of the preserveKeys parameter
			$preserveKeys = ( ! is_null($preserveKeys)) ? $preserveKeys : self::$ArrayHelperClassInstance->getMethodArgs('preserveKeys');

			//check if null value was set for the inputArray paramter
			if($inputArray === null) throw new ArrayHelperException("NULL \$array value passed to ArrayHelper::slice() method which splits an array and returns the specified section.part. A valid array is expected!", 1);
			
			//call the implementation class to slice the input array
			self::$ArrayHelperClassInstance->slice($inputArray, $offset, $length, $preserveKeys);

			//return the static instance
			return new static;

		} 

		//catch the error messages here
		catch (ArrayHelperException $ArrayHelperExceptionInstance) {

			//diplay the error message
			$ArrayHelperExceptionInstance->errorShow();
			
		}

	}

	/**
	*This method checks if an array key exists
	*
	*@param array $inputArrayToSearch The array to check against
	*@param string $key The key to search for inthe input array
 	*@return Object \ArrayHelper;
	*/
	public static function KeyExists($key = null, array $inputArrayToSearch = null){

		//check for the existance of the implementation class
		self::setImplmentationClass();

		//set the input parameters in a try...catch block for better error handling
		try {

			//set the value of the key paramter to use for searching in the input array
			$key = ( ! is_null($key)) ? $key : self::$ArrayHelperClassInstance->getMethodArgs('key');

			//set the value fo the $inputArray parameter
			$inputArrayToSearch = ( ! is_null($inputArrayToSearch)) ? $inputArrayToSearch : self::$ArrayHelperClassInstance->getOutputElement();

			//check if null was supplied for the $key parameter
			if($key === null) throw new ArrayHelperException("NULL \$key value passed to ArrayHelper::KeyExists() method which checks if an array key exists. A valid string is expected!", 1);

			//check if null value was set for the inputArray paramter
			if($inputArrayToSearch === null) throw new ArrayHelperException("NULL \$array value passed to ArrayHelper::KeyExists() method which checks if an array key exists. A valid array is expected!", 1);
			
			//call the implementation class to check for defined key in the input array
			self::$ArrayHelperClassInstance->KeyExists($key, $inputArrayToSearch);

			//return the static instance
			return new static;

		} 

		//catch the error messages here
		catch (ArrayHelperException $ArrayHelperExceptionInstance) {

			//diplay the error message
			$ArrayHelperExceptionInstance->errorShow();
			
		}

	}

	/**
	*This method merges the elements of an array
	*
	*@param array $array1
	*@param array $array2
 	*@return Object \ArrayHelper;
	*/
	public static function Merge(array $array1 = null, array $array2 = null){

		//check for the existance of the implementation class
		self::setImplmentationClass();

		//set the input parameters in a try...catch block for better error handling
		try {

			//set the value fo the $inputArray1 parameter
			$array1 = ( ! is_null($array1)) ? $array1 : self::$ArrayHelperClassInstance->getOutputElement();
			
			//set the value fo the $inputArray2 parameter
			$array2 = ( ! is_null($array2)) ? $array2 : self::$ArrayHelperClassInstance->getOutputElement();

			//check if null was supplied for the $key parameter
			if($array === null) throw new ArrayHelperException("NULL \$array1 value passed to ArrayHelper::Merge() method which merges the elements of two arrays. A valid array1 is expected!", 1);

			//check if null value was set for the inputArray paramter
			if($array2 === null) throw new ArrayHelperException("NULL \$array2 value passed to ArrayHelper::Merge() method which merges the elements of two arrays. A valid array2 is expected!", 1);
			
			//call the implementation class to merge the input array
			self::$ArrayHelperClassInstance->Merge($array1, $array2);

			//return the static instance
			return new static;

		} 

		//catch the error messages here
		catch (ArrayHelperException $ArrayHelperExceptionInstance) {

			//diplay the error message
			$ArrayHelperExceptionInstance->errorShow();
			
		}
	}

	/**
	*This method returns the output array after method manipulation
	*
	*@param null
	*@return array The output after array manipulation
	*/
	public static function get(){

		//get and return the value of the array property
		return self::$ArrayHelperClassInstance->get();

	}

}