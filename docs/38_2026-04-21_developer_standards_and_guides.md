# /var/www/lawangsewu/docs/09_2026-04-21_developer_standards_and_guides.md

## Isi dari: API.md

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

---

## Isi dari: CONFIGURATION_GUIDE.md

# Configuration Management Guide

## Overview
Complete configuration guide for Lawangsewu application including environment variables, config files, and feature flags.

## Configuration Files

### Core Configuration

#### `config/app.php`
Main application configuration including timezone, locale, and encryption.

```php
'timezone' => 'Asia/Jakarta',
'locale' => 'id',
'debug' => env('APP_DEBUG', false),
```

#### `config/database.php`
Database connections for main app and external sources like SIPP.

```php
'default' => env('DB_CONNECTION', 'mysql'),
'mysql' => [
    'host' => env('DB_HOST', 'localhost'),
    'database' => env('DB_DATABASE', 'lawangsewu'),
],
```

### Module Configuration

#### `config/wa_caraka.php`
WhatsApp messaging system configuration.

```php
'enabled' => env('WA_CARAKA_ENABLED', true),
'base_url' => env('LW_WA_V2_BASE', 'http://127.0.0.1:8790'),
'token' => env('LW_WA_V2_TOKEN', ''),
'timeout' => 20,
'broadcast_limit' => 50,
```

**Environment Variables:**
- `WA_CARAKA_ENABLED` - Enable/disable WA Caraka module
- `LW_WA_V2_BASE` - Base URL of WA runtime server
- `LW_WA_V2_TOKEN` - Authentication token for WA runtime
- `LW_WA_V2_TIMEOUT` - HTTP request timeout in seconds
- `LW_WA_V2_BROADCAST_LIMIT` - Max recipients for broadcast messages

#### `config/sipp.php`
SIPP database integration and cache settings.

```php
'enabled' => env('SIPP_ENABLED', true),
'database' => [
    'host' => env('SIPP_DB_HOST', '192.168.88.10'),
    'database' => env('SIPP_DB_DATABASE', 'sipp'),
],
'cache' => [
    'ttl_minutes' => env('SIPP_CACHE_TTL', 15),
],
```

**Environment Variables:**
- `SIPP_ENABLED` - Enable/disable SIPP integration
- `SIPP_DB_HOST` - SIPP database host
- `SIPP_DB_PORT` - SIPP database port
- `SIPP_DB_DATABASE` - SIPP database name
- `SIPP_DB_USERNAME` - SIPP database username
- `SIPP_DB_PASSWORD` - SIPP database password
- `SIPP_DB_TIMEOUT` - Connection timeout in seconds
- `SIPP_CACHE_TTL` - Cache validity in minutes

#### `config/health_check.php`
Application health monitoring configuration.

```php
'enabled' => env('HEALTH_CHECK_ENABLED', true),
'checks' => [
    'database' => ['enabled' => true],
    'cache' => ['enabled' => true],
    'wa_runtime' => ['enabled' => true, 'optional' => true],
    'sipp_database' => ['enabled' => true, 'optional' => true],
],
```

#### `config/features.php`
Feature flags for enabling/disabling functionality.

```php
'enabled' => [
    'wa_caraka' => env('FEATURE_WA_CARAKA', true),
    'sipp_hub' => env('FEATURE_SIPP_HUB', true),
    'pilar' => env('FEATURE_PILAR', true),
],
```

## Environment Setup

### Development Environment

Create `.env` file from `.env.example`:
```bash
cp .env.example .env
php artisan key:generate
```

Set development-specific values:
```env
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_DATABASE=lawangsewu
DB_USERNAME=root

CACHE_STORE=redis
QUEUE_CONNECTION=database

WA_CARAKA_ENABLED=false  # Disable if no WA runtime
SIPP_ENABLED=false       # Disable if no SIPP server
```

### Testing Environment

Use `.env.testing`:
```bash
# Already configured for testing
APP_ENV=testing
APP_DEBUG=false
DB_DATABASE=lawangsewu_test
CACHE_STORE=array
QUEUE_CONNECTION=sync
```

### Production Environment

Create `.env.production`:
```bash
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning

# Database
DB_CONNECTION=mysql
DB_HOST=db.prod.example.com
DB_DATABASE=lawangsewu_prod

# Cache
CACHE_STORE=redis
REDIS_HOST=redis.prod.example.com

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mail.example.com

# SIPP
SIPP_DB_HOST=sipp-server.internal
```

## Environment Variables Reference

### Application Core
| Variable | Default | Description |
|----------|---------|-------------|
| `APP_NAME` | Lawangsewu V2 | Application name |
| `APP_ENV` | local | Environment (local, testing, production) |
| `APP_DEBUG` | false | Enable debug mode |
| `APP_KEY` | - | Encryption key (generate with artisan) |
| `APP_TIMEZONE` | Asia/Jakarta | Server timezone |
| `APP_LOCALE` | id | Default locale |

### Database
| Variable | Default | Description |
|----------|---------|-------------|
| `DB_CONNECTION` | sqlite | Driver (sqlite, mysql, pgsql) |
| `DB_HOST` | 127.0.0.1 | Database host |
| `DB_PORT` | 3306 | Database port |
| `DB_DATABASE` | lawangsewu | Database name |
| `DB_USERNAME` | root | Database user |
| `DB_PASSWORD` | - | Database password |

### Cache
| Variable | Default | Description |
|----------|---------|-------------|
| `CACHE_DRIVER` | file | Cache driver (redis, file, database) |
| `CACHE_PREFIX` | - | Cache key prefix |
| `REDIS_HOST` | 127.0.0.1 | Redis host |
| `REDIS_PORT` | 6379 | Redis port |

### Mail
| Variable | Default | Description |
|----------|---------|-------------|
| `MAIL_MAILER` | log | Mail driver (smtp, mailgun, log) |
| `MAIL_HOST` | 127.0.0.1 | SMTP host |
| `MAIL_PORT` | 2525 | SMTP port |
| `MAIL_USERNAME` | - | SMTP username |
| `MAIL_PASSWORD` | - | SMTP password |

### WA Caraka
| Variable | Default | Description |
|----------|---------|-------------|
| `WA_CARAKA_ENABLED` | true | Enable WA module |
| `LW_WA_V2_BASE` | http://127.0.0.1:8790 | WA runtime URL |
| `LW_WA_V2_TOKEN` | - | Auth token for WA runtime |
| `LW_WA_V2_TIMEOUT` | 20 | Request timeout (seconds) |
| `LW_WA_V2_BROADCAST_LIMIT` | 50 | Max broadcast recipients |

### SIPP
| Variable | Default | Description |
|----------|---------|-------------|
| `SIPP_ENABLED` | true | Enable SIPP integration |
| `SIPP_DB_HOST` | 192.168.88.10 | SIPP server host |
| `SIPP_DB_PORT` | 3306 | SIPP database port |
| `SIPP_DB_DATABASE` | sipp | SIPP database name |
| `SIPP_DB_USERNAME` | admin | SIPP database user |
| `SIPP_DB_PASSWORD` | - | SIPP database password |
| `SIPP_CACHE_TTL` | 15 | Cache validity (minutes) |

### Logging
| Variable | Default | Description |
|----------|---------|-------------|
| `LOG_CHANNEL` | stack | Log channel (single, daily, stack) |
| `LOG_LEVEL` | debug | Log level (debug, info, warning, error) |

### Features
| Variable | Default | Description |
|----------|---------|-------------|
| `FEATURE_WA_CARAKA` | true | Enable WA Caraka feature |
| `FEATURE_SIPP_HUB` | true | Enable SIPP Hub feature |
| `FEATURE_PILAR` | true | Enable Pilar Antrian feature |

## Configuration Validation

Validate environment configuration:
```bash
php artisan config:cache
php artisan config:clear
```

Check configuration values:
```bash
php artisan tinker
> config('wa_caraka.base_url')
> config('sipp.database.host')
```

## Secrets Management

### Local Development
Store secrets in `.env` (never commit):
```env
DB_PASSWORD=your-secret-password
LW_WA_V2_TOKEN=your-token
SIPP_DB_PASSWORD=your-sipp-password
```

### CI/CD (GitHub Actions)
Use repository secrets:
```yaml
- name: Run tests
  env:
    DB_PASSWORD: ${{ secrets.TESTING_DB_PASSWORD }}
    SIPP_DB_PASSWORD: ${{ secrets.SIPP_PASSWORD }}
```

### Docker
Use compose environment files:
```yaml
# docker-compose.yml
services:
  app:
    environment:
      - DB_PASSWORD=${DB_PASSWORD}
      - SIPP_DB_PASSWORD=${SIPP_DB_PASSWORD}
```

### Cloud Deployment
Use managed secrets services:
- **AWS**: Secrets Manager, Systems Manager Parameter Store
- **GCP**: Secret Manager
- **Azure**: Key Vault
- **Kubernetes**: Secrets

Example with Kubernetes:
```yaml
apiVersion: v1
kind: Secret
metadata:
  name: lawangsewu-secrets
type: Opaque
stringData:
  DB_PASSWORD: your-password
  SIPP_DB_PASSWORD: your-sipp-password
```

## Configuration Caching

For production, cache configuration:
```bash
php artisan config:cache
```

This reads all config files and creates a single cached file. Clear cache when updating:
```bash
php artisan config:clear
php artisan config:cache
```

## Dynamic Configuration

