<?php namespace Drivers;

/**
 *This class creates and stores instances of objects
 *
 *This class implements the Singleton design pattern, so an instance of it cannot be created. It
 *initializes and stores instances of objects, this helps to avoid instantiating multiple instances 
 *of the same object while the same instance would suffice
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver 
 *@category Core
 *@package Core\Drivers
 *@link core.gliver.io
 */

class Registry {

	/**
	 *
	 *@var array $instances This stores instances of objects in key/instance pairs
	 */
	private static $instances = array();

	/**
	 *This is the constructor class. We make this private to avoid creating instances of
	 *this Register object
	 *
	 *@param null
	 *@return void
	 */
	private function __construct()
	{
		//do something

	}

	/**
	 *This method stops creation of a copy of this object by making it private
	 *
	 *@param null
	 *@return void
	 *
	 */
	private function __clone()
	{
		//do something

	}

	/**
	 *This method gets the occurance of an object instances and returns the object instance
	 *
	 *@param string $key Then index/name for the object to return
	 *@param object $default The default object to return if the specified object instance is not found
	 *@return object Object instance or teh default object if nor found
	 *@throws this method does not throw an error
	 */
	public static function get($key, $default = null)
	{
		//check if this object instance is instanciated already and return
		if(isset(self::$instances[$key]))
		{
			//return this object instance
			return self::$instances[$key];

		}

		//this object instance does not exist, return default
		return $default;

	}

	/**
	 *This method stores object instances in key/instance pairs
	 *
	 *@param string $key The key to use for setting and accessing this instance from array
	 *@param object $instance The object instance to be stored in Registry
	 *@return viod
	 *@throws This method does not throw an error
	 */
	public static function set($key, $instance = null)
	{
		//add new object instance to array
		self::$instances[$key] = $instance;

	}

	/**
	 *This method removes an object instance from memory
	 *
	 *@param string $key The object index name for accessing this index in array
	 *@return void
	 *@throws This method does not throw an error
	 */
	public static function erase($key)
	{
		//remove this object instance from memory by the key
		unset(self::$instances[$key]);

	}

}