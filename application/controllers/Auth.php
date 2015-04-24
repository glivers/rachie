<?php namespace Controllers;

class Auth {

	protected $_model;
	protected $_controller;
	protected $_action;
	protected $_template;

	function __construct()
	{
			echo "Something in here!";

	}

	function set($name, $value)
	{
		$this->_template->set($name, $value);

	}

	function __destruct()
	{
		//$this->_template->render();

	}

}