---
id: 9d723a18-5b2f-4e92-b8a1-7fa3c8d5e9b3
blueprint: example_v1
title: Sites & Markets Architecture
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264233
---
# Sites & Markets Architecture

Cartino uses **Sites as Markets** - a flexible architecture where each Site represents a geographic or strategic market with built-in support for multi-currency, multi-locale, and multi-catalog operations.

[TOC]

## Overview

### Key Concepts

- **Sites**: Geographic or strategic markets (e.g., Italy, USA, EU, Global)
- **Channels**: Sales methods within sites (Web, Mobile, POS, B2B Portal, Marketplaces)
- **Catalogs**: Product collections (Retail, B2B, Wholesale, Outlet)
- **Session-based**: Currency and locale selection based on user session

This architecture enables flexible pricing strategies, regional customization, and omnichannel commerce.

---

## Database Schema

### Sites Table

```php
sites {
  id                      bigint
  handle                  string (unique)
  name                    string
  description             text
  
  // URL Configuration
  url                     string
  domain                  string (unique, nullable)
  domains                 jsonb  // Multiple domains support
  
  // Localization
  locale                  string (default: 'en')
  lang                    string
  
  // Geographic Configuration
  countries               jsonb  // ['IT', 'SM', 'VA']
  
  // Currency
  default_currency        string  // 'EUR', 'USD', 'GBP'
  
  // Tax Configuration
  tax_included_in_prices  boolean
  tax_region              string (nullable)
  
  // Status & Priority
  priority                integer (default: 0)
  is_default              boolean (default: false)
  status                  string (active, draft, archived)
  order                   integer
  
  // Publishing
  published_at            timestamp
  unpublished_at          timestamp
  
  timestamps, soft_deletes, attributes (jsonb)
}
```

### Channels Table

```php
channels {
  id              bigint
  site_id         bigint FK → sites
  name            string
  slug            string (unique)
  description     text
  
  // Channel Type
  type            enum (web, mobile, pos, marketplace, b2b_portal, social, api)
  
  url             string (nullable)
  is_default      boolean
  status          string
  
  // Multi-locale & Multi-currency Support
  locales         jsonb  // ['en', 'it', 'fr', 'es']
  currencies      jsonb  // ['EUR', 'USD', 'GBP']
  
  settings        jsonb  // Channel-specific configuration
  
  timestamps, soft_deletes
}
```

### Site-Catalog Pivot

```php
site_catalog {
  id           bigint
  site_id      bigint FK → sites
  catalog_id   bigint FK → catalogs
  priority     integer
  is_default   boolean
  is_active    boolean
  starts_at    timestamp
  ends_at      timestamp
  settings     jsonb
  
  timestamps
}
```

---

## Architecture Patterns

### Pattern 1: Single Global Site

**Use case**: Startup, single market, multi-currency support

```php
Site: "default" (Global)
├─ Countries: null (all countries)
├─ Default Currency: EUR
├─ Locales: managed via channels
├─ Channels:
│  ├─ Web (type: web)
│  │  ├─ locales: ['en', 'it', 'fr', 'es', 'de']
│  │  └─ currencies: ['EUR', 'USD', 'GBP', 'CHF']
│  └─ Mobile (type: mobile)
│     └─ currencies: ['EUR', 'USD']
└─ Catalogs: [Retail, B2B]
```

**Benefits**:
- Simplest setup
- Single inventory pool
- Session-based currency switching
- Easy to start, scalable later

**Query Example**:
```php
// User selects currency in session
Session::put('currency', 'USD');
Session::put('locale', 'en');

// Price lookup
$price = VariantPrice::where('product_variant_id', $variantId)
    ->where('site_id', 1)
    ->where('channel_id', currentChannel()->id)
    ->where('currency', session('currency'))
    ->orderByDesc('priority')
    ->first();
```

### Pattern 2: Multi-Site Geographic Markets

**Use case**: Different catalogs, pricing, or logistics per region

