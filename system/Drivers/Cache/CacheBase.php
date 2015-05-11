<?php namespace Drivers\Cache;

/**
 *This the base class which all cache drivers extend
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Cache
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class CacheBase {

	use CacheImplementation;

	/**
	 *This constructor method sets the default Caching Service Type to use
	 *
	 *@param string $type Name of Cache Service
	 */
	public function __construct($type)
	{
		//set the default to cache service to use
		$this->type  = $type;

	}
	
}
