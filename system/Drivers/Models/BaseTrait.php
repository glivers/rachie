<?php namespace Drivers\Models;

/**
 *This is the Base Trait class that the Model Class implements
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Models
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

trait BaseTrait {

	/**
	 *@var int Primary Key
	 *@column
	 *@readwrite
	 *@primary
	 *@type autonumber
	 */
	protected $id;

	/**
	 *@var string 
	 *@column
	 *@readwrite
	 *@type text
	 *@length 100
	 */
	protected $first;

	/**
	 *@var string
	 *@column
	 *@readwrite
	 *@type text
	 *@length 100
	 */
	protected $last;

	/**
	 *@var string
	 *@column
	 *@readwrite
	 *@type text
	 *@length 100
	 *@index
	 */
	protected $email;

	/**
	 *@var string
	 *@column 
	 *@readwrite
	 *@type text
	 *@length 100
	 *@@index
	 */
	protected $password;

	/**
	 *@var string
	 *@readwrite
	 *@column
	 *@type text
	 */
	protected $notes;

	/**
	 *@var bool true|false
	 *@column
	 *@readwrite
	 *@type boolean
	 *@index
	 */
	protected $live;

	/**
	 *@var boolean
	 *@column
	 *@readwrite
	 *@type boolean
	 *@index
	 */
	protected $deleted;

	/**
	 *@var timestamp|string
	 *@column
	 *@readwrite
	 *@type datetime
	 */
	protected $modified;

	/**
	 *This method gets and returns the table name.
	 *@param null
	 *@return string The table name
	 */
	public function getTable()
	{
		//check if the table name variable contains a value
		if ( empty($this->table) ) 
		{
			//if table is not set yet, get a table name from this class instance
			$this->table = strtolower(StringUtility::singular(get_class($this)));

		}

		//return the formed table name
		return $this->table;

	}

	/**
	 *This method gets and returns a database connector instance
	 *
	 *@param null
	 *@return object The database connector object instance
	 */
	public function getConnector()
	{
		//check if the connector object instance has been set
		if ( empty($this->connector) ) 
		{
			//get a database instance object from the registry
			$database = Registry::get('database');

			//check if a connector instance was found
			if ( ! $database ) 
			{
				//if no instance was found, throw exception
				throw new ConnectorException("No connector instance available", 1);

			}

			//initialize database connector and assign object value to connector
			$this->connector = $database->initialize();

		}

		//return this connector object instance
		return $this->connector;

	}

	/**
	 *This method returns the columns as defined with all their metadata
	 *
	 *@param null
	 *@return array Array of column names
	 */
	public function getColumns()
	{
		//check if the columns array has been set
		if ( empty($this->columns) ) 
		{
			//set variable to contain the primaries
			$primaries = 0;

			//set array to contain the column names
			$columns = array();

			//get the class name
			$class = get_class($this);

			//get the data types into an array
			$types = $this->types;

			//get new inspector class instance
			$inspector = new Inspector($this);

			//get all the class properties for this class
			$properties = $inspector->getClassProperties();


			$first = function($array, $key)
			{
				//check if the key exists in array and the size of array greater than one
				if ( ! empty($array[$key]) && sizeof($array[$key]) == 1) 
				{
					//return the first element in $array[$key]
					return $array[$key][0];

				}

				//if no instance of this was found, return null
				return null;

			};

			//loop through each property in properties array performing the associated function
			foreach ($properties as $property ) 
			{
				$propertyMeta = $inspector->getPropertyMeta($property);

				if ( ! empty($propertyMeta["@column"]) )
				{

					$name = preg_replace("#^_#", "", $property);
					$primary = ! empty($propertyMeta["@primary"]);
					$type = $first($propertyMeta, "@type");
					$length = $first($propertyMeta, "@length");
					$index = ! empty($propertyMeta["@index"]);
					$readwrite = ! empty($propertyMeta["@readwrite"]);
					$read = ! empty($propertyMeta["@read"]) || $readwrite;
					$write = ! empty($propertyMeta["@write"]) || $readwrite;
					$validate = ! empty($propertyMeta["@validate"]) ? $propertyMeta["@validate"] : false;
					$label = $first($propertyMeta, "@label");

					if ( ! in_array($type, $types))
					{

						throw new Exception\Type("{$type} is not a valid type");
					}

					if ($primary)
					{

						$primaries++;

					}

					$columns[$name] = array(
						"raw" => $property,
						"name" => $name,
						"primary" => $primary,
						"type" => $type,
						"length" => $length,
						"index" => $index,
						"read" => $read,
						"write" => $write,
						"validate" => $validate,
						"label" => $label
					);

				}
					
			}

			if ($primaries !== 1)
			{

				throw new Exception\Primary("{$class} must have exactly one @primary column");

			}

			$this->columns = $columns;

		}

			return $this->columns;

	}

	public function getColumn($name)
	{

		if ( ! empty($this->columns[$name]))
		{

			return $this->columns[$name];

		}

		return null;

	}


	public function getPrimaryColumn()
	{

		if ( ! isset($this->primary) )
		{
			$primary;

			foreach ($this->columns as $column)
			{
				if ($column["primary"])
				{
					$primary = $column;

					break;

				}

			}

			$this->primary = $primary;

		}

		return $this->primary;

	}

}