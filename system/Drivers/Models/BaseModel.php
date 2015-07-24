<?php namespace Drivers\Models;

/**
 *This is the Base Model class that all Model Classes extend
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
use Drivers\Inspector;
use Drivers\Database\BaseTrait;
use Drivers\Utilities\StringUtility;


class BaseModel {

	use BaseTrait;

	/**
	 *@var string Table name
	 *@readwrite
	 */
	protected $table;

	/**
	 *@var object Database Connector Object Instance
	 *@readwrite
	 */
	protected $connector;

	/**
	 *@var array Of the datatype
	 *@write
	 */
	protected $types = array(
		'autonumber',
		'text',
		'integer',
		'decimal',
		'boolean',
		'datetime'
	);

	/**
	 *@var 
	 *
	 */
	protected $columns;

	/**
	 *@var 
	 *
	 */
	protected $primary;

	public function load()
	{
		
		$primary = $this->primaryColumn;

		$raw = $primary["raw"];

		$name = $primary["name"];

		if ( ! empty($this->$raw) )
		{

			$previous = $this->connector->query()->from($this->table)->where("{$name} = ?", $this->$raw)->first();

		}

		if ($previous == null)
		{

			throw new Exception\Primary("Primary key value invalid");
		}

		foreach ($previous as $key => $value)
		{
			
			$prop = "{$key}";

			if ( ! empty($previous->$key) && ! isset($this->$prop))
			{
				
				$this->$key = $previous->$key;
			}

		}

	}

	public function save()
	{
		
		$primary = $this->primaryColumn;

		$raw = $primary["raw"];

		$name = $primary["name"];

		$query = $this->connector->query()->from($this->table);

		if ( ! empty($this->$raw) )
		{
			
			$query->where("{$name} = ?", $this->$raw);
		}

		$data = array();

		foreach ($this->columns as $key => $column)
		{
			
			if (!$column["read"])
			{

				$prop = $column["raw"];

				$data[$key] = $this->$prop;

				continue;
			}

			if ($column != $this->primaryColumn && $column)
			{
				
				$method = "get".ucfirst($key);

				$data[$key] = $this->$method();

				continue;
			}

		}

		$result = $query->save($data);

		if ($result > 0)
		{
			
			$this->$raw = $result;
		}

		return $result;

	}

	public function delete()
	{
		
		$primary = $this->primaryColumn;

		$raw = $primary["raw"];

		$name = $primary["name"];

		if ( ! empty($this->$raw) )
		{
			
			return $this->connector->query()->from($this->table)->where("{$name} = ?", $this->$raw)->delete();
		}

	}


	public static function deleteAll($where = array())
	{
		
		$instance = new static();

		$query = $instance->connector->query()->from($instance->table);

		foreach ($where as $clause => $value)
		{
			
			$query->where($clause, $value);
		}

		return $query->delete();
	
	}

	public static function all($where = array(), $fields = array("*"),$order = null, $direction = null, $limit = null, $page = null)
	{
		
		$model = new static();

		return $model->_all($where, $fields, $order, $direction, $limit, $page);

	}

	protected function _all($where = array(), $fields = array("*"),	$order = null, $direction = null, $limit = null, $page = null)
	{
		
		$query = $this->connector->query()->from($this->table, $fields);

		foreach ($where as $clause => $value)
		{
			
			$query->where($clause, $value);

		}

		if ($order != null)
		{
			
			$query->order($order, $direction);
		}

		if ($limit != null)
		{
			
			$query->limit($limit, $page);
		}

		$rows = array();

		$class = get_class($this);

		foreach ($query->all() as $row)
		{
			$rows[] = new $class( $row );
		}

		return $rows;

	}

	public static function first($where = array(), $fields = array("*"), $order = null, $direction = null)
	{
		
		$model = new static();

		return $model->_first($where, $fields, $order, $direction);

	}

	protected function _first($where = array(), $fields = array("*"), $order = null, $direction = null)
	{
		
		$query = $this->connector->query()->from($this->table, $fields);

		foreach ($where as $clause => $value)
		{
			
			$query->where($clause, $value);

		}

		if ($order != null)
		{
			
			$query->order($order, $direction);
		}

		$first = $query->first();

		$class = get_class($this);

		if ($first)
		{
			
			return new $class( $query->first() );
		}

		return null;
	
	}

	public static function count( $where = array() )
	{
		
		$model = new static();

		return $model->_count($where);

	}

	protected function _count($where = array())
	{
		
		$query = $this->connector->query()->from($this->table);

		foreach ($where as $clause => $value)
		{
			
			$query->where($clause, $value);

		}

		return $query->count();
	
	}

}
