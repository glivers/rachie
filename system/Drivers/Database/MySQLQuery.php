<?php namespace Core\Drivers\Database;

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
	protected $where = array();

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
		//chekck if from is empty
		if ( empty($from)) 
		{
			//throw new exception
			throw new DbException("Invalid argument passed to from clause", 1);

		}

		//set the protected $from valus
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
			throw new Exception("Invalid argument $join passed for the Join Clause", 1);

		}

		//throw exception if the $on passed is empty
		if ( empty($on) ) 
		{
			//throw exception
			throw new DbException("Invalid argument $on passed for the Join Clause", 1);

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
			throw new DbException("Empty argument passed for $limit in method limit()", 1);

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
			throw new DBException("Empty value passed for parameter $order in order() method", 1);

		}

		//set the orders property
		$this->orders = $order;

		//set the directions property
		$this->directions = $direction;

	}

}
