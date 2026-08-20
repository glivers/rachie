<?php namespace Rackage;

/**
 * Queue Helper
 *
 * Provides in-memory FIFO (First In, First Out) queue data structure operations
 * using PHP's native SplQueue class. Queues enforce strict ordering where items
 * are processed in the exact order they were added.
 *
 * A queue is like a line at a store: first person in line gets served first.
 * Items are added to the back (enqueue) and removed from the front (dequeue).
 *
 * Common Use Cases:
 *   - URL crawling queues (process URLs in discovery order)
 *   - Email sending queues (send emails in order received)
 *   - Batch data processing (process API responses sequentially)
 *   - Job processing (execute tasks in submission order)
 *   - Image processing queues (resize/optimize in upload order)
 *   - CSV import queues (process rows in file order)
 *
 * Queue vs Array:
 *   - Queue enforces FIFO ordering (arrays don't)
 *   - Queue prevents random access (can't access middle items)
 *   - Queue is semantically clearer for sequential processing
 *   - Queue operations are O(1) for enqueue/dequeue
 *   - Arrays allow arbitrary insertion/removal, queues don't
 *
 * In-Memory Only:
 *   All queues are cleared when request/script ends. For persistent queues
 *   that survive across requests or processes, use database-backed queues
 *   (MySQL, PostgreSQL, Redis) instead.
 *
 * Static Design Pattern:
 *   Uses static methods (no instance creation). Manages multiple named queues
 *   internally. Each queue name is a separate queue instance.
 *
 * Performance:
 *   - Push: O(1) constant time
 *   - Pop: O(1) constant time
 *   - Peek: O(1) constant time
 *   - Count: O(1) constant time
 *   - Memory: Efficient, no reindexing needed
 *
 * Usage Examples:
 *
 *   // URL crawler queue
 *   Queue::push('urls_to_crawl', 'https://example.com');
 *   Queue::push('urls_to_crawl', 'https://example.com/about');
 *   Queue::push('urls_to_crawl', 'https://example.com/contact');
 *
 *   while (!Queue::isEmpty('urls_to_crawl')) {
 *       $url = Queue::pop('urls_to_crawl');
 *       $html = file_get_contents($url);
 *       processPage($html);
 *   }
 *
 *   // Email sending queue
 *   foreach ($users as $user) {
 *       Queue::push('emails', [
 *           'to' => $user['email'],
 *           'subject' => 'Newsletter',
 *           'body' => $emailContent
 *       ]);
 *   }
 *
 *   while (!Queue::isEmpty('emails')) {
 *       $email = Queue::pop('emails');
 *       Mail::to($email['to'])
 *           ->subject($email['subject'])
 *           ->body($email['body'])
 *           ->send();
 *   }
 *
 *   // Batch API data processing
 *   $response = callExternalAPI();
 *   foreach ($response['items'] as $item) {
 *       Queue::push('batch_import', $item);
 *   }
 *
 *   while (!Queue::isEmpty('batch_import')) {
 *       $item = Queue::pop('batch_import');
 *       ProductModel::save([
 *           'sku' => $item['sku'],
 *           'name' => $item['name'],
 *           'price' => $item['price']
 *       ]);
 *   }
 *
 *   // Job processing with task types
 *   Queue::push('jobs', ['type' => 'resize_image', 'path' => 'uploads/photo1.jpg']);
 *   Queue::push('jobs', ['type' => 'send_email', 'user_id' => 123]);
 *   Queue::push('jobs', ['type' => 'generate_pdf', 'order_id' => 456]);
 *
 *   while (!Queue::isEmpty('jobs')) {
 *       $job = Queue::pop('jobs');
 *
 *       switch ($job['type']) {
 *           case 'resize_image':
 *               resizeImage($job['path']);
 *               break;
 *           case 'send_email':
 *               sendNotification($job['user_id']);
 *               break;
 *           case 'generate_pdf':
 *               generateInvoice($job['order_id']);
 *               break;
 *       }
 *   }
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Queue
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

class Queue {

	/**
	 * In-memory queue storage
	 * Stores SplQueue instances indexed by queue name.
	 * Cleared when request/script ends.
	 *
	 * Format: ['urls_to_crawl' => SplQueue, 'batch_import' => SplQueue, ...]
	 *
	 * @var array<string, \SplQueue>
	 */
	private static $queues = [];

	/**
	 * Private constructor to prevent instantiation
	 *
	 * This class uses static methods only - no instances should be created.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning
	 *
	 * Ensures singleton-like behavior even though we don't use instances.
	 */
	private function __clone() {}

	// ===========================================================================
	// CORE OPERATIONS (Push, Pop, Peek)
	// ===========================================================================

	/**
	 * Push item onto queue (add to back)
	 *
	 * Adds an item to the back of the queue. This is the FIFO enqueue operation.
	 * Items are processed in the order they are pushed.
	 *
	 * Process:
	 * 1. Get or create queue instance for given name
	 * 2. Add item to back of queue (SplQueue::enqueue)
	 *
	 * Queue Behavior:
	 *   First pushed → First popped
	 *   Queue::push('q', 'A');
	 *   Queue::push('q', 'B');
	 *   Queue::push('q', 'C');
	 *   Queue::pop('q') returns 'A', then 'B', then 'C'
	 *
	 * Data Types:
	 *   Item can be any PHP type: string, int, array, object, etc.
	 *   Commonly used: arrays for structured data, strings for simple values.
	 *
	 * Usage:
	 *   Queue::push('urls', 'https://example.com');
	 *   Queue::push('emails', ['to' => 'user@example.com', 'subject' => 'Hi']);
	 *   Queue::push('jobs', ['type' => 'resize', 'path' => 'photo.jpg']);
	 *
	 * @param string $name Queue identifier
	 * @param mixed $item Item to add (any type)
	 * @return void
	 */
	public static function push($name, $item)
	{
		$queue = self::getQueue($name);
		$queue->enqueue($item);
	}

	/**
	 * Pop item from queue (remove from front)
	 *
	 * Removes and returns the item at the front of the queue. This is the
	 * FIFO dequeue operation. Returns null if queue is empty.
	 *
	 * Process:
	 * 1. Get queue instance
	 * 2. Check if empty
	 * 3. Remove and return front item (SplQueue::dequeue)
	 *
	 * Queue Behavior:
	 *   Queue::push('q', 'A');
	 *   Queue::push('q', 'B');
	 *   Queue::pop('q') returns 'A'  // First in, first out
	 *   Queue::pop('q') returns 'B'
	 *   Queue::pop('q') returns null // Empty
	 *
	 * Usage:
	 *   $url = Queue::pop('urls');
	 *   if ($url !== null) {
	 *       processURL($url);
	 *   }
	 *
	 *   // Safe pattern (check before pop)
	 *   if (!Queue::isEmpty('jobs')) {
	 *       $job = Queue::pop('jobs');
	 *       executeJob($job);
	 *   }
	 *
	 *   // Process all items
	 *   while (!Queue::isEmpty('emails')) {
	 *       $email = Queue::pop('emails');
	 *       sendEmail($email);
	 *   }
	 *
	 * @param string $name Queue identifier
	 * @return mixed|null Item from front of queue, or null if empty
	 */
	public static function pop($name)
	{
		$queue = self::getQueue($name);

		// Return null if queue is empty
		if ($queue->isEmpty()) {
			return null;
		}

		// Remove and return front item
		return $queue->dequeue();
	}

	/**
	 * Push multiple items onto queue (batch enqueue)
	 *
	 * Adds multiple items to the back of the queue in order.
	 * This is syntactic sugar - internally loops and calls enqueue() for each item.
	 *
	 * Process:
	 * 1. Get or create queue instance
	 * 2. Loop through items array
	 * 3. Add each item to back of queue (SplQueue::enqueue)
	 *
	 * Performance Note:
	 *   No performance benefit over manual loop - SplQueue enqueues one at a time.
	 *   Provided for cleaner syntax when adding multiple items.
	 *
	 * Usage:
	 *   // Add multiple URLs at once
	 *   $urls = ['https://example.com', 'https://example.com/about', 'https://example.com/contact'];
	 *   Queue::pushMany('urls', $urls);
	 *
	 *   // Batch job submission
	 *   $jobs = [
	 *       ['type' => 'email', 'to' => 'user1@example.com'],
	 *       ['type' => 'email', 'to' => 'user2@example.com'],
	 *       ['type' => 'email', 'to' => 'user3@example.com']
	 *   ];
	 *   Queue::pushMany('jobs', $jobs);
	 *
	 *   // Equivalent to:
	 *   foreach ($jobs as $job) {
	 *       Queue::push('jobs', $job);
	 *   }
	 *
	 * @param string $name Queue identifier
	 * @param array $items Array of items to add
	 * @return void
	 */
	public static function pushMany($name, $items)
	{
		$queue = self::getQueue($name);

		foreach ($items as $item) {
			$queue->enqueue($item);
		}
	}

	/**
	 * Pop multiple items from queue (batch dequeue)
	 *
	 * Removes and returns multiple items from the front of the queue.
	 * Returns up to $count items, or fewer if queue doesn't have enough.
	 * Returns empty array if queue is empty.
	 *
	 * Process:
	 * 1. Get queue instance
	 * 2. Loop up to $count times
	 * 3. Remove and collect front items
	 * 4. Stop early if queue becomes empty
	 *
	 * Queue Behavior:
	 *   Queue has: ['A', 'B', 'C', 'D', 'E']
	 *   Queue::popMany('q', 3) returns ['A', 'B', 'C']
	 *   Queue now has: ['D', 'E']
	 *
	 * Usage:
	 *   // Batch URL processing
	 *   $urls = Queue::popMany('urls', 100);
	 *   foreach ($urls as $url) {
	 *       crawlURL($url);
	 *   }
	 *
	 *   // Worker batch processing
	 *   while (!Queue::isEmpty('jobs')) {
	 *       $batch = Queue::popMany('jobs', 10);
	 *       processBatch($batch);
	 *       Log::info('Processed batch', ['count' => count($batch)]);
	 *   }
	 *
	 *   // Handle partial batches
	 *   $batch = Queue::popMany('emails', 50);
	 *   if (count($batch) < 50) {
	 *       Log::info('Final batch', ['remaining' => count($batch)]);
	 *   }
	 *
	 * @param string $name Queue identifier
	 * @param int $count Maximum number of items to pop
	 * @return array Array of items (may be fewer than $count if queue is small)
	 */
	public static function popMany($name, $count)
	{
		$queue = self::getQueue($name);
		$items = [];

		// Pop up to $count items (or until queue is empty)
		for ($i = 0; $i < $count; $i++) {
			if ($queue->isEmpty()) {
				break;
			}

			$items[] = $queue->dequeue();
		}

		return $items;
	}

	/**
	 * Peek at front item without removing
	 *
	 * Returns the item at the front of the queue WITHOUT removing it.
	 * Useful for inspecting the next item before deciding to process it.
	 * Returns null if queue is empty.
	 *
	 * Process:
	 * 1. Get queue instance
	 * 2. Check if empty
	 * 3. Return front item using bottom() method (does not dequeue)
	 *
	 * Queue Behavior:
	 *   Queue::push('q', 'A');
	 *   Queue::push('q', 'B');
	 *   Queue::peek('q') returns 'A'  // Does NOT remove
	 *   Queue::peek('q') returns 'A'  // Still there
	 *   Queue::pop('q')  returns 'A'  // Now removed
	 *   Queue::peek('q') returns 'B'  // Next item
	 *
	 * Usage:
	 *   // Check next job type without removing it
	 *   $nextJob = Queue::peek('jobs');
	 *   if ($nextJob && $nextJob['type'] === 'expensive_operation') {
	 *       // Skip during high traffic
	 *       Log::info('Deferring expensive job', $nextJob);
	 *   } else {
	 *       $job = Queue::pop('jobs');
	 *       executeJob($job);
	 *   }
	 *
	 *   // Preview next URL without processing
	 *   $nextUrl = Queue::peek('urls');
	 *   if ($nextUrl !== null) {
	 *       Log::debug('Next URL to crawl', ['url' => $nextUrl]);
	 *   }
	 *
	 * @param string $name Queue identifier
	 * @return mixed|null Item at front of queue, or null if empty
	 */
	public static function peek($name)
	{
		$queue = self::getQueue($name);

		// Return null if queue is empty
		if ($queue->isEmpty()) {
			return null;
		}

		// Return front item without removing (bottom = front in SplQueue)
		return $queue->bottom();
	}

	// ===========================================================================
	// QUERY OPERATIONS (Check State)
	// ===========================================================================

	/**
	 * Check if queue is empty
	 *
	 * Returns true if queue has no items, false otherwise.
	 * Use this before pop() to avoid null returns.
	 *
	 * Usage:
	 *   if (!Queue::isEmpty('urls')) {
	 *       $url = Queue::pop('urls');
	 *       crawlURL($url);
	 *   }
	 *
	 *   // Process all items
	 *   while (!Queue::isEmpty('jobs')) {
	 *       executeJob(Queue::pop('jobs'));
	 *   }
	 *
	 *   // Count remaining
	 *   $remaining = Queue::count('batch_import');
	 *   if (!Queue::isEmpty('batch_import')) {
	 *       Log::info("Processing batch: {$remaining} items remaining");
	 *   }
	 *
	 * @param string $name Queue identifier
	 * @return bool True if queue is empty, false otherwise
	 */
	public static function isEmpty($name)
	{
		$queue = self::getQueue($name);
		return $queue->isEmpty();
	}

	/**
	 * Get queue size (number of items)
	 *
	 * Returns the number of items currently in the queue.
	 *
	 * Usage:
	 *   $urlCount = Queue::count('urls');
	 *   Log::info("URLs queued for crawling: {$urlCount}");
	 *
	 *   $emailCount = Queue::count('emails');
	 *   echo "Sending {$emailCount} emails...";
	 *
	 *   // Progress tracking
	 *   $total = 1000;
	 *   $remaining = Queue::count('batch_import');
	 *   $processed = $total - $remaining;
	 *   $progress = ($processed / $total) * 100;
	 *   echo "Progress: {$progress}%";
	 *
	 * @param string $name Queue identifier
	 * @return int Number of items in queue
	 */
	public static function count($name)
	{
		$queue = self::getQueue($name);
		return $queue->count();
	}

	/**
	 * Check if queue exists
	 *
	 * Returns true if a queue with the given name has been created.
	 *
	 * Usage:
	 *   if (Queue::has('urls')) {
	 *       echo "URL queue exists with " . Queue::count('urls') . " items";
	 *   }
	 *
	 *   // Conditional processing
	 *   if (Queue::has('high_priority_jobs')) {
	 *       processQueue('high_priority_jobs');
	 *   } else {
	 *       processQueue('normal_jobs');
	 *   }
	 *
	 * @param string $name Queue identifier
	 * @return bool True if queue exists, false otherwise
	 */
	public static function has($name)
	{
		return isset(self::$queues[$name]);
	}

	// ===========================================================================
	// UTILITY OPERATIONS (Clear, Convert)
	// ===========================================================================

	/**
	 * Clear entire queue
	 *
	 * Removes all items from the queue by creating a fresh SplQueue instance.
	 * Useful for resetting state or discarding queued items.
	 *
	 * Usage:
	 *   // Clear failed jobs
	 *   Queue::clear('failed_jobs');
	 *
	 *   // Reset crawler state
	 *   Queue::clear('urls');
	 *   Queue::clear('visited');
	 *
	 *   // Cancel pending emails
	 *   $cancelCount = Queue::count('emails');
	 *   Queue::clear('emails');
	 *   Log::info("Cancelled {$cancelCount} pending emails");
	 *
	 * @param string $name Queue identifier
	 * @return void
	 */
	public static function clear($name)
	{
		self::$queues[$name] = new \SplQueue();
	}

	/**
	 * Convert queue to array
	 *
	 * Returns all items in the queue as an array, preserving order.
	 * Does NOT remove items from queue (non-destructive).
	 *
	 * Order:
	 *   Array[0] = front of queue (next to be popped)
	 *   Array[n] = back of queue (last to be popped)
	 *
	 * Usage:
	 *   // Preview all queued URLs
	 *   $urls = Queue::toArray('urls');
	 *   foreach ($urls as $index => $url) {
	 *       echo "#{$index}: {$url}\n";
	 *   }
	 *
	 *   // Export queue state for logging
	 *   $jobs = Queue::toArray('jobs');
	 *   Log::debug('Current job queue', ['jobs' => $jobs]);
	 *
	 *   // Batch processing preview
	 *   $batch = Queue::toArray('batch_import');
	 *   echo "Ready to process " . count($batch) . " items";
	 *
	 * @param string $name Queue identifier
	 * @return array All items in queue (front to back)
	 */
	public static function toArray($name)
	{
		$queue = self::getQueue($name);

		// Convert SplQueue to array
		$items = [];
		foreach ($queue as $item) {
			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Remove all queues
	 *
	 * Clears all in-memory queues. Useful for cleanup or testing.
	 *
	 * Usage:
	 *   // Cleanup after batch processing
	 *   Queue::flush();
	 *
	 *   // Reset state in tests
	 *   Queue::flush();
	 *   $this->assertEquals(0, Queue::count('test_queue'));
	 *
	 * @return void
	 */
	public static function flush()
	{
		self::$queues = [];
	}

	// ===========================================================================
	// INTERNAL HELPERS
	// ===========================================================================

	/**
	 * Get or create queue instance (internal factory method)
	 *
	 * Returns an SplQueue instance for the given name.
	 * Creates and caches a new SplQueue if it doesn't exist.
	 *
	 * Process:
	 * 1. Check if queue exists in static cache
	 * 2. If not, create new SplQueue instance
	 * 3. Store in static cache for reuse
	 * 4. Return queue instance
	 *
	 * @param string $name Queue identifier
	 * @return \SplQueue Queue instance
	 */
	private static function getQueue($name)
	{
		// Create and cache if doesn't exist
		if (!isset(self::$queues[$name])) {
			self::$queues[$name] = new \SplQueue();
		}

		return self::$queues[$name];
	}

}