Access configuration in code:
```php
// Use config() helper
$waBaseUrl = config('wa_caraka.base_url');
$sippCache = config('sipp.cache.ttl_minutes');
$featureEnabled = config('features.enabled.wa_caraka');

// With defaults
$timeout = config('wa_caraka.timeout', 20);

// In service classes
public function __construct() {
    $this->baseUrl = config('wa_caraka.base_url');
    $this->token = config('wa_caraka.token');
}
```

## Best Practices

1. **Never Commit Secrets**
   - Use `.env` and `.env.*.local` (gitignored)
   - Keep `.env.example` as template

2. **Document Environment Variables**
   - Update `.env.example` when adding variables
   - Include comments in examples

3. **Use Defaults Wisely**
   - Provide sensible defaults in config files
   - Override in `.env` as needed

4. **Environment-Specific Config**
   - Use `.env.testing` for tests
   - Use `.env.production` for prod
   - Use `.env.local` for local overrides

5. **Validation**
   - Validate required config at boot
   - Provide clear error messages

6. **Cache Configuration**
   - Cache in production for performance
   - Clear cache when deploying changes

## Troubleshooting

### Config Not Updating
```bash
# Clear cache and rebuild
php artisan config:clear
php artisan config:cache
```

### Environment Variables Not Reading
```bash
# Check .env file exists and is readable
ls -la .env

# Verify variable format
cat .env | grep VARIABLE_NAME

# Test with tinker
php artisan tinker
> env('VARIABLE_NAME')
```

### SIPP Connection Fails
Check configuration:
```bash
php artisan tinker
> config('sipp.database')
> DB::connection('sipp')->getPdo()
```

## References
- `/config/` - Configuration files
- `.env.example` - Environment template
- `.env.testing` - Test configuration
- `docs/ENVIRONMENT_VARIABLES.md` - Detailed variable reference

---

## Isi dari: DATABASE.md

# Database Documentation

## Overview

Lawangsewu uses MySQL 8.0 for persistent storage with Redis for caching and session management.

**Primary Database**: lawangsewu (MySQL)  
**Test Database**: lawangsewu_testing  
**External Database**: SIPP (Legacy court management system)

## Connection Configuration

### Laravel Connections
```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', 3306),
    'database' => env('DB_DATABASE', 'lawangsewu'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
],

'sipp' => [
    'driver' => 'mysql',
    'host' => env('SIPP_DB_HOST'),
    'port' => env('SIPP_DB_PORT', 3306),
    'database' => env('SIPP_DB_NAME'),
    'username' => env('SIPP_DB_USERNAME'),
    'password' => env('SIPP_DB_PASSWORD'),
    'readonly' => true,
],
```

## Schema

### Users Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    avatar_url VARCHAR(255),
    role ENUM('viewer','operator','useradmin','admin') DEFAULT 'viewer',
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    last_login_at TIMESTAMP,
    email_verified_at TIMESTAMP,
    two_factor_secret VARCHAR(255),
    google_id VARCHAR(255) UNIQUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);
```

**Relationships**:
- hasMany: ChatMessage
- hasMany: Conversation
- hasMany: AuditLog

**Eloquent Model**: `App\Models\User`

---

### Conversations Table
```sql
CREATE TABLE conversations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    case_id VARCHAR(50),
    nomor_perkara VARCHAR(50) UNIQUE,
    pihak_penggugat VARCHAR(255),
    pihak_tergugat VARCHAR(255),
    hakim_id VARCHAR(50),
    status ENUM('open','closed','archived') DEFAULT 'open',
    started_at TIMESTAMP,
    closed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_case_id (case_id),
    INDEX idx_nomor_perkara (nomor_perkara),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

**Relationships**:
- hasMany: ChatMessage
- belongsTo: SippCase (via case_id, SIPP database)

**Eloquent Model**: `App\Models\Conversation`

---

### Chat Messages Table
```sql
CREATE TABLE chat_messages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    conversation_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    message LONGTEXT NOT NULL,
    type ENUM('message','note','system') DEFAULT 'message',
    phone VARCHAR(20),
    sent_via_wa BOOLEAN DEFAULT FALSE,
    wa_status ENUM('pending','sent','delivered','read','failed'),
    wa_message_id VARCHAR(255),
    mention_ids JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_wa_status (wa_status),
    FULLTEXT INDEX ft_message (message)
);
```

**Relationships**:
- belongsTo: Conversation
- belongsTo: User

**Eloquent Model**: `App\Models\ChatMessage`

**Full-text Search**:
```php
// Search in message content
ChatMessage::whereRaw('MATCH(message) AGAINST(? IN BOOLEAN MODE)', [$search])
    ->get();
```

---

### Audit Logs Table
```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    model_type VARCHAR(255),
    model_id BIGINT UNSIGNED,
    changes JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);
```

**Purpose**: Track user actions for security audit.

**Example Entry**:
```json
{
    "user_id": 123,
    "action": "message_sent",
    "model_type": "ChatMessage",
    "model_id": 456,
    "changes": {
        "message": "...",
        "phone": "6281234567890"
    },
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0..."
}
```

---

## SIPP Integration

### SIPP Database (Read-Only)

Connected via separate connection (`'sipp'` in config/database.php).

**Key Tables** (External):
- `perkara` - Cases
- `pihak` - Parties (plaintiff/defendant)
- `hakim` - Judges
- `putusan` - Verdicts
- `dokumen` - Documents

### Sync Strategy

```php
// app/Models/SippCase.php
class SippCase extends Model {
    protected $connection = 'sipp';
    protected $table = 'perkara';
    public $timestamps = false;

    // Relationship to local Conversation
    public function conversation() {
        return Conversation::where('case_id', $this->id)->first();
    }
}
```

**Sync Job**:
```php
// app/Jobs/SyncSippCases.php
class SyncSippCases implements ShouldQueue {
    public function handle() {
        // Fetch from SIPP, create/update local Conversations
        $cases = SippCase::where('updated_at', '>', $lastSync)->get();
        
        foreach ($cases as $case) {
            Conversation::updateOrCreate(
                ['case_id' => $case->id],
                ['nomor_perkara' => $case->nomor_perkara, ...]
            );
        }
    }
}
```

## Migrations

### Creating Tables
```bash
php artisan make:migration create_users_table
php artisan make:migration create_conversations_table
```

### Running Migrations
```bash
# All migrations
php artisan migrate

# Specific database
php artisan migrate --database=mysql

# Rollback last batch
php artisan migrate:rollback

# Rollback all
php artisan migrate:reset

# Fresh install
php artisan migrate:fresh --seed
```

### Migration Example
```php
// database/migrations/2025_01_01_000000_create_chat_messages_table.php
Schema::create('chat_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conversation_id')->constrained();
    $table->foreignId('user_id')->constrained();
    $table->longText('message');
    $table->enum('type', ['message', 'note', 'system'])->default('message');
    $table->string('phone')->nullable();
    $table->boolean('sent_via_wa')->default(false);
    $table->enum('wa_status', ['pending', 'sent', 'delivered', 'read', 'failed'])->nullable();
    $table->timestamps();
    $table->fullText(['message']);
});
```

## Querying

### Eloquent Examples

**Basic Queries**:
```php
// Get user
$user = User::find(1);
$user = User::where('email', 'user@example.com')->first();

// Get all active users
$users = User::where('status', 'active')->get();

// Get with relationship
$conversations = Conversation::with('chatMessages')->get();
```

**Filtering**:
```php
// By role
User::where('role', 'operator')->get();

// By status
Conversation::where('status', '!=', 'archived')->get();

// Date range
ChatMessage::whereBetween('created_at', [$from, $to])->get();

// In list
User::whereIn('role', ['operator', 'admin'])->get();
```

**Searching**:
```php
// Full-text search
ChatMessage::whereRaw('MATCH(message) AGAINST(? IN BOOLEAN MODE)', ['search_term'])
    ->get();

// Like search
ChatMessage::where('message', 'LIKE', '%search%')->get();
```

**Aggregation**:
```php
// Count
$count = ChatMessage::count();
$count = ChatMessage::where('sent_via_wa', true)->count();

// Sum
$total = ChatMessage::sum('id');

// Group by
ChatMessage::selectRaw('DATE(created_at) as date, COUNT(*) as count')
    ->groupBy('date')
    ->get();
```

**Pagination**:
```php
$messages = ChatMessage::paginate(50);
$messages = ChatMessage::paginate(50, ['*'], 'page', 2);
```

## Optimization

### Indexes
```sql
-- Add index for common queries
ALTER TABLE chat_messages ADD INDEX idx_conversation_user (conversation_id, user_id);
ALTER TABLE conversations ADD INDEX idx_status_created (status, created_at);
```

### Query Optimization
```php
// Use select() to only get needed columns
User::select('id', 'name', 'email')->get();

// Eager load relationships
Conversation::with('chatMessages', 'chatMessages.user')->get();

// Avoid N+1 queries
// ❌ Bad
foreach ($conversations as $conv) {
    $messages = $conv->chatMessages; // Query per iteration
}

// ✅ Good
$conversations = Conversation::with('chatMessages')->get();
```

### Query Debugging
```php
// Enable query logging
DB::enableQueryLog();

// Run query
ChatMessage::all();

// View queries
$queries = DB::getQueryLog();
dd($queries);
```

## Transactions

```php
// Basic transaction
DB::transaction(function () {
    $conversation = Conversation::create([...]);
    ChatMessage::create(['conversation_id' => $conversation->id, ...]);
});

// With rollback
try {
    DB::beginTransaction();
    // ... operations
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    throw $e;
}
```

