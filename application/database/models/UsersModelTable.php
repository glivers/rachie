<?php

class UsersModelTable {

	/**
	*@tablename
	*@var
	*/
	public $table = 'users';

	/**
	 *@column
	 *@primary
	 *@type autonumber
	 */
	protected $id;

	/**
	 *@column
	 *@type datetime
	 *@add deleted
	 */
	protected $date_created;

	/**
	 *@column
	 *@type datetime
	 *@index
	 *@update
	 */
	protected $date_modified;

	/**
	 *@var string The query used to generate this model table
	 */
	protected $query_string;

}