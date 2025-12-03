---
id: model-channel
blueprint: documentation
title: 'Model: Channel'
updated_by: system
updated_at: 1738675127
---
# Model: Channel

The Channel model represents a sales method within a Site. Channels allow you to manage different selling points like web stores, mobile apps, POS systems, and marketplaces.

[TOC]

## Overview

A **Channel** represents a sales method or distribution channel within a site. Each site can have multiple channels, each with its own configuration:

```php
Channel {
    site_id: 1
    name: "Web Store"
    slug: "web"
    type: "web"
    locales: ['en', 'it', 'fr']
    currencies: ['EUR', 'USD', 'GBP']
    is_default: true
    status: "active"
}
```

**Common Channel Types**:
- **web** - E-commerce website
- **mobile** - Mobile application
- **pos** - Point of sale (physical stores)
- **b2b_portal** - Business-to-business portal
- **marketplace** - Third-party marketplace (Amazon, eBay)
- **social** - Social commerce (Instagram, Facebook)
- **api** - Headless API integrations

---

## Database Schema

### `channels` Table

```php
Schema::create('channels', function (Blueprint $table) {
    $table->id();

    // Parent Site
    $table->foreignId('site_id')->constrained()->cascadeOnDelete();

    // Identity
    $table->string('name'); // Display name
    $table->string('slug')->unique(); // URL-friendly identifier
    $table->text('description')->nullable();

    // Channel Type
    $table->string('type'); // web, mobile, pos, marketplace, b2b_portal, social, api

    // URL Configuration
    $table->string('url')->nullable(); // Channel-specific URL

    // Status & Priority
    $table->boolean('is_default')->default(false); // Default channel for site
    $table->string('status')->default('active'); // active, inactive, maintenance

    // Multi-locale Support
    $table->json('locales')->nullable(); // ['en', 'it', 'fr']

    // Multi-currency Support
    $table->json('currencies')->nullable(); // ['EUR', 'USD', 'GBP']

    // Channel-Specific Settings
    $table->json('settings')->nullable(); // Configuration data

    // Custom Fields (JSONB)
    $table->json('data')->nullable();

    // Timestamps
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('site_id');
    $table->index('slug');
    $table->index('type');
    $table->index('status');
    $table->index(['site_id', 'is_default']);
});
```

---

## Properties

### Core Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | bigint | Primary key |
| `site_id` | foreignId | Parent site |
| `name` | string | Display name |
| `slug` | string | Unique identifier |
| `description` | text | Channel description |
| `type` | string | Channel type enum |
| `url` | string | Optional channel URL |
| `is_default` | boolean | Default channel flag |
| `status` | string | `active`, `inactive`, `maintenance` |

### Configuration

| Property | Type | Description |
|----------|------|-------------|
| `locales` | json | Supported locales array |
| `currencies` | json | Supported currencies array |
| `settings` | json | Channel-specific config |

### Relationships

| Relation | Type | Description |
|----------|------|-------------|
| `site` | belongsTo | Parent site |
| `prices` | hasMany | VariantPrice |
| `orders` | hasMany | Order |
| `shippingRates` | hasMany | ShippingRate |

---

## Eloquent Model

### Basic Usage

```php
use Shopper\Models\Channel;
use Shopper\Models\Site;

// Create channel
$site = Site::find(1);

$channel = $site->channels()->create([
    'name' => 'Web Store',
    'slug' => 'web',
    'type' => 'web',
    'locales' => ['en', 'it', 'fr'],
    'currencies' => ['EUR', 'USD', 'GBP'],
    'is_default' => true,
    'status' => 'active',
]);

// Or directly
$channel = Channel::create([
    'site_id' => 1,
    'name' => 'Mobile App',
    'slug' => 'mobile',
    'type' => 'mobile',
    'locales' => ['en', 'it'],
    'currencies' => ['EUR'],
    'status' => 'active',
]);

// Find by ID
$channel = Channel::find(1);

// Find by slug
$channel = Channel::where('slug', 'web')->first();

// Update
$channel->update(['status' => 'maintenance']);

// Soft delete
$channel->delete();
```

