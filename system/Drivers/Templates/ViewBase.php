<?php namespace Drivers\Templates;

/**
 *This class is the base class that handles template processing 
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Templates
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Templates\Map;
use Drivers\Templates\Implementation;

class ViewBase extends Implementation {

	//use the class tha define s the grammar map
	use Map;

}

