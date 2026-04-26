# API Documentation

## Overview

Lawangsewu API provides access to case management, WhatsApp integration, and SIPP database synchronization for PA Semarang digital infrastructure.

**Base URL**: `https://lawangsewu.app/api`  
**Authentication**: OAuth 2.0 + JWT Bearer Token  
**Rate Limit**: 60 requests/minute per user

## Health & Status Endpoints

### Health Check
```http
GET /health
```

**Response**: `200 OK`
```json
{
  "status": "healthy",
  "timestamp": "2025-01-20T10:30:00Z",
  "environment": "production",
  "components": {
    "application": {
      "status": "healthy",
      "version": "1.0.0",
      "environment": "production",
      "debug": false
    },
    "database": {
      "status": "healthy",
      "latency_ms": 2.45
    },
    "cache": {
      "status": "healthy",
      "driver": "redis",
      "latency_ms": 1.23
    },
    "wa_runtime": {
      "status": "healthy",
      "latency_ms": 45.67
    },
    "sipp_database": {
      "status": "healthy",
      "latency_ms": 156.23
    }
  },
  "summary": {
    "healthy_count": 5,
    "degraded_count": 0,
    "unhealthy_count": 0,
    "disabled_count": 0
  }
}
```

### Readiness Probe (Kubernetes)
```http
GET /ready
```

Returns `200` if critical services ready, `503` otherwise.

### Liveness Probe (Kubernetes)
```http
GET /live
```

Always returns `200` if application process alive.

## Authentication Endpoints

### OAuth Login
```http
POST /auth/oauth/google
Content-Type: application/json

{
  "token": "google_id_token_jwt"
}
```

**Response**: `200 OK`
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "user": {
    "id": 123,
    "email": "user@example.com",
    "name": "John Doe",
    "role": "viewer"
  }
}
```

## Chat Message Endpoints

### List Messages
```http
GET /lawangsewu/chat?conversation_id=456&page=1&per_page=50
Authorization: Bearer {access_token}
```

**Query Parameters**:
- `conversation_id` (required): Conversation ID
- `page` (optional, default: 1): Page number
- `per_page` (optional, default: 50): Records per page

**Response**: `200 OK`
```json
{
  "data": [
    {
      "id": 789,
      "conversation_id": 456,
      "user_id": 123,
      "message": "Status update on case",
      "type": "message",
      "created_at": "2025-01-20T10:00:00Z",
      "user": {
        "id": 123,
        "name": "John Doe"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 245,
    "last_page": 5
  }
}
```

### Send Message
```http
POST /lawangsewu/chat/messages
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "conversation_id": 456,
  "message": "Status update on case",
  "type": "message"
}
```

**Validation**:
- `conversation_id`: required, exists in database
- `message`: required, 1-5000 characters, no XSS content
- `type`: optional, enum: message, note, system

**Response**: `201 Created`
```json
{
  "id": 789,
  "conversation_id": 456,
  "user_id": 123,
  "message": "Status update on case",
  "type": "message",
  "created_at": "2025-01-20T10:00:00Z"
}
```

**Error Response**: `422 Unprocessable Entity`
```json
{
  "error": "The given data was invalid.",
  "code": "VALIDATION_FAILED",
  "errors": {
    "message": [
      "The message field is required.",
      "The message must contain safe HTML only."
    ]
  },
  "timestamp": "2025-01-20T10:00:00Z"
}
```

## WhatsApp Integration Endpoints

### Send WhatsApp Message (Operator Only)
```http
POST /lawangsewu/chat/messages
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "conversation_id": 456,
  "message": "Important case update",
  "phone": "6281234567890",
  "send_via_wa": true
}
```

**Requirements**:
- User role: operator or admin
- Phone format: Indonesian (08x or +62x)
- Feature flag: `FEATURE_WA_CARAKA` must be enabled

**Response**: `201 Created`
```json
{
  "id": 789,
  "conversation_id": 456,
  "message": "Important case update",
  "phone": "6281234567890",
  "sent_via_wa": true,
  "wa_status": "pending",
  "created_at": "2025-01-20T10:00:00Z"
}
```

### Webhook: Incoming WhatsApp Message
```http
POST /wa-caraka/webhook/inbound
Content-Type: application/json
X-Signature: <hmac_sha256>

