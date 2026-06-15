# FieldOps API Contract — wajib dipenuhi sebelum IKR-ISP production

**ODP-A update (2026-06-15): Opsi A only — FieldOps REST API adalah single source of truth untuk ODP & ODC. Tidak ada ODP/ODC di IKR-ISP database lokal.**

---

## 1. Base URL

```
https://fieldops.sky.net.id/api
```

Set di `.env` VPS:
```
FIELDOPS_API_URL=https://fieldops.sky.net.id/api
FIELDOPS_API_KEY=<dari tim FieldOps, minta sekarang>
```

Auth header: `Authorization: Bearer <FIELDOPS_API_KEY>`

---

## 2. Endpoints yang dipakai IKR-ISP

### 2.1 `GET /odp-assets` — list ODP terdekat (radius dari titik GPS)

**Query params:**
- `lat` (required, float) — latitude titik pelanggan
- `lng` (required, float) — longitude titik pelanggan
- `radius` (optional, int, default 300) — radius dalam meter

**Expected response (200):**
```json
{
  "data": [
    {
      "id": 42,
      "code": "ODP-MLG-001",
      "name": "ODP Mangliawan 1",
      "lat": -7.9512,
      "lng": 112.6321,
      "distance_m": 87.3,
      "olt_id": 3,
      "odc_id": 5,
      "total_ports": 8,
      "used_ports": 5,
      "status": "active"
    }
  ]
}
```

**Field wajib (paling tidak):**
- `id` (int) — primary key
- `code` (string) — kode ODP, mis. `ODP-MLG-001` (dipake di PPPoE username)
- `name` (string) — display name
- `lat` (float)
- `lng` (float)

**Kalau ada** → dipakai di mapping:
- `olt_id` — foreign key ke OLT (referensi)
- `odc_id` — parent ODC
- `total_ports` / `used_ports` — capacity info

**Error responses:**
- 401 → API key salah/expired → cek `FIELDOPS_API_KEY`
- 500 → FieldOps down → IKR-ISP fallback chain

### 2.2 `GET /odp-assets/{id}` — detail 1 ODP

Dipakai saat teknisi mau lihat info lengkap ODP di step Coverage/Provisioning.

**Response:** sama shape dengan 2.1, single object di `data`.

### 2.3 `GET /odc-assets` — list ODC terdekat (ODP-A, baru)

**Query params:** sama dengan 2.1 (`lat`, `lng`, `radius`).

**Response:** sama shape dengan 2.1, field `id/code/name/lat/lng` wajib.

**Dipakai untuk:** visualisasi hierarki ODC → ODP → rumah di coverage map.

### 2.4 `GET /olt-assets` — list OLT (untuk dropdown)

**Query params:**
- `area_id` (optional, int)

**Response:**
```json
{
  "data": [
    {
      "id": 3,
      "name": "OLT C300 Mangliawan",
      "type": "c300",
      "ip": "10.10.10.3",
      "area_id": 1
    }
  ]
}
```

---

## 3. Test manual dari VPS

Setelah env di-set, test dari VPS:

```bash
ssh root@149.28.179.28

# 1. Test API direct
curl -H "Authorization: Bearer <API_KEY>" \
  "https://fieldops.sky.net.id/api/odp-assets?lat=-7.95&lng=112.63&radius=300"
# Expect: JSON {"data": [...]}

# 2. Test via IKR-ISP
cd /opt/ikr-isp
docker compose exec -T app php artisan tinker --execute="
\$svc = app(\App\Services\CoverageService::class);
\$odps = \$svc->findNearestOdps(-7.95, 112.63, 300);
echo 'ODP_count=' . count(\$odps) . PHP_EOL;
foreach (\$odps as \$o) {
  echo sprintf('  %s (%s) distance=%.1fm%s',
    \$o['code'], \$o['name'], \$o['distance_m'], PHP_EOL);
}
"

# 3. Test ODC (baru)
docker compose exec -T app php artisan tinker --execute="
\$svc = app(\App\Services\CoverageService::class);
\$odcs = \$svc->findNearestOdcs(-7.95, 112.63, 1000);
echo 'ODC_count=' . count(\$odcs) . PHP_EOL;
"
```

---

## 4. Fallback chain (kalau FieldOps down)

```
1. GET /odp-assets (FieldOps API)
   ├─ 200 + data → return sorted
   └─ 4xx/5xx/timeout → coba #2

2. SELECT odp_assets FROM fieldops DB (kalau config db.connections.fieldops ter-isi)
   ├─ rows found → return sorted by distance
   └─ fail / config empty → return []

3. UI tampil "Tidak ada ODP di radius X" — sales_leader approval manual (override)
```

---

## 5. Yang harus dimintain ke tim FieldOps

1. ✅ Production API key (Bearer token, gak expired)
2. ✅ Confirm endpoint `/odp-assets?lat=&lng=&radius=` exist & response shape match section 2.1
3. ✅ Confirm endpoint `/odc-assets?lat=&lng=&radius=` exist (ODP-A baru)
4. ✅ Confirm endpoint `/olt-assets` exist (untuk provisioning dropdown)
5. ✅ Rate limit info (jika ada, supaya gak kena throttle)
6. ⚠️  SLA uptime target (kalau FieldOps sering down, kita butuh fallback local DB)

---

## 6. Yang TIDAK boleh dilakukan IKR-ISP

❌ Query langsung ke DB FieldOps (read-only access mungkin OK, tapi API preferred)
❌ Cache ODP data > 1 jam (asset positions bisa berubah, teknisi perlu data fresh)
❌ Hardcode ODP list di IKR-ISP (single source of truth = FieldOps)

---

**Last updated: 2026-06-15 (ODP-A — FieldOps API only mode)**
