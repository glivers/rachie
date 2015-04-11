<?php

//This class has been taken from 
//http://kuwamoto.org/2007/12/17/improved-pluralizing-in-php-actionscript-and-ror
//Type: MIT License
//Changes: A few changes to add custom irregularWords from config/inflection.php

class Inflection {

	static $plural = array(

			'/(quiz)$/i'				=> "$1zes",
			'/^(ox)$/i'					=> "$1en",
			'/([m|l])ouse$/i'			=> "$1ice",
			'/(matr|vert|ind)ix|ex$/i'	=> "$1ices",
			'/(x|ch|ss|sh)$/i'			=> "$1es"



	);

}