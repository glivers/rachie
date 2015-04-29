<?php namespace Controllers;

/**
 *This is the base controller from which all named controllers derive from
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers
 *@link core.gliver.io
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Core\Helpers\View;
use Core\Drivers\Controllers\Implementation;

class Controller {

	//call the trait with the setter/getter classes
	use Implementation;

	function __construct()
	{
		//do something

	}

}