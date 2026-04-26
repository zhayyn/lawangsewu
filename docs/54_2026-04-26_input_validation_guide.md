# Input Validation & Sanitization Framework

## Overview
Comprehensive input validation and XSS prevention framework to protect against common web vulnerabilities.

## Components

### 1. Validation Rules

#### SafeHtml Rule
Prevents XSS attacks by rejecting dangerous content:
```php
use App\Rules\SafeHtml;

$validated = $request->validate([
    'message' => ['required', 'string', 'max:1000', new SafeHtml()],
    'comment' => ['required', 'string', new SafeHtml()],
]);
```

**Blocks:**
- JavaScript event handlers (onclick=, onerror=, etc.)
- Script tags
- Iframe, embed, object tags
- JavaScript protocol (javascript:)
- Data protocol (data:text/html)
- HTML entity bypasses

#### ValidPhoneNumber Rule
Validates Indonesian phone numbers:
```php
use App\Rules\ValidPhoneNumber;

$validated = $request->validate([
    'phone' => ['required', 'string', new ValidPhoneNumber()],
]);
```

**Accepts:**
- `08xxxxxxxxxx` (unformatted)
- `08xx-xxxx-xxxx` (formatted)
- `+62 8xx xxxx xxxx` (international)
- `628xxxxxxxxxx` (without plus)

### 2. Base Form Request

Provides consistent validation across application:
```php
use App\Http\Requests\BaseFormRequest;

class StoreMessageRequest extends BaseFormRequest {
    public function rules(): array {
        return [
            'message' => ['required', 'string', 'max:2000', new SafeHtml()],
        ];
    }
}
```

**Features:**
- Auto-trim whitespace from all inputs
- Consistent error messages in Indonesian
- CSRF protection (inherited from FormRequest)
- Easy to extend

### 3. Validation Helper

Utility class for manual validation:
```php
use App\Support\ValidationHelper;

// Sanitize input
$safe = ValidationHelper::sanitize($userInput);

// Check for dangerous content
if (ValidationHelper::hasDangerousContent($input)) {
    // Reject or clean
}

// Validate JSON
if (ValidationHelper::isValidJson($data)) {
    // Process JSON
}

// Sanitize arrays
$cleaned = ValidationHelper::sanitizeArray($userData);

// Validate email
if (ValidationHelper::isValidEmail($email)) {
    // Process email
}

// Sanitize URL
$url = ValidationHelper::sanitizeUrl($userUrl);

// Truncate string
echo ValidationHelper::truncate($longText, 100);
```

## Implementation Examples

### Guestbook Controller
```php
class GuestbookController extends Controller {
    public function store(StoreGuestbookEntryRequest $request) {
        // Automatic validation happens in form request
        $validated = $request->validated();
        
        // Data is already validated and safe
        GuestbookEntry::create($validated);
        
        return response()->json(['success' => true]);
    }
}
```

### Chat Message Controller
```php
class ChatController extends Controller {
    public function storeMessage(StoreChatMessageRequest $request) {
        $validated = $request->validated();
        
        ChatMessage::create([
            'conversation_id' => $validated['conversation_id'],
            'message' => $validated['message'],
            'user_id' => auth()->id(),
        ]);
        
        broadcast(new ChatMessageSent(...));
        
        return response()->json(['success' => true]);
    }
}
```

### Manual Validation
```php
use App\Support\ValidationHelper;

$input = request('comment');

// Check before storing
if (ValidationHelper::hasDangerousContent($input)) {
    return response()->json(['error' => 'Invalid input'], 422);
}

// Or sanitize
$safe = ValidationHelper::sanitize($input);
$this->saveComment($safe);
```

## Best Practices

1. **Always use Form Requests**
   - Centralize validation logic
   - Ensures consistency
   - Easier to test

2. **Stack Multiple Rules**
   ```php
   'email' => [
       'required',
       'email:rfc,dns',  // Built-in Laravel rule
       'max:255',
   ],
   ```

3. **Use SafeHtml for User Content**
   - All message fields
   - Comments
   - Feedback forms
   - Any user-generated content

4. **Sanitize Array Data**
   ```php
   $userData = ValidationHelper::sanitizeArray(
       request()->only(['name', 'email', 'message'])
   );
   ```

5. **Test Input Validation**
   - Test with XSS payloads
   - Test with oversized input
   - Test with special characters

## Testing

### Unit Tests for Rules
```php
// tests/Unit/Rules/SafeHtmlTest.php
public function test_rejects_script_tags() {
    $rule = new SafeHtml();
    $passes = true;
    $rule->validate('field', '<script>alert(1)</script>', 
        function() { $passes = false; }
    );
    
    $this->assertFalse($passes);
}
```

### Integration Tests
```php
// tests/Feature/GuestbookValidationTest.php
public function test_guestbook_rejects_xss_payload() {
    $response = $this->post('/guestbook', [
        'visitor_name' => 'John<script>alert(1)</script>',
        'visitor_email' => 'john@example.com',
        'visitor_phone' => '08123456789',
        'visit_purpose' => 'Meeting',
    ]);
    
    $response->assertInvalid('visitor_name');
}
```

## Common Validation Rules Library

### Files
- `app/Rules/SafeHtml.php` - XSS prevention
- `app/Rules/ValidPhoneNumber.php` - Phone validation
- `app/Http/Requests/BaseFormRequest.php` - Base request class
- `app/Support/ValidationHelper.php` - Utility functions

### Form Requests
- `app/Http/Requests/StoreGuestbookEntryRequest.php`
- `app/Http/Requests/StoreChatMessageRequest.php`

## Extension Points

### Create Custom Rules
```php
// app/Rules/ValidNIP.php
class ValidNIP implements ValidationRule {
    public function validate($attribute, $value, $fail) {
        if (!preg_match('/^\d{18}$/', $value)) {
            $fail('The NIP format is invalid.');
        }
    }
}
```

### Add to Validation Helper
```php
// In ValidationHelper class
public static function isValidNIP(string $nip): bool {
    return preg_match('/^\d{18}$/', $nip) === 1;
}
```

## Performance Considerations

- Rules are applied in order (put restrictive checks first)
- Use eager validation to stop early on first error
- Avoid expensive regex patterns on large strings
- Cache frequently used patterns if needed

## Security Checklist

- [ ] All user input uses SafeHtml or custom rule
- [ ] Email fields use email:rfc,dns rule
- [ ] URLs sanitized with ValidateUrl
- [ ] Arrays sanitized with sanitizeArray()
- [ ] Form requests extend BaseFormRequest
- [ ] Tests cover XSS payloads
- [ ] Error messages don't expose system info
