#!/bin/bash
# ============================================================
# Auto-deploy script — invoked by public/deploy-hook.php
# Энэ нь хурдан, хөнгөн (composer ажиллуулдаггүй).
# Том өөрчлөлт орвол гараар deploy.sh-г ажиллуулна уу.
# ============================================================
set -e

cd "$(dirname "$0")/.."
ROOT="$(pwd)"

echo ""
echo "=========================================="
echo "  Auto-Deploy — $(date '+%Y-%m-%d %H:%M:%S')"
echo "=========================================="
echo "Project: $ROOT"

# 0) Backup .env защитlах
if [ -f .env ]; then
    cp .env ".env.autobackup.$(date +%s)"
fi

# 1) Pull latest
echo "→ git pull origin master"
# git ажилллахын тулд safe.directory эзэмшилийн эрх www байх ёстой
git config --global --add safe.directory "$ROOT" 2>/dev/null || true
git pull origin master 2>&1 | tail -20

# 2) .env-г сэргээх (хэрэв git pull дарагдсан бол)
LATEST=$(ls -t .env.autobackup.* 2>/dev/null | head -1)
if [ -n "$LATEST" ] && ! grep -q "^APP_ENV=production" .env 2>/dev/null; then
    if grep -q "^APP_ENV=production" "$LATEST" 2>/dev/null; then
        cp "$LATEST" .env
        echo "→ .env restored from $LATEST"
    fi
fi

# 3) Хуучин backup-уудыг цэвэрлэх (5-аас илүү бол)
ls -t .env.autobackup.* 2>/dev/null | tail -n +6 | xargs -r rm -f

# 4) Caches цэвэрлэх + дахин build (config:cache config зэрэг хийнэ)
echo "→ artisan config:clear + view:clear + cache:clear"
php artisan config:clear 2>&1 | tail -2
php artisan view:clear 2>&1 | tail -2
php artisan cache:clear 2>&1 | tail -2

# 5) Production кэш build
echo "→ artisan config:cache + route:cache"
php artisan config:cache 2>&1 | tail -2
php artisan route:cache 2>&1 | tail -2 || echo "(route:cache skipped)"

# 6) Эрх тохируулах
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "✓ Deploy complete at $(date '+%H:%M:%S')"
echo ""
