# BELUM DIKERJAKAN / TODO

Item-item yang masih perlu lo handle sebelum production:

## Di eBilling (WAJIB)
- [ ] Buat endpoint `GET /api/teknisi` (return list teknisi + open_ticket_count)
- [ ] Pastikan endpoint `POST /api/customers` support field `nik`, `router_name`, `olt_id`, `odp_id`
- [ ] Pastikan endpoint `POST /api/customers/{id}/invoice` support `join_date` (default = today)
- [ ] Pastikan endpoint `POST /api/radius/accounts` support `groupname`
- [ ] Pastikan endpoint `POST /api/mikrotik/secret` ada & tested
- [ ] Pastikan endpoint `POST /api/customers/{id}/files` (multipart upload) tested

## Di FieldOps (WAJIB)
- [ ] Endpoint `GET /api/odp-assets?lat=&lng=&radius=` return data sesuai format (id, code, name, lat, lng)
- [ ] Endpoint `GET /api/olt-assets?area_id=` return list OLT
- [ ] Bearer token diset di FieldOps utk IKR-ISP

## Di Saleskit (PATCH migration)
- [ ] Run migration `2026_06_14_000006_patch_saleskit_add_router_name.php` di connection saleskit
      ATAU run manual: `ALTER TABLE skynet_saleskit.registrations ADD COLUMN router_name VARCHAR(100) NULL AFTER package`
- [ ] Update Saleskit form input registration → tambahkan field "Nama Router" (wajib)

## Di MikroTik
- [ ] Test koneksi SSH ke OLT C300 (phpseclib3\Net\SSH2)
- [ ] Test RouterOS API utk add PPPoE secret
- [ ] Verify PPPoE profile "paket-10M" dll udah dibuat di MikroTik

## OLT C300 SSH commands (perlu disesuaikan dgn firmware ZTE actual)
Edit `app/Services/OltService.php` bagian `C300Provisioner::provision()`. Command yang ditulis di sana adalah placeholder — perlu disesuaikan dgn syntax OLT ZTE C300 aktual:
- `show gpon onu by sn {sn}`
- `interface gpon {port}`
- `onu {id} sn {sn}`
- `vlan {vlan}`
- `service-port ...`
- `pon-onu-mng ...`

## Coolify Deploy
- [ ] Set env vars (lihat DEPLOY.md)
- [ ] Setup Redis service
- [ ] Setup queue worker systemd
- [ ] Setup scheduler systemd
- [ ] Setup cron job untuk backup
- [ ] SSL via Certbot
- [ ] Domain `ikr.sky.net.id` pointing ke VPS

## Tweak setelah running
- [ ] Map eBilling packages dgn IKR-ISP packages (`EbillingBridgeService::mapPackageToRadiusGroup`)
- [ ] Adjust PPPoE profile names di MikroTik
- [ ] Custom BAI PDF branding (header logo skynet)
- [ ] Custom WA message templates
- [ ] Setup backup MySQL (cron mysqldump)
- [ ] Setup monitoring (Grafana / Laravel Telescope)
