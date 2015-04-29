<?php namespace Core\Drivers\Database;

/**
 *This class serves as the driver for all MySQL service connections
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Database
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class MySQl {

	/**
	 *@var object Instance of MySQL object
	 *@readwrite
	 */
	protected $service;

	/**
	 *@var string Database Hostname
	 *@readwrite
	 */
	protected $host;

	/**
	 *@var string Database Username
	 *@readwrite
	 */
	protected $username;

	/**
	 *@var string Database password
	 *@readwrite
	 */
	protected $password;

	/**
	 *@var string The database schema
	 *@readwrite
	 */
	protected $schema;

	/**
	 *@var string Server Port number
	 *@readwrite
	 */
	protected $port = '3306';

	/**
	 *@var string Charset
	 *@readwrite
	 */
	protected $charset = 'utf8';

	/**
	 *@var String Default database Engine
	 *@readwrite
	 */
	protected $engine = "InnoDB";

	/**
	 *@var boolean Database connection status
	 *@readwrite
	 */
	protected $connected = false;

	/**
	 *This method checks if there is a valid database connection instance
	 *
	 *@param null
	 *@return bool true|false Depending on the database connection status
	 */
	protected function validService()
	{
		//check is the service variable is empty
		$empty  = empty($this->service);

		//check is this service is an instance if MySQL
		$instance = $this->service instanceof \MySQLi;

		//return true if there is a valid connection and $this->service is an instance of MySQLi
		if ($this->connected && $instance && ! $empty) 
		{
			//return true in this case
			return true;

		}

		//otherwise return false
		return false;

	}

	/**
	 *This method makes a connection to the database
	 *
	 *@param null
	 *@return object Instance of the MySQLi connection object
	 *@throws DbException If Unable to connect with the provided settings
	 */
	public function connect()
	{
		//check if there is a valid servic instance
		if ($this->validService()) 
		{
			//make a connection attempt
			$this->service = new \MySQLi($this->host,$this->username,$this->password,$this->schema,$this->port);

			//check is the connection attempt returned an error
			if ($this->service->connect_error) 
			{
				//throw exception
				throw new DbException("Unable to connect to Database service with the provided settings");

			}

			//set the connection variable to true
			$this->connected = true;

		}

		//return this connection instance
		return $this;

	}

	/**
	 *This method disconnects from this database conection
	 *
	 *@param null
	 *@return object Instance of this diconnection object
	 */
	public function disconnect()
	{
		//check if there is a valid connection instance
		if ($this->validService() ) 
		{
			//set the connection status to false
			$this->connected = false;

			//call method to disconnect from database
			$this->service->close();

		}

		//return this instance after disconnection 
		return $this;

	}

	/**
	 *This method returns an query instance
	 *
	 *@param null
	 *@return object Query instance
	 */
	public function query()
	{
		//return instance
		return new MySQLQuery(array('connector' => $this));

	}

	/**
	 *This method excecutes the provided SQL statement
	 *
	 *@param string $sql MySQL query string to execute
	 *@return object Reponse of Query Execution
	 *@throws DbException If there is not valid database connection established
	 */
	public function execute($sql)
	{
		//check if there is a valid service instance
		if (! $this->validService() ) 
		{
			//throw exceptions
			throw new DbException("Not connected to a valid database service");

		}

		//execute query and return response object
		return $this->service->query($sql);

	}

	/**
	 *This method escapes the provided value to make it safe for queries
	 *
	 *@param string $value String or Array to be escaped
	 *@return string New query safe string after execution
	 *@throws DbException Not connected to a valid database service
	 */
	public function escape($value)
	{
		//check if there is a valid service instance
		if ( ! $this->validService() ) 
		{
			//throw exception
			throw new DbException("Not connected to a valid database service");
			
		}

		//escape and return output
		return $this->service->real_escape_string($value);

	}

	/**
	 *This method returns the last Id if the row inserted
	 *
	 *@param null
	 *@return int The last insert row id
	 *@throws DbException Not connected to a valid database service
	 */
	public function lastInsertId()
	{
		//chekc is there is a valid service connection isntance
		if ( ! $this->validService() ) 
		{
			//throw exception
			throw new DbException("Not connected to a valid database service");

		}

		//get and return the last insert is
		return $this->service->insert_id;

	}

	/**
	 *This method returns the number of rows affected by the last SQL query executed
	 *
	 *@param null
	 *@return int The  numeber of affected rows
	 *@throws DbEXception Not connected to a valid database service
	 */
	public function affectedRows()
	{
		//check if there is valid service instance
		if ( ! $this->validService() ) 
		{
			//throw exception
			throw new DbException("Not connected to a valid database service");

		}

		//get number of affected rows and return
		return $this->service->affected_rows;

	}

	/**
	 *This method returns the last error message for the most recent MySQLi function call
	 *
	 *@param null
	 *@return string String description of error. Empty string if no error occured
	 *@throws DbException Not connected to a valid database service
	 */
	public function lastError()
	{
		//check if there is a valid service instance
		if ( ! $this->validService() ) 
		{
			//throw error
			throw new DbException("Not connected to a valid database service");

		}

		//get the error message and return
		return $this->service->error;

	}

}
	
