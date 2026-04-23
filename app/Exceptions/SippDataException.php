<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * SippDataException
 * 
 * Thrown when SIPP data source fails or returns invalid data.
 * Used for SIPP Hub integration and data synchronization.
 */
class SippDataException extends Exception
{
    protected int $statusCode;
    protected ?string $source;
    protected bool $isRetryable;

    public function __construct(
        string $message = 'Gagal mengakses data SIPP',
        int $code = 503,
        ?Throwable $previous = null,
        ?string $source = null,
        bool $isRetryable = true
    ) {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $code;
        $this->source = $source;
        $this->isRetryable = $isRetryable;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getSource(): ?string
    {
        return $this->source;
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
            'source' => $this->source,
            'retryable' => $this->isRetryable,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
