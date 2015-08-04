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

use Drivers\Utilities\ArrayUtility;

class MySQLQuery {

	/**
	 *@var object MySQL connection instance object
	 *@readwrite
	 */
	protected $connector;

	/**
	 *@var 
	 *@read
	 */
	protected $froms;

	/**
	 *@var
	 *@read
	 */
	protected $fields;

	/**
	 *@var 
	 *@read
	 */
	protected $limits;

	/**
	 *@var 
	 *@read
	 */
	protected $offset;

	/**
	 *@var
	 *@read
	 */
	protected $orders;

	/**
	 *@var
	 *@read
	 */
	protected $distinct = ' ';

	/**
	 *@var
	 *@read
	 */
	protected $directions;

	/**
	 *@var 
	 *@read
	 */
	protected $joins = array();

	/**
	 *@var
	 *@read
	 */
	protected $wheres = array();

	/**
	 *This stores the database connection instance object
	 *
	 **@param object $instance This is the database connection object instance
	 */
	public function __construct(array $instance)
	{
		//assign the connection object instance to the $this->connnector variable
		$this->connector = $instance['connector'];

	}

	/**
	 *This method quotes input data according to how MySQL woudl expect it
	 *
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
	 *
	 *
	 */
	public function from($from, $fields = array("*"))
	{
		//check if from is empty
		if ( empty($from)) 
		{
			//throw new exception
			throw new MySQLException("Invalid argument passed to from clause", 1);

		}

		//set the protected $from value
		$this->froms = $from;

		//check if fields is sset
		if($fields)
		{
			//set the fields param
			$this->fields[$from] = $fields;

		}

		//return this object instance
		return $this;

	}

	/**
	 *
	 *
	 */
	public function join($join, $table, $on, $fields = array())
	{
		//throw exception if $join passed is empty
		if ( empty($join) ||  empty($table) || empty($on) )  
		{
			//throw exception for invalid argument
			throw new MySQLException("Invalid argument $join passed for the Join Clause", 1);

		}

		//throw exception if the $on passed is empty
		if ( empty($on) ) 
		{
			//throw exception
			throw new MySQLException("Invalid argument $on passed for the Join Clause", 1);

		}

		//add to the fields property
		$this->fields += array($table => $fields);

		//populate the joins property
		$this->joins[] = strtoupper($join) . " JOIN {$table} ON {$on}";

		//return instance of this object
		return $this;

	}

	/**
	 *
	 *
	 *
	 */
	public function limit($limit, $page = 1)
	{
		if ( empty($limit) ) 
		{
			//throw exception if value of limit passed is negative
			throw new MySQLException("Empty argument passed for $limit in method limit()", 1);

		}

		//set the limits property
		$this->limits = $limit;

		//set the offset property
		$this->offset = (int)$limit * ($page - 1);

		//return this object instance
		return $this;

	}
	/**
	 *
	 *
	 *
	 */
	public function unique()
	{

		//set the value of distinct
		$this->distinct = ' DISTINCT ';

		//return this object instance
		return $this;

	}

	/**
	 *
	 *
	 */
	public function order($order, $direction = 'asc')
	{
		//throw esception if empty value for $order was passed
		if ( empty($order) ) 
		{
			//throw exception
			throw new MySQLException("Empty value passed for parameter $order in order() method", 1);

		}

		//set the orders property
		$this->orders = $order;

		//set the directions property
		$this->directions = $direction;

		return $this;

	}

