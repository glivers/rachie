<?php namespace Roline\Commands;

/**
 * Help Command
 *
 * Displays general help information for Roline CLI including usage syntax,
 * common examples, and a pointer to the full command list. This provides
 * quick guidance for users unfamiliar with Roline's capabilities.
 *
 * Usage:
 *   php roline help
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

class Help extends Command
{
    public function description()
    {
        return 'Show help information';
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

        Output::info('Usage:');
        Output::line('  php roline <command> [arguments]');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline controller:create Posts');
        Output::line('  php roline model:create Post');
        Output::line('  php roline table:create Posts');
        Output::line('  php roline cache:clear');
        Output::line();

        Output::info('Run "php roline list" to see all available commands.');
        Output::line();
    }
}
