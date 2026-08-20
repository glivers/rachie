<?php namespace Roline;

/**
 * Output - CLI Output Helper
 *
 * Static utility class for formatted terminal output with ANSI color support
 * and user interaction. Provides consistent styling across all Roline commands
 * with success/error/info messages and input prompts.
 *
 * Output Types:
 *   - success() - Green text with ✓ checkmark (successful operations)
 *   - error()   - Red text with ✗ mark (failures, warnings)
 *   - info()    - Yellow text with → arrow (informational messages)
 *   - line()    - Plain text without colors (general output)
 *
 * User Interaction:
 *   - ask()     - Prompt for text input (cyan colored question)
 *   - confirm() - Yes/no confirmation prompt (returns boolean)
 *
 * Usage Examples:
 *
 *   Output::success('Model created successfully!');
 *   Output::error('File not found');
 *   Output::info('Running migrations...');
 *   Output::line('Plain text output');
 *
 *   $name = Output::ask('Enter model name');
 *   if (Output::confirm('Delete this file?')) { ... }
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

class Output
{
    /**
     * ANSI color codes for terminal output
     *
     * Standard ANSI escape sequences that work on most modern terminals.
     * RESET should be applied after colored text to return to default colors.
     */
    const GREEN  = "\033[32m";  // Success messages, confirmations
    const RED    = "\033[31m";  // Errors, warnings, destructive actions
    const YELLOW = "\033[33m";  // Info messages, highlights
    const BLUE   = "\033[34m";  // Currently unused (reserved)
    const CYAN   = "\033[36m";  // User input prompts
    const GRAY   = "\033[90m";  // Dimmed text, optional arguments
    const RESET  = "\033[0m";   // Reset to default terminal colors

    /**
     * Display success message with green checkmark
     *
     * Use for successful operations like file creation, database updates,
     * or command completion.
     *
     * @param string $msg Success message to display
     * @return void
     */
    public static function success($msg)
    {
        echo self::GREEN . "✓ {$msg}" . self::RESET . "\n";
    }

    /**
     * Display error message with red X mark
     *
     * Use for failures, validation errors, or warnings. Does not exit -
     * caller must handle exit codes.
     *
     * @param string $msg Error message to display
     * @return void
     */
    public static function error($msg)
    {
        echo self::RED . "✗ {$msg}" . self::RESET . "\n";
    }

    /**
     * Display info message with yellow arrow
     *
     * Use for informational messages, progress updates, or instructions.
     *
     * @param string $msg Info message to display
     * @return void
     */
    public static function info($msg)
    {
        echo self::YELLOW . "→ {$msg}" . self::RESET . "\n";
    }

    /**
     * Display plain text line without formatting
     *
     * Use for general output, descriptions, or when colors aren't needed.
     * Pass empty string for blank line spacing.
     *
     * @param string $msg Message to display (empty string for blank line)
     * @return void
     */
    public static function line($msg = '')
    {
        echo "{$msg}\n";
    }

    /**
     * Prompt user for text input
     *
     * Displays a cyan-colored question and waits for user to type a response.
     * Returns the trimmed input (leading/trailing whitespace removed).
     *
     * @param string $question Question to ask (colon added automatically)
     * @return string User's trimmed response
     */
    public static function ask($question)
    {
        echo self::CYAN . "{$question}: " . self::RESET;
        return trim(fgets(STDIN));
    }

    /**
     * Prompt user for yes/no confirmation
     *
     * Displays a question with "(yes/no)" suffix and validates response.
     * Accepts 'yes' or 'y' (case-insensitive) as confirmation.
     * Any other response is treated as rejection.
     *
     * @param string $question Confirmation question
     * @return bool True if user confirmed (yes/y), false otherwise
     */
    public static function confirm($question)
    {
        $response = self::ask("{$question} (yes/no)");
        return in_array(strtolower($response), ['yes', 'y']);
    }

    /**
     * Apply color coding to usage argument syntax
     *
     * Colorizes argument type flags in usage strings to help users distinguish
     * between required and optional arguments. Processes special syntax like
     * <arg|required> and <arg|optional>, applying appropriate colors.
     *
     * Color Scheme:
     *   - |required flag: YELLOW (highlighting importance)
     *   - |optional flag: GRAY (dimmed, less important)
     *
     * @param string $usage Usage string with argument flags
     *                      Example: 'model:create <Model|required> [table|optional]'
     * @return string Colorized usage string with ANSI codes applied
     */
    public static function colorizeUsage($usage)
    {
        // Match patterns like <arg|required> or <arg|optional>
        $pattern = '/<([^|>]+)\|(required|optional)>/';

        $colorized = preg_replace_callback($pattern, function($matches) {
            $arg = $matches[1];   // Argument name
            $flag = $matches[2];  // 'required' or 'optional'

            // Apply appropriate color based on flag type
            $flagColor = ($flag === 'required') ? self::YELLOW : self::GRAY;

            return '<' . $arg . $flagColor . '|' . $flag . self::RESET . '>';
        }, $usage);

        return $colorized;
    }
}
