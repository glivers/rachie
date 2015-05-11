<?php namespace Drivers\Cache;

/**
 *This trait is implemented in the base class. It give the basic initialization code for a cache service
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Cache
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

trait CacheImplementation {

	/**
	 *@var string Name of Caching Service to Connect to
	 */
	protected $type;

	/**
	 *@var array Cache service initializaton options
	 */
	protected $options = array();

	/**
	 *This method intializes a cache service
	 *
	 *@param null
	 *@return object Instance of cache service
	 *@throws Core\Drivers\Cache\CacheException When cache service type is not set
	 */
	public function initialize()
	{
		//check if the cache service type is set
		if( ! $this->type )
		{
			//throw CacheException 
			throw new CacheException("Invalid Cache Service type supplied");
		}

		//The cache service type has been set, get driver instance
		//set the $this->type as switch value to chack type name
		switch ( $this->type ) 
		{
			case 'memcached':

				//grab the global configuration arary for settings
				global $config;

				//set the host name in options
				$this->options['host'] = $config['cache']['configuration'][$this->type]['host'];

				//set the port number
				$this->options['port'] = $config['cache']['configuration'][$this->type]['port'];

				//return instance of memcached driver
				return new Memcached($this->options); 

				break;
			
			default:

				//throw CacheException if not cache service type matched
				throw new CacheException("Invalid Cache Service type supplied");

				break;

		}

	}

}