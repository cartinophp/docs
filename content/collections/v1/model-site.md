---
id: 6f7a8b9c-0d1e-2f3a-4b5c-6d7e8f9a0b1c
blueprint: v1
title: 'Model: Site'
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264234
---
# 🌍 Site Model

The `Site` model represents a Market in Cartino's multi-site architecture. Each site has its own domain, currencies, languages, tax rules, and product catalog.

[TOC]

## 📋 Overview

**Namespace**: `App\Models\Site`  
**Table**: `sites`  
**Traits**: `HasFactory`, `SoftDeletes`

Sites implement the "**Markets**" concept: each site is a separate storefront with localized settings.

---

## 🗄️ Schema

```php
sites {
  id                    bigint
  
  // Identity
  name                  string
  slug                  string (unique)
  domain                string (unique)
  
  // Localization
  default_locale        string (default: 'en')
  available_locales     jsonb (array)
  default_currency      string (default: 'EUR')
  available_currencies  jsonb (array)
  timezone              string (default: 'UTC')
  
  // Regional
  country_code          string (ISO 3166-1 alpha-2)
  vat_region            string (nullable, EU|UK|US|CA|AU)
  
  // Contact
  email                 string (nullable)
  phone                 string (nullable)
  
  // Configuration
  is_active             boolean (default: true)
  is_default            boolean (default: false)
  
  // Pricing
  prices_include_tax    boolean (default: true)
  tax_enabled           boolean (default: true)
  
  // SEO
  meta_title            string (nullable)
  meta_description      text (nullable)
  meta_keywords         text (nullable)
  
  // Tracking
  google_analytics_id   string (nullable)
  facebook_pixel_id     string (nullable)
  
  // Features
  features              jsonb (nullable)
  
  // Settings
  settings              jsonb (nullable)
  
  timestamps, soft_deletes
}
```

---

## 🔧 Properties

### Fillable

```php
protected $fillable = [
    'name',
    'slug',
    'domain',
    'default_locale',
    'available_locales',
    'default_currency',
    'available_currencies',
    'timezone',
    'country_code',
    'vat_region',
    'email',
    'phone',
    'is_active',
    'is_default',
    'prices_include_tax',
    'tax_enabled',
    'meta_title',
    'meta_description',
    'meta_keywords',
    'google_analytics_id',
    'facebook_pixel_id',
    'features',
    'settings',
];
```

### Casts

```php
protected $casts = [
    'is_active' => 'boolean',
    'is_default' => 'boolean',
    'prices_include_tax' => 'boolean',
    'tax_enabled' => 'boolean',
    'available_locales' => 'array',
    'available_currencies' => 'array',
    'features' => 'array',
    'settings' => 'array',
];
```

---

## 🔗 Relationships

### HasMany

```php
public function channels()
{
    return $this->hasMany(Channel::class);
}

public function customers()
{
    return $this->hasMany(Customer::class);
}

public function orders()
{
    return $this->hasMany(Order::class);
}

public function products()
{
    return $this->hasMany(Product::class);
}

public function carts()
{
    return $this->hasMany(Cart::class);
}

public function taxRules()
{
    return $this->hasMany(TaxRule::class);
}

public function shippingMethods()
{
    return $this->hasMany(ShippingMethod::class);
}

public function paymentMethods()
{
    return $this->hasMany(PaymentMethod::class);
}
```

### BelongsToMany

```php
public function brands()
{
    return $this->belongsToMany(Brand::class, 'site_brands')
                ->withTimestamps();
}

public function categories()
{
    return $this->belongsToMany(Category::class, 'site_categories')
                ->withTimestamps();
}
```

---

## 🔍 Scopes

### Active

```php
public function scopeActive($query)
{
    return $query->where('is_active', true);
}
```

### Default

```php
public function scopeDefault($query)
{
    return $query->where('is_default', true);
}
```

### For Domain

```php
public function scopeForDomain($query, string $domain)
{
    return $query->where('domain', $domain);
}
```

