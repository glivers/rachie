<?php namespace Controllers;

/**
 *This class loads the application homepage
 *@author Geoffrey Okongo <code@gliver.org>
 *@copyright 2015 - 2030 Geoffrey Okongo
 *@category Controllers
 *@package Controllers\Home
 *@link https://github.com/gliverphp/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Gliver\Templates\View;
use Libraries\CronLibrary\SampleCronController;
use Models\UsersModel;
use Gliver\Url\Url;

class HomeController extends BaseController {

	/**
	 * @var bool Set to true to enable method filters in this controller
	 */
	public $enable_method_filters = false;

	/**
	 * This method loads the homepage. 
	 * @param int $id The user id
	 * @return void
	 */
	public function getIndex( $id)
	{

		$data['title'] = $this->site_title;
		$data['request_time'] = $this->request_exec_time();

		View::render('index',$data);

	}

	/**
	 * This method initializes a cron job in the library which executes a series of queries in the 
	 * database to keep user information in synch
	 *
	 * @param null
	 * @return void
	 */
	public function CronInit(){

		//create an instance of the cronController class
		$cronControllerObject = new SampleCronController();

		//call the method to lauch the cron job operation
		$cronControllerObject->init();

	}
	
}