## Backup & Recovery

### Backup
```bash
# Mysqldump
mysqldump -u username -p lawangsewu > backup.sql

# Laravel artisan (with package)
php artisan backup:run
```

### Restore
```bash
mysql -u username -p lawangsewu < backup.sql
```

## Performance Monitoring

### Slow Query Log
```bash
# Enable slow query log
mysql> SET GLOBAL slow_query_log = 'ON';
mysql> SET GLOBAL long_query_time = 2;

# View slow queries
tail -f /var/log/mysql/slow.log
```

### Analyze Queries
```php
// In AppServiceProvider
DB::listen(function ($query) {
    if ($query->time > 1000) {
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'time_ms' => $query->time,
            'bindings' => $query->bindings,
        ]);
    }
});
```

## Testing

### Test Database
```bash
# Create test database
mysql> CREATE DATABASE lawangsewu_testing;

# Run migrations
php artisan migrate --env=testing

# Run tests
php artisan test

# Test with seeding
php artisan test --seed
```

### Seeding
```php
// database/seeders/DatabaseSeeder.php
public function run() {
    User::factory(10)->create();
    Conversation::factory(5)->create();
    ChatMessage::factory(100)->create();
}

// Run seeder
php artisan db:seed
php artisan db:seed --class=UserSeeder
```

## Files Reference

- `config/database.php` - Database configuration
- `database/migrations/` - Schema migrations
- `database/seeders/` - Test data seeders
- `database/factories/` - Model factories
- `app/Models/` - Eloquent models

## Environment Variables

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=lawangsewu
DB_USERNAME=root
DB_PASSWORD=

SIPP_DB_HOST=192.168.88.10
SIPP_DB_PORT=3306
SIPP_DB_NAME=sipp_prod
SIPP_DB_USERNAME=sipp_user
SIPP_DB_PASSWORD=

# Connection pool (for production)
DB_POOL_MIN=5
DB_POOL_MAX=20
```

## Common Issues

### Connection Errors
```
SQLSTATE[HY000] [1045] Access denied
→ Check username/password in config/database.php

