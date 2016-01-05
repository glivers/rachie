<?php namespace Drivers\Utilities;

/**
 *This class extends the ReflectionClass for inspecting controller classes.
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Drivers
 *@package Drivers\Utilities\Inspector
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class Inspector extends \ReflectionClass {

	/**
	*This contructor extends the parent constructor
	*@param object $object Instance of the class reflect
	*@return void
	*/
	public function __construct($object)
	{
		//call the parent constructor
		parent::__construct($object);
	}

	/**
	*This method creates an instance of the ReflectionClass.
	*@param object $object Instance of the class reflect
	*@return self()
	*/
	public static function Create($object)
	{
		//return this instance
		return new self($object);

	}

}