---

## Relationships

### Site

```php
// Get parent site
$site = $channel->site;

// Channel's site details
$siteName = $channel->site->name;
$defaultCurrency = $channel->site->default_currency;

// All channels for a site
$channels = $site->channels;
```

### Prices

```php
// Get all prices for this channel
$prices = $channel->prices;

// Prices in specific currency
$eurPrices = $channel->prices()
    ->where('currency', 'EUR')
    ->get();

// Create price for channel
$variant->prices()->create([
    'site_id' => $channel->site_id,
    'channel_id' => $channel->id,
    'currency' => 'EUR',
    'price' => 19.99,
]);

// Get price for variant in this channel
$price = $channel->prices()
    ->where('product_variant_id', $variantId)
    ->where('currency', 'EUR')
    ->first();
```

### Orders

```php
// Get orders for this channel
$orders = $channel->orders;

// Orders count
$orderCount = $channel->orders()->count();

// Revenue by channel
$revenue = $channel->orders()
    ->where('status', 'completed')
    ->sum('total');

// Today's orders
$todayOrders = $channel->orders()
    ->whereDate('created_at', today())
    ->get();
```

### Shipping Rates

```php
// Get shipping rates for channel
$rates = $channel->shippingRates;

// Active rates only
$activeRates = $channel->shippingRates()
    ->where('is_active', true)
    ->get();

// Create shipping rate
$channel->shippingRates()->create([
    'shipping_zone_id' => $zoneId,
    'name' => 'Standard Shipping',
    'price' => 5.99,
    'currency' => 'EUR',
]);
```

---

## Scopes

### Query Scopes

```php
// Active channels
Channel::active()->get();

// By type
Channel::ofType('web')->get();

// By site
Channel::forSite($siteId)->get();

// Default channels
Channel::default()->get();

// By status
Channel::status('active')->get();

// With prices
Channel::with('prices')->get();
```

### Scope Definitions

```php
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

public function scopeOfType($query, string $type)
{
    return $query->where('type', $type);
}

public function scopeForSite($query, int $siteId)
{
    return $query->where('site_id', $siteId);
}

public function scopeDefault($query)
{
    return $query->where('is_default', true);
}

public function scopeStatus($query, string $status)
{
    return $query->where('status', $status);
}
```

---

## Accessors & Mutators

### Accessors

```php
// Check if channel is active
public function getIsActiveAttribute(): bool
{
    return $this->status === 'active';
}

// Check if supports locale
public function getSupportsLocaleAttribute(): \Closure
{
    return fn(string $locale) => in_array($locale, $this->locales ?? []);
}

// Check if supports currency
public function getSupportsCurrencyAttribute(): \Closure
{
    return fn(string $currency) => in_array($currency, $this->currencies ?? []);
}

// Get default locale
public function getDefaultLocaleAttribute(): string
{
    return $this->locales[0] ?? $this->site->locale ?? 'en';
}

// Get default currency
public function getDefaultCurrencyAttribute(): string
{
    return $this->currencies[0] ?? $this->site->default_currency;
}

// Get full URL
public function getFullUrlAttribute(): string
{
    return $this->url ?? $this->site->url;
}

// Usage
if ($channel->is_active) { ... }
if (($channel->supports_locale)('it')) { ... }
echo $channel->default_locale;
```

### Mutators

