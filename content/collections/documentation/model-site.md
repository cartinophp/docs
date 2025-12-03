---
id: model-site
blueprint: documentation
title: 'Model: Site'
updated_by: system
updated_at: 1738675127
---
# Model: Site

The Site model represents a geographic or strategic market in your multi-site e-commerce setup. Sites are the foundation of Cartino's "Sites as Markets" architecture.

[TOC]

## Overview

A **Site** in Cartino represents a market - either geographic (like "Italy", "USA") or strategic (like "Wholesale", "Retail"). Each site can have:

- Multiple **Channels** (web, mobile, POS, marketplace)
- Multiple **Currencies**
- Multiple **Catalogs**
- Geographic targeting (countries)
- Tax configuration
- Locale settings

```php
Site {
    handle: "italy"
    name: "Italy Store"
    countries: ['IT', 'SM', 'VA']
    default_currency: "EUR"
    tax_included_in_prices: true
    channels: [Web, Mobile, B2B]
    catalogs: [Retail, Outlet]
}
```

---

## Database Schema

### `sites` Table

```php
Schema::create('sites', function (Blueprint $table) {
    $table->id();

    // Identity
    $table->string('handle')->unique(); // URL-friendly identifier
    $table->string('name'); // Display name
    $table->text('description')->nullable();

    // URL Configuration
    $table->string('url'); // Primary URL
    $table->string('domain')->unique()->nullable(); // Primary domain
    $table->json('domains')->nullable(); // Additional domains

    // Localization
    $table->string('locale')->default('en'); // Default locale
    $table->string('lang')->nullable(); // Language code

    // Geographic
    $table->json('countries')->nullable(); // ['IT', 'SM', 'VA']

    // Currency
    $table->string('default_currency', 3)->default('EUR'); // ISO code

    // Tax Configuration
    $table->boolean('tax_included_in_prices')->default(true);
    $table->string('tax_region')->nullable(); // 'EU', 'US', etc.

    // Status & Priority
    $table->string('status')->default('active'); // active, draft, archived
    $table->integer('priority')->default(0); // Sort order
    $table->boolean('is_default')->default(false); // Default site
    $table->integer('order')->default(0); // Manual ordering

    // Publishing
    $table->timestamp('published_at')->nullable();
    $table->timestamp('unpublished_at')->nullable();

    // Custom Fields (JSONB)
    $table->json('data')->nullable();
    $table->json('attributes')->nullable(); // Additional metadata

    // Timestamps
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('handle');
    $table->index('status');
    $table->index('is_default');
    $table->index('priority');
});
```

---

## Properties

### Core Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | bigint | Primary key |
| `handle` | string | Unique identifier (e.g., "italy") |
| `name` | string | Display name (e.g., "Italy Store") |
| `description` | text | Site description |
| `status` | string | `active`, `draft`, `archived` |
| `priority` | integer | Display/sort priority |
| `is_default` | boolean | Default site flag |

### URL Configuration

| Property | Type | Description |
|----------|------|-------------|
| `url` | string | Primary URL (e.g., "https://it.example.com") |
| `domain` | string | Primary domain (e.g., "it.example.com") |
| `domains` | json | Additional domains array |

### Localization

| Property | Type | Description |
|----------|------|-------------|
| `locale` | string | Default locale (e.g., "it", "en") |
| `lang` | string | Language code (ISO 639-1) |
| `countries` | json | Target countries (ISO 3166-1 alpha-2) |

### Currency & Tax

| Property | Type | Description |
|----------|------|-------------|
| `default_currency` | string | ISO 4217 currency code |
| `tax_included_in_prices` | boolean | Tax inclusion flag |
| `tax_region` | string | Tax region identifier |

### Publishing

| Property | Type | Description |
|----------|------|-------------|
| `published_at` | timestamp | Publication date |
| `unpublished_at` | timestamp | Unpublish date |

### Relationships

| Relation | Type | Description |
|----------|------|-------------|
| `channels` | hasMany | Sales channels |
| `catalogs` | belongsToMany | Product catalogs (pivot) |
| `prices` | hasMany | VariantPrice |
| `shippingZones` | hasMany | Shipping zones |
| `taxRates` | hasMany | Tax rates |
| `orders` | hasMany | Orders |

---

## Eloquent Model

