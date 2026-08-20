<?php namespace Rackage\Database\MySQL;
 
/**
 * MySQL Query Builder
 *
 * This class handles MySQL-specific query construction and execution.
 * It is the implementation layer that Model.php delegates to for all database operations.
 *
 * Architecture:
 *   - Model.php provides static methods for developer-facing API
 *   - Model.php delegates to this class via Query() singleton
 *   - This class builds SQL query strings from method calls
 *   - Query strings are executed via MySQLConnector
 *   - Results are returned via MySQLResponse
 *
 * Query Building Pattern:
 *   1. Query builder methods (setTable, where, order, etc.) store parameters in properties
 *   2. Build methods (buildSelect, buildInsert, etc.) compose SQL from stored properties
 *   3. Execution methods (all, first, save, delete) build SQL and execute via connector
 *   4. Response object contains results, metadata, and query performance metrics
 * 
 * Security:
 *   All values are escaped via quote() method which uses MySQLConnector->escape()
 *   WHERE clause uses sprintf with %s placeholders - values are quoted before sprintf
 *   This prevents SQL injection while maintaining query flexibility
 *
 * Example Flow:
 *   Posts::where('status', 'published')->order('created_at', 'desc')->all()
 *     → Model::where() calls MySQLQuery->where(['status', 'published'])
 *     → Model::order() calls MySQLQuery->order('created_at', 'desc')
 *     → Model::all() calls MySQLQuery->all()
 *       → all() calls buildSelect() to compose SQL
 *       → Executes: SELECT * FROM posts WHERE status='published' ORDER BY created_at desc
 *       → Returns MySQLResponse with results
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Database
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

use Rackage\Arr;
use Rackage\Database\MySQL\MySQLAsync;
use Rackage\Database\MySQL\MySQLResponse;
use Rackage\Database\DatabaseException;

class MySQLQuery
{

	// =========================================================================
	// PROPERTIES
	// =========================================================================

	/**
	 * Table to work on
	 * @var string
	 */
	protected $table;

	/**
	 * Timestamps flag
	 * @var bool
	 */
	protected $timestamps;

	/**
	 * MySQL connection instance
	 * @var object
	 */
	protected $connector;

	/**
	 * Table name for query
	 * @var string
	 */
	protected $from; 

	/**
	 * Fields to select (multidimensional array: table => [fields])
	 * @var array
	 */
	protected $fields = [];

	/**
	 * Maximum number of rows to return
	 * @var int
	 */
	protected $limit;

	/**
	 * Row offset for pagination
	 * @var int
	 */
	protected $offset;

	/**
	 * ORDER BY clauses (array of ['column' => 'direction'])
	 * @var array
	 */
	protected $order = array();

	/**
	 * DISTINCT keyword for unique results
	 * @var string
	 */
	protected $distinct = ' ';

	/**
	 * GROUP BY columns
	 * @var array
	 */
	protected $group = array();

	/**
	 * HAVING conditions (for grouped queries)
	 * @var array
	 */
	protected $having = array();

	/**
	 * JOIN clauses (tables and conditions)
	 * @var array
	 */
	protected $join = array();

	/**
	 * WHERE conditions (stores ['condition' => ..., 'operator' => 'AND'|'OR'])
	 * @var array
	 */
	protected $where = array();

	/**
	 * Query string property; stores dynamically generated queries
	 *
	 */
	protected $query_string;

	/**
	 * SQL debug mode flag - when true, return SQL instead of executing
	 * @var bool
	 */
	protected $returnSql = false;

	/**
	 * Row-level lock clause (FOR UPDATE, FOR SHARE, etc.)
	 * @var string
	 */
	protected $lockClause = '';

	/**
	 * Unbuffered query execution flag
	 *
	 * When true, uses MYSQLI_USE_RESULT to stream rows one at a time
	 * instead of loading entire result set into memory.
	 *
	 * @var bool
	 */
	protected $unbuffered = false;

	/**
	 * Async mode flag
	 *
	 * When true, terminal methods (all, first, count, etc.) fire query
	 * with MYSQLI_ASYNC and return a Promise instead of blocking.
	 *
	 * @var bool
	 */
	protected $async = false;

	// =========================================================================
	// CONSTRUCTOR
	// =========================================================================

	/**
	 * Constructor - Initialize query builder
	 *
	 * Sets up the MySQL connection and response object.
	 *
	 * @param array $instance Array containing 'connector' key with MySQLConnector instance
	 * @return void
	 */
	public function __construct(array $instance, $table, $timestamps)
	{
		// Store connection instance
		$this->connector = $instance['connector'];

		$this->table = $table;
		$this->timestamps = $timestamps;

	}


	/**
	 * Enable SQL debug mode
	 *
	 * When enabled, exit methods (all, first, save, delete, etc.) return
	 * the SQL query string instead of executing it. Use for debugging.
	 *
	 * Examples:
	 *   UserModel::toSql()->where('status', 'active')->all()
	 *   → "SELECT users.* FROM users WHERE status = 'active'"
	 *
	 *   UserModel::toSql()->where('id', 5)->save(['status' => 'banned'])
	 *   → "UPDATE users SET status = 'banned' WHERE id = 5"
	 *
	 * @return $this For method chaining
	 */
	public function toSql()
	{
		$this->returnSql = true;
		return $this;
	}

	/**
	 * Enable streaming query execution (unbuffered mode)
	 *
	 * Streams results row-by-row instead of loading entire result set into memory.
	 * Uses MySQL's MYSQLI_USE_RESULT flag to enable unbuffered mode.
	 * Essential for processing large datasets (millions of rows) without memory exhaustion.
	 *
	 * Memory comparison:
	 *   Buffered (default): 100M rows × 60 bytes = 6 GB in memory
	 *   Unbuffered (stream): 1 row × 60 bytes = 60 bytes in memory at a time
	 *
	 * Use for:
	 *   - Large result sets (millions of rows)
	 *   - Building graphs/structures incrementally
	 *   - Export operations
	 *
	 * Do NOT use for:
	 *   - Small result sets (< 10K rows)
	 *   - When you need result count before processing
	 *   - Multiple concurrent queries on same connection
	 *
	 * Examples:
	 *   $result = LinkModel::stream()->select(['source', 'target'])->all();
	 *   while ($row = $result->fetch_assoc()) {
	 *       // Process one row at a time (minimal memory)
	 *   }
	 *
	 * @return $this For method chaining
	 */
	public function stream()
	{
		$this->unbuffered = true;
		return $this;
	}

	/**
	 * Enable async query execution
	 *
	 * When enabled, terminal methods (all, first, count, delete, save)
	 * fire the query with MYSQLI_ASYNC and return a Promise instead
	 * of blocking for the result.
	 *
	 * Call await() on the Promise to get the actual result.
	 *
	 * Limitations:
	 *   - One async query at a time per connection
	 *   - Must await() before starting another async query
	 *   - Cannot be combined with stream() (unbuffered mode)
	 *
	 * Examples:
	 *   // Fire query, do other work, then get result
	 *   $promise = PageModel::async()->where('status', 'active')->all();
	 *   $otherData = doSomethingElse();
	 *   $pages = $promise->await();
	 *
	 *   // Check if ready without blocking
	 *   if ($promise->ready()) {
	 *       $pages = $promise->await();
	 *   }
	 *
	 * @return $this For method chaining
	 */
	public function async()
	{
		$this->async = true;
		return $this;
	}

	// =========================================================================
	// SECURITY / ESCAPING
	// =========================================================================

	/**
	 * Quote and escape values for MySQL
	 *
	 * Escapes and quotes input data according to MySQL requirements.
	 * Handles strings, integers, arrays, nulls, booleans, and empty values.
	 *
	 * Security:
	 *   Uses MySQLConnector->escape() for proper escaping.
	 *   Prevents SQL injection by sanitizing all values.
	 *
	 * Examples:
	 *   quote('hello')        → 'hello'
	 *   quote(123)            → '123'
	 *   quote(['a', 'b'])     → ('a', 'b')
	 *   quote(null)           → NULL
	 *   quote(true)           → 1
	 *
	 * @param mixed $value Value to quote (string, int, array, null, bool)
	 * @return mixed Quoted and escaped value
	 */
	public function quote($value)
	{
		// String or integer - escape and quote
		if (is_string($value) || is_int($value))
		{
			$escaped = $this->connector->escape($value);

			return "'{$escaped}'";
		}

		// Array - quote each element and join
		elseif (is_array($value))
		{
			$buffer = array();

			foreach ($value as $i)
			{
				// Recursively quote each element
				array_push($buffer, $this->quote($i));
			}

			// Join array elements
			$buffer = join(", ", $buffer);

			// Return in parentheses
			return "({$buffer})";
		}

		// NULL value
		elseif (is_null($value))
		{
			return 'NULL';
		}

		// Boolean - convert to integer
		elseif (is_bool($value))
		{
			return (int)$value;
		}

		// Empty value
		elseif (empty($value))
		{
			return "' '";
		}

		// Fallback - escape and return
		else
		{
			return $this->connector->escape($value);
		}
	}

	// =========================================================================
	// QUERY BUILDER METHODS (Set Parameters)
	// =========================================================================

	/**
	 * Set table name for query
	 *
	 * Specifies which table to query.
	 * Automatically initializes fields array with '*' (all columns).
	 *
	 * @param string $table Table name
	 * @return $this For method chaining
	 * @throws DatabaseException If table name is empty
	 */
	public function setTable($table)
	{
		// Validate table name is not empty
		if (empty($table)) throw new DatabaseException("Invalid argument passed for table name", 1);

		// Set table name
		$this->from = $table;

		// Initialize fields if not set
		if(!isset($this->fields[$table])) $this->fields[$table] = array("*");

		return $this;
	}

	/**
	 * Set columns to select from table
	 *
	 * Specifies which columns to retrieve from the table.
	 *
	 * @param string $table Table name
	 * @param array $fields Column names to select (default: all columns)
	 * @return void
	 * @throws DatabaseException If table name is empty
	 */
	public function setFields($table, $fields = array("*"))
	{
		// Validate table name is not empty
		if (empty($table))
		{
			throw new DatabaseException("Invalid argument passed for table name", 1);
		}

		// Set table and fields
		$this->from = $table;
		$this->fields[$table] = $fields;

		return $this;
	}

	/**
	 * Chainable select method for specifying columns
	 *
	 * This is a convenience wrapper around setFields() that allows
	 * select() to be chained after other query methods like whereIn().
	 *
	 * Usage:
	 *   // Chained after whereIn()
	 *   Model::whereIn('id', [1,2,3])->select(['id', 'name'])->all();
	 *
	 * @param array $fields Column names to select (default: all columns)
	 * @return MySQLQuery Returns $this for method chaining
	 */
	public function select($fields = array("*"))
	{
		// Delegate to setFields() using the current table
		return $this->setFields($this->from, $fields);
	}

	/**
	 * Add LEFT JOIN to query
	 *
	 * Joins another table to retrieve related data.
	 * Supports multiple joins by calling this method multiple times.
	 *
	 * Examples:
	 *   leftJoin('users', 'posts.user_id = users.id', ['users.name'])
	 *   leftJoin('categories', 'posts.category_id = categories.id', ['*'])
	 *
	 * @param string $table Table name to join
	 * @param string $condition Join condition (e.g., 'posts.user_id = users.id')
	 * @param array $fields Columns to select from joined table
	 * @return $this For method chaining
	 * @throws DatabaseException If table or condition is empty
	 */
	public function leftJoin($table, $condition, $fields = array("*"))
	{
		// Validate table is not empty
		if (empty($table))
		{
			throw new DatabaseException("Invalid table argument $table passed for the leftJoin Clause", 1);
		}

		// Validate condition is not empty
		if (empty($condition))
		{
			throw new DatabaseException("Invalid argument $condition passed for the leftJoin Clause", 1);
		}

		// Add fields for this table
		$this->fields += array($table => $fields);

		// Store join table, condition, and type
		$this->join['tables'][] = $table;
		$this->join['conditions'][] = $condition;
		$this->join['types'][] = 'LEFT';  // Mark as LEFT JOIN

		return $this;
	}

	/**
	 * Set row limit with pagination
	 *
	 * Limits the number of rows returned by the query.
	 * Supports pagination by calculating offset from page number.
	 *
	 * Examples:
	 *   limit(10)      → LIMIT 10
	 *   limit(20, 1)   → LIMIT 0, 20 (page 1)
	 *   limit(20, 2)   → LIMIT 20, 20 (page 2)
	 *   limit(20, 3)   → LIMIT 40, 20 (page 3)
	 *
	 * @param int $limit Maximum number of rows to return
	 * @param int $page Page number for pagination (default: 1)
	 * @return $this For method chaining
	 * @throws DatabaseException If limit is empty
	 */
	public function limit($limit, $page = 1)
	{
		// Validate limit is not empty
		if (empty($limit)) {

			throw new DatabaseException("Empty argument passed for $limit in method limit()", 1);
		}

		// Cast to int to prevent injection
		$limit 	= (int) $limit;
		$page 	= (int) $page;

		// Set limit
		$this->limit = $limit;

		// Calculate offset for pagination
		$this->offset = (int)$limit * ($page - 1);

		return $this;
	}

	/**
	 * Set DISTINCT to return only unique results
	 *
	 * Adds DISTINCT keyword to query to eliminate duplicate rows.
	 *
	 * @return $this For method chaining
	 */
	public function unique()
	{
		$this->distinct = ' DISTINCT ';

		return $this;
	}

	/**
	 * Add ORDER BY clause
	 *
	 * Specifies how to sort query results.
	 * Supports multiple order clauses - call multiple times for multi-column sorting.
	 *
	 * Examples:
	 *   order('created_at', 'desc')  → ORDER BY created_at desc
	 *   order('name', 'asc')         → ORDER BY name asc
	 *
	 *   // Multiple columns
	 *   order('featured', 'desc')->order('created_at', 'desc')
	 *   → ORDER BY featured desc, created_at desc
	 *
	 * @param string $order Column name to sort by
	 * @param string $direction Sort direction: 'asc' or 'desc' (default: 'asc')
	 * @return $this For method chaining
	 * @throws DatabaseException If order is empty
	 */
	public function order($order, $direction = 'asc')
	{
		// Validate order field is not empty
		if (empty($order))
		{
			throw new DatabaseException("Empty value passed for parameter $order in order() method", 1);
		}

		// Add order clause to array (supports multiple ORDER BY)
		$this->order[$order] = $direction;

		return $this;
	}

	/**
	 * Add WHERE conditions
	 *
	 * Builds WHERE clause from arguments.
	 * Supports multiple WHERE calls - they are combined with AND.
	 *
	 * Usage Patterns:
	 *   where(['status', 'published'])
	 *     → WHERE status='published'
	 *
	 *   where(['price > ?', 100])
	 *     → WHERE price > 100
	 *
	 *   where(['status = ? AND views > ?', 'published', 1000])
	 *     → WHERE status='published' AND views > 1000
	 *
	 * Security:
	 *   Values are quoted via quote() method before insertion.
	 *   Uses sprintf with %s placeholders for safe substitution.
	 *
	 * @param array $arguments Arguments array from Model::where()
	 * @return $this For method chaining
	 * @throws DatabaseException If argument count is invalid
	 */
	public function where($arguments)
	{
		if(!is_array($arguments)) $arguments = func_get_args();

		if (is_float(sizeof($arguments) / 2)) throw new DatabaseException("No arguments passed for the where clause");

		// Single argument pair (field, value)
		if (sizeof($arguments) == 2)
		{
			// Auto-add '= ?' if no placeholder present (shorthand syntax)
			if (strpos($arguments[0], '?') === false) $arguments[0] = $arguments[0] . ' = ?';

			// Replace ? with %s placeholder
			$arguments[0] = preg_replace("#\?#", "%s", $arguments[0]);

			// Quote the value
			$arguments[1] = $this->quote($arguments[1]);

			// Build WHERE clause with sprintf and store with AND operator
			$this->where[] = array(
				'condition' => call_user_func_array("sprintf", $arguments),
				'operator' => 'AND'
			);

			return $this;
		}

		// Multiple argument pairs
		else
		{
			// Calculate number of iterations
			$count = sizeof($arguments) / 2;

			// Process each pair
			for ($i = 0; $i < $count; $i++)
			{
				// Extract one pair
				$argumentsPair = array_splice($arguments, 0, 2);

				// Auto-add '= ?' if no placeholder present (shorthand syntax)
				if (strpos($argumentsPair[0], '?') === false)
				{
					$argumentsPair[0] = $argumentsPair[0] . ' = ?';
				}

				// Replace ? with %s placeholder
				$argumentsPair[0] = preg_replace("#\?#", "%s", $argumentsPair[0]);

				// Quote the value
				$argumentsPair[1] = $this->quote($argumentsPair[1]);

				// Build WHERE clause with sprintf and store with AND operator
				$this->where[] = array(
					'condition' => call_user_func_array("sprintf", $argumentsPair),
					'operator' => 'AND'
				);
			}

			return $this;
		}
	}

	/**
	 * Add OR WHERE clause
	 *
	 * Adds WHERE conditions with OR operator instead of AND.
	 * Use when you want records matching ANY of the conditions.
	 *
	 * Syntax: Same as where() but uses OR operator.
	 *
	 * Examples:
	 *   orWhere('status', 'draft')
	 *   → WHERE ... OR status = 'draft'
	 *
	 *   orWhere('views > ?', 1000)
	 *   → WHERE ... OR views > 1000
	 *
	 *   orWhere('author_id = ?', 5, 'status = ?', 'published')
	 *   → WHERE ... OR author_id = 5 OR status = 'published'
	 *
	 * @param mixed ...$arguments Variadic arguments (condition, value pairs)
	 * @return MySQLQuery Chainable query instance
	 */
	public function orWhere($arguments)
	{
		// Validate argument pairs match
		if (is_float(sizeof($arguments) / 2))
		{
			throw new DatabaseException("Invalid arguments passed for the orWhere clause");
		}

		// Single argument pair (field, value)
		if (sizeof($arguments) == 2)
		{
			// Auto-add '= ?' if no placeholder present (shorthand syntax)
			if (strpos($arguments[0], '?') === false)
			{
				$arguments[0] = $arguments[0] . ' = ?';
			}

			// Replace ? with %s placeholder
			$arguments[0] = preg_replace("#\?#", "%s", $arguments[0]);

			// Quote the value
			$arguments[1] = $this->quote($arguments[1]);

			// Build WHERE clause with sprintf and store with OR operator
			$this->where[] = array(
				'condition' => call_user_func_array("sprintf", $arguments),
				'operator' => 'OR'
			);

			return $this;
		}

		// Multiple argument pairs
		else
		{
			// Calculate number of iterations
			$count = sizeof($arguments) / 2;

			// Process each pair
			for ($i = 0; $i < $count; $i++)
			{
				// Extract one pair
				$argumentsPair = array_splice($arguments, 0, 2);

				// Auto-add '= ?' if no placeholder present (shorthand syntax)
				if (strpos($argumentsPair[0], '?') === false)
				{
					$argumentsPair[0] = $argumentsPair[0] . ' = ?';
				}

				// Replace ? with %s placeholder
				$argumentsPair[0] = preg_replace("#\?#", "%s", $argumentsPair[0]);

				// Quote the value
				$argumentsPair[1] = $this->quote($argumentsPair[1]);

				// Build WHERE clause with sprintf and store with OR operator
				$this->where[] = array(
					'condition' => call_user_func_array("sprintf", $argumentsPair),
					'operator' => 'OR'
				);
			}

			return $this;
		}
	}

	/**
	 * Add WHERE IN clause
	 *
	 * Checks if column value matches any value in an array.
	 * More efficient than multiple OR conditions.
	 *
	 * Examples:
	 *   whereIn('id', [1, 2, 3, 4, 5])
	 *   → WHERE id IN (1, 2, 3, 4, 5)
	 *
	 *   whereIn('status', ['draft', 'pending', 'review'])
	 *   → WHERE status IN ('draft', 'pending', 'review')
	 *
	 * @param string $column Column name
	 * @param array $values Array of values to match
	 * @return MySQLQuery Chainable query instance
	 */
	public function whereIn($column, $values)
	{
		if (empty($column))
		{
			throw new DatabaseException("No column provided for whereIn clause", 1);
		}

		if (!is_array($values) || empty($values))
		{
			throw new DatabaseException("Invalid or empty array provided for whereIn clause", 1);
		}

		// Quote the array values (quote() handles arrays)
		$quotedValues = $this->quote($values);

		// Build WHERE IN condition
		$this->where[] = array(
			'condition' => "{$column} IN {$quotedValues}",
			'operator' => 'AND'
		);

		return $this;
	}

	/**
	 * Add WHERE NOT IN clause
	 *
	 * Checks if column value does NOT match any value in an array.
	 * Opposite of whereIn().
	 *
	 * Examples:
	 *   whereNotIn('status', ['deleted', 'banned'])
	 *   → WHERE status NOT IN ('deleted', 'banned')
	 *
	 *   whereNotIn('id', [10, 20, 30])
	 *   → WHERE id NOT IN (10, 20, 30)
	 *
	 * @param string $column Column name
	 * @param array $values Array of values to exclude
	 * @return MySQLQuery Chainable query instance
	 */
	public function whereNotIn($column, $values)
	{
		if (empty($column))
		{
			throw new DatabaseException("No column provided for whereNotIn clause", 1);
		}

		if (!is_array($values) || empty($values))
		{
			throw new DatabaseException("Invalid or empty array provided for whereNotIn clause", 1);
		}

		// Quote the array values (quote() handles arrays)
		$quotedValues = $this->quote($values);

		// Build WHERE NOT IN condition
		$this->where[] = array(
			'condition' => "{$column} NOT IN {$quotedValues}",
			'operator' => 'AND'
		);

		return $this;
	}

	/**
	 * Add WHERE BETWEEN clause
	 *
	 * Checks if column value falls within a range (inclusive).
	 * Works with numbers, dates, and strings.
	 *
	 * Examples:
	 *   whereBetween('age', 18, 65)
	 *   → WHERE age BETWEEN 18 AND 65
	 *
	 *   whereBetween('created_at', '2024-01-01', '2024-12-31')
	 *   → WHERE created_at BETWEEN '2024-01-01' AND '2024-12-31'
	 *
	 *   whereBetween('price', 10.00, 99.99)
	 *   → WHERE price BETWEEN 10.00 AND 99.99
	 *
	 * @param string $column Column name
	 * @param mixed $min Minimum value (inclusive)
	 * @param mixed $max Maximum value (inclusive)
	 * @return MySQLQuery Chainable query instance
	 */
	public function whereBetween($column, $min, $max)
	{
		if (empty($column))
		{
			throw new DatabaseException("No column provided for whereBetween clause", 1);
		}

		if ($min === null || $max === null)
		{
			throw new DatabaseException("Invalid min/max values provided for whereBetween clause", 1);
		}

		// Quote min and max values
		$quotedMin = $this->quote($min);
		$quotedMax = $this->quote($max);

		// Build WHERE BETWEEN condition
		$this->where[] = array(
			'condition' => "{$column} BETWEEN {$quotedMin} AND {$quotedMax}",
			'operator' => 'AND'
		);

		return $this;
	}

	/**
	 * Add WHERE LIKE clause
	 *
	 * Performs pattern matching on column values.
	 * Supports wildcards: % (any characters), _ (single character).
	 *
	 * Examples:
	 *   whereLike('title', 'WordPress%')
	 *   → WHERE title LIKE 'WordPress%'
	 *
	 *   whereLike('email', '%@gmail.com')
	 *   → WHERE email LIKE '%@gmail.com'
	 *
	 *   whereLike('name', '%John%')
	 *   → WHERE name LIKE '%John%'
	 *
	 * @param string $column Column name
	 * @param string $pattern Pattern to match (with % or _ wildcards)
	 * @return MySQLQuery Chainable query instance
	 */
	public function whereLike($column, $pattern)
	{
		if (empty($column))
		{
			throw new DatabaseException("No column provided for whereLike clause", 1);
		}

		if ($pattern === null || $pattern === '')
		{
			throw new DatabaseException("No pattern provided for whereLike clause", 1);
		}

		// Quote the pattern
		$quotedPattern = $this->quote($pattern);

		// Build WHERE LIKE condition
		$this->where[] = array(
			'condition' => "{$column} LIKE {$quotedPattern}",
			'operator' => 'AND'
		);

		return $this;
	}

	/**
	 * Add WHERE NOT LIKE clause
	 *
	 * Performs pattern exclusion on column values.
	 * Opposite of whereLike().
	 *
	 * Examples:
	 *   whereNotLike('email', '%spam.com')
	 *   → WHERE email NOT LIKE '%spam.com'
	 *
	 *   whereNotLike('title', 'Draft:%')
	 *   → WHERE title NOT LIKE 'Draft:%'
	 *
	 * @param string $column Column name
	 * @param string $pattern Pattern to exclude (with % or _ wildcards)
	 * @return MySQLQuery Chainable query instance
	 */
	public function whereNotLike($column, $pattern)
	{
		if (empty($column))
		{
			throw new DatabaseException("No column provided for whereNotLike clause", 1);
		}

		if ($pattern === null || $pattern === '')
		{
			throw new DatabaseException("No pattern provided for whereNotLike clause", 1);
		}

		// Quote the pattern
		$quotedPattern = $this->quote($pattern);

		// Build WHERE NOT LIKE condition
		$this->where[] = array(
			'condition' => "{$column} NOT LIKE {$quotedPattern}",
			'operator' => 'AND'
		);

		return $this;
	}

	/**
	 * Add WHERE NULL clause
	 *
	 * Checks if column value is NULL.
	 *
	 * Examples:
	 *   whereNull('deleted_at')
	 *   → WHERE deleted_at IS NULL
	 *
	 *   whereNull('parent_id')
	 *   → WHERE parent_id IS NULL
	 *
	 * @param string $column Column name
	 * @return MySQLQuery Chainable query instance
	 */
	public function whereNull($column)
	{
		if (empty($column))
		{
			throw new DatabaseException("No column provided for whereNull clause", 1);
		}

		// Build WHERE IS NULL condition
		$this->where[] = array(
			'condition' => "{$column} IS NULL",
			'operator' => 'AND'
		);

		return $this;
	}

	/**
	 * Add WHERE NOT NULL clause
	 *
	 * Checks if column value is NOT NULL.
	 *
	 * Examples:
	 *   whereNotNull('published_at')
	 *   → WHERE published_at IS NOT NULL
	 *
	 *   whereNotNull('user_id')
	 *   → WHERE user_id IS NOT NULL
	 *
	 * @param string $column Column name
	 * @return MySQLQuery Chainable query instance
	 */
	public function whereNotNull($column)
	{
		if (empty($column))
		{
			throw new DatabaseException("No column provided for whereNotNull clause", 1);
		}

		// Build WHERE IS NOT NULL condition
		$this->where[] = array(
			'condition' => "{$column} IS NOT NULL",
			'operator' => 'AND'
		);

		return $this;
	}

	/**
	 * Increment column values
	 *
	 * Unified method for single and bulk increment operations.
	 *
	 * SINGLE INCREMENT (with optional where clause):
	 *   ['field']           → increment field by 1
	 *   ['field' => 10]     → increment field by 10
	 *   ['a' => 5, 'b' => 2] → increment multiple fields
	 *
	 * BULK INCREMENT (pass $key as second parameter):
	 *   [['id' => 1, 'views' => 10], ['id' => 2, 'views' => 5]], 'id'
	 *
	 * Examples:
	 *   increment(['views'])
	 *   → UPDATE table SET views = views + 1
	 *
	 *   increment(['votes' => 5])
	 *   → UPDATE table SET votes = votes + 5
	 *
	 *   increment([['id' => 1, 'views' => 10], ['id' => 2, 'views' => 5]], 'id')
	 *   → Bulk increment with CASE statements
	 *
	 * @param array $data Fields to increment or array of rows for bulk
	 * @param string|null $key Column name for bulk increment (e.g., 'id')
	 * @return int Number of affected rows
	 * @throws DatabaseException If data is empty or query fails
	 */
	public function increment($data, $key = null)
	{
		if (empty($data)) {
			throw new DatabaseException("No columns provided for increment operation", 1);
		}

		// Bulk increment - delegate to incrementBulk
		if ($key !== null) {
			return $this->incrementBulk($data, $key);
		}

		// Build SET clause for each field
		$setParts = [];
		foreach ($data as $k => $value) {
			if (is_numeric($k)) {
				// ['views'] format - field name as value, increment by 1
				$field = $value;
				$amount = 1;
			} else {
				// ['views' => 10] format - field name as key, amount as value
				$field = $k;
				$amount = (int) $value;
			}

			if ($amount <= 0) {
				throw new DatabaseException("Invalid increment amount for {$field} (must be positive number)", 1);
			}

			$setParts[] = "{$field} = {$field} + {$amount}";
		}

		$setClause = implode(', ', $setParts);

		// Build WHERE clause
		$where = '';
		if (!empty($this->where)) {
			$whereParts = array();
			foreach ($this->where as $index => $whereClause) {
				$condition = $whereClause['condition'];
				$operator = $whereClause['operator'];
				if ($index === 0) {
					$whereParts[] = $condition;
				} else {
					$whereParts[] = "{$operator} {$condition}";
				}
			}
			$where = "WHERE " . join(" ", $whereParts);
		}

		$sql = "UPDATE {$this->from} SET {$setClause} {$where}";

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException("Increment query failed: " . $this->connector->lastError(), 1);
		}

		return $this->connector->affectedRows();
	}

	/**
	 * Decrement column values
	 *
	 * Unified method for single and bulk decrement operations.
	 *
	 * SINGLE DECREMENT (with optional where clause):
	 *   ['field']           → decrement field by 1
	 *   ['field' => 10]     → decrement field by 10
	 *   ['a' => 5, 'b' => 2] → decrement multiple fields
	 *
	 * BULK DECREMENT (pass $key as second parameter):
	 *   [['id' => 1, 'stock' => 10], ['id' => 2, 'stock' => 5]], 'id'
	 *
	 * Examples:
	 *   decrement(['stock'])
	 *   → UPDATE table SET stock = stock - 1
	 *
	 *   decrement(['credits' => 10])
	 *   → UPDATE table SET credits = credits - 10
	 *
	 *   decrement([['id' => 1, 'stock' => 10], ['id' => 2, 'stock' => 5]], 'id')
	 *   → Bulk decrement with CASE statements
	 *
	 * @param array $data Fields to decrement or array of rows for bulk
	 * @param string|null $key Column name for bulk decrement (e.g., 'id')
	 * @return int Number of affected rows
	 * @throws DatabaseException If data is empty or query fails
	 */
	public function decrement($data, $key = null)
	{
		if (empty($data)) {
			throw new DatabaseException("No columns provided for decrement operation", 1);
		}

		// Bulk decrement - delegate to decrementBulk
		if ($key !== null) {
			return $this->decrementBulk($data, $key);
		}

		// Build SET clause for each field
		$setParts = [];
		foreach ($data as $k => $value) {
			if (is_numeric($k)) {
				// ['stock'] format - field name as value, decrement by 1
				$field = $value;
				$amount = 1;
			} else {
				// ['stock' => 10] format - field name as key, amount as value
				$field = $k;
				$amount = (int) $value;
			}

			if ($amount <= 0) {
				throw new DatabaseException("Invalid decrement amount for {$field} (must be positive number)", 1);
			}

			$setParts[] = "{$field} = {$field} - {$amount}";
		}

		$setClause = implode(', ', $setParts);

		// Build WHERE clause
		$where = '';
		if (!empty($this->where)) {
			$whereParts = array();
			foreach ($this->where as $index => $whereClause) {
				$condition = $whereClause['condition'];
				$operator = $whereClause['operator'];
				if ($index === 0) {
					$whereParts[] = $condition;
				} else {
					$whereParts[] = "{$operator} {$condition}";
				}
			}
			$where = "WHERE " . join(" ", $whereParts);
		}

		$sql = "UPDATE {$this->from} SET {$setClause} {$where}";

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException("Decrement query failed: " . $this->connector->lastError(), 1);
		}

		return $this->connector->affectedRows();
	}

	/**
	 * Add FULLTEXT search clause
	 *
	 * Performs full-text search on columns with FULLTEXT index.
	 * Much faster than LIKE for searching large text content.
	 * Requires columns to have FULLTEXT index defined.
	 *
	 * Search Modes:
	 *   - 'natural' (default): Natural language search
	 *   - 'boolean': Boolean mode with +/- operators
	 *   - 'expansion': Natural language with query expansion
	 *
	 * Examples:
	 *   whereFulltext(['title', 'content'], 'WordPress tutorial')
	 *   → WHERE MATCH(title, content) AGAINST('WordPress tutorial' IN NATURAL LANGUAGE MODE)
	 *
	 *   whereFulltext(['body'], '+MySQL -Oracle', 'boolean')
	 *   → WHERE MATCH(body) AGAINST('+MySQL -Oracle' IN BOOLEAN MODE)
	 *
	 * @param array $columns Array of column names with FULLTEXT index
	 * @param string $search Search query string
	 * @param string $mode Search mode: 'natural', 'boolean', 'expansion'
	 * @return MySQLQuery Chainable query instance
	 */
	public function whereFulltext($columns, $search, $mode = 'natural')
	{
		if (!is_array($columns) || empty($columns))		{
			throw new DatabaseException("Invalid columns array for whereFulltext clause", 1);
		}

		if (empty($search))		{
			throw new DatabaseException("No search term provided for whereFulltext clause", 1);
		}

		$validModes = ['natural', 'boolean', 'expansion'];

		if (!in_array($mode, $validModes))		{
			throw new DatabaseException("Invalid search mode '{$mode}' for whereFulltext clause", 1);
		}

		// Map mode to MySQL syntax
		$modeMap = [
			'natural' => 'IN NATURAL LANGUAGE MODE',
			'boolean' => 'IN BOOLEAN MODE',
			'expansion' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION'
		];

		$modeString = $modeMap[$mode];

		// Quote search term
		$quotedSearch = $this->quote($search);

		// Build MATCH...AGAINST clause
		$columnList = join(', ', $columns);

		$this->where[] = array(
			'condition' => "MATCH({$columnList}) AGAINST({$quotedSearch} {$modeString})",
			'operator' => 'AND'
		);

		return $this;
	}

	/**
	 * Begin database transaction
	 *
	 * Starts a transaction to group multiple queries atomically.
	 * Use commit() to save changes or rollback() to cancel.
	 *
	 * Transaction Benefits:
	 *   - Atomicity: All queries succeed or all fail
	 *   - Prevents race conditions
	 *   - Maintains data consistency
	 *
	 * Example:
	 *   $db->begin();
	 *   $db->save(['user_id' => 1, 'amount' => 100]);
	 *   $db->where('id', 1)->save(['balance' => 'balance - 100']);
	 *   $db->commit(); // or $db->rollback();
	 *
	 * @return bool True on success
	 */
	public function begin()
	{
		$result = $this->connector->execute("START TRANSACTION");

		if ($result === false)
		{
			throw new DatabaseException("Failed to start transaction: " . $this->connector->lastError(), 1);
		}

		return true;
	}

	/**
	 * Commit database transaction
	 *
	 * Saves all changes made during the transaction.
	 * Call after successful completion of all operations.
	 *
	 * Example:
	 *   $db->begin();
	 *   // ... multiple queries ...
	 *   $db->commit(); // Save all changes
	 *
	 * @return bool True on success
	 */
	public function commit()
	{
		$result = $this->connector->execute("COMMIT");

		if ($result === false)
		{
			throw new DatabaseException("Failed to commit transaction: " . $this->connector->lastError(), 1);
		}

		return true;
	}

	/**
	 * Rollback database transaction
	 *
	 * Cancels all changes made during the transaction.
	 * Call when an error occurs or operation needs to be cancelled.
	 *
	 * Example:
	 *   $db->begin();
	 *   try {
	 *       // ... queries ...
	 *       $db->commit();
	 *   } catch (Exception $e) {
	 *       $db->rollback(); // Cancel all changes
	 *   }
	 *
	 * @return bool True on success
	 */
	public function rollback()
	{
		$result = $this->connector->execute("ROLLBACK");

		if ($result === false)
		{
			throw new DatabaseException("Failed to rollback transaction: " . $this->connector->lastError(), 1);
		}

		return true;
	}

	/**
	 * Add row-level UPDATE lock (FOR UPDATE)
	 *
	 * Applies exclusive lock on selected rows, preventing other transactions
	 * from reading (with locks) or modifying them. Use when you plan to
	 * UPDATE or DELETE the rows.
	 *
	 * Lock Modes:
	 *   - null (default): Wait for locked rows to become available
	 *   - 'skip': Skip locked rows (FOR UPDATE SKIP LOCKED) - ideal for queues
	 *   - 'nowait': Fail immediately if rows are locked (FOR UPDATE NOWAIT)
	 *
	 * Examples:
	 *   QueueModel::where('status', 'pending')
	 *             ->updateLock('skip')
	 *             ->all();
	 *
	 *   QueueModel::where('id', 5)
	 *             ->updateLock()
	 *             ->first();
	 *
	 * Note: Must be used within a transaction for proper isolation.
	 *
	 * @param string|null $mode Lock mode: null (wait), 'skip', or 'nowait'
	 * @return $this For method chaining
	 */
	public function updateLock($mode = null)
	{
		$this->lockClause = 'FOR UPDATE';

		if ($mode !== null)
		{
			$mode = strtolower($mode);

			if ($mode === 'skip')
			{
				$this->lockClause = 'FOR UPDATE SKIP LOCKED';
			}
			elseif ($mode === 'nowait')
			{
				$this->lockClause = 'FOR UPDATE NOWAIT';
			}
		}

		return $this;
	}

	/**
	 * Add row-level SHARE lock (FOR SHARE)
	 *
	 * Applies shared lock on selected rows, allowing other transactions
	 * to read but not modify them. Use when you want to ensure data
	 * doesn't change during your transaction but don't plan to update it.
	 *
	 * Lock Modes:
	 *   - null (default): Wait for locked rows to become available
	 *   - 'skip': Skip locked rows (FOR SHARE SKIP LOCKED)
	 *   - 'nowait': Fail immediately if rows are locked (FOR SHARE NOWAIT)
	 *
	 * Examples:
	 *   UserModel::where('id', 5)
	 *            ->shareLock()
	 *            ->first();
	 *
	 *   ProductModel::where('category', 'electronics')
	 *               ->shareLock('skip')
	 *               ->all();
	 *
	 * Note: Must be used within a transaction for proper isolation.
	 *
	 * @param string|null $mode Lock mode: null (wait), 'skip', or 'nowait'
	 * @return $this For method chaining
	 */
	public function shareLock($mode = null)
	{
		$this->lockClause = 'FOR SHARE';

		if ($mode !== null)
		{
			$mode = strtolower($mode);

			if ($mode === 'skip')
			{
				$this->lockClause = 'FOR SHARE SKIP LOCKED';
			}
			elseif ($mode === 'nowait')
			{
				$this->lockClause = 'FOR SHARE NOWAIT';
			}
		}

		return $this;
	}

	// =========================================================================
	// SHOULD HAVE FEATURES - Convenience Methods
	// =========================================================================

	/**
	 * Extract single column values as array
	 *
	 * Returns a flat array of values from a single column.
	 * Useful for getting lists of IDs, names, etc.
	 *
	 * Examples:
	 *   pluck('id')
	 *   → [1, 2, 3, 4, 5]
	 *
	 *   pluck('email')
	 *   → ['user1@example.com', 'user2@example.com', ...]
	 *
	 * @param string $column Column name to extract
	 * @return array Flat array of column values
	 */
	public function pluck($column)
	{
		if (empty($column)) {
			throw new DatabaseException("No column provided for pluck operation", 1);
		}

		$results = $this->all();

		if (is_string($results)) {
			return $results;
		}

		$values = array();
		foreach ($results as $row) {
			if (isset($row[$column])) {
				$values[] = $row[$column];
			}
		}

		return $values;
	}

	/**
	 * Check if any records exist
	 *
	 * Returns true if at least one record matches the query.
	 * More efficient than count() > 0.
	 *
	 * Examples:
	 *   exists()
	 *   → true/false
	 *
	 *   where('email', 'test@example.com')->exists()
	 *   → true/false
	 *
	 * @return bool True if records exist, false otherwise
	 */
	public function exists()
	{
		$this->limit = 1;
		$this->offset = 0;

		$sql = $this->buildSelect();

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException("Exists query failed: " . $this->connector->lastError(), 1);
		}

		return $result->num_rows > 0;
	}

	/**
	 * Filter by date component (day)
	 *
	 * Matches records where date column equals specific date (ignoring time).
	 *
	 * Examples:
	 *   whereDate('created_at', '2024-01-15')
	 *   → WHERE DATE(created_at) = '2024-01-15'
	 *
	 * @param string $column Date/datetime column name
	 * @param string $date Date in Y-m-d format
	 * @return MySQLQuery Chainable query instance
	 */
	public function whereDate($column, $date)
	{
		if (empty($column))
		{
			throw new DatabaseException("No column provided for whereDate clause", 1);
		}

		if (empty($date))
		{
			throw new DatabaseException("No date provided for whereDate clause", 1);
		}

		// Quote the date
		$quotedDate = $this->quote($date);

		// Build WHERE DATE() condition
		$this->where[] = array(
			'condition' => "DATE({$column}) = {$quotedDate}",
			'operator' => 'AND'
		);

		return $this;
	}

	/**
	 * Filter by month component
	 *
	 * Matches records where date column is in specific month (1-12).
	 *
	 * Examples:
	 *   whereMonth('created_at', 12)
	 *   → WHERE MONTH(created_at) = 12
	 *
	 * @param string $column Date/datetime column name
	 * @param int $month Month number (1-12)
	 * @return MySQLQuery Chainable query instance
	 */
	public function whereMonth($column, $month)
	{
		if (empty($column))
		{
			throw new DatabaseException("No column provided for whereMonth clause", 1);
		}

		if ($month < 1 || $month > 12)
		{
			throw new DatabaseException("Invalid month value (must be 1-12)", 1);
		}

		// Build WHERE MONTH() condition
		$this->where[] = array(
			'condition' => "MONTH({$column}) = {$month}",
			'operator' => 'AND'
		);

		return $this;
	}

	/**
	 * Filter by year component
	 *
	 * Matches records where date column is in specific year.
	 *
	 * Examples:
	 *   whereYear('created_at', 2024)
	 *   → WHERE YEAR(created_at) = 2024
	 *
	 * @param string $column Date/datetime column name
	 * @param int $year Year value
	 * @return MySQLQuery Chainable query instance
	 */
	public function whereYear($column, $year)
	{
		if (empty($column))
		{
			throw new DatabaseException("No column provided for whereYear clause", 1);
		}

		if (!is_numeric($year) || $year < 1000 || $year > 9999)
		{
			throw new DatabaseException("Invalid year value", 1);
		}

		// Build WHERE YEAR() condition
		$this->where[] = array(
			'condition' => "YEAR({$column}) = {$year}",
			'operator' => 'AND'
		);

		return $this;
	}

	/**
	 * Paginate results with metadata
	 *
	 * Returns paginated results with additional pagination metadata.
	 * More convenient than manually calculating LIMIT/OFFSET.
	 *
	 * Returns array with:
	 *   - 'data': Result rows
	 *   - 'current_page': Current page number
	 *   - 'per_page': Items per page
	 *   - 'total': Total matching records
	 *   - 'last_page': Last page number
	 *   - 'from': First item number on page
	 *   - 'to': Last item number on page
	 *
	 * Examples:
	 *   paginate(10, 1)
	 *   → ['data' => [...], 'current_page' => 1, 'per_page' => 10, ...]
	 *
	 * @param int $perPage Items per page
	 * @param int $page Current page number (1-based)
	 * @return array Pagination result with metadata
	 */
	public function paginate($perPage = 15, $page = 1)
	{
		// Cast to int to prevent injection
		$perPage 	= (int) $perPage;
		$page 		= (int) $page;

		if ($perPage < 1) {
			throw new DatabaseException("Invalid perPage value (must be >= 1)", 1);
		}

		if ($page < 1) {
			throw new DatabaseException("Invalid page value (must be >= 1)", 1);
		}

		$countQuery = clone $this;
		$total = $countQuery->count();

		if(is_string($total))  return $total;

		$this->limit($perPage, $page);
		$data = $this->all();

		if (is_string($data)) return $data;

		$lastPage 	= (int)ceil($total / $perPage);
		$from 		= (($page - 1) * $perPage) + 1;
		$to 		= min($from + $perPage - 1, $total);

		return array(
			'data' => $data,
			'current_page' => $page,
			'per_page' => $perPage,
			'total' => $total,
			'last_page' => $lastPage,
			'from' => $total > 0 ? $from : null,
			'to' => $total > 0 ? $to : null
		);
	}

	/**
	 * Update existing record or create new one
	 *
	 * If WHERE clause matches a record, updates it.
	 * If no match, creates new record with attributes + values.
	 *
	 * Examples:
	 *   where('email', 'test@example.com')->updateOrCreate(['name' => 'John'])
	 *   → Updates if exists, creates if not
	 *
	 * @param array $values Values to update/insert
	 * @return MySQLResponse Query execution result
	 */
	public function updateOrCreate($values)
	{
		if (empty($values)) {
			throw new DatabaseException("No values provided for updateOrCreate", 1);
		}

		if ($this->exists()) {
			return $this->save($values);
		} else {
			$insertQuery = new static(['connector' => $this->connector], $this->from, $this->timestamps);
			$insertQuery->from = $this->from;
			return $insertQuery->save($values);
		}
	}

	/**
	 * Get first matching record or create new one
	 *
	 * If WHERE clause matches a record, returns it.
	 * If no match, creates new record with attributes and returns it.
	 *
	 * Examples:
	 *   where('email', 'test@example.com')->firstOrCreate(['name' => 'John'])
	 *   → Returns existing or creates new
	 *
	 * @param array $values Values to insert if not found
	 * @return array The found or created record
	 */
	public function firstOrCreate($values)
	{
		if (empty($values)) {
			throw new DatabaseException("No values provided for firstOrCreate", 1);
		}

		$existing = $this->first();

		if ($existing) {
			return $existing;
		} else {
			$insertQuery = new static(['connector' => $this->connector], $this->from, $this->timestamps);
			$insertQuery->from = $this->from;

			$lastId = $insertQuery->save($values);

			$newQuery = new static(['connector' => $this->connector], $this->from, $this->timestamps);
			$newQuery->from = $this->from;

			return $newQuery->where('id', $lastId)->first();
		}
	}

	// =========================================================================
	// NICE TO HAVE FEATURES - Advanced Utilities
	// =========================================================================

	/**
	 * Process large datasets in chunks
	 *
	 * Retrieves and processes records in batches to avoid memory issues.
	 * Executes callback function for each chunk.
	 *
	 * Examples:
	 *   chunk(100, function($records) {
	 *       foreach ($records as $record) {
	 *           // Process each record
	 *       }
	 *   });
	 *
	 * @param int $size Chunk size (records per batch)
	 * @param callable $callback Function to execute for each chunk
	 * @return void
	 */
	public function chunk($size, $callback)
	{
		if ($size < 1) {
			throw new DatabaseException("Invalid chunk size (must be >= 1)", 1);
		}

		if (!is_callable($callback)) {
			throw new DatabaseException("Invalid callback function for chunk operation", 1);
		}

		$page = 1;

		do {
			$chunkQuery = clone $this;
			$chunkQuery->limit($size, $page);
			$results = $chunkQuery->all();

			if (empty($results)) {
				break;
			}

			$continue = call_user_func($callback, $results);

			if ($continue === false) {
				break;
			}

			$page++;
		} while (count($results) === $size);
	}

	/**
	 * Calculate sum of column values
	 *
	 * Returns the sum of all values in a numeric column.
	 *
	 * Examples:
	 *   sum('price')
	 *   → 1500.00
	 *
	 *   where('status', 'completed')->sum('amount')
	 *   → 5000.00
	 *
	 * @param string $column Column name to sum
	 * @return float Sum of column values
	 */
	public function sum($column)
	{
		if (empty($column)) {
			throw new DatabaseException("No column provided for sum operation", 1);
		}

		$sql = "SELECT SUM({$column}) as total FROM {$this->from}";

		if (!empty($this->where)) {
			$whereParts = array();
			foreach ($this->where as $index => $whereClause) {
				$condition = $whereClause['condition'];
				$operator = $whereClause['operator'];
				if ($index === 0) {
					$whereParts[] = $condition;
				} else {
					$whereParts[] = "{$operator} {$condition}";
				}
			}
			$sql .= " WHERE " . join(" ", $whereParts);
		}

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException("Sum query failed: " . $this->connector->lastError(), 1);
		}

		$row = $result->fetch_assoc();
		return $row['total'] !== null ? (float)$row['total'] : 0.0;
	}

	/**
	 * Calculate average of column values
	 *
	 * Returns the average of all values in a numeric column.
	 *
	 * Examples:
	 *   avg('rating')
	 *   → 4.5
	 *
	 *   where('category', 'electronics')->avg('price')
	 *   → 299.99
	 *
	 * @param string $column Column name to average
	 * @return float Average of column values
	 */
	public function avg($column)
	{
		if (empty($column)) {
			throw new DatabaseException("No column provided for avg operation", 1);
		}

		$sql = "SELECT AVG({$column}) as average FROM {$this->from}";

		if (!empty($this->where)) {
			$whereParts = array();
			foreach ($this->where as $index => $whereClause) {
				$condition = $whereClause['condition'];
				$operator = $whereClause['operator'];
				if ($index === 0) {
					$whereParts[] = $condition;
				} else {
					$whereParts[] = "{$operator} {$condition}";
				}
			}
			$sql .= " WHERE " . join(" ", $whereParts);
		}

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException("Average query failed: " . $this->connector->lastError(), 1);
		}

		$row = $result->fetch_assoc();
		return $row['average'] !== null ? (float)$row['average'] : 0.0;
	}

	/**
	 * Find minimum column value
	 *
	 * Returns the smallest value in a column.
	 *
	 * Examples:
	 *   min('price')
	 *   → 9.99
	 *
	 *   where('in_stock', 1)->min('price')
	 *   → 19.99
	 *
	 * @param string $column Column name
	 * @return mixed Minimum value
	 */
	public function min($column)
	{
		if (empty($column)) {
			throw new DatabaseException("No column provided for min operation", 1);
		}

		$sql = "SELECT MIN({$column}) as minimum FROM {$this->from}";

		if (!empty($this->where)) {
			$whereParts = array();
			foreach ($this->where as $index => $whereClause) {
				$condition = $whereClause['condition'];
				$operator = $whereClause['operator'];
				if ($index === 0) {
					$whereParts[] = $condition;
				} else {
					$whereParts[] = "{$operator} {$condition}";
				}
			}
			$sql .= " WHERE " . join(" ", $whereParts);
		}

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException("Min query failed: " . $this->connector->lastError(), 1);
		}

		$row = $result->fetch_assoc();
		return $row['minimum'];
	}

	/**
	 * Find maximum column value
	 *
	 * Returns the largest value in a column.
	 *
	 * Examples:
	 *   max('price')
	 *   → 999.99
	 *
	 *   where('category', 'laptops')->max('price')
	 *   → 2499.99
	 *
	 * @param string $column Column name
	 * @return mixed Maximum value
	 */
	public function max($column)
	{
		if (empty($column)) {
			throw new DatabaseException("No column provided for max operation", 1);
		}

		$sql = "SELECT MAX({$column}) as maximum FROM {$this->from}";

		if (!empty($this->where)) {
			$whereParts = array();
			foreach ($this->where as $index => $whereClause) {
				$condition = $whereClause['condition'];
				$operator = $whereClause['operator'];
				if ($index === 0) {
					$whereParts[] = $condition;
				} else {
					$whereParts[] = "{$operator} {$condition}";
				}
			}
			$sql .= " WHERE " . join(" ", $whereParts);
		}

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException("Max query failed: " . $this->connector->lastError(), 1);
		}

		$row = $result->fetch_assoc();
		return $row['maximum'];
	}

	/**
	 * Compare two columns in WHERE clause
	 *
	 * Adds WHERE condition comparing two columns.
	 * Useful for checking relationships or duplicates.
	 *
	 * Examples:
	 *   whereColumn('first_name', 'last_name')
	 *   → WHERE first_name = last_name
	 *
	 *   whereColumn('created_at', '>', 'updated_at')
	 *   → WHERE created_at > updated_at
	 *
	 * @param string $column1 First column name
	 * @param string $operatorOrColumn2 Operator or second column name
	 * @param string|null $column2 Second column name (if operator provided)
	 * @return MySQLQuery Chainable query instance
	 */
	public function whereColumn($column1, $operatorOrColumn2, $column2 = null)
	{
		if (empty($column1))
		{
			throw new DatabaseException("No first column provided for whereColumn clause", 1);
		}

		// Two arguments: whereColumn('col1', 'col2') - defaults to =
		if ($column2 === null)
		{
			$operator = '=';
			$column2 = $operatorOrColumn2;
		}
		// Three arguments: whereColumn('col1', '>', 'col2')
		else
		{
			$operator = $operatorOrColumn2;
		}

		if (empty($column2))
		{
			throw new DatabaseException("No second column provided for whereColumn clause", 1);
		}

		// Build WHERE column comparison
		$this->where[] = array(
			'condition' => "{$column1} {$operator} {$column2}",
			'operator' => 'AND'
		);

		return $this;
	}

	/**
	 * Add GROUP BY clause
	 *
	 * group results by one or more columns.
	 * Typically used with aggregate functions (COUNT, SUM, AVG, etc.).
	 *
	 * Examples:
	 *   groupBy('category')
	 *   → GROUP BY category
	 *
	 *   groupBy('year', 'month')
	 *   → GROUP BY year, month
	 *
	 * @param string ...$columns Column names to group by
	 * @return $this For method chaining
	 * @throws DatabaseException If no columns provided
	 */
	public function groupBy(...$columns)
	{
		// Validate at least one column provided
		if (empty($columns))
		{
			throw new DatabaseException("No columns provided for GROUP BY clause", 1);
		}

		// Add columns to group array
		foreach ($columns as $column)
		{
			$this->group[] = $column;
		}

		return $this;
	}

	/**
	 * Add HAVING clause
	 *
	 * Filters grouped results (like WHERE but for GROUP BY).
	 * Must be used with GROUP BY.
	 *
	 * Examples:
	 *   having('COUNT(*) > ?', 5)
	 *   → HAVING COUNT(*) > 5
	 *
	 *   having('SUM(amount) >= ?', 1000)
	 *   → HAVING SUM(amount) >= 1000
	 *
	 * @param mixed ...$arguments Condition and values (same pattern as where)
	 * @return $this For method chaining
	 * @throws DatabaseException If invalid arguments
	 */
	public function having(...$arguments)
	{
		// Validate argument pairs match
		if (count($arguments) < 2 || count($arguments) % 2 != 0)
		{
			throw new DatabaseException("Invalid arguments for HAVING clause", 1);
		}

		// Single argument pair
		if (count($arguments) == 2)
		{
			// Replace ? with %s placeholder
			$arguments[0] = preg_replace("#\?#", "%s", $arguments[0]);

			// Quote the value
			$arguments[1] = $this->quote($arguments[1]);

			// Build HAVING clause
			$this->having[] = call_user_func_array("sprintf", $arguments);

			return $this;
		}

		// Multiple argument pairs
		$count = count($arguments) / 2;

		for ($i = 0; $i < $count; $i++)
		{
			// Extract one pair
			$argumentsPair = array_splice($arguments, 0, 2);

			// Replace ? with %s placeholder
			$argumentsPair[0] = preg_replace("#\?#", "%s", $argumentsPair[0]);

			// Quote the value
			$argumentsPair[1] = $this->quote($argumentsPair[1]);

			// Build HAVING clause
			$this->having[] = call_user_func_array("sprintf", $argumentsPair);
		}

		return $this;
	}

	/**
	 * Add INNER JOIN to query
	 *
	 * Joins another table and returns only matching records from both tables.
	 * More efficient than LEFT JOIN when you know relationships exist.
	 *
	 * Examples:
	 *   innerJoin('users', 'posts.user_id = users.id', ['users.name'])
	 *   innerJoin('categories', 'posts.category_id = categories.id', ['*'])
	 *
	 * @param string $table Table name to join
	 * @param string $condition Join condition (e.g., 'posts.user_id = users.id')
	 * @param array $fields Columns to select from joined table
	 * @return $this For method chaining
	 * @throws DatabaseException If table or condition is empty
	 */
	public function innerJoin($table, $condition, $fields = array("*"))
	{
		// Validate table is not empty
		if (empty($table))
		{
			throw new DatabaseException("Invalid table argument $table passed for the innerJoin Clause", 1);
		}

		// Validate condition is not empty
		if (empty($condition))
		{
			throw new DatabaseException("Invalid argument $condition passed for the innerJoin Clause", 1);
		}

		// Add fields for this table
		$this->fields += array($table => $fields);

		// Store join table, condition, and type
		$this->join['tables'][] = $table;
		$this->join['conditions'][] = $condition;
		$this->join['types'][] = 'INNER';  // Mark as INNER JOIN

		return $this;
	}

	// =========================================================================
	// BUILD METHODS (Construct SQL Strings)
	// =========================================================================

	/**
	 * Build SELECT query string
	 *
	 * Constructs SELECT query from stored parameters.
	 * Handles fields, joins, where, group by, having, order, and limit clauses.
	 *
	 * Query Template:
	 *   SELECT [DISTINCT] fields FROM table [JOIN] [WHERE] [GROUP BY] [HAVING] [ORDER BY] [LIMIT]
	 *
	 * @return string Complete SELECT query
	 */
	protected function buildSelect()
	{
		$fields = array();
		$where = $order = $limit = $join = $group = $having = "";
		$template = "SELECT %s %s FROM %s %s %s %s %s %s %s %s";

		// Build fields list
		foreach ($this->fields as $table => $tableFields)
		{
			foreach ($tableFields as $field => $alias)
			{
				// Field with alias
				if (is_string($field) && $field != 'COUNT(1)')
				{
					$fields[] = "{$table}.{$field} AS {$alias}";
				}
				// COUNT function
				elseif (is_string($field) && $field == 'COUNT(1)')
				{
					$fields[] = "{$field} AS {$alias}";
				}
				// Field without alias
				else
				{
					$fields[] = "{$table}.{$alias}";
				}
			}
		}

		// Convert fields array to string
		$fields = join(", ", $fields);

		// Build JOIN clauses (supports multiple joins with different types)
		$queryJoin = $this->join;

		if (!empty($queryJoin))
		{
			$joinParts = array();

			// Build each join separately to support different join types
			foreach ($queryJoin['tables'] as $index => $table)
			{
				$condition = $queryJoin['conditions'][$index];
				$type = isset($queryJoin['types'][$index]) ? $queryJoin['types'][$index] : 'LEFT';

				// Auto-prefix join condition columns
				// Left side gets base table, right side gets join table
				if (!preg_match('/^\w+\./', $condition)) {

					$condition = preg_replace('/^(\w+)/', $this->from . '.$1', $condition);
				}
				if (!preg_match('/=\s*\w+\./', $condition)) {

					$condition = preg_replace('/=\s*(\w+)$/', '= ' . $table . '.$1', $condition);
				}

				$joinParts[] = "{$type} JOIN {$table} ON {$condition}";
			}

			$join = " " . join(" ", $joinParts);
		}

		// Build WHERE clause (supports AND/OR operators)
		$queryWhere = $this->where;

		if (!empty($queryWhere))
		{
			$whereParts = array();

			foreach ($queryWhere as $index => $whereClause)
			{
				$condition = $whereClause['condition'];
				$operator = $whereClause['operator'];

				// Auto-prefix column with base table if joins exist and no table prefix
				if (!empty($this->join) && !preg_match('/^\w+\./', $condition)){

					// Don't prepend table name if it's a full text search
					if(stripos($condition, 'MATCH(') !== 0){
						$condition = preg_replace('/^(\w+)/', $this->from . '.$1', $condition);			
					}
				}

				// First condition has no operator prefix
				if ($index === 0) $whereParts[] = $condition;

				// Subsequent conditions use their operator
				else $whereParts[] = "{$operator} {$condition}";
			}

			$joined = join(" ", $whereParts);

			$where = "WHERE {$joined}";
		}

		// Build GROUP BY clause
		$queryGroup = $this->group;

		if (!empty($queryGroup))
		{
			// Auto-prefix columns with base table if joins exist
			if (!empty($this->join))
			{
				$queryGroup = array_map(function($col) {
					return preg_match('/^\w+\./', $col) ? $col : $this->from . '.' . $col;
				}, $queryGroup);
			}

			$group = "GROUP BY " . join(", ", $queryGroup);
		}

		// Build HAVING clause
		$queryHaving = $this->having;

		if (!empty($queryHaving))
		{
			// Auto-prefix columns with base table if joins exist
			if (!empty($this->join)){
				
				$queryHaving = array_map(function($cond) {
					return preg_match('/^\w+\./', $cond) ? $cond : preg_replace('/^(\w+)/', $this->from . '.$1', $cond);
				}, $queryHaving);
			}

			$joined = join(" AND ", $queryHaving);

			$having = "HAVING {$joined}";
		}

		// Build ORDER BY clause (supports multiple columns)
		$queryOrder = $this->order;

		if (!empty($queryOrder))
		{
			$orderParts = array();

			foreach ($queryOrder as $column => $direction)
			{
				// Auto-prefix column with base table if joins exist and no table prefix
				if (!empty($this->join) && !preg_match('/^\w+\./', $column)) $column = $this->from . '.' . $column;

				$orderParts[] = "{$column} {$direction}";
			}

			$order = "ORDER BY " . join(", ", $orderParts);
		}

		// Build LIMIT clause
		$queryLimit = $this->limit;

		if (!empty($queryLimit))
		{
			$limitOffset = $this->offset;

			// With offset (pagination)
			if ($limitOffset)
			{
				$limit = "LIMIT {$limitOffset}, {$queryLimit}";
			}
			// Without offset
			else
			{
				$limit = "LIMIT {$queryLimit}";
			}
		}

		// Return complete query
		return sprintf($template, $this->distinct, $fields, $this->from, $join, $where, $group, $having, $order, $limit, $this->lockClause);
	}

	/**
	 * Build INSERT query string
	 *
	 * Constructs INSERT query for single row.
	 * Optionally sets created_at timestamp.
	 *
	 * Query Template:
	 *   INSERT INTO table (fields) VALUES (values)
	 *
	 * @param array $data Associative array of field => value pairs
	 * @param bool $set_timestamps Whether to set created_at field
	 * @return string Complete INSERT query
	 */
	protected function buildInsert($data, $set_timestamps)
	{
		$fields = array();
		$values = array();
		$template = "INSERT INTO %s (%s) VALUES (%s)";

		// Add timestamp if requested
		if($set_timestamps)
		{
			$data['created_at'] = date('Y-m-d H:i:s');
		}

		// Build fields and values
		foreach ($data as $field => $value)
		{
			$fields[] = $field;
			$values[] = $this->quote($value);
		}

		// Convert arrays to strings
		$fields = join(", ", $fields);
		$values = join(", ", $values);

		// Return complete query
		return sprintf($template, $this->from, $fields, $values);
	}

	/**
	 * Build bulk INSERT query string
	 *
	 * Constructs INSERT query for multiple rows.
	 * More efficient than multiple single inserts.
	 *
	 * Query Template:
	 *   INSERT INTO table (fields) VALUES (row1), (row2), (row3)
	 *
	 * @param array $data Multidimensional array of rows
	 * @param bool $set_timestamps Whether to set created_at field
	 * @return string Complete INSERT query
	 */
	protected function buildBulkInsert($data, $set_timestamps)
	{
		$fields = array();
		$values = array();
		$template = "INSERT INTO %s (%s) VALUES %s";

		// Get field names from first row
		$fieldsArray = $data[0];

		foreach ($fieldsArray as $field => $value)
		{
			$fields[] = $field;
		}

		// Get count of rows
		$count = sizeof($data);

		// Build values for each row
		for ($i = 0; $i < $count; $i++)
		{
			$array = $data[$i];
			$valuesArray = array();

			// Quote each value
			foreach ($array as $field => $value)
			{
				$valuesArray[] = $this->quote($value);
			}

			// Group row values in parentheses
			$values[] = '(' . join(", ", $valuesArray) . ')';
		}

		// Convert arrays to strings
		$fields = join(", ", $fields);
		$values = join(", ", $values);

		// Return complete query
		return sprintf($template, $this->from, $fields, $values);
	}

	/**
	 * Build UPDATE query string
	 *
	 * Constructs UPDATE query for single or multiple records.
	 * Uses WHERE clause to target specific records.
	 * Optionally sets updated_at timestamp.
	 *
	 * Query Template:
	 *   UPDATE table SET field1=value1, field2=value2 WHERE conditions LIMIT n
	 *
	 * @param array $data Associative array of field => value pairs to update
	 * @param bool $set_timestamps Whether to set updated_at field
	 * @return string Complete UPDATE query
	 */
	protected function buildUpdate($data, $set_timestamps)
	{
		$parts = array();
		$where = $limit = '';
		$template = "UPDATE %s SET %s %s %s";

		// Add timestamp if requested
		if($set_timestamps)
		{
			$data['updated_at'] = date('Y-m-d H:i:s');
		}

		// Build SET clause
		foreach ($data as $field => $value)
		{
			$parts[] = "{$field}=" . $this->quote($value);
		}

		// Convert to string
		$parts = join(", ", $parts);

		// Build WHERE clause (supports AND/OR operators)
		$queryWhere = $this->where;

		if (!empty($queryWhere))
		{
			$whereParts = array();

			foreach ($queryWhere as $index => $whereClause)
			{
				$condition = $whereClause['condition'];
				$operator = $whereClause['operator'];

				// First condition has no operator prefix
				if ($index === 0)
				{
					$whereParts[] = $condition;
				}
				// Subsequent conditions use their operator
				else
				{
					$whereParts[] = "{$operator} {$condition}";
				}
			}

			$joined = join(" ", $whereParts);

			$where = "WHERE {$joined}";
		}

		// Build LIMIT clause
		$queryLimit = $this->limit;

		if (!empty($queryLimit))
		{
			$limitOffset = $this->offset;

			$limit = "LIMIT {$queryLimit} {$limitOffset}";
		}

		// Return complete query
		return sprintf($template, $this->from, $parts, $where, $limit);
	}

	/**
	 * Build bulk UPDATE query string
	 *
	 * Constructs UPDATE query for multiple records using CASE WHEN pattern.
	 * Updates multiple rows with different values in a single query.
	 *
	 * Query Template:
	 *   UPDATE table
	 *   SET field1 = (CASE id WHEN 1 THEN 'value1' WHEN 2 THEN 'value2' END),
	 *       field2 = (CASE id WHEN 1 THEN 'value3' WHEN 2 THEN 'value4' END)
	 *   WHERE id IN (1, 2)
	 *
	 * @param array $data Multidimensional array indexed by ID
	 * @param array $fields Field names to update
	 * @param array $ids ID values for WHERE IN clause
	 * @param string $key Primary key column name (e.g., 'id')
	 * @param bool $set_timestamps Whether to add updated_at timestamp
	 * @return string Complete UPDATE query
	 */
	protected function buildBulkUpdate($data, $fields, $ids, $key, $set_timestamps = false)
	{
		$parts = array();
		$template = "UPDATE %s SET %s WHERE %s IN (%s) ";

		// Build CASE WHEN for each field
		foreach ($fields as $index => $field)
		{
			// Start CASE statement
			$subparts = $field . ' = (CASE ' . $key . ' ';

			// Add WHEN clause for each ID
			foreach ($data as $id => $info)
			{
	            if (!empty($info))
	            {
					$subparts .=  ' WHEN '. $id . ' THEN ' . $this->quote($info[$field]) . ' ';
	            }
			}

			// End CASE statement
			$subparts .= ' END) ';

			$parts[] = $subparts;
		}

		// Add timestamp if requested (applies to all rows)
		if ($set_timestamps)
		{
			$parts[] = "updated_at = '" . date('Y-m-d H:i:s') . "'";
		}

		// Convert parts to string
		$parts = join(", ", $parts);

		// Build WHERE IN clause
		$queryWhere = $ids;

		if (!empty($queryWhere))
		{
			$where = join(", ", $queryWhere);
		}

		// Return complete query
		return sprintf($template, $this->from, $parts, $key, $where);
	}

	/**
	 * Build DELETE query string
	 *
	 * Constructs DELETE query.
	 * Uses WHERE clause to target specific records.
	 *
	 * Query Template:
	 *   DELETE FROM table WHERE conditions LIMIT n
	 *
	 * @return string Complete DELETE query
	 */
	protected function buildDelete()
	{
		$where = $limit = '';
		$template = "DELETE FROM %s %s %s";

		// Build WHERE clause (supports AND/OR operators)
		$queryWhere = $this->where;

		if (!empty($queryWhere))
		{
			$whereParts = array();

			foreach ($queryWhere as $index => $whereClause)
			{
				$condition = $whereClause['condition'];
				$operator = $whereClause['operator'];

				// First condition has no operator prefix
				if ($index === 0)
				{
					$whereParts[] = $condition;
				}
				// Subsequent conditions use their operator
				else
				{
					$whereParts[] = "{$operator} {$condition}";
				}
			}

			$joined = join(" ", $whereParts);

			$where = "WHERE {$joined}";
		}

		// Build LIMIT clause
		$queryLimit = $this->limit;

		if (!empty($queryLimit))
		{
			$limitOffset = $this->offset;

			$limit = "LIMIT {$queryLimit} {$limitOffset}";
		}

		// Return complete query
		return sprintf($template, $this->from, $where, $limit);
	}

	// =========================================================================
	// EXECUTION METHODS (Execute Queries)
	// =========================================================================

	/**
	 * Insert or update records
	 *
	 * Unified method for single insert, bulk insert, update with where, and bulk update.
	 * Automatically detects operation based on context.
	 *
	 * SINGLE INSERT - Associative array, no where clause:
	 *   $query->save(['name' => 'John', 'email' => 'john@test.com']);
	 *
	 * BULK INSERT - Array of arrays, no where clause:
	 *   $query->save([
	 *       ['name' => 'John', 'email' => 'john@test.com'],
	 *       ['name' => 'Jane', 'email' => 'jane@test.com']
	 *   ]);
	 *
	 * UPDATE WITH WHERE - Any data, has where clause:
	 *   $query->where('id = ?', 1)->save(['name' => 'Updated']);
	 *
	 * BULK UPDATE - Array of rows with key column (pass key as second param):
	 *   $query->save([
	 *       ['id' => 1, 'title' => 'New Title 1'],
	 *       ['id' => 2, 'title' => 'New Title 2']
	 *   ], 'id');
	 *
	 * Detection logic:
	 *   - Second param is string → BULK UPDATE (different values per row)
	 *   - Has where clause → UPDATE (same values to all matched rows)
	 *   - No where + $data[0] is array → BULK INSERT
	 *   - No where + associative array → SINGLE INSERT
	 *
	 * @param array $data Single record, multiple records, or rows for bulk update
	 * @param string|bool $key Key column for bulk update (string) or timestamps flag (bool)
	 * @param bool $set_timestamps Whether to set timestamps (only used with bulk update)
	 * @return mixed Insert ID for single insert, affected rows for bulk insert/update
	 * @throws DatabaseException If query execution fails
	 */
	public function save($data, $key = false)
	{
		// Bulk UPDATE - second param is string (key column name)
		if (is_string($key)) {
			return $this->update($data, $key, $this->timestamps);
		}

		$hasWhere = sizeof($this->where) > 0;

		if ($hasWhere) {
			// UPDATE - same values to all rows matching where clause
			$sql = $this->buildUpdate($data, $this->timestamps);
		} else {
			// INSERT - detect single vs bulk
			$isBulk = isset($data[0]) && is_array($data[0]);

			if ($isBulk) {
				$sql = $this->buildBulkInsert($data, $this->timestamps);
			} else {
				$sql = $this->buildInsert($data, $this->timestamps);
			}
		}

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException($this->connector->lastError() . '<br><br><strong>SQL:</strong><br>' . htmlspecialchars($sql));
		}

		if ($hasWhere) {
			return $this->connector->affectedRows();
		} 
		else {
			// For inserts, return last insert ID (for bulk, this is the first ID)
			return $this->connector->lastInsertId();
		}
	}

	/**
	 * Update record by ID
	 *
	 * Extracts ID from data array, sets WHERE clause, and executes update.
	 *
	 * @param array $data Data array containing 'id' key and fields to update
	 * @return int Number of affected rows
	 * @throws DatabaseException If ID missing or no data to update
	 */
	public function saveById($data)
	{
		// Validate ID exists in data
		if (!isset($data['id']))
		{
			throw new DatabaseException("The unique ID field for update records was not found in the input array");
		}

		// Extract ID and remove from data
		$id = $data['id'];
		unset($data['id']);

		// Validate there is data to update
		if (empty($data))
		{
			throw new DatabaseException("There is no data to update in the query");
		}

		// Add WHERE clause for ID
		$this->where('id = ?', $id);

		// Execute update via save()
		return $this->save($data);
	}

	/**
	 * Bulk UPDATE with different values per row
	 *
	 * Updates multiple rows with different values in a single query using
	 * CASE WHEN statements. Much more efficient than multiple UPDATE queries.
	 *
	 * Data Structure:
	 *   Same format as bulk INSERT - the key column is inside each row:
	 *   [
	 *       ['id' => 1, 'views' => 100, 'title' => 'New Title'],
	 *       ['id' => 2, 'views' => 200, 'title' => 'Other Title'],
	 *       ['id' => 3, 'views' => 50, 'title' => 'Third Title'],
	 *   ]
	 *
	 * SQL Generated:
	 *   UPDATE table SET
	 *     views = (CASE id WHEN 1 THEN 100 WHEN 2 THEN 200 WHEN 3 THEN 50 END),
	 *     title = (CASE id WHEN 1 THEN 'New Title' WHEN 2 THEN 'Other Title' ... END)
	 *   WHERE id IN (1, 2, 3)
	 *
	 * Performance:
	 *   - Old: 1000 rows = 1000 UPDATE queries
	 *   - New: 1000 rows = 1 UPDATE query with CASE statements
	 *
	 * Usage (called from Model::save with 2 arguments):
	 *   PageModel::save([
	 *       ['id' => 1, 'views' => 100],
	 *       ['id' => 2, 'views' => 200],
	 *   ], 'id');
	 *
	 * @param array $data Array of rows, each containing the key column and fields to update
	 * @param string $key Identifier column name (e.g., 'id', 'url_hash')
	 * @param bool $set_timestamps Whether to set updated_at timestamp
	 * @return int Number of affected rows
	 * @throws DatabaseException If query execution fails
	 */
	public function update($data, $key, $set_timestamps = false)
	{
		// Extract IDs and rekey data by ID for buildBulkUpdate
		$ids = [];
		$rekeyedData = [];

		foreach ($data as $row) {
			$id = $row[$key];
			$ids[] = $id;

			// Remove key from row data (we don't want to update the key itself)
			unset($row[$key]);
			$rekeyedData[$id] = $row;
		}

		// Get field names from first row (excluding key)
		$fields = array_keys(reset($rekeyedData));

		$sql = $this->buildBulkUpdate($rekeyedData, $fields, $ids, $key, $set_timestamps);

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException($this->connector->lastError() . '<br><br><strong>SQL:</strong><br>' . htmlspecialchars($sql));
		}

		return $this->connector->affectedRows();
	}

	/**
	 * Insert with INSERT IGNORE (skip duplicates)
	 *
	 * Auto-detects single vs bulk insert based on data structure.
	 * Uses INSERT IGNORE to silently skip duplicate key errors.
	 * Much faster than checking for duplicates with whereIn() before inserting.
	 *
	 * How it Works:
	 *   - Detects single: ['url' => 'x'] vs bulk: [['url' => 'x'], ['url' => 'y']]
	 *   - Builds INSERT IGNORE query (instead of INSERT)
	 *   - Database silently skips rows that violate unique constraints
	 *   - Returns actual number of inserted rows (excludes skipped duplicates)
	 *
	 * Performance:
	 *   - Old approach: SELECT whereIn() + INSERT (2 queries, slow with large datasets)
	 *   - New approach: INSERT IGNORE (1 query, fast even with duplicates)
	 *
	 * Examples:
	 *   // Single
	 *   QueueModel::saveIgnore(['url' => 'https://example.com', 'url_hash' => 'abc123']);
	 *
	 *   // Bulk
	 *   QueueModel::saveIgnore([
	 *       ['url' => 'https://example.com', 'url_hash' => 'abc123'],
	 *       ['url' => 'https://test.com', 'url_hash' => 'def456'],
	 *   ]);
	 *
	 * @param array $data Single record or array of records
	 * @return int Number of rows actually inserted (excludes duplicates)
	 */
	public function saveIgnore($data)
	{
		// Detect if bulk insert (array of arrays) or single insert (associative array)
		$isBulk = isset($data[0]) && is_array($data[0]);

		// Build appropriate INSERT IGNORE query
		if ($isBulk) {
			$sql = $this->buildBulkInsertIgnore($data, $this->timestamps);
		} else {
			$sql = $this->buildInsertIgnore($data, $this->timestamps);
		}

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException($this->connector->lastError() . '<br><br><strong>SQL:</strong><br>' . htmlspecialchars($sql));
		}

		// Return number of rows actually inserted (affected_rows excludes duplicates)
		return $this->connector->affectedRows();
	}

	/**
	 * Build single INSERT IGNORE query (skip duplicates)
	 *
	 * Similar to buildInsert() but uses INSERT IGNORE instead of INSERT.
	 * Duplicate key errors are silently skipped by the database.
	 *
	 * Query Template:
	 *   INSERT IGNORE INTO table (field1, field2, ...) VALUES (val1, val2, ...)
	 *
	 * @param array $data Single record to insert
	 * @param bool $set_timestamps Whether to set timestamps
	 * @return string Complete INSERT IGNORE query
	 */
	protected function buildInsertIgnore($data, $set_timestamps)
	{
		$fields = array();
		$values = array();
		$template = "INSERT IGNORE INTO %s (%s) VALUES (%s)";

		// Add timestamp if requested
		if($set_timestamps)
		{
			$data['created_at'] = date('Y-m-d H:i:s');
		}

		// Build fields and values
		foreach ($data as $field => $value)
		{
			$fields[] = $field;
			$values[] = $this->quote($value);
		}

		// Convert arrays to strings
		$fields = join(", ", $fields);
		$values = join(", ", $values);

		// Return complete query
		return sprintf($template, $this->from, $fields, $values);
	}

	/**
	 * Build INSERT IGNORE query (skip duplicates)
	 *
	 * Similar to buildBulkInsert() but uses INSERT IGNORE instead of INSERT.
	 * Duplicate key errors are silently skipped by the database.
	 *
	 * Query Template:
	 *   INSERT IGNORE INTO table (field1, field2, ...) VALUES (val1, val2, ...), (val3, val4, ...), ...
	 *
	 * @param array $data Array of rows to insert
	 * @param bool $set_timestamps Whether to set timestamps
	 * @return string Complete INSERT IGNORE query
	 */
	protected function buildBulkInsertIgnore($data, $set_timestamps)
	{
		$fields = array();
		$values = array();
		$template = "INSERT IGNORE INTO %s (%s) VALUES %s";

		// Get field names from first row
		$fieldsArray = $data[0];

		foreach ($fieldsArray as $field => $value)
		{
			$fields[] = $field;
		}

		// Get count of rows
		$count = sizeof($data);

		// Build values for each row
		for ($i = 0; $i < $count; $i++)
		{
			$array = $data[$i];
			$valuesArray = array();

			// Quote each value
			foreach ($array as $field => $value)
			{
				$valuesArray[] = $this->quote($value);
			}

			// Group row values in parentheses
			$values[] = '(' . join(", ", $valuesArray) . ')';
		}

		// Convert arrays to strings
		$fields = join(", ", $fields);
		$values = join(", ", $values);

		// Return complete query
		return sprintf($template, $this->from, $fields, $values);
	}

	/**
	 * Insert or update on duplicate key (upsert)
	 *
	 * Inserts new rows or updates existing ones when a duplicate key is encountered.
	 * Auto-detects single vs bulk insert based on data structure.
	 *
	 * Query Template:
	 *   INSERT INTO table (f1, f2) VALUES (v1, v2)
	 *   ON DUPLICATE KEY UPDATE f1 = VALUES(f1), f2 = VALUES(f2)
	 *
	 * Process:
	 *   1. Detect if data is single record (associative) or bulk (array of arrays)
	 *   2. Delegate to buildInsertUpdate() or buildBulkInsertUpdate()
	 *   3. Execute query and return affected row count
	 *
	 * Affected Rows:
	 *   - 1 = row was inserted (new)
	 *   - 2 = row was updated (duplicate key)
	 *   - 0 = row unchanged (duplicate with same values)
	 *
	 * @param array $data Single record or array of records
	 * @param array|null $fields Fields to update (null = all fields)
	 * @return int Number of affected rows
	 */
	public function saveUpdate($data, $fields = null)
	{
		$isBulk = isset($data[0]) && is_array($data[0]);

		if ($isBulk) {
			$sql = $this->buildBulkInsertUpdate($data, $fields, $this->timestamps);
		}
		else {
			$sql = $this->buildInsertUpdate($data, $fields, $this->timestamps);
		}

		if ($this->returnSql) {

			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException($this->connector->lastError() . '<br><br><strong>SQL:</strong><br>' . htmlspecialchars($sql));
		}

		return $this->connector->affectedRows();
	}

	/**
	 * Build single INSERT ... ON DUPLICATE KEY UPDATE query
	 *
	 * Constructs a single-row upsert query. On duplicate key, updates specified
	 * fields with their new values or custom expressions.
	 *
	 * Query Template:
	 *   INSERT INTO table (f1, f2, f3) VALUES (v1, v2, v3)
	 *   ON DUPLICATE KEY UPDATE f1 = VALUES(f1), f2 = expression
	 *
	 * Process:
	 *   1. Add created_at timestamp if requested
	 *   2. Extract field names and quoted values from data
	 *   3. Build UPDATE clause via buildUpdateClause()
	 *   4. Assemble complete query string
	 *
	 * @param array $data Single record to insert (associative array)
	 * @param array|null $fields Fields to update on duplicate (null = all)
	 * @param bool $set_timestamps Whether to add created_at
	 * @return string Complete INSERT ... ON DUPLICATE KEY UPDATE query
	 */
	protected function buildInsertUpdate($data, $fields, $set_timestamps)
	{
		$fieldNames = array();
		$values = array();
		$template = "INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s";

		if ($set_timestamps) {
			$data['created_at'] = date('Y-m-d H:i:s');
		}

		foreach ($data as $field => $value) {
			$fieldNames[] = $field;
			$values[] = $this->quote($value);
		}

		$updateClause = $this->buildUpdateClause($fields, array_keys($data));

		$fieldNames = join(", ", $fieldNames);
		$values = join(", ", $values);

		return sprintf($template, $this->from, $fieldNames, $values, $updateClause);
	}

	/**
	 * Build bulk INSERT ... ON DUPLICATE KEY UPDATE query
	 *
	 * Constructs a multi-row upsert query. Inserts multiple rows in a single
	 * statement, updating specified fields when duplicates are encountered.
	 *
	 * Query Template:
	 *   INSERT INTO table (f1, f2) VALUES (v1, v2), (v3, v4), ...
	 *   ON DUPLICATE KEY UPDATE f1 = VALUES(f1), f2 = expression
	 *
	 * Process:
	 *   1. Extract field names from first row
	 *   2. Iterate all rows, quote values, wrap each in parentheses
	 *   3. Build UPDATE clause via buildUpdateClause()
	 *   4. Assemble complete query string
	 *
	 * @param array $data Array of records to insert
	 * @param array|null $fields Fields to update on duplicate (null = all)
	 * @param bool $set_timestamps Whether to add created_at
	 * @return string Complete INSERT ... ON DUPLICATE KEY UPDATE query
	 */
	protected function buildBulkInsertUpdate($data, $fields, $set_timestamps)
	{
		$fieldNames = array();
		$values = array();
		$template = "INSERT INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE %s";

		$firstRow = $data[0];
		foreach ($firstRow as $field => $value) {
			$fieldNames[] = $field;
		}

		$count = sizeof($data);

		for ($i = 0; $i < $count; $i++) {
			$row = $data[$i];
			$rowValues = array();

			foreach ($row as $field => $value) {
				$rowValues[] = $this->quote($value);
			}

			$values[] = '(' . join(", ", $rowValues) . ')';
		}

		$updateClause = $this->buildUpdateClause($fields, array_keys($firstRow));

		$fieldNames = join(", ", $fieldNames);
		$values = join(", ", $values);

		return sprintf($template, $this->from, $fieldNames, $values, $updateClause);
	}

	/**
	 * Build ON DUPLICATE KEY UPDATE clause
	 *
	 * Constructs the UPDATE portion of an upsert query. Supports both simple
	 * field names (uses VALUES()) and custom expressions.
	 *
	 * Process:
	 *   1. If $fields is null, update all fields using VALUES()
	 *   2. If key is numeric, value is field name: field = VALUES(field)
	 *   3. If key is string, key is field, value is expression: key = value
	 *
	 * Examples:
	 *   null                              → "f1 = VALUES(f1), f2 = VALUES(f2)"
	 *   ['title', 'views']                → "title = VALUES(title), views = VALUES(views)"
	 *   ['title', 'views' => 'views + 1'] → "title = VALUES(title), views = views + 1"
	 *
	 * @param array|null $fields Fields to update (mixed numeric/associative)
	 * @param array $allFields All field names from data (fallback when $fields is null)
	 * @return string UPDATE clause content (without ON DUPLICATE KEY UPDATE prefix)
	 */
	protected function buildUpdateClause($fields, $allFields)
	{
		$updates = array();

		if ($fields === null) {
			foreach ($allFields as $field) {
				$updates[] = "$field = VALUES($field)";
			}
		}
		else {
			foreach ($fields as $key => $value) {
				if (is_numeric($key)) {
					$updates[] = "$value = VALUES($value)";
				}
				else {
					$updates[] = "$key = $value";
				}
			}
		}

		return join(", ", $updates);
	}

	/**
	 * Bulk increment multiple rows with different values
	 *
	 * Efficiently increments fields for multiple records in a single query using CASE statements.
	 * Much faster than individual increment() calls in a loop.
	 *
	 * Data Structure:
	 *   Same format as bulk update - key column is inside each row:
	 *   [
	 *       ['id' => 1, 'views' => 10, 'shares' => 2],
	 *       ['id' => 2, 'views' => 5, 'shares' => 1],
	 *   ]
	 *
	 *   Supports flexible columns - rows can have different fields:
	 *   [
	 *       ['id' => 1, 'forbidden_errors' => 1],
	 *       ['id' => 2, 'rate_limit_errors' => 1],
	 *       ['id' => 3, 'server_errors' => 3],
	 *   ]
	 *   Missing fields keep their original value via ELSE clause.
	 *
	 * Generated SQL:
	 *   UPDATE table SET
	 *     views = CASE WHEN id = 1 THEN views + 10 WHEN id = 2 THEN views + 5 ELSE views END,
	 *     shares = CASE WHEN id = 1 THEN shares + 2 WHEN id = 2 THEN shares + 1 ELSE shares END
	 *   WHERE id IN (1, 2)
	 *
	 * @param array $data Array of rows, each with key column and fields to increment
	 * @param string $key Key column name (e.g., 'id')
	 * @return int Number of rows affected
	 * @throws DatabaseException If query execution fails
	 */
	public function incrementBulk($data, $key)
	{
		if (empty($data)) {
			return 0;
		}

		// Extract IDs and rekey data
		$ids = [];
		$rekeyedData = [];

		foreach ($data as $row) {
			$id = $row[$key];
			$ids[] = $id;

			unset($row[$key]);
			$rekeyedData[$id] = $row;
		}

		// Get ALL unique field names from ALL rows (flexible columns)
		$fields = [];
		foreach ($rekeyedData as $values) {
			foreach (array_keys($values) as $field) {
				$fields[$field] = true;
			}
		}
		$fields = array_keys($fields);

		if (empty($fields)) {
			return 0;
		}

		// Build CASE statement for each field
		$setClauses = [];
		foreach ($fields as $field) {
			$cases = [];
			foreach ($rekeyedData as $id => $values) {
				$amount = (int)($values[$field] ?? 0);
				if ($amount > 0) {
					$cases[] = "WHEN {$key} = {$id} THEN {$field} + {$amount}";
				}
			}

			if (!empty($cases)) {
				$caseStmt = implode(' ', $cases);
				$setClauses[] = "{$field} = CASE {$caseStmt} ELSE {$field} END";
			}
		}

		if (empty($setClauses)) {
			return 0;
		}

		$setClause = implode(', ', $setClauses);
		$whereClause = "{$key} IN (" . implode(',', array_map('intval', $ids)) . ")";

		$sql = "UPDATE {$this->from} SET {$setClause} WHERE {$whereClause}";

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException($this->connector->lastError() . '<br><br><strong>SQL:</strong><br>' . htmlspecialchars($sql));
		}

		return $this->connector->affectedRows();
	}

	/**
	 * Bulk decrement multiple rows with different values
	 *
	 * Efficiently decrements fields for multiple records in a single query using CASE statements.
	 * Much faster than individual decrement() calls in a loop.
	 *
	 * Data Structure:
	 *   Same format as bulk update - key column is inside each row:
	 *   [
	 *       ['id' => 1, 'stock' => 10, 'reserved' => 2],
	 *       ['id' => 2, 'stock' => 5, 'reserved' => 1],
	 *   ]
	 *
	 *   Supports flexible columns - rows can have different fields.
	 *   Missing fields keep their original value via ELSE clause.
	 *
	 * Generated SQL:
	 *   UPDATE table SET
	 *     stock = CASE WHEN id = 1 THEN stock - 10 WHEN id = 2 THEN stock - 5 ELSE stock END,
	 *     reserved = CASE WHEN id = 1 THEN reserved - 2 WHEN id = 2 THEN reserved - 1 ELSE reserved END
	 *   WHERE id IN (1, 2)
	 *
	 * @param array $data Array of rows, each with key column and fields to decrement
	 * @param string $key Key column name (e.g., 'id')
	 * @return int Number of rows affected
	 * @throws DatabaseException If query execution fails
	 */
	public function decrementBulk($data, $key)
	{
		if (empty($data)) {
			return 0;
		}

		// Extract IDs and rekey data
		$ids = [];
		$rekeyedData = [];

		foreach ($data as $row) {
			$id = $row[$key];
			$ids[] = $id;

			unset($row[$key]);
			$rekeyedData[$id] = $row;
		}

		// Get ALL unique field names from ALL rows (flexible columns)
		$fields = [];
		foreach ($rekeyedData as $values) {
			foreach (array_keys($values) as $field) {
				$fields[$field] = true;
			}
		}
		$fields = array_keys($fields);

		if (empty($fields)) {
			return 0;
		}

		// Build CASE statement for each field
		$setClauses = [];
		foreach ($fields as $field) {
			$cases = [];
			foreach ($rekeyedData as $id => $values) {
				$amount = (int)($values[$field] ?? 0);
				if ($amount > 0) {
					$cases[] = "WHEN {$key} = {$id} THEN {$field} - {$amount}";
				}
			}

			if (!empty($cases)) {
				$caseStmt = implode(' ', $cases);
				$setClauses[] = "{$field} = CASE {$caseStmt} ELSE {$field} END";
			}
		}

		if (empty($setClauses)) {
			return 0;
		}

		$setClause = implode(', ', $setClauses);
		$whereClause = "{$key} IN (" . implode(',', array_map('intval', $ids)) . ")";

		$sql = "UPDATE {$this->from} SET {$setClause} WHERE {$whereClause}";

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException($this->connector->lastError() . '<br><br><strong>SQL:</strong><br>' . htmlspecialchars($sql));
		}

		return $this->connector->affectedRows();
	}

	/**
	 * Execute DELETE query
	 *
	 * Deletes records matching WHERE conditions.
	 *
	 * @return MySQLResponse Response with affected rows count
	 * @throws DatabaseException If query execution fails
	 */
	public function delete()
	{
		$sql = $this->buildDelete();

		if ($this->returnSql) {
			return $sql;
		}

		$result = $this->connector->execute($sql);

		if ($result === false) {
			throw new DatabaseException($this->connector->lastError() . '<br><br><strong>SQL:</strong><br>' . htmlspecialchars($sql));
		}

		return $this->connector->affectedRows();
	}

	/**
	 * Get first result from query
	 *
	 * Executes query with LIMIT 1 and returns first row.
	 * Preserves original limit/offset settings.
	 *
	 * @return MySQLResponse Response with single result
	 */
	public function first()
	{
		$this->limit(1);
		$all = $this->all();

		if (is_string($all)) {
			return $all;
		}

		return isset($all[0]) ? $all[0] : null;
	}

	/**
	 * Count matching records
	 *
	 * Executes COUNT(1) query to get number of matching rows.
	 * More efficient than fetching all rows and counting.
	 *
	 * @return int Number of matching rows
	 */
	public function count()
	{
		$this->fields = array($this->from => array("COUNT(1)" => "row_count"));
		$this->limit(1);

		$row = $this->first();

		if (is_string($row)) {
			return $row;
		}

		return ($row !== null) ? (int)$row['row_count'] : 0;
	}

	/**
	 * Get all matching results
	 *
	 * Executes SELECT query and returns all matching rows.
	 *
	 * @return MySQLResponse Response with all results and metadata
	 * @throws DatabaseException If query execution fails
	 */
	public function all()
	{
		$sql = $this->buildSelect();

		if ($this->returnSql) {
			return $sql;
		}

		// Async mode: Fire query and return Promise
		if ($this->async) {
			$mysqli = $this->connector->executeAsync($sql);
			return new MySQLAsync($mysqli, function($result) {
				$rows = [];
				while ($row = $result->fetch_assoc()) {
					$rows[] = $row;
				}
				return $rows;
			});
		}

		// Execute query (buffered or unbuffered based on flag)
		if ($this->unbuffered) {
			// Unbuffered mode: Return mysqli_result for streaming
			$result = $this->connector->executeNoBuffer($sql);

			if ($result === false) {
				throw new DatabaseException($this->connector->lastError() . '<br><br><strong>SQL:</strong><br>' . htmlspecialchars($sql));
			}

			// Return mysqli_result directly (user iterates with fetch_assoc())
			return $result;
		}
		else {
			// Buffered mode: Load all results into array (default behavior)
			$result = $this->connector->execute($sql);

			if ($result === false) {
				throw new DatabaseException($this->connector->lastError() . '<br><br><strong>SQL:</strong><br>' . htmlspecialchars($sql));
			}

			$results = array();
			while ($row = $result->fetch_assoc()) {
				$results[] = $row;
			}

			return $results;
		}
	}

	/**
	 * Execute raw SQL query with optional parameter binding
	 *
	 * Executes a raw SQL query directly on the database.
	 * Supports optional parameter binding for safe value injection.
	 * Respects unbuffered mode when set via noBuffer().
	 *
	 * Parameter Binding:
	 *   Use ? as placeholders, pass values as additional arguments.
	 *   Values are automatically escaped for security.
	 *
	 * Unbuffered Mode:
	 *   Chain with noBuffer() for memory-efficient large result sets.
	 *   Fetches rows one at a time instead of loading all into memory.
	 *
	 * Examples:
	 *   sql('SELECT * FROM users')
	 *   → Executes query directly
	 *
	 *   sql('SELECT * FROM users WHERE age > ? AND status = ?', 18, 'active')
	 *   → SELECT * FROM users WHERE age > 18 AND status = 'active'
	 *
	 *   noBuffer()->sql('SELECT * FROM large_table')
	 *   → Executes in unbuffered mode for memory efficiency
	 *
	 * @param string $query SQL query (with ? placeholders if binding)
	 * @param mixed ...$params Values to bind to placeholders
	 * @return mixed Query result or mysqli_result for unbuffered
	 * @throws DatabaseException If query fails or param count mismatch
	 */
	public function sql($query, ...$params)
	{
		if (empty($query)) throw new DatabaseException("No query provided for sql()");

		// If params passed, do binding
		if (!empty($params))
		{
			$placeholderCount = substr_count($query, '?');

			if ($placeholderCount !== count($params)) {
				throw new DatabaseException("Parameter count mismatch: {$placeholderCount} placeholders, " . count($params) . " params provided");
			}

			$query = preg_replace("#\?#", "%s", $query);

			$quotedParams = array_map(function($param) {
				return $this->quote($param);
			}, $params);

			$query = vsprintf($query, $quotedParams);
		}

		// Execute - respect unbuffered flag
		if ($this->unbuffered) $result = $this->connector->executeNoBuffer($query);
		else $result = $this->connector->execute($query);

		if ($result === false) throw new DatabaseException($this->connector->lastError() . ' (SQL: ' . $query . ')');

		return $result;
	}
}
