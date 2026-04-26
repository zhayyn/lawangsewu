<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * DatabaseException
 * 
 * Thrown when database operations fail.
 * Helps distinguish database errors from other types of failures.
 */
class DatabaseException extends Exception
{
    protected int $statusCode;
    protected string $operation;
    protected bool $isRetryable;

    public function __construct(
        string $message = 'Database operation failed',
        int $code = 500,
        ?Throwable $previous = null,
        string $operation = 'unknown',
        bool $isRetryable = true
    ) {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $code;
        $this->operation = $operation;
        $this->isRetryable = $isRetryable;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function isRetryable(): bool
    {
        return $this->isRetryable;
    }

    public function toArray(): array
    {
        return [
            'error' => $this->message,
            'code' => $this->code,
            'operation' => $this->operation,
            'retryable' => $this->isRetryable,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
