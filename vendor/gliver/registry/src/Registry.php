<?php namespace Drivers\Registry;

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
use Drivers\Database\BaseDb;
use Drivers\Cache\CacheBase;
use Drivers\Templates\BaseTemplateClass;

class Registry {

	/**
	*@var float The application start time in seconds
	*/
	public static $gliver_app_start;

	/**
	 *
	 *@var array $instances This stores instances of objects in key/instance pairs
	 */
	private static $instances = array();

	/**
	 *
	 *@var array $instances This stores the configuration of this application
	 */
	private static $config = array();

	/**
	 *
	 *@var array $instances This stores database settings of this application
	 */
	private static $settings = array();

	/**
	 *
	 *@var string $url This stores the url string for this request
	 */
	private static $url = '';

	/**
	 *
	 *@var string $url This stores the url string for this request
	 */
	public static $errorFilePath = '';

	/**
	 *
	 *@var array $instances This stores rejistered resources for  this framework
	 */
	private static $resources = array(
			'database',
			'cache',
			'template',
	);

	/**
	 *This is the constructor class. We make this private to avoid creating instances of
	 *this Register object
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
	 *This method gets the occurance of an object instances and returns the object instance
	 *
	 *@param string $key Then index/name for the object to return
	 *@param object $default The default object to return if the specified object instance is not found
	 *@return object Object instance or teh default object if nor found
	 *@throws this method does not throw an error
	 */
	public static function get()
	{
		//check if there were arguments passed
		if ( sizeof(func_num_args()) == 0 ){echo 'No arguments passed for this resource'; exit();} //throw an exceptionk

		//get the arguments passed
		$args = func_get_args();

		//get the resource key
		$key = array_shift($args);

		//check if this object instance is instanciated already and return
		if(isset(self::$instances[$key]))
		{
			//return this object instance
			return self::$instances[$key];

		}
		//this resource is not defined yet
		else
		{
			//check if this resource is defined
			if ( in_array($key, self::$resources) )  
			{
				//get an object instance of this 
				$instance = self::getInstance($key, $args);

				//set this resource instance
				if( $instance ) $set = self::setInstance($key, $instance);

				//return this object instance
				if( $set ) return self::$instances[$key]; 

			}

			//this resource is not defined, return an error
			else
			{
				//throw an exception
				echo "We have decided to throw an exception";
			}

		}

	}

	/**
	 *This method stores object instances in key/instance pairs
	 *
	 *@param string $key The key to use for setting and accessing this instance from array
	 *@param object $instance The object instance to be stored in Registry
	 *@return viod
	 *@throws This method does not throw an error
	 */
	public static function set($key, $instance)
	{		
		//add new object instance to array
		self::$instances[$key] = $instance;

	}

	/**
	 *This method stores object instances in key/instance pairs
	 *
	 *@param string $key The key to use for setting and accessing this instance from array
	 *@param object $instance The object instance to be stored in Registry
	 *@return viod
	 *@throws This method does not throw an error
	 */
	private static function setInstance($key, $instance)
	{		
		//add new object instance to array
		self::$instances[$key] = $instance;

		return true;

	}

	/**
	 *This method sets the configuration settings of this application
	 *
	 *@param array The array containing the configuration parameters
	 *@return void 
	 *@throws This method does not throw an error
	 */
	public static function setConfig($config)
	{
		//set the error file path
		self::$errorFilePath = dirname(dirname(__FILE__)) . '/Exceptions/';

		//set the config array
		self::$config = $config; 

	}

	/**
	 *This method set the database settings of this application
	 *
	 *@param array The array with database settings
	 *@return void
	 *@throws This method does not throw an error
	 */
	public static function setSettings($database)
	{
		//set the database settings
		return self::$settings = $database; 

	}

	/**
	 *This method set the url for this request
	 *
	 *@param string The url string
	 *@return void
	 *@throws This method does not throw an error
	 */
	public static function setUrl($url)
	{
		//set the database settings
		return self::$url = $url; 

	}

	/**
	 *This method gets and returns the object instance of a resource
	 *
	 *@param string $key The key to use for setting and accessing this instance from array
	 *@param object $instance The object instance to be stored in Registry
	 *@return viod
	 *@throws This method does not throw an error
	 */
	private static function getInstance($key, $args)
	{
		//get the name of the method
		$key = 'get' . ucfirst($key);

		//get an instance of this resource
		$instance = self::{"{$key}"}($args);

		//return this object instance
		return $instance;

	}

	/**
	 *This method returns an instance of the template class
	 *
	 *@param string $key The object index name for accessing this index in array
	 *@return void
	 *@throws This method does not throw an error
	 */
	public static function getTemplate()
	{
		//return the instance of this template
		return new BaseTemplateClass;

	}

	/**
	 *This method returns an instance of the database class
	 *
	 *@return object The instance of this database class.
	 *@throws This method does not throw an error
	 */
	public static function getDatabase()
	{
		//get the database settings from global space
        global $database;

        //create instance of database class
        $instance = new BaseDb($database['default'], $database[$database['default']]);

        //get connection instance and make attempt to connect to the database
        $instance = $instance->initialize()->connect();

		return $instance; 

	}

	/**
	 *This method returns the configuration settings of this application
	 *
	 *@return array The array containing the configuration parameters
	 *@throws This method does not throw an error
	 */
	public static function getConfig()
	{
		//return the cinfig array
		return self::$config; 

	}

	/**
	 *This method returns the url string
	 *
	 *@return string The url string for this request
	 *@throws This method does not throw an error
	 */
	public static function getUrl()
	{
		//return the cinfig array
		return self::$url; 

	}

	/**
	 *This method returns the database settings of this application
	 *
	 *@return array The array with database settings
	 *@throws This method does not throw an error
	 */
	public static function getSettings()
	{
		//return the database settings
		return self::$settings; 

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