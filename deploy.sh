#!/bin/bash
# ============================================================================
# IKR-ISP Deploy Script — run once on VPS as root
# Usage:
#   curl -fsSL https://raw.githubusercontent.com/tiarh/ikr-isp/main/deploy.sh | bash
#   atau: bash deploy.sh
# ============================================================================
set -e

# ---- CONFIG ----
GITHUB_REPO="tiarh/ikr-isp"
GITHUB_BRANCH="main"
APP_DIR="/opt/ikr-isp"
APP_PORT=8080
DB_NAME="ikr_isp"
DB_USER="ikr_isp"
SKYNET_NETWORK="skynet-ebilling_default"  # existing bridge dari skynet-ebilling

# ---- COLORS ----
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log()  { echo -e "${GREEN}[+]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
err()  { echo -e "${RED}[x]${NC} $1"; exit 1; }

# ---- PRECHECK ----
[ "$EUID" -eq 0 ] || err "Harus run as root (sudo -i)"
command -v docker >/dev/null || err "Docker belum terinstall"
docker ps >/dev/null 2>&1 || err "Docker daemon gak running"

# ---- 1. CLONE REPO ----
if [ ! -d "$APP_DIR/.git" ]; then
    log "Cloning repo to $APP_DIR..."
    mkdir -p "$APP_DIR"
    git clone --branch "$GITHUB_BRANCH" --depth 1 "https://github.com/$GITHUB_REPO.git" "$APP_DIR"
else
    log "Updating existing repo..."
    cd "$APP_DIR"
    git fetch origin
    git reset --hard "origin/$GITHUB_BRANCH"
fi

cd "$APP_DIR"

# ---- 2. SETUP .env (interaktif, hanya first run) ----
if [ ! -f "$APP_DIR/.env" ]; then
    warn "File .env belum ada. Membuat dari template..."
    cp .env.production.example .env

    # Generate secure passwords
    DB_PASS=$(openssl rand -base64 24 | tr -d '\n=' | head -c 32)
    APP_KEY=$(openssl rand -base64 32 | tr -d '\n=' | head -c 32)

    # Update .env
    sed -i "s|^APP_KEY=.*|APP_KEY=base64:${APP_KEY}|" .env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
    sed -i "s|^REDIS_PASSWORD=.*|REDIS_PASSWORD=$(openssl rand -base64 24 | tr -d '\n=' | head -c 24)|" .env
    sed -i "s|^SESSION_DOMAIN=.*|SESSION_DOMAIN=|$|" .env  # IP-only, no domain

    warn ""
    warn "=================================================================="
    warn "  GENERATED SECRETS — SIMPAN di password manager SEKARANG"
    warn "=================================================================="
    warn "DB_PASSWORD:     ${DB_PASS}"
    warn "APP_KEY:         base64:${APP_KEY}"
    warn "=================================================================="
    warn ""
    warn "PRESS ENTER untuk lanjut (secrets udah di-save ke .env)..."
    read -r
fi

# ---- 3. SETUP MYSQL DATABASE (kalo belum ada) ----
log "Checking MySQL..."
if docker ps --format '{{.Names}}' | grep -q "skynet-ebilling-mysql-1"; then
    MYSQL_CONTAINER="skynet-ebilling-mysql-1"
elif docker ps --format '{{.Names}}' | grep -q "arya_charisa_mysql"; then
    MYSQL_CONTAINER="arya_charisa_mysql"
else
    err "MySQL container gak ketemu. Setup manual dulu."
fi

# Get root password
if [ -f "/root/skynet-secrets/.mysql-root" ]; then
    DB_ROOT_PASS=$(cat /root/skynet-secrets/.mysql-root)
else
    warn "MySQL root password? (kosongin kalo auto, atau paste)"
    read -s -p "MySQL root pass: " DB_ROOT_PASS
    echo ""
fi

log "Creating database $DB_NAME (jika belum ada)..."
docker exec "$MYSQL_CONTAINER" mysql -uroot -p"$DB_ROOT_PASS" \
    -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';
        GRANT ALL ON ${DB_NAME}.* TO '${DB_USER}'@'%';
        FLUSH PRIVILEGES;" 2>/dev/null || warn "Gagal create DB (mungkin sudah ada / permission issue)"

# ---- 4. CONNECT TO SKYNET-NETWORK ----
log "Connecting to existing skynet network..."
NETWORK_EXISTS=$(docker network ls --format '{{.Name}}' | grep -E "skynet-ebilling_default|skynet_default" | head -1 || echo "")
if [ -n "$NETWORK_EXISTS" ]; then
    log "Found existing skynet network: $NETWORK_EXISTS"
    sed -i "s|^skynet-shared:|skynet-shared:\n    external: true\n    name: $NETWORK_EXISTS|" docker-compose.prod.yml 2>/dev/null || true
fi

# ---- 5. BUILD & DEPLOY ----
log "Pulling image ghcr.io/${GITHUB_REPO}:latest..."
docker pull "ghcr.io/${GITHUB_REPO}:latest" || warn "Pull gagal (mungkin perlu login GHCR dulu: docker login ghcr.io)"

log "Starting IKR-ISP containers..."
docker compose -f docker-compose.prod.yml --env-file .env up -d --no-build

# ---- 6. WAIT FOR HEALTHY ----
log "Waiting for app to be healthy..."
for i in {1..30}; do
    sleep 2
    STATUS=$(docker inspect --format='{{.State.Health.Status}}' skynet-ikr-app 2>/dev/null || echo "starting")
    if [ "$STATUS" = "healthy" ]; then
        log "✅ App is healthy!"
        break
    fi
    if [ $i -eq 30 ]; then
        warn "App belum healthy setelah 60s. Cek logs: docker logs skynet-ikr-app"
    fi
done

# ---- 7. RUN MIGRATIONS ----
log "Running migrations..."
docker exec skynet-ikr-app php artisan migrate --force --no-interaction || warn "Migrate gagal (cek koneksi DB)"

log "Seeding default users..."
docker exec skynet-ikr-app php artisan db:seed --force --no-interaction || warn "Seed gagal (skip kalo udah pernah)"

log "Storage link..."
docker exec skynet-ikr-app php artisan storage:link || true

# ---- 8. VERIFY ----
log "Verifying deployment..."
sleep 3
HTTP_CODE=$(curl -s -o /dev/null -w '%{http_code}' "http://localhost:${APP_PORT}/up" || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    log "✅ Health check OK: http://localhost:${APP_PORT}/up → 200"
else
    warn "Health check: ${HTTP_CODE} (cek docker logs skynet-ikr-app)"
fi

# ---- 9. INFO ----
# Auto-detect public IP (kalo ada, fallback ke placeholder)
PUBLIC_IP=$(curl -fsS --max-time 3 https://ifconfig.me 2>/dev/null || echo "<your-vps-ip>")

echo ""
echo "=================================================================="
echo "  IKR-ISP DEPLOYED"
echo "=================================================================="
echo "  URL:       http://${PUBLIC_IP}:${APP_PORT}"
echo "  Container: skynet-ikr-app"
echo "  Logs:      docker logs -f skynet-ikr-app"
echo "  Tinker:    docker exec -it skynet-ikr-app php artisan tinker"
echo "  Migrate:   docker exec skynet-ikr-app php artisan migrate"
echo "  Restart:   docker compose -f /opt/ikr-isp/docker-compose.prod.yml restart app"
echo ""
echo "  DEFAULT LOGIN: lihat output 'php artisan db:seed' — COPY password"
echo "                GANTI setelah first login via /admin/users (Filament)"
echo "=================================================================="
