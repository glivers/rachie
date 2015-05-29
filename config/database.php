<?php 

/**
 *Define the database settings as per the driver in use
 */

return array(

	/*
	 *Define the default driver
	 */
	'default' => 'mysql',
	/*
	 *Settings for the mysql database
	 */
	'mysql' => array(
		/**
		 *Define the database server hostname
		 */
		'host' 		=> 'localhost',
		/*
		 *Define the username
		 */
		'username' 	=> 'root',
		/*
		 *Define the database password
		 */
		'password' 	=> '',
		/*
		 *Specify the database to connnect to
		 */
		'database' 	=> '',
		/**
		 *Specify the database server port
		 */
		'port' => '3306',
		/**
		 *Set the default charset
		 */
		'charset' => 'utf8',
		/**
		 *Set the default database engine
		 */
		'engine' => 'InnoDB'

		),

	'sqlite' => array(
		/**
		 *Define the database server hostname
		 */
		'host' 		=> '',
		/*
		 *Define the username
		 */
		'username' 	=> '',
		/*
		 *Define the database password
		 */
		'password' 	=> '',
		/*
		 *Specify the database to connnect to
		 */
		'database' 	=> '',
		/**
		 *Specify the database server port
		 */
		'port' => '3306',
		/**
		 *Set the default charset
		 */
		'charset' => 'utf8',
		/**
		 *Set the default database engine
		 */
		'engine' => 'InnoDB'

	),
	'postgresql' => array(
		/**
		 *Define the database server hostname
		 */
		'host' 		=> '',
		/*
		 *Define the username
		 */
		'username' 	=> '',
		/*
		 *Define the database password
		 */
		'password' 	=> '',
		/*
		 *Specify the database to connnect to
		 */
		'database' 	=> '',
		/**
		 *Specify the database server port
		 */
		'port' => '3306',
		/**
		 *Set the default charset
		 */
		'charset' => 'utf8',
		/**
		 *Set the default database engine
		 */
		'engine' => 'InnoDB'

	),

);

