<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Exception Handler
 * 
 * Handles application exceptions and renders appropriate responses.
 * Provides consistent error formatting for API and web responses.
 */
class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log custom exceptions with full context
            if ($e instanceof WaRuntimeException) {
                \Log::warning('WA Runtime error: ' . $e->getMessage(), [
                    'detail' => $e->getDetail(),
                    'code' => $e->getCode(),
                    'retryable' => $e->isRetryable(),
                ]);
            }

            if ($e instanceof SippDataException) {
                \Log::warning('SIPP data error: ' . $e->getMessage(), [
                    'source' => $e->getSource(),
                    'code' => $e->getCode(),
                    'retryable' => $e->isRetryable(),
                ]);
            }

            if ($e instanceof DatabaseException) {
                \Log::error('Database error: ' . $e->getMessage(), [
                    'operation' => $e->getOperation(),
                    'code' => $e->getCode(),
                ]);
            }
        });

        // Render custom exceptions
        $this->renderable(function (WaRuntimeException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json($e->toArray(), $e->getStatusCode());
            }
        });

        $this->renderable(function (SippDataException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json($e->toArray(), $e->getStatusCode());
            }
        });

        $this->renderable(function (InvalidInputException $e, Request $request) {
            if ($request->expectsJson()) {
                return $e->toResponse($request);
            }
        });

        $this->renderable(function (DatabaseException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json($e->toArray(), $e->getStatusCode());
            }
        });
    }

    /**
     * Prepare exception for rendering.
     */
    public function render($request, Throwable $e): Response
    {
        // Handle validation exceptions
        if ($e instanceof ValidationException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Validasi gagal',
                    'code' => 422,
                    'errors' => $e->errors(),
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }
        }

        return parent::render($request, $e);
    }
}
