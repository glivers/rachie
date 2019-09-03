<?php namespace Drivers\Database;

/**
 *This class is the base database class which all subclasses extend
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Database
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Database\DbImplement;

class BaseDb {

	use DbImplement;

	/**
	 *This constructor method intializes the database type and sets the options parameters
	 *
	 *@param string $name This is the default database name
	 *@param array $options This is the array containing the database connection paramters
	 */
	public function __construct($name, $options)
	{
		//assign $name to the database type
		$this->type = $name;

		//assign the $options to the database connection options
		$this->options = $options;

	}


}