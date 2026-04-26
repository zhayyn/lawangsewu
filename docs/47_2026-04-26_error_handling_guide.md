# Error Handling Guide

## Overview
Comprehensive error handling with custom exceptions and consistent error responses.

## Custom Exceptions

### WaRuntimeException
Thrown when WA Caraka runtime communication fails.

```php
use App\Exceptions\WaRuntimeException;

try {
    $data = $waService->fetchMessages();
} catch (ConnectException $e) {
    throw new WaRuntimeException(
        message: 'Koneksi WA runtime gagal',
        code: 502,
        previous: $e,
        detail: 'Koneksi ditolak ke ' . config('wa_caraka.base_url'),
        isRetryable: true
    );
} catch (TimeoutException $e) {
    throw new WaRuntimeException(
        message: 'Request timeout ke WA runtime',
        code: 504,
        previous: $e,
        detail: 'Timeout setelah ' . config('wa_caraka.timeout') . ' detik',
        isRetryable: true
    );
}
```

**Response:**
```json
{
  "error": "Koneksi WA runtime gagal",
  "code": 502,
  "detail": "Koneksi ditolak ke http://127.0.0.1:8790",
  "retryable": true,
  "timestamp": "2026-04-21T10:30:00+07:00"
}
```

### SippDataException
Thrown when SIPP data source fails.

```php
use App\Exceptions\SippDataException;

try {
    $data = $sippService->fetchCaseData($caseId);
} catch (\PDOException $e) {
    throw new SippDataException(
        message: 'Gagal mengakses data SIPP',
        code: 503,
        previous: $e,
        source: 'SIPP Database',
        isRetryable: true
    );
}
```

**Response:**
```json
{
  "error": "Gagal mengakses data SIPP",
  "code": 503,
  "source": "SIPP Database",
  "retryable": true,
  "timestamp": "2026-04-21T10:30:00+07:00"
}
```

### InvalidInputException
Thrown for validation failures with detailed error information.

```php
use App\Exceptions\InvalidInputException;

try {
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'message' => ['required', 'string', 'max:1000', new SafeHtml()],
    ]);
} catch (ValidationException $e) {
    throw InvalidInputException::fromValidationException($e);
}
```

**Response:**
```json
{
  "error": "Data tidak valid",
  "code": 422,
  "errors": {
    "email": ["Email tidak valid"],
    "message": ["Message field contains invalid or dangerous content."]
  },
  "timestamp": "2026-04-21T10:30:00+07:00"
}
```

### DatabaseException
Thrown for database operation failures.

```php
use App\Exceptions\DatabaseException;

try {
    $user = User::create([
        'email' => $email,
        'name' => $name,
    ]);
} catch (\Illuminate\Database\QueryException $e) {
    if ($e->getCode() === '23505') { // Unique constraint
        throw new DatabaseException(
            message: 'Email sudah terdaftar',
            code: 409,
            previous: $e,
            operation: 'create_user',
            isRetryable: false
        );
    }
    
    throw new DatabaseException(
        message: 'Gagal menyimpan data',
        code: 500,
        previous: $e,
        operation: 'create_user',
        isRetryable: true
    );
}
```

**Response:**
```json
{
  "error": "Email sudah terdaftar",
  "code": 409,
  "operation": "create_user",
  "retryable": false,
  "timestamp": "2026-04-21T10:30:00+07:00"
}
```

## Exception Handler

The custom `Handler` class in `app/Exceptions/Handler.php` handles:
- Custom exception rendering to JSON
- Detailed error logging
- Consistent error response format
- Validation error formatting

## Usage Patterns

### Service Layer Error Handling
```php
namespace App\Services;

use App\Exceptions\WaRuntimeException;

class WaCarakaService {
    public function fetchMessages(string $conversationId): array {
        try {
            $response = $this->request()
                ->timeout(20)
                ->get('/messages', ['conversation_id' => $conversationId]);
            
            if (!$response->successful()) {
                throw new WaRuntimeException(
                    message: 'Gagal mengambil pesan',
                    code: $response->status(),
                    detail: $response->json('error'),
                    isRetryable: in_array($response->status(), [502, 503, 504])
                );
            }
            
            return $response->json('data');
        } catch (ConnectException $e) {
            throw new WaRuntimeException(
                message: 'Koneksi WA runtime gagal',
                code: 502,
                previous: $e,
                isRetryable: true
            );
        }
    }
}
```

