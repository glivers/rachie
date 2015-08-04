<?php

return array(

	/**
	 *Set the application environment. Set true for development, otherwise, set to false
	 */
	'dev' => true,
	/**
	 *Set the Site Title
	 */
	'title' => 'Gliver MVC Framework',
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
		'controller' => 'Home',
		/**
		 *Set default action
		 */
		'action' => 'Index'

	),

	/**
	 *Define class aliases
	 *cd 
	 */
	'aliases' => array(
		'Helpers\Url' 				=> 'Url',
		'Helpers\Path' 				=> 'Path',
		'Helpers\Session'			=> 'Session',
		'Helpers\ArrayHelper'		=> 'Array',
		'Helpers\Cookie'			=> 'Cookie',
		'Helpers\Form'				=> 'Form',
		'Helpers\Input'				=> 'Input',
		'Helpers\Redirect'			=> 'Redirect',
		'Helpers\View'				=> 'View',

	),
	/**
	 *Define the Caching Driver Settings
	 */
	'cache' => array(
		/**
		 *Set true|false to turn caching on and off
		 */
		'cache' => false,
		/**
		 *Define the default driver
		 */
		'default' => 'memcached',
		/**
		 *Define the setting for individual drivers
		 */
		'configuration' => array(
			/**
			 *Define the settings for Memcached Driver
			 */
			'memcached' => array(
				/**
				 *Set the host name
				 */
				'host' => '',
				/**
				 *Set the port for access
				 */
				'port' => '',
				/**
				 *Set the Socket file
				 */
				'socket' => '',

			),
			/**
			 *Define the settings for Redis Server
			 */
			'redis' => array(
				/**
				 *Set the server host name
				 */
				'host' => '',
				/**
				 *Set the access port numnber
				 */
				'port' => '',
				/**
				 *Set the socket file
				 */
				'socket' => '',

			),

		),

	),

);
