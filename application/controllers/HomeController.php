<?php namespace Controllers;

/**
 *This class loads the application homepage
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Controllers
 *@package Controllers\Home
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Templates\View;
use Libraries\CronLibrary\SampleCronController;

class HomeController extends BaseController {

	/**
	 *This method loads the homepage 
	 *
	 *@param null
	 *@return void
	 */
	public function getIndex()
	{
		//define the page title
		$data['title'] = $this->site_title;
		$data['request_time'] = $this->request_exec_time();

		//load the framework homepage
		View::render('index', $data);

	}

	/**
	*This method initializes a cron job in the library which executes a series of queries in the 
	*database to keep user information in synch
	*
	*@param null
	*@return void
	*/
	public function CronInit(){

		//create an instance of the cronController class
		$cronControllerObject = new SampleCronController();

		//call the method to lauch the cron job operation
		$cronControllerObject->init();

	}
	
}

