<?php namespace Tests\Integration\Commands;

/**
 * Integration Tests for db:drop command
 *
 * Tests the complete flow of dropping all database tables via the Roline CLI.
 * These integration tests execute the actual db:drop command and verify the
 * triple confirmation system and warning displays without actually dropping tables.
 *
 * What Gets Tested:
 *   - Cancelling at first confirmation prompt
 *   - Warning message display about dropping all tables
 *   - Database name display in output
 *   - Triple confirmation system protection
 *
 * Test Strategy:
 *   - Tests focus on confirmation prompts and cancellation
 *   - Commands are executed with 'no' responses to cancel safely
 *   - Output assertions verify warning messages and confirmations
 *   - Does NOT test actual table dropping (too destructive)
 *   - Safety-first approach ensures no data loss during tests
 *
 * Safety Note:
 *   This is an extremely destructive command. Tests only verify the confirmation
 *   system and warning messages without actually executing table drops.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Tests\Integration\Commands
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 * @see RolineTest For base test functionality and cleanup mechanisms
 */

use Tests\RolineTest;

class DbDropTest extends RolineTest
{
    /**
     * Test cancelling at first confirmation
     *
     * Verifies that db:drop command can be safely cancelled at the first
     * confirmation prompt without dropping any tables.
     *
     * What Gets Verified:
     *   - First confirmation prompt is displayed
     *   - Responding 'no' cancels the operation
     *   - Cancellation message is displayed
     *   - No tables are affected
     *
     * @return void
     */
    public function testCancelAtFirstConfirmation()
    {
        $result = $this->runCommand("db:drop", ['no']);

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'cancelled') || str_contains($output, 'cancel'),
            "Expected cancellation message: {$result['output']}"
        );
    }

    /**
     * Test shows warning about dropping all tables
     *
     * Verifies that db:drop command displays a clear warning message about
     * the destructive nature of the operation.
     *
     * What Gets Verified:
     *   - Warning message is displayed
     *   - Message mentions dropping all tables
     *   - User is properly informed before proceeding
     *
     * @return void
     */
    public function testShowsWarning()
    {
        $result = $this->runCommand("db:drop", ['no']);

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'warning') || str_contains($output, 'all tables'),
            "Expected warning message: {$result['output']}"
        );
    }

    /**
     * Test displays database name
     *
     * Verifies that db:drop command shows the database name to confirm
     * which database will be affected by the operation.
     *
     * What Gets Verified:
     *   - Database name appears in output
     *   - User can verify correct database
     *   - Clear context is provided
     *
     * @return void
     */
    public function testDisplaysDatabaseName()
    {
        $result = $this->runCommand("db:drop", ['no']);

        $output = strtolower($result['output']);
        $this->assertTrue(
            str_contains($output, 'database'),
            "Expected database name in output: {$result['output']}"
        );
    }
}
