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
		//get the server name
		$base  = $_SERVER['SERVER_NAME']; //. $_SERVER['REQUEST_URI']; 


		//get the protocol
		if(isset($_SERVER['HTTPS']))
		{
        	//use https if its defined in the $_SERVER global variable
        	$protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";

    	}
    	else
    	{
        	//defualt to http if https is not set to 'on'
        	$protocol = 'http';

    	}

		$urlArray = explode("/", $_SERVER['REQUEST_URI']);

		//compose the url string
		echo $protocol . "://" . $base . $_SERVER['REQUEST_URI'];echo "<pre>"; print_r($urlArray); exit();

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
		//get the server name

		//get the protocol

		//compose the url string

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
		//get the server name

		//get the protocol

		//compose the url string

	}

	
}