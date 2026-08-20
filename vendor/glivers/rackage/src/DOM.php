<?php namespace Rackage;

/**
 * DOM - HTML/XML Document Parser
 *
 * Simplified DOM parsing and extraction for web crawling and content extraction.
 * Wraps PHP's DOMDocument with a clean, intuitive API.
 *
 * Purpose: Parse HTML documents and extract content for search engine indexing.
 *
 * Basic Usage:
 *   $dom = new DOM();
 *   $dom->load($html, 'https://example.co.ke');
 *
 *   echo $dom->title();
 *   echo $dom->description();
 *
 *   $links = $dom->links();
 *   $images = $dom->images();
 *
 * Features:
 *   - CSS selector support
 *   - Metadata extraction (title, description, author, etc.)
 *   - Link extraction (internal/external/TLD filtering)
 *   - Image extraction with alt text
 *   - Emphasized text extraction (bold, italic, underline) for keyword scoring
 *   - Automatic relative-to-absolute URL conversion
 *   - Content cleaning (remove scripts/styles)
 *   - Structured heading extraction
 *   - TLD filtering (.ke, .co.ke, etc.)
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\DOM
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.0
 */

class DOM
{
	/**
	 * DOMDocument instance
	 * @var \DOMDocument
	 */
	private $document;

	/**
	 * DOMXPath instance for querying
	 * @var \DOMXPath
	 */
	private $xpath;

	/**
	 * Base URL 'https://domain.com' for resolving relative URLs
	 * @var string|null
	 */
	private $baseUrl;

	/**
	 * Base Host 'domain.com' for resolving relative URLs
	 * @var string|null
	 */
	private $baseHost;

	/**
	 * Base Port '8080' for resolving relative URLs
	 * @var string|null
	 */
	private $basePort;

	/**
	 * Base Scheme 'https' for resolving relative URLs
	 * @var string|null
	 */
	private $baseScheme;

	/**
	 * Current filtered node list
	 * @var \DOMNodeList|array
	 */
	private $nodes = [];

	/**
	 * Constructor - Initialize empty DOM
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->document = new \DOMDocument('1.0', 'UTF-8');
		// $this->document->preserveWhiteSpace  = false;
		// $this->document->strictErrorChecking = false;
	}

	// =========================================================================
	// LOADING & CONFIGURATION
	// =========================================================================

	/**
	 * Load HTML content
	 *
	 * Parses HTML string and optionally sets base URL for resolving relative links.
	 * Suppresses warnings from malformed HTML.
	 *
	 * Examples:
	 *   $dom->load($html);
	 *   $dom->load($html, 'https://example.co.ke');
	 *
	 * @param string $html HTML content to parse
	 * @param string|null $baseUrl Base URL for resolving relative URLs
	 * @return self Returns self for chaining
	 */
	public function load($html, $baseUrl = null)
	{
		// Set base URL if provided
		if ($baseUrl !== null) {
			$this->setBaseUrl($baseUrl);
		}

		// Suppress warnings from malformed HTML
		libxml_use_internal_errors(true);

		// Load HTML with UTF-8 encoding
		// Add meta charset to ensure proper encoding
		$this->document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE | LIBXML_BIGLINES);
		// $this->document->loadHTML('<?xml encoding="UTF-8">', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE | LIBXML_BIGLINES);
		
		// Clear libxml errors
		libxml_clear_errors();

		// Create XPath instance for querying
		$this->xpath = new \DOMXPath($this->document);

		// Initialize nodes to all nodes
		$this->nodes = $this->xpath->query('//*');

