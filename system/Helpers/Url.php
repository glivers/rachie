<?php namespace Core\Helpers;

/**
 *This class resolves urls and returns the appropriate url string required
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright Copyright (c) 2015 - 2020 Geoffrey Oliver
 *@link http://libraries.gliver.io
 *@category Core
 *@package Core\Helpers\Url
 *
 */

class Url {

	/**
	 *Class constructor
	 *This class intializes the required variables and object for this task
	 *
	 */
	public function __constructor()
	{
		//

	}

	/**
	 *This method returns the base url
	 *
	 *@param null
	 *@return string $url the base url for this application
	 *@throws this method does not throw an error
	 *
	 */
	public static function base()
	{
		global $url;

		//get the server name from global $_SERVER[] array()
		$base  = $_SERVER['SERVER_NAME']; 

		//prepend installation folder to server name
		//$base .= substr($_SERVER['REQUEST_URI'], 0,  strpos($_SERVER['REQUEST_URI'], str_replace("url=","",$_SERVER['REDIRECT_QUERY_STRING'])));
		
		//prepend installation folder to server name
		$base .= substr($_SERVER['REQUEST_URI'], 0,  strpos($_SERVER['REQUEST_URI'], $url));

    	//use https if its defined in the $_SERVER global variable
    	$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";

		//compose the url string
		return $protocol . '://' . $base;

	}

	/**
	 *This method returns the assets url
	 *
	 *@param null
	 *@return string $url the assets url for this application
	 *@throws this method does not throw an error
	 *
	 */
	public static function assets()
	{
		global $url;

		//get the server name from global $_SERVER[] array()
		$base  = $_SERVER['SERVER_NAME']; 

		//prepend installation folder to server name
		$base .= substr($_SERVER['REQUEST_URI'], 0,  strpos($_SERVER['REQUEST_URI'], $url));

    	//use https if its defined in the $_SERVER global variable
    	$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";

		//compose the url string
		return $protocol . '://' . $base . 'public/';

	}

	/**
	 *This method returns the assets url
	 *
	 *@param string $string the string to use to compose this url
	 *@return string $url the full url for this request
	 *@throws malformed string
	 *
	 */
	public static function link( $string = null )
	{
		global $url;

		//get the server name from global $_SERVER[] array()
		$base  = $_SERVER['SERVER_NAME']; 

		//prepend installation folder to server name
		$base .= substr($_SERVER['REQUEST_URI'], 0,  strpos($_SERVER['REQUEST_URI'], $url));

    	//use https if its defined in the $_SERVER global variable
    	$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";

		//compose the url string
		return $protocol . '://' . $base . '<br />';


	}

	
}