```php
Site: "italy" (IT Market)
├─ Countries: ['IT', 'SM', 'VA']
├─ Default Currency: EUR
├─ Tax Region: 'EU'
├─ Tax Included: true
├─ Channels:
│  ├─ Web (currencies: ['EUR'])
│  └─ B2B Portal (currencies: ['EUR', 'USD'])
└─ Catalogs: [Retail, B2B, Outlet]

Site: "usa" (US Market)
├─ Countries: ['US', 'PR']
├─ Default Currency: USD
├─ Tax Region: 'US'
├─ Tax Included: false
├─ Channels:
│  ├─ Web (currencies: ['USD'])
│  ├─ Amazon (type: marketplace)
│  └─ POS (type: pos)
└─ Catalogs: [Retail, Wholesale]

Site: "eu" (European Union)
├─ Countries: ['FR', 'DE', 'ES', 'NL', 'BE']
├─ Default Currency: EUR
├─ Tax Region: 'EU'
├─ Tax Included: true
├─ Channels:
│  └─ Web (locales: ['fr', 'de', 'es', 'nl'])
└─ Catalogs: [Retail]
```

**Benefits**:
- Separate catalogs per region
- Regional pricing strategies
- Different tax rules
- Localized shipping zones
- Independent inventory (optional)

### Pattern 3: Multi-Channel Per Site

**Use case**: Omnichannel retail with different pricing per channel

```php
Site: "italy"
├─ Channels:
│  ├─ Web Store (type: web)
│  │  └─ Base retail prices
│  ├─ Mobile App (type: mobile)
│  │  └─ App-exclusive discounts
│  ├─ Retail POS (type: pos)
│  │  └─ In-store pricing
│  ├─ B2B Portal (type: b2b_portal)
│  │  └─ Wholesale prices + tier pricing
│  └─ Amazon (type: marketplace)
│     └─ Marketplace fees included in price
```

**Pricing Example**:
```php
// variant_prices table
[
  // Web price
  {
    variant_id: 123, 
    site_id: 1, 
    channel_id: 1, // Web
    currency: 'EUR', 
    price: 99.00
  },
  
  // Mobile app discount
  {
    variant_id: 123,
    site_id: 1,
    channel_id: 2, // Mobile
    currency: 'EUR',
    price: 94.00  // 5% app discount
  },
  
  // B2B tier pricing
  {
    variant_id: 123,
    site_id: 1,
    channel_id: 4, // B2B
    currency: 'EUR',
    price: 79.00,
    min_quantity: 10
  },
  {
    variant_id: 123,
    site_id: 1,
    channel_id: 4,
    currency: 'EUR',
    price: 69.00,
    min_quantity: 50
  }
]
```

---

## Session-Based Currency & Locale

### Middleware Flow

```php
namespace App\Http\Middleware;

class DetectMarket
{
    public function handle($request, $next)
    {
        // 1. Detect from IP or browser if not set
        if (!session()->has('locale')) {
            $detectedLocale = $this->detectLocale($request);
            session(['locale' => $detectedLocale]);
        }
        
        if (!session()->has('currency')) {
            $detectedCurrency = $this->detectCurrency($request);
            session(['currency' => $detectedCurrency]);
        }
        
        // 2. Validate against current site/channel
        $this->validateSessionPreferences();
        
        // 3. Set app locale
        app()->setLocale(session('locale'));
        
        return $next($request);
    }
    
    private function detectLocale($request): string
    {
        // Try browser locale first
        $browserLocale = substr($request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);
        
        // Fallback to GeoIP
        $geoLocale = GeoIP::getLocale($request->ip());
        
        return $browserLocale ?? $geoLocale ?? 'en';
    }
    
    private function detectCurrency($request): string
    {
        return GeoIP::getCurrency($request->ip()) 
            ?? currentSite()->default_currency 
            ?? 'EUR';
    }
    
    private function validateSessionPreferences(): void
    {
        $site = currentSite();
        $channel = currentChannel();
        
        // Validate locale is supported by channel
        if (!in_array(session('locale'), $channel->locales ?? [])) {
            session(['locale' => $channel->locales[0] ?? $site->locale]);
        }
        
        // Validate currency is supported by channel
        if (!in_array(session('currency'), $channel->currencies ?? [])) {
            session(['currency' => $site->default_currency]);
        }
    }
}
```

### Currency/Locale Switcher

