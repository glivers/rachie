<?php

return array(

	/**
	*The timezone to use with your application DateFunctions
	*/
	'timezone' => 'America/New_York',

	/**
	 *Your name as the author of this application
	 *this would appear in your console generated classes
	 */
	'author' => 'Geoffrey Bans <geoffreybans@gmail.com>',

	/**
	*Your copyright statement
	*/
	'copyright' => 'Copyright (c) 2015 - 2020 Geoffrey Bans',

	/*
	*Your license name and link
	*/
	'license' => 'http://opensource.org/licenses/MIT MIT License',

	/*
	*The version number of this application that you are writing
	*/
	'version' => '0.0.1',

	/**
	 *Set the application environment. Set true for development, otherwise, set to false
	 */
	'dev' => true,

	/**
	*The location of the error log file, you can change this to the file of your choice
	*ensure to use relative path as specified in the default here
	*/
	'error_log_file_path' => 'bin/logs/error.log',
	/**
	 *Set the Site Title
	 */
	'title' => 'Gliver Framework',
	/**
	 *Set the protocol for this url
	 */
	'protocol' => 'http',
	/**
	 *The server name for this application
	 */
	'servername' => 'localhost',
	/**
	 *The url component separator character
	 */
	'url_component_separator' => '.',
	/**
	*The default upload file path
	*/
	'upload_path' => 'public/uploads/',
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
		'Helpers\Url\Url' 					=> 'Url',
		'Helpers\Path\Path' 				=> 'Path',
		'Helpers\Session\Session'			=> 'Session',
		'Helpers\ArrayHelper\ArrayHelper'	=> 'ArrayHelper',
		'Helpers\Cookie\Cookie'				=> 'Cookie',
		'Helpers\Form\Form'					=> 'Form',
		'Helpers\Input\Input'				=> 'Input',
		'Helpers\View\View'					=> 'View',
		'Drivers\Registry'					=> 'Registry',

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