SQLSTATE[HY000] [2002] No such file or directory
→ Check DB_HOST and DB_PORT, ensure MySQL running
```

### Integrity Constraint Violation
```
SQLSTATE[23000]: Integrity constraint violation
→ Check foreign key constraints, cascade delete options
```

### Slow Queries
→ Add indexes to frequently searched columns
→ Use eager loading for relationships
→ Check query analysis with EXPLAIN

## Best Practices

- [ ] Always use migrations for schema changes
- [ ] Write indexes for foreign keys
- [ ] Use full-text indexes for search
- [ ] Eager load relationships
- [ ] Avoid SELECT * queries
- [ ] Use transactions for multi-step operations
- [ ] Log slow queries in production
- [ ] Regular backups (daily minimum)
- [ ] Monitor disk space
- [ ] Use connection pooling in production
- [ ] Test migrations before production deploy
- [ ] Document custom table structures

---

## Isi dari: DATABASE_SEEDERS_GUIDE.md

# Database Seeders Guide

## Overview

Comprehensive database seeding system for development, testing, and demonstration.

## Base Seeder

Create base seeder with shared logic:

```php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run() {
        // Seed in order of dependencies
        $this->call([
            UserSeeder::class,
            ConversationSeeder::class,
            ChatMessageSeeder::class,
        ]);
    }
}
```

## User Seeder

```php
// database/seeders/UserSeeder.php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder {
    public function run() {
        // Create admin
        User::create([
            'email' => 'admin@example.com',
            'name' => 'Administrator',
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create operators
        User::factory()
            ->count(5)
            ->state([
                'role' => 'operator',
                'status' => 'active',
            ])
            ->create();

        // Create regular users
        User::factory()
            ->count(20)
            ->state([
                'role' => 'viewer',
                'status' => 'active',
            ])
            ->create();

        // Create test user for development
        if (app()->environment('local')) {
            User::create([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'role' => 'viewer',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }
    }
}
```

## Conversation Seeder

```php
// database/seeders/ConversationSeeder.php
namespace Database\Seeders;

use App\Models\Conversation;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder {
    public function run() {
        // Create conversations with various statuses
        Conversation::factory()
            ->count(10)
            ->state(['status' => 'open'])
            ->create();

        Conversation::factory()
            ->count(5)
            ->state(['status' => 'closed'])
            ->create();

        Conversation::factory()
            ->count(3)
            ->state(['status' => 'archived'])
            ->create();
    }
}
```

## Chat Message Seeder

```php
// database/seeders/ChatMessageSeeder.php
namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatMessageSeeder extends Seeder {
    public function run() {
        $conversations = Conversation::all();
        $users = User::all();

        foreach ($conversations as $conversation) {
            // Create 10-50 messages per conversation
            ChatMessage::factory()
                ->count(rand(10, 50))
                ->state([
                    'conversation_id' => $conversation->id,
                    'user_id' => $users->random()->id,
                ])
                ->create();

            // Add some WA messages
            ChatMessage::factory()
                ->count(rand(0, 5))
                ->state([
                    'conversation_id' => $conversation->id,
                    'user_id' => $users->random()->id,
                    'sent_via_wa' => true,
                    'phone' => fake()->phoneNumber(),
                    'wa_status' => 'delivered',
                ])
                ->create();
        }
    }
}
```

## Factory Classes

Create factories for realistic data:

```php
// database/factories/UserFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory {
    public function definition() {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'avatar_url' => fake()->imageUrl(),
            'role' => 'viewer',
            'status' => 'active',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin() {
        return $this->state(['role' => 'admin']);
    }

    public function operator() {
        return $this->state(['role' => 'operator']);
    }

    public function inactive() {
        return $this->state(['status' => 'inactive']);
    }
}
```

```php
// database/factories/ConversationFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory {
    public function definition() {
        return [
            'case_id' => 'CASE-' . fake()->numerify('######'),
            'nomor_perkara' => fake()->numerify('###/PDT/####/PN.SMG'),
            'pihak_penggugat' => fake()->company(),
            'pihak_tergugat' => fake()->company(),
            'hakim_id' => fake()->numerify('####'),
            'status' => 'open',
            'started_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function closed() {
        return $this->state([
            'status' => 'closed',
            'closed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function archived() {
        return $this->state([
            'status' => 'archived',
            'closed_at' => fake()->dateTimeBetween('-1 year', '-6 months'),
        ]);
    }
}
```

```php
// database/factories/ChatMessageFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory {
    public function definition() {
        return [
            'message' => fake()->paragraph(),
            'type' => 'message',
            'sent_via_wa' => false,
            'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function note() {
        return $this->state(['type' => 'note']);
    }

    public function system() {
        return $this->state(['type' => 'system']);
    }

    public function waMessage() {
        return $this->state([
            'sent_via_wa' => true,
            'phone' => fake()->numerify('62812345####'),
            'wa_status' => 'delivered',
            'wa_message_id' => fake()->uuid(),
        ]);
    }
}
```

## Running Seeders

### Command Line
```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=UserSeeder

# Fresh install with seeding
php artisan migrate:fresh --seed

# Fresh with specific seeders
php artisan migrate:fresh --seeder=DatabaseSeeder
```

### Programmatically
```php
// In console command or test
use Illuminate\Database\Seeder;

$seeder = new UserSeeder();
$seeder->run();
```

## Development Seeder

Create lightweight seeder for development:

```php
// database/seeders/DevelopmentSeeder.php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder {
    public function run() {
        if (!app()->environment('local')) {
            return;
        }

        // Create minimal test data
        User::factory(3)->create([
            'role' => 'admin',
        ]);

        $this->call([
            ConversationSeeder::class,
            ChatMessageSeeder::class,
        ]);
    }
}
```

## Testing Seeder

```php
// database/seeders/TestingSeeder.php
namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestingSeeder extends Seeder {
    public function run() {
        // Create exactly what tests expect
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        $operator = User::factory()->create([
            'role' => 'operator',
            'email' => 'operator@test.com',
        ]);

        $conversation = Conversation::factory()->create([
            'case_id' => 'TEST-001',
        ]);

        ChatMessage::factory(5)->create([
            'conversation_id' => $conversation->id,
            'user_id' => $admin->id,
        ]);
    }
}
```

Register in `phpunit.xml`:
```xml
<php>
    <env name="DB_CONNECTION" value="testing"/>
    <env name="DB_DATABASE" value="lawangsewu_testing"/>
    <env name="SEED_DATABASE" value="TestingSeeder"/>
</php>
```

## Conditional Seeding

```php
// database/seeders/DatabaseSeeder.php
public function run() {
    if (app()->environment('testing')) {
        $this->call(TestingSeeder::class);
    } elseif (app()->environment('local')) {
        $this->call(DevelopmentSeeder::class);
    } else {
        $this->call(ProductionSeeder::class);
    }
}
```

## State Management

Use factory states for different scenarios:

```php
// In tests
test('can update user role', function () {
    $user = User::factory()->operator()->create();
    
    $this->actingAs($user)
        ->patch("/users/{$user->id}/role", ['role' => 'admin'])
        ->assertOk();
});

// Multiple states
Conversation::factory()
    ->closed()
    ->count(5)
    ->create();
```

## Mass Seeding

For performance testing:

```php
// database/seeders/MassDataSeeder.php
namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ChatMessage;
use Illuminate\Database\Seeder;

class MassDataSeeder extends Seeder {
    public function run() {
        // Create large datasets
        Conversation::factory(1000)->create();

        Conversation::all()->each(function ($conversation) {
            ChatMessage::factory(100)->create([
                'conversation_id' => $conversation->id,
            ]);
        });
    }
}
```

Run with progress:
```bash
php artisan db:seed --class=MassDataSeeder --verbose
```

## Clear Seeders

Create command to clear specific data:

```php
// app/Console/Commands/ClearSeeds.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearSeeds extends Command {
    protected $signature = 'db:clear-seeds {--all}';

    public function handle() {
        if ($this->option('all')) {
            DB::table('chat_messages')->truncate();
            DB::table('conversations')->truncate();
            DB::table('users')->truncate();
        } else {
            // Clear test data only
            ChatMessage::where('created_at', '>', now()->subHour())
                ->delete();
        }

        $this->info('Seeds cleared successfully');
    }
}
```

Usage:
```bash
php artisan db:clear-seeds
php artisan db:clear-seeds --all
```

## Best Practices

- [ ] Create factories for each model
- [ ] Use realistic fake data
- [ ] Seed in dependency order
- [ ] Create environment-specific seeders
- [ ] Use factory states for variations
- [ ] Create test data fixtures
- [ ] Document expected data
- [ ] Keep seeders fast for development
- [ ] Use transactions for atomic seeding
- [ ] Seed only in development/testing
- [ ] Version control seed definitions
- [ ] Create fresh data regularly
- [ ] Clear old seed data
- [ ] Test seeding in CI/CD
- [ ] Document seeding process

## Files Reference

- `database/seeders/DatabaseSeeder.php` - Main seeder
- `database/factories/` - Model factories
- `database/seeders/*Seeder.php` - Specific seeders
- `phpunit.xml` - Test environment configuration

---

## Isi dari: ERROR_HANDLING_GUIDE.md

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

---

## Isi dari: FEATURE_FLAGS_GUIDE.md

# Feature Flags Guide

## Overview
Feature flags enable safe, controlled rollout of features without redeployment.

## Configuration

### Base Configuration
File: `config/features.php`

```php
return [
    'wa_caraka' => env('FEATURE_WA_CARAKA', true),
    'sipp_hub' => env('FEATURE_SIPP_HUB', true),
    'inbox_optimization' => env('FEATURE_INBOX_OPTIMIZATION', true),
    'advanced_search' => env('FEATURE_ADVANCED_SEARCH', true),
];
```

## Usage Examples

### 1. Service Level
```php
class ChatMessageService {
    public function sendMessage($message) {
        if (!config('features.wa_caraka')) {
            throw new FeatureDisabledException('WA Caraka not enabled');
        }
        
        return $this->waCarakaService->send($message);
    }
}
```

### 2. Controller Level
```php
class ChatController {
    public function store(StoreChatMessageRequest $request) {
        if (config('features.wa_caraka')) {
            $this->sendViaWaCaraka($request->message);
        } else {
            $this->sendViaDatabaseOnly($request->message);
        }
    }
}
```

### 3. Blade Template
```blade
@if(config('features.advanced_search'))
    <div class="search-panel">
        <!-- Advanced search UI -->
    </div>
@endif
```

### 4. Route Level
```php
Route::middleware(['feature:wa_caraka'])->group(function () {
    Route::post('/messages/wa', [ChatController::class, 'sendViaWa']);
});
```

## Feature Middleware

Create custom middleware:

```php
// app/Http/Middleware/CheckFeature.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckFeature {
    public function handle(Request $request, Closure $next, string $feature) {
        if (!config("features.{$feature}")) {
            return response()->json([
                'error' => 'Feature not available',
                'feature' => $feature,
            ], 503);
        }

        return $next($request);
    }
}
```

Register in `bootstrap/app.php`:
```php
$app->make(Kernel::class)->pushMiddleware(\App\Http\Middleware\CheckFeature::class);
```

## Progressive Rollout Pattern

### Strategy 1: User Percentage
```php
class FeatureFlagService {
    public function isEnabledForUser($userId, $feature, $percentage = 50) {
        $hash = crc32($userId . $feature) % 100;
        return $hash < $percentage;
    }
}

// Usage
if (app(FeatureFlagService::class)->isEnabledForUser(auth()->id(), 'advanced_search', 10)) {
    // Show to 10% of users
}
```

### Strategy 2: User List
```php
class FeatureFlagService {
    public function isEnabledForUser($userId, $feature) {
        $whitelist = config("features.whitelist.{$feature}", []);
        return in_array($userId, $whitelist);
    }
}

// config/features.php
'whitelist' => [
    'advanced_search' => [1, 2, 5, 10],
    'sipp_hub' => [1, 3, 4, 100],
],
```

### Strategy 3: User Role
```php
if (auth()->user()->hasRole('admin')) {
    // Always show to admins
    $enableFeature = true;
} elseif (config('features.advanced_search')) {
    // Show to others based on flag
    $enableFeature = true;
} else {
    $enableFeature = false;
}
```

## Advanced Implementation

### Feature Manager Class
```php
namespace App\Support;

class FeatureFlagManager {
    private $features;
    private $userWhitelist = [];

    public function __construct() {
        $this->features = config('features', []);
    }

    public function isEnabled($feature, $userId = null) {
        // Check if globally enabled
        if (!($this->features[$feature] ?? false)) {
            return false;
        }

        // Check user whitelist if userId provided
        if ($userId && isset($this->userWhitelist[$feature])) {
            return in_array($userId, $this->userWhitelist[$feature]);
        }

        return true;
    }

    public function withWhitelist($feature, $userIds) {
        $this->userWhitelist[$feature] = $userIds;
        return $this;
    }

    public function getAllEnabled() {
        return array_filter($this->features);
    }

    public function getStatus() {
        return [
            'features' => $this->features,
            'enabled_count' => count(array_filter($this->features)),
            'total_count' => count($this->features),
        ];
    }
}
```

### Usage in Service Provider
```php
class AppServiceProvider extends ServiceProvider {
    public function register() {
        $this->app->singleton(FeatureFlagManager::class);
    }

    public function boot() {
        // Load user-specific whitelists
        $manager = app(FeatureFlagManager::class);
        $manager->withWhitelist('advanced_search', [1, 2, 5]);
    }
}
```

## Monitoring & Logging

```php
class FeatureFlagMiddleware {
    public function handle($request, Closure $next) {
        $enabled = [
            'wa_caraka' => config('features.wa_caraka'),
            'sipp_hub' => config('features.sipp_hub'),
            'advanced_search' => config('features.advanced_search'),
        ];

        Log::debug('Feature flags', $enabled);
        
        return $next($request);
    }
}
```

### Track Feature Usage
```php
class FeatureUsageLogger {
    public static function log($feature, $userId = null, $action = 'used') {
        Log::info('Feature usage', [
            'feature' => $feature,
            'user_id' => $userId,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

// In controller
FeatureUsageLogger::log('advanced_search', auth()->id(), 'search_executed');
```

## Testing with Feature Flags

### Unit Tests
```php
class SearchControllerTest extends TestCase {
    public function test_advanced_search_only_when_enabled() {
        // Feature disabled
        config(['features.advanced_search' => false]);
        $response = $this->get('/api/search/advanced');
        $response->assertStatus(503);

        // Feature enabled
        config(['features.advanced_search' => true]);
        $response = $this->get('/api/search/advanced');
        $response->assertStatus(200);
    }
}
```

### Feature Tests
```php
class FeatureFlagTest extends TestCase {
    public function test_feature_enabled_for_whitelisted_users() {
        $user = User::factory()->create(['id' => 1]);
        $this->actingAs($user);

        config(['features.advanced_search' => true]);
        config(['features.whitelist.advanced_search' => [1]]);

        $this->assertTrue(app(FeatureFlagManager::class)->isEnabled('advanced_search', 1));
    }

    public function test_feature_disabled_for_non_whitelisted_users() {
        $user = User::factory()->create(['id' => 999]);
        
        config(['features.whitelist.advanced_search' => [1, 2]]);

        $this->assertFalse(app(FeatureFlagManager::class)->isEnabled('advanced_search', 999));
    }
}
```

## Deployment Strategy

### Zero-Downtime Feature Rollout
```
1. Deploy code with feature flag disabled
   FEATURE_NEW_FEATURE=false

2. Verify on staging
   - All new code paths are correct
   - No errors in logs
   - Database migrations work

3. Enable for 10% of users
   FEATURE_NEW_FEATURE=true (with 10% rollout)

4. Monitor:
   - Error rates
   - Performance metrics
   - User feedback

5. Increase to 50% if no issues
6. Increase to 100%
7. Remove feature flag code after stable
```

## Environment Configuration

### Development
```env
FEATURE_WA_CARAKA=true
FEATURE_SIPP_HUB=true
FEATURE_ADVANCED_SEARCH=true
FEATURE_INBOX_OPTIMIZATION=true
```

### Staging
```env
FEATURE_WA_CARAKA=true
FEATURE_SIPP_HUB=true
FEATURE_ADVANCED_SEARCH=false
FEATURE_INBOX_OPTIMIZATION=false
```

### Production
```env
FEATURE_WA_CARAKA=true
FEATURE_SIPP_HUB=true
FEATURE_ADVANCED_SEARCH=false
FEATURE_INBOX_OPTIMIZATION=false
```

## Health Check Integration

```php
class HealthCheckService {
    public function featureFlagsStatus() {
        return [
            'wa_caraka' => config('features.wa_caraka'),
            'sipp_hub' => config('features.sipp_hub'),
            'advanced_search' => config('features.advanced_search'),
            'inbox_optimization' => config('features.inbox_optimization'),
        ];
    }
}

// Endpoint
GET /api/health → includes feature flags in response
```

## Best Practices

- [ ] Keep feature flags simple (boolean or percentage)
- [ ] Document why each flag exists
- [ ] Add expiration date for temporary flags
- [ ] Remove flags when feature is stable
- [ ] Monitor feature usage
- [ ] Test with flag enabled and disabled
- [ ] Use consistent naming (FEATURE_*)
- [ ] Log feature flag decisions
- [ ] Version control flag changes
- [ ] Include in CI/CD pipeline
- [ ] Set sensible defaults
- [ ] Document rollback strategy
- [ ] Communicate flag changes to team

## Files Reference

- `config/features.php` - Feature flag configuration
- `app/Support/FeatureFlagManager.php` - Feature flag management
- `routes/api.php` - Include feature flag routes
- Tests should verify both enabled and disabled paths

---

## Isi dari: INPUT_VALIDATION_GUIDE.md

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

---

## Isi dari: LOGGING_GUIDE.md

# Structured Logging Guide

## Overview
Comprehensive structured logging system for consistent, queryable log entries across the application.

## Logging Configuration

### Log Channels

Configured in `config/logging.php`:

```php
'channels' => [
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'days' => 14,
    ],
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
    ],
    'wa_caraka' => [
        'driver' => 'single',
        'path' => storage_path('logs/wa_caraka.log'),
        'level' => 'debug',
    ],
    'sipp' => [
        'driver' => 'single',
        'path' => storage_path('logs/sipp.log'),
        'level' => 'info',
    ],
    'performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/performance.log'),
        'days' => 7,
    ],
]
```

## Logging Best Practices

### 1. Use Structured Context

Instead of string concatenation:
```php
// ❌ Bad
Log::info('User ' . $user->id . ' created at ' . now());

// ✅ Good
Log::info('User created', [
    'user_id' => $user->id,
    'email' => $user->email,
    'timestamp' => now()->toIso8601String(),
]);
```

### 2. Consistent Log Format

All logs should include:
```php
Log::info('Action performed', [
    'action' => 'message_sent',
    'user_id' => auth()->id(),
    'message_id' => $message->id,
    'conversation_id' => $message->conversation_id,
    'recipient_count' => count($recipients),
    'duration_ms' => $stopwatch->stop(),
    'status' => 'success',
    'timestamp' => now()->toIso8601String(),
]);
```

### 3. Log Levels

Use appropriate levels:

```php
// DEBUG - Detailed development info
Log::debug('Variable value', ['variable' => $value]);

// INFO - General informational
Log::info('User logged in', ['user_id' => $user->id]);

// NOTICE - Normal but significant
Log::notice('Configuration changed', ['setting' => 'timeout']);

// WARNING - Potentially harmful situation
Log::warning('Slow query detected', ['query' => $query, 'time_ms' => 1200]);

// ERROR - Error condition but still running
Log::error('Database query failed', ['error' => $e->getMessage()]);

// CRITICAL - Critical condition
Log::critical('Disk space critical', ['free_bytes' => 100000000]);

// ALERT - Action must be taken
Log::alert('Security breach detected', ['ip' => $ip]);

// EMERGENCY - System unusable
Log::emergency('Database offline', ['error' => $e->getMessage()]);
```

### 4. Exception Logging

Log exceptions with full context:

```php
try {
    $data = $service->fetchData();
} catch (WaRuntimeException $e) {
    Log::warning('WA runtime error', [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'detail' => $e->getDetail(),
        'is_retryable' => $e->isRetryable(),
        'exception_class' => get_class($e),
        'stack_trace' => $e->getTraceAsString(),
    ]);
} catch (Exception $e) {
    Log::error('Unexpected error', [
        'error' => $e->getMessage(),
        'exception' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}
```

## Service Logging Examples

### WA Caraka Service
```php
class WaCarakaService {
    public function sendMessage($phone, $message) {
        Log::info('Sending WA message', [
            'phone' => $this->maskPhone($phone),
            'message_length' => strlen($message),
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            $response = $this->post('/send', [
                'to' => $phone,
                'message' => $message,
            ]);
            
            if ($response['ok']) {
                Log::info('WA message sent', [
                    'phone' => $this->maskPhone($phone),
                    'message_id' => $response['message_id'],
                    'duration_ms' => $response['duration'],
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to send WA message', [
                'phone' => $this->maskPhone($phone),
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);
            throw $e;
        }
    }
}
```

### Database Operations
```php
class UserRepository {
    public function create(array $data) {
        Log::debug('Creating user', ['email' => $data['email']]);

        try {
            $user = User::create($data);
            
            Log::info('User created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]);
            
            return $user;
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') { // Unique constraint
                Log::notice('Duplicate user email', [
                    'email' => $data['email'],
                ]);
            } else {
                Log::error('Failed to create user', [
                    'email' => $data['email'],
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                ]);
            }
            throw $e;
        }
    }
}
```

### API Requests
```php
class ChatController {
    public function storeMessage(StoreChatMessageRequest $request) {
        Log::debug('Processing chat message', [
            'conversation_id' => $request->conversation_id,
            'message_length' => strlen($request->message),
            'user_id' => auth()->id(),
        ]);

        try {
            $message = ChatMessage::create($request->validated());
            
            Log::info('Chat message stored', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'user_id' => $message->user_id,
                'processed_time_ms' => round((microtime(true) - $start) * 1000),
            ]);
            
            broadcast(new ChatMessageSent($message));
            
            return response()->json(['success' => true, 'message_id' => $message->id]);
        } catch (Exception $e) {
            Log::error('Failed to store chat message', [
                'conversation_id' => $request->conversation_id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

## Log Analysis

### Viewing Logs
```bash
# View live logs
tail -f storage/logs/laravel.log

# Watch specific channel
tail -f storage/logs/wa_caraka.log

# Search logs
grep "error" storage/logs/laravel.log
grep "user_id: 123" storage/logs/laravel.log

# Count occurrences
grep -c "message sent" storage/logs/laravel.log

# Show context around match
grep -A 5 -B 5 "error" storage/logs/laravel.log
```

### Using Laravel Pail (Real-time)
```bash
# View all logs in real-time
php artisan pail

# Filter by level
php artisan pail --level=error

# Filter by type
php artisan pail --message="user"

# Follow specific channel
php artisan pail --channel=wa_caraka
```

### Log File Size Management
```php
// In config/logging.php
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'days' => 14,  // Keep 14 days
],
```

Archive old logs:
```bash
# Compress logs older than 30 days
find storage/logs -name "*.log" -mtime +30 -exec gzip {} \;

# Delete logs older than 60 days
find storage/logs -name "*.log" -mtime +60 -delete
```

## Sensitive Data

### Masking Sensitive Information
```php
private function maskPhone($phone) {
    return substr($phone, 0, 4) . '****' . substr($phone, -4);
}

private function maskEmail($email) {
    $parts = explode('@', $email);
    return substr($parts[0], 0, 2) . '*****@' . $parts[1];
}

Log::info('User action', [
    'user_email' => $this->maskEmail($user->email),
    'phone' => $this->maskPhone($user->phone),
]);
```

### What NOT to Log
- Passwords
- API keys and tokens
- Full credit card numbers
- Social security numbers
- Personal identification numbers
- Full phone numbers (mask them)
- Full email addresses (optional: mask)
- Full IP addresses in some contexts
- Browser cookies
- Session tokens

## Monitoring & Alerting

### Example: Alert on Errors
```php
// In exception handler
if ($e instanceof WaRuntimeException) {
    Log::error('WA Runtime Error', [
        'error' => $e->getMessage(),
        'status' => $e->getStatusCode(),
    ]);
    
    // Send alert if critical
    if ($e->getStatusCode() >= 500) {
        Notification::route('slack', config('services.slack.alert_webhook'))
            ->notify(new CriticalErrorNotification($e));
    }
}
```

## Performance Logging

```php
class PerformanceLogger {
    public static function logSlowQuery($query, $time) {
        if ($time > 1000) { // > 1 second
            Log::channel('performance')->warning('Slow query', [
                'query' => $query,
                'time_ms' => $time,
                'threshold_ms' => 1000,
            ]);
        }
    }

    public static function logSlowRequest($path, $method, $time) {
        if ($time > 500) { // > 500ms
            Log::channel('performance')->warning('Slow request', [
                'path' => $path,
                'method' => $method,
                'time_ms' => $time,
                'threshold_ms' => 500,
            ]);
        }
    }
}
```

Use in middleware:
```php
class LogSlowRequests {
    public function handle($request, Closure $next) {
        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        PerformanceLogger::logSlowRequest(
            $request->path(),
            $request->method(),
            $duration
        );

        return $response;
    }
}
```

## Centralized Logging

### ELK Stack (Elasticsearch, Logstash, Kibana)
```php
// Log to ELK
'elk' => [
    'driver' => 'monolog',
    'handler' => \Monolog\Handler\ElasticsearchHandler::class,
    'handler_with' => [
        'client' => [
            'hosts' => [env('ELK_HOST', 'localhost:9200')],
        ],
    ],
],
```

### Sentry (Error Tracking)
```php
// In config/services.php
'sentry' => [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'traces_sample_rate' => 0.1,
    'environment' => config('app.env'),
],
```

## Best Practices Checklist

- [ ] Use structured context arrays instead of string concatenation
- [ ] Include relevant IDs (user_id, message_id, conversation_id, etc.)
- [ ] Log before and after significant operations
- [ ] Include timing/performance metrics
- [ ] Mask sensitive data
- [ ] Use appropriate log levels
- [ ] Include error codes and error messages
- [ ] Log in exceptions handlers
- [ ] Include timestamps for async operations
- [ ] Log important state changes
- [ ] Make logs searchable and queryable
- [ ] Regular cleanup of old log files
- [ ] Monitor log file sizes
- [ ] Set up alerts for errors
- [ ] Review logs regularly

## Files Reference

- `config/logging.php` - Logging configuration
- `app/Services/HealthCheckService.php` - Health monitoring
- `routes/api.php` - Health check endpoints
- `storage/logs/` - Log storage directory

---

## Isi dari: PERFORMANCE_MONITORING_GUIDE.md

# Performance Monitoring Guide

## Overview

Comprehensive performance monitoring system including request tracking, query analysis, and resource utilization.

## Request Timing Middleware

Create middleware to track request duration:

```php
// app/Http/Middleware/LogRequestPerformance.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequestPerformance {
    public function handle(Request $request, Closure $next) {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = (microtime(true) - $start) * 1000; // milliseconds
        
        // Log slow requests
        if ($duration > config('performance.slow_request_threshold', 500)) {
            Log::channel('performance')->warning('Slow request detected', [
                'path' => $request->path(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'threshold_ms' => config('performance.slow_request_threshold'),
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        // Add timing header
        $response->header('X-Response-Time-Ms', round($duration, 2));
        
        return $response;
    }
}
```

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(\App\Http\Middleware\LogRequestPerformance::class);
})
```

## Query Performance Monitoring

Enable in `AppServiceProvider`:

```php
public function boot() {
    DB::listen(function ($query) {
        $duration = $query->time; // milliseconds
        
        if ($duration > config('performance.slow_query_threshold', 1000)) {
            Log::channel('performance')->warning('Slow query', [
                'sql' => $query->sql,
                'duration_ms' => $duration,
                'bindings' => $query->bindings,
                'threshold_ms' => config('performance.slow_query_threshold'),
            ]);
        }
    });
}
```

## Memory Usage Tracking

```php
class MemoryTracker {
    private $startMemory;
    private $peakMemory;

    public function start() {
        $this->startMemory = memory_get_usage(true);
    }

    public function getUsage() {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        
        return [
            'current_mb' => round($current / 1024 / 1024, 2),
            'peak_mb' => round($peak / 1024 / 1024, 2),
            'used_mb' => round(($current - $this->startMemory) / 1024 / 1024, 2),
            'limit_mb' => round(ini_get('memory_limit') / 1024 / 1024, 2),
        ];
    }

    public function logUsage($context = []) {
        Log::channel('performance')->info('Memory usage', array_merge(
            $this->getUsage(),
            $context
        ));
    }
}
```

Usage:
```php
$tracker = new MemoryTracker();
$tracker->start();

// Heavy operation
$users = User::with('messages', 'conversations')->get();

$tracker->logUsage(['operation' => 'user_load']);
```

## Database Metrics

Create a service to collect database metrics:

```php
// app/Services/DatabaseMetricsService.php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class DatabaseMetricsService {
    public function getMetrics() {
        return [
            'connections' => [
                'active' => $this->getActiveConnections(),
                'total' => $this->getTotalConnections(),
                'max' => config('database.max_connections', 150),
            ],
            'queries' => [
                'total_today' => $this->getTotalQueriesToday(),
                'slow_queries' => $this->getSlowQueriesToday(),
                'failed_queries' => $this->getFailedQueriesToday(),
            ],
            'replication' => [
                'lag_seconds' => $this->getReplicationLag(),
                'is_healthy' => $this->isReplicationHealthy(),
            ],
        ];
    }

    private function getActiveConnections() {
        try {
            $result = DB::select('SHOW PROCESSLIST');
            return count($result);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getTotalConnections() {
        // Read from performance_schema if available
        try {
            $result = DB::select(
                'SELECT COUNT(*) as total FROM information_schema.PROCESSLIST'
            );
            return $result[0]->total ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getTotalQueriesToday() {
        // From custom query logging
        return \Cache::get('db_metrics.queries_today', 0);
    }

    private function getSlowQueriesToday() {
        return \Log::getMonolog()
            ->getHandlers()[0]
            ->getRecords()
            ->filter(fn($r) => $r['level'] >= 300) // Warning level
            ->count();
    }

    private function getFailedQueriesToday() {
        return \Cache::get('db_metrics.failed_queries', 0);
    }

    private function getReplicationLag() {
        try {
            if (config('database.replication.enabled')) {
                $result = DB::connection('replica')
                    ->select('SHOW SLAVE STATUS');
                return $result[0]->Seconds_Behind_Master ?? null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    private function isReplicationHealthy() {
        $lag = $this->getReplicationLag();
        return $lag !== null && $lag < 10; // Less than 10 seconds lag
    }
}
```

## Cache Efficiency

```php
class CacheMetrics {
    public function trackCacheHit($key, $hit = true) {
        $stats = \Cache::get('cache_stats', [
            'hits' => 0,
            'misses' => 0,
        ]);

        if ($hit) {
            $stats['hits']++;
        } else {
            $stats['misses']++;
        }

        \Cache::put('cache_stats', $stats, 3600);
    }

    public function getEfficiency() {
        $stats = \Cache::get('cache_stats', ['hits' => 0, 'misses' => 0]);
        $total = $stats['hits'] + $stats['misses'];
        
        return $total > 0 ? ($stats['hits'] / $total) * 100 : 0;
    }

    public function logMetrics() {
        Log::channel('performance')->info('Cache efficiency', [
            'hit_rate_percent' => round($this->getEfficiency(), 2),
            'hits' => \Cache::get('cache_stats.hits', 0),
            'misses' => \Cache::get('cache_stats.misses', 0),
        ]);
    }
}
```

## API Response Time Tracking

```php
class ApiResponseMetrics {
    public function trackResponse($path, $method, $duration, $statusCode) {
        $key = "api_metrics.{$path}.{$method}";
        
        $metrics = \Cache::get($key, [
            'total_requests' => 0,
            'total_time_ms' => 0,
            'min_time_ms' => PHP_FLOAT_MAX,
            'max_time_ms' => 0,
            'error_count' => 0,
        ]);

        $metrics['total_requests']++;
        $metrics['total_time_ms'] += $duration;
        $metrics['min_time_ms'] = min($metrics['min_time_ms'], $duration);
        $metrics['max_time_ms'] = max($metrics['max_time_ms'], $duration);
        
        if ($statusCode >= 400) {
            $metrics['error_count']++;
        }

        \Cache::put($key, $metrics, 3600);
    }

    public function getAverageResponseTime($path, $method) {
        $key = "api_metrics.{$path}.{$method}";
        $metrics = \Cache::get($key);
        
        if (!$metrics || $metrics['total_requests'] == 0) {
            return null;
        }

        return $metrics['total_time_ms'] / $metrics['total_requests'];
    }
}
```

## Monitoring Dashboard Endpoint

```php
// app/Http/Controllers/MetricsController.php
class MetricsController extends Controller {
    public function __construct(
        private DatabaseMetricsService $dbMetrics,
        private CacheMetrics $cacheMetrics,
    ) {}

    public function index() {
        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'server' => [
                'memory' => [
                    'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'limit_mb' => round(ini_get('memory_limit') / 1024 / 1024, 2),
                ],
                'uptime_seconds' => $this->getServerUptime(),
                'cpu_load' => $this->getCpuLoad(),
            ],
            'database' => $this->dbMetrics->getMetrics(),
            'cache' => [
                'hit_rate_percent' => round($this->cacheMetrics->getEfficiency(), 2),
                'driver' => config('cache.default'),
            ],
            'application' => [
                'total_users' => \App\Models\User::count(),
                'active_conversations' => \App\Models\Conversation::where('status', 'open')->count(),
                'pending_messages' => \App\Models\ChatMessage::where('wa_status', 'pending')->count(),
            ],
        ]);
    }

    private function getServerUptime() {
        if (function_exists('shell_exec')) {
            $uptime = shell_exec('uptime -s 2>/dev/null');
            return $uptime ? trim($uptime) : 'unknown';
        }
        return 'unknown';
    }

    private function getCpuLoad() {
        if (function_exists('sys_getloadavg')) {
            $loads = sys_getloadavg();
            return [
                '1_minute' => round($loads[0], 2),
                '5_minute' => round($loads[1], 2),
                '15_minute' => round($loads[2], 2),
            ];
        }
        return null;
    }
}
```

Register route in `routes/api.php`:
```php
Route::get('/metrics', [MetricsController::class, 'index'])
    ->middleware('auth:api');
```

## Configuration

Create `config/performance.php`:

```php
return [
    'slow_request_threshold' => env('SLOW_REQUEST_THRESHOLD_MS', 500),
    'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD_MS', 1000),
    'track_memory' => env('TRACK_MEMORY', false),
    'track_cache' => env('TRACK_CACHE', true),
    'performance_log_channel' => 'performance',
];
```

## Alerts

Send alerts for performance issues:

```php
class PerformanceAlertService {
    public static function checkAndAlert() {
        // Check database metrics
        $metrics = app(DatabaseMetricsService::class)->getMetrics();
        
        if ($metrics['connections']['active'] > $metrics['connections']['max'] * 0.8) {
            Notification::route('slack', config('services.slack.alert_webhook'))
                ->notify(new HighDatabaseConnectionAlert($metrics));
        }

        // Check cache efficiency
        $cacheHitRate = app(CacheMetrics::class)->getEfficiency();
        if ($cacheHitRate < 50) {
            Log::warning('Low cache hit rate', ['hit_rate' => $cacheHitRate]);
        }
    }
}
```

Run periodically:
```bash
php artisan schedule:add "App\Console\Commands\CheckPerformanceMetrics"
```

## Profiling

Use Laravel Debugbar in development:

```bash
composer require barryvdh/laravel-debugbar --dev
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

## Best Practices

- [ ] Monitor response times regularly
- [ ] Track slow queries
- [ ] Monitor memory usage
- [ ] Check database connections
- [ ] Track cache efficiency
- [ ] Set up performance alerts
- [ ] Review metrics daily
- [ ] Optimize based on data
- [ ] Use caching effectively
- [ ] Index frequently queried columns
- [ ] Profile before optimizing
- [ ] Document performance baselines
- [ ] Monitor third-party services
- [ ] Track API response times
- [ ] Archive old performance logs

## Files Reference

- `config/performance.php` - Performance configuration
- `app/Http/Middleware/LogRequestPerformance.php` - Request logging
- `app/Services/DatabaseMetricsService.php` - Database metrics
- `app/Http/Controllers/MetricsController.php` - Metrics endpoint
- `storage/logs/performance.log` - Performance logs

---

## Isi dari: RATE_LIMITING_GUIDE.md

# Rate Limiting Guide

## Overview

Comprehensive rate limiting to prevent abuse and protect application resources.

## Configuration

Create `config/rate_limit.php`:

```php
return [
    'enabled' => env('RATE_LIMIT_ENABLED', true),
    
    'limits' => [
        'default' => '60,1',  // 60 requests per minute
        'api' => '100,1',     // 100 requests per minute for authenticated API
        'auth' => '5,1',      // 5 requests per minute for login attempts
        'wa_webhook' => '1000,1', // High for webhook processing
    ],

    'cache_driver' => env('RATE_LIMIT_CACHE', 'cache'),
    
    'skip_paths' => [
        'health',
        'ready',
        'live',
    ],

    'response' => [
        'status_code' => 429,
        'message' => 'Too many requests. Please try again in :retry_after seconds.',
    ],
];
```

## Middleware Implementation

### Global Rate Limiting
```php
// app/Http/Middleware/RateLimitRequests.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitRequests {
    public function handle(Request $request, Closure $next) {
        if (!config('rate_limit.enabled')) {
            return $next($request);
        }

        // Skip health endpoints
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Get appropriate limit
        $limit = $this->getLimit($request);
        $key = $this->getKey($request);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'error' => 'Too many requests',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after_seconds' => $seconds,
                'timestamp' => now()->toIso8601String(),
            ], 429)
            ->header('Retry-After', $seconds);
        }

        RateLimiter::hit($key, 1);
        
        $response = $next($request);
        
        // Add rate limit headers
        return $response
            ->header('X-RateLimit-Limit', config('rate_limit.limits.default'))
            ->header('X-RateLimit-Remaining', RateLimiter::remaining($key, $limit))
            ->header('X-RateLimit-Reset', RateLimiter::resetAfter($key));
    }

    private function shouldSkip(Request $request) {
        foreach (config('rate_limit.skip_paths') as $path) {
            if ($request->is($path)) {
                return true;
            }
        }
        return false;
    }

    private function getLimit(Request $request) {
        if ($request->is('api/*') && auth()->check()) {
            return config('rate_limit.limits.api', 100);
        }
        
        if ($request->is('*/login', '*/register')) {
            return config('rate_limit.limits.auth', 5);
        }

        return config('rate_limit.limits.default', 60);
    }

    private function getKey(Request $request) {
        $identifier = auth()->check() ? auth()->id() : $request->ip();
        $method = $request->method();
        $path = $request->path();
        
        return "{$method}|{$path}|{$identifier}";
    }
}
```

### User-Specific Rate Limiting
```php
// Routes
Route::middleware(['auth', 'throttle:api_limit'])->group(function () {
    Route::post('/messages', [ChatController::class, 'store']);
});