		return $this;
	}

	/**
	 * Set base URL for resolving relative URLs
	 *
	 * Examples:
	 *   $dom->setBaseUrl('https://example.co.ke');
	 *   $dom->setBaseUrl('https://example.co.ke/page/');
	 *
	 * @param string $url Base URL
	 * @return self Returns self for chaining
	 */
	public function setBaseUrl($url)
	{
		// Parse the base URL
		$base = parse_url($url, PHP_URL_HOST);

		$this->baseUrl 	= rtrim($url, '/');
		$this->baseHost	= preg_replace('/^www\./i', '', $base['host'] ?? '');
		$this->baseScheme 	= $base['scheme'] ?? 'http';
		$this->basePort 	= isset($base['port']) ? ':' . $base['port'] : '';

		return $this;
	}

	// =========================================================================
	// SELECTION & FILTERING
	// =========================================================================

	/**
	 * Filter nodes by CSS selector
	 *
	 * Converts CSS selector to XPath and filters current nodes.
	 * Chainable for multiple filters.
	 *
	 * Examples:
	 *   $dom->filter('a');
	 *   $dom->filter('.content p');
	 *   $dom->filter('meta[name="description"]');
	 *   $dom->filter('h1, h2, h3');
	 *
	 * @param string $selector CSS selector
	 * @return self Returns self for chaining
	 */
	public function filter($selector)
	{
		$xpath = $this->cssToXpath($selector);
		$this->nodes = $this->xpath->query($xpath);

		return $this;
	}

	/**
	 * Get first node
	 *
	 * Returns new DOM instance with only the first node.
	 *
	 * Examples:
	 *   $dom->filter('p')->first()->text();
	 *
	 * @return self Returns new instance with first node
	 */
	public function first()
	{
		$new = clone $this;
		$new->nodes = ($this->nodes->length > 0) ? [$this->nodes->item(0)] : [];

		return $new;
	}

	/**
	 * Get last node
	 *
	 * Returns new DOM instance with only the last node.
	 *
	 * Examples:
	 *   $dom->filter('p')->last()->text();
	 *
	 * @return self Returns new instance with last node
	 */
	public function last()
	{
		$new 		= clone $this;
		$count 		= $this->nodes->length;
		$new->nodes = ($count > 0) ? [$this->nodes->item($count - 1)] : [];

		return $new;
	}

	/**
	 * Get node at specific index
	 *
	 * Returns new DOM instance with node at given index (0-based).
	 *
	 * Examples:
	 *   $dom->filter('p')->eq(2)->text();  // Get 3rd paragraph
	 *
	 * @param int $index Zero-based index
	 * @return self Returns new instance with node at index
	 */
	public function eq($index)
	{
		$new 		= clone $this;
		$new->nodes = ($this->nodes->length > $index) ? [$this->nodes->item($index)] : [];

		return $new;
	}

	/**
	 * Count filtered nodes
	 *
	 * Examples:
	 *   if ($dom->filter('p')->count() > 0) { ... }
	 *
	 * @return int Number of nodes
	 */
	public function count()
	{
		return is_array($this->nodes) ? count($this->nodes) : $this->nodes->length;
	}

	/**
	 * Check if filtered nodes exist
	 *
	 * Examples:
	 *   if ($dom->filter('.author')->exists()) {
	 *       echo $dom->text();
	 *   }
	 *
	 * @return bool True if nodes exist
	 */
	public function exists()
	{
		return $this->count() > 0;
	}

	/**
	 * Check if current node matches selector
	 *
	 * Examples:
	 *   if ($dom->filter('div')->first()->matches('.content')) { ... }
	 *
	 * @param string $selector CSS selector to match
	 * @return bool True if matches
	 */
	public function matches($selector)
	{
		if (!$this->exists()) { return false; }

		$node 	= is_array($this->nodes) ? $this->nodes[0] : $this->nodes->item(0);
		$xpath 	= $this->cssToXpath($selector);
		$result = $this->xpath->query($xpath, $node);

		return $result->length > 0;
	}

	/**
	 * Find closest ancestor matching selector
	 *
	 * Traverses up the DOM tree to find nearest ancestor matching selector.
	 *
	 * Examples:
	 *   $dom->filter('span')->closest('div');
	 *
	 * @param string $selector CSS selector
	 * @return self Returns new instance with matching ancestor
	 */
	public function closest($selector)
	{
		$new = clone $this;

		if (!$this->exists()) {

			$new->nodes = [];
			return $new;
		}

		$node 	= is_array($this->nodes) ? $this->nodes[0] : $this->nodes->item(0);
		$parent = $node->parentNode;

		while ($parent && $parent instanceof \DOMElement) {

			// Create temp DOM to test if parent matches selector
			$temp 	= clone $this;
			$temp->nodes = [$parent];

			if ($temp->matches($selector)) {

				$new->nodes = [$parent];
				return $new;
			}

			$parent = $parent->parentNode;
		}

		$new->nodes = [];
		return $new;
	}

	/**
	 * Iterate over all filtered nodes
	 *
	 * Executes callback for each node. Callback receives (DOM instance, index).
	 * Returns array of callback return values.
	 *
	 * Examples:
	 *   $texts = $dom->filter('p')->each(function($node, $i) {
	 *       return $node->text();
	 *   });
	 *
	 * @param callable $callback Function to call for each node
	 * @return array Array of callback return values
	 */
	public function each(callable $callback)
	{
		$results = [];
		$count 	 = $this->count();

		for ($i = 0; $i < $count; $i++) {

			$node = is_array($this->nodes) ? $this->nodes[$i] : $this->nodes->item($i);

			// Create new DOM instance for this node
			$new = clone $this;
			$new->nodes = [$node];

			$results[] = $callback($new, $i);
		}

		return $results;
	}

	// =========================================================================
	// CONTENT EXTRACTION
	// =========================================================================

	/**
	 * Get text content of first node
	 *
	 * Returns text content with normalized whitespace.
	 *
	 * Examples:
	 *   $title = $dom->filter('title')->text();
	 *   $para = $dom->filter('p')->first()->text();
	 *
	 * @return string Text content
	 */
	public function text()
	{
		if (!$this->exists()) { return ''; }

		$node = is_array($this->nodes) ? $this->nodes[0] : $this->nodes->item(0);
		$text = $node->textContent;

		return $this->cleanText($text);
	}

	/**
	 * Get inner HTML of first node
	 *
	 * Returns HTML content inside the node.
	 *
	 * Examples:
	 *   $html = $dom->filter('div.content')->html();
	 *
	 * @return string Inner HTML
	 */
	public function html()
	{
		if (!$this->exists()) { return ''; }

		$node = is_array($this->nodes) ? $this->nodes[0] : $this->nodes->item(0);
		$html = '';

		foreach ($node->childNodes as $child) {
			$html .= $this->document->saveHTML($child);
		}

		return $html;
	}

	/**
	 * Get outer HTML of first node
	 *
	 * Returns HTML including the node itself.
	 *
	 * Examples:
	 *   $html = $dom->filter('div')->outerHtml();
	 *
	 * @return string Outer HTML
	 */
	public function outerHtml()
	{
		if (!$this->exists()) { return ''; }

		$node = is_array($this->nodes) ? $this->nodes[0] : $this->nodes->item(0);
		return $this->document->saveHTML($node);
	}

	/**
	 * Get attribute value of first node
	 *
	 * Returns attribute value or empty string if not found.
	 *
	 * Examples:
	 *   $href = $dom->filter('a')->attr('href');
	 *   $alt = $dom->filter('img')->attr('alt');
	 *
	 * @param string $name Attribute name
	 * @return string Attribute value
	 */
	public function attr($name)
	{
		if (!$this->exists()) { return ''; }

		$node = is_array($this->nodes) ? $this->nodes[0] : $this->nodes->item(0);

		if ($node instanceof \DOMElement && $node->hasAttribute($name)) {
			return $node->getAttribute($name);
		}

		return '';
	}

	/**
	 * Run multiple queries at the same time.
	 * 
	 * Takes a combined list of xpath queries and execute them at the same time in onesingle DOM 
	 * traversal. Huge saves on speed if you needed, then you iterate over results in memory.
	 */
	public function queries(string $query) 
	{
		return $this->xpath->query($query);
	}

	// =========================================================================
	// METADATA EXTRACTION
	// =========================================================================

	/**
	 * Get page title
	 *
	 * Returns content of <title> tag.
	 *
	 * Examples:
	 *   $title = $dom->title();
	 *
	 * @return string Page title
	 */
	public function title()
	{
		return $this->filter('title')->text();
	}

	/**
	 * Get meta tag content
	 *
	 * Supports both name and property attributes.
	 * Works with: <meta name="description"> and <meta property="og:title">
	 *
	 * Examples:
	 *   $desc = $dom->meta('description');
	 *   $ogTitle = $dom->meta('og:title');
	 *
	 * @param string $name Meta tag name or property
	 * @return string Meta content
	 */
	public function meta($name)
	{
		// Try name attribute first
		$content = $this->filter("meta[name=\"{$name}\"]")->attr('content');

		// If not found, try property attribute (for Open Graph)
		if (empty($content)) {
			$content = $this->filter("meta[property=\"{$name}\"]")->attr('content');
		}

		return $content;
	}

	/**
	 * Get meta description
	 *
	 * Shortcut for meta('description').
	 *
	 * Examples:
	 *   $desc = $dom->description();
	 *
	 * @return string Meta description
	 */
	public function description()
	{
		return $this->meta('description');
	}

	/**
	 * Get meta keywords
	 *
	 * Shortcut for meta('keywords').
	 *
	 * Examples:
	 *   $keywords = $dom->keywords();
	 *
	 * @return string Meta keywords
	 */
	public function keywords()
	{
		return $this->meta('keywords');
	}

	/**
	 * Get author
	 *
	 * Tries multiple sources: meta tag, Schema.org, common selectors.
	 *
	 * Examples:
	 *   $author = $dom->author();
	 *
	 * @return string Author name
	 */
	public function author()
	{
		// Try meta tag
		$author = $this->meta('author');

		// Try Open Graph
		if (empty($author)) {
			$author = $this->meta('article:author');
		}

		// Try Schema.org
		if (empty($author)) {
			$author = $this->filter('[itemprop="author"]')->text();
		}

		// Try common class names
		if (empty($author)) {
			$author = $this->filter('.author, .byline, .author-name')->first()->text();
		}

		return $author;
	}

	/**
	 * Get language
	 *
	 * Returns HTML lang attribute or content-language meta tag.
	 *
	 * Examples:
	 *   $lang = $dom->lang();  // 'en', 'sw', etc.
	 *
	 * @return string Language code
	 */
	public function lang()
	{
		// Try HTML lang attribute
		$lang = $this->filter('html')->attr('lang');

		// Try meta tag
		if (empty($lang)) {
			$lang = $this->filter('meta[http-equiv="content-language"]')->attr('content');
		}

		return $lang;
	}

	/**
	 * Get canonical URL
	 *
	 * Returns canonical link if present.
	 *
	 * Examples:
	 *   $canonical = $dom->canonical();
	 *
	 * @return string Canonical URL
	 */
	public function canonical()
	{
		return $this->filter('link[rel="canonical"]')->attr('href');
	}

	/**
	 * Get published date
	 *
	 * Tries multiple sources: Open Graph, Schema.org, time element.
	 *
	 * Examples:
	 *   $date = $dom->publishedDate();
	 *
	 * @return string Published date
	 */
	public function publishedDate()
	{
		// Try Open Graph
		$date = $this->meta('article:published_time');

		// Try Schema.org
		if (empty($date)) {
			$date = $this->filter('[itemprop="datePublished"]')->attr('content');
		}

		// Try time element
		if (empty($date)) {
			$date = $this->filter('time[datetime]')->attr('datetime');
		}

		// Try common class names
		if (empty($date)) {
			$date = $this->filter('.published, .date, .post-date')->first()->text();
		}

		return $date;
	}

	/**
	 * Get modified date
	 *
	 * Tries multiple sources: Open Graph, Schema.org, time element.
	 *
	 * Examples:
	 *   $date = $dom->modifiedDate();
	 *
	 * @return string Modified date
	 */
	public function modifiedDate()
	{
		// Try Open Graph
		$date = $this->meta('article:modified_time');

		// Try Schema.org
		if (empty($date)) {
			$date = $this->filter('[itemprop="dateModified"]')->attr('content');
		}

		// Try last-modified meta
		if (empty($date)) {
			$date = $this->meta('last-modified');
		}

		return $date;
	}

	// =========================================================================
	// CONTENT EXTRACTION
	// =========================================================================

	/**
	 * Get body text content
	 *
	 * Returns cleaned body text without scripts, styles, nav, footer.
	 * Good for indexing main content.
	 *
	 * Examples:
	 *   $content = $dom->bodyText();
	 *
	 * @return string Body text
	 */
	public function bodyText()
	{
		// Clone document to avoid modifying original
		$doc 	= clone $this->document;
		$xpath 	= new \DOMXPath($doc);
		$text 	= '';

		// Always remove scripts/styles first (they're never content)
		$alwaysRemove = $xpath->query('//head | //script | //style | //noscript | //nav | //header | //footer | //aside');
		foreach ($alwaysRemove as $node) {
			if ($node->parentNode) { $node->parentNode?->removeChild($node); }
		}

		// Prepare html before extracting. Append \n to all block level tags
		// This helps ensure <li> items are listed and not mangled into one block of text
		$nodesToUpdate = $xpath->query(' //p | //li | //h1 | //h2 | //h3 | //h4 | //h5 | //div | //section | //article | //td | //tr');
		foreach ($nodesToUpdate as $node) {
			$node->appendChild($doc->createTextNode("\n"));
		}

		// Remove skip links and screen reader elements
		$skipLinks = $xpath->query(
			'//*[contains(@class, "skip")] | ' .
			'//*[contains(@class, "sr-only")] | ' .
			'//*[contains(@class, "screen-reader")] | ' .
			'//*[contains(@class, "visually-hidden")] | ' .
			'//a[contains(translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "skip to")]'
		);
		foreach ($skipLinks as $node) { 
			$node->parentNode?->removeChild($node); 
		}

		$doc = $xpath->query('//body')->item(0);
		if (!$doc) { return ''; }

		// Extract text content 
		$text	= $doc->textContent;

		// Try to find main content container first (cleanest signal)
		// $mainContent = $xpath->query(
		// 	'//main | //article | //*[@role="main"] | ' .
		// 	'//*[@id="content"] | //*[@id="main-content"] | //*[@id="main"] | ' .
		// 	'//*[@class="content"] | //*[@class="main-content"]'
		// )->item(0);

		// if ($mainContent) {	

		// 	// Found main content - use it directly
		// 	$text 	= $mainContent->textContent;
		// } 
		// else {

		// 	$doc = $xpath->query('//body')->item(0);
		// 	if (!$doc) { return ''; }

		// 	// Extract text content 
		// 	$text	= $doc->textContent;
		// }
		// else {
			// Fallback: remove boilerplate from body

			// Remove semantic nav elements
			// $nodesToUpdate = $xpath->query('//nav | //header | //footer | //aside');
			// foreach ($nodesToUpdate as $node) {

			// 	if ($node->parentNode) {
			// 		$node->parentNode->removeChild($node);
			// 	}
			// }

			// // Remove class/id-based navigation (word-boundary matching)
			// $boilerplate = $xpath->query(
			// 	'//*[contains(concat(" ", normalize-space(@class), " "), " nav ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@class), " "), " navbar ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@class), " "), " navigation ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@class), " "), " menu ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@class), " "), " sidebar ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@class), " "), " footer ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@class), " "), " header ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@id), " "), " nav ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@id), " "), " navbar ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@id), " "), " navigation ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@id), " "), " menu ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@id), " "), " sidebar ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@id), " "), " footer ")] | ' .
			// 	'//*[contains(concat(" ", normalize-space(@id), " "), " header ")]'
			// );
			// foreach ($boilerplate as $node) {

			// 	if ($node->parentNode) {
			// 		$node->parentNode->removeChild($node);
			// 	}
			// }

			// Define structural target keywords
			// $keywords 		= ["nav", "navbar", "navigation", "menu", "sidebar", "footer", "header"];
		// 	$keywords 		= ["menu", "sidebar"];
		// 	$expressions 	= [];

		// 	// Loop to build boundary logic 
		// 	foreach($keywords as $word) {

		// 		// Translate converts '-' and '_' into spaces before checking boundaries
		// 		$expressions[] = "//*[contains(concat(' ', normalize-space(translate(@class, '-_', ' ')), ' '), ' $word ')]";
		// 		$expressions[] = "//*[contains(concat(' ', normalize-space(translate(@id , '-_', ' ')), ' '), ' $word ')]";
		// 	}

		// 	// Glue it all together in one single pass query and execute
		// 	$finalQuery 	= implode(' | ', $expressions);
		// 	$boilerplate 	= $xpath->query($finalQuery);

		// 	foreach($boilerplate as $node) {
		// 		$node->parentNode?->removeChild($node);
		// 	}		

		// 	// Clean whitespace, preserve paragraph breaks
		// 	// $text = preg_replace('/[^\S\n]+/u', ' ', $text);       // Non-newline whitespace → space
		// 	// $text = preg_replace('/\n+/u', "\n", $text);           // Multiple newlines → single
		// 	// $text = preg_replace('/ ?\n ?/u', "\n", $text);        // Trim spaces around newlines
		// 	// $text = preg_replace('/(\n ?){2,}/u', "\n\n", $text);  // 2+ newlines → paragraph break

		// 	$doc = $xpath->query('//body')->item(0);
		// 	if (!$doc) { return ''; }

		// 	// Extract text content 
		// 	$text	= $doc->textContent;
		// }

		// Check if it's already perfect 'UTF-8' before attempting to fix
		if(!mb_check_encoding($text, 'UTF-8')) {
			// then strip out any broken bytes sequences
			$text	= mb_convert_encoding($text, 'UTF-8', 'UTF-8');
		}

		// Extract visible text
        $text    = preg_replace('/^[ \t\x{00A0}\n]+/u', '', $text);
        $text    = preg_replace('/^[ \t\x{00A0}]+/mu', '', $text);
        $text    = preg_replace('/[ \t\r\x{00A0}]+/u', ' ', $text);
        $text    = preg_replace('/[\n]{3,}/u', "\n\n", $text);

		return trim($text);
	}

	/**
	 * Get all headings structured by level
	 *
	 * Returns array with h1, h2, h3, h4, h5, h6 keys.
	 *
	 * Examples:
	 *   $headings = $dom->headings();
	 *   // ['h1' => ['Main Title'], 'h2' => ['Section 1', 'Section 2'], ...]
	 *
	 * @return array Headings grouped by level
	 */
	public function headings()
	{
		$headings = [
			'h1' => [],
			'h2' => [],
			'h3' => [],
			'h4' => [],
			'h5' => [],
			'h6' => []
		];

		foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {

			$headings[$tag] = $this->filter($tag)->each(function($node) {
				return $node->text();
			});
		}

		return $headings;
	}

	/**
	 * Get all emphasized text (bold, italic, underline, mark)
	 *
	 * Returns text from <strong>, <em>, <b>, <i>, <u>, <mark> tags.
	 * Useful for keyword scoring - emphasized text has higher weight.
	 *
	 * Examples:
	 *   $emphasized = $dom->emphasized();
	 *   // ['important keyword', 'emphasized word', ...]
	 *
	 *   $emphasized = $dom->emphasized(true);
	 *   // [['text' => 'important', 'tag' => 'strong'], ...]
	 *
	 * @param bool $withTags Include tag information (default: false)
	 * @return array Array of emphasized text
	 */
	public function emphasized($withTags = false)
	{
		$emphasized = [];
		$tags 		= ['strong', 'em', 'b', 'i', 'u', 'mark'];

		foreach ($tags as $tag) {

			$this->filter($tag)->each(function($node) use (&$emphasized, $tag, $withTags) {
				
				$text = trim($node->text());

				// Skip empty text
				if (empty($text)) { return; }

				if ($withTags) {
					
					$emphasized[] = [
						'text' => $text,
						'tag' => $tag
					];
				} 
				else { $emphasized[] = $text; }
			});
		}

		return $emphasized;
	}

	// =========================================================================
	// LINKS EXTRACTION
	// =========================================================================

	/**
	 * Get all links from page
	 *
	 * Returns array of links with metadata.
	 *
	 * Options:
	 *   - type: 'all', 'internal', 'external' (default: 'all')
	 *   - absolute: Convert to absolute URLs (default: true)
	 *   - follow: null (all), true (follow only), false (nofollow only)
	 *   - with_anchors: Include #fragment links (default: false)
	 *   - tld: Filter by single TLD (e.g., '.ke', '.co.ke')
	 *   - tlds: Filter by multiple TLDs (e.g., ['.ke', '.co.ke'])
	 *
	 * Examples:
	 *   $links = $dom->links();
	 *   $external = $dom->links(['type' => 'external']);
	 *   $internal = $dom->links(['type' => 'internal', 'follow' => true]);
	 *   $keLinks = $dom->links(['tld' => '.ke']);
	 *   $kenyaLinks = $dom->links(['tlds' => ['.ke', '.co.ke']]);
	 *
	 * @param array $options Link filtering options
	 * @return array Array of links [['url' => ..., 'text' => ..., 'rel' => ...], ...]
	 */
	// public function links($options = [])
	// {
	// 	// Default options
	// 	$options = array_merge([
	// 		'type' 		=> 'all',        // 'all', 'internal', 'external'
	// 		'absolute' 	=> true,         // Convert to absolute URLs
	// 		'follow' 	=> null,         // null (all), true (follow), false (nofollow)
	// 		'with_anchors' => false,     // Include #fragment links
	// 		'tld' 		=> null,         // Filter by single TLD (e.g., '.ke')
	// 		'tlds' 		=> null          // Filter by multiple TLDs (e.g., ['.ke', '.co.ke'])
	// 	], $options);

	// 	$links = [];

	// 	$this->filter('a[href]')->each(function($node) use (&$links, $options) {

	// 		$href 	= $node->attr('href');
	// 		$text 	= $node->text();
	// 		$rel 	= $node->attr('rel');

	// 		// Skip empty hrefs
	// 		if (empty($href)) { return;	}

	// 		// Skip javascript:, mailto:, tel: links
	// 		if (preg_match('/^(javascript|mailto|tel|whatsapp):/i', $href)) {
	// 			return;
	// 		}

	// 		// Skip anchor links if not requested
	// 		if (!$options['with_anchors'] && strpos($href, '#') === 0) {
	// 			return;
	// 		}

	// 		// Convert to absolute URL if requested
	// 		if ($options['absolute']) {
	// 			$href = $this->absUrl($href);
	// 		}

	// 		// Filter by type (internal/external)
	// 		if ($options['type'] !== 'all') {

	// 			$isExternal = $this->isExternal($href);

	// 			if ($options['type'] === 'internal' && $isExternal) {
	// 				return;
	// 			}

	// 			if ($options['type'] === 'external' && !$isExternal) {
	// 				return;
	// 			}
	// 		}

	// 		// Filter by TLD (single)
	// 		if ($options['tld'] !== null) {

	// 			if (!$this->isTld($href, $options['tld'])) {
	// 				return;
	// 			}
	// 		}

	// 		// Filter by TLDs (multiple)
	// 		if ($options['tlds'] !== null && is_array($options['tlds'])) {

	// 			$matchesTld = false;
	// 			foreach ($options['tlds'] as $tld) {

	// 				if ($this->isTld($href, $tld)) {
	// 					$matchesTld = true;
	// 					break;
	// 				}
	// 			}
	// 			if (!$matchesTld) { return; }
	// 		}

	// 		// Filter by follow/nofollow
	// 		if ($options['follow'] !== null) {

	// 			$isNofollow = stripos($rel, 'nofollow') !== false;

	// 			if ($options['follow'] === true && $isNofollow) { return; }

	// 			if ($options['follow'] === false && !$isNofollow) { return;	}
	// 		}

	// 		$links[] = [
	// 			'url' => $href,
	// 			'text' => $text,
	// 			'rel' => $rel
	// 		];
	// 	});

	// 	return $links;
	// }

	// =========================================================================
	// IMAGES EXTRACTION
	// =========================================================================

	/**
	 * Get all images from page
	 *
	 * Returns array of images with src, alt, title.
	 *
	 * Examples:
	 *   $images = $dom->images();
	 *   $images = $dom->images(true);  // With absolute URLs
	 *
	 * @param bool $absolute Convert src to absolute URLs (default: true)
	 * @return array Array of images [['src' => ..., 'alt' => ..., 'title' => ...], ...]
	 */
	// public function images($absolute = true)
	// {
	// 	$images = [];

	// 	$this->filter('img')->each(function($node) use (&$images, $absolute) {

	// 		$src 	= $node->attr('src');
	// 		$alt 	= $node->attr('alt');
	// 		$title 	= $node->attr('title');
	// 		$width 	= $node->attr('width');
	// 		$height = $node->attr('height');

	// 		// Skip empty src
	// 		if (empty($src)) { return; }

	// 		// Convert to absolute URL if requested
	// 		if ($absolute) { $src = $this->absUrl($src); }

	// 		$images[] = [
	// 			'src' 	=> $src,
	// 			'alt' 	=> $alt,
	// 			'title' => $title,
	// 			'width' => $width ?: null,
	// 			'height'=> $height ?: null
	// 		];
	// 	});

	// 	return $images;
	// }

	/**
	 * Get detailed image data with comprehensive metadata
	 *
	 * Returns images with full context: position, caption, placement,
	 * surrounding text, link information, etc.
	 *
	 * Similar to linkDetails() - use this for search indexing, use images()
	 * for simple extraction.
	 *
	 * Options:
	 *   - context_words: Number of words to extract around image (default: 20)
	 *   - min_size: Skip images smaller than this (width/height, default: 0)
	 *
	 * Examples:
	 *   $images = $dom->imageDetails();
	 *   $images = $dom->imageDetails(['context_words' => 30, 'min_size' => 100]);
	 *
	 * @param array $options Configuration options
	 * @return array Array of images with full metadata
	 */
	public function images($options = [])
	{
		// Default options
		$defaults = [
			'context_words' => null,
			'min_size' 		=> 0,
			'max_images' 	=> null,
		];
		$options = array_merge($defaults, $options);

		$images 	= [];

		$maxImages 	= $options['max_images'] ? "[position() <= " . (int) $options['max_images'] . "]" : "";
		$imgNodes 	= $this->xpath->query("(//body//img[@src])$maxImages");
		$position 	= 1;

		foreach ($imgNodes as $img) {

			$src 	= trim($img->getAttribute('src'));
			$alt 	= trim($img->getAttribute('alt'));
			$title 	= trim($img->getAttribute('title'));
			$width 	= $img->getAttribute('width') ?: null;
			$height = $img->getAttribute('height') ?: null;

			// Skip empty src
			if (empty($src)) { continue; }

			// Skip tiny images (icons, spacers)
			if ($options['min_size'] > 0) {

				if (($width && $width < $options['min_size']) ||
				    ($height && $height < $options['min_size'])) {
					continue;
				}
			}

			// Convert to absolute URL
			$src = $this->absUrl($src);

			// Check if image is wrapped in <a> tag
			$parent 	= $img->parentNode;
			$isLink 	= false;
			$linkUrl 	= null;

			if ($parent && strtolower($parent->nodeName) === 'a') {

				$isLink 	= true;
				$linkUrl 	= $parent->getAttribute('href');
				if ($linkUrl) {
					$linkUrl = $this->absUrl($linkUrl);
				}
			}

			// Check if image is in <figure>, extract <figcaption>
			$caption 		= null;
			$figureParent 	= $img->parentNode;

			if ($figureParent && strtolower($figureParent->nodeName) === 'a') {
				// If wrapped in <a>, check grandparent for <figure>
				$figureParent = $figureParent->parentNode;
			}
			if ($figureParent && strtolower($figureParent->nodeName) === 'figure') {
				// Find figcaption sibling
				$figcaptionNodes = $this->xpath->query(".//figcaption", $figureParent);
				if ($figcaptionNodes->length > 0) {
					$caption = trim($figcaptionNodes->item(0)->textContent);
				}
			}

			// Detect placement (content, header, sidebar, footer, navigation)
			$placement = $this->detectPlacement($img);

			// Extract surrounding text context
			$contextWords = null;
			// if ($placement === 'content' && $options['context_words']) {
			if ($options['context_words']) {
				$contextWords = $this->extractSurroundingContext($img, (int) $options['context_words']);
			}

			$images[] = [
				'src' 		=> $src,
				'alt' 		=> $alt ?: null,
				'title' 	=> $title ?: null,
				'width' 	=> $width,
				'height' 	=> $height,
				'position' 	=> $position,
				'context_text' => $contextWords,
				'caption' 	=> $caption,
				'is_link' 	=> $isLink,
				'link_url' 	=> $linkUrl,
				'placement' => $placement,
			];

			$position++;
		}

		return $images;
	}

	/**
	 * Check if page has mobile viewport tag
	 *
	 * Returns true if page has <meta name="viewport"> tag,
	 * which is the primary signal for mobile-friendliness.
	 *
	 * Examples:
	 *   $isMobile = $dom->hasViewport();  // true/false
	 *
	 * @return bool True if has viewport meta tag
	 */
	public function hasViewport()
	{
		$viewportTags = $this->xpath->query("//meta[@name='viewport']");
		return $viewportTags->length > 0;
	}

	/**
	 * Get favicon URL from page
	 *
	 * Checks multiple sources in order of preference:
	 *   1. <link rel="icon">
	 *   2. <link rel="shortcut icon">
	 *   3. <link rel="apple-touch-icon">
	 *   4. Default /favicon.ico
	 *
	 * Returns absolute URL or null if no favicon found.
	 *
	 * Examples:
	 *   $favicon = $dom->favicon();
	 *
	 * @return string|null Favicon URL (absolute) or null
	 */
	public function favicon()
	{
		// Check <link rel="icon">
		$iconNodes = $this->xpath->query("//link[@rel='icon'] | //link[@rel='shortcut icon'] | //link[@rel='apple-touch-icon']");
		if ($iconNodes->length > 0) {

			foreach($iconNodes as $node) {
				$href = $node->item(0)->getAttribute('href');
				if ($href) return $this->absUrl($href);
			}			
		}

		// Default fallback: /favicon.ico
		if ($this->baseUrl) {

			$parsed = parse_url($this->baseUrl);
			if (isset($parsed['scheme']) && isset($parsed['host'])) {
				return $parsed['scheme'] . '://' . $parsed['host'] . '/favicon.ico';
			}
		}
		
		// // Check <link rel="icon">
		// $iconLinks = $this->xpath->query("//link[@rel='icon']");
		// if ($iconLinks->length > 0) {
		// 	$href = $iconLinks->item(0)->getAttribute('href');
		// 	if ($href) { return $this->absUrl($href); }
		// }

		// // Check <link rel="shortcut icon">
		// $shortcutLinks = $this->xpath->query("//link[@rel='shortcut icon']");
		// if ($shortcutLinks->length > 0) {

		// 	$href = $shortcutLinks->item(0)->getAttribute('href');
		// 	if ($href) {
		// 		return $this->absUrl($href);
		// 	}
		// }

		// // Check <link rel="apple-touch-icon">
		// $appleLinks = $this->xpath->query("//link[@rel='apple-touch-icon']");
		// if ($appleLinks->length > 0) {

		// 	$href = $appleLinks->item(0)->getAttribute('href');
		// 	if ($href) { return $this->absUrl($href); }
		// }

		// // Default fallback: /favicon.ico
		// if ($this->baseUrl) {

		// 	$parsed = parse_url($this->baseUrl);
		// 	if (isset($parsed['scheme']) && isset($parsed['host'])) {
		// 		return $parsed['scheme'] . '://' . $parsed['host'] . '/favicon.ico';
		// 	}
		// }

		return null;
	}

	// =========================================================================
	// UTILITIES
	// =========================================================================

	/**
	 * Convert relative URL to absolute
	 *
	 * Uses base URL to resolve relative URLs.
	 *
	 * Examples:
	 *   $abs = $dom->absUrl('/about');
	 *   $abs = $dom->absUrl('../contact');
	 *
	 * @param string $url Relative or absolute URL
	 * @return string Absolute URL
	 */
	public function absUrl($url)
	{
		// Already absolute - check for common URL schemes
		// Web: http, https, ftp, ftps
		// Special: mailto, tel, sms, javascript, data, file
		if (preg_match('/^(https?|ftp|ftps|mailto|tel|whatsapp|sms|javascript|data|file):/i', $url)) {
			return $url;
		}

		// No base URL set
		if ($this->baseUrl === null) {
			return $url;
		}

		// Parse base URL
		$base = parse_url($this->baseUrl);

		// Protocol-relative URL (//example.com/path)
		if (strpos($url, '//') === 0) {
			return ($base['scheme'] ?? 'http') . ':' . $url;
		}

		// Absolute path (/path)
		if (strpos($url, '/') === 0) {

			$scheme = $base['scheme'] ?? 'http';
			$host 	= $base['host'] ?? '';
			$port 	= isset($base['port']) ? ':' . $base['port'] : '';

			return "{$scheme}://{$host}{$port}{$url}";
		}

		// Relative path (path or ../path)
		$basePath = $base['path'] ?? '/';
		$basePath = preg_replace('/\/[^\/]*$/', '/', $basePath);  // Remove filename

		// Handle ../ and ./
		$absolutePath = $basePath . $url;
		$parts = [];

		foreach (explode('/', $absolutePath) as $part) {

			if ($part === '..') { array_pop($parts); } 
			elseif ($part !== '.' && $part !== '') {
				$parts[] = $part;
			}
		}

		$path = '/' . implode('/', $parts);

		$scheme = $base['scheme'] ?? 'http';
		$host 	= $base['host'] ?? '';
		$port 	= isset($base['port']) ? ':' . $base['port'] : '';

		return "{$scheme}://{$host}{$port}{$path}";
	}

	/**
	 * Check if URL is external
	 *
	 * Compares URL domain with base URL domain.
	 *
	 * Examples:
	 *   if ($dom->isExternal($url)) { ... }
	 *
	 * @param string $url URL to check
	 * @return bool True if external
	 */
	public function isExternal($url)
	{
		// No base URL set - can't determine
		if ($this->baseUrl === null) {
			return false;
		}

		// Make URL absolute first
		$absoluteUrl = $this->absUrl($url);

		// Parse both URLs
		$urlHost 	= parse_url($absoluteUrl, PHP_URL_HOST);
		// $baseHost 	= parse_url($this->baseUrl, PHP_URL_HOST);

		// Different hosts = external
		return $urlHost !== $this->baseHost;
	}

	/**
	 * Check if URL has specific TLD (top-level domain)
	 *
	 * Useful for filtering .ke, .co.ke, .com, etc.
	 *
	 * Examples:
	 *   if ($dom->isTld($url, '.ke')) { ... }
	 *   if ($dom->isTld($url, '.co.ke')) { ... }
	 *
	 * @param string $url URL to check
	 * @param string $tld TLD to check for (e.g., '.ke', '.co.ke', '.com')
	 * @return bool True if URL has the TLD
	 */
	// public function isTld($url, $tld)
	// {
	// 	// Make URL absolute first
	// 	$absoluteUrl = $this->absUrl($url);

	// 	// Parse URL to get host
	// 	$host = parse_url($absoluteUrl, PHP_URL_HOST);

	// 	if (!$host) {
	// 		return false;
	// 	}

	// 	// Normalize TLD (ensure it starts with dot)
	// 	if (strpos($tld, '.') !== 0) {
	// 		$tld = '.' . $tld;
	// 	}

	// 	// Check if host ends with TLD
	// 	return substr($host, -strlen($tld)) === $tld;
	// }

	/**
	 * Remove script and style tags from current selection
	 *
	 * Modifies the document by removing script and style elements.
	 * Optionally preserves Schema.org JSON-LD scripts for structured data extraction.
	 *
	 * Examples:
	 *   $dom->removeScripts()->filter('body')->text();
	 *   $dom->removeScripts(true)->fullHtml(); // Keep JSON-LD
	 *
	 * @param bool $preserveJsonLd If true, keeps application/ld+json scripts
	 * @return self Returns self for chaining
	 */
	public function removeScripts($preserveJsonLd = false)
	{
		// Remove all <style> tags
		$styles = $this->xpath->query('//style');
		foreach ($styles as $node) {
			$node->parentNode->removeChild($node);
		}

		// Remove <script> tags (optionally preserve JSON-LD)
		if ($preserveJsonLd) {
			$scripts = $this->xpath->query('//script[not(@type="application/ld+json")]');
		} 
		else { $scripts = $this->xpath->query('//script'); }

		foreach ($scripts as $node) {
			$node->parentNode->removeChild($node);
		}

		return $this;
	}

	/**
	 * Export full document HTML
	 *
	 * Returns the complete HTML document as a string, including
	 * DOCTYPE, html, head, and body tags.
	 *
	 * Useful after removeScripts() to get cleaned HTML for spam checking.
	 *
	 * Examples:
	 *   $cleanHtml = $dom->removeScripts()->fullHtml();
	 *
	 * @return string Full HTML document
	 */
	public function fullHtml()
	{
		return $this->document->saveHTML();
	}

	/**
	 * Clean and normalize text
	 *
	 * Removes extra whitespace, normalizes line breaks.
	 *
	 * Examples:
	 *   $clean = $dom->cleanText($rawText);
	 *
	 * @param string $text Text to clean
	 * @return string Cleaned text
	 */
	public function cleanText($text)
	{
		// Replace multiple whitespace with single space
		$text = preg_replace('/\s+/u', ' ', $text);

		// Trim whitespace
		$text = trim($text);

		return $text;
	}

	// =========================================================================
	// INTERNAL HELPERS
	// =========================================================================

	/**
	 * Convert CSS selector to XPath expression
	 *
	 * Simple CSS to XPath converter for common selectors.
	 *
	 * @param string $selector CSS selector
	 * @return string XPath expression
	 */
	private function cssToXpath($selector)
	{
		// Remove extra spaces
		$selector = trim($selector);

		// Handle multiple selectors (comma-separated)
		if (strpos($selector, ',') !== false) {

			$selectors 	= explode(',', $selector);
			$xpaths 	= array_map([$this, 'cssToXpath'], array_map('trim', $selectors));

			return implode(' | ', $xpaths);
		}

		$xpath = '//';

		// Universal selector
		if ($selector === '*') {
			return '//*';
		}

		// Split by spaces (descendant combinator)
		$parts 		= preg_split('/\s+/', $selector);
		$xpathParts = [];

		foreach ($parts as $part) {

			$xpathPart = $this->cssPartToXpath($part);
			$xpathParts[] = $xpathPart;
		}

		$xpath .= implode('//', $xpathParts);

		return $xpath;
	}

	/**
	 * Convert single CSS selector part to XPath
	 *
	 * @param string $part CSS selector part
	 * @return string XPath expression
	 */
	private function cssPartToXpath($part)
	{
		// Element with ID: div#main
		if (preg_match('/^(\w+)?#([\w-]+)$/', $part, $matches)) {

			$element = $matches[1] ?: '*';
			$id 	 = $matches[2];

			return "{$element}[@id='{$id}']";
		}

		// Element with class: div.content
		if (preg_match('/^(\w+)?\.([\w-]+)$/', $part, $matches)) {

			$element 	= $matches[1] ?: '*';
			$class 		= $matches[2];

			return "{$element}[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]";
		}

		// ID only: #main
		if (preg_match('/^#([\w-]+)$/', $part, $matches)) {

			$id = $matches[1];
			return "*[@id='{$id}']";
		}

		// Class only: .content
		if (preg_match('/^\.([\w-]+)$/', $part, $matches)) {

			$class = $matches[1];
			return "*[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]";
		}

		// Attribute selector: [name="value"]
		if (preg_match('/^(\w+)?\[(\w+)(?:="([^"]+)")?\]$/', $part, $matches)) {

			$element 	= $matches[1] ?: '*';
			$attr 		= $matches[2];
			$value 		= $matches[3] ?? null;

			if ($value !== null) {
				return "{$element}[@{$attr}='{$value}']";
			} 
			else {
				return "{$element}[@{$attr}]";
			}
		}

		// Child combinator: >
		if ($part === '>') {
			return '';  // Handle separately
		}

		// Plain element: div, p, span
		return $part;
	}

	// =========================================================================
	// SEARCH ENGINE METADATA EXTRACTION
	// =========================================================================

	/**
	 * Extract meta robots directives
	 *
	 * Returns robots meta tag content (noindex, nofollow, etc.)
	 *
	 * @return string|null Robots directives or null
	 */
	public function metaRobots()
	{
		$robots = $this->xpath->query("//meta[@name='robots']")->item(0);

		if ($robots) {
			return strtolower($robots->getAttribute('content'));
		}

		return null;
	}

	/**
	 * Extract Open Graph metadata
	 *
	 * Returns array of Open Graph tags (og:title, og:description, etc.)
	 *
	 * @return array Open Graph data
	 */
	public function openGraph()
	{
		$ogTags = $this->xpath->query("//meta[starts-with(@property, 'og:')]");

		$og = [];
		foreach ($ogTags as $tag) {

			$property 	= $tag->getAttribute('property');
			$content 	= $tag->getAttribute('content');

			// Remove 'og:' prefix for cleaner keys
			$key = str_replace('og:', '', $property);
			$og[$key] = $content;
		}

		return $og;
	}

	/**
	 * Extract JSON-LD structured data
	 *
	 * Returns array of Schema.org structured data from JSON-LD scripts.
	 *
	 * @return array Structured data objects
	 */
	public function schemaData()
	{
		$scripts = $this->xpath->query("//script[@type='application/ld+json']");

		$schemas = [];
		foreach ($scripts as $script) {

			$json = trim($script->textContent);
			if (!empty($json)) {

				$data = json_decode($json, true);
				if ($data !== null) { $schemas[] = $data; }
			}
		}

		return $schemas;
	}

	/**
	 * Extract content statistics
	 *
	 * Returns word count, sentence count, paragraph count for quality scoring.
	 *
	 * @return array Content statistics
	 */
	public function contentStats()
	{
		$bodyText = $this->bodyText();

		// Word count
		$wordCount = str_word_count($bodyText);

		// Sentence count (rough approximation)
		$sentences = preg_split('/[.!?]+/', $bodyText, -1, PREG_SPLIT_NO_EMPTY);
		$sentenceCount = count($sentences);

		// Paragraph count (double newlines)
		$paragraphs = preg_split('/\n\n+/', $bodyText, -1, PREG_SPLIT_NO_EMPTY);
		$paragraphCount = count($paragraphs);

		// Average sentence length
		$avgSentenceLength = $sentenceCount > 0 ? round($wordCount / $sentenceCount, 1) : 0;

		// Unique word ratio (for duplicate content detection)
		$words 		 = str_word_count(strtolower($bodyText), 1);
		$uniqueWords = count(array_unique($words));
		$uniqueRatio = $wordCount > 0 ? round($uniqueWords / $wordCount, 3) : 0;

		return [
			'word_count' => $wordCount,
			'sentence_count' => $sentenceCount,
			'paragraph_count' => $paragraphCount,
			'avg_sentence_length' => $avgSentenceLength,
			'unique_word_count' => $uniqueWords,
			'unique_word_ratio' => $uniqueRatio,
		];
	}

	/**
	 * Extract detailed link information with context
	 *
	 * Returns all links categorized by type (crawlable, document, media, etc.)
	 * with surrounding context for relevance scoring.
	 *
	 * Options:
	 *   - tlds: Array of TLDs to filter (default: ['.ke'])
	 *   - context_words: Number of words before/after link (default: 25)
	 *   - include_navigation: Include navigation links (default: true)
	 *
	 * @param array $options Configuration options
	 * @return array Categorized links with context
	 */
	public function links($options = [])
	{	

		// Default options
		$options = array_merge([
			'type' 			=> 'all',        // 'all', 'internal', 'external'
			'max_links' 	=> null, 		 // Maximum number of links to return, default 1k
			'body_links' 	=> false, 		 // Whether to only pick links from <body> or whole <html>
			'absolute' 		=> true,         // Convert to absolute URLs
			'do_follow' 	=> null,         // null (all), true (follow), false (nofollow)
			'with_anchors' 	=> false,        // Include #fragment links
			'tlds' 			=> null,         // Filter by single or multiple TLDs (e.g., ['.ke', '.co.ke'])
			'context_words' => null, 		 // Number of surrounding words around the link
			'include_nav'   => true,
		], $options);

		$bodyLinks 	= $options['body_links'] ? "//body" : "";
		$maxLinks	= $options['max_links']  ? "[position() <=" . (int)$options['max_links'] . "]" :  "";

		// Query string
		$linkNodes 	= $this->xpath->query("($bodyLinks//a[@href])$maxLinks");

		$links = [
			'crawlable' => [],
			'document' 	=> [],
			'media' 	=> [],
			'resource' 	=> [],
			'anchor' 	=> [],
		];

		$position 	= 0;

		foreach ($linkNodes as $link) {

			$href = trim($link->getAttribute('href'));

			// Skip empty hrefs
			if (empty($href)) continue;

			// Skip javascript:, mailto:, tel: links
			if (preg_match('/^(javascript|mailto|tel):/i', $href)) {
				continue;
			} 

			// Resolve to absolute URL
			if($options['absolute']) $href = $this->absUrl($href);

			// Strip fragment identifier (#section) - same page, different scroll position
			// URLs like /page#section1 and /page#section2 are the same HTML document
			if(!$options['with_anchors']) $href = preg_replace('/#.*$/', '', $href);

			if($options['tlds']) {
				// if (!$this->matchesTlds($href, $options['tlds']) && $linkData['link_type'] === 'crawlable') {
				if (!$this->matchesTlds($href, $options['tlds'])) {
					
					$position++;
					continue;
				}
			}	
			
			// Extract link data
			$linkData = $this->analyzeLinkNode($link, $href, $position, $options);

			// Categorize link
			$category = $linkData['link_type'];

			if (isset($links[$category])) {
				$links[$category][] = $linkData;
			}

			$position++;
		}

		return $links;
	}

	/**
	 * Analyze a single link node
	 *
	 * @param \DOMElement $link Link element
	 * @param string $absoluteUrl Resolved absolute URL
	 * @param int $position Position in page
	 * @param array $options Options
	 * @return array Link data
	 */
	private function analyzeLinkNode($link, $absoluteUrl, $position, $options)
	{
		// Basic link data
		$anchorText = trim($link->textContent);
		$anchorType = 'text';

		// Payload to return; populate it along the way
		$details 	= [];

		// If no anchor text, check for image and use alt text
		if (empty($anchorText)) {

			$imgNodes = $this->xpath->query('.//img[@src]', $link);

			if ($imgNodes->length > 0) {

				$img 	 = $imgNodes->item(0);
				$altText = trim($img->getAttribute('alt'));

				if (!empty($altText)) {

					$anchorText = $altText;
					$anchorType = 'image';
				} 
				else {

					$anchorText = '[image]';
					$anchorType = 'image';
				}
			} 
			else {

				// No text, no image - mark as empty
				$anchorText = '[empty]';
			}
		}

		$rel 			= $link->getAttribute('rel') ?: '';
		$downloadAttr 	= $link->hasAttribute('download');
		$typeAttr 		= $link->getAttribute('type') ?: '';

		// Classify link type
		$classification = $this->classifyLink($absoluteUrl, $typeAttr, $downloadAttr);

		// Detect context type (navigation, content, metadata, footer)
		$placement 	= $this->detectPlacement($link);

		// Extract surrounding context (for content links)
		$contextWords = null;

		if ($options['context_words']) {
			$contextWords = $this->extractSurroundingContext($link, (int) $options['context_words']);
		}

		// Get parent tag
		$parentTag 	= $link->parentNode ? $link->parentNode->nodeName : null;

		// Detect semantic role
		$semanticRole 	= $this->detectSemanticRole($link);

		// Check if internal
		$isInternal 	= $this->isInternalLink($absoluteUrl);		

		return [
			'url' 		  => $absoluteUrl,
			'anchor_text' => $anchorText,
			'anchor_type' => $anchorType,
			'position' 	  => $position,

			// Classification
			'link_type' 	=> $classification['type'],
			'document_type' => $classification['document_type'],
			'rel' 			=> $rel,

			// Context
			'placement' 		=> $placement,
			'parent_tag' 		=> $parentTag,
			'context_words' 	=> $contextWords,
			'semantic_role' 	=> $semanticRole,

			// Technical
			'is_internal' 		=> $isInternal,
			'has_download_attr' => $downloadAttr,
		];
	}

	/**
	 * Classify link by URL and attributes
	 *
	 * @param string $url URL to classify
	 * @param string $typeAttr Type attribute value
	 * @param bool $downloadAttr Has download attribute
	 * @return array Classification result
	 */
	private function classifyLink($url, $typeAttr, $downloadAttr)
	{
		// Same-page anchor
		if (strpos($url, '#') === 0) {
			return ['type' => 'anchor', 'document_type' => null];
		}

		// External resources (CSS, JS, fonts)
		$resourceExtensions = ['css', 'js', 'woff', 'woff2', 'ttf', 'eot'];
		$path = parse_url($url, PHP_URL_PATH);
		$ext = $path ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';

		if (in_array($ext, $resourceExtensions)) {
			return ['type' => 'resource', 'document_type' => $ext];
		}

		// Documents
		$documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'odt', 'ods', 'odp'];
		if (in_array($ext, $documentExtensions) || $downloadAttr) {
			return ['type' => 'document', 'document_type' => $ext ?: 'unknown'];
		}

		// Media files
		$mediaExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'ico', 'mp3', 'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
		if (in_array($ext, $mediaExtensions)) {
			return ['type' => 'media', 'document_type' => $ext];
		}

		// Check MIME type from type attribute
		if (strpos($typeAttr, 'application/pdf') !== false) {
			return ['type' => 'document', 'document_type' => 'pdf'];
		}

		// Default: crawlable HTML page
		return ['type' => 'crawlable', 'document_type' => null];
	}

	/**
	 * Detect link placement (nav, content, metadata, footer, sidebar)
	 *
	 * Values match LinkModel enum: nav, content, footer, sidebar, metadata
	 *
	 * @param \DOMElement $link Link element
	 * @return string Placement type
	 */
	private function detectPlacement($link)
	{
		// Walk up the DOM tree looking for semantic containers
		$current 	= $link->parentNode;
		$depth 		= 0;
		$maxDepth 	= 5; // Don't go too far up

		while ($current && $depth < $maxDepth) {

			$tagName = strtolower($current->nodeName);

			// Semantic HTML5 tags
			if ($tagName === 'nav') 	return 'nav';
			if ($tagName === 'header') 	return 'nav';
			if ($tagName === 'footer') 	return 'footer';
			if ($tagName === 'aside') 	return 'sidebar';

			// Content tags
			if ($tagName === 'article') return 'content';
			if ($tagName === 'main') 	return 'content';
			if (in_array($tagName, ['p', 'li', 'td', 'blockquote'])) return 'content';

			// Check class/id for common patterns
			if ($current instanceof \DOMElement) {
				$class = $current->getAttribute('class');
				$id = $current->getAttribute('id');

				// Navigation indicators
				if (preg_match('/\b(nav|navbar|navigation|menu|topbar|breadcrumb|pagination)\b/i', $class . ' ' . $id)) {
					return 'nav';
				}

				// Sidebar indicators
				if (preg_match('/\b(sidebar|widget|aside)\b/i', $class . ' ' . $id)) {
					return 'sidebar';
				}

				// Footer indicators
				if (preg_match('/\bfooter\b/i', $class . ' ' . $id)) {
					return 'footer';
				}

				// Author/metadata indicators
				if (preg_match('/\b(author|byline|meta|social|share)\b/i', $class . ' ' . $id)) {
					return 'metadata';
				}
			}

			$current = $current->parentNode;
			$depth++;
		}

		// Default to content
		return 'content';
	}

	/**
	 * Extract surrounding text context (25 words before and after link)
	 *
	 * @param \DOMElement $link Link element
	 * @param int $contextWords Number of words before/after
	 * @return string|null Surrounding context
	 */
	private function extractSurroundingContext($link, $contextWords)
	{
		// Get parent element that contains text
		$parent = $link->parentNode;
		if (!$parent) {
			return null;
		}

		// Get all text from parent
		$parentText = trim($parent->textContent);
		if (empty($parentText)) {
			return null;
		}

		// Get link text to find position
		$linkText = trim($link->textContent);
		if (empty($linkText)) {
			return null;
		}

		// Find link position in parent text
		$linkPos = mb_strpos($parentText, $linkText);
		if ($linkPos === false) {
			return $parentText; // Fallback to full parent text
		}

		// Split into words
		$words 		= preg_split('/\s+/u', $parentText);
		$linkWords  = preg_split('/\s+/u', $linkText);

		// Find word position of link
		$beforeText 	 = mb_substr($parentText, 0, $linkPos);
		$beforeWords 	 = preg_split('/\s+/u', $beforeText, -1, PREG_SPLIT_NO_EMPTY);
		$linkStartWordPos= count($beforeWords);

		// Extract context window
		$startPos 	= max(0, $linkStartWordPos - $contextWords);
		$endPos 	= min(count($words), $linkStartWordPos + count($linkWords) + $contextWords);

		$contextWords = array_slice($words, $startPos, $endPos - $startPos);

		return implode(' ', $contextWords);
	}

	/**
	 * Detect semantic role of link (author, category, tag, etc.)
	 *
	 * @param \DOMElement $link Link element
	 * @return string|null Semantic role
	 */
	private function detectSemanticRole($link)
	{
		// Check rel attribute
		$rel = $link->getAttribute('rel');

		if ($rel === 'author') 	 return 'author';
		if ($rel === 'tag') 	 return 'tag';
		if ($rel === 'category') return 'category';

		// Check link class/id
		$class 	 	= $link->getAttribute('class');
		$id 		= $link->getAttribute('id');
		$combined 	= strtolower($class . ' ' . $id);

		if (preg_match('/\b(author|byline)\b/', $combined)) return 'author';
		if (preg_match('/\b(category|cat)\b/', $combined)) return 'category';
		if (preg_match('/\btag\b/', $combined)) return 'tag';
		if (preg_match('/\b(related|similar)\b/', $combined)) return 'related';

		// Check parent context
		$parent = $link->parentNode;
		if ($parent instanceof \DOMElement) {

			$parentClass = strtolower($parent->getAttribute('class'));

			if (preg_match('/\b(author|byline)\b/', $parentClass)) return 'author';
			if (preg_match('/\b(category|categories)\b/', $parentClass)) return 'category';
			if (preg_match('/\b(tag|tags)\b/', $parentClass)) return 'tag';
		}

		return null;
	}

	/**
	 * Check if link is internal (same domain)
	 *
	 * @param string $url URL to check
	 * @return bool True if internal
	 */
	private function isInternalLink($url)
	{
		$urlHost 	= parse_url($url, PHP_URL_HOST);
		$baseHost 	= parse_url($this->baseUrl, PHP_URL_HOST);

		// Remove www. prefix for comparison
		if ($urlHost) $urlHost = preg_replace('/^www\./i', '', $urlHost);
		$baseHost = preg_replace('/^www\./i', '', $baseHost);

		return $urlHost === $baseHost;
	}

	/**
	 * Check if URL matches TLD filter
	 *
	 * @param string $url URL to check
	 * @param array $tlds Array of TLDs to match
	 * @return bool True if matches
	 */
	private function matchesTlds($url, $tlds)
	{
		$host = parse_url($url, PHP_URL_HOST);
		if (!$host) { return false; }

		// foreach ($tlds as $tld) {
		// 	if (substr($host, -strlen($tld)) === $tld) { return true; }
		// }	
		
		// Let's use the new function in town
		foreach($tlds as $tld) {			

			if(str_ends_with($host, $tld)) {
				return true;
			}					
		}		

		return false;
	}
}
