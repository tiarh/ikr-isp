# SECURITY.md

> Dokumen ini wajib dibaca sebelum production deploy. Mengacu ke alert GitGuardian + best practices untuk rotasi token.

---

## 1. Token Rotation (WAJIB setelah first deploy)

Semua kredensial di `.env` di-generate **unik per environment**. **JANGAN** copy `.env.production.example` apa adanya — ganti SEMUA placeholder berikut sebelum `php artisan migrate --seed`:

| Variable | Sumber | Cara Generate |
|---|---|---|
| `APP_KEY` | `php artisan key:generate` | Auto-generated |
| `DB_PASSWORD` | MySQL/VPS | `openssl rand -base64 32` |
| `SALESKIT_DB_PASSWORD` | Saleskit DB | dari admin Saleskit |
| `EBILLING_DB_PASSWORD` | eBilling DB | dari admin eBilling |
| `SALESKIT_API_KEY` | Saleskit app | create API key baru |
| `FIELDOPS_API_KEY` | FieldOps app | create API key baru |
| `EBILLING_API_KEY` | eBilling app | create API key baru |
| `OLT_C300_SSH_PASSWORD` | OLT device | dari NOC team |
| `MIKROTIK_API_PASSWORD` | MikroTik | `/user set name=api_rw password=...` |
| `EVOLUTION_API_KEY` | Evolution admin panel | create instance key |

Helper one-liner:
```bash
openssl rand -base64 32 | tr -d '\n' | pbcopy  # macOS
openssl rand -base64 32 | xclip -selection clipboard  # Linux
```

---

## 2. GitGuardian Alert Playbook

Kalo GitGuardian (atau tool scan lain) trigger alert di repo ini:

### Step A — Identifikasi
```bash
# Liat exact line yang di-flag
git log --all -p | grep -nE "PASSWORD|SECRET|TOKEN" | head

# Atau spesifik commit:
git show <commit-hash> | grep -A1 -B1 "PASSWORD"
```

### Step B — Fix
1. **Kalo placeholder** (string `CHANGE_ME`, `<DEMO_PASS>`, `xxx`): Mark sebagai "false positive" di GitGuardian dashboard, lalu tambahkan ke `detect-secrets` baseline (lihat §4).
2. **Kalo real secret**: **ROTATE** token di provider (Saleskit, eBilling, dll), lalu hapus dari git history (lihat §3).

### Step C — Prevent recurrence
- Tambah `detect-secrets` ke pre-commit hook
- Enable GitGuardian `auto-block` di branch protection
- Pakai **environment variables** (Vault, Doppler, AWS Secrets Manager) — jangan commit `.env`

---

## 3. Rewrite Git History (kalo ada real secret bocor)

```bash
# Install git-filter-repo (better than git filter-branch)
pip install git-filter-repo

# Remove sensitive file dari SEMUA commits
git filter-repo --path .env.example --invert-paths
# atau specific line
git filter-repo --replace-text "old-secret-text->REDACTED"

# Force push (akan break history — semua collaborator harus re-clone)
git push origin --force --all
```

**PENTING:** Setelah rewrite, semua developer/operator yang punya local clone HARUS:
```bash
rm -rf skynet-ikr
git clone https://github.com/tiarh/ikr-isp.git
```

---

## 4. detect-secrets Baseline

Repo ini punya baseline file `.secrets.baseline` (format JSON). Untuk update setelah perubahan:

```bash
# Install
pip install detect-secrets

# Scan & update baseline
detect-secrets scan --baseline .secrets.baseline
# atau auto-add new false-positives
detect-secrets scan --baseline .secrets.baseline --exclude-files 'README.md|DEPLOY.md|API.md|TODO.md|.env.example|.env.production.example'
```

Yang **sudah masuk baseline** (verified false-positives):
- `README.md` — string `bcrypt(...)` di code example
- `DEPLOY.md` — placeholder `<DEMO_PASS_FROM_SEEDER>`
- `API.md` — string `pppoe_secret` (bukan password)
- `database/seeders/DatabaseSeeder.php` — kolom `password` (column name, bukan value)
- `composer.json` — package name `barryvdh/laravel-dompdf` (false positive utk regex PDF)

---

## 5. Pre-commit Hook (recommended)

Tambah file `.git/hooks/pre-commit`:
```bash
#!/bin/sh
detect-secrets-hook --baseline .secrets.baseline
```

Atau pake Husky + lint-staged di `package.json` (kalo frontend project).

---

## 6. Demo Account Security (DB Seeder)

`database/seeders/DatabaseSeeder.php` generate random password per-user (`CHANGE_ME_xxxxxxxx`).
Password di-print ke console **sekali** pas seeding. Operator **WAJIB**:

1. Copy password dari output `db:seed`
2. Simpan di password manager (1Password, Bitwarden, etc)
3. Login ke `https://ikr.sky.net.id/admin/users` (Filament)
4. Ganti SEMUA password ke strong secret
5. Enable 2FA (TODO: integrasi ke TOTP jika diperlukan)

---

## 7. Incident Response (kalo ada real secret bocor)

1. **ROTATE** token di upstream provider dalam 1 jam
2. **Hapus** dari git history (lihat §3)
3. **Force push** ke GitHub
4. **Mark alert sebagai "Resolved"** di GitGuardian dashboard dgn note: "Token rotated, history rewritten"
5. **Audit logs** di provider — pastikan gak ada unauthorized access antara waktu leak & rotation
6. **Notify** semua user yg impact (kalau ada data leak)

---

## 8. Compliance

- **GDPR**: IKR-ISP simpan PII (nama, NIK, alamat, foto KTP). Pastikan ada retention policy (hapus order > 2 tahun, kecuali ada legal hold).
- **UU PDP Indonesia**: Same as GDPR, plus butuh explicit consent untuk BAI signature.
- **Audit trail**: Tabel `psb_status_logs` track semua perubahan. Export ke SIEM (Splunk/ELK) untuk compliance audit.

---

## 9. Network Security Checklist

- [ ] HTTPS only (Certbot + Let's Encrypt atau Coolify auto-SSL)
- [ ] HSTS header (Laravel 11: set `SESSION_SECURE_COOKIE=true`)
- [ ] Database di private network (VPC) — jangan expose port 3306 ke internet
- [ ] Redis di private network + password (`requirepass` di redis.conf)
- [ ] SSH key-only (disable password auth di `/etc/ssh/sshd_config`)
- [ ] Firewall: hanya 80/443 inbound ke public; SSH 22 dari trusted IP only
- [ ] WAF (Cloudflare) untuk DDoS protection
- [ ] Backup MySQL harian (mysqldump + encrypt) ke S3
- [ ] Log rotation (logrotate utk `/var/log/nginx` + laravel.log)

---

## 10. Reporting Security Issue

Kalo nemu vulnerability di IKR-ISP:
- Email: `security@sky.net.id` (atau kontak Tiar langsung via Telegram)
- **JANGAN** buka public GitHub issue untuk security report
- Encrypted reporting key: (TODO - generate PGP key)

Bug bounty: (TODO - consider offering bounty via HackerOne/Bugcrowd)
