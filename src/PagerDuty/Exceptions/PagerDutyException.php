<?php declare(strict_types=1);

namespace PagerDuty\Exceptions;

/**
 * PagerDutyException.
 *
 * @author adil
 */
class PagerDutyException extends \Exception
{
    public function __construct($message, protected array $errors)
    {
        parent::__construct($message);
    }

    public function getErrors() : array
    {
        return $this->errors;
    }
}
