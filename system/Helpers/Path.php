<?php namespace Core\Helpers;

/**
 *This class defines the various paths that will be used in this application
 *
 */

class Path {

	/**
	 *This method defines the path to the application folder
	 *
	 */
	public static function app()
	{
		//get the global configuration array
		global $config;

		return $config['root'] . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR;

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
		//get the global configuration array
		global $config;

		return $config['root'] . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR;

	}

	/**
	 *This method defines the path to tmp folder
	 *
	 */
	public static function tmp()
	{
		//get the global configuration array
		global $config;

		return $config['root'] . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;

	}
	
}