```php
// Normalize slug
public function setSlugAttribute($value)
{
    $this->attributes['slug'] = Str::slug($value);
}

// Ensure only one default per site
public function setIsDefaultAttribute($value)
{
    if ($value) {
        static::where('site_id', $this->site_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
    }

    $this->attributes['is_default'] = $value;
}

// Validate locales
public function setLocalesAttribute($value)
{
    if (is_string($value)) {
        $value = json_decode($value, true);
    }

    $this->attributes['locales'] = json_encode(array_values(array_unique($value)));
}

// Validate currencies
public function setCurrenciesAttribute($value)
{
    if (is_string($value)) {
        $value = json_decode($value, true);
    }

    // Convert to uppercase
    $value = array_map('strtoupper', $value);

    $this->attributes['currencies'] = json_encode(array_values(array_unique($value)));
}
```

---

## Methods

### Locale Management

```php
// Check if locale is supported
public function supportsLocale(string $locale): bool
{
    return in_array($locale, $this->locales ?? []);
}

// Add locale
public function addLocale(string $locale): void
{
    $locales = $this->locales ?? [];
    $locales[] = $locale;
    $this->update(['locales' => array_unique($locales)]);
}

// Remove locale
public function removeLocale(string $locale): void
{
    $locales = array_filter(
        $this->locales ?? [],
        fn($l) => $l !== $locale
    );
    $this->update(['locales' => array_values($locales)]);
}

// Get available locales
public function getAvailableLocales(): array
{
    return $this->locales ?? [$this->site->locale];
}
```

### Currency Management

```php
// Check if currency is supported
public function supportsCurrency(string $currency): bool
{
    $currency = strtoupper($currency);
    return in_array($currency, $this->currencies ?? []);
}

// Add currency
public function addCurrency(string $currency): void
{
    $currency = strtoupper($currency);
    $currencies = $this->currencies ?? [];
    $currencies[] = $currency;
    $this->update(['currencies' => array_unique($currencies)]);
}

// Remove currency
public function removeCurrency(string $currency): void
{
    $currency = strtoupper($currency);
    $currencies = array_filter(
        $this->currencies ?? [],
        fn($c) => $c !== $currency
    );
    $this->update(['currencies' => array_values($currencies)]);
}

// Get available currencies
public function getAvailableCurrencies(): array
{
    return $this->currencies ?? [$this->site->default_currency];
}
```

### Status Management

```php
// Activate channel
public function activate(): void
{
    $this->update(['status' => 'active']);
}

// Deactivate channel
public function deactivate(): void
{
    $this->update(['status' => 'inactive']);
}

// Set maintenance mode
public function setMaintenance(): void
{
    $this->update(['status' => 'maintenance']);
}

// Check if in maintenance
public function isInMaintenance(): bool
{
    return $this->status === 'maintenance';
}
```

### Settings Management

```php
// Get setting
public function getSetting(string $key, $default = null)
{
    return data_get($this->settings, $key, $default);
}

// Set setting
public function setSetting(string $key, $value): void
{
    $settings = $this->settings ?? [];
    data_set($settings, $key, $value);
    $this->update(['settings' => $settings]);
}

// Has setting
public function hasSetting(string $key): bool
{
    return data_get($this->settings, $key) !== null;
}

// Example settings:
// - api_key
// - webhook_url
// - integration_settings
// - theme_config
```

### Analytics

```php
// Get channel statistics
public function getStatistics(string $period = '30d'): array
{
    $startDate = now()->sub($period);

    return [
        'orders_count' => $this->orders()
            ->where('created_at', '>=', $startDate)
            ->count(),

        'revenue' => $this->orders()
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('total'),

        'average_order_value' => $this->orders()
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->avg('total'),

        'conversion_rate' => $this->calculateConversionRate($startDate),
    ];
}

// Calculate conversion rate
protected function calculateConversionRate(\DateTime $startDate): float
{
    // Implementation depends on tracking system
    return 0.0;
}
```

---

## Channel Types

### Channel Type Definitions

