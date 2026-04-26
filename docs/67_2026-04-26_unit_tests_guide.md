# Unit Tests Guide

## Overview
Comprehensive unit tests for validation rules, helpers, exceptions, and services.

## Test Files

### Rules Tests

#### SafeHtmlTest
Tests the SafeHtml validation rule for XSS prevention.

```bash
php artisan test tests/Unit/Rules/SafeHtmlTest.php
```

**Test Cases:**
- Rejects script tags
- Rejects JavaScript protocol
- Rejects data URIs
- Rejects event handlers (onclick, onerror, etc.)
- Rejects iframe, embed, object tags
- Accepts safe HTML and plain text
- Handles encoded XSS attempts
- Ignores non-string values

**Example Usage:**
```php
$rule = new SafeHtml();
$passed = true;
$rule->validate('message', '<script>alert(1)</script>', fn() => $passed = false);
assert($passed === false);
```

#### ValidPhoneNumberTest
Tests the ValidPhoneNumber validation rule.

```bash
php artisan test tests/Unit/Rules/ValidPhoneNumberTest.php
```

**Test Cases:**
- Accepts standard Indonesian format (08xxxxxxxxxx)
- Accepts formatted numbers (0812-3456-789)
- Accepts international format (+62 8xx xxxx xxxx)
- Accepts alternative international format (628xxxxxxxxxx)
- Rejects short numbers
- Rejects wrong area codes
- Rejects invalid country codes
- Handles spaces and dashes

### Support Tests

#### ValidationHelperTest
Tests the ValidationHelper utility class.

```bash
php artisan test tests/Unit/Support/ValidationHelperTest.php
```

**Test Cases:**
- `sanitize()` - Removes dangerous content while preserving safe text
- `hasDangerousContent()` - Detects XSS patterns
- `isValidJson()` - Validates JSON strings
- `sanitizeArray()` - Recursively sanitizes arrays
- `isValidEmail()` - Email validation
- `isValidUrl()` - URL validation
- `sanitizeUrl()` - Removes dangerous URL schemes
- `truncate()` - Shortens strings with ellipsis

**Example Usage:**
```php
use App\Support\ValidationHelper;

$safe = ValidationHelper::sanitize($userInput);
if (!ValidationHelper::hasDangerousContent($input)) {
    // Process input
}
```

### Exception Tests

#### CustomExceptionsTest
Tests custom exception classes and their behavior.

```bash
php artisan test tests/Unit/Exceptions/CustomExceptionsTest.php
```

**Test Cases:**
- WaRuntimeException stores metadata correctly
- SippDataException includes source information
- InvalidInputException stores validation errors
- DatabaseException tracks operation details
- All exceptions include timestamps
- toArray() methods return proper structure
- toResponse() returns valid JSON

**Example:**
```php
$exception = new WaRuntimeException(
    message: 'Connection failed',
    code: 502,
    detail: 'Timeout after 30s',
    isRetryable: true
);

$array = $exception->toArray();
// Returns: [
//   'error' => 'Connection failed',
//   'code' => 502,
//   'detail' => 'Timeout after 30s',
//   'retryable' => true,
//   'timestamp' => '2026-04-21T10:30:00+07:00'
// ]
```

### Service Tests

#### WaCarakaServiceTest
Tests WA Caraka service with mocked HTTP requests.

```bash
php artisan test tests/Unit/Services/WaCarakaServiceTest.php
```

**Test Cases:**
- Health check returns successful response
- Handles connection failures gracefully
- Base URL is properly configured
- Broadcast limit is positive integer
- QR endpoint returns valid data
- Authentication token is included in requests
- Timeout is respected
- Device control endpoints work (disconnect, reconnect, refresh QR)
- Sequential requests work correctly

**Example:**
```php
Http::fake([
    '*' => Http::response(['status' => 'connected'], 200),
]);

$result = $this->service->health();
assert($result['ok'] === true);
```

## Running Tests

### Run All Unit Tests
```bash
php artisan test tests/Unit/
```

### Run Specific Test File
```bash
php artisan test tests/Unit/Rules/SafeHtmlTest.php
```

