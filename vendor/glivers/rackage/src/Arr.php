<?php namespace Rackage;

/**
 * Array Manipulation Helper
 *
 * Provides fluent interface for array operations with method chaining.
 * This class wraps common PHP array functions in a chainable API that makes
 * complex array transformations readable and expressive.
 *
 * Architecture:
 *   Uses a hybrid pattern combining static factory methods and instance methods:
 *   - Static methods create new instances (parts, join, merge, exists)
 *   - Instance methods transform stored data and return $this for chaining
 *   - Final get() method retrieves the transformed result
 *
 * Internal Storage:
 *   Each instance stores intermediate results in the $output property.
 *   Every transformation method updates $output and returns $this.
 *
 * Two Usage Patterns:
 *
 *   1. Method Chaining (Fluent Interface):
 *      Result = Creator → Transform → Transform → Get
 *      Example: Arr::parts('/', 'user/123/edit')->clean()->trim()->get()
 *
 *   2. Direct Static Calls (Simple Operations):
 *      Example: Arr::exists('key', $array)
 *
 * Why Use This Over Native PHP Functions?
 *   - Readable: Arr::parts(',', $str)->clean()->trim()->get()
 *   - vs: array_values(array_filter(array_map('trim', explode(',', $str))))
 *   - Chainable: Multiple operations flow naturally left-to-right
 *   - Consistent: All array operations use same API
 *
 * Common Use Cases:
 *   - Parsing delimited strings: "user/123/edit" → ['user', '123', 'edit']
 *   - Cleaning user input: Remove empty values and trim whitespace
 *   - Route parsing: Split and clean URL segments
 *   - Data transformation: Flatten nested arrays, extract slices
 *
 * Example Flows:
 *
 *   Parse and clean URL:
 *     Arr::parts('/', '/user/123/edit')
 *        ->clean()      // Remove empty strings
 *        ->trim()       // Trim whitespace
 *        ->get();       // ['user', '123', 'edit']
 *
 *   Extract first element:
 *     Arr::parts(',', 'apple,banana,cherry')
 *        ->first()
 *        ->get();       // ['apple']
 *
 *   Flatten nested array:
 *     Arr::flatten([['a', 'b'], ['c', ['d']]])->get();  // ['a', 'b', 'c', 'd']
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Arr
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

class Arr {

	/**
	 * Internal storage for intermediate transformation results
	 * Updated by each chainable method, retrieved by get()
	 * @var mixed
	 */
	private $output;

	/**
	 * Private constructor to prevent direct instantiation
	 *
	 * Instances are created internally by static factory methods (parts, join, merge).
	 * Users should never call new Arr() directly.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning
	 *
	 * Maintains single transformation pipeline per instance.
	 */
	private function __clone() {}

	// ===========================================================================
	// STATIC CREATORS (Start a Chain or Standalone Use)
	// ===========================================================================

	/**
	 * Split string into array (explode wrapper)
	 *
	 * Static factory method that creates an Arr instance with the result of explode().
	 * Use this to start a transformation chain from a delimited string.
	 *
	 * Process:
	 * 1. Create new Arr instance
	 * 2. Explode string by delimiter
	 * 3. Store result in $output
	 * 4. Return instance for chaining
	 *
	 * Parameters:
	 *   - $limit null: Split on all occurrences
	 *   - $limit N: Split into maximum N elements
	 *
	 * Usage:
	 *   Arr::parts('/', 'user/123/edit')->get();
	 *   // ['user', '123', 'edit']
	 *
	 *   Arr::parts(',', 'a,b,c,d,e', 3)->get();
	 *   // ['a', 'b', 'c,d,e']
	 *
	 * Chaining example:
	 *   Arr::parts('/', '/user//123/')
	 *      ->clean()    // Remove empty strings from double slashes
	 *      ->trim()     // Clean whitespace
	 *      ->get();     // ['user', '123']
	 *
	 * @param string $delimiter Character(s) to split on
	 * @param string $string The string to split
	 * @param int|null $limit Maximum elements (null for unlimited)
	 * @return Arr Instance for method chaining
	 */
	public static function parts($delimiter, $string, $limit = null)
	{
		$instance = new static();

		if ($limit === null) {
			// Split on all occurrences
			$instance->output = explode($delimiter, $string);
		} else {
			// Split with limit
			$instance->output = explode($delimiter, $string, $limit);
		}

		return $instance;
	}

	/**
	 * Join array into string (implode wrapper)
	 *
	 * Static factory method that creates an Arr instance with the result of implode().
	 * Less commonly used for chaining since result is a string.
	 *
	 * Usage:
	 *   Arr::join(', ', ['apple', 'banana'])->get();
	 *   // 'apple, banana'
	 *
	 * @param string $glue String to insert between elements
	 * @param array $array The array to join
	 * @return Arr Instance for method chaining
	 */
	public static function join($glue, array $array)
	{
		$instance = new static();
		$instance->output = implode($glue, $array);
		return $instance;
	}

	/**
	 * Check if array key exists (array_key_exists wrapper)
	 *
	 * Static helper method - does NOT return Arr instance.
	 * Use for simple existence checks without chaining.
	 *
	 * Usage:
	 *   if (Arr::exists('id', $params)) {
	 *       // Key exists
	 *   }
	 *
	 * @param string|int $key The key to check
	 * @param array $array The array to search
	 * @return bool True if key exists, false otherwise
	 */
	public static function exists($key, array $array)
	{
		return array_key_exists($key, $array);
	}

	/**
	 * Merge two arrays (array_merge wrapper)
	 *
	 * Static factory method that creates an Arr instance with merged arrays.
	 * Use to start a chain or get merged result.
	 *
	 * Behavior:
	 *   - Numeric keys are renumbered
	 *   - String keys from $array2 override $array1
	 *
	 * Usage:
	 *   Arr::merge(['a' => 1], ['b' => 2])->get();
	 *   // ['a' => 1, 'b' => 2]
	 *
	 * @param array $array1 First array
	 * @param array $array2 Second array
	 * @return Arr Instance for method chaining
	 */
	public static function merge(array $array1, array $array2)
	{
		$instance = new static();
		$instance->output = array_merge($array1, $array2);
		return $instance;
	}

	// ===========================================================================
	// CHAINABLE TRANSFORMATIONS (Instance Methods)
	// ===========================================================================

	/**
	 * Remove empty values from array
	 *
	 * Filters out empty strings, nulls, false, and 0 using PHP's empty() check.
	 * Reindexes array with sequential numeric keys (0, 1, 2...).
	 *
	 * Process:
	 * 1. Apply array_filter to remove empty values
	 * 2. Apply array_values to reindex keys
	 * 3. Store result in $output
	 * 4. Return $this for chaining
	 *
	 * What's removed:
	 *   - Empty strings: ''
	 *   - Null values: null
	 *   - Boolean false: false
	 *   - Integer zero: 0
	 *   - String zero: '0'
	 *
	 * Usage:
	 *   Arr::parts('/', 'user//123//')
	 *      ->clean()
	 *      ->get();
	 *   // ['user', '123'] (empty strings removed)
	 *
	 * Common use case:
	 *   Cleaning URL segments that have double slashes or trailing slashes.
	 *
	 * @param array|null $array Optional array to clean (uses $output if null)
	 * @return Arr Returns $this for method chaining
	 */
	public function clean(array $array = null)
	{
		// Use provided array or stored output
		$input = ($array !== null) ? $array : $this->output;

		// Filter empty values and reindex
		$this->output = array_values(array_filter($input, function($item) {
			return !empty($item);
		}));

		return $this;
	}

	/**
	 * Trim whitespace from all string elements
	 *
	 * Applies PHP's trim() to every element in the array.
	 * Non-string elements are passed through array_map but trim only affects strings.
	 *
	 * Process:
	 * 1. Apply array_map with trim callback
	 * 2. Store result in $output
	 * 3. Return $this for chaining
	 *
	 * What's trimmed:
	 *   - Spaces: ' '
	 *   - Tabs: \t
	 *   - Newlines: \n, \r
	 *   - Null bytes: \0
	 *   - Vertical tabs: \x0B
	 *
	 * Usage:
	 *   Arr::parts(',', ' apple , banana , cherry ')
	 *      ->trim()
	 *      ->get();
	 *   // ['apple', 'banana', 'cherry'] (whitespace removed)
	 *
	 * Common use case:
	 *   Cleaning CSV data or user input split by delimiters.
	 *
	 * @param array|null $array Optional array to trim (uses $output if null)
	 * @return Arr Returns $this for method chaining
	 */
	public function trim(array $array = null)
	{
		// Use provided array or stored output
		$input = ($array !== null) ? $array : $this->output;

		// Apply trim to all elements
		$this->output = array_map(function($item) {
			return trim($item);
		}, $input);

		return $this;
	}

	/**
	 * Flatten multi-dimensional array into single dimension
	 *
	 * Recursively extracts all scalar values from nested arrays and objects.
	 * Produces a flat array with sequential numeric keys.
	 *
	 * Process:
	 * 1. Iterate through array
	 * 2. If element is array/object, recurse into it
	 * 3. If element is scalar, add to result
	 * 4. Store flattened result in $output
	 * 5. Return $this for chaining
	 *
	 * Usage:
	 *   $nested = [
	 *       ['a', 'b'],
	 *       ['c', ['d', 'e']],
	 *       'f'
	 *   ];
	 *   Arr::flatten($nested)->get();
	 *   // ['a', 'b', 'c', 'd', 'e', 'f']
	 *
	 * Common use case:
	 *   Processing hierarchical data structures into linear lists.
	 *
	 * Note:
	 *   All array keys are lost - only values are preserved.
	 *
	 * @param array|null $array Optional array to flatten (uses $output if null)
	 * @param array $return Internal accumulator for recursion (do not pass)
	 * @return Arr Returns $this for method chaining
	 */
	public function flatten(array $array = null, $return = [])
	{
		// Use provided array or stored output
		$input = ($array !== null) ? $array : $this->output;

		foreach ($input as $value) {
			if (is_array($value) || is_object($value)) {
				// Recurse into nested structure
				$return = $this->flatten($value, $return)->output;
			} else {
				// Add scalar value to result
				$return[] = $value;
			}
		}

		$this->output = $return;
		return $this;
	}

	/**
	 * Extract first element(s) from array
	 *
	 * Returns array containing only the first element.
	 * Result is always an array, not a scalar value.
	 *
	 * Process:
	 * 1. Use array_slice to extract first element
	 * 2. Store result in $output
	 * 3. Return $this for chaining
	 *
	 * Usage:
	 *   Arr::parts('/', 'user/123/edit')
	 *      ->first()
	 *      ->get();
	 *   // ['user'] (array with one element)
	 *
	 * To get scalar value:
	 *   Arr::parts('/', 'user/123/edit')
	 *      ->first()
	 *      ->get()[0];
	 *   // 'user' (string)
	 *
	 * Common use case:
	 *   Extracting first segment of URL or first item from CSV.
	 *
	 * @param array|null $array Optional array (uses $output if null)
	 * @return Arr Returns $this for method chaining
	 */
	public function first(array $array = null)
	{
		// Use provided array or stored output
		$input = ($array !== null) ? $array : $this->output;

		// Extract first element (result is array)
		$this->output = array_slice($input, 0, 1);

		return $this;
	}

	/**
	 * Extract a slice of the array
	 *
	 * Returns portion of array specified by offset and length.
	 * Wrapper for array_slice with chainable interface.
	 *
	 * Parameters:
	 *   - $offset: Start position (negative counts from end)
	 *   - $length: Number of elements (null for rest of array)
	 *   - $preserveKeys: Keep original keys (default false - reindex)
	 *
	 * Usage:
	 *   Arr::parts('/', 'a/b/c/d/e')
	 *      ->slice(1, 3)
	 *      ->get();
	 *   // ['b', 'c', 'd'] (skip first, take 3)
	 *
	 *   Arr::parts('/', 'a/b/c/d/e')
	 *      ->slice(-2)
	 *      ->get();
	 *   // ['d', 'e'] (last 2 elements)
	 *
	 * Common use case:
	 *   Extracting URL parameters after controller/method.
	 *   URL: /user/show/123/edit → slice(2) → ['123', 'edit']
	 *
	 * @param array|null $array Optional array (uses $output if null)
	 * @param int|null $offset Start position
	 * @param int|null $length Number of elements (null for rest)
	 * @param bool $preserveKeys Keep original keys (default false)
	 * @return Arr Returns $this for method chaining
	 */
	public function slice(array $array = null, $offset = null, $length = null, $preserveKeys = false)
	{
		// Use provided array or stored output
		$input = ($array !== null) ? $array : $this->output;

		// Extract slice
		$this->output = array_slice($input, $offset, $length, $preserveKeys);

		return $this;
	}

	// ===========================================================================
	// OUTPUT
	// ===========================================================================

	/**
	 * Retrieve the final result
	 *
	 * Terminates the method chain and returns the transformed data.
	 * This is the only way to extract the result from an Arr instance.
	 *
	 * Usage:
	 *   $result = Arr::parts('/', 'a/b/c')
	 *                ->clean()
	 *                ->trim()
	 *                ->get();  // ← Must call to get result
	 *
	 * Note:
	 *   Without calling get(), you have an Arr object, not the actual data.
	 *
	 * @return mixed The transformed data (usually an array or string)
	 */
	public function get()
	{
		return $this->output;
	}

}
