#!/bin/bash
# ============================================================
# DONA B2B Trade - Server Deploy Script
# Сервер дээр нэг команд л ажиллуулна: ./deploy.sh
# ============================================================
set -e

cd "$(dirname "$0")"
echo "📁 Working dir: $(pwd)"

# ------------------------------------------------------------
# 0/8  🛡️  Backup .env ALWAYS (in case it's still tracked)
# ------------------------------------------------------------
if [ -f .env ]; then
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    echo "💾 .env backup created"
fi

echo ""
echo "============================================================"
echo " 1/8  🔄 Pulling latest from GitHub..."
echo "============================================================"
git pull origin master

echo ""
echo "============================================================"
echo " 2/8  🛡️  Restoring .env (if git pull overwrote it)..."
echo "============================================================"
# Шинэ commit-д .env untrack хийгдсэн тул дарагдахгүй.
# Гэхдээ хуучин commit-аар pull хийвэл дарагдах магадлалтай тул шалгая.
LATEST_BACKUP=$(ls -t .env.backup.* 2>/dev/null | head -1)
if [ -n "$LATEST_BACKUP" ] && [ -f "$LATEST_BACKUP" ]; then
    # Хэрэв одоогийн .env-д production-ийн чухал утга байхгүй бол backup-аас сэргээх
    if ! grep -q "^APP_ENV=production" .env 2>/dev/null; then
        if grep -q "^APP_ENV=production" "$LATEST_BACKUP" 2>/dev/null; then
            echo "⚠️  Current .env looks non-production, restoring from backup..."
            cp "$LATEST_BACKUP" .env
            echo "✅ .env restored from $LATEST_BACKUP"
        fi
    fi
fi

echo ""
echo "============================================================"
echo " 3/8  📦 Installing composer dependencies..."
echo "============================================================"
if ! command -v composer >/dev/null 2>&1; then
    echo "❌ composer not found. Please install composer first."
    exit 1
fi
composer install --no-dev --optimize-autoloader --no-interaction

echo ""
echo "============================================================"
echo " 4/8  🔐 Ensuring storage/installed exists..."
echo "============================================================"
if [ ! -f storage/installed ]; then
    echo "Laravel Installer successfully INSTALLED on $(date +'%Y/%m/%d %I:%M:%S%p')" > storage/installed
    echo "✅ Created storage/installed"
else
    echo "✅ storage/installed already exists"
fi
chmod 644 storage/installed

echo ""
echo "============================================================"
echo " 5/8  🧹 Clearing caches..."
echo "============================================================"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "============================================================"
echo " 6/8  ⚡ Building production cache..."
echo "============================================================"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "============================================================"
echo " 7/8  🗄️  Running migrations (safe, --force)..."
echo "============================================================"
php artisan migrate --force

echo ""
echo "============================================================"
echo " 8/8  🔧 Setting permissions..."
echo "============================================================"
chmod -R 775 storage bootstrap/cache 2>/dev/null || echo "⚠️  chmod skipped (permission denied — not critical)"

echo ""
echo "============================================================"
echo " ✅ DEPLOY COMPLETE!"
echo "============================================================"
echo "Одоо сайтаа шалгаж үзээрэй: https://dona-trade.com"
echo ""
echo "📌 .env backup файлууд:"
ls -la .env.backup.* 2>/dev/null | tail -5 || echo "   (байхгүй)"
