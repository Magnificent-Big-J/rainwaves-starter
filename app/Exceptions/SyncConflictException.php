<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown by sync resource handlers when a client operation loses against
 * newer server state; the processor records the op as a conflict.
 */
class SyncConflictException extends Exception
{
    public function __construct(
        public readonly array $errors,
        string $message = 'Sync conflict.'
    ) {
        parent::__construct($message);
    }
}
