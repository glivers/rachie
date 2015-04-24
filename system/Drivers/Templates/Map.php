<?php namespace Core\Drivers\Templates;

/**
 *This trait defines the grammar map for our default template parser.
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Templates
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

trait  Map {

	/**
	 *@var array Array of grammar syntax for our template parser
	 *
	 */
	protected $map = array(

		'echo' => array(
				'opener' => "{echo",
				'closer' => "}",
				'handler' => "echo"
			),
		'script' => array(
				'opener' => "{script",
				'closer' => "}",
				'tags' => array(
						'foreach' => array(
								'isolated' => false,
								'arguments' => "{element} in {object}",
								'handler' => "each"
							),
						'for' => array(
								'isolated' => false,
								'arguments' => "{element} in {object}",
								'handler' => 'for'
							),
						'if' => array(
								'isolated' => false,
								'arguments' => null,
								'handler' => 'if'
							),
						'elseif' => array(
								'isolated' => true,
								'arguments' => null,
								'handler' => 'elif'
							),
						'else' => array(
								'isolated' => true,
								'arguments' => null,
								'handler' => 'else'
							),
						'macro' => array(
								'isolated' => false,
								'arguments' => "{name}({args})",
								'handler' => 'macro'
							),
						'literal' => array(
								'isolated' => false,
								'arguments' => null,
								'handler' => 'literal'
							)

					)
			)

	);

}