// config/rate_limit.php
'limits' => [
    'api_limit' => '100,1',  // Per authenticated user
],
```

## Advanced Strategies

### Dynamic Rate Limiting by User Role
```php
class DynamicRateLimiter {
    public static function getLimit($request) {
        $user = auth()->user();
        
        return match ($user?->role) {
            'admin' => 1000,
            'operator' => 500,
            'useradmin' => 200,
            'viewer' => 100,
            default => 60,
        };
    }
}
```

### IP-Based Rate Limiting
```php
$key = $request->ip(); // Use IP instead of user ID

RateLimiter::tooManyAttempts($key, 100, 60); // 100 requests per hour
```

### Sliding Window Algorithm
```php
class SlidingWindowRateLimiter {
    public function isAllowed($key, $limit, $windowMinutes) {
        $now = now();
        $window = $now->copy()->subMinutes($windowMinutes);
        
        $count = Cache::get($key . ':requests', [])
            ->filter(fn($time) => $time > $window)
            ->count();

        if ($count >= $limit) {
            return false;
        }

        Cache::put(
            $key . ':requests',
            [...Cache::get($key . ':requests', []), $now],
            $windowMinutes * 60
        );

        return true;
    }
}
```

## Monitoring & Alerts

```php
class RateLimitMonitor {
    public static function trackLimit($key, $limit) {
        $exceeded = Cache::get($key . ':exceeded', 0);
        
        if ($exceeded > $limit * 0.8) {
            Log::warning('High rate limit usage', [
                'key' => $key,
                'usage_percent' => ($exceeded / $limit) * 100,
                'limit' => $limit,
            ]);

            if ($exceeded >= $limit) {
                Notification::route('slack', config('services.slack.alert_webhook'))
                    ->notify(new RateLimitExceededAlert($key));
            }
        }
    }
}
```

## Testing

```php
class RateLimitTest extends TestCase {
    public function test_rate_limit_blocks_excess_requests() {
        $response = $this->get('/api/messages');
        $this->assertEquals(200, $response->status());

        // Make requests up to limit
        for ($i = 0; $i < 99; $i++) {
            $this->get('/api/messages');
        }

        // Next request should fail
        $response = $this->get('/api/messages');
        $this->assertEquals(429, $response->status());
    }