**Vue Component**:
```vue
<template>
  <div class="flex gap-4">
    <!-- Currency Switcher -->
    <select 
      v-model="selectedCurrency" 
      @change="switchCurrency"
      class="form-select"
    >
      <option 
        v-for="currency in availableCurrencies" 
        :key="currency"
        :value="currency"
      >
        {{ formatCurrency(currency) }}
      </option>
    </select>
    
    <!-- Locale Switcher -->
    <select 
      v-model="selectedLocale" 
      @change="switchLocale"
      class="form-select"
    >
      <option 
        v-for="locale in availableLocales" 
        :key="locale"
        :value="locale"
      >
        {{ formatLocale(locale) }}
      </option>
    </select>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const availableCurrencies = ref(['EUR', 'USD', 'GBP', 'CHF']);
const availableLocales = ref(['en', 'it', 'fr', 'de']);

const selectedCurrency = ref(window.sessionCurrency || 'EUR');
const selectedLocale = ref(window.sessionLocale || 'en');

function switchCurrency() {
  axios.post('/api/session/currency', { 
    currency: selectedCurrency.value 
  }).then(() => window.location.reload());
}

function switchLocale() {
  axios.post('/api/session/locale', { 
    locale: selectedLocale.value 
  }).then(() => window.location.reload());
}

function formatCurrency(code) {
  const symbols = { EUR: '€', USD: '$', GBP: '£', CHF: 'CHF' };
  return `${symbols[code] || code} ${code}`;
}

function formatLocale(code) {
  const names = { en: 'English', it: 'Italiano', fr: 'Français', de: 'Deutsch' };
  return names[code] || code;
}
</script>
```

**Controller**:
```php
namespace App\Http\Controllers\Api;

class SessionController extends Controller
{
    public function setCurrency(Request $request)
    {
        $currency = $request->validate([
            'currency' => 'required|string|size:3'
        ])['currency'];
        
        // Validate currency is supported
        $channel = currentChannel();
        if (!in_array($currency, $channel->currencies ?? [])) {
            return response()->json(['error' => 'Currency not supported'], 400);
        }
        
        session(['currency' => $currency]);
        
        return response()->json(['success' => true]);
    }
    
    public function setLocale(Request $request)
    {
        $locale = $request->validate([
            'locale' => 'required|string|size:2'
        ])['locale'];
        
        // Validate locale is supported
        $channel = currentChannel();
        if (!in_array($locale, $channel->locales ?? [])) {
            return response()->json(['error' => 'Locale not supported'], 400);
        }
        
        session(['locale' => $locale]);
        
        return response()->json(['success' => true]);
    }
}
```

---

## Pricing Resolution

### Hierarchical Fallback Algorithm

Cartino resolves prices with a **priority-based fallback** system:

**Priority Order** (highest to lowest):
1. Site + Channel + Customer Group + Catalog
2. Site + Channel + Customer Group
3. Site + Channel
4. Site only
5. Global fallback

```php
namespace App\Services;

class PricingService
{
    public function resolvePrice(
        int $variantId,
        ?int $siteId = null,
        ?int $channelId = null,
        ?string $currency = null,
        ?int $customerGroupId = null,
        ?int $catalogId = null,
        int $quantity = 1
    ): ?VariantPrice {
        
        // Use current context if not specified
        $siteId ??= currentSite()->id;
        $channelId ??= currentChannel()->id;
        $currency ??= session('currency');
        $customerGroupId ??= auth()->user()?->customer_group_id;
        
        return VariantPrice::where('product_variant_id', $variantId)
            ->where('currency', $currency)
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')
                  ->orWhere('max_quantity', '>=', $quantity);
            })
            ->where(function ($q) use ($siteId, $channelId, $customerGroupId, $catalogId) {
                // Priority 1: Full match
                $q->where(function ($sub) use ($siteId, $channelId, $customerGroupId, $catalogId) {
                    $sub->where('site_id', $siteId)
                        ->where('channel_id', $channelId)
                        ->where('customer_group_id', $customerGroupId)
                        ->where('catalog_id', $catalogId);
                })
                // Priority 2: Site + Channel + Group
                ->orWhere(function ($sub) use ($siteId, $channelId, $customerGroupId) {
                    $sub->where('site_id', $siteId)
                        ->where('channel_id', $channelId)
                        ->where('customer_group_id', $customerGroupId)
                        ->whereNull('catalog_id');
                })
                // Priority 3: Site + Channel
                ->orWhere(function ($sub) use ($siteId, $channelId) {
                    $sub->where('site_id', $siteId)
                        ->where('channel_id', $channelId)
                        ->whereNull('customer_group_id')
                        ->whereNull('catalog_id');
                })
                // Priority 4: Site only
                ->orWhere(function ($sub) use ($siteId) {
                    $sub->where('site_id', $siteId)
                        ->whereNull('channel_id')
                        ->whereNull('customer_group_id')
                        ->whereNull('catalog_id');
                })
                // Priority 5: Global fallback
                ->orWhere(function ($sub) {
                    $sub->whereNull('site_id')
                        ->whereNull('channel_id')
                        ->whereNull('customer_group_id')
                        ->whereNull('catalog_id');
                });
            })
            // Validate date ranges
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('priority')
            ->orderByDesc('min_quantity')  // Higher quantity tiers first
            ->first();
    }
    
    // Bulk pricing for product listings
    public function resolvePricesBulk(array $variantIds): Collection
    {
        $siteId = currentSite()->id;
        $channelId = currentChannel()->id;
        $currency = session('currency');
        $customerGroupId = auth()->user()?->customer_group_id;
        
        // Single query for all variants
        $prices = VariantPrice::whereIn('product_variant_id', $variantIds)
            ->where('currency', $currency)
            // Same hierarchy as above
            ->get()
            ->groupBy('product_variant_id');
        
        // Apply priority rules per variant
        return $prices->map(fn($group) => 
            $group->sortByDesc('priority')->first()
        );
    }
}
```

