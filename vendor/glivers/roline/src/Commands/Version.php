<?php namespace Roline\Commands;

/**
 * Version Command
 *
 * Displays version information for Roline CLI and Rachie Framework.
 * Useful for debugging, support requests, and compatibility checking.
 *
 * Usage:
 *   php roline version
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

class Version extends Command
{
    public function description()
    {
        return 'Show version information';
    }

    public function usage()
    {
        return '';
    }

    public function execute($arguments)
    {
        Output::line();
        Output::success('Roline CLI v1.0.0');
        Output::line('Rachie Framework v2.0');
        Output::line();
    }
}
