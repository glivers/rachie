<?php namespace Core\Drivers\Database\MySQL;

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

use Core\Drivers\Utilities\ArrayUtility;

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
		if ( is_string($value) )  
		{
			//call connector method to escape string
			$escaped = $this->connector->escape($value);

			//return escaped string
			return "'{$escaped}'";

		}

		//if input is array, loop through values escaping
		if ( is_array($value) ) 
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

			//return null if null value was passed
			if ( is_null($value) ) 
			{
				//return null as string
				return 'NULL';

			}

			//if boolean, return interger value of boolean
			if ( is_bool($value) ) 
			{
				//get interger value and return
				return (int)$value;

			}

			//if input value does not fall among any of these, escape and return output
			return $this->connector->escape($value);

		}

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
	public function join($join, $on, $fields = array())
	{
		//throw exception if $join passed is empty
		if ( empty($join) )  
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
		$this->fields += array($join => $fields);

		//populate the joins property
		$this->joins[] = "JOIN {$join} ON {$on}";

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
		$this->offset = $limit * ($page - 1);

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
	 *
	 *
	 *
	 */
	public function where()
	{
		//get the arguments
		$arguments = func_get_args();

		//throw exception if no arguments passed for the where clause
		if ( sizeof($arguments) < 1 ) 
		{
			//throw exception
			throw new MySQLException("No arguments passed for the where clause");

		}

		//
		$arguments[0] = preg_replace("#\?#", "%s", $arguments[0]);

		//loop through the arguments array
		foreach (array_slice($arguments, 1, null, true) as $i => $parameter) 
		{
			//quote the argument contect
			$arguments[$i] = $this->quote($arguments[$i]);

		}

		//populate the wheres array
		$this->wheres[] = call_user_func_array("sprintf", $arguments);

		//return this object instance
		return $this;	

	}

	/**
	 *
	 *
	 *
	 */
	protected function buildSelect()
	{
		//define a container fields array
		$fields = array();

		//
		$where = $order = $limit = $join ="";

		//
		$template = "SELECT %s FROM %s %s %s %s %s";

		//loop through the fields
		foreach ($this->fields as $table => $tableFields) 
		{
			//loop through the fields checking for aliases
			foreach ($tableFields as $field => $alias) 
			{
				//if field is string, return field name
				if ( is_string($field) )
				{
					//add to fields array
					$fields[] = "{$field} AS {$alias}";

				}
				//not an array, return alias
				else
				{
					//get alias as field name
					$fields[] = $alias;

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
				$limit = "LIMIT {$queryLimit}, {$limitOffset}";

			}
			//limit offset undefined
			else
			{
				//get the limit string
				$limit = "LIMIT {$queryLimit}";

			}

		}

		//return the formated query string in the format of $template
		return sprintf($template, $fields, $this->froms, $join, $where, $order, $limit);

	}

	/**
	 *
	 *
	 *
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
	 *
	 *
	 *
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
	 *
	 *
	 *
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
	 *
	 *
	 *
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
	 *
	 *
	 *
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
	 *
	 *
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
	 *
	 *
	 *
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
	 *
	 *
	 *
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
