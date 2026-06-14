# IKR ISP — PSB Management System

Laravel 11 + Filament 3 + Inertia.js + React 18. Starlite/IKR-style PSB management untuk ISP FTTH.

> **Status:** Production-ready (v1.0.0) — semua 10 klarifikasi operator terimplementasi, full test coverage untuk state machine + PPPoE generator.

## Stack

- **Backend**: Laravel 11, Sanctum auth, Spatie Permission, Filament 3 admin
- **Frontend**: Inertia.js + React 18 + Tailwind CSS 3 + Vite
- **State**: Redis (cache + queue) + MySQL session table
- **DB**: MySQL 8 (1 lokal, 1 shared dgn Saleskit, 1 read-only ke eBilling)
- **External**: FieldOps API, eBilling API, OLT C300 (phpseclib SSH), MikroTik (RouterOS API), Evolution API (WA)
- **Tests**: PHPUnit + Pest (enum coverage, PPPoE generator)

## Alur PSB (7-step pipeline)

```
draft → submitted → coverage_ok → assigned → provisioning → photos → done
                              ↘ rejected → (revert to provisioning)
```

1. **Sales** input pendaftar (form, dgn router_name wajib)
2. **Sales Leader** coverage check (Haversine ≤300m ke ODP via FieldOps)
3. **Leader Teknisi** assign teknisi (sort by open_ticket ASC, filter idle)
4. **Teknisi** pilih OLT (C300/HiOS) + input SN ONT
5. **Provisioning**: generate PPPoE (auto), C300 SSH-auto atau HiOS manual+checklist, add PPPoE secret ke MikroTik
6. **Teknisi**: 6 foto + redaman + GPS + HiOS checklist (kalo HiOS) + BAI ttd digital → PDF
7. **Sync**: fullSync ke eBilling (customer + invoice + RADIUS + upload 6 foto + BAI)

## Quick Start (Dev)

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# Edit .env: set DB_HOST, SALESKIT_DB_*, EBILLING_DB_*, dll
php artisan migrate --seed
npm run dev

php artisan serve
```

Login: `admin@ikr.local` / `password` (atau sales/salesleader/leadteknisi/teknisi1/2@ikr.local).

## Test

```bash
php artisan test
```

## Production Deploy

Lihat [DEPLOY.md](DEPLOY.md) untuk langkah lengkap di Coolify.

## API Endpoints

Lihat [API.md](API.md) atau langsung di `routes/api.php`.

## Struktur Folder

```
skynet-ikr/
├── app/
│   ├── Enums/              # PsbStatus, OltType, CoverageStatus, dll
│   ├── Models/             # PsbOrder, User, PsbStatusLog, PsbHiosoChecklist
│   ├── Services/           # CoverageService, OltService, EbillingBridge, dll
│   ├── Http/Controllers/   # 9 PSB web + 4 API
│   ├── Filament/           # Admin panel
│   ├── Jobs/               # SendWaNotification, SyncOrderToEbilling
│   ├── Observers/          # PsbOrderObserver (auto-create HiOS checklist)
│   └── Providers/
├── database/migrations/    # 5 lokal + 1 patch Saleskit
├── resources/
│   ├── js/Pages/Psb/       # 9 Inertia pages
│   ├── js/Components/Psb/  # 7 React components
│   ├── views/pdf/bai.blade.php
│   └── views/app.blade.php
├── routes/
│   ├── web.php
│   ├── psb.php             # 30+ PSB routes
│   └── api.php             # 4 v1 API endpoints
├── config/psb.php          # 1 config file (Saleskit/FieldOps/ebilling/OLT/MikroTik/Evolution)
└── docker/
    ├── nginx.conf
    └── supervisord.conf
```

## Lisensi

Proprietary — internal skynet.id.
