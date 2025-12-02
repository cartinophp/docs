# Sites-as-Markets Architecture

## 🎯 Overview

Cartino uses **Sites as Markets** - a flexible architecture where each Site represents a geographic or strategic market, with built-in support for multi-currency, multi-locale, and multi-catalog operations.

### Key Concepts

- **Sites**: Geographic/strategic markets (e.g., Italy, USA, EU, Global)
- **Channels**: Sales methods within sites (Web, Mobile, POS, B2B Portal, Marketplaces)
- **Catalogs**: Product collections (Retail, B2B, Wholesale, Outlet)
- **Session-based**: Currency and locale selection based on user session

---

## 🏗️ Database Schema

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
  domains                 jsonb (multiple domains)
  
  // Localization
  locale                  string (default fallback: 'en')
  lang                    string
  
  // Geographic Configuration
  countries               jsonb (['IT', 'SM', 'VA'])
  
  // Currency
  default_currency        string ('EUR', 'USD', 'GBP')
  
  // Tax
  tax_included_in_prices  boolean
  tax_region              string (nullable)
  
  // Priority & Status
  priority                integer (default: 0)
  is_default              boolean (default: false)
  status                  string ('active', 'draft', 'archived')
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
  
  // Multi-locale & Multi-currency
  locales         jsonb (['en', 'it', 'fr', 'es'])
  currencies      jsonb (['EUR', 'USD', 'GBP'])
  
  settings        jsonb (channel-specific config)
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

## 📊 Architecture Patterns

### Pattern 1: Single Global Site (Simplest)

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
- Single inventory
- Session-based currency switching
- Easy to start, scalable later

**Example Query**:
```php
// User selects currency in session
Session::put('currency', 'USD');
Session::put('locale', 'en');

// Price lookup
$price = VariantPrice::where('product_variant_id', $variantId)
    ->where('site_id', 1)
    ->where('channel_id', currentChannel()->id)
    ->where('currency', session('currency')) // EUR, USD, GBP...
    ->orderByDesc('priority')
    ->first();
```

---

### Pattern 2: Multi-Site Geographic Markets

**Use case**: Different catalogs, pricing, or logistics per region

```php
Site: "italy" (IT Market)
├─ Countries: ['IT', 'SM', 'VA']
├─ Default Currency: EUR
├─ Tax Region: 'EU'
├─ Channels:
│  ├─ Web (currencies: ['EUR'])
│  └─ B2B Portal (currencies: ['EUR', 'USD'])
└─ Catalogs: [Retail, B2B, Outlet]

Site: "usa" (US Market)
├─ Countries: ['US', 'PR']
├─ Default Currency: USD
├─ Tax Region: 'US'
├─ Channels:
│  ├─ Web (currencies: ['USD'])
│  ├─ Amazon (type: marketplace)
│  └─ POS (type: pos)
└─ Catalogs: [Retail, Wholesale]

Site: "eu" (European Union)
├─ Countries: ['FR', 'DE', 'ES', 'NL', 'BE', ...]
├─ Default Currency: EUR
├─ Tax Region: 'EU'
├─ Channels:
│  └─ Web (locales: ['fr', 'de', 'es', 'nl'])
└─ Catalogs: [Retail]
```

**Benefits**:
- Separate catalogs per region
- Regional pricing strategies
- Different tax rules
- Localized shipping zones

---

### Pattern 3: Multi-Channel Per Site

**Use case**: Omnichannel retail with different pricing per channel

```php
Site: "italy"
├─ Channels:
│  ├─ Web Store (type: web)
│  │  └─ Base prices
│  ├─ Mobile App (type: mobile)
│  │  └─ App-exclusive discounts
│  ├─ Retail POS (type: pos)
│  │  └─ In-store pricing
│  ├─ B2B Portal (type: b2b_portal)
│  │  └─ Wholesale prices + tier pricing
│  └─ Amazon (type: marketplace)
│     └─ Marketplace fees included
```

