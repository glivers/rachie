<?php namespace Helpers\Input;

/**
 *This class handles post and get input data
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Helpers
 *@package Helpers\Input\InputClass
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Helpers\Exceptions\HelperException;

class InputClass {

	/**
	*@var array The input data to set
	*/
	private $inputDataSources = array('url', 'get', 'post');
	
	/**
	*@var array Url data submitted in url segments
	*/
	private $urlInputData = array();

	/**
	*@var boolean true|false This boolean sets whether the url data has been set
	*/
	private $urlInputDataSet = false;
	
	/**
	*@var array The getInputData
	*/
	private $getInputData = array();

	/**
	*@var boolean true|false This boolean sets whether the get data has been set
	*/
	private $getInputDataSet = false;

	/**
	*@var array This array contains postInputData
	*/
	private $postInputData = array();

	/**
	*@var boolean true|false This boolean sets whether the post data has been set
	*/
	private $postInputDataSet = false;

	/**
	*@var array The array containing all the INPUT helper class values
	*/
	private $inputData = array();
	
	/**
	*this method sets the URL parameter request data
	*
	*@param array $params The values from url parameter to add to the Input Class
	*@return Object \InputClass
	*/
	public function setUrl($params){

		//set the urlInputData
		$this->urlInputData = $params;

		//merge the url data with the whole inputData array
		$this->inputData = $this->Merge($this->inputData, $this->urlInputData);

		//set the urlInputDataSet to true
		$this->urlInputDataSet = true;

		//return this object instance
		return $this;

	}

	/**
	*this method gets the URL parameter request data
	*
	*@param string $name The paramter name whose value we want to retrieve
	*@return mixed bool false if paramter not set or the value of the parameter, whole url param array if null supplied
	*/
	public function getUrl($name){

		//returnt he whole array if null is supplied
		if($name === null){

			//return the whole url input array
			return $this->urlInputData;

		}

		//check if the key provided exists
		elseif($this->KeyExists($name, $this->urlInputData)){

			//return the value of this key
			return $this->urlInputData[$name];

		}

		//this key does not exists, return false
		else{

			//return bool false
			return false;

		}

	}

	/**
	*this method checks if a particular name paramter exixts in the url params
	*
	*@param string $name The name of the key to check for
	*@return bool true|false True of the key exists and false if key not found
	*/
	public function urlHas($name){

		//check if this key exists
		if($this->KeyExists($name, $this->urlInputData)){

			//return bool true
			return true;

		}

		//this key does not exists, return bool false
		else{

			//return bool false
			return false;

		}

	}

	/**
	*this method sets the $_GET parameter
	*
	*@param null
	*@return Object \Input
	*/
	public function setGet(){

		//populate the get array
		$getArray = (isset($_GET)) ? $_GET : array();

		//set the post data array
		$this->getInputData = $getArray;

		//merge this with the InputData array
		$this->inputData = $this->Merge($this->inputData, $this->getInputData);

		//set the getInputDataSet to true
		$this->getInputDataSet = true;

		//return this object instance
		return $this;

	}

	/**
	*this method gets the $_GET parameter
	*
	*@param string $name The name of the key to search for
	*@return mixed True if key exists, whole $_GET array if no param supplied or false if key does not exist
	*/
	public function getGet($name){

		//check if null param supplied and return the while GET array
		if($name === null){

			//return the whole array
			return $this->getInputData;

		}

		//check for named paramter
		elseif ($this->KeyExists($name, $this->getInputData)) {
			
			//return the value of this key
			return htmlentities($this->getInputData[$name], ENT_QUOTES);

		}

		//this key does not exists, return bool false
		else{

			//return bool false
			return false;

		}

	}

	/**
	*this method checks if the $_GET parameter exists
	*
	*@param string $name The name of the key to check
	*@return bool true|false True if key exists and False if Key not exists
	*/
	public function getHas($name){

		//check if this key exists and return true
		if($this->KeyExists($name, $this->getInputData)){

			//return bool true
			return true;

		}

		//key does not exists, return bool false
		else{

			return false;

		}

	}

	/**
	*this method sets the $_POST parameter
	*
	*@param null
	*@return Object \Input
	*/
	public function setPost(){

		//populate the post array
		$postArray = (isset($_POST)) ? $_POST : array();

		//set the postInputData
		$this->postInputData = $postArray;

		//add this to the inputData
		$this->inputData = $this->Merge($this->inputData, $this->postInputData);

		//set the postInputDataSet to true
		$this->postInputDataSet = true;

		//return this object instance
		return $this;

	}

	/**
	*this method gets the $_POST parameter
	*
	*@param string $name The name of the key to get
	*@return mixed False if key not exists, Value of key if key exists, whole POST array if null supplied
	*/
	public function getPost($name){

		//check if null param supplied and return the while POST array
		if($name === null){

			//return the whole array
			return $this->postInputData;

		}

		//check for named paramter
		elseif ($this->KeyExists($name, $this->postInputData)) {
			
			//return the value of this key
			return htmlentities($this->postInputData[$name], ENT_QUOTES);

		}

		//this key does not exists, return bool false
		else{

			//return bool false
			return false;

		}

	}

	/**
	*this method checks if specified key exists in the $_POST array
	*
	*@param string $name The key to check for in the POST array
	*@return bool True|False True of the key exists, False if the key not exists
	*/
	public function postHas($name){

		//check if this key exists and return true
		if($this->KeyExists($name, $this->postInputData)){

			//return bool true
			return true;

		}

		//key does not exists, return bool false
		else{

			return false;

		}

	}


	/**
	 *This method gets all the InputData
	 *
	 *@param string $name The name with which to access post variable
	 *@return mixed False if key not exists, Value of key of the key exists, Whole Input array if null param supplied
	 */	
	public function get($name)
	{
		//check if null param supplied and return the while InputData array
		if($name === null){

			//return the whole array
			return $this->inputData;

		}

		//check for named paramter
		elseif ($this->KeyExists($name, $this->inputData)) {
			
			//return the value of this key
			return htmlentities($this->inputData[$name], ENT_QUOTES);

		}

		//this key does not exists, return bool false
		else{

			//return bool false
			return null;

		}

	}

	/**
	 *This method checks if there is a value with a particular name in the input post/get
	 *
	 *@param string $variableKey The name with which to access post variable 
	 */	
	public function has($name)
	{
		//check if this key exists and return true
		if($this->KeyExists($name, $this->inputData)){

			//return bool true
			return true;

		}

		//key does not exists, return bool false
		else{

			return false;

		}

	}

	/**
	*This method checks if a variable key exists in an array
	*
	*@param string $key The key to check
	*@param array $array The array in which to check for the key
	*@return boolean true|false
	*/
	public function KeyExists($key, $array){

		//check if the array key exists and return
		return array_key_exists($key, $array);

	}

	/**
	*This method combines two arrays into one
	*
	*@param array $array1 The array to merge into
	*@param array $array2 The array with which to merge
	*@return array The final array after merging
	*/
	public function Merge($array1, $array2){

		//merge and return the supplied arrays
		return array_merge($array1, $array2);	

	}

}
