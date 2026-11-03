# Production Performance & Deployment Guide

This guide details recommended production configurations and server optimizations for the **AI-Driven Vehicle Repair and Fleet Management System**.

---

## 1. Laravel Production Caching Commands

Always run these commands during automated deployments (e.g. GitHub Actions, Forge, Envoyer) to pre-compile configuration, route maps, and Blade templates:

```bash
# 1. Clear stale caches
php artisan optimize:clear

# 2. Cache configuration files into a single optimized array
php artisan config:cache

# 3. Compile route definitions into a fast lookup tree
php artisan route:cache

# 4. Pre-compile all Blade views to eliminate runtime compilation overhead
php artisan view:cache

# 5. Cache discovery manifests for packages and events
php artisan event:cache
```

> [!IMPORTANT]
> When `config:cache` is active, the `env()` helper function will return `null` everywhere outside of `config/` files. Never call `env()` in controllers, models, or services; use `config(...)` instead.

---

## 2. PHP OPcache Recommended Configuration

Configure OPcache in your production `php.ini` to retain compiled bytecode in shared memory:

```ini
[opcache]
; Enable OPcache for CLI and FPM
opcache.enable=1
opcache.enable_cli=0

; Allocate 256MB for compiled PHP bytecode
opcache.memory_consumption=256

; Size of interned strings buffer (in MB)
opcache.interned_strings_buffer=16

; Maximum number of cached scripts (recommended > 20000 for Laravel)
opcache.max_accelerated_files=20000

; In production, disable file timestamp checks for maximum speed
opcache.validate_timestamps=0

; Revalidate frequency (ignored if validate_timestamps=0)
opcache.revalidate_freq=0

; Fast shutdown sequence
opcache.fast_shutdown=1

; Enable JIT compilation (PHP 8.4+)
opcache.jit=tracing
opcache.jit_buffer_size=128M
```

---

## 3. Database Tuning (MySQL 8.0 / MariaDB 11)

For fleet databases managing tens of thousands of vehicles and appointments:

1. **InnoDB Buffer Pool:**
   Allocate 70%–80% of available server RAM to InnoDB buffer pool:
   ```ini
   innodb_buffer_pool_size = 4G
   innodb_buffer_pool_instances = 4
   ```
2. **Log File & I/O Capacity:**
   ```ini
   innodb_log_file_size = 512M
   innodb_flush_log_at_trx_commit = 2   # Or 1 for absolute ACID durability
   innodb_flush_method = O_DIRECT
   innodb_io_capacity = 2000
   innodb_io_capacity_max = 4000
   ```
3. **Connection Pooling & Temporary Tables:**
   ```ini
   max_connections = 250
   tmp_table_size = 64M
   max_heap_table_size = 64M
   ```

---

## 4. Cache & Queue Architecture (Redis / Horizon)

1. **Default Cache Store:**
   In `.env`, configure Redis as the primary cache and session driver:
   ```env
   CACHE_STORE=redis
   SESSION_DRIVER=redis
   QUEUE_CONNECTION=redis
   ```
2. **Scheduled Predictive Maintenance Worker:**
   Add the Laravel scheduler cron entry on your Linux server:
   ```crontab
   * * * * * cd /home/izzxd/projects/vehicle-repair-fleet-system && php artisan schedule:run >> /dev/null 2>&1
   ```
3. **Queue Workers:**
   Process background notifications, emails, and bulk report exports via Supervisor or Laravel Horizon:
   ```bash
   php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
   ```

---

## 5. Security & Verification Checks Before Deployment

- Run the full regression test suite:
  ```bash
  php artisan test
  ```
- Run the code style linter:
  ```bash
  ./vendor/bin/pint --test
  ```
- Verify database migrations:
  ```bash
  php artisan migrate --status
  ```
