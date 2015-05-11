<?php namespace Core\Drivers\Templates;

/**
 *This class has method that process template files
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Templates
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Templates\Base;


class  Implementation extends Base {

	/**
	 *This is the 'echo' tag handler
	 *
	 *@param array $tree
	 *@param string $source Source string to process
	 *@return string The processed text
	 */
	protected function getEcho($tree, $content)
	{
		//parse the contents of the 'echo' tag
		$raw = $this->script($tree, $content);

		//return the processed content
		return "\$_text[] = {$raw}";

	}

	/**
	 *This method processes content within the 'script' tag
	 *
	 *@param array $tree
	 *@param string $content The source string to parse
	 *@return string Processed string
	 */
	protected function getScript($tree, $content)
	{
		//return the content of the raw index, or emplty if no contect
		$raw = ! empty($tree['raw']) ? $tree['raw'] : '';

		//return content
		return "{$raw};";

	}

	/**
	 *This method processes the contents within the 'each' tag
	 *
	 *@param array $tree
	 *@param string $content The source string to parse
	 *@return array Processed content
	 */
	protected function getEach($tree, $content)
	{
		//get the object name
		$object = $tree['arguments']['object'];

		//get the elements names
		$element = $tree['arguments']['element'];

		return $this->loop(
				$tree,
				"foreach ({$object} as {$element}_i => {$element}) { {$content} }"
			);

	}

	/**
	 *This method processes the content between the for tag
	 *
	 *@param array $tree
	 *@param string $content Source string to parse
	 *@return array Processed content
	 */
	protected function getFor($tree, $content)
	{
		//get the object name
		$object = $tree['arguments']['object'];

		//get the element name
		$element = $tree['arguments']['element'];

		return $this->loop(
				$tree,
				"for ({$element}_i=0; {$element}_i < sizeof({$object}); {$element}_i++) { {$element} = {$object}[{$element}_i]; {$content} } "
			);

	}

	/**
	 *This method processes the content within the 'if' tag
	 *
	 *@param array $tree
	 *@param string $content The source content to parse
	 *@return string Proccessed string
	 */
	protected function getIf($tree, $content)
	{
		//set the value of raw
		$raw = $tree['raw'];

		return "if ({$raw}) {{$content}}";

	}

	/**
	 *This method processes the content within the 'elseif' tag
	 *
	 *@param array $tree
	 *@param string $content The source string to parse
	 *@return string Processed string
	 */
	protected function getElif($tree, $content)
	{
		//get the $raw
		$raw = $tree['raw'];

		//return 
		return "elseif ({$raw}) {{$content}}";

	}

	/**
	 *This method processes the content within the 'else' tag
	 *
	 *@param array $tree
	 *@param string $content The source string to parse
	 *@return string Processed content
	 */
	protected function getElse($tree, $content)
	{
		//return the content
		return "else {{$content}}";

	}

	/**
	 *This method processes the content within the 'macro' tag
	 *
	 *@param array $tree
	 *@param string $content The content of string to parse
	 *@return string Processed string
	 */
	protected function getMacro($tree, $content)
	{
		//get the arguments
		$arguments = $tree['arguments'];

		//get argument name
		$name = $arguments['name'];

		//get argument args
		$args = $arguments['args'];

		return "function {$name}({$args}) {
				\$_text=array();
				{$content}
				return implode(\_text);
				}";

	}

	/**
	 *This method processes content within the 'literal' tag
	 *
	 *@param array $tree
	 *@param string $content
	 *@return string Processed content
	 */
	protected function getLiteral($tree, $content)
	{
		//get the source
		$source = addslashes($tree['source']);

		//return
		return "\$_text[]=\"{$source}\";";

	}

	/**
	 *This method processes the content in the 'loop' tags
	 *
	 *@param array $tree
	 *@param string $content
	 *@return string Processed content
	 */
	protected function loop($tree, $inner)
	{
		//get the number
		$number = $tree['number'];

		//get the object
		$object = $tree['arguments']['object'];

		//get children
		$children = $tree['parent']['children'];

		if( ! empty($children[$number +1]['tag']) && $children[$number+1]['tag'] == "else" )
		{
			return "if (is_array({$object}) && sizeof({$object}) >0) {{$inner}}";

		}

		return $inner;

	}

}
