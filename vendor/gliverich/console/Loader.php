<?php namespace Gliverich\Console;

/**
 *This class loads the classes that handle gliverich command line tools
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Gliverich
 *@package Gliverich\Console\Loader
 *@link https://github.com/gliver-mvc/console
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Gliverich\Console\Commands\Controller;
use Gliverich\Console\Commands\Database;
use Symfony\Component\Console\Application;

class Loader {


	public function __construct(){

		$console = new Application();
		$console->add(new Controller());
		$console->add(new Database());
		$console->run();

	}

}

?>