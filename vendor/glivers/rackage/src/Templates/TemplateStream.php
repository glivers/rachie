<?php namespace Rackage\Templates;

/**
 * Template Stream Wrapper
 *
 * Provides a PHP stream wrapper for serving compiled templates from memory.
 * Used in production mode to eliminate disk I/O during view rendering.
 *
 * How it works:
 *   1. Compiled template stored in static $content via setContent()
 *   2. PHP includes 'rachie-template://render'
 *   3. Stream wrapper serves content from memory (no disk access)
 *
 * Benefits:
 *   - Zero disk I/O (no write/delete per request)
 *   - Consistent performance under server load
 *   - No orphaned temp files on errors
 *   - No disk wear from repeated writes
 *
 * Usage:
 *   // Register once (in bootstrap)
 *   TemplateStream::register();
 *
 *   // Use in View class
 *   TemplateStream::setContent($compiledTemplate);
 *   include 'rachie-template://render';
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Templates
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

class TemplateStream
{
    /**
     * Stream context resource
     *
     * Automatically set by PHP when opening the stream. Required to be
     * declared explicitly for PHP 8.2+ compatibility (no dynamic properties).
     *
     * @var resource|null
     */
    public $context;

    /**
     * The compiled template content to serve
     *
     * Stores the fully compiled PHP template code that will be served
     * via the stream wrapper. Set via setContent() before including.
     *
     * @var string
     */
    private static $content = '';

    /**
     * Current read position in content
     *
     * Tracks how many bytes have been read during stream_read() calls.
     * PHP reads in ~8KB chunks until stream_eof() returns true.
     *
     * @var int
     */
    private $position = 0;

    /**
     * Register the stream wrapper with PHP
     *
     * Registers 'rachie-template://' as a valid stream protocol. Must be called
     * once per request before using include 'rachie-template://render'.
     *
     * Process:
     *   1. Check if 'rachie-template' wrapper already registered
     *   2. If not, register this class as the handler
     *   3. Return success/failure status
     *
     * @return bool True if registered, false if already registered
     */
    public static function register()
    {
        if (!in_array('rachie-template', stream_get_wrappers())) {

            return stream_wrapper_register('rachie-template', __CLASS__);
        }

        return false;
    }

    /**
     * Set the compiled template content to serve
     *
     * Stores the compiled PHP code in static memory. This must be called
     * before including the stream, as the content is served from here.
     *
     * @param string $code The compiled PHP template code
     * @return void
     */
    public static function setContent($code)
    {
        self::$content = $code;
    }

    /**
     * Clear the content after rendering
     *
     * Frees memory by clearing the stored template content.
     * Called after rendering is complete.
     *
     * @return void
     */
    public static function clearContent()
    {
        self::$content = '';
    }

    /**
     * Open the stream for reading
     *
     * Called by PHP when include/require targets 'rachie-template://render'.
     * Resets read position to start of content.
     *
     * @param string $path The stream path (e.g., 'rachie-template://render')
     * @param string $mode The open mode (e.g., 'rb')
     * @param int $options Stream options bitmask
     * @param string|null &$opened_path Set to the actual opened path
     * @return bool True on success
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->position = 0;

        return true;
    }

    /**
     * Read a chunk of content from the stream
     *
     * Called repeatedly by PHP during include. PHP typically reads in
     * ~8KB chunks until stream_eof() returns true.
     *
     * Process:
     *   1. Extract substring from current position
     *   2. Advance position by bytes read
     *   3. Return the chunk
     *
     * @param int $count Number of bytes to read
     * @return string The read data
     */
    public function stream_read($count)
    {
        $chunk = substr(self::$content, $this->position, $count);
        $this->position += strlen($chunk);
        
        return $chunk;
    }

    /**
     * Check if end of content reached
     *
     * Called by PHP after each stream_read() to determine if
     * there's more content to read.
     *
     * @return bool True if at end of content
     */
    public function stream_eof()
    {
        $atEnd = $this->position >= strlen(self::$content);

        return $atEnd;
    }

    /**
     * Return file statistics for the stream
     *
     * Required by PHP's stream wrapper interface. Returns minimal
     * stat array with content size.
     *
     * @return array Stat array with size key
     */
    public function stream_stat()
    {
        return ['size' => strlen(self::$content)];
    }

    /**
     * Return URL statistics for the stream
     *
     * Required by PHP for include/require to work with the stream.
     * Called before stream_open() to check if resource exists.
     *
     * @param string $path The stream path
     * @param int $flags Stat flags
     * @return array Stat array with size key
     */
    public function url_stat($path, $flags)
    {
        return ['size' => strlen(self::$content)];
    }

    /**
     * Set stream options
     *
     * Required by PHP 7.4+ for stream wrapper compatibility.
     * We don't need special option handling, so always return true.
     *
     * @param int $option The option to set
     * @param int $arg1 First argument
     * @param int $arg2 Second argument
     * @return bool Always returns true
     */
    public function stream_set_option($option, $arg1, $arg2)
    {
        return true;
    }
}