{
  "phone": "6281234567890",
  "message": "Case update",
  "timestamp": "2025-01-20T10:00:00Z",
  "message_id": "wamid.xxx"
}
```

**Verification**: 
- Calculate HMAC-SHA256(body, secret)
- Compare with X-Signature header

**Response**: `200 OK`
```json
{
  "success": true,
  "message_id": "local_id_789"
}
```

## SIPP Integration Endpoints

### Get Case from SIPP
```http
GET /lawangsewu/sipp/cases/{case_id}
Authorization: Bearer {access_token}
```

**Response**: `200 OK`
```json
{
  "id": "case_12345",
  "nomor_perkara": "123/PDT/2025/PN.SMG",
  "pihak_penggugat": "PT ABC Corporation",
  "pihak_tergugat": "PT XYZ Limited",
  "perkara": "Perdata - Gugatan",
  "tanggal_pendaftaran": "2025-01-15",
  "hakim": "Dr. Ahmad Suryanto, S.H., M.H.",
  "status": "Penggugat Menjawab",
  "createdAt": "2025-01-15T10:00:00Z"
}
```

**Error Response**: `503 Service Unavailable`
```json
{
  "error": "SIPP database temporarily unavailable",
  "code": "SIPP_UNAVAILABLE",
  "source": "sipp_database",
  "retryable": true,
  "retry_after_seconds": 30,
  "timestamp": "2025-01-20T10:00:00Z"
}
```

## Error Responses

### Standard Error Format
```json
{
  "error": "Error message",
  "code": "ERROR_CODE",
  "timestamp": "2025-01-20T10:00:00Z"
}
```

### Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | OK | Request succeeded |
| 201 | Created | Resource created |
| 206 | Partial Content | Health degraded but working |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Missing/invalid token |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Database constraint violation |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Error | Server error |
| 502 | Bad Gateway | External service error |
| 503 | Unavailable | Service temporarily down |
| 504 | Gateway Timeout | External service timeout |

### Error Codes

| Code | HTTP | Meaning |
|------|------|---------|
| `VALIDATION_FAILED` | 422 | Input validation failed |
| `UNAUTHORIZED` | 401 | Authentication required |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `NOT_FOUND` | 404 | Resource not found |
| `DATABASE_ERROR` | 409 | Database operation conflict |
| `WA_RUNTIME_ERROR` | 502 | WhatsApp service error |
| `SIPP_UNAVAILABLE` | 503 | SIPP database error |
| `REQUEST_TIMEOUT` | 504 | Request timeout |

## Rate Limiting

**Headers**:
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1705762200
```

**Exceeded Response**: `429 Too Many Requests`
```json
{
  "error": "Too many requests",
  "code": "RATE_LIMIT_EXCEEDED",
  "retry_after_seconds": 60,
  "timestamp": "2025-01-20T10:00:00Z"
}
```

## Pagination

All list endpoints support pagination:

**Query Parameters**:
- `page`: Page number (default: 1)
- `per_page`: Records per page (default: 50, max: 250)

**Response Structure**:
```json
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 245,
    "last_page": 5
  }
}
```

## Filtering & Searching

**Query Parameters**:
```http
GET /lawangsewu/chat?conversation_id=456&sort=created_at&order=desc&search=status
```

- `sort`: Field to sort by
- `order`: asc or desc
- `search`: Search term (searches message content)
- `from_date`: ISO 8601 date
- `to_date`: ISO 8601 date

## Timestamps

All timestamps are ISO 8601 format in UTC:
```
2025-01-20T10:30:45.123456Z
```

For local time, convert using timezone from user profile.

## Examples

### cURL: Send Chat Message
```bash
curl -X POST https://lawangsewu.app/api/lawangsewu/chat/messages \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "conversation_id": 456,
    "message": "Case status update"
  }'
```

### JavaScript/Fetch
```javascript
const response = await fetch('https://lawangsewu.app/api/lawangsewu/chat/messages', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    conversation_id: 456,
    message: 'Case status update'
  }),
});

const result = await response.json();
```

### PHP/Guzzle
```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://lawangsewu.app/api',
    'headers' => [
        'Authorization' => "Bearer {$token}",
        'Content-Type' => 'application/json',
    ],
]);

$response = $client->post('lawangsewu/chat/messages', [
    'json' => [
        'conversation_id' => 456,
        'message' => 'Case status update',
    ],
]);

$result = json_decode($response->getBody());
```

## Webhook Events

### Inbound WhatsApp Message
Sent to `WA_WEBHOOK_URL` when message received.

```json
{
  "event": "message.received",
  "phone": "6281234567890",
  "message": "Message text",
  "timestamp": "2025-01-20T10:00:00Z",
  "message_id": "wamid.xxx"
}
```

### Chat Message Created
Broadcast via WebSocket to connected clients.

```json
{
  "event": "message.created",
  "data": {
    "id": 789,
    "conversation_id": 456,
    "message": "Text",
    "created_at": "2025-01-20T10:00:00Z"
  }
}
```

## Testing

Use `.env.testing` credentials and endpoints:

```bash
# Run tests with API
php artisan test tests/Feature/Api/

# Test specific endpoint
php artisan test tests/Feature/Api/ChatMessageTest.php --filter=testSendMessage
```

## Versioning

Current version: **v1**  
Deprecation notice at least 6 months before version change.

## Support

For API issues:
- Check `/health` endpoint status
- Review error response details
- Check X-RateLimit headers
- Consult API documentation at `/api/docs`