```php
namespace Shopper\Enums;

enum ChannelType: string
{
    case WEB = 'web';
    case MOBILE = 'mobile';
    case POS = 'pos';
    case B2B_PORTAL = 'b2b_portal';
    case MARKETPLACE = 'marketplace';
    case SOCIAL = 'social';
    case API = 'api';

    public function label(): string
    {
        return match($this) {
            self::WEB => 'Web Store',
            self::MOBILE => 'Mobile App',
            self::POS => 'Point of Sale',
            self::B2B_PORTAL => 'B2B Portal',
            self::MARKETPLACE => 'Marketplace',
            self::SOCIAL => 'Social Commerce',
            self::API => 'API Integration',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::WEB => 'globe',
            self::MOBILE => 'device-mobile',
            self::POS => 'building-storefront',
            self::B2B_PORTAL => 'briefcase',
            self::MARKETPLACE => 'shopping-cart',
            self::SOCIAL => 'share',
            self::API => 'code-bracket',
        };
    }
}
```

### Channel-Specific Configuration

```php
// Web Store Channel
$webChannel = Channel::create([
    'name' => 'Web Store',
    'type' => 'web',
    'settings' => [
        'theme' => 'default',
        'google_analytics' => 'UA-XXXXX',
        'facebook_pixel' => 'XXXXX',
        'checkout_flow' => 'single_page',
        'guest_checkout' => true,
    ],
]);

// POS Channel
$posChannel = Channel::create([
    'name' => 'Retail Store',
    'type' => 'pos',
    'settings' => [
        'location_id' => 1,
        'printer_ip' => '192.168.1.100',
        'cash_drawer' => true,
        'receipt_template' => 'default',
        'offline_mode' => true,
    ],
]);

// Marketplace Channel
$marketplaceChannel = Channel::create([
    'name' => 'Amazon',
    'type' => 'marketplace',
    'settings' => [
        'marketplace' => 'amazon',
        'seller_id' => 'XXXXX',
        'api_key' => 'XXXXX',
        'fulfillment' => 'FBA',
        'sync_inventory' => true,
    ],
]);

// B2B Portal Channel
$b2bChannel = Channel::create([
    'name' => 'B2B Portal',
    'type' => 'b2b_portal',
    'settings' => [
        'require_approval' => true,
        'credit_limit' => true,
        'min_order_value' => 500,
        'payment_terms' => 'net30',
        'volume_discounts' => true,
    ],
]);
```

---

## Events

### Model Events

```php
use Shopper\Events\ChannelCreated;
use Shopper\Events\ChannelUpdated;
use Shopper\Events\ChannelActivated;

Event::listen(ChannelCreated::class, function ($event) {
    $channel = $event->channel;

    // Initialize default settings
    // Create default shipping rates
    // Set up analytics tracking
});

Event::listen(ChannelActivated::class, function ($event) {
    $channel = $event->channel;

    // Send notifications
    // Clear cache
    // Update integrations
});
```

### Eloquent Events

```php
protected static function booted()
{
    static::creating(function ($channel) {
        // Ensure unique slug per site
        if (empty($channel->slug)) {
            $channel->slug = Str::slug($channel->name);
        }

        // Inherit site settings if not specified
        if (empty($channel->locales)) {
            $channel->locales = [$channel->site->locale];
        }

        if (empty($channel->currencies)) {
            $channel->currencies = [$channel->site->default_currency];
        }
    });

    static::saved(function ($channel) {
        // Clear cache
        Cache::forget("channel.{$channel->id}");
        Cache::forget("site.{$channel->site_id}.channels");
    });

    static::deleting(function ($channel) {
        // Prevent deleting default channel
        if ($channel->is_default && $channel->site->channels()->count() > 1) {
            throw new \RuntimeException('Cannot delete default channel. Set another channel as default first.');
        }

        // Delete related data
        $channel->prices()->delete();
        $channel->shippingRates()->delete();
    });
}
```

---

## API Endpoints

### REST API