	/**
	 *This method defines the where parameters of the query string
	 *
	 *@param null
	 *@return object Sets the where parameter and returns this instance of the query object
	 */
	public function where()
	{
		//get the arguments
		$arguments = func_get_args();

		//throw exception if argument pairs do ot match
		if ( is_float( sizeof($arguments) / 2 ) ) 
		{
			//throw exception
			throw new MySQLException("No arguments passed for the where clause");

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
	 *This method builds a select query string 
	 *
	 *@param null
	 *@return string The select query string formed
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
			//add the joins
			$join = join(" ", $queryJoin);

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
	 *This method builds the query string for inserting one row of records into the database
	 *
	 *@param array The row of data to be inserted in associative array
	 *@return string The formed insert query string
	 */
	protected function buildInsert($data)
	{
		//define a fields container array
		$fields = array(); 

		//define a values container array
		$values = array();

		//define a template format for query
		$template = "INSERT INTO %s (%s) VALUES (%s)";

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
	 *This method builds the insert query for more than one row of data
	 *
	 *@param array The data to be inserted in associative array
	 *@return string The formed query string for this insert operation
	 */
	protected function buildBulkInsert($data)
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
	 *This method builds an update query for a single record of data
	 *
	 *@param array The data to be updated into the database
	 *@return string The forrmed SQL query string for this update operation
	 */
	protected function buildUpdate($data)
	{
		//define the parts container array
		$parts = array();

		//define the continer vars initializing to empty
		$where = $limit = '';

		//define the template format string
		$template = "UPDATE %s SET %s %s %s";

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
			$joined = join(", ", $queryWhere);

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
	 *This method builds the SQL query string for updating large amounts of data
	 *
	 *
	 *@param array The data to be updated in multidimensional array
	 *@param array The field names to be updated in a numeric array
	 *@param array The unique field ids to use for updating in numberic array
	 *@return string The formed SQL update query string
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
					//echo "<pre>";print_r($info);exit();
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
	 *This method builds the SQL query string to perform a delete query
	 *
	 *@param null
	 *@return string The SQL query string for delete operation.
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
			$joined = join(", ", $queryWhere);

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
	 *This methods inserts/updates one row of data into the database
	 *
	 *@param array The array containing the data to be inserted
	 *@return bool true Returns true if the query execution was successful
	 */
	public function save($data)
	{
		//get the size of the wheres parameter
		$doInsert = sizeof($this->wheres) == 0;

		//check if doInsert is true
		if ( $doInsert ) 
		{
			//get insert query string
			$sql = $this->buildInsert($data);

		}

		//not insert, this should be an update
		else
		{
			//get update query string
			$sql = $this->buildUpdate($data);

		}
//echo $sql . '<pre>';print_r($data); exit();
		//excecute query
		$result = $this->connector->execute($sql);

		//check if query execution failure
		if ( $result === false) 
		{
			//throw exception 
			throw new MySQLException($this->connector->lastError());

		}

		//if this was an insert, get the insert id
		if( $doInsert )
		{
			//get last insert id and retunr
			return $this->connector->lastInsertId();
		}

		//if this was update and sucess, return 0 to show operation successful
		return true;

	}
	/**
	 *The method perform insert/update of large amounts of data
	 *
	 *@param array The data to be inserted/updated in a multidimensional array
	 *@param array The fields into which the data is to be inerted ot updated
	 *@param array For update query, The unique id fields to use for updating
	 *@return boolean true Return 0 when query execution is success to indicate true
	 */
	public function saveBulk($data, $fields = null , $ids = null, $key = null )
	{
		//get the size of the wheres parameter
		$doInsert = sizeof($this->wheres) == 0;

		//check if doInsert is true
		if ( $doInsert ) 
		{
			//get insert query string
			$sql = $this->buildBulkInsert($data);

		}

		//not insert, this should be an update
		else
		{
			//get update query string
			$sql = $this->buildBulkUpdate($data, $fields, $ids, $key);
		}

		//excecute query
		$result = $this->connector->execute($sql);

		//check if query execution failure
		if ( $result === false) 
		{
			//throw exception 
			throw new MySQLException($this->connector->lastError());
 
		}

		//if this was an insert, get the insert id
		if( $doInsert )
		{
			//get last insert id and retunr
			return $this->connector->lastInsertId();
		}

		//if this was update and sucess, return 0 to show operation successful
		return 0;

	}

	/**
	 *This method deletes a set of rows that match the query parameters provided
	 *
	 *@param null
	 *@return array The affected rows by this delete action in an array
	 */
	public function delete()
	{
		//build the delete query string
		$sql = $this->buildDelete();

		//execute the query string
		$result = $this->connector->execute($sql);

		//throw error if there was an error perfrorming this query
		if ( $result === false ) 
		{
			//throw excepton
			throw new MySQLException();
			
		}

		//if delete was successfull, get and return the number of affected rows
		return $this->connector->affectedRows();

	}

	/**
	 *This method returns the first row match in a query
	 *
	 *@param null
	 *@return array The first element found in an array
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
		$first = ArrayUtility::first($all);

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

		//return the first query row
		return $first;

	}

	/**
	 *This me`thod counts the number of rows returned per query
	 *
	 *
	 *@param null
	 *@return int The count of the number of rows
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
		$row = $this->first();

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

		//return the rows count
		return $row[0]['rows'];

	}

	/**
	 *This method returns all rows that match the query parameters
	 *
	 *@param null
	 *@return array The query results in array format
	 */
	public function all()
	{
		//build the select query
		$sql = $this->buildSelect();

		//execute the sql query
		$result = $this->connector->execute($sql);

		//check if the query return an error and throw exception
		if ( $result === false ) 
		{
			//get the erro message
			$error = $this->connector->lastError();

			//throw exception
			throw new MySQLException("There was an error with your SQL Query : {$error}");

		}

		//define container rows() array
		$rows = array();

		//loop through the result object composing the rows array
		for( $i = 0; $i < $result->num_rows; $i++ )
		{
			//add to the rows array
			$rows[] = $result->fetch_array(MYSQLI_ASSOC);

		}

		//return the full rows array
		return $rows;
	}

}
