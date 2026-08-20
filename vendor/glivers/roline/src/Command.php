<?php namespace Roline;

/**
 * Command - Base Command Class
 *
 * Abstract base class that all Roline CLI commands must extend. Provides the
 * template structure for command implementation using the Template Method pattern,
 * along with helper methods for user interaction and output formatting.
 *
 * Subclasses must implement three abstract methods:
 *   - execute($arguments)  - The command's main logic
 *   - description()        - Short description for command listing
 *   - usage()              - Argument syntax (e.g., '<Model>' or '<name> [--option]')
 *
 * Creating New Commands:
 *
 *   class MyCommand extends Command {
 *       public function description() {
 *           return 'Does something useful';
 *       }
 *
 *       public function usage() {
 *           return '<arg1> [arg2]';
 *       }
 *
 *       public function execute($arguments) {
 *           $this->success('Command executed!');
 *       }
 *   }
 *
 * Available Helper Methods:
 *   - success($msg)   - Green output with checkmark
 *   - error($msg)     - Red output with X mark
 *   - info($msg)      - Yellow output with arrow
 *   - line($msg)      - Plain text output
 *   - ask($question)  - Prompt for text input
 *   - confirm($q)     - Prompt for yes/no confirmation
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

abstract class Command
{
    /**
     * Full command name as registered in Roline
     *
     * Set by Roline::run() after instantiation. Used for help display
     * and internal reference. Format: 'category:action' (e.g., 'model:create')
     *
     * @var string
     */
    protected $commandName;

    /**
     * Execute the command logic
     *
     * This is the main entry point for command execution. Implement your
     * command's logic here, including argument parsing, validation, file
     * operations, database queries, etc.
     *
     * @param array $arguments Command arguments from CLI (everything after command name)
     *                         Example: ['User', '--force'] for "php roline model:create User --force"
     * @return void
     */
    abstract public function execute($arguments);

    /**
     * Get short command description for listing
     *
     * Return a brief one-line description (50 chars max recommended) that
     * explains what this command does. Used in command listings and help output.
     *
     * @return string Brief description (e.g., 'Create a new model class')
     */
    abstract public function description();

    /**
     * Get command usage syntax showing arguments
     *
     * Define the argument signature using conventions:
     *   - <required>   - Required argument
     *   - [optional]   - Optional argument
     *   - <name|flag>  - Argument with type flag (required/optional)
     *
     * Examples:
     *   ''                          - No arguments
     *   '<Model>'                   - One required argument
     *   '<Model> [table]'           - Required + optional
     *   '<name|required> [--force]' - With type flags
     *
     * @return string Usage syntax or empty string if no arguments
     */
    abstract public function usage();

    /**
     * Display detailed help information for this command
     *
     * Shows formatted help including command name, usage syntax, and description.
     * Override this method in subclasses to provide additional help sections
     * like examples, argument details, or notes. Call parent::help() first to
     * show the standard help header.
     *
     * @return void Outputs formatted help to terminal
     */
    public function help()
    {
        $commandName = $this->getCommandName();
        $usage = $this->usage();
        $description = $this->description();

        Output::line();
        Output::success("Command: {$commandName}");
        Output::line();

        Output::info('Usage:');

        // Build usage line with or without arguments
        $usageDisplay = $usage ? "  php roline {$commandName} {$usage}" : "  php roline {$commandName}";
        Output::line(Output::colorizeUsage($usageDisplay));
        Output::line();

        Output::info('Description:');
        Output::line("  {$description}");
        Output::line();
    }

    /**
     * Set the command name for internal reference
     *
     * Called by Roline::run() after instantiation to store the full command
     * name. This allows the command to know its own registered name for help
     * display and logging purposes.
     *
     * @param string $name Full command name (e.g., 'model:create')
     * @return void
     */
    public function setCommandName($name)
    {
        $this->commandName = $name;
    }

    /**
     * Get the command's registered name
     *
     * Returns the full command name as registered in Roline. If not set,
     * returns 'unknown' (shouldn't happen in normal execution).
     *
     * @return string Command name (e.g., 'model:create') or 'unknown'
     */
    protected function getCommandName()
    {
        return $this->commandName ?? 'unknown';
    }

    // =========================================================================
    // OUTPUT HELPER METHODS
    // =========================================================================

    /**
     * Display success message with green checkmark
     *
     * @param string $msg Success message to display
     * @return void
     */
    protected function success($msg)
    {
        Output::success($msg);
    }

    /**
     * Display error message with red X mark
     *
     * @param string $msg Error message to display
     * @return void
     */
    protected function error($msg)
    {
        Output::error($msg);
    }

    /**
     * Display info message with yellow arrow
     *
     * @param string $msg Info message to display
     * @return void
     */
    protected function info($msg)
    {
        Output::info($msg);
    }

    /**
     * Display plain text line without formatting
     *
     * @param string $msg Message to display (optional, defaults to blank line)
     * @return void
     */
    protected function line($msg = '')
    {
        Output::line($msg);
    }

    // =========================================================================
    // USER INTERACTION METHODS
    // =========================================================================

    /**
     * Prompt user for text input
     *
     * Displays a question and waits for user to type a response.
     * Returns the trimmed input.
     *
     * @param string $question Question to ask (e.g., 'Enter model name')
     * @return string User's trimmed response
     */
    protected function ask($question)
    {
        return Output::ask($question);
    }

    /**
     * Prompt user for yes/no confirmation
     *
     * Displays a question and waits for user to answer yes/no.
     * Accepts: 'yes', 'y' (case-insensitive) as confirmation.
     *
     * @param string $question Confirmation question (e.g., 'Delete this file?')
     * @return bool True if user confirmed (yes/y), false otherwise
     */
    protected function confirm($question)
    {
        return Output::confirm($question);
    }
}
