<?php namespace Drivers\Database\MySQL;

/**
 *This class writes MySQLi vendor-specific database code
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Database
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Helpers\ArrayHelper\ArrayHelper as ArrayUtility;
use Drivers\Database\MySQL\MySQLResultObject;
use Drivers\Database\MySQL\MySQLException;

class MySQLQuery {

	/**
	 *@var object MySQL connection instance object
	 */
	protected $connector;

	/**
	 *@var string The name of the table on which to perform query
	 */
	protected $froms;

	/**
	 *@var array The array of table fields to select
	 */
	protected $fields;

	/**
	 *@var int The max number of rows to return per query
	 */
	protected $limits;

	/**
	 *@var int The row number from where to start returning rows
	 */
	protected $offset;

	/**
	 *@var string The column name of method to sort the selected data
	 */
	protected $orders;

	/**
	 *@var string Set to DISTINCT to only return unique fields.
	 */
	protected $distinct = ' ';

	/**
	 *@var
	 */
	protected $directions;

	/**
	 *@var string This specifies the joins to be performed on the table
	 */
	protected $joins = array();

	/**
	 *@var string The where parameters to use in performing query
	 */
	protected $wheres = array();

	/**
	*@var object Query Response Object
	*/
	protected $responseObject;

	/**
	 *This stores the database connection instance object
	 *
	 **@param object $instance This is the database connection object instance
	 */
	public function __construct(array $instance)
	{
		//assign the connection object instance to the $this->connnector variable
		$this->connector = $instance['connector'];

		//create the reponse object instance
		$this->responseObject = new MySQLResponseObject();

	}

	/**
	 *This method quotes input data according to how MySQL woudl expect it.
	 *@param mixed String|Array Input to be quoted
	 *@return mixed Output after input is quoted
	 */
	protected function quote($value)
	{
		//if string, quote and return
		if ( is_string($value) || is_int($value) )  
		{
			//call connector method to escape string
			$escaped = $this->connector->escape($value);

			//return escaped string
			return "'{$escaped}'";

		}

		//if input is array, loop through values escaping
		elseif ( is_array($value) ) 
		{
			//define array to contain new quoted values
			$buffer = array();

			//loop through the array one item at a time
			foreach ($value as $i) 
			{
				//quote string and add to the $buffer array container
				array_push($buffer,$this->quote($i));

				//join the array elements to form one string
				$buffer = join(", ", $buffer);

				//quote new string, put in parenthesis and return
				return "({$buffer})";

			}

		}

		//return null if null value was passed
		elseif ( is_null($value) ) 
		{
			//return null as string
			return 'NULL';

		}

		//if boolean, return interger value of boolean
		elseif ( is_bool($value) ) 
		{
			//get interger value and return
			return (int)$value;

		}

		//check if empty value was returned
		elseif ( empty($value) ) 
		{
			//return empty string
			return "' '";

		}

		//if input value does not fall among any of these, escape and return output
		else return $this->connector->escape($value);

	}

	/**
	 * This method sets the table name to be selected
	 * @param string $table The name of the table
	 * @return $this
	 */
	public function setTable($table){

		//put this in try...catch block for better error handling
		try{

			//check if from is empty
			if ( empty($table)) 
			{
				//throw new exception
				throw new MySQLException("Invalid argument passed for table name", 1);

			}

		}

		//diplay error message
		catch(MySQLException $MySQLExceptionObject){

			//display error message
			$MySQLExceptionObject->errorShow();

		}		

		//set the protected $from value
		$this->froms = $table;
		if(!isset($this->fields[$table])) $this->fields[$table] = array("*");
		return $this;
			
	}

	/** 
	 * This method sets the columns for a table to be selected
	 * @param string $table The name of the table
	 * @param array $fields The numeric array of table column names
	 * @return $this
	 */
	public function setFields($table, $fields = array("*")){

		//put this in try...catch block for better error handling
		try{

			//check if from is empty
			if ( empty($table)) 
			{
				//throw new exception
				throw new MySQLException("Invalid argument passed for table name", 1);

			}

		}

		//diplay error message
		catch(MySQLException $MySQLExceptionObject){

			//display error message
			$MySQLExceptionObject->errorShow();

		}		

		//check if fields is sset and set the fields param
		$this->froms = $table;
		$this->fields[$table] = $fields;
	}

	/**
	 *This method builds query string for joining tables in query.
	 *@param string $table the table to perform join on
	 *@param string $condition The conditions for the join
	 *@param array $fields The table column name to join in numeric array
	 *@return object $this
	 *@throws \MySQLException if $table or $condition have empty string values 
	 */
	public function leftJoin($table, $condition, $fields = array("*"))
	{

		//put this in try...catch block for better error handling
		try{

			//throw exception if $table passed is empty
			if ( empty($table) )  
			{
				//throw exception for invalid argument
				throw new MySQLException("Invalid table argument $table passed for the leftJoin Clause", 1);

			}

			//throw exception if the $condition passed is empty
			if ( empty($condition) ) 
			{
				//throw exception
				throw new MySQLException("Invalid argument $condition passed for the leftJoin Clause", 1);

			}
			
		}

		//diplay error message
		catch(MySQLException $MySQLExceptionObject){

			//display error message
			$MySQLExceptionObject->errorShow();

		}

		//add to the fields property
		$this->fields += array($table => $fields);

		//populate the joins property
		$this->joins['tables'][] = $table; //= " LEFT JOIN {$table} ON {$condition} ";
		$this->joins['conditions'][] = $condition;

		//return instance of this object
		return $this;

	}

	/**
	 *This method sets the limit for the number of rows to return.
	 *@param int $limit The maximum number of rows to return per query
	 *@param int $page An interger used to define the offset of the select query
	 *@return object $this
	 *@throws \MySQLException if $limit has an empty value
	 */
	public function limit($limit, $page = 1)
	{

		//put this in try...catch block for better error handling
		try{

			if ( empty($limit) ) 
			{
				//throw exception if value of limit passed is negative
				throw new MySQLException("Empty argument passed for $limit in method limit()", 1);

			}

		}

		//diplay error message
		catch(MySQLException $MySQLExceptionObject){

			//display error message
			$MySQLExceptionObject->errorShow();

		}

		//set the limits property
		$this->limits = $limit;

		//set the offset property
		$this->offset = (int)$limit * ($page - 1);

		//return this object instance
		return $this;

	}

	/**
	 *This method sets the DISTINCT param in query string to only return non duplicated values in a column.
	 *@param null
	 *@return Object $this
	 *@throws This method does not throw an error
	 */
	public function unique()
	{

		//set the value of distinct
		$this->distinct = ' DISTINCT ';

		//return this object instance
		return $this;

	}

	/**
	 *This method sets the order in which to sort the query results.
	 *@param string $order The name of the field to use for sorting
	 *@param string $direction This specifies whether sorting should be in ascending or descending order
	 *@return Object $this
	 *@throws \MySQLException if $order has an empty value
	 */
	public function order($order, $direction = 'asc')
	{

		//put this in try...catch block for better error handling
		try{

			//throw esception if empty value for $order was passed
			if ( empty($order) ) 
			{
				//throw exception
				throw new MySQLException("Empty value passed for parameter $order in order() method", 1);

			}
			
		}

		//diplay error message
		catch(MySQLException $MySQLExceptionObject){

			//display error message
			$MySQLExceptionObject->errorShow();

		}

		//set the orders property
		$this->orders = $order;

		//set the directions property
		$this->directions = $direction;

		return $this;

	}

	/**
	 *This method defines the where parameters of the query string.
	 *@param array $argument The array containing the arguments passed.
	 *@return Object $this
	 *@throws \MySQLException if an uneven number of arguments was passed
	 */
	public function where($arguments)
	{

		//put this in try...catch block for better error handling
		try{

			//throw exception if argument pairs do ot match
			if ( is_float( sizeof($arguments) / 2 ) ) 
			{
				//throw exception
				throw new MySQLException("No arguments passed for the where clause");

			}
			
		}

		//diplay error message
		catch(MySQLException $MySQLExceptionObject){

			//display error message
			$MySQLExceptionObject->errorShow();

		}


		//check if only one argument pair was passed'
		if ( sizeof($arguments) == 2) 
		{
			//get first argument and replace ? with a placeholder
			$arguments[0] = preg_replace("#\?#", "%s", $arguments[0]);

			//quote the argument content
			$arguments[1] = $this->quote($arguments[1]);

			//populate the wheres array
			$this->wheres[] = call_user_func_array("sprintf", $arguments);

			//return this object instance
			return $this;	


		}

		//there are multiple argument supplied
		else
		{
			//get the number of iterations 
			$count = sizeof($arguments) / 2;

			//loop through number of iterations population the wheres array
			for ($i = 0; $i < $count; $i++ ) 
			{ 
				//get the batch of array to work with
				$argumentsPair  = array_splice($arguments, 0, 2);

				//get first argument and replace ? with a placeholder
				$argumentsPair[0] = preg_replace("#\?#", "%s", $argumentsPair[0]);

				//quote the argument content
				$argumentsPair[1] = $this->quote($argumentsPair[1]);
				
				//populate the wheres array
				$this->wheres[] = call_user_func_array("sprintf", $argumentsPair);

			}

			//return this object instance
			return $this;	

		}
		
	}

	/**
	 *This method builds a select query string.
	 *@param null
	 *@return string The select query string formed
	 *@throws This method does not throw an error
	 */
	protected function buildSelect()
	{
		//define a container fields array
		$fields = array();

		//
		$where = $order = $limit = $join ="";

		//
		$template = "SELECT %s %s FROM %s %s %s %s %s";

		//loop through the fields
		foreach ($this->fields as $table => $tableFields) 
		{
			//loop through the fields checking for aliases
			foreach ($tableFields as $field => $alias) 
			{
				//if field is string, return field name
				if ( is_string($field) && $field != 'COUNT(1)')
				{
					//add to fields array
					$fields[] = "{$table}.{$field} AS {$alias}";

				}
				elseif (is_string($field) && $field == 'COUNT(1)') 
				{
					//add to fields array
					$fields[] = "{$field} AS {$alias}";

				}
				//not an array, return alias
				else
				{
					//get alias as field name
					$fields[] = "{$table}.{$alias}";

				}

			}

		}

		//turn the fields into a string
		$fields = join(", ", $fields);

		//get the query joins
		$queryJoin = $this->joins;

		//check if the join parameter is empty
		if ( ! empty($queryJoin) ) 
		{
			$joinTables = "(" . join(", ", $queryJoin['tables']) . ")";
			$joinConditions = "(" . join(" AND ", $queryJoin['conditions']) . ")";
			//add the joins
			$join = " LEFT JOIN $joinTables ON $joinConditions";

		}

		//get the where clause
		$queryWhere = $this->wheres;

		//check if the wheres clause is empty
		if ( ! empty($queryWhere) ) 
		{
			//append where clause
			$joined = join(" AND ", $queryWhere);

			//assign string to $where var
			$where = "WHERE {$joined}";

		}

		//get the order clause
		$queryOrder = $this->orders;

		//check if the orders clause is empty
		if ( ! empty($queryOrder) ) 
		{
			//get the direction if order
			$orderDirection = $this->directions;

			//assign string to order variable
			$order = "ORDER BY {$queryOrder} {$orderDirection}";

		}

		//get the limits clause
		$queryLimit = $this->limits;

		//check if the limits clause is empty
		if ( ! empty($queryLimit) ) 
		{
			//get the offset
			$limitOffset = $this->offset;

			//if offset has been defined
			if ( $limitOffset ) 
			{
				//get the limit string with offset
				$limit = "LIMIT {$limitOffset}, {$queryLimit}";

			}
			//limit offset undefined
			else
			{
				//get the limit string
				$limit = "LIMIT {$queryLimit}";

			}

		}

		//return the formated query string in the format of $template
		return sprintf($template, $this->distinct, $fields, $this->froms, $join, $where, $order, $limit);

	}

	/**
	 *This method builds the query string for inserting one row of records into the database.
	 *@param array The row of data to be inserted in associative array
	 *@param bool $set_timestamps Sets whether to fill the created_at and updated_at fields
	 *@return string The formed insert query string
	 *@throws This method does not throw an error
	 */
	protected function buildInsert($data, $set_timestamps)
	{
		//define a fields container array
		$fields = array(); 

		//define a values container array
		$values = array();

		//define a template format for query
		$template = "INSERT INTO %s (%s) VALUES (%s)";

		//check if the $set_timestamp is true and add created_at fields
		if($set_timestamps) $data['date_created'] = date('Y-m-d h:i:s');

		//loop through the input data 
		foreach ($data as $field => $value) 
		{
			//populate the fields array
			$fields[] = $field;

			//populate the values array
			$values[] = $this->quote($value);

		}

		//convert strings array to string
		$fields = join(", ", $fields);

		//convert values array to string
		$values = join(", ", $values);

		//return the formated query string
		return sprintf($template, $this->froms, $fields, $values);

	}
	/**
	 *This method builds the insert query for more than one row of data.
	 *@param array The data to be inserted in associative array
	 *@param bool $set_timestamps Sets whether to fill the created_at and updated_at fields
	 *@return string The formed query string for this insert operation
	 *@throws This method does not throw an error
	 */
	protected function buildBulkInsert($data, $set_timestamps)
	{
		//define a fields container array
		$fields = array(); 

		//define a values container array
		$values = array();

		//define a template format for query
		$template = "INSERT INTO %s (%s) VALUES %s";

		//get the fields array
		$fieldsArray = $data[0];

		//get the fields
		foreach ($fieldsArray as $field => $value) 
		{
			//populate the fields array
			$fields[] = $field;

		}
		//get the count of number of rows
		$count = sizeof($data);

		//loop through the input data composing input data
		for ( $i = 0; $i < $count; $i++ ) 
		{ 
			//remove the first array and return
			$array = $data[$i];

			//define array to contain the string components
			$valuesArray = array();

			//loop through the array composing the values
			foreach ($array as $field => $value) 
			{
				//populate the values array
				$valuesArray[] = $this->quote($value);

			}

			//convert strings array to string
			$values[] = '(' . join(", ", $valuesArray) . ')';

		}

		//convert strings array to string
		$fields = join(", ", $fields);

		//convert values array to string
		$values = join(", ", $values);

		//return the formated query string
		return sprintf($template, $this->froms, $fields, $values);

	}

	/**
	 *This method builds an update query for a single record of data.
	 *@param array The data to be updated into the database
	 *@param bool $set_timestamps Sets whether to fill the created_at and updated_at fields
	 *@return string The formed SQL query string for this update operation
	 *@throws This method does not throw an error
	 */
	protected function buildUpdate($data, $set_timestamps)
	{
		//define the parts container array
		$parts = array();

		//define the container vars initializing to empty
		$where = $limit = '';

		//define the template format string
		$template = "UPDATE %s SET %s %s %s";

		//check if the $set_timestamp is true and add update_at fields
		if($set_timestamps) $data['date_modified'] = date('Y-m-d h:i:s');

		//loop through the input data array, populating the parts array
		foreach ($data as $field => $value) 
		{
			//populate the parts array
			$parts[] = "{$field}=" . $this->quote($value);

		}

		//convert parts array into string
		$parts = join(", ", $parts);

		//get the where clause
		$queryWhere = $this->wheres;

		//check if where clause is empty
		if ( ! empty($queryWhere) ) 
		{
			//convert where clause array to string
			$joined = join(" AND ", $queryWhere);

			//set the where var valus
			$where = "WHERE {$joined}";

		}

		//get the limits var value
		$queryLimit = $this->limits;

		//check if the limit clause is empty
		if ( ! empty($queryLimit) ) 
		{
			//get the offset value
			$limitOffset = $this->offset;

			//compose limit string
			$limit = "LIMIT {$queryLimit} {$limitOffset}";

		}

		//return the formated query string
		return sprintf($template, $this->froms, $parts, $where, $limit);

	}
	/**
	 *This method builds the SQL query string for updating large amounts of data.
	 *@param array The data to be updated in multidimensional array
	 *@param array The field names to be updated in a numeric array
	 *@param array The unique field ids to use for updating in numberic array
	 *@return string The formed SQL update query string
	 *@throws This method does not throw an error
	 */
	protected function buildBulkUpdate($data, $fields, $ids, $key)
	{

		//define the parts container array
		$parts = array();

		//define the template format string
		$template = "UPDATE %s SET %s WHERE %s IN (%s) ";

		//loop through the fields array composing the array
		foreach ($fields as $index => $field ) 
		{
			//initialize the subparts variable
			$subparts = $field . ' = (CASE ' . $key . ' ';

			//loop through the data array composing parts
			foreach ($data as $id => $info ) 
			{
	            //check if array is not empty 
	            if ( ! empty($info) ) 
	            {

					$subparts .=  ' WHEN '. $id . ' THEN ' . $this->quote($info[$field]) . ' ';

	            }

			}

			//finish the subpart
			$subparts .= ' END) ';

			//add this to the main parts array
			$parts[] = $subparts;

		}

		//convert parts array into string
		$parts = join(", ", $parts);

		//get the where clause
		$queryWhere = $ids;

		//check if where clause is empty
		if ( ! empty($queryWhere) ) 
		{
			//convert where clause array to string
			$where = join(", ", $queryWhere);

		}

		//return the formated query string
		return sprintf($template, $this->froms, $parts, $key, $where);

	}

	/**
	 *This method builds the SQL query string to perform a delete query.
	 *@param null
	 *@return string The SQL query string for delete operation.
	 *@throws This method does not throw an error
	 */
	protected function buildDelete()
	{
		//initialize $where and $limit container vars to empty strings
		$where = $limit = '';

		//define the string formating template
		$template = "DELETE FROM %s %s %s";

		//get the where clause variable
		$queryWhere = $this->wheres;

		//check if where clause is empty
		if ( ! empty($queryWhere) ) 
		{
			//convert where clause array to string
			$joined = join(" AND ", $queryWhere);

			//compose the where string
			$where = "WHERE {$joined}";

		}

		//get the limit clause variable
		$queryLimit = $this->limits;

		//check if limit clause is empty
		if ( ! empty($queryLimit) ) 
		{
			//get the offset value
			$limitOffset = $this->offset;

			//compose the limit clause string
			$limit = "LIMIT {$queryLimit} {$limitOffset}";

		}

		//return the formated query string
		return sprintf($template, $this->froms, $where, $limit);

	}

	/**
	 *This methods inserts/updates one row of data into the database.
	 *@param array The array containing the data to be inserted
	 *@param bool $set_timestamps Sets whether to fill the created_at and updated_at fields
	 *@return \MySQLResponseObject
	 *@throws \MySQLException if there was an error in query execution
	 */
	public function save($data, $set_timestamps)
	{
		//get the size of the wheres parameter
		$doInsert = sizeof($this->wheres) == 0;

		//check if doInsert is true, //get insert query string
		if ( $doInsert ) $sql = $this->buildInsert($data, $set_timestamps);

		//not insert, this should be an update //get update query string
		else $sql = $this->buildUpdate($data, $set_timestamps);

		//set the value of the query string
		$this->responseObject
			->setQueryString($sql);

		//start timer
		$query_start_time = microtime(true);

		//excecute query
		$result = $this->connector->execute($sql);

		$query_stop_time = microtime(true);
		$query_excec_time = $query_stop_time - $query_start_time;

		$this->responseObject
			->setQueryTime($query_excec_time);

		//put this in try...catch block for better error handling
		try{

			//check if query execution failure
			if ( $result === false) 
			{
				//throw exception 
				throw new MySQLException(get_class(new MySQLException) . ' ' .$this->connector->lastError() . '<span class="query-string"> (' . $sql . ') </span>');

			}


		}

		//diplay error message
		catch(MySQLException $MySQLExceptionObject){

			//display error message
			$MySQLExceptionObject->errorShow();

		}

		//if this was an insert, get the insert id
		if( $doInsert )
		{
			$this->responseObject
				->setLastInsertId($this->connector->lastInsertId());

			return $this->responseObject;
		}

		else{

			$this->responseObject
				->setUpdateSuccess(true)
				->setAffectedRows($this->connector->affectedRows());

			return $this->responseObject;
		}

	}

	/**
	 *The method perform insert/update of large amounts of data.
	 *@param array The data to be inserted/updated in a multidimensional array
	 *@param array The fields into which the data is to be inserted ot updated
	 *@param array For update query, The unique id fields to use for updating
	 *@param bool $set_timestamps Sets whether to fill the created_at and updated_at fields
	 *@return \MySQLResponseObject
	 *@throws \MySQLException if query execution return an error message
	 */
	public function saveBulk($data, $fields = null , $ids = null, $key = null, $set_timestamps )
	{
		//get the size of the wheres parameter
		$doInsert = sizeof($this->wheres) == 0;

		//check if doInsert is true //get insert query string
		if ( $doInsert ) $sql = $this->buildBulkInsert($data, $set_timestamps);

		//not insert, this should be an update, //get update query string
		else $sql = $this->buildBulkUpdate($data, $fields, $ids, $key, $set_timestamps);

		//set the value of the query string
		$this->responseObject
			->setQueryString($sql);

		$query_start_time = microtime(true);

		//excecute query
		$result = $this->connector->execute($sql);

		$query_stop_time = microtime(true);
		$query_excec_time = $query_stop_time - $query_start_time;
		$this->setQueryTime($query_excec_time);

		//put this in try...catch block for better error handling
		try{

			//check if query execution failure
			if ( $result === false) 
			{
				//throw exception 
				throw new MySQLException(get_class(new MySQLException) . ' ' .$this->connector->lastError() . '<span class="query-string"> (' . $sql . ') </span>');
	 
			}

		}

		//diplay error message
		catch(MySQLException $MySQLExceptionObject){

			//display error message
			$MySQLExceptionObject->errorShow();

		}


		//if this was an insert, get the insert id
		if( $doInsert )
		{
			//set the insert id
			$this->responseObject
				->setLastInsertId($this->connector->lastInsertId());

			return $this->responseObject;
		}

		else 
		{
			$this->responseObject
				->setUpdateSuccess(true)
				->setAffectedRows($this->connector->affectedRows());

			return $this->responseObject;

		}

	}

	/**
	 *This method deletes a set of rows that match the query parameters provided.
	 *@param null
	 *@return \MySQLResponseObject
	 *@throws \MySQLException 
	 */
	public function delete()
	{
		//build the delete query string
		$sql = $this->buildDelete();

		//set the value of the query_string
		$this->responseObject->setQueryString($sql);

		//time query execution
		$query_start_time = microtime(true);

		//execute the query string
		$result = $this->connector->execute($sql);

		$query_stop_time = microtime(true);
		$query_excec_time = $query_stop_time - $query_start_time;
		$this->responseObject->setQueryTime($query_excec_time);

		//put this in try...catch block for better error handling
		try{

			//throw error if there was an error perfrorming this query
			if ( $result === false ) 
			{
				//throw excepton
				throw new MySQLException(get_class(new MySQLException) . ' ' .$this->connector->lastError() . '<span class="query-string"> (' . $sql . ') </span>');
				
			}
			
		}

		//diplay error message
		catch(MySQLException $MySQLExceptionObject){

			//display error message
			$MySQLExceptionObject->errorShow();

		}


		//if delete was successfull, get and return the number of affected rows
		$this->responseObject
			->setAffectedRows($this->connector->affectedRows());

		return $this->responseObject;

	}

	/**
	 *This method returns the first row match in a query.
	 *@param null
	 *@return \MySQLResponseObject
	 *@throws This method does not throw an error
	 */
	public function first()
	{
		//get the limit clause value
		$limit = $this->limits;

		//get the offset value
		$limitOffset = $this->offset;

		//set the limit to 1
		$this->limit(1);

		//get all
		$all = $this->all();

		//get the first
		$first = ArrayUtility::first($all->result_array())->get();

		//if limit is defined
		if ( $limit ) 
		{
			//set the limit property
			$this->limits = $limit;

		}

		//if limit offset is set
		if ( $limitOffset ) 
		{
			//set the offset
			$this->offset = $limitOffset;

		}

		//get the response object instance
		$this->responseObject
			->setResultArray($first);

		//return the full response object
		return $this->responseObject;

	}

	/**
	 *This method counts the number of rows returned by query.
	 *@param null
	 *@return \MySQLResponseObject
	 *@throws This method does not throw an error
	 */
	public function count()
	{
		//get the limit clause value
		$limit = $this->limits;

		//get the offset value
		$limitOffset = $this->offset;

		//get the fields clause
		$fields = $this->fields;

		//compose the fields array
		$this->fields = array($this->froms => array("COUNT(1)" => "rows"));

		//set limit to 1
		$this->limit(1);

		//get the first row
		$row = $this->first()->result_array();

		//set the value of the fields
		$this->fields = $fields;

		//check if fields is defined
		if ( $fields ) 
		{
			//set the value o fields
			$this->fields = $fields;

		}

		//check if the limit value is set
		if ( $limit ) 
		{
			//set the value of limit
			$this->limits = $limit;

		}

		//check if the limit offset value is defined
		if ( $limitOffset ) 
		{
			//set the offset value
			$this->offset = $limitOffset;

		}

		//get the response object instance
		$this->responseObject 
			->setNumRows($row[0]['rows'])
			->setResultArray($row);

		//return the full response object
		return $this->responseObject;

	}

	/**
	 *This method returns all rows that match the query parameters.
	 *@param null
	 *@return \MySQLResponseObject
	 *@throws \MySQLException if query returned an error message string
	 */
	public function all()
	{
		//build the select query
		$sql = $this->buildSelect();

		//set the value of the query string
		$this->query_string = $sql;

		//set the query excecution start time
		$query_start_time = microtime(true);

		//execute the sql query
		$result = $this->connector->execute($sql);

		//set the query excecution end time
		$query_stop_time = microtime(true);

		//get the query_excecution
		$query_excec_time = $query_stop_time - $query_start_time;


		//put this in try...catch block for better error handling
		try{

			//check if the query return an error and throw exception
			if ( $result === false ) 
			{
				//get the erro message
				$error = $this->connector->lastError();

				//throw exception
				throw new MySQLException(get_class(new MySQLException) . ' ' .$this->connector->lastError() . '<span class="query-string"> (' . $sql . ') </span>');

			}
			
		}

		//diplay error message
		catch(MySQLException $MySQLExceptionObject){

			//display error message
			$MySQLExceptionObject->errorShow();

		}


		//define container rows() array
		$result_array = array();

		//loop through resultset setting the values of arrays and objects
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {

			$result_array[] = $row;

		}

		//get the response object instance
		$this->responseObject
			->setQueryString($this->query_string)
			->setQueryTime($query_excec_time)
			->setFieldCount($result->field_count)
			->setNumRows($result->num_rows)
			->setQueryFields($result->fetch_fields())
			->setResultArray($result_array);

		//return the full response object
		return $this->responseObject;
	}

	/**
	 *This method executes a raw query in the database.
	 *@param string $query_string The query string to execute
	 *@return \MySQLResponseObject
	 *@throws \MySQLException if query returned an error message string
	 */
	public function rawQuery($query_string)
	{
		try{
			//execute the sql query and return response object
		 	$result = $this->connector->execute($query_string);

			//check if the query return an error and throw exception
			if ( $result === false ) 
			{
				//throw exception
				throw new MySQLException(get_class(new MySQLException) . ' ' .$this->connector->lastError() . '<span class="query-string"> (' . $query_string . ') </span>');

			}

			else return $result;

		}

		catch(MySQLException $MySQLExceptionObject){

			$MySQLExceptionObject->errorShow();
		}

	}
	
}