### Controller Error Handling
```php
namespace App\Http\Controllers;

use App\Exceptions\WaRuntimeException;
use App\Exceptions\InvalidInputException;

class ChatController {
    public function getMessages(GetMessagesRequest $request) {
        try {
            $messages = $this->chatService->fetchMessages(
                $request->conversation_id
            );
            
            return response()->json([
                'success' => true,
                'data' => $messages,
            ]);
        } catch (WaRuntimeException $e) {
            // Will be handled by exception handler
            throw $e;
        } catch (Exception $e) {
            // Unexpected error
            \Log::error('Unexpected error fetching messages', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'Terjadi kesalahan',
                'code' => 500,
            ], 500);
        }
    }
}
```

### Queue Job Error Handling
```php
namespace App\Jobs;

use App\Exceptions\SippDataException;

class SyncSippData implements ShouldQueue {
    public function handle() {
        try {
            $this->sippService->syncCacheData();
        } catch (SippDataException $e) {
            if ($e->isRetryable()) {
                // Will retry automatically
                throw $e;
            } else {
                // Log and mark as failed
                \Log::error('SIPP sync failed (non-retryable)', $e->toArray());
                $this->fail($e);
            }
        }
    }
}
```

## Error Response Format

All error responses follow this format:

```json
{
  "error": "Human-readable error message",
  "code": 422,
  "detail": "Optional detailed information",
  "errors": {
    "field": ["Specific error messages"]
  },
  "retryable": true,
  "timestamp": "2026-04-21T10:30:00+07:00"
}
```

### Status Codes
- `400` - Bad request (invalid input)
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not found
- `409` - Conflict (e.g., duplicate entry)
- `422` - Validation failed
- `500` - Server error (unexpected)
- `502` - Bad gateway (external service failed)
- `503` - Service unavailable (SIPP, WA runtime)
- `504` - Gateway timeout

## Testing

### Test Exception Handling
```php
public function test_wa_runtime_exception_returns_json() {
    $this->mock(WaCarakaService::class, function ($mock) {
        $mock->shouldReceive('fetchMessages')
            ->andThrow(new WaRuntimeException(
                'Koneksi gagal',
                502,
                null,
                'Timeout'
            ));
    });
    
    $response = $this->getJson('/api/messages/1');
    
    $response->assertStatus(502);
    $response->assertJsonStructure([
        'error',
        'code',
        'detail',
        'retryable',
        'timestamp',
    ]);
}
```

### Test Validation Error Handling
```php
public function test_invalid_input_returns_detailed_errors() {
    $response = $this->postJson('/api/messages', [
        'message' => '<script>alert(1)</script>',
        'conversation_id' => 'invalid',
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonPath('errors.message.0', 
        'The message field contains invalid or dangerous content.'
    );
    $response->assertJsonPath('errors.conversation_id.0',
        'The conversation_id field must be an integer.'
    );
}
```

## Best Practices

1. **Throw at the Right Layer**
   - Services throw domain exceptions
   - Controllers handle and respond
   - Handlers provide consistent formatting

2. **Provide Context**
   - Include detail/source information
   - Log with context array
   - Help debugging and monitoring

3. **Mark Retryable Exceptions**
   - Network errors: retryable
   - Validation errors: not retryable
   - Database constraint violations: not retryable

4. **Avoid Exposing Internals**
   - Use clear, non-technical messages for users
   - Log technical details server-side
   - Never expose SQL, file paths, or API keys

5. **Test Error Scenarios**
   - Test each exception type
   - Test validation with XSS payloads
   - Test timeout and connection failures

## Files Reference

- `app/Exceptions/Handler.php` - Main exception handler
- `app/Exceptions/WaRuntimeException.php` - WA Caraka errors
- `app/Exceptions/SippDataException.php` - SIPP data errors
- `app/Exceptions/InvalidInputException.php` - Validation errors
- `app/Exceptions/DatabaseException.php` - Database errors
