---
id: quick-start
blueprint: documentation
title: 'Quick Start Guide'
updated_by: system
updated_at: 1738675127
---
# Quick Start Guide

Get your first Cartino store up and running in 15 minutes.

[TOC]

## Prerequisites

- Laravel 11 installed
- PHP 8.2+
- Database configured
- Node.js 18+

## Step 1: Install Cartino

```bash
composer require vitalijalbu/cartino
php artisan cartino:install
```

## Step 2: Create Your First Site

```php
use Shopper\Models\Site;

$site = Site::create([
    'handle' => 'default',
    'name' => 'My Store',
    'url' => 'https://mystore.com',
    'locale' => 'en',
    'default_currency' => 'EUR',
    'status' => 'active',
    'is_default' => true,
]);

// Create web channel
$site->channels()->create([
    'name' => 'Web Store',
    'slug' => 'web',
    'type' => 'web',
    'locales' => ['en'],
    'currencies' => ['EUR', 'USD'],
    'is_default' => true,
    'status' => 'active',
]);
```

## Step 3: Create Your First Product

```php
use Shopper\Models\Product;

$product = Product::create([
    'title' => 'Cotton T-Shirt',
    'slug' => 'cotton-t-shirt',
    'description' => 'Comfortable cotton t-shirt',
    'type' => 'physical',
    'status' => 'active',
    'published_at' => now(),
]);

// Add default variant
$variant = $product->variants()->create([
    'title' => 'Cotton T-Shirt',
    'sku' => 'TS-001',
    'price' => 29.99,
    'inventory_quantity' => 100,
]);
```

## Step 4: Access Admin Panel

Visit: `http://your-domain.com/admin`

Default credentials from installation.

## Step 5: Test Checkout

```php
// Add to cart
$cart = Cart::getOrCreate($customer, $site, $channel);
$cart->addItem($variant, 2);

// Calculate totals
$cart->calculate();

// Create order
$order = $cart->convertToOrder();
```

## Next Steps

- [Creating Products with Variants](/docs/guide-first-product)
- [Setting Up Multi-Site](/docs/guide-multi-site)
- [Configuring Shipping](/docs/guide-shipping-setup)
- [Payment Integration](/docs/advanced-payment-gateways)

## Common Issues

### Assets not loading
```bash
npm run build
php artisan cartino:build
```

### Database errors
```bash
php artisan migrate:fresh
php artisan db:seed
```

## Video Tutorial

[Watch our 10-minute quick start video]

## Community

- GitHub: [github.com/vitalijalbu/cartino](https://github.com/vitalijalbu/cartino)
- Discord: Join our community
- Docs: [cartino.dev/docs](https://cartino.dev/docs)