### Basic Usage

```php
use Shopper\Models\Site;

// Create site
$site = Site::create([
    'handle' => 'italy',
    'name' => 'Italy Store',
    'url' => 'https://it.example.com',
    'domain' => 'it.example.com',
    'locale' => 'it',
    'default_currency' => 'EUR',
    'countries' => ['IT', 'SM', 'VA'],
    'tax_included_in_prices' => true,
    'tax_region' => 'EU',
    'status' => 'active',
    'is_default' => false,
]);

// Find by ID
$site = Site::find(1);

// Find by handle
$site = Site::where('handle', 'italy')->first();
// Or use helper
$site = Site::findByHandle('italy');

// Get default site
$defaultSite = Site::default()->first();

// Update
$site->update(['name' => 'Italy - Premium Store']);

// Soft delete
$site->delete();
```

---

## Relationships

### Channels

```php
use Shopper\Models\Channel;

// Create channel
$webChannel = $site->channels()->create([
    'name' => 'Web Store',
    'slug' => 'web',
    'type' => 'web',
    'locales' => ['it', 'en'],
    'currencies' => ['EUR', 'USD'],
    'is_default' => true,
    'status' => 'active',
]);

$mobileChannel = $site->channels()->create([
    'name' => 'Mobile App',
    'slug' => 'mobile',
    'type' => 'mobile',
    'locales' => ['it', 'en'],
    'currencies' => ['EUR'],
    'status' => 'active',
]);

// Get all channels
$channels = $site->channels;

// Get default channel
$defaultChannel = $site->defaultChannel();

// Get active channels
$activeChannels = $site->channels()->active()->get();

// Find channel by type
$posChannel = $site->channels()->where('type', 'pos')->first();
```

### Catalogs

```php
use Shopper\Models\Catalog;

// Attach catalogs with pivot data
$site->catalogs()->attach($retailCatalog->id, [
    'priority' => 1,
    'is_default' => true,
    'is_active' => true,
    'starts_at' => now(),
]);

$site->catalogs()->attach($outletCatalog->id, [
    'priority' => 2,
    'is_active' => true,
]);

// Get catalogs
$catalogs = $site->catalogs;

// Get default catalog
$defaultCatalog = $site->catalogs()
    ->wherePivot('is_default', true)
    ->first();

// Sync catalogs
$site->catalogs()->sync([
    $retailCatalog->id => ['priority' => 1, 'is_default' => true],
    $b2bCatalog->id => ['priority' => 2],
]);
```

### Prices

```php
// Get all prices for this site
$prices = $site->prices;

// Prices for specific currency
$eurPrices = $site->prices()->where('currency', 'EUR')->get();

// Set prices for site
$variant->prices()->create([
    'site_id' => $site->id,
    'channel_id' => $webChannel->id,
    'currency' => 'EUR',
    'price' => 19.99,
]);
```

### Shipping Zones

```php
use Shopper\Models\ShippingZone;

// Create shipping zone
$zone = $site->shippingZones()->create([
    'name' => 'Italy Mainland',
    'countries' => ['IT'],
    'is_active' => true,
]);

// Add rates to zone
$zone->rates()->create([
    'name' => 'Standard Shipping',
    'price' => 5.99,
    'currency' => 'EUR',
    'min_order_value' => 0,
    'max_order_value' => 50,
]);

// Get zones
$zones = $site->shippingZones;
```

### Tax Rates

```php
use Shopper\Models\TaxRate;

// Create tax rate
$site->taxRates()->create([
    'name' => 'IVA Standard',
    'rate' => 22.00, // 22%
    'country' => 'IT',
    'is_inclusive' => true,
    'priority' => 1,
]);

// Get tax rates
$taxRates = $site->taxRates;

// Get rate for location
$rate = $site->taxRates()
    ->where('country', 'IT')
    ->orderByDesc('priority')
    ->first();
```

### Orders

```php
// Get orders for this site
$orders = $site->orders;

// Revenue by site
$revenue = $site->orders()
    ->where('status', 'completed')
    ->sum('total');

// Orders count
$orderCount = $site->orders()->count();
```

---

## Scopes

### Query Scopes

