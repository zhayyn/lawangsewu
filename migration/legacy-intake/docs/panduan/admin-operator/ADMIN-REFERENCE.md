# 🔧 Lawangsewu Portal - Administrator Reference

**Target Audience:** System Administrators, Developers, Technical Support Staff

---

## Table of Contents
1. [System Architecture](#system-architecture)
2. [Configuration Guide](#configuration-guide)  
3. [Troubleshooting](#troubleshooting)
4. [Maintenance & Monitoring](#maintenance--monitoring)
5. [Database Schema](#database-schema)
6. [API Reference](#api-reference)

---

## System Architecture

### Component Diagram

```
┌─────────────────────────────────────────────────┐
│ User Browser (https://lawangsewu.pa-semarang.go.id) │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│ Lawangsewu Gateway (PHP)                       │
│ ├─ /index.php (ROOT - session router)         │
│ ├─ /login.php (Auth form)                     │
│ ├─ /bootstrap.php (Auth functions)            │
│ └─ /logout.php                                │
└──────────────┬──────────────────────────────────┘
               │
        ┌──────┴──────┐
        │             │
        ▼             ▼
  ┌──────────────┐  ┌────────────────────────┐
  │CI4 Dashboard │  │Node.js Runtime Server │
  │  (Modules)   │  │ (server.mjs)          │
  │ ├─ Blast     │  │ ├─ POST /blast        │
  │ ├─ Pengaduan │  │ ├─ GET /blast         │
  │ └─ Konsultasi│  │ ├─ DELETE /blast      │
  │              │  │ └─ WhatsApp I/O       │
  └──────┬───────┘  └────────────┬───────────┘
         │                       │
         └───────────┬───────────┘
                     │
                     ▼
         ┌────────────────────────┐
         │    MySQL 5.7+          │
         │  - db_wacaraka         │
         │  - Other DBs           │
         └────────────────────────┘
```

### Session Flow

```
User Request
    │
    ▼
┌─────────────────────────────────┐
│ PHP Session Check               │
│ (lawangsewu_gateway_session)     │
└────────┬────────────────────────┘
         │
    ┌────┴────┐
    │ YES      │ NO
    │          │
    ▼          ▼
Dashboard   Login Page
    │          │
    └────┬─────┘
         │
    POST with credentials
         │
         ▼
  gateway_attempt_login()
         │
    ┌────┴────┐
    │ OK      │ FAIL
    │         │
    ▼         ▼
Set Session Error Message
    │         │
    └────┬────┘
         │
  Redirect/Refresh
```

---

## Configuration Guide

### 1. Gateway Configuration
**File:** `/var/www/html/lawangsewu/gateway/.env`

```bash
# Gateway settings
GATEWAY_APP_NAME=Lawangsewu
GATEWAY_BASE_PATH=/lawangsewu/gateway
GATEWAY_SESSION_NAME=lawangsewu_gateway_session

# SSO/Auth settings
# (others configured via admin panel or env)
```

### 2. WA-Caraka Runtime Configuration
**File:** `/var/www/html/lawangsewu/wa-caraka/server.mjs`

```javascript
// Config section (lines ~100-110)
const BLAST_DELAY_MS = 2000;           // Milliseconds between sends
const BLAST_MAX_RECIPIENTS = 500;      // Max recipients per job
```

**Configurable via environment:**
```bash
export BLAST_DELAY_MS=1000      # Send faster (1 sec per recipient)
export BLAST_MAX_RECIPIENTS=200 # Limit to 200 per batch
```

### 3. Database Configuration
**File:** `/var/www/html/lawangsewu/wa-caraka/dashboard-ci4-admin/.env`

```bash
database.default.hostname = localhost
database.default.database = db_wacaraka
database.default.username = root
database.default.password = root
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306
```

---

## Troubleshooting

### Issue 1: "User is not logged in" on Dashboard

**Symptoms:**
- Clicking dashboard button redirects to login
- Session appears to be lost

**Diagnosis:**
```bash
# Check if PHP sessions are working
php -r "session_start(); echo 'Sessions OK';"

# Verify session storage directory exists
ls -la /var/lib/php/sessions/ | head -5

# Check if session cookie is being set
curl -i https://lawangsewu.pa-semarang.go.id/gateway/login | grep -i "Set-Cookie"
```

**Solutions:**
1. Verify PHP session storage has write permissions:
```bash
chmod 1733 /var/lib/php/sessions/
```

2. Check session lifetime settings in `/gateway/bootstrap.php`:
```php
ini_set('session.gc_maxlifetime', 86400); // 24 hours
session_set_cookie_params([
    'lifetime' => 86400,
    'httponly' => true,
    'secure' => true, // For HTTPS only
    'samesite' => 'Lax'
]);
```

### Issue 2: Blast Job Stuck in "Pending"

**Symptoms:**
- Job shows status "Pending" even after 30+ seconds
- Recipients not being contacted

**Diagnosis:**
```bash
# Check if server.mjs is running
pgrep -f "node.*server.mjs" || echo "NOT RUNNING"

# Check Node process logs
tail -100 /var/www/html/lawangsewu/wa-caraka/logs/server.log

# Check if Baileys is authenticated
curl -s http://localhost:8793/status | python3 -m json.tool
```

**Solutions:**
1. Restart Node.js server:
```bash
cd /var/www/html/lawangsewu/wa-caraka
npm restart
# or manually
pkill -f "node.*server.mjs"
node server.mjs &
```

2. Check Baileys authentication:
```bash
curl http://localhost:8793/auth-state
# Should return: { "authenticated": true }
```

3. Verify database connection:
```bash
mysql -u root -proot db_wacaraka -e "SELECT COUNT(*) FROM wacaraka_blasts;"
```

### Issue 3: Pengaduan/Konsultasi Messages Not Appearing

**Symptoms:**
- WhatsApp messages received by bot but not showing in modules
- Inbox appears empty

**Diagnosis:**
```bash
# Check if incoming messages are being logged
tail -50 /var/www/html/lawangsewu/wa-caraka/logs/messages.log | grep -i "pengaduan\|konsultasi"

# Check database for recent messages
mysql -u root -proot db_wacaraka -e "
SELECT * FROM wacaraka_pengaduan 
ORDER BY created_at DESC 
LIMIT 5;
"
```

**Solutions:**
1. Verify message handlers are enabled in server.mjs (check for `case 'text'` handlers)
2. Check database tables exist:
```bash
mysql -u root -proot db_wacaraka -e "SHOW CREATE TABLE wacaraka_pengaduan\G"
```

3. Verify route is accessible:
```bash
curl -s http://localhost/lawangsewu/wa-caraka/dashboard-ci4-admin/pengaduan \
  -H "Cookie: lawangsewu_gateway_session=YOUR_SESSION_ID"
```

### Issue 4: Login Page Shows Old/Cached Content

**Symptoms:**
- Updated login page text not showing
- Form buttons appear different in different browsers

**Diagnosis:**
```bash
# Clear browser cache (user side):
# Ctrl+Shift+Delete or Command+Shift+Delete

# Server side: Check if HTML is being minified
grep -i "minif" /var/www/html/lawangsewu/gateway/login.php
```

**Solutions:**
1. Force browser cache clear (add version parameter):
```html
<!-- In page header -->
<meta name="viewport" content="width=device-width, initial-scale=1.0?v=20250317"/>
```

2. Add cache-busting headers to login.php:
```php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
```

### Issue 5: Module Routes Return 404

**Symptoms:**
- Clicking module buttons shows "404 Not Found"
- Routes not recognized by CodeIgniter

**Diagnosis:**
```bash
# Check if routes are registered
cd /var/www/html/lawangsewu/wa-caraka/dashboard-ci4-admin
php spark route:list | grep -E "blast|pengaduan|konsultasi"

# Check routes file syntax
php -l app/Config/Routes.php
```

**Solutions:**
1. Verify routes file has proper syntax:
```bash
php -l /var/www/html/lawangsewu/wa-caraka/dashboard-ci4-admin/app/Config/Routes.php
# Should output: "No syntax errors detected"
```

2. Clear CodeIgniter cache:
```bash
rm -rf /var/www/html/lawangsewu/wa-caraka/dashboard-ci4-admin/writable/cache/*
```

3. Verify routes are added to Routes.php:
```bash
grep -c "blast\|pengaduan\|konsultasi" \
  /var/www/html/lawangsewu/wa-caraka/dashboard-ci4-admin/app/Config/Routes.php
# Should output: 11
```

---

## Maintenance & Monitoring

### 1. Daily Checks

```bash
#!/bin/bash
# Daily health check script

echo "=== System Health Check ==="

# 1. Check if server.mjs is running
if pgrep -f "node.*server.mjs" > /dev/null; then
    echo "✅ Node.js server is running"
else
    echo "❌ Node.js server is DOWN - restarting..."
    cd /var/www/html/lawangsewu/wa-caraka
    npm restart
fi

# 2. Check MySQL
if mysql -u root -proot -e "SELECT 1" > /dev/null 2>&1; then
    echo "✅ MySQL is running"
else
    echo "❌ MySQL is DOWN"
fi

# 3. Check disk space
USAGE=$(df /var/www/html | awk 'NR==2 {print $5}' | cut -d'%' -f1)
if [ $USAGE -gt 90 ]; then
    echo "⚠️  Disk usage at $USAGE% - CLEANUP NEEDED"
else
    echo "✅ Disk usage at $USAGE%"
fi

# 4. Check database blast jobs
PENDING=$(mysql -u root -proot db_wacaraka -N -e \
    "SELECT COUNT(*) FROM wacaraka_blasts WHERE status='pending';")
if [ $PENDING -gt 0 ]; then
    echo "⚠️  $PENDING blast jobs still pending"
fi

echo "=== End Check ==="
```

Run daily via cron:
```bash
0 8 * * * /var/www/html/lawangsewu/maintenance/daily-check.sh >> /var/log/lawangsewu-check.log
```

### 2. Log Monitoring

**Key log files:**
```bash
/var/www/html/lawangsewu/wa-caraka/logs/server.log      # Node.js errors
/var/www/html/lawangsewu/wa-caraka/logs/messages.log    # Incoming messages
/var/www/html/lawangsewu/wa-caraka/writable/logs/       # CI4 logs
/var/log/apache2/access.log                             # HTTP access
/var/log/apache2/error.log                              # HTTP errors
```

**Monitoring commands:**
```bash
# Watch real-time logs
tail -f /var/www/html/lawangsewu/wa-caraka/logs/server.log

# Count errors in last hour
grep "ERROR" /var/www/html/lawangsewu/wa-caraka/logs/server.log | \
  awk '{print $1,$2}' | sort | uniq -c

# Monitor disk usage
watch -n 10 'df /var/www/html | tail -1'
```

### 3. Database Maintenance

```bash
# Weekly optimization
mysql -u root -proot db_wacaraka -e "OPTIMIZE TABLE wacaraka_blasts;"
mysql -u root -proot db_wacaraka -e "OPTIMIZE TABLE wacaraka_pengaduan;"
mysql -u root -proot db_wacaraka -e "OPTIMIZE TABLE wacaraka_konsultasi;"

# Monthly backup
mysqldump -u root -proot db_wacaraka > \
  /backup/db_wacaraka_$(date +%Y%m%d).sql

# Check table integrity
mysqlcheck -u root -proot db_wacaraka --check
```

---

## Database Schema

### wacaraka_blasts
```sql
CREATE TABLE wacaraka_blasts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    job_id VARCHAR(64) UNIQUE NOT NULL,
    message TEXT NOT NULL,
    total_recipients INT NOT NULL,
    sent_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    status ENUM('pending','running','completed','cancelled') DEFAULT 'pending',
    created_by VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (created_at),
    KEY (status)
);
```

### wacaraka_blast_recipients
```sql
CREATE TABLE wacaraka_blast_recipients (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    job_id VARCHAR(64) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    status ENUM('pending','sent','failed') DEFAULT 'pending',
    attempt_count INT DEFAULT 0,
    error_message VARCHAR(255),
    sent_at TIMESTAMP NULL,
    KEY (job_id),
    KEY (status),
    FOREIGN KEY (job_id) REFERENCES wacaraka_blasts(job_id)
);
```

### wacaraka_pengaduan
```sql
CREATE TABLE wacaraka_pengaduan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    wa_message_id VARCHAR(64),
    sender_phone VARCHAR(20) NOT NULL,
    sender_name VARCHAR(255),
    message TEXT NOT NULL,
    response TEXT,
    status ENUM('masuk','diproses','selesai','ditolak') DEFAULT 'masuk',
    handled_by VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (status),
    KEY (created_at),
    KEY (sender_phone)
);
```

### wacaraka_konsultasi
```sql
CREATE TABLE wacaraka_konsultasi (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    wa_message_id VARCHAR(64),
    asker_phone VARCHAR(20) NOT NULL,
    asker_name VARCHAR(255),
    question TEXT NOT NULL,
    answer TEXT,
    status ENUM('open','aktif','selesai','ditutup') DEFAULT 'open',
    answered_by VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (status),
    KEY (created_at),
    KEY (asker_phone)
);
```

---

## API Reference

### Blast API (server.mjs)

**POST /blast - Create new blast job**
```bash
curl -X POST http://localhost:8793/blast \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Halo, ini pesan blast dari sistem kami",
    "recipients": ["62812345678", "62887654321"],
    "metadata": { "campaign_id": "20250317-001" }
  }'

Response (201):
{
  "ok": true,
  "jobId": "blast-20250317-1234567890",
  "status": "pending",
  "totalRecipients": 2,
  "created": "2025-03-17T10:30:45Z"
}
```

**GET /blast - List all blast jobs**
```bash
curl http://localhost:8793/blast | python3 -m json.tool

Response (200):
{
  "ok": true,
  "jobs": [
    {
      "jobId": "blast-20250317-1234567890",
      "message": "...",
      "status": "completed",
      "totalRecipients": 100,
      "sent": 98,
      "failed": 2,
      "createdAt": "2025-03-17T10:30:45Z"
    }
  ]
}
```

**GET /blast/:jobId - Get specific job status**
```bash
curl http://localhost:8793/blast/blast-20250317-1234567890 | python3 -m json.tool

Response (200):
{
  "ok": true,
  "job": {
    "jobId": "...",
    "status": "running",
    "progress": "45/100",
    "results": {
      "sent": [
        {"phone": "62812345678", "sentAt": "2025-03-17T10:30:50Z"},
        {"phone": "62887654321", "sentAt": "2025-03-17T10:30:52Z"}
      ],
      "failed": [
        {"phone": "6289999999", "error": "Invalid number format"}
      ]
    }
  }
}
```

**DELETE /blast/:jobId - Cancel blast job**
```bash
curl -X DELETE http://localhost:8793/blast/blast-20250317-1234567890

Response (200):
{
  "ok": true,
  "message": "Blast job cancelled",
  "jobId": "blast-20250317-1234567890",
  "sentBefore": 45,
  "cancelledAt": "2025-03-17T10:35:00Z"
}
```

---

## Performance Tuning

### Database Optimization
```sql
-- Add indexes for frequently filtered columns
CREATE INDEX idx_blast_status ON wacaraka_blasts(status);
CREATE INDEX idx_blast_created ON wacaraka_blasts(created_at);
CREATE INDEX idx_pengaduan_status ON wacaraka_pengaduan(status);
CREATE INDEX idx_konsultasi_status ON wacaraka_konsultasi(status);
```

### Node.js Optimization
```javascript
// In server.mjs
const os = require('os');
const numWorkers = os.cpus().length;

// Start multiple worker processes for load balancing
// (if using cluster module, recommended for production)
```

### PHP/CodeIgniter Optimization
```bash
# Enable PHP opcode caching
apt-get install php-opcache

# Edit /etc/php/8.x/apache2/php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
```

---

## Backup & Recovery

### Automated Daily Backup
```bash
#!/bin/bash
BACKUP_DIR="/backup/lawangsewu"
DATE=$(date +%Y%m%d_%H%m%s)

mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u root -proot db_wacaraka > $BACKUP_DIR/db_wacaraka_$DATE.sql
gzip $BACKUP_DIR/db_wacaraka_$DATE.sql

# Backup code (daily)
tar -czf $BACKUP_DIR/code_$DATE.tar.gz /var/www/html/lawangsewu

# Keep only last 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

### Recovery Procedure
```bash
# Restore database
gunzip < /backup/lawangsewu/db_wacaraka_20250317.sql.gz | \
  mysql -u root -proot db_wacaraka

# Restore code
cd /var/www/html
tar -xzf /backup/lawangsewu/code_20250317.tar.gz
chown -R www-data:www-data lawangsewu/
```

---

## Security Hardening

### 1. File Permissions
```bash
# Make config files read-only
chmod 400 /var/www/html/lawangsewu/gateway/.env
chmod 400 /var/www/html/lawangsewu/wa-caraka/server.mjs

# Restrict writable directories
chmod 755 /var/www/html/lawangsewu/wa-caraka/writable/
chown www-data:www-data /var/www/html/lawangsewu/wa-caraka/writable/
```

### 2. Apache Security Headers
```apache
# In /etc/apache2/sites-available/lawangsewu.conf
<Directory /var/www/html/lawangsewu>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com;"
</Directory>
```

### 3. Rate Limiting
```apache
# Prevent brute force attacks on login
<Location /lawangsewu/gateway/login>
    SetEnvIf Request_URI "login" rate_limit_login=1
    # Use mod_ratelimit or custom logic
</Location>
```

---

## Deployment Checklist

- [ ] All files deployed and executable
- [ ] Database tables created
- [ ] MySQL user has proper permissions
- [ ] Node.js server started and authenticated
- [ ] Session directory writable by PHP
- [ ] SSL certificate valid (HTTPS working)
- [ ] Backup system configured
- [ ] Log monitoring in place
- [ ] Development code removed (no debug mode)
- [ ] Admin credentials changed from defaults
- [ ] Documentation available to support team

---

**Last Updated:** March 17, 2025  
**Version:** 1.0  
**Maintained By:** System Administration Team
