<?php namespace Drivers\Cache;

/**
 *This class serves as the driver for all Memcached service connections
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Cache
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class Memcached {
	
	/**
	 *@var object Instance of Memcached driver class
	 */
	protected $service;

	/**
	 *@var string The Memcached server host address
	 */
	protected $host = '';

	/**
	 *@var string The Memcached server access port
	 */
	protected $port = '';

	/**
	 *@var boolean Returns true|false for memcached server connection
	 */
	protected $connected = false;

	/**
	 *This constructor method initialize the class properties for making connection
	 *
	 *@param array $options Array with server host name and port
	 *
	 */
	public function __construct(array $options)
	{
		//set the host name
		$this->host = $options['host'];

		//set the listening port number
		$this->port = $options['port'];

	}


	/**
	 *This method checks whether this $serive is a valid instance of the Memcached Driver Class
	 *
	 *@param null
	 *@return boolean true|false Checks and returns status of connection
	 */
	protected function validServiceCheck()
	{
		//get this service instance
		$getInstance = empty($this->service);

		//check this service instance
		$checkInstance = $this->service instanceof \Memcache;

		//check if connectoin is set and this service is an instance of the \Memcache
		if( $this->connected && $checkInstance && ! $getInstance)
		{
			//return true
			return true;

		}

		//otherwise return false
		return false;

	}

	/**
	 *This method opens a  connection to the Memcached Server
	 *
	 *@param null
	 *@return object Instance of the \Memcached Class Object
	 *@throws CacheException If the connection is not successful
	 */
	public function connect()
	{
		//attempt memcached server connection
		try{

			//get intance and assign to this service
			$this->service = new \Memcache();

			//make connections
			$this->service->connect($this->host, $this->port);

			//connection status to true
			$this->connected = true;

		}
		catch(\Exception $e){

			throw new CacheException("Unable to connect to service ");

		}

		//return this object instance
		return $this;

	}

	/**
	 *This method disconnects from the Memcache server
	 *
	 *@param null
	 *@return object Instance with disconnection
	 */
	public function disconnect()
	{
		//check there is a valid service connection before you attempt to disconnect
		if( $this->validServiceCheck() )
		{
			//This is a valid service connection. Close connection
			$this->service->close();

			//set connecition status to boolean false
			$this->connected = false;

		}

		//return this instance
		return $this;

	}

	/**
	 *This method get key/value pair from the memcached server
	 *
	 *@param string $key The key index to search for
	 *@param constant Representing decompression mode 
	 *@return object|array|string|int or $dafault the value associated with this key\
	 *@throws CacheException When there is not valid connection to service
	 */
	public function get($key, $default = null)
	{
		try{
			//check if there is a valid service connection
			if( ! $this->validServiceCheck() )
			{
				//throw exception for non existent connecition
				throw new CacheException("Not connected to a valid service connection");

			}

			//get the index value
			$value = $this->service->get($key, MEMCACHE_COMPRESSED);

			//check if value is not boolean false or empty
			if ( $value ) 
			{
				//return this value
				return $value;

			}

			//otherwise return default
			return $default;

		}
		catch(CacheException $error){

			$error->show();

		}

	}

	/**
	 *This method set key value pairs into memcache memory
	 *@param sting $key Name of Index to use for storing info
	 *@param object|array|literal Value to store against key in memory
	 *@param int $duration The amount of time before the store information is erased from memory
	 *@return object instance of this object
	 *@throws CacheException When there is not valid connection to service
	 */
	public function set($key, $value, $duration = 120)
	{
		//check is there is a valid connection before attempting to get data
		if ( ! $this->validServiceCheck() )
		{
			//throw exception
			throw new CacheException("Not connected to a valid service connecition");

		} 

		//set the key value pairs in memcache
		$this->service->set($key, $value,MEMCACHE_COMPRESSED, $duration );

		//return instance
		return $this;

	}

	/**
	 *This method removes stored key/value pairs from memory
	 *
	 *@param string $key The index of data whose value is to be erased
	 *@return object This instance
	 *@throws CacheException When there is not valid connection to service
	 */
	public function erase($key)
	{
		//check if there is a valid connection
		if ( ! $this->validServiceCheck() ) 
		{
			//throw exception
			throw new CacheException("Not connected to a valid service connecition");

		}

		//remove data from memory
		$this->service->delete($key);

		//return this instance
		return $this;

	}

	/**
	 *This method gets the statistics of memoey usage
	 *@param null
	 *@return object 
	 */
	public function getStats()
	{
		//return stats
		return $this->service->getStats();

	}
}