```php
// Active sites
Site::active()->get();

// Published sites
Site::published()->get();

// Default site
Site::default()->first();

// By status
Site::status('active')->get();

// By handle
Site::handle('italy')->first();

// With channels
Site::with('channels')->get();

// Priority order
Site::orderByPriority()->get();
```

### Scope Definitions

```php
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

public function scopePublished($query)
{
    return $query->where('status', 'active')
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now())
        ->where(function ($q) {
            $q->whereNull('unpublished_at')
              ->orWhere('unpublished_at', '>', now());
        });
}

public function scopeDefault($query)
{
    return $query->where('is_default', true);
}

public function scopeStatus($query, string $status)
{
    return $query->where('status', $status);
}

public function scopeHandle($query, string $handle)
{
    return $query->where('handle', $handle);
}

public function scopeOrderByPriority($query)
{
    return $query->orderByDesc('priority')->orderBy('order');
}
```

---

## Accessors & Mutators

### Accessors

```php
// Get full URL with scheme
public function getFullUrlAttribute(): string
{
    return $this->url ?? "https://{$this->domain}";
}

// Get primary domain
public function getPrimaryDomainAttribute(): string
{
    return $this->domain ?? parse_url($this->url, PHP_URL_HOST);
}

// Get all domains (including primary)
public function getAllDomainsAttribute(): array
{
    $domains = $this->domains ?? [];
    if ($this->domain) {
        array_unshift($domains, $this->domain);
    }
    return array_unique($domains);
}

// Check if published
public function getIsPublishedAttribute(): bool
{
    return $this->status === 'active'
        && $this->published_at
        && $this->published_at <= now()
        && (!$this->unpublished_at || $this->unpublished_at > now());
}

// Get supported currencies
public function getSupportedCurrenciesAttribute(): array
{
    $currencies = [$this->default_currency];

    foreach ($this->channels as $channel) {
        $currencies = array_merge($currencies, $channel->currencies ?? []);
    }

    return array_unique($currencies);
}

// Get supported locales
public function getSupportedLocalesAttribute(): array
{
    $locales = [$this->locale];

    foreach ($this->channels as $channel) {
        $locales = array_merge($locales, $channel->locales ?? []);
    }

    return array_unique($locales);
}

// Usage
echo $site->full_url;
$currencies = $site->supported_currencies;
if ($site->is_published) { ... }
```

### Mutators

```php
// Normalize handle
public function setHandleAttribute($value)
{
    $this->attributes['handle'] = Str::slug($value);
}

// Ensure only one default site
public function setIsDefaultAttribute($value)
{
    if ($value) {
        // Remove default flag from other sites
        static::where('id', '!=', $this->id)
            ->update(['is_default' => false]);
    }

    $this->attributes['is_default'] = $value;
}

// Validate currency code
public function setDefaultCurrencyAttribute($value)
{
    $value = strtoupper($value);

    if (!in_array($value, config('shopper.currencies'))) {
        throw new \InvalidArgumentException("Invalid currency code: {$value}");
    }

    $this->attributes['default_currency'] = $value;
}
```

---

## Methods

### Domain Management

```php
// Check if domain belongs to site
public function hasDomain(string $domain): bool
{
    return in_array($domain, $this->all_domains);
}

// Add domain
public function addDomain(string $domain): void
{
    $domains = $this->domains ?? [];
    $domains[] = $domain;
    $this->update(['domains' => array_unique($domains)]);
}

// Remove domain
public function removeDomain(string $domain): void
{
    $domains = array_filter(
        $this->domains ?? [],
        fn($d) => $d !== $domain
    );
    $this->update(['domains' => array_values($domains)]);
}
```

### Country Management

```php
// Check if country is supported
public function supportsCountry(string $countryCode): bool
{
    // If no countries specified, supports all
    if (empty($this->countries)) {
        return true;
    }

    return in_array(strtoupper($countryCode), $this->countries);
}

// Add country
public function addCountry(string $countryCode): void
{
    $countries = $this->countries ?? [];
    $countries[] = strtoupper($countryCode);
    $this->update(['countries' => array_unique($countries)]);
}

// Remove country
public function removeCountry(string $countryCode): void
{
    $countries = array_filter(
        $this->countries ?? [],
        fn($c) => $c !== strtoupper($countryCode)
    );
    $this->update(['countries' => array_values($countries)]);
}
```

### Currency & Locale

