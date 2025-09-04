#!/bin/bash

# 🚀 Smart URL Shortener - Deployment Preparation Script
# This script prepares your application for production deployment

echo "🔗 Smart URL Shortener - Deployment Preparation"
echo "=============================================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the project root."
    exit 1
fi

echo "📦 Installing production dependencies..."

# Install PHP dependencies (production only)
composer install --no-dev --optimize-autoloader --no-interaction

if [ $? -ne 0 ]; then
    echo "❌ Error: Composer install failed"
    exit 1
fi

# Install Node.js dependencies
npm ci

if [ $? -ne 0 ]; then
    echo "❌ Error: npm install failed"
    exit 1
fi

echo "🏗️  Building production assets..."

# Build production assets
npm run build

if [ $? -ne 0 ]; then
    echo "❌ Error: Asset build failed"
    exit 1
fi

echo "🔧 Optimizing Laravel..."

# Clear and cache config
php artisan config:clear
php artisan config:cache

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache views
php artisan view:clear
php artisan view:cache

# Optimize autoloader
php artisan optimize

echo "✅ Deployment preparation complete!"
echo ""
echo "📋 Next steps:"
echo "1. Commit and push your changes to Git"
echo "2. Deploy using your chosen platform:"
echo "   • Railway: Connect GitHub repo and deploy"
echo "   • Vercel: Run 'vercel --prod'"
echo "   • DigitalOcean: Push to connected repository"
echo ""
echo "🔐 Don't forget to set these environment variables in production:"
echo "   • APP_ENV=production"
echo "   • APP_DEBUG=false"
echo "   • APP_URL=https://yourdomain.com"
echo "   • Database credentials"
echo ""
echo "🎉 Happy deploying!"