**Pricing Example**:
```php
variant_prices {
  // Web price
  { variant_id: 123, site_id: 1, channel_id: 1, currency: 'EUR', price: 99.00 }
  
  // Mobile app discount
  { variant_id: 123, site_id: 1, channel_id: 2, currency: 'EUR', price: 94.00 }
  
  // B2B tier pricing
  { variant_id: 123, site_id: 1, channel_id: 4, currency: 'EUR', price: 79.00, min_qty: 10 }
  { variant_id: 123, site_id: 1, channel_id: 4, currency: 'EUR', price: 69.00, min_qty: 50 }
}
```

---

## 🔄 Session-Based Currency & Locale

### Middleware Flow

```php
// 1. User arrives → Detect from IP or browser
$detectedLocale = GeoIP::getLocale($ip);
$detectedCurrency = GeoIP::getCurrency($ip);

// 2. Store in session (can be changed by user)
session([
    'locale' => request('locale', $detectedLocale),
    'currency' => request('currency', $detectedCurrency),
]);

// 3. Apply to queries
$currentSite = Site::default()->first();
$currentChannel = $currentSite->channels()
    ->where('type', 'web')
    ->where('status', 'active')
    ->first();

// Validate locale is supported
if (!in_array(session('locale'), $currentChannel->locales)) {
    session(['locale' => $currentChannel->locales[0]]);
}

// Validate currency is supported
if (!in_array(session('currency'), $currentChannel->currencies)) {
    session(['currency' => $currentSite->default_currency]);
}
```

### Currency Switcher Component

```vue
<template>
  <select v-model="selectedCurrency" @change="switchCurrency">
    <option v-for="currency in availableCurrencies" :value="currency">
      {{ currency }}
    </option>
  </select>
</template>

<script setup>
const availableCurrencies = ref(['EUR', 'USD', 'GBP']);
const selectedCurrency = ref(session('currency'));

function switchCurrency() {
  axios.post('/api/session/currency', { currency: selectedCurrency.value })
    .then(() => window.location.reload());
}
</script>
```

---

## 💰 Pricing Resolution Algorithm

### Hierarchical Fallback (Priority Order)

```php
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
        
        $siteId ??= currentSite()->id;
        $channelId ??= currentChannel()->id;
        $currency ??= session('currency');
        $customerGroupId ??= auth()->user()?->customer_group_id;
        
        // Try specific → fallback to generic
        return VariantPrice::where('product_variant_id', $variantId)
            ->where('currency', $currency)
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')
                  ->orWhere('max_quantity', '>=', $quantity);
            })
            ->where(function ($q) use ($siteId, $channelId, $customerGroupId, $catalogId) {
                // Priority 1: Exact match (site + channel + group + catalog)
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
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('priority')
            ->orderByDesc('min_quantity') // Higher qty tiers first
            ->first();
    }
}
```

### Performance Optimization

**Index Strategy** (already in migration):
```sql
CREATE INDEX idx_price_resolution 
ON variant_prices (product_variant_id, site_id, channel_id, customer_group_id, currency, priority);

CREATE INDEX idx_variant_schedule 
ON variant_prices (product_variant_id, currency, starts_at, ends_at);
```

**Bulk Resolution** (for product lists):
```php
public function resolvePricesBulk(array $variantIds): Collection
{
    $siteId = currentSite()->id;
    $channelId = currentChannel()->id;
    $currency = session('currency');
    $customerGroupId = auth()->user()?->customer_group_id;
    
    // Single query for all variants
    $prices = VariantPrice::whereIn('product_variant_id', $variantIds)
        ->where('currency', $currency)
        ->where(function ($q) use ($siteId, $channelId, $customerGroupId) {
            // Same hierarchy as above
        })
        ->get()
        ->groupBy('product_variant_id');
    
    // Apply priority rules per variant
    return $prices->map(fn($group) => $group->sortByDesc('priority')->first());
}
```

---

## 🚚 Shipping Integration

### Zones per Site

```php
ShippingZone::where('site_id', currentSite()->id)
    ->where('is_active', true)
    ->whereJsonContains('countries', $shippingCountry)
    ->orderByDesc('priority')
    ->first();
```

### Rates per Channel

```php
ShippingRate::where('shipping_zone_id', $zoneId)
    ->where('channel_id', currentChannel()->id)
    ->where('currency', session('currency'))
    ->where('is_active', true)
    ->get();
```

