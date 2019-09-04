<?php namespace Drivers\Models;

/**
 *This is the Base Model class that all Model Classes extend. All methods in this class
 *are implemented in a static mannger so no instance of this can be created.
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Drivers
 *@package Drivers\Models\BaseModelClass
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Registry;
use Drivers\Database\MySQL\MySQLTable;
use Drivers\ModelException;

class BaseModelClass {

	/**
	 *This is the constructor class. We make this private to avoid creating instances of
	 *this Register object
	 *
	 *@param null
	 *@return void
	 */
	private function __construct() {}

	/**
	 *This method stops creation of a copy of this object by making it private
	 *
	 *@param null
	 *@return void
	 *
	 */
	private function __clone(){}

	/**
	 *@var object Resource Instance of the database object
	 */
	protected static $connection;

	/**
	 *@var object Instance of the database query object
	 */
	protected static $queryObject;

	/**
	*@var bool Sets whether the query table has been set
	*/
	protected static $queryTableSet = false;

	/**
	*@var bool Stores whether database connection made
	*/
	protected static $dbConnectionMade = false;

	/**
	 *This method returns a query instance
	 *
	 *@param null
	 *@return object Query instance
	 */
	protected static function Query()
	{
		//get the connection resource, if not set yet
        if(static::$connection === null) static::$connection = Registry::get('database');

        //set the query builder instance, if not set
        if(static::$queryObject === null) static::$queryObject = static::$connection->query();

        //set the value of $dbConnectionMade to true, if false
        if(static::$dbConnectionMade === false) static::$dbConnectionMade = true;

        //return the query object
        return static::$queryObject;

	}

	/**
	 * This method sets the table name to perform the query on
	 * @param null
	 * @return $this
	 */
	final private static function setTable(){
		//call method to set table and field names
		static::Query()->setTable(static::$table);
	}

	/**
	 *This method sets the fields upon which to perform database queries.
	 *@param array $fields The names of the fields to select in numeric array
	 *@return object $this
	 */
	final public static function select($fields = array("*"))
	{
		//call method to set table and field names
		static::Query()->setFields(static::$table, $fields);
		
        //return the static class
        return new static;

	}

	/**
	 * This method specifies the LEFT JOIN clause
	 * @param string $table The name of the table to join
	 * @param string $condition The string specifying the table join condition
	 * @param array $fields The array of table columns from the joined table to be selected
	 * @return new static
	 */
	final public static function leftJoin($table, $condition, $fields = array("*")){
		//call the join method of the query object
		static::Query()->leftJoin($table, $condition, $fields);

		//return static class
		return new static;

	}

	/**
	 *This method sets the limit for the number of rows to return.
	 *@param int $limit The maximum number of rows to return per query
	 *@param int $page An interger used to define the offset of the select query
	 *@return object $this
	 */
	final public static function limit($limit, $page = 1)
	{
		//call the limit method of the query builder object
		static::Query()->limit($limit, $page);

		//return static class
		return new static;

	}
	
	/**
	 *This method sets the DISTINCT param in query string to only return non duplicated values in a column.
	 *@param null
	 *@return Object $this
	 */
	final public static function unique()
	{
		//call the unique method of the query builder
		static::Query()->unique();

		//return static class
		return new static;

	}

	/**
	 *This method sets the order in which to sort the query results.
	 *@param string $order The name of the field to use for sorting
	 *@param string $direction This specifies whether sorting should be in ascending or descending order
	 *@return Object $this
	 */
	final public static function order($order, $direction = 'asc')
	{
		//call the order method of the query builder
		static::Query()->order($order, $direction);

		//return static class
		return new static;

	}

	/**
	 *This method defines the where parameters of the query string.
	 *@param mixed Thie method expects an undefined number of arguments
	 *@return Object static class
	 */
	final public static function where()
	{
		//call the query builder object where method passing the argument list
		static::Query()->where(func_get_args());

		//return static class
		return new static;

	}

	/**
	 *This methods inserts/updates one row of data into the database.
	 *@param array The array containing the data to be inserted
	 *@return \MySQLResponseObject
	 */
	final public static function save($data)
	{
		//set the query table
		static::setTable();
		$result = static::$queryObject->save($data, static::$update_timestamps);
		static::$queryObject = null;
		return $result;

	}
	
	/**
	 *The method perform insert/update of large amounts of data.
	 *@param array The data to be inserted/updated in a multidimensional array
	 *@param array The fields into which the data is to be inserted ot updated
	 *@param array For update query, The unique id fields to use for updating
	 *@return \MySQLResponseObject
	 */
	final public static function saveBulk($data, $fields = null, $ids = null, $key = null)
	{
		//set the query table
		static::setTable();
		$result =static::$queryObject->saveBulk($data, $fields, $ids, $key, static::$update_timestamps);
		static::$queryObject = null;
		return $result;

	}

	/**
	 *This method deletes a set of rows that match the query parameters provided.
	 *@param null
	 *@return \MySQLResponseObject
	 */ 
	final public static function delete()
	{
		//set the query table
		static::setTable();
		$result = static::$queryObject->delete();
		static::$queryObject = null;
		return $result;

	}

	/**
	 *This method returns the first row match in a query.
	 *@param null
	 *@return \MySQLResponseObject
	 */
	final public static function first()
	{
		//set the query table
		static::setTable();
		$result = static::$queryObject->first();
		static::$queryObject = null;
		return $result;

	}

	/**
	 *This method counts the number of rows returned by query.
	 *@param null
	 *@return \MySQLResponseObject
	 */
	final public static function count()
	{
		//set the query table
		static::setTable();
		$result = static::$queryObject->count();
		static::$queryObject = null;
		return $result;


	}

	/**
	 *This method returns all rows that match the query parameters.
	 *@param null
	 *@return \MySQLResponseObject
	 */
	final public static function all()
	{
		//set the query table
		static::setTable();
		$result = static::$queryObject->all();
		static::$queryObject = null;
		return $result;

	}

	/**
	 * This method returns rows found based on a match on the 'id' column.
	 * @param int $id The id to use for getting the data
	 * @return MySQLResponseObject
	 */
	final public static function getById($id)
	{
		//call the query builder object where method passing the argument list
		static::Query()->where(array('id = ?', $id));
		//set the query table
		static::setTable();
		$result = static::$queryObject->all();
		static::$queryObject = null;
		return $result;
	}

	/**
	 *This method saves the data provide by the value in the id column.
	 *@param array $data
	 *@return \MySQLReponseObject
	 *@throws \ModelException if id field not found or array empty after removing id field.
	 */
	final public static function saveById($data)
	{
		
		try{	

			if(!isset($data['id'])) throw new ModelException(get_class(new ModelException) ." : The unique ID field for update records was not found in the input array to method updateById()");
						
			$id['id'] = $data['id'];
			$data = array_diff_key($data, $id);

			if(empty($data)) throw new ModelException(get_class(new ModelException) ." : There is no data to update in the query submitted by method updateById() ");
			
			//call the query builder object where method passing the argument list
			static::Query()->where(array('id = ?', $id));
			//set the query table
			static::setTable();
			$result = static::$queryObject->save($data, static::$update_timestamps);
			static::$queryObject = null;
			return $result;
			
		}

		catch (ModelException $e){
			$e->errorShow();
		}	

	}

	/**
	 * This method deletes a database entry based on the unique id.
	 * @param int $id The unique id value to use for deleting
	 * @return \MySQLResponseObject
	 */
	final public static function deleteById($id)
	{
		//call the query builder object where method passing the argument list
		static::Query()->where(array('id = ?', $id));
		//set the query table
		static::setTable();
		$result = static::$queryObject->delete();
		static::$queryObject = null;
		return $result;

	}

	/**
	 *This method returns rows found by matching date created fields.
	 *@param string The date string to use to fetch data
	 *@return \MySQLResponseObject
	 */
	final public static function getByDateCreated($dateCreated)
	{
		//call the query builder object where method passing the argument list
		static::Query()->where(array('date_created = ?', $dateCreated));
		//set the query table
		static::setTable();
		$result = static::$queryObject->all();
		static::$queryObject = null;
		return $result;
		
	}

	/**
	 *This method returns rows found by matching date modified fields.
	 *@param string The date string to use to fetch data
	 *@return \MySQLResponseObject
	 */
	final public static function getByDateModified($dateModified)
	{
		
		//call the query builder object where method passing the argument list
		static::Query()->where(array('date_modified = ?', $dateModified));
		//set query table
		static::setTable();
		$result = static::$queryObject->all();
		static::$queryObject = null;
		return $result;

	}

	/**
	 *This method executes a raw query in the database.
	 *@param string $query_string The query string to execute
	 *@return \MySQLResponseObject
	 *@throws \MySQLException if query returned an error message string
	 */
	final public static function rawQuery($query_string)
	{
		//call the raw method and return response object
		return static::Query()->rawQuery($query_string);

	}

	/**
	*This method creates a new table associated with this model.
	*@param null
	*@return bool true if table create success
	*/
	final public static function createTable()
	{
		//call the method to create a table in the database
		return (new MySQLTable(static::$table, get_called_class(), Registry::get('database')))->createTable();

	}

	/**
	*This model updates a table structure in the database.
	*@param null
	*@return bool true if update structure success
	*/
	final public static function updateTable()
	{
		//call the method to update table structure in the database
		return (new MySQLTable(static::$table, get_called_class(), Registry::get('database')))->updateTable();


	}

}