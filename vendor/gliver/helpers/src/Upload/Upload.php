<?php namespace Helpers\Upload;

/**
 *This class performs files uploads on the server. *
 *This class implements Singleton and cannot be subclassed. .
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Helpers
 *@package Helpers\Upload
 *@link core.gliver.io
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Helpers\Path\Path;
use Helpers\Upload\UploadClass;

class Upload {


	/**
	*@var string The file name to upload
	*/
	private static $file_name;

	/**
	*@var \Object The UploadClass implementation class
	*/
	private static $uploadClassInstance;

	/**
	*This method sets the instance of the Upload implementation class
	*@param null
	*@return void
	*/
	public static function setUploadClassInstance()
	{
		//check if the class has already been set
		if( self::$uploadClassInstance === null)
		{
			//set the instance
			self::$uploadClassInstance = new UploadClass();

			return new static ;
		}

		else return new static;

	}

	/**
	*This method sets the file name submitted for uploading
	*
	*@param string $file_name The nameof the file in submitted form
	*@return \Object This static instance
	*/
	protected static function setFilename($file_name)
	{
		//set the static property of file_name
		self::$file_name = $file_name;

		//call class method to set file name
		self::$uploadClassInstance->setFilename($file_name);

		return new static;
	}

	/**
	*This method sets the upload file path
	*
	*@param string $dir_name The directory where to upload the file
	*@return \Object $this instance
	*/
	protected static function setUploadpath($dir_name)
	{
		//set the value of upload_path property
		self::$uploadClassInstance->setUploadpath($dir_name);

		return new static;
	}

	/**
	*This method sets the upload target directory
	*
	*@param null
	*@return \Object This static class instance
	*/
	protected static function setTargetdir()
	{
		//call method tp set the target_dir
		self::$uploadClassInstance->setTargetdir();

		return new static;

	}

	/**
	*This method sets the file type of the uploaded file
	*
	*@param null
	*@return \Object This static class instance
	*/
	protected static function setFiletype()
	{
		//call the method to set the file type
		self::$uploadClassInstance->setFiletype();

		return new static;

	}

	/**
	*This method sets a unique target file name
	*
	*@param null
	*@return \Object This static class instance
	*/
	protected static function setTargetfilename()
	{
		//call the method to set the target file
		self::$uploadClassInstance->setTargetfilename();

		return new static;

	}


	/**
	*This method sets the target file name
	*
	*@param null
	*@return \Object This static class instance
	*/
	protected static function setTargetfile()
	{
		//call the method to set the target file
		self::$uploadClassInstance->setTargetfile();

		return new static;

	}

	/**
	*This method checks if a valid file type was uploaded
	*@param string $file_type The name of the file type to check
	*@return \Object This static class instance
	*/
	protected static function checkFiletype($file_type)
	{
		//call the method to check for the file type
		self::$uploadClassInstance->checkFiletype($file_type);

		return new static;

	}

	/**
	*This method sets the uploaded filesize
	*
	*@param null
	*@return \Object This static class instance
	*/
	protected static function setFilesize()
	{
		//call method to get file size
		self::$uploadClassInstance->setFilesize();

		return new static;

	}

	/**
	*This method performs the actual file upload
	*
	*@param string $file_name The name of the file to upload
	*@param string $target_dir The name of the directory where to upload the file to
	*@param string $file_type The file type to be checked for validation
	*@return stdClass class object with success message
	*/
	public static function doUpload($file_name, $target_dir = null, $file_type = null)
	{
		//set the class instance setting the class properties
		self::setUploadClassInstance()
			->setUploadpath($target_dir)
			->setFilename($file_name)
			->setTargetdir()
			->setFiletype()
			->setTargetfilename()
			->setTargetfile()		
			->checkFiletype($file_type)
			->setFilesize();

		//call method to upload file
		return self::$uploadClassInstance->upload();

	}

}