```http
# List channels
GET /api/channels

# List channels for site
GET /api/sites/{siteId}/channels

# Get single channel
GET /api/channels/{id}

# Create channel
POST /api/sites/{siteId}/channels

# Update channel
PUT /api/channels/{id}

# Delete channel
DELETE /api/channels/{id}

# Activate channel
POST /api/channels/{id}/activate

# Deactivate channel
POST /api/channels/{id}/deactivate

# Get channel statistics
GET /api/channels/{id}/statistics
```

### Request/Response Examples

```http
# Create channel
POST /api/sites/1/channels
Content-Type: application/json

{
  "name": "Mobile App",
  "slug": "mobile",
  "type": "mobile",
  "locales": ["en", "it"],
  "currencies": ["EUR", "USD"],
  "is_default": false,
  "status": "active",
  "settings": {
    "app_version": "2.0.0",
    "push_notifications": true
  }
}

# Response
{
  "data": {
    "id": 2,
    "site_id": 1,
    "name": "Mobile App",
    "slug": "mobile",
    "type": "mobile",
    "locales": ["en", "it"],
    "currencies": ["EUR", "USD"],
    "is_default": false,
    "status": "active",
    "default_locale": "en",
    "default_currency": "EUR",
    "created_at": "2025-12-03T10:00:00Z"
  }
}
```

### GraphQL API

```graphql
# Query channel
query {
  channel(id: 2) {
    id
    name
    slug
    type
    status
    locales
    currencies
    default_locale
    default_currency
    is_active
    site {
      id
      name
    }
    statistics(period: "30d") {
      orders_count
      revenue
      average_order_value
    }
  }
}

# Mutation - Create channel
mutation {
  createChannel(input: {
    site_id: 1
    name: "B2B Portal"
    slug: "b2b"
    type: "b2b_portal"
    locales: ["en"]
    currencies: ["EUR"]
  }) {
    channel {
      id
      name
      type
    }
  }
}

# Mutation - Update settings
mutation {
  updateChannelSettings(
    id: 2
    settings: {
      theme: "dark"
      checkout_flow: "multi_step"
    }
  ) {
    channel {
      id
      settings
    }
  }
}
```

---

## Examples

### Creating Channels for Different Use Cases

```php
// E-commerce Website
$webChannel = $site->channels()->create([
    'name' => 'Web Store',
    'slug' => 'web',
    'type' => 'web',
    'url' => 'https://shop.example.com',
    'locales' => ['en', 'it', 'fr', 'de'],
    'currencies' => ['EUR', 'USD', 'GBP'],
    'is_default' => true,
    'settings' => [
        'theme' => 'modern',
        'google_analytics' => 'UA-XXXXX',
        'facebook_pixel' => 'XXXXX',
        'guest_checkout' => true,
        'newsletter_popup' => true,
    ],
]);

// Mobile App
$mobileChannel = $site->channels()->create([
    'name' => 'iOS & Android App',
    'slug' => 'mobile',
    'type' => 'mobile',
    'locales' => ['en', 'it'],
    'currencies' => ['EUR'],
    'settings' => [
        'app_version' => '2.0.0',
        'push_notifications' => true,
        'biometric_auth' => true,
        'offline_mode' => true,
    ],
]);

// Physical Store POS
$posChannel = $site->channels()->create([
    'name' => 'Milan Store',
    'slug' => 'milan-pos',
    'type' => 'pos',
    'locales' => ['it', 'en'],
    'currencies' => ['EUR'],
    'settings' => [
        'location_id' => 1,
        'printer_ip' => '192.168.1.100',
        'cash_drawer' => true,
        'barcode_scanner' => true,
        'offline_mode' => true,
        'receipt_template' => 'milan-store',
    ],
]);

// B2B Wholesale Portal
$b2bChannel = $site->channels()->create([
    'name' => 'B2B Wholesale',
    'slug' => 'b2b',
    'type' => 'b2b_portal',
    'locales' => ['en', 'it'],
    'currencies' => ['EUR', 'USD'],
    'settings' => [
        'require_approval' => true,
        'credit_limit' => true,
        'min_order_value' => 1000,
        'payment_terms' => ['net30', 'net60', 'net90'],
        'volume_discounts' => true,
        'quote_requests' => true,
        'bulk_ordering' => true,
    ],
]);

// Amazon Marketplace
$amazonChannel = $site->channels()->create([
    'name' => 'Amazon IT',
    'slug' => 'amazon-it',
    'type' => 'marketplace',
    'locales' => ['it'],
    'currencies' => ['EUR'],
    'settings' => [
        'marketplace' => 'amazon',
        'marketplace_id' => 'APJ6JRA9NG5V4',
        'seller_id' => 'XXXXX',
        'api_key' => env('AMAZON_API_KEY'),
        'fulfillment' => 'FBA',
        'sync_inventory' => true,
        'sync_prices' => true,
        'auto_import_orders' => true,
    ],
]);
```

