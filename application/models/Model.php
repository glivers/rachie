<?php namespace Models;

/**
 * This is the base Model from which all named models derive by extending.
 * @author Geoffrey Okongo <code@gliver.org>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Models
 * @package Models\BaseModel
 * @link https://github.com/gliverphp/gliver
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
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