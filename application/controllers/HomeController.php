<?php namespace Controllers;

/**
 * This class loads the application homepage
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Controllers
 * @package Controllers\Home
 * @link https://github.com/glivers/rachie
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.1
 */

use Rackage\View;
use Rackage\Controller;

class HomeController extends Controller {

	/**
	 * @var bool Set to true to enable method filters in this controller
	 */
	public $enable_filters = false;

	/**
	 * This method loads the homepage. 
	 * @param int $id The user id
	 * @return void
	 */
	public function getIndex( $id)
	{

		$data['title'] = $this->site_title;
		$data['request_time'] = $this->request_exec_time();

		View::render('home/index',$data);

	}
	
}