    public function test_rate_limit_resets_after_window() {
        Config::set('rate_limit.limits.default', '2,1'); // 2 per minute

        $this->get('/api/messages');
        $this->get('/api/messages');
        
        $response = $this->get('/api/messages');
        $this->assertEquals(429, $response->status());

        // Advance time
        $this->travelTo(now()->addMinute());
        
        $response = $this->get('/api/messages');
        $this->assertEquals(200, $response->status());
    }
}
```

## Best Practices

- [ ] Set appropriate limits per endpoint
- [ ] Use user ID for authenticated requests
- [ ] Use IP for unauthenticated requests
- [ ] Skip health/status endpoints
- [ ] Add rate limit headers
- [ ] Provide clear error messages
- [ ] Log rate limit violations
- [ ] Monitor limit usage
- [ ] Adjust limits based on data
- [ ] Consider burst capacity
- [ ] Use sliding window algorithm
- [ ] Test rate limiting thoroughly
- [ ] Document limits in API docs
- [ ] Provide retry-after header
- [ ] Exempt critical operations

## Files Reference

- `config/rate_limit.php` - Rate limit configuration
- `app/Http/Middleware/RateLimitRequests.php` - Rate limit middleware
- Routes with `:throttle` - Route-level limits

---

## Isi dari: REQUEST_RESPONSE_LOGGING_GUIDE.md

# Request/Response Logging Middleware Guide

## Overview

Comprehensive logging of all HTTP requests and responses for debugging, security auditing, and compliance.

## Request/Response Logging Middleware

Create comprehensive logging middleware:

```php
// app/Http/Middleware/LogRequestResponse.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequestResponse {
    private array $skipPaths = [
        'health',
        'ready',
        'live',
        'metrics',
    ];

    private array $maskFields = [
        'password',
        'token',
        'secret',
        'api_key',
        'credit_card',
        'phone',
    ];

    public function handle(Request $request, Closure $next) {
        // Skip logging for certain paths
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Log request
        $this->logRequest($request);

        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        // Log response
        $this->logResponse($request, $response, $duration);

        return $response;
    }

    private function logRequest(Request $request) {
        Log::info('HTTP request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id' => auth()->id(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'query' => $this->maskSensitive($request->query()),
            'body' => $this->maskSensitive($request->except(['password', 'token'])),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function logResponse(Request $request, $response, $duration) {
        // Get response content
        $content = $response->getContent();
        $contentSize = strlen($content);

        Log::info('HTTP response', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'content_size_bytes' => $contentSize,
            'user_id' => auth()->id(),
            'response_headers' => $this->sanitizeHeaders($response->headers->all()),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Log errors
        if ($response->getStatusCode() >= 400) {
            Log::warning('HTTP error response', [
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'user_id' => auth()->id(),
                'body' => $this->maskSensitive(json_decode($content, true) ?? $content),
            ]);
        }

        // Log slow responses
        if ($duration > config('logging.slow_request_threshold_ms', 500)) {
            Log::warning('Slow HTTP request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'duration_ms' => round($duration, 2),
                'threshold_ms' => config('logging.slow_request_threshold_ms'),
            ]);
        }
    }

    private function sanitizeHeaders($headers) {
        $sensitive = ['authorization', 'cookie', 'x-api-key', 'x-token'];
        
        return collect($headers)
            ->mapWithKeys(function ($value, $key) use ($sensitive) {
                $lower = strtolower($key);
                if (in_array($lower, $sensitive)) {
                    return [$key => '***REDACTED***'];
                }
                return [$key => is_array($value) ? implode(',', $value) : $value];
            })
            ->toArray();
    }

    private function maskSensitive($data) {
        if (!is_array($data)) {
            return $data;
        }

        return collect($data)
            ->map(function ($value, $key) {
                if ($this->isSensitive($key)) {
                    return '***REDACTED***';
                }
                if (is_array($value)) {
                    return $this->maskSensitive($value);
                }
                return $value;
            })
            ->toArray();
    }

    private function isSensitive($key) {
        $lower = strtolower($key);
        foreach ($this->maskFields as $field) {
            if (strpos($lower, $field) !== false) {
                return true;
            }
        }
        return false;
    }

    private function shouldSkip(Request $request) {
        foreach ($this->skipPaths as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return true;
            }
        }
        return false;
    }
}
```

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(\App\Http\Middleware\LogRequestResponse::class);
    $middleware->api(\App\Http\Middleware\LogRequestResponse::class);
})
```

## Structured Logging Channel

Create dedicated channel in `config/logging.php`:

```php
'channels' => [
    'requests' => [
        'driver' => 'daily',
        'path' => storage_path('logs/requests.log'),
        'level' => 'info',
        'days' => 14,
        'formatter' => \App\Logging\JsonFormatter::class,
    ],
    'errors' => [
        'driver' => 'daily',
        'path' => storage_path('logs/errors.log'),
        'level' => 'error',
        'days' => 30,
    ],
    'performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/performance.log'),
        'level' => 'notice',
        'days' => 7,
    ],
],
```

## JSON Formatter

Create custom formatter for structured logs:

```php
// app/Logging/JsonFormatter.php
namespace App\Logging;

use Monolog\Formatter\FormatterInterface;

class JsonFormatter implements FormatterInterface {
    public function format(array $record) {
        $formatted = [
            'timestamp' => $record['datetime']->toIso8601String(),
            'level' => $record['level_name'],
            'message' => $record['message'],
            'context' => $record['context'],
            'extra' => $record['extra'],
        ];

        return json_encode($formatted) . PHP_EOL;
    }

    public function formatBatch(array $records) {
        return implode('', array_map([$this, 'format'], $records));
    }
}
```

## Audit Logging

Log sensitive operations:

```php
// app/Services/AuditLogger.php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuditLogger {
    public static function log($action, $model, $changes = []) {
        Log::channel('audit')->info('Audit log', [
            'action' => $action,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'model' => get_class($model),
            'model_id' => $model->id,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

Usage:
```php
// In controller
$message = ChatMessage::create($validated);
AuditLogger::log('message_created', $message, [
    'conversation_id' => $message->conversation_id,
    'message_length' => strlen($message->message),
]);
```

## Query Logging

Log database queries:

```php
// In AppServiceProvider
public function boot() {
    DB::listen(function ($query) {
        Log::channel('queries')->debug('Database query', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'duration_ms' => $query->time,
            'caller' => $this->getCaller(),
        ]);
    });
}

