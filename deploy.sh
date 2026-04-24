#!/bin/bash
# ============================================================
# DONA B2B Trade - Server Deploy Script
# Сервер дээр нэг команд л ажиллуулна: ./deploy.sh
# ============================================================
set -e

cd "$(dirname "$0")"
echo "📁 Working dir: $(pwd)"

echo ""
echo "============================================================"
echo " 1/7  🔄 Pulling latest from GitHub..."
echo "============================================================"
git pull origin master

echo ""
echo "============================================================"
echo " 2/7  📦 Installing composer dependencies..."
echo "============================================================"
if ! command -v composer >/dev/null 2>&1; then
    echo "❌ composer not found. Please install composer first."
    exit 1
fi
composer install --no-dev --optimize-autoloader --no-interaction

echo ""
echo "============================================================"
echo " 3/7  🔐 Ensuring storage/installed exists..."
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
echo " 4/7  🧹 Clearing caches..."
echo "============================================================"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "============================================================"
echo " 5/7  ⚡ Building production cache..."
echo "============================================================"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "============================================================"
echo " 6/7  🗄️  Running migrations (safe, --force)..."
echo "============================================================"
php artisan migrate --force

echo ""
echo "============================================================"
echo " 7/7  🔧 Setting permissions..."
echo "============================================================"
chmod -R 775 storage bootstrap/cache 2>/dev/null || echo "⚠️  chmod skipped (permission denied — not critical)"

echo ""
echo "============================================================"
echo " ✅ DEPLOY COMPLETE!"
echo "============================================================"
echo "Одоо сайтаа шалгаж үзээрэй: https://dona-trade.com"
