<?php namespace Helpers;

/**
 *This class handles php session re-direction
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Helpers\Redirect
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class Redirect {

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
	 *This method redirect to the specified page
	 *
	 *@param string $path The path to redirect to 
	 *@param mixed $data The data with which to do redirect
	 */	
	public static function to($path, $data = null )
	{
		//compose full url
		$path = Url::base($path);

		//redirect to path
		header('Location: ' . $path);

		//stop any further html output
		exit();

	}

}