### With Currency

```php
public function scopeWithCurrency($query, string $currency)
{
    return $query->whereJsonContains('available_currencies', $currency);
}
```

---

## 🎯 Accessors

### Full URL

```php
public function getFullUrlAttribute(): string
{
    $protocol = config('app.env') === 'production' ? 'https' : 'http';
    return "{$protocol}://{$this->domain}";
}
```

### Default Channel

```php
public function getDefaultChannelAttribute(): ?Channel
{
    return $this->channels()->where('is_default', true)->first();
}
```

---

## ⚡ Methods

### Supports Currency

```php
public function supportsCurrency(string $currency): bool
{
    return in_array($currency, $this->available_currencies ?? []);
}
```

### Supports Locale

```php
public function supportsLocale(string $locale): bool
{
    return in_array($locale, $this->available_locales ?? []);
}
```

### Get Tax Rate

```php
public function getTaxRate(string $taxCategory = 'standard', ?string $region = null): float
{
    if (!$this->tax_enabled) {
        return 0;
    }
    
    $taxRule = $this->taxRules()
        ->where('category', $taxCategory)
        ->when($region, fn($q) => $q->where('region', $region))
        ->first();
    
    return $taxRule?->rate ?? 0;
}
```

### Calculate Price With Tax

```php
public function calculatePriceWithTax(float $basePrice, string $taxCategory = 'standard'): array
{
    $taxRate = $this->getTaxRate($taxCategory);
    
    if ($this->prices_include_tax) {
        // Price already includes tax, extract tax amount
        $taxAmount = round($basePrice * ($taxRate / (100 + $taxRate)), 2);
        $priceExcludingTax = $basePrice - $taxAmount;
        
        return [
            'price' => $basePrice,
            'price_excluding_tax' => $priceExcludingTax,
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxRate,
        ];
    }
    
    // Price excludes tax, add tax amount
    $taxAmount = round($basePrice * ($taxRate / 100), 2);
    $priceIncludingTax = $basePrice + $taxAmount;
    
    return [
        'price' => $priceIncludingTax,
        'price_excluding_tax' => $basePrice,
        'tax_amount' => $taxAmount,
        'tax_rate' => $taxRate,
    ];
}
```

### Is Feature Enabled

```php
public function isFeatureEnabled(string $feature): bool
{
    return $this->features[$feature] ?? false;
}
```

### Get Setting

```php
public function getSetting(string $key, $default = null)
{
    return data_get($this->settings, $key, $default);
}
```

### Set Setting

```php
public function setSetting(string $key, $value): void
{
    $settings = $this->settings ?? [];
    data_set($settings, $key, $value);
    $this->settings = $settings;
    $this->save();
}
```

### Clone For New Market

```php
public function cloneForNewMarket(array $attributes): Site
{
    $clone = $this->replicate();
    $clone->fill($attributes);
    $clone->is_default = false;
    $clone->save();
    
    // Clone channels
    foreach ($this->channels as $channel) {
        $clonedChannel = $channel->replicate();
        $clonedChannel->site_id = $clone->id;
        $clonedChannel->is_default = $channel->is_default;
        $clonedChannel->save();
    }
    
    // Clone tax rules
    foreach ($this->taxRules as $taxRule) {
        $clonedTax = $taxRule->replicate();
        $clonedTax->site_id = $clone->id;
        $clonedTax->save();
    }
    
    return $clone;
}
```

---

## 🎪 Events

```php
protected static function booted()
{
    static::creating(function ($site) {
        if (!$site->slug) {
            $site->slug = Str::slug($site->name);
        }
        
        // Ensure only one default site
        if ($site->is_default) {
            static::where('is_default', true)->update(['is_default' => false]);
        }
    });
    
    static::created(function ($site) {
        // Create default channel
        $site->channels()->create([
            'name' => 'Web',
            'slug' => 'web',
            'type' => 'online',
            'is_default' => true,
            'is_active' => true,
        ]);
        
        event(new SiteCreated($site));
    });
    
    static::updating(function ($site) {
        // Ensure only one default site
        if ($site->is_default && $site->isDirty('is_default')) {
            static::where('id', '!=', $site->id)
                  ->where('is_default', true)
                  ->update(['is_default' => false]);
        }
    });
}
```

