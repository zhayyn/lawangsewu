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
