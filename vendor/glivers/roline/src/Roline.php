<?php namespace Roline;

/**
 * Roline - Rachie Command Line Interface
 *
 * The main CLI application orchestrator that handles command routing, discovery,
 * validation, and execution. Roline provides a developer-friendly command-line
 * interface for Rachie framework operations including code generation, database
 * management, migrations, and maintenance tasks.
 *
 * Architecture:
 *   - Command Pattern: Each operation is encapsulated as a Command class
 *   - Smart Discovery: Partial command matching with helpful suggestions
 *   - Auto Help: Built-in --help flag support for all commands
 *   - Error Handling: Graceful failures with actionable error messages
 *
 * Command Registration:
 *   All commands are registered in registerCommands() using the format:
 *   'command:subcommand' => Fully\Qualified\ClassName::class
 *
 * Execution Flow:
 *   1. User runs: php roline controller:create Posts
 *   2. Roline parses command name and arguments from $argv
 *   3. Checks if command exists in registry
 *   4. If not found, tries partial matching (e.g., "controller" → "controller:*")
 *   5. Instantiates command class and calls execute()
 *   6. Handles --help flag automatically for all commands
 *   7. Catches exceptions and displays formatted errors
 *
 * Usage Examples:
 *
 *   // List all available commands
 *   php roline list
 *
 *   // Get help for specific command
 *   php roline controller:create --help
 *
 *   // Execute command with arguments
 *   php roline model:create User
 *   php roline table:create posts
 *   php roline migration:run
 *
 *   // Partial command triggers suggestions
 *   php roline controller
 *   → Shows: controller:create, controller:append, etc.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

class Roline
{
    /**
     * Registry of all available CLI commands
     *
     * Format: ['command:subcommand' => ClassName::class]
     * Populated by registerCommands() during instantiation
     *
     * @var array
     */
    private $commands = [];

    /**
     * Initialize Roline CLI and register all available commands
     *
     * The constructor is called once when Roline is instantiated in the
     * main roline executable file. It pre-loads all command class mappings
     * into the registry for fast lookup during execution.
     */
    public function __construct()
    {
        $this->registerCommands();
    }

    /**
     * Execute a CLI command based on user input
     *
     * This is the main entry point for command execution. It handles the complete
     * command lifecycle: parsing arguments, validating command existence, checking
     * for help flags, instantiating the appropriate command class, and executing it.
     *
     * Command Resolution Order:
     *   1. Check for exact command match in registry
     *   2. If not found, try partial matching (e.g., "controller" → "controller:*")
     *   3. Display suggestions if partial matches found
     *   4. Show error if no matches at all
     *
     * Special Handling:
     *   - Defaults to 'list' command if no command provided
     *   - Automatically handles --help and -h flags
     *   - Catches all exceptions and displays formatted errors
     *   - Uses exit codes: 0 = success, 1 = error
     *
     * @param array $argv Raw command-line arguments from PHP's $argv
     *                    Format: [0 => 'roline', 1 => 'command:name', 2+ => arguments]
     * @return void Exits with status code (0 or 1)
     */
    public function run($argv)
    {
        // Parse command name from arguments, default to 'list' if none provided
        $commandName = $argv[1] ?? 'list';
        $arguments = array_slice($argv, 2);

        // Handle global flags (--version, -v, --help, -h) before command lookup
        if (in_array($commandName, ['--version', '-v']))
        {
            $commandName = 'version';
        }
        elseif (in_array($commandName, ['--help', '-h']))
        {
            $commandName = 'help';
        }

        // Validate that the requested command exists in our registry
        if (!isset($this->commands[$commandName]))
        {
            // Command not found - try smart partial matching for better UX
            // Example: "controller" will match "controller:create", "controller:append", etc.
            $relatedCommands = $this->findRelatedCommands($commandName);

            if (!empty($relatedCommands))
            {
                // Found partial matches - display helpful suggestions
                $this->showRelatedCommands($commandName, $relatedCommands);
            }
            else
            {
                // No matches at all - display error and help message
                Output::error("Unknown command: {$commandName}");
                Output::line();
                Output::info("Run 'php roline list' to see all available commands.");
                Output::line();
            }

            exit(1);
        }

        // Command exists - retrieve the fully qualified class name
        $commandClass = $this->commands[$commandName];

        try
        {
            // Instantiate the command class and prepare it for execution
            $command = new $commandClass();

            // Set the command name for help display and internal reference
            $command->setCommandName($commandName);

            // Check for help flag before executing the command
            // This allows users to get help without triggering validation errors
            if (in_array('--help', $arguments) || in_array('-h', $arguments))
            {
                $command->help();
                exit(0);
            }

            // Execute the command with provided arguments
            $command->execute($arguments);
        }
        catch (\Exception $e)
        {
            // Catch any exceptions during command execution and display formatted error
            Output::error("Command failed: {$e->getMessage()}");
            exit(1);
        }
    }

    /**
     * Register all available Roline commands into the command registry
     *
     * This method defines the complete mapping of command names to their
     * implementing classes. Commands are organized by category (Controller,
     * Model, View, Table, Migration, Database, Cleanup, Utility) for better
     * maintainability.
     *
     * Command Naming Convention:
     *   - Format: 'category:action' (e.g., 'model:create', 'table:update')
     *   - Category should be singular noun (model, not models)
     *   - Action should be verb (create, delete, update)
     *
     * Adding New Commands:
     *   1. Create command class extending Roline\Command
     *   2. Add entry here: 'category:action' => Commands\Category\ClassName::class
     *   3. Command automatically available in CLI
     *
     * @return void
     */
    private function registerCommands()
    {
        $this->commands = [
            // Controller commands - Create, modify, and delete controllers
            'controller:create'   => Commands\Controller\ControllerCreate::class,
            'controller:append'   => Commands\Controller\ControllerAppend::class,
            'controller:delete'   => Commands\Controller\ControllerDelete::class,
            'controller:rename'   => Commands\Controller\ControllerRename::class,
            'controller:complete' => Commands\Controller\ControllerComplete::class,

            // Model commands - Generate and manage model classes and tables
            'model:create'        => Commands\Model\ModelCreate::class,
            'model:delete'        => Commands\Model\ModelDelete::class,
            'model:append'        => Commands\Model\ModelAppend::class,
            'model:rename'        => Commands\Model\ModelRename::class,
            'model:table-create'  => Commands\Model\ModelCreateTable::class,
            'model:table-update'  => Commands\Model\ModelUpdateTable::class,
            'model:table-drop'    => Commands\Model\ModelDropTable::class,
            'model:table-rename'  => Commands\Model\ModelRenameTable::class,
            'model:table-schema'  => Commands\Model\ModelTableSchema::class,
            'model:table-empty'   => Commands\Model\ModelEmptyTable::class,
            'model:table-reset'   => Commands\Model\ModelResetTable::class,
            'model:table-export'  => Commands\Model\ModelExportTable::class,

            // View commands - Create and manage view templates
            'view:create'         => Commands\View\ViewCreate::class,
            'view:add'            => Commands\View\ViewAdd::class,
            'view:delete'         => Commands\View\ViewDelete::class,
            'view:rename'         => Commands\View\ViewRename::class,

            // Table commands - Direct database table operations (no models required)
            'table:create'        => Commands\Table\TableCreate::class,
            'table:copy'          => Commands\Table\TableCopy::class,
            'table:delete'        => Commands\Table\TableDelete::class,
            'table:rename'        => Commands\Table\TableRename::class,
            'table:schema'        => Commands\Table\TableSchema::class,
            'table:empty'         => Commands\Table\TableEmpty::class,
            'table:reset'         => Commands\Table\TableReset::class,
            'table:export'        => Commands\Table\TableExport::class,
            'table:partition'     => Commands\Table\TablePartition::class,
            'table:unpartition'   => Commands\Table\TableUnpartition::class,

            // Migration commands - Version-controlled database changes
            'migration:make'      => Commands\Migration\MigrationMake::class,
            'migration:run'       => Commands\Migration\MigrationRun::class,
            'migration:rollback'  => Commands\Migration\MigrationRollback::class,
            'migration:status'    => Commands\Migration\MigrationStatus::class,
            'migration:reset'     => Commands\Migration\MigrationReset::class,

            // Database commands - Database-level operations
            'db:list'             => Commands\Database\DbList::class,
            'db:tables'           => Commands\Database\DbTables::class,
            'db:create'           => Commands\Database\DbCreate::class,
            'db:drop'             => Commands\Database\DbDrop::class,
            'db:drop-tables'      => Commands\Database\DbDropTables::class,
            'db:reset'            => Commands\Database\DbReset::class,
            'db:schema'           => Commands\Database\DbSchema::class,
            'db:seed'             => Commands\Database\DbSeed::class,
            'db:export'           => Commands\Database\DbExport::class,
            'db:import'           => Commands\Database\DbImport::class,

            // Cleanup commands - Maintenance and cache clearing
            'cleanup:cache'       => Commands\Cleanup\CleanupCache::class,
            'cleanup:views'       => Commands\Cleanup\CleanupViews::class,
            'cleanup:logs'        => Commands\Cleanup\CleanupLogs::class,
            'cleanup:sessions'    => Commands\Cleanup\CleanupSessions::class,
            'cleanup:all'         => Commands\Cleanup\CleanupAll::class,

            // Utility commands - Information and help
            'list'                => Commands\ListCommands::class,
            'help'                => Commands\Help::class,
            'version'             => Commands\Version::class,
        ];
    }

    /**
     * Get all registered commands from the command registry
     *
     * This method is primarily used by the 'list' command to display
     * all available commands to the user. It exposes the internal
     * command registry as a read-only array.
     *
     * @return array Associative array of registered commands
     *               Format: ['command:name' => 'Fully\Qualified\ClassName']
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Find all commands that match a partial command prefix
     *
     * This method provides smart command discovery when users type incomplete
     * commands. For example, if a user types "php roline controller", this will
     * find all commands starting with "controller:" and display them as suggestions.
     *
     * This improves UX by:
     *   - Helping users discover available subcommands
     *   - Reducing need to constantly run "php roline list"
     *   - Providing contextual help when users forget subcommand names
     *
     * Matching Strategy:
     *   - Only matches exact prefix + colon separator
     *   - Example: "model" matches "model:create" but not "models" or "modeling"
     *   - Case-sensitive matching
     *   - Returns empty array if no matches found
     *
     * @param string $partial Partial command prefix without colon (e.g., "controller", "table")
     * @return array Associative array of matching commands ['command:name' => ClassName]
     *               Empty array if no matches found
     */
    private function findRelatedCommands($partial)
    {
        $related = [];

        // Iterate through all registered commands looking for prefix matches
        foreach ($this->commands as $name => $class)
        {
            // Match commands with exact format: "{partial}:{subcommand}"
            // Using strpos for performance (faster than preg_match for simple prefix check)
            if (strpos($name, $partial . ':') === 0)
            {
                $related[$name] = $class;
            }
        }

        return $related;
    }

    /**
     * Display a formatted list of related commands for a partial match
     *
     * When a user types an incomplete command (e.g., "controller" instead of
     * "controller:create"), this method displays a helpful message with all
     * matching subcommands, their usage syntax, descriptions, and examples.
     *
     * Output Format:
     *   - Error message indicating incomplete command
     *   - List of matching commands with colorized usage syntax
     *   - General usage pattern
     *   - Concrete examples using first matched command
     *
     * The colorized output helps users distinguish between:
     *   - Required arguments (highlighted)
     *   - Optional arguments (dimmed)
     *   - Command structure
     *
     * @param string $partial Partial command name that was typed (e.g., "controller")
     * @param array $relatedCommands Array of matching commands from findRelatedCommands()
     *                               Format: ['command:name' => ClassName]
     * @return void Outputs formatted help and exits with status 1
     */
    private function showRelatedCommands($partial, $relatedCommands)
    {
        Output::line();
        Output::error("Incomplete command: {$partial}");
        Output::line();

        Output::info("Did you mean one of these " . ucfirst($partial) . " commands?");
        Output::line();

        // Display each related command with its usage and description
        foreach ($relatedCommands as $name => $class)
        {
            // Instantiate command to access its metadata
            $command = new $class();
            $usage = method_exists($command, 'usage') ? $command->usage() : '';
            $description = method_exists($command, 'description') ? $command->description() : '';

            // Build full command display with usage syntax
            $fullCommand = $name . ($usage ? ' ' . $usage : '');

            // Apply color coding to usage arguments (required/optional highlighting)
            $colorizedDisplay = Output::colorizeUsage($fullCommand);

            // Calculate padding to align descriptions in a column
            // Uses max(1, ...) to ensure at least one space between command and description
            $padding = str_repeat(' ', max(1, 45 - strlen($fullCommand)));

            Output::line("  {$colorizedDisplay}{$padding}{$description}");
        }

        // Display general usage instructions
        Output::line();
        Output::info('Usage:');
        Output::line("  php roline {$partial}:<subcommand> [arguments]");
        Output::line("  php roline {$partial}:<subcommand> --help");
        Output::line();

        // Show concrete examples using the first available command
        Output::info('Examples:');
        $firstCommand = array_key_first($relatedCommands);
        Output::line("  php roline {$firstCommand}");
        Output::line("  php roline {$firstCommand} --help");
        Output::line();
    }
}
