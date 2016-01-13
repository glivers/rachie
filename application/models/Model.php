<?php namespace Models;

/**
 * This is the base Model from which all named models derive by extending.
 * @author Geoffrey Bans <geoffreybans@gmail.com>
 * @copyright 2015 - 2020 Geoffrey Bans
 * @category Models
 * @package Models\BaseModel
 * @link https://github.com/gliver-mvc/gliver
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.1
 */

use Drivers\Models\BaseModelClass;

class Model extends BaseModelClass {

	/**
	 * @var string The name of the table associated with this model
	 */
	protected static $table;

	/**
	 * @var bool Set whether query timestamps should be updated
	 */	
	protected static $update_timestamps;

}