```php
// Check if currency is supported
public function supportsCurrency(string $currency): bool
{
    return in_array(strtoupper($currency), $this->supported_currencies);
}

// Check if locale is supported
public function supportsLocale(string $locale): bool
{
    return in_array($locale, $this->supported_locales);
}

// Get default channel
public function defaultChannel(): ?Channel
{
    return $this->channels()->where('is_default', true)->first()
        ?? $this->channels()->orderBy('id')->first();
}
```

### Publishing

```php
// Publish site
public function publish(): void
{
    $this->update([
        'status' => 'active',
        'published_at' => now(),
        'unpublished_at' => null,
    ]);
}

// Unpublish site
public function unpublish(): void
{
    $this->update([
        'status' => 'draft',
        'unpublished_at' => now(),
    ]);
}

// Schedule publishing
public function schedulePublish(\DateTime $date): void
{
    $this->update([
        'status' => 'active',
        'published_at' => $date,
    ]);
}

// Archive site
public function archive(): void
{
    $this->update(['status' => 'archived']);
}
```

### Analytics

```php
// Get site statistics
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

        'customers_count' => $this->orders()
            ->where('created_at', '>=', $startDate)
            ->distinct('customer_id')
            ->count('customer_id'),
    ];
}
```

---

## Static Methods

### Site Detection

```php
// Find site by domain
public static function findByDomain(string $domain): ?Site
{
    return static::where('domain', $domain)
        ->orWhereJsonContains('domains', $domain)
        ->first();
}

// Find site by handle
public static function findByHandle(string $handle): ?Site
{
    return static::where('handle', $handle)->first();
}

// Get default site
public static function getDefault(): ?Site
{
    return static::where('is_default', true)->first()
        ?? static::orderBy('priority', 'desc')->first();
}

// Get current site (from session/context)
public static function current(): ?Site
{
    return app('current_site') ?? static::getDefault();
}
```

---

## Events

### Model Events

```php
use Shopper\Events\SiteCreated;
use Shopper\Events\SiteUpdated;
use Shopper\Events\SitePublished;

Event::listen(SiteCreated::class, function ($event) {
    $site = $event->site;

    // Create default channel
    $site->channels()->create([
        'name' => 'Web Store',
        'slug' => 'web',
        'type' => 'web',
        'is_default' => true,
        'status' => 'active',
    ]);
});

Event::listen(SitePublished::class, function ($event) {
    $site = $event->site;

    // Clear cache
    // Send notifications
    // Update search index
});
```

### Eloquent Events

```php
protected static function booted()
{
    static::creating(function ($site) {
        // Ensure only one default site
        if ($site->is_default) {
            static::where('is_default', true)->update(['is_default' => false]);
        }

        // Set priority if not specified
        if ($site->priority === 0) {
            $site->priority = static::max('priority') + 1;
        }
    });

    static::saved(function ($site) {
        // Clear cache
        Cache::forget('sites.all');
        Cache::forget("site.{$site->handle}");
    });

    static::deleting(function ($site) {
        // Prevent deleting default site
        if ($site->is_default) {
            throw new \RuntimeException('Cannot delete default site');
        }

        // Delete relations
        $site->channels()->delete();
        $site->catalogs()->detach();
        $site->shippingZones()->delete();
        $site->taxRates()->delete();
    });
}
```

---

## API Endpoints

### REST API

```http
# List sites
GET /api/sites

# Get single site
GET /api/sites/{id}

# Create site
POST /api/sites

# Update site
PUT /api/sites/{id}

# Delete site
DELETE /api/sites/{id}

# Publish site
POST /api/sites/{id}/publish

# Get site statistics
GET /api/sites/{id}/statistics
```

### GraphQL API

```graphql
# Query site
query {
  site(id: 1) {
    id
    handle
    name
    url
    domain
    default_currency
    locale
    countries
    is_published
    channels {
      id
      name
      type
      currencies
      locales
    }
    catalogs {
      id
      name
    }
  }
}

# Query sites list
query {
  sites(status: "active") {
    edges {
      node {
        id
        handle
        name
        is_default
        supported_currencies
        supported_locales
      }
    }
  }
}

# Create site
mutation {
  createSite(input: {
    handle: "spain"
    name: "Spain Store"
    domain: "es.example.com"
    default_currency: "EUR"
    locale: "es"
    countries: ["ES", "AD", "PT"]
  }) {
    site {
      id
      handle
      name
    }
  }
}
```

