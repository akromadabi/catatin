#!/bin/bash
# Script Deployment untuk Catat-in

echo "=========================================="
echo "🚀 MEMULAI DEPLOYMENT CATAT-IN"
echo "=========================================="

# Pindah ke direktori aplikasi (sesuaikan jika path di aaPanel berbeda)
cd /www/wwwroot/catatin

echo "⬇️ 1. Menarik pembaruan terbaru dari GitHub..."
git pull origin main

echo "📦 2. Menginstall dependensi PHP (Composer)..."
# Gunakan flag --no-dev jika di production
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "📦 3. Menginstall dependensi Node.js (NPM)..."
npm install

echo "🛠️ 4. Membangun aset frontend (Vite)..."
npm run build

echo "🗄️ 5. Menjalankan migrasi database..."
php artisan migrate --force

echo "🧹 6. Membersihkan cache aplikasi..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=========================================="
echo "✅ DEPLOYMENT SELESAI DENGAN SUKSES!"
echo "=========================================="