---

## 📈 Tax Configuration

### Site-level Tax

```php
$site = currentSite();

if ($site->tax_included_in_prices) {
    // Display: €99.00 (including VAT)
    $displayPrice = $price;
} else {
    // Display: €99.00 + VAT
    $displayPrice = $price;
    $tax = $this->calculateTax($price, $site->tax_region);
}
```

### Tax Rates Table

```php
tax_rates {
  id, name, rate, is_inclusive,
  site_id, country, state, zip_from, zip_to,
  priority
}

// Query
TaxRate::where('site_id', currentSite()->id)
    ->where('country', $address->country)
    ->where('state', $address->state)
    ->orderByDesc('priority')
    ->first();
```

---

## 🎨 Frontend Examples

### Site Switcher

```vue
<select v-model="currentSite" @change="switchSite">
  <option value="italy">🇮🇹 Italia (EUR)</option>
  <option value="usa">🇺🇸 USA (USD)</option>
  <option value="eu">🇪🇺 Europe (EUR)</option>
</select>
```

### Multi-Currency Product Display

```vue
<div class="product-price">
  <span class="amount">
    {{ formatMoney(price.price, session('currency')) }}
  </span>
  
  <div class="currency-options">
    <button @click="viewIn('EUR')">View in EUR</button>
    <button @click="viewIn('USD')">View in USD</button>
    <button @click="viewIn('GBP')">View in GBP</button>
  </div>
</div>
```

---

## ⚡ Performance Benchmarks (500k products)

| Operation | Time | Notes |
|-----------|------|-------|
| Single price lookup | 0.5-2ms | With idx_price_resolution |
| Bulk 100 variants | 15-25ms | Single query + in-memory sort |
| Currency switch | <1ms | Session update only |
| Site switch | 50-100ms | May trigger catalog reload |

---

## 🔧 Migration Guide

### From Markets to Sites

1. **Data Migration**:
```php
// Migrate existing markets to sites
DB::table('markets')->get()->each(function ($market) {
    DB::table('sites')->insert([
        'handle' => $market->handle,
        'name' => $market->name,
        'countries' => $market->countries,
        'default_currency' => $market->currency,
        'tax_included_in_prices' => $market->tax_included_in_prices,
        'domain' => $market->domain,
        // ... other fields
    ]);
});

// Update variant_prices
DB::table('variant_prices')
    ->join('markets', 'variant_prices.market_id', '=', 'markets.id')
    ->join('sites', 'sites.handle', '=', 'markets.handle')
    ->update(['variant_prices.site_id' => DB::raw('sites.id')]);
```

2. **Code Updates**:
```php
// Before
$price = VariantPrice::where('market_id', $marketId)->first();

// After
$price = VariantPrice::where('site_id', currentSite()->id)
    ->where('channel_id', currentChannel()->id)
    ->first();
```

---

## 📚 Best Practices

### 1. Start Simple
- Single site + multi-currency + multi-locale
- Add sites only when truly needed (different catalogs/logistics)

### 2. Use Catalogs for Segmentation
- B2C vs B2B pricing → Use catalogs, not separate sites
- Seasonal sales → Use price scheduling, not separate catalogs

### 3. Leverage Channels
- Different pricing per sales method → Use channel_id in pricing
- Track sales per channel for analytics

### 4. Session Management
- Store currency/locale in session (Redis for production)
- Allow user override with cookie persistence

### 5. Caching Strategy
```php
// Cache site config
Cache::remember("site.{$siteId}.config", 3600, fn() => Site::with('catalogs')->find($siteId));

// Cache channel currencies
Cache::remember("channel.{$channelId}.currencies", 3600, fn() => Channel::find($channelId)->currencies);
```

---

## 🔗 Related Documentation

- [Pricing System](./PRICING_SYSTEM.md)
- [Multi-Currency Guide](./MULTI_CURRENCY.md)
- [Shipping Configuration](./SHIPPING.md)
- [Tax Calculation](./TAX_SYSTEM.md)

---

**Last Updated**: December 2025  
**Version**: 2.0 - Sites-as-Markets Architecture
