<?php namespace Controllers;

/**
 * This is the base controller from which all named controllers derive by extending.
 * @author Geoffrey Barnes <geoffreybans@gmail.com>
 * @copyright 2015 - 2030 Geoffrey Barnes
 * @category Controllers
 * @package Controllers\BaseController
 * @link https://github.com/gliverphp/gliver
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.1
 */

use Gliver\Controllers\BaseControllerTrait;

class BaseController {

	//call the trait with the setter/getter methods
	use BaseControllerTrait;

	/**
	 * @var bool Set to true to enable method filters in this controller
	 */
	public $enable_method_filters = false;

}