---

## Tax Configuration

### Site-Level Tax Settings

```php
$site = currentSite();

if ($site->tax_included_in_prices) {
    // European style: €99.00 (VAT included)
    $displayPrice = $price;
    $taxAmount = $price - ($price / (1 + $taxRate));
} else {
    // US style: $99.00 + tax
    $displayPrice = $price;
    $taxAmount = $price * $taxRate;
    $totalPrice = $price + $taxAmount;
}
```

### Tax Rates

```php
$taxRate = TaxRate::where('site_id', currentSite()->id)
    ->where('country', $address->country)
    ->where(function ($q) use ($address) {
        $q->whereNull('state')
          ->orWhere('state', $address->state);
    })
    ->orderByDesc('priority')
    ->first();
```

---

## Working with Sites & Channels

### Creating a Site

```php
$site = Site::create([
    'handle' => 'italy',
    'name' => 'Cartino Italy',
    'countries' => ['IT', 'SM', 'VA'],
    'default_currency' => 'EUR',
    'locale' => 'it',
    'tax_included_in_prices' => true,
    'tax_region' => 'EU',
    'status' => 'active',
    'is_default' => false,
]);
```

### Creating Channels

```php
// Web channel
$webChannel = $site->channels()->create([
    'name' => 'Web Store',
    'slug' => 'web',
    'type' => 'web',
    'locales' => ['it', 'en'],
    'currencies' => ['EUR', 'USD'],
    'is_default' => true,
    'status' => 'active',
]);

// B2B channel
$b2bChannel = $site->channels()->create([
    'name' => 'B2B Portal',
    'slug' => 'b2b',
    'type' => 'b2b_portal',
    'locales' => ['it', 'en'],
    'currencies' => ['EUR'],
    'status' => 'active',
]);
```

### Helper Functions

```php
// Get current site
function currentSite(): Site
{
    return app('currentSite') ?? Site::where('is_default', true)->first();
}

// Get current channel
function currentChannel(): Channel
{
    return app('currentChannel') ?? 
        currentSite()->channels()->where('is_default', true)->first();
}

// Format price with currency
function formatPrice(float $price, string $currency = null): string
{
    $currency ??= session('currency');
    
    return match($currency) {
        'EUR' => '€' . number_format($price, 2, ',', '.'),
        'USD' => '$' . number_format($price, 2, '.', ','),
        'GBP' => '£' . number_format($price, 2, '.', ','),
        default => $currency . ' ' . number_format($price, 2),
    };
}
```

---

## Best Practices

### DO ✅

- Use session-based currency/locale for flexibility
- Implement hierarchical price fallbacks
- Validate user selections against channel capabilities
- Index pricing queries properly
- Cache pricing lookups for performance

### DON'T ❌

- Don't hard-code currency or locale
- Don't skip price validation for supported currencies
- Don't forget to clear pricing cache on updates
- Don't mix tax-inclusive and tax-exclusive displays

---

## Next Steps

- [**Pricing Strategies**](/example/1.x/pricing) - Advanced pricing & discounts
- [**Inventory Management**](/example/1.x/inventory) - Multi-location stock
- [**Shipping Zones**](/example/1.x/shipping) - Geographic shipping
- [**Tax Configuration**](/example/1.x/tax) - Regional tax setup
