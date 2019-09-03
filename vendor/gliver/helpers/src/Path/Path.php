<?php namespace Helpers\Path;

/**
 *This class defines the various paths that will be used in this application
 *
 */

use Drivers\Registry\Registry;

class Path {

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
	 *This method defines the path to the application folder
	 *
	 */
	public static function app()
	{
		//get the global configuration array
		global $config;

		return Registry::getConfig()['root'] . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR;

	}
	
	/**
	 *This method defines the path  to the root directory
	 *
	 */
	public static function base()
	{
		//get the global configuration array
		global $config;

		return $config['root'] . DIRECTORY_SEPARATOR;

	}

	/**
	 *This method defines the path to system folder
	 *
	 */
	public static function sys()
	{
		
		return Registry::getConfig()['root'] . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR;

	}

	/**
	 *This method defines the path to tmp folder
	 *
	 */
	public static function tmp()
	{

		return Registry::getConfig()['root'] . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;

	}

	/**
	 *This method defines the path to views folder
	 *
	 *@param $name The name of the view file for which to return path
	 *@return void
	 */
	public static function view($fileName)
	{
		//explode the view files name into array
        $array = explode("/", $fileName);

        if(count($array) == 1) $array = explode('.',$fileName);

		return Registry::getConfig()['root'] . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, $array) . '.glade.php';

	}
	
}