<?php namespace Drivers\Templates;

/**
 *This class defines the template grammar map
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Core
 *@package Core\Drivers\Templates
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Templates\View as ViewHelper;

trait GrammarMapTrait {

	/**
	 * The file currently being compiled.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * All of the available compiler functions.
	 *
	 * @var array
	 */
	protected $compilers = array(
		'Statements',
		'Echos'
	);

	/**
	 * Array of opening and closing tags for escaped echos.
	 *
	 * @var array
	 */
	protected $echoTags = array('{{', '}}');

	/**
	 * Array of opening and closing tags for escaped echos.
	 *
	 * @var array
	 */
	protected $echoEscapeTags = array('{{{', '}}}');

	/**
	 * Array of footer lines to be added to template.
	 *
	 * @var array
	 */
	protected $footer = array();

	/**
	 * Counter to keep track of nested forelse statements.
	 *
	 * @var int
	 */
	protected $forelseCounter = 0;

	/**
	 * Compile the view at the given path.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function compile($path = null)
	{
		$this->footer = array();

		//get the contents of this view file
		$contents = file_get_contents($this->path);

		$compiledContents = $this->compileString($contents);

		//return the compiled comtents
		return $compiledContents;

	}

	/**
	 * Compile the given template contents.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function compileString($string)
	{
		$result = '';

		// Here we will loop through all of the tokens returned by the Zend engine's lexical scanner and
		// parse each one into the corresponding valid PHP. We will then have this
		// template as the correctly rendered PHP that can be rendered natively.
		foreach (token_get_all($string) as $token)
		{
			$result .= is_array($token) ? $this->parseToken($token) : $token;
		}

		// If there are any footer lines that need to get added to a template we will
		// add them here at the end of the template. This gets used mainly for the
		// template inheritance via the extends keyword that should be appended.
		if (count($this->footer) > 0)
		{
			$result = ltrim($result, PHP_EOL)
					.PHP_EOL.implode(PHP_EOL, array_reverse($this->footer));
		}

		return $result;
	}

	/**
	 * Parse the tokens from the template.
	 *
	 * @param  array  $token
	 * @return string The compiled template contents
	 */
	protected function parseToken($token)
	{
		list($id, $content) = $token;

		if ($id == T_INLINE_HTML)
		{
			foreach ($this->compilers as $type)
			{
				$content = $this->{"compile{$type}"}($content);
			}

		}

		return $content;
	}

	/**
	 * Compile Glade echos into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileEchos($value)
	{
		$difference = strlen($this->echoTags[0]) - strlen($this->echoEscapeTags[0]);

		if ($difference > 0)
		{
			return $this->compileEscapedEchos($this->compileRegularEchos($value));
		}

		return $this->compileRegularEchos($this->compileEscapedEchos($value));
	}

	/**
	 * Compile Blade Statements that start with "@"
	 *
	 * @param  string  $value
	 * @return mixed
	 */
	protected function compileStatements($value)
	{
		$callback = function($match)
		{
			//echo "<pre>";print_r($match);exit();
			if (method_exists($this, $method = 'compile'.ucfirst($match[1])))
			{
				$match[0] = $this->$method( isset( $match[3] ) ? $match[3] : '');
			}

			return isset($match[3]) ? $match[0] : $match[0].$match[2];
		};

		return preg_replace_callback('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', $callback, $value);
	}

	/**
	 * Compile the "regular" echo statements.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileRegularEchos($value)
	{
		$pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->echoTags[0], $this->echoTags[1]);

		$callback = function($matches)
		{
			$whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

			return $matches[1] ? substr($matches[0], 1) : '<?php echo '.$this->compileEchoDefaults($matches[2]).'; ?>'.$whitespace;
		};

		return preg_replace_callback($pattern, $callback, $value);
	}

	/**
	 * Compile the escaped echo statements.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileEscapedEchos($value)
	{
		$pattern = sprintf('/%s\s*(.+?)\s*%s(\r?\n)?/s', $this->echoEscapeTags[0], $this->echoEscapeTags[1]);

		$callback = function($matches)
		{
			$whitespace = empty($matches[2]) ? '' : $matches[2].$matches[2];

			return '<?php echo e('.$this->compileEchoDefaults($matches[1]).'); ?>'.$whitespace;
		};

		return preg_replace_callback($pattern, $callback, $value);
	}

	/**
	 * Compile the default values for the echo statement.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function compileEchoDefaults($value)
	{
		return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
	}

	/**
	 * Compile the each statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEach($expression)
	{
		return "<?php echo \$__env->renderEach{$expression}; ?>";
	}



	/**
	 * Compile the else statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileElse($expression)
	{
		return "<?php else: ?>";
	}

	/**
	 * Compile the for statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileFor($expression)
	{
		return "<?php for{$expression}: ?>";
	}

	/**
	 * Compile the foreach statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileForeach($expression)
	{
		return "<?php foreach{$expression}: ?>";
	}

	/**
	 * Compile the forelse statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileForelse($expression)
	{
		$empty = '$__empty_' . ++$this->forelseCounter;

		return "<?php {$empty} = true; foreach{$expression}: {$empty} = false; ?>";
	}

	/**
	 * Compile the if statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileIf($expression)
	{
		return "<?php if{$expression}: ?>";
	}

	/**
	 * Compile the else-if statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileElseif($expression)
	{
		return "<?php elseif{$expression}: ?>";
	}

	/**
	 * Compile the forelse statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */ 
	protected function compileEmpty($expression)
	{
		$empty = '$__empty_' . $this->forelseCounter--;

		return "<?php endforeach; if ({$empty}): ?>";
	}

	/**
	 * Compile the while statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileWhile($expression)
	{
		return "<?php while{$expression}: ?>";
	}

	/**
	 * Compile the end-while statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndwhile($expression)
	{
		return "<?php endwhile; ?>";
	}

	/**
	 * Compile the end-for statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndfor($expression)
	{
		return "<?php endfor; ?>";
	}

	/**
	 * Compile the end-for-each statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndforeach($expression)
	{
		return "<?php endforeach; ?>";
	}

	/**
	 * Compile the end-if statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndif($expression)
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile the end-for-else statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndforelse($expression)
	{
		return "<?php endif; ?>";
	}


	/**
	 * Compile the include statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileInclude($pathExpression)
	{
			$fileName = substr($pathExpression, strpos($pathExpression, '\'') + 1, -(strlen($pathExpression) - strripos($pathExpression, '\'')));

			$includeContent =  ViewHelper::getContents($fileName, true );

			return $includeContent;

		/*return "<?php echo \$__env->make($expression, array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";*/
	}


	/**
	 * Sets the content tags used for the compiler.
	 *
	 * @param  string  $openTag
	 * @param  string  $closeTag
	 * @param  bool    $escaped
	 * @return void
	 */
	public function setContentTags($openTag, $closeTag, $escaped = false)
	{
		$property = ($escaped === true) ? 'escapedTags' : 'contentTags';

		$this->{$property} = array(preg_quote($openTag), preg_quote($closeTag));
	}

	/**
	 * Sets the escaped content tags used for the compiler.
	 *
	 * @param  string  $openTag
	 * @param  string  $closeTag
	 * @return void
	 */
	public function setEscapedContentTags($openTag, $closeTag)
	{
		$this->setContentTags($openTag, $closeTag, true);
	}

	/**
	* Gets the content tags used for the compiler.
	*
	* @return string
	*/
	public function getContentTags()
	{
		return $this->contentTags;
	}

	/**
	* Gets the escaped content tags used for the compiler.
	*
	* @return string
	*/
	public function getEscapedContentTags()
	{
		return $this->escapedTags;
	}

}
