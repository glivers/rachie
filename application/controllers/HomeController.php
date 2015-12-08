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

use Helpers\View\View;
use Helpers\Input\Input;
use Libraries\CronLibrary\SampleCronController;

class HomeController extends BaseController {

	/**
	 *This method loads the homepage 
	 *
	 *@param null
	 *@return void
	 */
	public function index()
	{
		//define the page title
		$data['title'] = 'Gliver MVC PHP Framework';

		//get the ending date today
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

