<?php namespace Models;

/**
 *This models updates user information in the database
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Cron\Models
 *@package Cron\Models\Sample
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Models\Model;

class SampleCronModel extends Model{

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
	public static function activateAllUsers()
	{
		//excecute query to set all users to activated status
		$usersActivate = static::Query()->from(self::$table)->where('status = ?', 'inactive')->save(array('status' => 'active'));

		//return the status of this operation
		return $usersActivate;

	}

}