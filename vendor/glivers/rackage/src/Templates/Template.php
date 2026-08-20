<?php namespace Rackage\Templates;

/**
 * Template Compiler
 *
 * This class handles ALL template compilation for Rachie's template engine.
 * It parses template files and converts template syntax into executable PHP code.
 *
 * WHAT IT DOES:
 * - Validates template file existence
 * - Defines template syntax rules ({{ }}, {{{ }}}, @directives)
 * - Parses template files using PHP's token_get_all()
 * - Converts template directives (@if, @foreach, etc.) into PHP control structures
 * - Converts echo tags ({{ }}, {{{ }}}) into PHP echo statements
 * - Handles inline file inclusion via @include
 * - Handles layout inheritance via @extends, @section, @endsection, @parent
 *
 * SUPPORTED TEMPLATE SYNTAX:
 * - Echo (escaped, secure by default): {{ $variable }}
 * - Echo (raw, unescaped): {{{ $html }}}
 * - Echo with default: {{ $name or 'Guest' }}
 * - Escape directives: @@if → outputs literal @if
 * - Control structures: @if, @else, @elseif, @endif
 * - Variable checks: @isset, @endisset
 * - Loops: @for, @foreach, @endforeach, @while, @endwhile
 * - Special loop: @loopelse / @empty / @endloop (foreach with empty fallback)
 * - File inclusion: @include('path/to/file')
 * - Layout inheritance: @extends('layout'), @section, @endsection, @parent, @yield
 *
 * COMPILATION PROCESS:
 * 1. Validate file exists
 * 2. ASSEMBLE - Recursively merge all parent layouts (@extends)
 * 3. COMPILE - Convert template syntax to PHP (@if, {{ }}, etc.)
 * 4. Return compiled PHP code
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Templates
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

use Rackage\View;
use Rackage\Path;
use Rackage\Registry;
use Rackage\Templates\TemplateException;

class Template {

	/**
	 * The file currently being compiled.
	 * @var string
	 */
	protected $path;

	/**
	 * All of the available compiler functions.
	 * These are the "passes" the compiler makes over the template.
	 * @var array
	 */
	protected $compilers = array(
		'Statements',  // Handles @directives
		'Echos'        // Handles {{ }} and {{{ }}}
	);

	/**
	 * Array of opening and closing tags for ESCAPED echos (default, secure).
	 * Loaded from config: 'template_echo_tags'
	 * Default: {{ }}
	 * @var array
	 */
	protected $echoTags = null;

	/**
	 * Array of opening and closing tags for RAW/UNESCAPED echos.
	 * Loaded from config: 'template_raw_tags'
	 * Default: {{{ }}}
	 * @var array
	 */
	protected $rawEchoTags = null;

	/**
	 * Counter to keep track of nested @loopelse statements.
	 * @var int
	 */
	protected $loopelseCounter = 0;

	/**
	 * Sections defined in the current template (for layout inheritance).
	 * @var array
	 */
	protected $sections = array();

	/**
	 * Name of the parent layout being extended (for layout inheritance).
	 * @var string|null
	 */
	protected $layout = null;

	/**
	 * Entry point - Parse and compile the provided view file
	 *
	 * @param string $path The path to the file to compile
	 * @param mixed $embeded Whether this is an embedded/included view
	 * @param string $fileName The file name for error messages
	 * @return string The compiled PHP code
	 */
	public function compiled($path, $embeded, $fileName)
	{
		try {
			// Check if this file exists
			if (!file_exists($path)) {
				throw new TemplateException("The view file named '$fileName' cannot be found!", 1);
			}

			// Set the file path
			$this->path = $path;

			// Compile this template
			$compiled = $this->compile();

			// Return the compiled contents
			return $compiled;

		} 
		catch (TemplateException $exception) { throw $exception; }
	}

	/**
	 * Initialize the template tags from config.
	 *
	 * @return void
	 */
	protected function initializeTags()
	{
		// Only initialize once
		if ($this->echoTags !== null) {
			return;
		}

		// Load tags from config, with fallback to defaults
		$config = Registry::settings();

		$this->echoTags = isset($config['template_echo_tags'])
			? $config['template_echo_tags']
			: array('{{', '}}');

		$this->rawEchoTags = isset($config['template_raw_tags'])
			? $config['template_raw_tags']
			: array('{{{', '}}}');
	}

	/**
	 * Compile the view at the given path.
	 * This is the MAIN compilation entry point.
	 *
	 * PROCESS:
	 * 1. Initialize template tags from config
	 * 2. Reset state for this compilation
	 * 3. ASSEMBLE the complete document (handles @extends recursively)
	 * 4. COMPILE the assembled document (handles @if, {{ }}, etc.)
	 * 5. Return the compiled PHP code
	 *
	 * @param string $path (Not used - we use $this->path instead)
	 * @return string The compiled PHP code
	 */
	public function compile($path = null)
	{
		// Initialize template tags from config
		$this->initializeTags();

		// Reset state for this compilation
		$this->layout = null;
		$this->sections = array();

		// STEP 1: ASSEMBLY - Recursively merge all layouts
		$assembledContent = $this->assembleTemplate($this->path);

		// STEP 2: COMPILATION - Convert template syntax to PHP
		return $this->compileString($assembledContent);
	}

	/**
	 * Recursively assemble template with all parent layouts.
	 *
	 * Handles UNLIMITED nesting depth:
	 * child → parent → grandparent → great-grandparent → ...
	 *
	 * @param string $filePath Full path to template file
	 * @return string The assembled template content
	 */
	protected function assembleTemplate($filePath)
	{
		// Read the raw template file
		$contents = file_get_contents($filePath);

		// Check if this template extends a parent layout
		if (preg_match('/^\s*(?<!@)@extends\(\s*[\'"]([^\'"]+)[\'"]\s*\)/m', $contents, $match))
		{
			$layoutName = $match[1];

			// Extract all sections from THIS file
			$childSections = $this->extractRawSections($contents);

			// Get parent layout path
			$parentPath = Path::view($layoutName);

			// RECURSIVE: Assemble the parent layout
			$parentAssembled = $this->assembleTemplate($parentPath);

			// Inject this file's sections into the assembled parent
			$assembledContent = $this->injectSectionsIntoLayout($parentAssembled, $childSections);

			return $assembledContent;
		}

		// No @extends - return content as-is
		return $contents;
	}

	/**
	 * Extract raw section content from child template.
	 *
	 * @param string $content The raw template content
	 * @return array Associative array of section names => content
	 */
	protected function extractRawSections($content)
	{
		$sections = array();

		// Remove @extends line
		$content = preg_replace('/^\s*(?<!@)@extends\(\s*[\'"][^\'"]+[\'"]\s*\)\s*/m', '', $content);

		// PATTERN 1: Inline sections - @section('name', 'content')
		if (preg_match_all('/(?<!@)@section\(\s*[\'"]([^\'\"]+)[\'"]\s*,\s*[\'"]([^\'\"]*?)[\'"]\s*\)/s', $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$sectionName = $match[1];
				$sectionContent = $match[2];

				// Unescape quotes
				$sectionContent = str_replace("\\'", "'", $sectionContent);
				$sectionContent = str_replace('\\"', '"', $sectionContent);

				$sections[$sectionName] = $sectionContent;
			}
		}

		// PATTERN 2: Block sections - @section('name') ... @endsection
		if (preg_match_all('/(?<!@)@section\(\s*[\'"]([^\'\"]+)[\'"]\s*\)(.*?)(?<!@)@endsection/s', $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$sectionName = $match[1];
				$sectionContent = trim($match[2]);

				// Check if section contains @parent
				if (preg_match('/(?<!@)@parent(?:\s|$)/', $sectionContent)) {
					$sections[$sectionName] = array(
						'content' => $sectionContent,
						'has_parent' => true
					);
				} else {
					$sections[$sectionName] = $sectionContent;
				}
			}
		}

		return $sections;
	}

	/**
	 * Inject child sections into parent layout placeholders.
	 *
	 * @param string $layoutContent The raw parent layout content
	 * @param array $childSections Extracted sections from child
	 * @return string The layout with child sections injected
	 */
	protected function injectSectionsIntoLayout($layoutContent, $childSections)
	{
		// Process each section defined in the child
		foreach ($childSections as $name => $content) {

			// Handle sections that use @parent directive
			if (is_array($content) && isset($content['has_parent'])) {
				// Extract parent's default content
				$parentContent = $this->extractParentSectionContent($layoutContent, $name);

				// Replace @parent with parent content
				$childContent = preg_replace(
					'/(?<!@)@parent(?:\s|$)/',
					$parentContent . ' ',
					$content['content']
				);
				$content = $childContent;
			}

			// Replace empty placeholder @section('name'):
			$pattern1 = '/(?<!@)@section\(\s*[\'"]' . preg_quote($name, '/') . '[\'"]\s*\):/';
			$layoutContent = preg_replace_callback($pattern1, function() use ($content) {
			return $content;
		}, $layoutContent);

			// Replace block section @section('name') ... @endsection
			$pattern2 = '/(?<!@)@section\(\s*[\'"]' . preg_quote($name, '/') . '[\'"]\s*\).*?(?<!@)@endsection/s';
			$layoutContent = preg_replace_callback($pattern2, function() use ($content) {
			return $content;
		}, $layoutContent);

			// Replace @yield('name') - cleaner alternative to @section('name'):
			$pattern3 = '/(?<!@)@yield\(\s*[\'"]' . preg_quote($name, '/') . '[\'"]\s*\)/';
			$layoutContent = preg_replace_callback($pattern3, function() use ($content) {
			return $content;
		}, $layoutContent);
		}

		// Clean up: Remove remaining empty placeholders
		$layoutContent = preg_replace('/(?<!@)@section\(\s*[\'"][^\'"]+[\'"]\s*\):/', '', $layoutContent);

		// Clean up: Remove remaining @yield with no matching section
		$layoutContent = preg_replace('/(?<!@)@yield\(\s*[\'"][^\'"]+[\'"]\s*\)/', '', $layoutContent);

		// Clean up: Keep default content for undefined sections
		$layoutContent = preg_replace_callback(
			'/(?<!@)@section\(\s*[\'"]([^\'"]+)[\'"]\s*\)(.*?)(?<!@)@endsection/s',
			function($matches) {
				return trim($matches[2]);
			},
			$layoutContent
		);

		return $layoutContent;
	}

	/**
	 * Extract parent's default content for a section (for @parent directive).
	 *
	 * @param string $layoutContent The parent layout raw content
	 * @param string $sectionName The section name to extract
	 * @return string The parent's content for this section
	 */
	protected function extractParentSectionContent($layoutContent, $sectionName)
	{
		$pattern = '/(?<!@)@section\(\s*[\'"]' . preg_quote($sectionName, '/') . '[\'"]\s*\)(.*?)(?<!@)@endsection/s';

		if (preg_match($pattern, $layoutContent, $match)) {
			return trim($match[1]);
		}

		return '';
	}

	/**
	 * Compile the given template string.
	 *
	 * @param string $string The raw template content
	 * @return string The compiled PHP code
	 */
	public function compileString($string)
	{
		$result = '';

		foreach (token_get_all($string) as $token)
		{
			$result .= is_array($token) ? $this->parseToken($token) : $token;
		}

		return $result;
	}

	/**
	 * Parse individual tokens from the template.
	 *
	 * @param array $token Format: [token_id, content, line_number]
	 * @return string The compiled content for this token
	 */
	protected function parseToken($token)
	{
		list($id, $content) = $token;

		// Only process HTML tokens (where our template syntax lives)
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
	 * Compile echo statements into valid PHP.
	 *
	 * @param string $value The template content to process
	 * @return string Content with echo tags converted to PHP
	 */
	protected function compileEchos($value)
	{
		$difference = strlen($this->echoTags[0]) - strlen($this->rawEchoTags[0]);

		if ($difference > 0)
		{
			return $this->compileRawEchos($this->compileEscapedEchos($value));
		}

		return $this->compileEscapedEchos($this->compileRawEchos($value));
	}

	/**
	 * Compile directive statements that start with "@"
	 *
	 * @param string $value The template content
	 * @return string Content with directives converted to PHP
	 */
	protected function compileStatements($value)
	{
		$callback = function($match)
		{
			// Handle escaped directives (@@)
			if (isset($match[1]) && $match[1] === '@')
			{
				return substr($match[0], 1);
			}

			// Handle @section('name'): (empty placeholder with colon)
			if ($match[2] === 'section' && isset($match[5]) && $match[5] === ':')
			{
				return $this->compileEmptySection(isset($match[4]) ? $match[4] : '');
			}

			// Check if a compile method exists for this directive
			if (method_exists($this, $method = 'compile'.ucfirst($match[2])))
			{
				$match[0] = $this->$method(isset($match[4]) ? $match[4] : '');
			}

			return isset($match[4]) ? $match[0] : $match[0].$match[3];
		};

		return preg_replace_callback('/(@)?\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?4) )* \))?(:)?/x', $callback, $value);
	}

	/**
	 * Compile the ESCAPED echo statements (secure by default).
	 *
	 * @param string $value Template content
	 * @return string Content with {{ }} converted to escaped echo
	 */
	protected function compileEscapedEchos($value)
	{
		$pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', preg_quote($this->echoTags[0]), preg_quote($this->echoTags[1]));

		$callback = function($matches)
		{
			$whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

			return $matches[1] ? substr($matches[0], 1) : '<?php echo HTML::escape('.$this->compileEchoDefaults($matches[2]).'); ?>'.$whitespace;
		};

		return preg_replace_callback($pattern, $callback, $value);
	}

	/**
	 * Compile the RAW/UNESCAPED echo statements.
	 *
	 * @param string $value Template content
	 * @return string Content with {{{ }}} converted to raw echo
	 */
	protected function compileRawEchos($value)
	{
		$pattern = sprintf('/%s\s*(.+?)\s*%s(\r?\n)?/s', preg_quote($this->rawEchoTags[0]), preg_quote($this->rawEchoTags[1]));

		$callback = function($matches)
		{
			$whitespace = empty($matches[2]) ? '' : $matches[2].$matches[2];

			return '<?php echo '.$this->compileEchoDefaults($matches[1]).'; ?>'.$whitespace;
		};

		return preg_replace_callback($pattern, $callback, $value);
	}

	/**
	 * Compile default values for echo statements.
	 *
	 * @param string $value The content inside {{ }}
	 * @return string Content with "or" converted to ternary operator
	 */
	public function compileEchoDefaults($value)
	{
		return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
	}

	/**
	 * Compile @else statements.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileElse($expression)
	{
		return "<?php else: ?>";
	}

	/**
	 * Compile @for loops.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileFor($expression)
	{
		return "<?php for{$expression}: ?>";
	}

	/**
	 * Compile @foreach loops.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileForeach($expression)
	{
		return "<?php foreach{$expression}: ?>";
	}

	/**
	 * Compile @loopelse statements.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileLoopelse($expression)
	{
		$noitems = '$__noitems_' . ++$this->loopelseCounter;

		return "<?php {$noitems} = true; foreach{$expression}: {$noitems} = false; ?>";
	}

	/**
	 * Compile @if statements.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileIf($expression)
	{
		return "<?php if{$expression}: ?>";
	}

	/**
	 * Compile @isset statements
	 *
	 * Convenience directive to check if a variable is set and not null.
	 * Equivalent to PHP's isset() function.
	 *
	 * Examples:
	 *   @isset($user)
	 *       <p>Welcome, {{ $user->name }}</p>
	 *   @endisset
	 *
	 *   @isset($post->author)
	 *       <span>By {{ $post->author }}</span>
	 *   @endisset
	 *
	 * Multiple variables:
	 *   @isset($user, $posts)
	 *       <p>User has {{ count($posts) }} posts</p>
	 *   @endisset
	 *
	 * @param string $expression The isset expression with variable(s) to check
	 * @return string Compiled PHP code
	 */
	protected function compileIsset($expression)
	{
		return "<?php if(isset{$expression}): ?>";
	}

	/**
	 * Compile @endisset statements
	 *
	 * Closes an @isset block.
	 *
	 * @param string $expression Not used
	 * @return string Compiled PHP code
	 */
	protected function compileEndisset($expression)
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile @elseif statements.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileElseif($expression)
	{
		return "<?php elseif{$expression}: ?>";
	}

	/**
	 * Compile @empty statements (used with @loopelse).
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileEmpty($expression)
	{
		$noitems = '$__noitems_' . $this->loopelseCounter--;

		return "<?php endforeach; if ({$noitems}): ?>";
	}

	/**
	 * Compile @while loops.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileWhile($expression)
	{
		return "<?php while{$expression}: ?>";
	}

	/**
	 * Compile @endwhile statements.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileEndwhile($expression)
	{
		return "<?php endwhile; ?>";
	}

	/**
	 * Compile @endfor statements.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileEndfor($expression)
	{
		return "<?php endfor; ?>";
	}

	/**
	 * Compile @endforeach statements.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileEndforeach($expression)
	{
		return "<?php endforeach; ?>";
	}

	/**
	 * Compile @endif statements.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileEndif($expression)
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile @endloop statements.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileEndloop($expression)
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile @php statements.
	 *
	 * Opens a raw PHP block. Use for complex logic that doesn't
	 * fit cleanly into template directives.
	 *
	 * Usage:
	 *   @php
	 *       $total = array_sum($prices);
	 *       $formatted = number_format($total, 2);
	 *   @endphp
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compilePhp($expression)
	{
		return '<?php ';
	}

	/**
	 * Compile @endphp statements.
	 *
	 * Closes a raw PHP block opened with @php.
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileEndphp($expression)
	{
		return ' ?>';
	}

	/**
	 * Compile @break statements.
	 *
	 * Breaks out of a loop. Can be used with optional condition.
	 *
	 * Usage:
	 *   @foreach($users as $user)
	 *       @break($user->id === $targetId)
	 *       {{ $user->name }}
	 *   @endforeach
	 *
	 * Or unconditionally:
	 *   @break
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileBreak($expression)
	{
		if ($expression) {

			return "<?php if{$expression}: break; endif; ?>";
		}

		return '<?php break; ?>';
	}

	/**
	 * Compile @continue statements.
	 *
	 * Skips to next iteration of a loop. Can be used with optional condition.
	 *
	 * Usage:
	 *   @foreach($users as $user)
	 *       @continue($user->inactive)
	 *       {{ $user->name }}
	 *   @endforeach
	 *
	 * Or unconditionally:
	 *   @continue
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileContinue($expression)
	{
		if ($expression) {

			return "<?php if{$expression}: continue; endif; ?>";
		}

		return '<?php continue; ?>';
	}

	/**
	 * Compile @include statements.
	 *
	 * @param string $pathExpression
	 * @return string
	 */
	protected function compileInclude($pathExpression)
	{
		// Extract the filename from the expression
		$fileName = substr($pathExpression, strpos($pathExpression, '\'') + 1, -(strlen($pathExpression) - strripos($pathExpression, '\'')));

		// Get the compiled contents of the included file
		$includeContent = View::getContents($fileName, true);

		return $includeContent;
	}

	/**
	 * Compile empty section placeholders @section('name'):
	 * This is a stub - the actual logic is in compileStatements
	 *
	 * @param string $expression
	 * @return string
	 */
	protected function compileEmptySection($expression)
	{
		// This is handled during assembly, so we just return empty string
		// The actual replacement happens in injectSectionsIntoLayout()
		return '';
	}
}
