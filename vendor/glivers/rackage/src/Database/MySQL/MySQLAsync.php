<?php namespace Rackage\Database\MySQL;

/**
 * Async Query Result
 *
 * Represents a pending asynchronous MySQL query result.
 * Returned by terminal methods (all, first, count, etc.) when async mode is enabled.
 *
 * How It Works:
 *   1. Query is fired with MYSQLI_ASYNC flag (non-blocking)
 *   2. Terminal method returns MySQLAsync instead of data
 *   3. Call await() when you need the result (blocks until ready)
 *   4. MySQLAsync processes raw result using the provided callback
 *
 * Benefits:
 *   - Fire query early, fetch result later
 *   - Do other work while query runs
 *   - Cleaner code than manual mysqli_poll handling
 *
 * Limitations: 
 *   - Single connection = one async query at a time
 *   - Must call await() before starting another async query
 *   - Connection pool needed for true parallelism (future)
 *
 * Usage:
 *   // Fire async query (returns immediately)
 *   $async = PageModel::async()->where('status', 'active')->all();
 *
 *   // Do other work...
 *   $calculation = expensiveOperation();
 *
 *   // Now get the result (blocks until ready)
 *   $pages = $async->await();
 *
 * With Multiple Queries (sequential, same connection):
 *   $a1 = Model::async()->where('id', 1)->first();
 *   $row1 = $a1->await();  // must await before next async
 *
 *   $a2 = Model::async()->where('id', 2)->first();
 *   $row2 = $a2->await();
 *
 * @package Rackage\Database\MySQL
 */
class MySQLAsync
{
	/**
	 * The mysqli connection that executed the async query
	 *
	 * @var \mysqli
	 */
	private \mysqli $connection;

	/**
	 * Callback to process the raw mysqli_result
	 *
	 * Each terminal method provides its own processor:
	 *   - all():    fn($r) => $r->fetch_all(MYSQLI_ASSOC)
	 *   - first():  fn($r) => $r->fetch_assoc()
	 *   - count():  fn($r) => (int) $r->fetch_row()[0]
	 *   - delete(): fn($r, $c) => $c->affected_rows
	 *
	 * @var \Closure
	 */
	private \Closure $processor;

	/**
	 * Whether the result has been fetched
	 *
	 * @var bool
	 */
	private bool $resolved = false;

	/**
	 * Cached result after await() is called
	 *
	 * @var mixed
	 */
	private mixed $result = null;

	/**
	 * Create a new Promise
	 *
	 * Called internally by terminal methods when async mode is enabled.
	 * The query has already been fired with MYSQLI_ASYNC at this point.
	 *
	 * @param \mysqli $connection The connection with pending async query
	 * @param callable $processor Callback to process raw result
	 */
	public function __construct(\mysqli $connection, callable $processor)
	{
		$this->connection = $connection;
		$this->processor = $processor instanceof \Closure
			? $processor
			: \Closure::fromCallable($processor);
	}

	/**
	 * Wait for query to complete and return processed result
	 *
	 * Polls the connection until the async query is ready,
	 * then reaps the result and processes it using the callback
	 * provided by the terminal method.
	 *
	 * Blocking Behavior:
	 *   - Polls every 50ms until result is ready
	 *   - Returns immediately if already resolved
	 *   - Caches result for subsequent calls
	 *
	 * Example:
	 *   $promise = Model::async()->where('id', 1)->first();
	 *   $row = $promise->await();  // blocks until ready
	 *   $row = $promise->await();  // returns cached result instantly
	 *
	 * @return mixed The processed query result (array, row, int, etc.)
	 * @throws DatabaseException If query failed
	 */
	public function await(): mixed
	{
		// Return cached result if already resolved
		if ($this->resolved) {
			return $this->result;
		}

		// Poll until connection has result ready
		do {
			$links = [$this->connection];
			$errors = [];
			$reject = [];
			$ready = mysqli_poll($links, $errors, $reject, 0, 50000); // 50ms timeout
		} while ($ready === 0 && empty($errors) && empty($reject));

		// Check for errors
		if (!empty($errors) || !empty($reject)) {
			throw new DatabaseException("Async query failed during poll");
		}

		// Reap the async result
		$rawResult = $this->connection->reap_async_query();

		if ($rawResult === false) {
			throw new DatabaseException(
				$this->connection->error . ' (async query)'
			);
		}

		// Process using the terminal method's callback
		$this->result = ($this->processor)($rawResult, $this->connection);
		$this->resolved = true;

		// Free result if it's a mysqli_result
		if ($rawResult instanceof \mysqli_result) {
			$rawResult->free();
		}

		return $this->result;
	}

	/**
	 * Check if the async query result is ready without blocking
	 *
	 * Useful for checking status without waiting.
	 * Does not fetch or process the result.
	 *
	 * Example:
	 *   $promise = Model::async()->where(...)->all();
	 *
	 *   while (!$promise->ready()) {
	 *       doOtherWork();
	 *   }
	 *
	 *   $data = $promise->await();
	 *
	 * @return bool True if result is ready or already resolved
	 */
	public function ready(): bool
	{
		if ($this->resolved) {
			return true;
		}

		$links = [$this->connection];
		$errors = [];
		$reject = [];

		// Non-blocking check (0 timeout)
		$ready = mysqli_poll($links, $errors, $reject, 0, 0);

		return $ready > 0 || !empty($errors) || !empty($reject);
	}

	/**
	 * Check if the promise has been resolved
	 *
	 * @return bool True if await() has been called and completed
	 */
	public function isResolved(): bool
	{
		return $this->resolved;
	}
}
