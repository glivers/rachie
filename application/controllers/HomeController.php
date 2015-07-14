<?php namespace Controllers;

/**
 *This class loads the purchase history for all user types.
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Marketplace
 *@package Marketplace\Controllers\Home
 *@link mobeoffice.com
 */

use Helpers\View;

class HomeController extends BaseController {

	/**
	 *This method loads the homepage 
	 *
	 *@param null
	 *@return void
	 */
	public function index()
	{
		//get the ending date today
		View::render('index');

	}

}

