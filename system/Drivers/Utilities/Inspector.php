<?php namespace Drivers;

/**
 *This class has methods that return the DocComment string values, parse them into associative arrays 
 *and return usable metadata
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers
 *@link core.gliver.io
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Array;
use Drivers\String;


class Inspector {

	/**
	 *@var string $class This is the class name of the object instance to be parsed
	 *
	 */
	protected $class;

	/**
	 *@var array $meta Stores array of class,method, properties and their respective array of comment string data
	 *
	 */
	protected $meta;

	/**
	 *@var array $properties Stores all properties of class to be inspected in array key/value pairs
	 *
	 */
	protected $properties;

	/**
	 *@var array $methods Stores all methods of class be be inpected in array of key/value pairs
	 *
	 */
	protected $methods;

	/**
	 *This method initializes the class name to be inspected
	 *
	 *@param string $class The name of class to be inspected
	 *@return void
	 */
	public function __construct()
	{
		//assing the name of class to the protectd $class variable
		$this->class = $class;

	}

	/**
	 *This method gets the top level class comment
	 *
	 *@param null Uses the protected value of class already set in constructor
	 *@return string The comment string if found
	 *
	 */
	protected function _getClassComment()
	{
		//create an instance of the ReflectionClass passing the class name as parameter
		$reflection = new \ReflectionClass($this->class);

		//get and return the comment block for this class
		return $reflection->getDocComment();

	}

	/**
	 *This method gets and returns the class properties
	 *
	 *@param null
	 *@return array Class properties in associative array
	 */
	protected function _getClassProperties()
	{
		//instanctiate the ReflectionClass passing the $this->class name as parameter
		$reflection = new \ReflectionClass($this->class);

		//get and return the class properties
		return $reflection->getProperties();

	}

	/**
	 *This method gets and return $this->class methods
	 *
	 *@param null
	 *@return array Class methods in associative array
	 *
	 */
	protected function _getClassMethods()
	{
		//instanciate the ReflectionClass passing $this->class as parameter
		$reflection = new \ReflectionClass($this->class);

		//get and return this class methods
		return $reflection->getMethods();

	}

	/**
	 *This method gets the comment string for a class property
	 *
	 *@param string $property Property name for which to get comment string
	 *@return string Comment string for this class property
	 *
	 */
	protected function _getPropertyComment($property)
	{
		//instantiate the ReflectionProperty class passing $this->class and property name as paramter
		$reflection = new \ReflectionProperty($this->class, $property);

		//get and return the comment string for this property
		return $reflection->getDocComment();

	}

	/**
	 *This method gets the comment string for $this->class method
	 *
	 *@param string $method The method name for which to get comment string
	 *@return string The comment string if found
	 *
	 */
	protected function _getMethodComment($method)
	{
		//instantiate the ReflectionMethod class passing $this->class and $method as params
		$reflection = new \ReflectionMethod($this->class, $method);

		//get and return the comment string for this method
		return $reflection->getDocComment();

	}

	/**
	 *This method will parse the comment string return into an array of key/value pairs
	 *
	 *@param string $comment The comment string to be parsed
	 *@return array Associative array if comment substrings
	 *
	 */
	protected function _parse($comment)
	{	
		//define the array variable to hold the return value
		$metaArray = array();

		//define the regular expression pattern to use for string matching
		$pattern = "(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_]*)";

		//call String utility method to fetch for string matches
		$matches = String::match($comment, $pattern);

		//process return data if matches were found
		if( $matches != null )
		{
			//loop through each substring cleaning and triming
			foreach ($matches as $match) 
			{
				//parse and assign return variable to $parts array
				$parts = Array::clean(

						Array::trim(

							String::split($match, "[\s]", 2)

							)
					);

				//assign the value to $metaArray
				$metaArray[$parts[0]] = true;

				//parse if array element count is greater that one
				if( sizeof($parts) > 1)
				{
					//pass the metaArray to String and Array Utility functions for parsing
					$metaArray[$parts[0]] = Array::clean(
							Array::trim(
								String::split($parts[1], ",")
								)
						);

				}

			}

		}

		//return the resultant metaArray
		return $metaArray;

	}

	/**
	 *This method returns the metaData from class comment string
	 *
	 *@param null
	 *@return array Multidimensional array with class metaData
	 *
	 */
	public function getClassMeta()
	{
		//check if the class meta array index is not set and populate the value
		if( ! isset($meta['class']))
		{
			//get the comment for this class
			$comment = $this->_getClassComment();

			//if the DocBlock comment was found
			if( ! empty($comment) )
			{
				//parse comment string and assign return value to class meta
				$meta['class'] = $this->_parse($comment);

			}

			//if no comment block was found,  set value to null
			else
			{
				//set to null
				$meta['class'] = null;

			}

		}

		//return the $meta['class'] variale
		return $meta['class'];

	}

	/**
	 *This method gets the class properties of $this->class
	 *
	 *@param null
	 *@return array Class properties in associative array
	 *
	 */
	public function getClassProperties()
	{
		//if $_properties var is not set yet, populate it
		if( ! isset($properties) )
		{
			//get the class properties
			$propertiesArray = $this->_getClassProperties();

			//loop through resultset getting the property names and populating array
			foreach ($propertiesArray as $property) 
			{
				//add property name to associative array
				$properties[] = $property->getName();

			}

		}

		//return the resultant properties array
		return $properties;

	}

	/**
	 *This method gets the class methods for $this->class
	 *
	 *@param null
	 *@return array Method names in associative array
	 *
	 */
	public function getClassMethods()
	{
		//if not set yet, populate the $methods array
		if( ! isset($methods) )
		{
			//get class methods
			$methodsArray = $this->_getClassMethods();

			//loop through return array getting methodNames and population array
			foreach ($methodsArray as $method) 
			{
				//add name to $methods array
				$methods[] = $method->getName();

			}

		}

		//return the method names
		return $methods;

	}

	/**
	 *This method gets a property's metaData from comment block
	 *
	 *@param string $property Property for which to get metaData
	 *@return array Property metaData in multidimenstional array
	 */
	public function getPropertyMeta($property)
	{
		//get property meta, if not set yet
		if( ! isset($meta['properties'][$property]) )
		{
			//get the comment string for this property
			$comment = $this->_getPropertyComment($property);

			//parse comment block if found
			if( ! empty($comment) )
			{
				//parse comment and assign metaData array to this property index
				$meta['properties'][$property] = $this->_parse($comment);

			}

			//no valid comment block found, return null
			else
			{
				//set metaData value for this property to null
				$meta['properties'][$property] = null;

			}

		}

		//return the metaData property array
		return $meta['properties'][$property];

	}

	/**
	 *This method gets the metaData for class methods
	 *
	 *@param string $method Method name for which to get comments metaData
	 *@return array Method metaData in multidimensional array
	 */
	public function getMethodMeta($method)
	{
		//if metaData for this method is not yet set, get it
		if( ! isset($meta['methods'][$method]) )
		{
			//get the comment string for this method
			$comment = $this->_getMethodComment($method);

			//if valid comment sting was foung, parse it and assign value to method index in $meta array
			if( ! empty($comment) )
			{
				//parse comment and add to array
				$meta['methods'][$method] = $this->_parse($comment);

			}

			//no valid comment block found, return null
			else
			{
				//set method metaData to null
				$meta['methods'][$method] = null;

			}

		}

		//return metaData array
		return $meta['methods'][$method];

	}

}