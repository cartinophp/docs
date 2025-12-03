---
id: installation
blueprint: documentation
title: Installation
updated_by: system
updated_at: 1738675127
---
# Installation

Get Cartino up and running in your Laravel application.

[TOC]

## System Requirements

Before installing Cartino, ensure your system meets these requirements:

| Requirement | Version |
|-------------|---------|
| PHP | 8.2+ |
| Laravel | 11.0+ |
| Node.js | 18.0+ |
| Database | MySQL 8.0+, PostgreSQL 14+, or SQLite |
| Composer | 2.0+ |

### Required PHP Extensions

- BCMath
- Ctype
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML

---

## Installation Methods

### Method 1: New Laravel Project (Recommended)

Create a fresh Laravel project with Cartino:

```bash
# Create new Laravel project
composer create-project laravel/laravel my-store
cd my-store

# Install Cartino
composer require vitalijalbu/cartino

# Run installation command
php artisan cartino:install
```

The installation command will:
- Publish configuration files
- Run database migrations
- Build frontend assets
- Create an admin user (interactive)
- Set up example data (optional)

### Method 2: Existing Laravel Project

Add Cartino to an existing Laravel application:

```bash
# Install package
composer require vitalijalbu/cartino

# Run installation
php artisan cartino:install --no-migrate

# Review migrations before running
php artisan migrate
```

---

## Installation Options

### Interactive Installation

```bash
php artisan cartino:install
```

This will prompt you for:
- Admin user credentials
- Sample data installation
- Site configuration

### Silent Installation

For automated deployments:

```bash
php artisan cartino:install \
  --admin-email=admin@example.com \
  --admin-name="Admin User" \
  --admin-password=secret \
  --no-interaction
```

### Development Installation

For development with hot reload:

```bash
# Install with dev dependencies
composer require vitalijalbu/cartino --dev

# Install frontend dependencies
npm install

# Start development servers
php artisan serve & npm run dev
```

---

## Post-Installation Steps

### 1. Configure Environment

Edit your `.env` file:

```env
# Application
APP_NAME="My Cartino Store"
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cartino
DB_USERNAME=root
DB_PASSWORD=

# Cartino
CARTINO_FIDELITY_ENABLED=true
CARTINO_FIDELITY_POINTS_ENABLED=true
CARTINO_DEFAULT_CURRENCY=EUR
```

### 2. Build Assets

For production:

```bash
# Build optimized assets
npm run build

# Or use Cartino command
php artisan cartino:build
```

### 3. Set Up Storage

```bash
# Create storage link
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache
```

### 4. Configure Queue Worker

Cartino uses queues for background processing:

```bash
# Start queue worker
php artisan queue:work

# Or use Supervisor for production
```

### 5. Access Admin Panel

Visit your admin panel:

```
http://your-domain.com/admin
```

Login with the credentials you created during installation.

---

## Verifying Installation

Check that everything is working:

```bash
# Run health check
php artisan cartino:health

# Expected output:
# ✓ Database connection
# ✓ Assets built
# ✓ Storage linked
# ✓ Admin user created
# ✓ Default site configured
```

---

## Troubleshooting

### Assets Not Loading

If assets aren't loading in the admin panel:

```bash
# Rebuild assets
npm run build
php artisan cartino:build

# Clear cache
php artisan cache:clear
php artisan config:clear
```

### Database Connection Issues

Check your database credentials in `.env`:

```bash
# Test database connection
php artisan migrate:status
```

### Permission Errors

Fix storage permissions:

```bash
# Linux/macOS
sudo chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Or with your user
sudo chown -R $USER:www-data storage bootstrap/cache
```

### Composer Memory Limit

If Composer runs out of memory:

```bash
php -d memory_limit=-1 /usr/local/bin/composer require vitalijalbu/cartino
```

---

## Docker Installation

Using Laravel Sail:

```bash
# Create new Laravel project with Sail
curl -s https://laravel.build/my-store | bash
cd my-store

# Start Sail
./vendor/bin/sail up -d

# Install Cartino
./vendor/bin/sail composer require vitalijalbu/cartino
./vendor/bin/sail artisan cartino:install
```

---

## Development vs Production

### Development Setup

```bash
# .env
APP_ENV=local
APP_DEBUG=true

# Start dev servers
php artisan serve
npm run dev
```

### Production Setup

```bash
# .env
APP_ENV=production
APP_DEBUG=false

# Optimize for production
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

---

## Next Steps

Now that Cartino is installed:

1. [Configuration](/docs/configuration) - Configure your store settings
2. [Quick Start](/docs/quick-start) - Create your first products
3. [Sites Architecture](/docs/sites-architecture) - Set up multi-site
4. [Blueprint System](/docs/blueprint-system) - Customize fields

---

## Updating Cartino

To update to the latest version:

```bash
composer update vitalijalbu/cartino
php artisan cartino:update
```

This will:
- Run new migrations
- Rebuild assets
- Clear caches

---

## Uninstalling

To remove Cartino:

```bash
# Remove package
composer remove vitalijalbu/cartino

# Optional: Remove migrations
php artisan migrate:rollback --path=vendor/vitalijalbu/cartino/database/migrations
```

> **Note**: This will not delete your data. Back up your database first!
