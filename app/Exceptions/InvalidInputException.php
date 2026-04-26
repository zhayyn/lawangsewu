<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * InvalidInputException
 * 
 * Custom exception for validation errors with detailed error information.
 * Implements Responsable for automatic JSON response rendering.
 */
class InvalidInputException extends Exception implements Responsable
{
    protected array $errors = [];
    protected int $statusCode = 422;

    public function __construct(
        array $errors,
        string $message = 'Validasi input gagal',
        int $code = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
        $this->statusCode = $code;
    }

    public static function fromValidationException(ValidationException $e): self
    {
        return new self(
            errors: $e->validator->errors()->toArray(),
            message: 'Data tidak valid',
            code: 422
        );
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'error' => $this->message,
            'code' => $this->code,
            'errors' => $this->errors,
            'timestamp' => now()->toIso8601String(),
        ], $this->statusCode);
    }

    public function toArray(): array
    {
        return [
            'error' => $this->message,
            'code' => $this->code,
            'errors' => $this->errors,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
