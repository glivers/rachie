<?php namespace Libraries\CronLibrary;

/**
 *This class launches the cron job to activate all users database
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Cron\Controllers
 *@package Cron\Controllers\Sample
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Libraries\CronLibrary\SampleCronModel;

class SampleCronController {

	/**
	*This method initializes the database update query for this cron job instance
	*
	*@param null
	*@return bool True|False True if update successful, False on failure
	*/
	public function init(){

		//call the model to update all users
		$updated = SampleCronModel::activateAllUsers();

		//chekc if update was success and echo message
		if($updated) return "Update Success!";
		else return false;

	}

}