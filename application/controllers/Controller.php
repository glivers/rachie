<?php namespace Controllers;

use Core\Helpers\View;

class Controller {

	protected $_controller;
	protected $_action;

	function __construct($model, $controller, $action)
	{
		echo "Something in here!";
		$this->_controller 	= $controller;
		$this->_action 		= $action;

	}

/*
	function set($name, $value)
	{
		View::set($name, $value);

	}
*/
	function __destruct()
	{
		View::render('items/viewall');

	}

}