### Channel Detection Middleware

```php
// DetectChannel Middleware
public function handle($request, Closure $next)
{
    $site = currentSite();

    // Detect channel from request
    $channelSlug = $request->header('X-Channel')
        ?? $request->get('channel')
        ?? 'web';

    $channel = $site->channels()
        ->where('slug', $channelSlug)
        ->active()
        ->first();

    // Fallback to default
    if (!$channel) {
        $channel = $site->defaultChannel();
    }

    // Store in container
    app()->instance('current_channel', $channel);

    // Validate currency
    if (!$channel->supportsCurrency(session('currency'))) {
        session(['currency' => $channel->default_currency]);
    }

    // Validate locale
    if (!$channel->supportsLocale(session('locale'))) {
        session(['locale' => $channel->default_locale]);
    }

    return $next($request);
}
```

### Pricing by Channel

```php
// Different prices per channel
$variant = ProductVariant::find(1);

// Web price
$variant->prices()->create([
    'site_id' => $site->id,
    'channel_id' => $webChannel->id,
    'currency' => 'EUR',
    'price' => 99.99,
]);

// Mobile app price (with discount)
$variant->prices()->create([
    'site_id' => $site->id,
    'channel_id' => $mobileChannel->id,
    'currency' => 'EUR',
    'price' => 94.99, // 5% app discount
]);

// B2B price (wholesale)
$variant->prices()->create([
    'site_id' => $site->id,
    'channel_id' => $b2bChannel->id,
    'currency' => 'EUR',
    'price' => 79.99, // 20% wholesale discount
    'min_quantity' => 10,
]);

// Get price for current channel
$price = $variant->getPriceFor(
    siteId: currentSite()->id,
    channelId: currentChannel()->id,
    currency: session('currency'),
);
```

---

## Helpers & Facades

```php
use Shopper\Facades\Channel;

// Get current channel
$currentChannel = Channel::current();
// Or helper
$currentChannel = currentChannel();

// Find by slug
$channel = Channel::findBySlug('web');

// Find by type
$posChannels = Channel::ofType('pos')->get();

// Check if current channel is type
if (currentChannel()->type === 'mobile') {
    // Mobile-specific logic
}
```

---

## Performance Tips

### Caching

```php
// Cache channel data
$channel = Cache::remember("channel.{$id}", 3600, function () use ($id) {
    return Channel::with('site')->find($id);
});

// Cache channels for site
$channels = Cache::remember("site.{$siteId}.channels", 3600, function () use ($siteId) {
    return Channel::where('site_id', $siteId)->active()->get();
});
```

### Eager Loading

```php
// Load relations
$channels = Channel::with(['site', 'prices', 'orders'])->get();
```

---

## Related Documentation

- [Site Model](/docs/model-site)
- [VariantPrice Model](/docs/model-variant-price)
- [Order Model](/docs/model-order)
- [Sites Architecture](/docs/sites-architecture)
- [Pricing System](/docs/pricing-system)
- [REST API - Channels](/docs/api-channels)
- [GraphQL API - Channels](/docs/graphql-channels)
