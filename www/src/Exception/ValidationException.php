<?php

namespace App\Exception;

use Exception;

class ValidationException extends Exception
{
    /** @var array $messages */
    private array $messages;

    public function __construct(array $messages)
    {
        parent::__construct('Validation failed', 422);
        $this->messages = $messages;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}