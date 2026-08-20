<?php namespace Roline\Commands;

/**
 * ListCommands Command
 *
 * Displays all available Roline commands organized by category with usage
 * syntax and descriptions. This is the default command when running 'php roline'
 * without any arguments, providing an overview of the CLI's capabilities.
 *
 * Categories:
 *   - Controller: Create and manage controllers
 *   - Model: Generate and manage models
 *   - View: Create and manage views
 *   - Table: Database schema operations
 *   - Migration: Version-controlled database changes
 *   - Database: Database-level operations
 *   - Cache: Cleanup commands
 *   - Utility: Help, version, and list commands
 *
 * Usage:
 *   php roline list
 *   php roline
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Command;
use Roline\Output;
use Roline\Roline;

class ListCommands extends Command
{
    public function description()
    {
        return 'List all available commands';
    }

    public function usage()
    {
        return '';
    }

    public function execute($arguments)
    {
        Output::line();
        Output::success('Roline - Rachie Command Line Interface');
        Output::line();

        // Get all registered commands from Roline
        $roline = new Roline();
        $commands = $roline->getCommands();

        // Initialize category buckets for organizing commands
        $categories = [
            'Controller' => [],
            'Model' => [],
            'View' => [],
            'Table' => [],
            'Migration' => [],
            'Database' => [],
            'Cache' => [],
            'Utility' => []
        ];

        // Categorize each command by its prefix
        foreach ($commands as $name => $class)
        {
            // Instantiate command to access metadata
            $command = new $class();
            $usage = method_exists($command, 'usage') ? $command->usage() : '';
            $description = method_exists($command, 'description') ? $command->description() : '';

            // Build full command display with usage syntax
            $fullCommand = $name . ($usage ? ' ' . $usage : '');

            // Sort into appropriate category based on command prefix
            if (strpos($name, 'controller:') === 0)
            {
                $categories['Controller'][$fullCommand] = $description;
            }
            elseif (strpos($name, 'model:') === 0)
            {
                $categories['Model'][$fullCommand] = $description;
            }
            elseif (strpos($name, 'view:') === 0)
            {
                $categories['View'][$fullCommand] = $description;
            }
            elseif (strpos($name, 'table:') === 0)
            {
                $categories['Table'][$fullCommand] = $description;
            }
            elseif (strpos($name, 'migration:') === 0)
            {
                $categories['Migration'][$fullCommand] = $description;
            }
            elseif (strpos($name, 'db:') === 0)
            {
                $categories['Database'][$fullCommand] = $description;
            }
            elseif (strpos($name, 'cache:') === 0 || strpos($name, 'cleanup:') === 0)
            {
                $categories['Cache'][$fullCommand] = $description;
            }
            else
            {
                // Everything else goes in Utility (list, help, version)
                $categories['Utility'][$fullCommand] = $description;
            }
        }

        // Display commands grouped by category
        foreach ($categories as $category => $cmds)
        {
            // Skip empty categories
            if (empty($cmds)) continue;

            Output::info("{$category} Commands:");

            foreach ($cmds as $commandDisplay => $description)
            {
                // Apply color coding to usage arguments
                $colorizedDisplay = Output::colorizeUsage($commandDisplay);

                // Calculate padding to align descriptions in a column
                // max(1, ...) ensures at least one space between command and description
                $padding = str_repeat(' ', max(1, 35 - strlen($commandDisplay)));

                Output::line("  {$colorizedDisplay}{$padding}{$description}");
            }

            Output::line();
        }

        // Display general usage instructions
        Output::info('Usage:');
        Output::line('  php roline <command> [arguments]');
        Output::line('  php roline <command> --help');
        Output::line();

        // Show practical examples
        Output::info('Examples:');
        Output::line('  php roline controller:create Posts');
        Output::line('  php roline model:create Post');
        Output::line('  php roline table:create posts');
        Output::line();
    }
}
