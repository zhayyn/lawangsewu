<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * WaRuntimeException
 * 
 * Thrown when communication with WA Caraka runtime fails.
 * Includes error details and retry recommendations.
 */
class WaRuntimeException extends Exception
{
    protected int $statusCode;
    protected ?string $detail;
    protected bool $isRetryable;

    public function __construct(
        string $message = 'Gagal terhubung ke WA runtime',
        int $code = 502,
        ?Throwable $previous = null,
        ?string $detail = null,
        bool $isRetryable = true
    ) {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $code;
        $this->detail = $detail;
        $this->isRetryable = $isRetryable;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
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
            'detail' => $this->detail,
            'retryable' => $this->isRetryable,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
