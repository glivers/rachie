<?php namespace Core\Exceptions;

use Core\Exceptions\Exception;
use Core\Helpers\Path;

class FileErrorException extends Exception {

	/**
	 *This function prints the appropriate error messages to the client
	 *
	 */
	public function show()
	{
		//get the global $config array for site title
		global $config;

		//set the title variable
		$title = $config['title'];

		//the variable to be populated with the error message
		$msg = $this->getCode() . ': Error on line '.$this->getLine().' in '.$this->getFile() .': <b> The view file named "'.$this->getMessage().'.php " </b> was not found';

		//parent::__construct($this->msg, $error->code);

		//load the template file
		include Path::sys() . 'Exceptions' . DIRECTORY_SEPARATOR . 'index.php';

	}

}