---

## Examples

### Creating Sites

```php
// Single global site (simplest)
$site = Site::create([
    'handle' => 'default',
    'name' => 'Global Store',
    'url' => 'https://example.com',
    'locale' => 'en',
    'default_currency' => 'EUR',
    'status' => 'active',
    'is_default' => true,
]);

// Geographic site (Italy)
$italy = Site::create([
    'handle' => 'italy',
    'name' => 'Italy Store',
    'url' => 'https://it.example.com',
    'domain' => 'it.example.com',
    'locale' => 'it',
    'default_currency' => 'EUR',
    'countries' => ['IT', 'SM', 'VA'],
    'tax_included_in_prices' => true,
    'tax_region' => 'EU',
    'status' => 'active',
]);

// USA site
$usa = Site::create([
    'handle' => 'usa',
    'name' => 'USA Store',
    'url' => 'https://us.example.com',
    'domain' => 'us.example.com',
    'locale' => 'en',
    'default_currency' => 'USD',
    'countries' => ['US', 'PR'],
    'tax_included_in_prices' => false,
    'tax_region' => 'US',
    'status' => 'active',
]);
```

### Site with Channels

```php
$site = Site::create([
    'handle' => 'italy',
    'name' => 'Italy Store',
    'default_currency' => 'EUR',
    'locale' => 'it',
]);

// Web channel
$site->channels()->create([
    'name' => 'Web Store',
    'slug' => 'web',
    'type' => 'web',
    'locales' => ['it', 'en'],
    'currencies' => ['EUR', 'USD'],
    'is_default' => true,
    'status' => 'active',
]);

// Mobile channel
$site->channels()->create([
    'name' => 'Mobile App',
    'slug' => 'mobile',
    'type' => 'mobile',
    'locales' => ['it'],
    'currencies' => ['EUR'],
    'status' => 'active',
]);

// B2B channel
$site->channels()->create([
    'name' => 'B2B Portal',
    'slug' => 'b2b',
    'type' => 'b2b_portal',
    'locales' => ['it', 'en'],
    'currencies' => ['EUR'],
    'status' => 'active',
]);
```

### Site Detection Middleware

```php
// Middleware: DetectSite.php
public function handle($request, Closure $next)
{
    $domain = $request->getHost();

    // Find site by domain
    $site = Site::findByDomain($domain);

    // Fallback to default
    if (!$site) {
        $site = Site::getDefault();
    }

    // Store in container
    app()->instance('current_site', $site);

    // Set locale
    app()->setLocale($site->locale);

    return $next($request);
}
```

---

## Helpers & Facades

```php
use Shopper\Facades\Site;

// Get current site
$currentSite = Site::current();
// Or helper
$currentSite = currentSite();

// Get default site
$defaultSite = Site::default();

// Find by handle
$site = Site::findByHandle('italy');

// Check if site exists
if (Site::has('italy')) {
    // ...
}

// Format money for site
$formatted = money($amount, $site->default_currency);
```

---

## Performance Tips

### Caching

```php
// Cache sites list
$sites = Cache::remember('sites.all', 3600, function () {
    return Site::with('channels')->active()->get();
});

// Cache site by handle
$site = Cache::remember("site.{$handle}", 3600, function () use ($handle) {
    return Site::with(['channels', 'catalogs'])
        ->where('handle', $handle)
        ->first();
});

// Clear site cache
Site::saved(function ($site) {
    Cache::forget('sites.all');
    Cache::forget("site.{$site->handle}");
});
```

### Eager Loading

```php
// Load relations
$sites = Site::with([
    'channels',
    'catalogs',
    'shippingZones.rates',
    'taxRates',
])->get();
```

---

## Related Documentation

- [Channel Model](/docs/model-channel)
- [Catalog Model](/docs/model-catalog)
- [Sites Architecture](/docs/sites-architecture)
- [Multi-Currency](/docs/multi-currency)
- [Shipping Configuration](/docs/shipping-configuration)
- [Tax System](/docs/tax-system)
- [REST API - Sites](/docs/api-sites)
- [GraphQL API - Sites](/docs/graphql-sites)
