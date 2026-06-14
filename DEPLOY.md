# DEPLOY.md — IKR ISP to Coolify / VPS

## Prasyarat

- PHP 8.2+ dengan ext: pdo, pdo_mysql, gd, zip, intl, opcache, bcmath
- MySQL 8.0+
- Redis 6+
- Node.js 20+ (untuk build frontend)
- Composer 2
- Akses ke: Saleskit DB (shared), eBilling DB (read-only) + API, FieldOps API, Evolution API, 1 OLT C300 (test), 1 MikroTik router (test)

## 1. Setup Project di VPS

```bash
# Clone atau copy folder
cd /opt
git clone <repo-url> skynet-ikr
cd skynet-ikr

# Install deps
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Copy env
cp .env.production.example .env
nano .env   # edit semua *_PASSWORD / *_KEY / *_SECRET (lihat SECURITY.md)

# Generate key
php artisan key:generate

# Storage link
php artisan storage:link

# Run migrations (5 lokal + 1 patch Saleskit)
php artisan migrate --force

# Seed default users
php artisan db:seed --force

# Permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## 2. Deploy via Coolify

**Option A: Docker Compose (recommended)**

1. Coolify → New Resource → Docker Compose
2. Paste isi `docker-compose.yml`
3. Set environment variables di Coolify:
   - `DB_PASSWORD`, `DB_ROOT_PASSWORD`
   - `SALESKIT_DB_HOST`, `SALESKIT_DB_DATABASE`, `SALESKIT_DB_USERNAME`, `SALESKIT_DB_PASSWORD`
   - `EBILLING_DB_HOST`, `EBILLING_DB_DATABASE`, `EBILLING_DB_USERNAME`, `EBILLING_DB_PASSWORD`
4. Set domain: `ikr.sky.net.id`
5. Deploy

**Option B: Static build + PHP-FPM**

```bash
# Build
npm run build

# Nginx config (save as /etc/nginx/sites-enabled/ikr.conf)
server {
    listen 80;
    server_name ikr.sky.net.id;
    root /opt/skynet-ikr/public;
    index index.php;

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}

# SSL via Certbot
certbot --nginx -d ikr.sky.net.id
```

## 3. Background Workers (WAJIB)

**Queue worker** (untuk SyncOrderToEbilling, SendWaNotification):

```bash
# /etc/systemd/system/ikr-queue.service
[Unit]
Description=IKR-ISP Queue Worker
After=redis.service mysql.service

[Service]
User=www-data
WorkingDirectory=/opt/skynet-ikr
ExecStart=/usr/bin/php artisan queue:work redis --tries=3 --sleep=3 --timeout=60
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

**Scheduler** (untuk cron-like jobs):

```bash
# /etc/systemd/system/ikr-scheduler.service
[Unit]
Description=IKR-ISP Scheduler
After=redis.service mysql.service

[Service]
User=www-data
WorkingDirectory=/opt/skynet-ikr
ExecStart=/bin/sh -c 'while true; do php artisan schedule:run; sleep 60; done'
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Enable:
```bash
systemctl daemon-reload
systemctl enable --now ikr-queue ikr-scheduler
```

## 4. WAJIB dibuat di eBilling

API endpoint `GET /api/teknisi` (return list teknisi + open_ticket_count). Tanpa ini, assignment step 3 bakal fallback ke query langsung DB eBilling (kurang ideal).

```php
// eBilling: app/Http/Controllers/Api/TeknisiController.php
public function index(): JsonResponse {
    $teknisis = User::whereIn('role', ['teknisi', 'leader_teknisi'])->get();
    return response()->json([
        'data' => $teknisis->map(fn($u) => [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
            'phone' => $u->phone, 'role' => $u->role,
            'open_tickets' => SupportTicket::where('teknisi_id', $u->id)
                ->whereNotIn('status', ['closed','resolved'])->count(),
        ])
    ]);
}
```

## 5. WAJIB ditambah di Saleskit.registrations

Column `router_name VARCHAR(100) NULL` — auto-handled oleh migration ke-6 (pakai connection `saleskit`).

Kalo gak mau run migration ke Saleskit DB, run manual:
```sql
ALTER TABLE skynet_saleskit.registrations ADD COLUMN router_name VARCHAR(100) NULL AFTER package;
CREATE INDEX idx_router_name ON skynet_saleskit.registrations(router_name);
```

## 6. Smoke Test

```bash
# 1. Login admin
curl -X POST https://ikr.sky.net.id/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@ikr.local","password":"<DEMO_PASS_FROM_SEEDER>"}'

# 2. Create test PSB (sebagai sales)
# 3. Approve coverage (sebagai sales_leader)
# 4. Assign teknisi (sebagai leader_teknisi)
# 5. Provision (sebagai teknisi) - test C300 SSH
# 6. Upload 6 foto + BAI
# 7. Sync to eBilling - verify di eBilling
```

## 7. Monitoring

- Logs: `tail -f /opt/skynet-ikr/storage/logs/laravel.log`
- Queue: `php artisan queue:monitor redis`
- Failed jobs: `php artisan queue:failed`
- Retry failed: `php artisan queue:retry all`
