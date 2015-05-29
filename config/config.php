<?php

return array(

	/**
	 *Set the application environment. Set true for development, otherwise, set to false
	 */
	'environment' => 'development',
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
		'controller' => 'home',
		/**
		 *Set default action
		 */
		'action' => 'index'

	),

	/**
	 *Define class aliases
	 *cd 
	 */
	'aliases' => array(
		'Helpers\Url' 				=> 'Url',
		'Helpers\Path' 				=> 'Path',
		'Helpers\Session'			=> 'Session'

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
				'host' => '127.0.0.1',
				/**
				 *Set the port for access
				 */
				'port' => '11211',
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
