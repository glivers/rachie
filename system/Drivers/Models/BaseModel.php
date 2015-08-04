<?php namespace Drivers\Models;

/**
 *This is the Base Model class that all Model Classes extend. All methods in this class
 *are implemented in a static mannger so no instance of this can be created.
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Models
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Registry;

class BaseModel {

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
	 *This method returns a query instance
	 *
	 *@param null
	 *@return object Query instance
	 */
	public static function Query()
	{

        static::$connection = Registry::get('database');

        static::$queryObject = static::$connection->query();

        return static::$queryObject;

	}

	public static function from($from, $fields = array("*"))
	{
		//call the from method of this query instance
		static::$queryObject->from($from, $fields);

	}

	public static function join($join, $table, $on, $fields = array() )
	{
		//call the join method of the query object
		static::$queryObject->join($join, $table, $on, $fields);

	}

	public static function limit($limit, $page = 1)
	{
		//call the limit method of the query builder object
		static::$queryObject->limit($limit, $page);

	}

	public static function unique()
	{
		//call the unique method of the query builder
		static::$queryObject->unique();

	}

	public static function order($order, $direction = 'asc')
	{
		//call the order method of the query builder
		static::$queryObject->order($order, $direction);

	}

	public static function where()
	{
		//call the query builder object where method passing the argument list
		static::$queryObject->where();

	}

	public static function save($data)
	{
		//call the query builder save method
		static::$queryObject->save($data);

	}

	public static function saveBulk($data, $fields = null, $ids = null, $key = null)
	{
		//call the query builder save bulk method
		static::$queryObject->saveBulk($data, $fields, $ids, $key);

	}
 
	public static function delete()
	{
		//call the query builder delete method
		static::$queryObject->delete();

	}

	public static function first()
	{
		//call the query builder get first record method
		static::$queryObject->first();
	}

	public static function count()
	{
		//call the query builder object count method
		static::$queryObject->count();

	}

	public static function all()
	{
		//call the query builder get all method
		static::$queryObject->all();

	}

}