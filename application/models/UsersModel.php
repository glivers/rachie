<?php namespace Models;

/**
 * This models handles all user management datatabase operations.
 * @author Geoffrey Bans <geoffreybans@gmail.com>
 * @copyright 2015 - 2020 Geoffrey Bans
 * @category Models
 * @package Models\UsersModel
 * @link https://github.com/gliver-mvc/gliver
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.1
 */

class UsersModel extends Model {

	/**
	 * @var string The name of the table associated with this model
	 */
	protected static $table = 'users';

	/**
	 * @var bool Set whether query timestamps should be updated
	 */	
	protected static $update_timestamps = true;

}