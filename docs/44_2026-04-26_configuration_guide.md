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
