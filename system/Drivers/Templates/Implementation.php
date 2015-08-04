<?php namespace Drivers\Templates;

/**
 *This class processes templates
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Templates
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class Implementation {

	/**
	 *This method is callesd to parse the provided vie file
	 *
	 *@param $path The path to the file to compile
	 *@return string The file path to the compiles string
	 */
	public function compiled($path, $embeded, $fileName)
	{
		//check if this file exists
		if ( file_exists( $path) ) 
		{
			//set the file path
			$this->path  = $path;

			//compile this template
			$compiled = $this->compile();

			//return the compiled contents
			return $compiled;

		}

		//throw an exception if this file does not exist
		else
		{
			//check if this is an embeded view file
			if ( $embeded == true ) 
			{
				//compose error message
				$message = 'The embeded view file \'' . $fileName . '\' cannot be found!';

			}
			//this is not an embeded file
			else  
			{
				//compose error message
				$message = 'The  view file ' . $fileName . ' cannot be found!';

			}
			//throw an exception
			echo $message;exit();

		}

	}

}

