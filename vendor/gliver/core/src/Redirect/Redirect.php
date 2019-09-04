<?php namespace Helpers\Redirect;

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

use Helpers\Url\Url;

class Redirect {

	/**
	*@var string The query string to append to the url string
	*/
	private static $query_string;

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
	public static function to($path, array $data = null )
	{
		//compose query string if data was set
		($data !== null) ? self::with($data) : '';

		//compose full url
		$path = (self::$query_string === null) ?  Url::link($path) : Url::link($path) . '?' . self::$query_string;

		//redirect to path
		header('Location: ' . $path);

		//stop any further html output
		exit();

	}

	/**
	 * This method sets the url parameters to redirect with in the $_GET data.
	 * @param string $key The key with which to pass this data
	 * @param mixed $value of this key
	 * @return \Object this static instance
	 */
	public static function with($key, $query_data)
	{
		//compose url string
		$query_string = http_build_query(array($key => $query_data));

		//set the value of query string
		self::$query_string .= $query_string;

		return new static;

	}

}