---

## 💡 Usage Examples

### Creating a Site

```php
$site = Site::create([
    'name' => 'Cartino Italy',
    'slug' => 'it',
    'domain' => 'cartino.it',
    'default_locale' => 'it',
    'available_locales' => ['it', 'en'],
    'default_currency' => 'EUR',
    'available_currencies' => ['EUR'],
    'timezone' => 'Europe/Rome',
    'country_code' => 'IT',
    'vat_region' => 'EU',
    'email' => 'support@cartino.it',
    'phone' => '+39 02 1234 5678',
    'is_active' => true,
    'prices_include_tax' => true,
    'tax_enabled' => true,
]);

// Add tax rules
$site->taxRules()->create([
    'category' => 'standard',
    'region' => 'IT',
    'rate' => 22.00, // 22% IVA
    'name' => 'IVA Standard',
]);
```

### Multi-Currency Pricing

```php
$site = Site::find(1);

// Check if currency is supported
if ($site->supportsCurrency('USD')) {
    // Calculate price with tax
    $priceData = $site->calculatePriceWithTax(99.99, 'standard');
    
    // $priceData = [
    //     'price' => 99.99,
    //     'price_excluding_tax' => 81.96,
    //     'tax_amount' => 18.03,
    //     'tax_rate' => 22.00,
    // ]
}
```

### Site Detection Middleware

```php
class DetectSite
{
    public function handle($request, Closure $next)
    {
        $domain = $request->getHost();
        
        $site = Site::active()
            ->forDomain($domain)
            ->firstOr(fn() => Site::default()->first());
        
        if (!$site) {
            abort(404, 'Site not found');
        }
        
        app()->instance('currentSite', $site);
        app()->setLocale($site->default_locale);
        
        return $next($request);
    }
}
```

### Querying Sites

```php
// Active sites with EUR
$euroSites = Site::active()
    ->withCurrency('EUR')
    ->get();

// Sites in EU VAT region
$euSites = Site::where('vat_region', 'EU')
    ->with(['taxRules', 'channels'])
    ->get();
```

---

## 🌐 API Resource

```php
class SiteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'full_url' => $this->full_url,
            'localization' => [
                'default_locale' => $this->default_locale,
                'available_locales' => $this->available_locales,
                'default_currency' => $this->default_currency,
                'available_currencies' => $this->available_currencies,
                'timezone' => $this->timezone,
            ],
            'regional' => [
                'country_code' => $this->country_code,
                'vat_region' => $this->vat_region,
            ],
            'tax_settings' => [
                'enabled' => $this->tax_enabled,
                'prices_include_tax' => $this->prices_include_tax,
            ],
            'contact' => [
                'email' => $this->email,
                'phone' => $this->phone,
            ],
            'seo' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
            ],
            'analytics' => [
                'google_analytics_id' => $this->google_analytics_id,
                'facebook_pixel_id' => $this->facebook_pixel_id,
            ],
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'channels' => ChannelResource::collection($this->whenLoaded('channels')),
        ];
    }
}
```

---

## 🔗 Related Models

- [**Channel**](/v1/models/channel) - Sales channels
- [**TaxRule**](/v1/models/tax-rule) - Tax configuration
- [**Customer**](/v1/models/customer) - Site customers
- [**Product**](/v1/models/product) - Site products

---

## 📚 See Also

- [**Sites Architecture**](/v1/sites-architecture) - Multi-site guide
- [**Tax Configuration**](/v1/tax-configuration) - Tax rules
- [**REST API - Sites**](/v1/rest-api/sites) - API endpoints
