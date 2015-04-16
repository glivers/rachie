<?php

return array(

	/**
	 *Set the application environment. Set true for development, otherwise, set to false
	 */
	'development' => true,
	/**
	 *Set the Site Title
	 */
	'title' => 'Gliver MVC Application',
	/**
	 *Set the protocol for this url
	 */
	'protocol' => 'http',
	/**
	 *Set the server name for this url for example localhost or example.com for a domain
	 */
	'servername' => 'localhost',
	/**
	 *Define the root directory
	 */
	'root' => dirname((dirname(__FILE__))),
	/**
	 *Define the defaults
	 */
	'default' => array(
		/**
		 *Set the default controller
		 */
		'controller' => 'home',
		/**
		 *Set default action
		 */
		'action' => 'index'

	),

);
