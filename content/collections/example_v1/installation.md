---
id: b5d31cb4-744b-4415-9720-49c53ca3b79a
blueprint: example_v1
title: Installation
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264233
---
# Installation

Get Cartino up and running in your local environment.

[TOC]

## Requirements

Before installing Cartino, ensure your system meets these requirements:

### Server Requirements
- **PHP** 8.3 or higher
- **Composer** 2.0 or higher
- **Node.js** 18.0 or higher
- **NPM** or **Yarn**

### PHP Extensions
- BCMath
- Ctype
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- GD or Imagick (for image processing)
- fileinfo

### Database
- **MySQL** 8.0+ or **MariaDB** 10.3+
- **PostgreSQL** 13+ (recommended for JSONB support)

---

## Installation Methods

### Method 1: Clone the Repository (Recommended)

Clone the Cartino repository and set it up:

```bash
# Clone the repository
git clone https://github.com/cartinophp/cartino.git
cd cartino

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Method 2: Using Composer Create-Project

```bash
composer create-project cartino/cartino my-shop
cd my-shop
npm install
```

---

## Configuration

### 1. Environment Setup

Edit your `.env` file with your database credentials:

```env
APP_NAME=Cartino
APP_ENV=local
APP_KEY=base64:... # Generated automatically
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=pgsql  # or mysql
DB_HOST=127.0.0.1
DB_PORT=5432         # 3306 for MySQL
DB_DATABASE=cartino
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Multi-site Configuration
CARTINO_FIDELITY_ENABLED=true
CARTINO_FIDELITY_POINTS_ENABLED=true

# Asset Configuration
CARTINO_DEV=true  # Enable during development
```

### 2. Create Database

Create your database:

```bash
# PostgreSQL
createdb cartino

# MySQL
mysql -u root -p -e "CREATE DATABASE cartino CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Run Migrations

Run the database migrations:

```bash
php artisan migrate
```

This will create all necessary tables including:
- Products & variants
- Sites, channels, catalogs
- Customers & orders
- Inventory management
- Loyalty system
- And more...

### 4. Seed Database (Optional)

Populate your database with sample data:

```bash
php artisan db:seed
```

This creates:
- Sample sites and channels
- Product types and brands
- Categories and products
- Customer groups
- Demo customers
- Sample orders

---

## Asset Building

Cartino uses **Vite** for frontend asset compilation.

### Development Mode

For local development with hot module replacement:

```bash
# Start Vite dev server
npm run dev
```

Or with Cartino development mode:

```bash
# Build assets to public/build with hot reload
CARTINO_DEV=true npm run dev
```

### Production Build

Build optimized assets for production:

```bash
# Build assets to public/vendor/shopper
npm run build

# Or using Artisan command
php artisan cartino:build
```

---

## Initial Setup

### 1. Create Admin User

Create your first admin user:

```bash
php artisan cartino:make:user
```

Follow the prompts to enter:
- First name
- Last name
- Email
- Password

### 2. Configure Default Site

Access the admin panel and configure your default site:

```
http://localhost:8000/admin
```

Navigate to **Settings > Sites** and configure:
- Site name and handle
- Default currency
- Supported countries
- Tax settings
- Locale preferences

### 3. Create Your First Product

Navigate to **Products > Add Product** and create your first product:

1. Enter product details (title, description)
2. Set product type and brand
3. Add product variants (or use default)
4. Set pricing
5. Configure inventory
6. Add images
7. Publish!

---

## Local Development Server

Start the Laravel development server:

```bash
php artisan serve
```

Your Cartino installation will be available at:
- **Frontend**: `http://localhost:8000`
- **Admin Panel**: `http://localhost:8000/admin`

For a better development experience, use **Laravel Valet** (macOS) or **Laravel Homestead**:

### Using Laravel Valet

```bash
# Install Valet globally
composer global require laravel/valet
valet install

# Park your Cartino directory
cd ~/Sites/cartino
valet park

# Access at http://cartino.test
```

### Using Laravel Sail (Docker)

```bash
# Install Sail
composer require laravel/sail --dev

# Publish Sail configuration
php artisan sail:install

# Start Sail
./vendor/bin/sail up -d
```

---

## Verifying Installation

Check that everything is working:

### 1. Check System Status

```bash
php artisan about
```

### 2. Run Tests

```bash
# Run PHP tests
php artisan test

# Or using PHPUnit
./vendor/bin/phpunit
```

### 3. Check Asset Compilation

Visit the admin panel and verify assets are loading correctly.

---

## Troubleshooting

### Permission Issues

Set proper permissions on storage and cache directories:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache  # Linux
chown -R _www:_www storage bootstrap/cache           # macOS
```

### Database Connection Failed

- Verify database credentials in `.env`
- Ensure database exists
- Check database server is running
- Test connection: `php artisan db:show`

### Asset Compilation Errors

```bash
# Clear and rebuild
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Cache Issues

Clear all caches:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Next Steps

Now that Cartino is installed:

1. [**Product Architecture**](/example/1.x/product-architecture) - Understand the product/variant system
2. [**Sites & Markets**](/example/1.x/sites-architecture) - Configure multi-site setup
3. [**Blueprint System**](/example/1.x/blueprint-system) - Customize content fields
4. [**Development**](/example/1.x/development) - Start building with Cartino

---

## Updating Cartino

To update your Cartino installation:

```bash
# Pull latest changes
git pull origin main

# Update dependencies
composer update
npm update

# Run migrations
php artisan migrate

# Rebuild assets
npm run build

# Clear caches
php artisan optimize:clear
php artisan optimize
```
