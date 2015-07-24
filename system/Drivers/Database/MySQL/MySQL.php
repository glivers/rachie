<?php namespace Drivers\Database\MySQL;

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

class MySQL {

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
	protected $database;

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
	 *This method sets the initial database connectivity parameters 
	 *
	 *@param array $options Array containing the parameters like username, password e.t.c
	 */
	public function __construct(array $options)
	{
		//cast the input array into an object
		$options  = (object)$options;

		//set the database host
		$this->host = $options->host;

		//set the database username
		$this->username = $options->username;

		//set the database password
		$this->password = $options->password;

		//set the database schema
		$this->database = $options->database;

		//set the database port
		$this->port = $options->port;

		//set the default charset
		$this->charset = $options->charset;

		//set the default database engine
		$this->engine = $options->engine;

	}

	/**
	 *This method checks if there is a valid database connection instance
	 *
	 *@param null
	 *@return bool true|false Depending on the database connection status
	 */
	protected function validService()
	{
		//check if the service variable is empty
		$empty  = empty($this->service);

		//check if this service is an instance if MySQL
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
		//check if there is a valid service instance
		if ( ! $this->validService()) 
		{
			//make a connection attempt
			$this->service = new \MySQLi($this->host,$this->username,$this->password,$this->database,$this->port);

			//check is the connection attempt returned an error
			if ($this->service->connect_error) 
			{
				//throw exception
				throw new MySQLException("Unable to connect to Database service with the provided settings");

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
			throw new MySQLException("Not connected to a valid database service");

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
			throw new MySQLException("Not connected to a valid database service");
			
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
			throw new MySQLException("Not connected to a valid database service");

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
			throw new MySQLException("Not connected to a valid database service");

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
			throw new MySQLException("Not connected to a valid database service");

		}

		//get the error message and return
		return $this->service->error;

	}

	public function sync($model)
	{
		$lines = array();

		$indices = array();

		$columns = $model->columns;

		$template = "CREATE TABLE '%s' (\n%s,\n%s\n) ENGINE=%s DEFAULT CHARSET=%s;";

		foreach ($columns as $column)
		{

			$raw = $column["raw"];

			$name = $column["name"];

			$type = $column["type"];

			$length = $column["length"];

			if ($column["primary"])
			{

				$indices[] = "PRIMARY KEY ('{$name}')";

			}

			if ($column["index"])
			{

				$indices[] = "KEY '{$name}' ('{$name}')";

			}

			switch ($type)
			{
				case "autonumber":
				{

					$lines[] = "'{$name}' int(11) NOT NULL AUTO_INCREMENT";

					break;

				}

				case "text":
				{

					if ($length !== null && $length <= 255)
					{

						$lines[] = "'{$name}' varchar({$length}) DEFAULT NULL";

					}

					else
					{

						$lines[] = "'{$name}' text";

					}

					break;
				}

				case "integer":
				{

					$lines[] = "'{$name}' int(11) DEFAULT NULL";

					break;

				}

				case "decimal":
				{

					$lines[] = "'{$name}' float DEFAULT NULL";

					break;

				}

				case "boolean":
				{

					$lines[] = "'{$name}' tinyint(4) DEFAULT NULL";

					break;

				}

				case "datetime":
				{

					$lines[] = "'{$name}' datetime DEFAULT NULL";

					break;

				}

			}

		}

		$table = $model->table;

		$sql = sprintf($template, $table, join(",\n", $lines), join(",\n", $indices), $this->_engine, $this->_charset);
		
		$result = $this->execute("DROP TABLE IF EXISTS {$table};");

		if ($result === false)
		{

			$error = $this->lastError;

			throw new Exception\Sql("There was an error in the query: {$error}");

		}

		$result = $this->execute($sql);

		if ($result === false)
		{

			$error = $this->lastError;

			throw new Exception\Sql("There was an error in the query: {$error}");

		}

		return $this;
		
	}

}
	
