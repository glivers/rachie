<?php namespace Helpers\File;

/**
 *This class provides information about a file
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Helpers
 *@package Helpers\File\FileInfo
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class FileInfo extends \SplFileInfo {

	/**
	 *This method creates and returns an object of the File handler object.
	 *@param string $file_path The full path to the file
	 *@return object $this
	 */
	public static function Create($file_path)
	{

		//return this instance
		return new self($file_path);

	}

}