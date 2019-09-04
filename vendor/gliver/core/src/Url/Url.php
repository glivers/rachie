<?php namespace Helpers\Url;

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

use Helpers\ArrayHelper\ArrayHelper;
use Drivers\Registry\Registry;

class Url {

	/**
	 *This is the constructor class. We make this private to avoid creating instances of
	 *this object
	 *
	 *@param null
	 *@return void
	 */
	private function __construct(){}

	/**
	 *This method stops creation of a copy of this object by making it private
	 *
	 *@param null
	 *@return void
	 *
	 */
	private function __clone(){}

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

		//get the server name from global $_SERVER[] array()
		$base  = $_SERVER['SERVER_NAME']; 

		$url = Registry::getUrl();

		//check if there is a uri string
		if ( ! empty($url) ) 
		{
			//prepend installation folder to server name
			$base .= substr($_SERVER['REQUEST_URI'], 0,  strpos($_SERVER['REQUEST_URI'], Registry::getUrl()));

		}
		//there is no query string, 
		else
		{
			//prepend installation folder to server name
			$base .= substr($_SERVER['REQUEST_URI'], 0);

		}

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
	public static function assets($assetName = null)
	{
		//get the server name from global $_SERVER[] array()
		$base  = $_SERVER['SERVER_NAME']; 

		$url = Registry::getUrl();

		//check if there is a uri string
		if ( ! empty($url) ) 
		{
			//prepend installation folder to server name
			$base .= substr($_SERVER['REQUEST_URI'], 0,  strpos($_SERVER['REQUEST_URI'], Registry::getUrl()));

		}
		//there is no query string, 
		else
		{
			//prepend installation folder to server name
			$base .= substr($_SERVER['REQUEST_URI'], 0);

		}


    	//use https if its defined in the $_SERVER global variable
    	$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";

		//compose the url string
		return $protocol . '://' . $base . 'public/' . $assetName;

	}

	/**
	 * This method returns the url string.
	 * @param mixed $linkParams The params to add to the link to generate
	 * @return string $url the base url for this application
	 * @throws this method does not throw an error
	 */
	public static function link($linkParams = null)
	{
		if($linkParams === null){

			$link_params = null;
		}

		elseif(is_array($linkParams)){

			$link_params = join(Registry::getConfig()['url_component_separator'], $linkParams);
		
		}

		else{

			$params = func_get_args();
			$link_params = join(Registry::getConfig()['url_component_separator'], $params);
		}

		//get the server name from global $_SERVER[] array()
		$base  = $_SERVER['SERVER_NAME']; 

		$url = Registry::getUrl();

		//check if there is a uri string
		if ( ! empty($url) ) 
		{
			//prepend installation folder to server name
			$base .= substr($_SERVER['REQUEST_URI'], 0,  strpos($_SERVER['REQUEST_URI'], Registry::getUrl()));

		}
		//there is no query string, 
		else
		{
			//prepend installation folder to server name
			$base .= substr($_SERVER['REQUEST_URI'], 0);

		}

    	//use https if its defined in the $_SERVER global variable
    	$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";

		//compose the url string
		return $protocol . '://' . $base . $link_params;

	}


	
}