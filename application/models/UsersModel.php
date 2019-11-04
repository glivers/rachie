<?php namespace Models;

/**
 * This models handles all user management datatabase operations.
 * @author Geoffrey Okongo <code@gliver.org>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Models
 * @package Models\UsersModel
 * @link https://github.com/gliverphp/gliver
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
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