<?php namespace Controllers;

/**
 * This is the base controller from which all named controllers derive by extending.
 * @author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 * @copyright 2015 - 2020 Geoffrey Oliver
 * @category Controllers
 * @package Controllers\BaseController
 * @link https://github.com/gliver-mvc/gliver
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.1
 */

use Drivers\Controllers\BaseControllerTrait;

class BaseController {

	//call the trait with the setter/getter methods
	use BaseControllerTrait;

	/**
	 * @var bool Set to true to enable method filters in this controller
	 */
	public $enable_method_filters = false;

}