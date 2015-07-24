<?php namespace Drivers\Database\MySQL;

/**
 *This class handles all exceptions thrown in the context of mysql database operations
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Database\MySQL
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Helpers\Path;

class MySQLException extends \Exception {

	/**
	 *This method displays the error message
	 *
	 *@param 
	 *
	 */
	public function show()
	{
		//get the global $config array for site title
		global $config;

		//set the title variable
		$title = $config['title'];

		//the variable to be populated with the error message
		$msg = $this->getCode() . ': Error on line '.$this->getLine().' in '.$this->getFile() .': <b>  "'.$this->getMessage().' " </b> ';

		//load the template file
		include Path::sys() . 'Exceptions' . DIRECTORY_SEPARATOR . 'index.php';

		//stop further output
		exit();

	}

}