### Run Specific Test Method
```bash
php artisan test tests/Unit/Rules/SafeHtmlTest.php --filter test_rejects_script_tags
```

### Run with Coverage
```bash
php artisan test tests/Unit/ --coverage
```

### Run and Stop on First Failure
```bash
php artisan test tests/Unit/ --stop-on-failure
```

## Test Structure

### BaseFormRequest Pattern
```php
// app/Http/Requests/StoreMessageRequest.php
class StoreMessageRequest extends BaseFormRequest {
    public function rules(): array {
        return [
            'message' => [
                'required',
                'string',
                'max:2000',
                new SafeHtml(),
            ],
        ];
    }
}

// tests/Feature/StoreMessageTest.php
public function test_rejects_xss_payload() {
    $response = $this->postJson('/api/messages', [
        'message' => '<script>alert(1)</script>',
    ]);
    
    $response->assertInvalid('message');
}
```

## Best Practices

### 1. Test XSS Prevention
```php
public function test_rejects_dangerous_content() {
    $payloads = [
        '<script>alert(1)</script>',
        '<img src=x onerror=alert(1)>',
        'javascript:alert(1)',
        '<iframe src="http://evil.com"></iframe>',
    ];

    foreach ($payloads as $payload) {
        $rule = new SafeHtml();
        $passed = true;
        $rule->validate('field', $payload, fn() => $passed = false);
        $this->assertFalse($passed, "Failed to reject: $payload");
    }
}
```

### 2. Mock HTTP Requests
```php
public function test_handles_http_errors() {
    Http::fake([
        '*' => Http::sequence()
            ->push(Http::response([], 500))
            ->push(Http::response([], 503)),
    ]);

    $result1 = $this->service->request();
    $result2 = $this->service->request();

    $this->assertEquals(500, $result1['status']);
    $this->assertEquals(503, $result2['status']);
}
```

### 3. Assert Request Headers
```php
public function test_includes_authentication_header() {
    Http::fake();
    
    $this->service->authenticatedRequest();
    
    Http::assertSent(function ($request) {
        return $request->hasHeader('X-API-Token');
    });
}
```

### 4. Test Edge Cases
```php
public function test_handles_empty_input() {
    $this->assertFalse(ValidationHelper::isValidJson(''));
}

public function test_handles_whitespace() {
    $result = ValidationHelper::sanitize('   text   ');
    $this->assertStringContainsString('text', $result);
}
```

## Coverage Goals

| Component | Target | Status |
|-----------|--------|--------|
| Rules | 100% | ✓ |
| Helpers | 95%+ | ✓ |
| Exceptions | 100% | ✓ |
| Services | 85%+ | In Progress |
| Controllers | 80%+ | In Progress |

## Integration with CI/CD

### GitHub Actions Example
```yaml
name: Unit Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: php-actions/setup-php@v1
      - run: composer install
      - run: php artisan test tests/Unit/
        env:
          APP_ENV: testing
```

## Debugging Failed Tests

### Enable SQL Logging
```php
// In test setup
\DB::enableQueryLog();

// In test
$this->service->doWork();

// Debug
\Log::debug('Queries', \DB::getQueryLog());
```

### Print Debug Info
```php
public function test_something() {
    $result = $this->service->doWork();
    
    dump($result);  // Laravel dump
    dd($result);    // die and dump
}
```

### Use test:debug
```bash
php artisan test tests/Unit/Rules/SafeHtmlTest.php --debug
```

## Common Issues

### Issue: Faker Locale Not Found
**Solution:** Check `.env.testing` has `APP_FAKER_LOCALE=en_US`

### Issue: Database Table Not Found
**Solution:** Ensure migrations run before tests. Check `TestCase::setUp()`

### Issue: Mock Not Working
**Solution:** Verify mock setup before service instantiation
```php
Http::fake([...]);
$service = app(WaCarakaService::class);  // After mock setup
```

## References

- Tests: `tests/Unit/`
- Rules: `app/Rules/`
- Helpers: `app/Support/`
- Exceptions: `app/Exceptions/`
- Services: `app/Services/`
