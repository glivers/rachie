<?php namespace Drivers\Database\MySQL;

/**
 *This is the response object for all successful MySQL database queries
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Drivers\Database
 *@package Drivers\Database\MySQL\MySQLResponseObject
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class MySQLResponseObject {

	/**
	*@var string The query string that was excecuted
	*/
	private $query_string = '';

	/**
	*@var float This property stores the query excecution time
	*/
	private $query_time = 0.0;

	/**
	*@var int The number of fields queried
	*/
	private $field_count;

	/**
	*@var int The number of rows returned
	*/
	private $num_rows;

	/**
	*@var object The queried table field names and metadata
	*/
	private $query_fields;

	/**
	*@var array The result set rows in array format
	*/
	private $result_array = array();

	/**
	*@var object The result set rows in object format
	*/
	private $result_object;

	/**
	*This method sets the query excecution time
	*
	*@param int $microtime The query excecution time
	*@return \Object $this object instance
	*/
	public function setQueryTime($microtime)
	{
		//set the value of the query excecution time
		$this->query_time = $microtime;

		return $this;

	}

	/**
	*This method returns the query excecution time
	*
	*@param null
	*@return int The value of the Query Excecution time
	*/
	public function query_time()
	{
		//return the query excecution time
		return $this->query_time;

	}

	/**
	*This method sets the query string that was excecuted
	*
	*@param string $query_string The query string that was excecuted
	*@return \Object $this object instance
	*/
	public function setQueryString($query_string)
	{
		//set the value of the query string
		$this->query_string = $query_string;

		return $this;

	}

	/**
	*This method returns the value of the query_string property
	*
	*@param null
	*@return string The value of the query_string property
	*/
	public function query_string()
	{
		//return the value of $query_string
		return $this->query_string;

	}

	/**
	*This method sets the value of the field count
	*
	*@param int $field_count The number of fields in query
	*@return \Object $this object instance
	*/
	public function setFieldCount($field_count)
	{
		//set the value of field count
		$this->field_count = $field_count;

		return $this;

	}

	/**
	*This method gets the value of the field count
	*
	*@param null
	*@return int The value of field counts
	*/
	public function field_count()
	{
		//return the value fo the field count
		return $this->field_count;

	}

	/**
	*This method sets the value of the number of rows returned by query
	*
	*@param int $num_rows The number of rows returned
	*@return \Object $this object instance
	*/
	public function setNumRows($num_rows)
	{
		//the value of num_rows property
		$this->num_rows = $num_rows;

		return $this;

	}

	/**
	*This method returns the value of the num_rows property
	*
	*@param null
	*@return int THe number of rows returned by query
	*/
	public function num_rows()
	{
		//return the value of num_rows property
		return $this->num_rows;

	}

	/**
	*This method sets information about the fields queried
	*
	*@param \Object The stdClass object of the fields and metadata
	*@return \Object $this object instance
	*/
	public function setQueryFields($query_fields)
	{
		//set the value of query_fields property
		$this->query_fields = $query_fields;

		return $this;

	}

	/**
	*This method returns the fields in the query
	*
	*@param null
	*@return \Object The fields in stdClass object
	*/
	public function query_fields()
	{
		//return the value of the query_fields property
		return $this->query_fields;

	}

	/**
	*This method returns the number of fields in the query
	*
	*@param null
	*@return \Object The fields in stdClass object
	*/
	public function query_fields_count()
	{
		//return the value of the query_fields property
		return count($this->query_fields);

	}

	/**
	*This method sets the number of rows in array format
	*
	*@param array $result_array The resultset in array format
	*@return \Object $this object instance
	*/
	public function setResultArray($result_array)
	{
		//set the value of result_array property
		$this->result_array = $result_array;

		return $this;

	}	

	/**
	*This method returns the resultset in array format
	*
	*@param null
	*@return array The resultset in array format
	*/
	public function result_array()
	{
		//return the value of the result_array property
		return $this->result_array;

	}

	/**
	*This method sets the resultset in object format
	*
	*@param array $result_array The resultset in array format
	*@return \Object $this object instance
	*/
	public function getResultObject(array $result_array)
	{
		//loop through, converting to objects
		foreach($result_array as $key => $value)
		{
			//check for nested array 
			if(is_array($value))
			{
				//call getResultObject recursively
				$result_array[$key] = $this->getResultObject($value);

			}

			//cast to object and return
			return (object)$result_array;

		} 

	}

	/**
	*This method returns the value of the result object
	*@param null
	*@return \Object The resultset in object notation
	*/
	public function result()
	{
		//return the value of the result_objet
		return $this->getResultObject($this->result_array);

	}

}