<?php namespace Helpers\Upload;

/**
 *This class performs files uploads on the server. *
 *This is the implementation class of the Upload class. .
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
use Drivers\Registry;
use Helpers\Upload\UploadException;
use Helpers\Upload\UploadResponseClass;

class UploadClass {

	/**
	*@var string The name of the file submited for upload
	*/
	private $file_name;

	/**
	*@var string The target file name
	*/
	private $target_file_name;

	/**
	*@var string The target directory for the file upload
	*/
	private $target_dir;

	/**
	*@var string The target file to upload
	*/
	private $target_file;

	/**
	*@var string The uploaded file type
	*/
	private $file_type;

	/**
	*@var int The file size of the uploaded file
	*/
	private $file_size;

	/**
	*@var string The file upload directory
	*/
	private $upload_path;

	/**
	*@var string The relative upload file path
	*/
	private $upload_path_relative;

	/**
	*This method sets the upload file path
	*
	*@param string $dir_name The directory where to upload the file
	*@return \Object $this instance
	*/
	public function setUploadpath($dir_name)
	{
		//set the value of upload_path property
		//set the upload path
		if($dir_name === null)	$this->upload_path = Registry::getConfig()['upload_path'];

		else $this->upload_path = $dir_name;
		
		return $this;
	}

	/**
	*This method sets the file name submitted for uploading
	*
	*@param string $file_name The nameof the file in submitted form
	*@return \Object $this instance
	*/
	public function setFilename($file_name)
	{
		//set the static property of file_name
		$this->file_name = $file_name;

		return $this;
	}

	/**
	*This method sets the upload target directory
	*
	*@param null
	*@return \Object $this instance
	*/
	public function setTargetdir()
	{
		//set the target dir
		$this->target_dir =  Path::base() . $this->upload_path;

		//check if the directory exists and create if not
		if( ! file_exists($this->target_dir)) mkdir($this->target_dir);

		return $this;

	}

	/**
	*This method sets the file type of the uploaded file
	*
	*@param null
	*@return \Object $this instance
	*/
	public function setFiletype()
	{
		//set the value of the file type
		$this->file_type =  pathinfo($_FILES[$this->file_name]['name'],PATHINFO_EXTENSION);

		return $this;

	}

	/**
	*This method sets a unique target file name
	*
	*@param null
	*@return \Object $this instance
	*/
	public function setTargetfilename()
	{
		//set the value of target file name
		$this->target_file_name = sprintf('%s.%s',sha1_file($_FILES[$this->file_name]['tmp_name']),$this->file_type);

		return $this;

	}

	/**
	*This method sets the target file name
	*
	*@param null
	*@return \Object $this instance
	*/
	public function setTargetfile()
	{
		//set the upload_path_relative
		$this->upload_path_relative = $this->upload_path . $this->target_file_name;

		//set the full target file path
		$this->target_file = $this->target_dir . $this->target_file_name;

		return $this;

	}


	/**
	*This method checks if a valid file type was uploaded
	*@param string $file_type The name of the file type to check
	*@return \Object $this instance
	*/
	public function checkFiletype($file_type)
	{
		//check if the type was specified
		if( null !== $file_type)
		{

			return $this;
		}

		else return $this;

	}


	/**
	*This method sets the uploaded filesize
	*
	*@param null
	*@return \Object $this instance
	*/
	public function setFilesize()
	{
		//call method to get file size
		$this->file_size = $_FILES[$this->file_name]["size"];

		return new static;

	}

	/**
	*This method performs the actual file upload
	*
	*@param null
	*@return stdClass class object with success message
	*/
	public function upload()
	{
		//create the response object
		$response = new UploadResponseClass();

		//do upload in try...catch block to enable catching errors
		try{

			//upload file	
		 	if (move_uploaded_file($_FILES[$this->file_name]["tmp_name"], $this->target_file))
		 	{
		 		//set success parameters
		 		$response->success = true;
		 		$response->upload_path_full = $this->target_file;
		 		$response->upload_path_relative = $this->upload_path_relative;
		 		$response->file_name = $_FILES[$this->file_name]["name"];
		 		$response->file_size = $this->file_size;

		 		return $response;

	    	} 

	    	else 
	    	{
	        	//return error message
	        	$response->error = true;

	        	return $response;
	    	
	    	}

	    }
	    catch(UploadException $uploadExceptionObject){

	    	//display error message
	    	$uploadExceptionObject->errorShow();
	    	

	    }

	}

}