private function getCaller() {
    $backtrace = debug_backtrace();
    foreach ($backtrace as $trace) {
        if (strpos($trace['file'], 'vendor') === false) {
            return "{$trace['file']}:{$trace['line']}";
        }
    }
    return 'unknown';
}
```

## Log Analysis

### Search Logs
```bash
# Find errors for user
grep '"user_id": 123' storage/logs/requests.log

# Find slow requests
grep 'duration_ms.*[5-9][0-9][0-9]' storage/logs/requests.log

# Count requests by status
grep -o '"status": [0-9]*' storage/logs/requests.log | sort | uniq -c

# Real-time monitoring
tail -f storage/logs/requests.log | jq '.duration_ms'
```

### Using jq
```bash
# Parse JSON logs
tail -f storage/logs/requests.log | jq '.method, .path, .status'

# Filter by status
cat storage/logs/requests.log | jq 'select(.status >= 400)'

# Average response time
cat storage/logs/requests.log | jq '.duration_ms' | awk '{sum+=$1; count++} END {print sum/count}'
```

## Centralized Logging

### ELK Stack Integration
```php
// Store logs in Elasticsearch
'elk' => [
    'driver' => 'monolog',
    'handler' => \Monolog\Handler\ElasticsearchHandler::class,
    'handler_with' => [
        'client' => [
            'hosts' => ['localhost:9200'],
        ],
        'index' => 'lawangsewu-logs',
    ],
],
```

### Splunk Integration
```php
'splunk' => [
    'driver' => 'monolog',
    'handler' => \Monolog\Handler\SyslogUdpHandler::class,
    'handler_with' => [
        'host' => 'splunk-collector.example.com',
        'port' => 514,
    ],
],
```

## Testing

```php
class RequestLoggingTest extends TestCase {
    public function test_requests_are_logged() {
        Log::spy();

        $this->post('/api/messages', ['message' => 'Test']);

        Log::shouldHaveReceived('info')
            ->withArgs(fn($msg) => $msg === 'HTTP request')
            ->once();
    }

