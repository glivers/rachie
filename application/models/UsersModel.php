<?php namespace Models;

/**
 *This models handles all user management datatabase operations
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Models
 *@package Models\UsersModel
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class UsersModel extends Model{

	/**
	*@var string The name of the table associated with this model
	*/
	protected $table = 'users';	

	/**
	 *This method gets the records of all users from the database
	 *
	 *@param null
	 *@return array The users data in an array format
	 */
	public static function all()
	{
		//excecute query to return all users
		$users = static::Query()->from(self::$table)->all();

		//return the rows found
		return $users;

	}

}