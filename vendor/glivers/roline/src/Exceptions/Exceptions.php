<?php namespace Roline\Exceptions;

/**
 * Exceptions
 *
 * Roline CLI exception with error type and auto-fix support.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Roline
 * @package Roline\Exceptions
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */
class Exceptions extends \Exception
{
    /**
     * Type of validation error
     * @var string
     */
    protected $errorType;

    /**
     * Suggested fix for the error
     * @var string|null
     */
    protected $suggestedFix;

    /**
     * Can this error be auto-fixed by Roline?
     * @var bool
     */
    protected $autoFixable;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param string $errorType Type of error (missing_pk, missing_timestamps, etc.)
     * @param bool $autoFixable Can Roline auto-fix this?
     * @param string|null $suggestedFix Suggested fix description
     */
    public function __construct($message, $errorType = 'error', $autoFixable = false, $suggestedFix = null)
    {
        parent::__construct($message);
        $this->errorType = $errorType;
        $this->autoFixable = $autoFixable;
        $this->suggestedFix = $suggestedFix;
    }

    /**
     * Get error type
     *
     * @return string
     */
    public function getErrorType()
    {
        return $this->errorType;
    }

    /**
     * Check if error can be auto-fixed
     *
     * @return bool
     */
    public function isAutoFixable()
    {
        return $this->autoFixable;
    }

    /**
     * Get suggested fix
     *
     * @return string|null
     */
    public function getSuggestedFix()
    {
        return $this->suggestedFix;
    }
}