    public function test_sensitive_data_is_masked() {
        Log::spy();

        $this->post('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        Log::shouldHaveReceived('info')
            ->withArgs(
                fn($msg, $context) =>
                    $context['body']['password'] === '***REDACTED***'
            )
            ->once();
    }
}
```

## Best Practices

- [ ] Log all requests/responses
- [ ] Mask sensitive data
- [ ] Skip health endpoints
- [ ] Include request ID for tracing
- [ ] Include user ID for audit
- [ ] Log timing metrics
- [ ] Log errors separately
- [ ] Use structured logging (JSON)
- [ ] Archive old logs
- [ ] Monitor log file size
- [ ] Use appropriate log levels
- [ ] Include context in logs
- [ ] Test logging in tests
- [ ] Set up log rotation
- [ ] Centralize logs for analysis

## Files Reference

- `config/logging.php` - Logging configuration
- `app/Http/Middleware/LogRequestResponse.php` - Request/response logging
- `app/Services/AuditLogger.php` - Audit logging
- `app/Logging/JsonFormatter.php` - JSON formatting
- `storage/logs/` - Log files

---

## Isi dari: TESTING_CREDENTIALS_SECURITY.md

# Security: Test Credentials Management

## Overview
Test database credentials are properly managed to prevent exposure in version control.

## Files
- `.env.testing` - Test environment variables (committed)
- `.env.testing.local` - Local overrides (gitignored)
- `phpunit.xml.dist` - Test configuration template (committed)
- `phpunit.xml` - Local test configuration (gitignored)

## How It Works

### Local Development
1. Copy `phpunit.xml.dist` to `phpunit.xml`
2. Update credentials in `phpunit.xml` as needed
3. `phpunit.xml` is gitignored and won't be committed

Alternatively, use `.env.testing` to provide credentials:
```bash
# .env.testing (committed)
DB_PASSWORD=semakinhebat@26
SIPP_DB_PASSWORD=R4h4514@
```

### GitHub Actions / CI/CD
1. Use `phpunit.xml.dist` as template
2. Set repository secrets for sensitive values
3. Inject during test run:

```yaml
# .github/workflows/test.yml
name: Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_PASSWORD: ${{ secrets.TESTING_DB_PASSWORD }}
          MYSQL_ROOT_PASSWORD: ${{ secrets.TESTING_ROOT_PASSWORD }}
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    steps:
      - uses: actions/checkout@v3
      - uses: php-actions/setup-php@v1
      - run: composer install
      - run: npm ci && npm run build
      - run: cp phpunit.xml.dist phpunit.xml
      - run: php artisan test
        env:
          DB_PASSWORD: ${{ secrets.TESTING_DB_PASSWORD }}
          SIPP_DB_PASSWORD: ${{ secrets.TESTING_DB_SIPP_PASSWORD }}
```

## Credentials Location
Actual test credentials are stored in:
- `.env.testing` (for local development, MAKE SURE IT'S SAFE)
- Repository Secrets (for GitHub Actions)
- Environment variables (for Docker/Container runs)

## Best Practices
1. ✅ Never commit `phpunit.xml` with credentials
2. ✅ Use `.env.testing` for local development
3. ✅ Use CI/CD secrets for automated testing
4. ✅ Rotate credentials periodically
5. ✅ Don't share `.env.testing` externally
6. ✅ Document credentials location in team wiki

## Troubleshooting

### Tests failing with "Access denied for user"
Check that credentials in `.env.testing` match your local MySQL:
```bash
# Run these commands in terminal to verify
mysql -u dbprakom -p -e "SELECT USER();"
```

### CI/CD failing with credential errors
1. Verify secrets are set in GitHub Settings > Secrets
2. Check secret names match environment variable names
3. Ensure workflow YAML uses correct secret syntax: `${{ secrets.NAME }}`

## Related Files
- `.gitignore` - Excludes test config files
- `.env.testing` - Test environment configuration
- `phpunit.xml.dist` - Distribution test template

---

## Isi dari: UNIT_TESTS_GUIDE.md

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

---

