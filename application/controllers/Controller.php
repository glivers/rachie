<?php namespace Controllers;

use Core\Template;

class Controller {

	protected $_model;
	protected $_controller;
	protected $_action;
	protected $_template;

	function __construct($model, $controller, $action)
	{
			echo "Something in here!";
		$this->_controller 	= $controller;
		$this->_action 		= $action;
		$this->_model 		= $model;

		$model = 'Models\\' . $model;

		$this->$model 		= new $model;
		$this->_template 	= new Template($controller, $action); 

	}

	function set($name, $value)
	{
		$this->_template->set($name, $value);

	}

	function __destruct()
	{
		$this->_template->render();

	}

}