<?php namespace Helpers\StringHelper;

/**
 *This class performs repetative String  functions.
 *
 *This class implements Singleton and cannot be subclassed. It sanitizes on our strings before we use them.
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers
 *@link core.gliver.io
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

class StringHelper {

	/**
	 *@var string $delimiter Used to sanitize regular expression pattern
	 *
	 */
	public static $delimiter = "#";

	/**
	 *This constructor class is private to prevent creating instances of this class
	 *
	 *@param null
	 *@return void
	 *@example Array.php 17 29 Example of Similar Singleton implementation.
	 */
	private function __construct()
	{
		//do something

	}

	/**
	 *This magic __clone() is private to prevent cloning of this class
	 *
	 *@param null
	 *@return void
	 *@example Array.php 30  41 Example of Singleton Implementation
	 */
	private function __clone()
	{
		//do something

	}

	/**
	 *This method normalizes the regular expression pattern before we use it
	 *
	 *@param string $pattern The regular expression string to be used
	 *@return string Normalized/Sanitized regular expression string
	 *@throws This method does not throw an error
	 */
	public static function normalize($pattern)
	{
		//normalize the string pattern and return safe pattern for use
		return self::$delimiter.trim($pattern, self::$delimiter).self::$delimiter;

	}

	/**
	 *This method returns the delimiter property value set by this object instance
	 *
	 *@param null
	 *@return string The delimiter string set
	 *@throws This method does not throw an error
	 */
	public static function getDelimiter()
	{
		//get the set delimiter value and return 
		return self::$delimiter;

	}

	/**
	 *This method sets the delimiter property value
	 *
	 *@param string $delimiter The string to assign to this property
	 *@return void
	 *
	 */
	public static function setDelimiter()
	{
		//set the delimiter value
		self::$delimiter = $delimiter;

	}


	/**
	 *This methos performs a regular expression match on the string provided using the pattern provided
	 *
	 *@param string $string The string on which to perform a match
	 *@param string $pattern The regular expression pattern to use for matching
	 *@return array||null Returns array of match successful or null if no match was found
	 *
	 */
	public static function match($string, $pattern)
	{
		//perform the regular expression on the string provided
		preg_match_all(self::normalize($pattern), $string, $matches, PREG_PATTERN_ORDER);

		//return array of strings matched by the first parenthesized sub pattern, if found
		if( ! empty($matches[1]))
		{
			//return this array
			return $matches[1];

		}

		//return an array of full pattern matches if step above missed
		if( ! empty($matches[0]))
		{
			//return multi-dimensional array
			return $matches[0];

		}

		//no matches were found, return null
		return null;

	}

	/**
	 *This function splits an string and returns an array of substrings
	 *
	 *@param string $string The string to split into substrings
	 *@param string $pattern The pattern to use to perform the split
	 *@param int $limit The string position limit
	 *@return array The array containing the substrings of $string split along boundaries matched by pattern 
	 *
	 */
	public static function split($string, $pattern, $limit = null)
	{
		//set the flags to use to perform the preg_split
		$flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;

		//split string and return array,
		return preg_split(self::normalize($pattern), $string, $limit, $flags);

	}

	/**
	 *This method cleans strips a string of html, xml and php tags
	 *
	 *@param string $string The string to be cleaned of tags
	 *@return string String after tags have been removed
	 */
	public static function removeTags($string)
	{
		//remove tags and return new string
		return strip_tags($string);

	}

	/**
	 *This method loops through the characters of a string, replacing them with regualar expression friendly 
	 *character representations.
	 *
	 *@param string $string The string to be converted
	 *@param string $mask
	 *@return string The string after it has been sanitized
	 */
	public static function sanitize($string, $mask)
	{
		//check if the input mask is an array
		if( is_array($mask))
		{
			//assign value to parts
			$parts = $mask;

		}

		//check if $mask is a string
		else if( is_string($mask))
		{
			//divide string into substrings
			$parts = str_split($mask);

		} 

		//not any of the above, return the string 
		else
		{
			//return string
			return $string;

		}
		
		//loop through the parts array normalizing each part
		foreach ($parts as $part) 
		{
			//normalize the part
			$normalized = self::normalize("\\{$part}");

			//search string and replace normalized string in place of original string from input $string
			$string = preg_replace("{$normalized}m", "\\{$part}", $string);

		}

		//return the string after sanitizing is complete
		return $string;

	}

	/**
	 *This method removes duplicates from a string
	 *
	 *@param string $string The string for which duplicates are to be removed
	 *@return string String with only unique values
	 */
	public static function unique($string)
	{
		//set intitial unique var sting as empty
		$unique = '';

		//split the string into substrings
		$parts = str_split($string);

		//loop through the sting parts array removing duplicated characters
		foreach ($parts as $part) 
		{
			//add this character if it doesnt exist yet
			if( ! strstr($unique, $part) )
			{
				//add this character to the main unique array
				$unique .= $part;

			}

		}

		//return the unique string
		return $unique;

	}

	/**
	 *This method determines substrings within larger strings
	 *
	 *@param string $string The main sting against which to check for substring
	 *@param string $substring The substring to check for
	 *@param int $offset The offset value to start from 
	 *@return int The position of the substring in the main string
	 */
	public static function indexOf($string, $substring, $offset = null)
	{
		//get the position of this string in the main string
		$position = strpos($string, $substring, $offset);

		//return -1 of the substring was not found
		if( ! is_int($position) )
		{
			//return string position  as -1
			return -1;

		}

		//return actual string position if found
		return $position;
	}


	private static $singulars = array(
		"(matr)ices$" => "\\1ix",
		"(vert|ind)ices$" => "\\1ex",
		"^(ox)en" => "\\1",
		"(alias)es$" => "\\1",
		"([octop|vir])i$" => "\\1us",
		"(cris|ax|test)es$" => "\\1is",
		"(shoe)s$" => "\\1",
		"(o)es$" => "\\1",
		"(bus|campus)es$" => "\\1",
		"([m|l])ice$" => "\\1ouse",
		"(x|ch|ss|sh)es$" => "\\1",
		"(m)ovies$" => "\\1\\2ovie",
		"(s)eries$" => "\\1\\2eries",
		"([^aeiouy]|qu)ies$" => "\\1y",
		"([lr])ves$" => "\\1f",
		"(tive)s$" => "\\1",
		"(hive)s$" => "\\1",
		"([^f])ves$" => "\\1fe",
		"(^analy)ses$" => "\\1sis",
		"((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$" => "\\1\\2sis",
		"([ti])a$" => "\\1um",
		"(p)eople$" => "\\1\\2erson",
		"(m)en$" => "\\1an",
		"(s)tatuses$" => "\\1\\2tatus",
		"(c)hildren$" => "\\1\\2hild",
		"(n)ews$" => "\\1\\2ews",
		"([^u])s$" => "\\1"

	);

	private static $plurals = array(
		"^(ox)$" => "\\1\\2en",
		"([m|l])ouse$" => "\\1ice",
		"(matr|vert|ind)ix|ex$" => "\\1ices",
		"(x|ch|ss|sh)$" => "\\1es",
		"([^aeiouy]|qu)y$" => "\\1ies",
		"(hive)$" => "\\1s",
		"(?:([^f])fe|([lr])f)$" => "\\1\\2ves",
		"sis$" => "ses",
		"([ti])um$" => "\\1a",
		"(p)erson$" => "\\1eople",
		"(m)an$" => "\\1en",
		"(c)hild$" => "\\1hildren",
		"(buffal|tomat)o$" => "\\1\\2oes",
		"(bu|campu)s$" => "\\1\\2ses",
		"(alias|status|virus)" => "\\1es",
		"(octop)us$" => "\\1i",
		"(ax|cris|test)is$" => "\\1es",
		"s$" => "s",
		"$" => "s"

	);

	/**
	 *This method gets the singular form of an input string
	 *
	 *@param string $string The input string for which an singular term is to be found.
	 *@return string The output string after appropriate replacement has been done.
	 */
	public static function singular($string)
	{
		//assing the input string to a result variable
		$result = $string;

		//loop through the array of singular patterns, getting the regular exppression patters
		foreach ($self::$singulars as $rule => $replacement) 
		{
			//get the regular expression friendly pattern
			$rule = self::normalize($rule);

			//check if there is a matching pattern in the input string
			if ( preg_match($rule, $string) ) 
			{
				//replace with the appropriate string and return
				$result = preg_replace($rule, $replacement, $string);

				//once  a replacement is found, break out of the loop
				break;

			}

		}

		//return the result that was found
		return $result;

	}

	/**
	 *This method gets the plural form of an input string
	 *
	 *@param string $string Input string for which to find plural
	 *@return string The output string after chaning to plural
	 */
	public static function plural($string)
	{
		//assign the input string to a result variable
		$result = $string;

		//loop through the array of plural patterns, getting the regular exppression friendly pattern
		foreach (self::$plurals as $rule => $replacement) 
		{
			//get the regular axpression friendlt pattern
			$result = $string;

			//check if there is a match for this pattern in the input string
			if ( preg_match($rule, $string) ) 
			{
				//if match found, replace rule with the replacement string
				$result = preg_replace($rule, $replacement, $string);

				//break out of loop once match is found
				break;

			}

		}

		//return the new string contained in result
		return $result;

	}

}

