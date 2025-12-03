---
id: 7a8b9c0d-1e2f-3a4b-5c6d-7e8f9a0b1c2d
blueprint: v1
title: 'Model: Channel'
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264235
---
# 📺 Channel Model

The `Channel` model represents a sales channel within a site (e.g., Web, Mobile App, POS, Marketplace). Each channel can have separate pricing, inventory, and settings.

[TOC]

## 📋 Overview

**Namespace**: `App\Models\Channel`  
**Table**: `channels`  
**Traits**: `HasFactory`, `SoftDeletes`

Channels allow products to be sold through different platforms with channel-specific configurations.

---

## 🗄️ Schema

```php
channels {
  id              bigint
  site_id         bigint FK → sites
  
  // Identity
  name            string
  slug            string
  
  // Type
  type            enum (online, mobile, pos, marketplace, wholesale)
  
  // Status
  is_active       boolean (default: true)
  is_default      boolean (default: false)
  
  // URLs
  url             string (nullable)
  api_url         string (nullable)
  
  // Configuration
  settings        jsonb (nullable)
  
  timestamps, soft_deletes
}
```

---

## 🔧 Properties

### Fillable

```php
protected $fillable = [
    'site_id',
    'name',
    'slug',
    'type',
    'is_active',
    'is_default',
    'url',
    'api_url',
    'settings',
];
```

### Casts

```php
protected $casts = [
    'is_active' => 'boolean',
    'is_default' => 'boolean',
    'type' => ChannelType::class,
    'settings' => 'array',
];
```

---

## 🔗 Relationships

### BelongsTo

```php
public function site()
{
    return $this->belongsTo(Site::class);
}
```

### HasMany

```php
public function products()
{
    return $this->hasMany(Product::class);
}

public function orders()
{
    return $this->hasMany(Order::class);
}

public function prices()
{
    return $this->hasMany(ChannelPrice::class);
}

public function inventory()
{
    return $this->hasMany(ChannelInventory::class);
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

### OfType

```php
public function scopeOfType($query, ChannelType $type)
{
    return $query->where('type', $type);
}
```

---

## ⚡ Methods

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

---

## 🎪 Events

```php
protected static function booted()
{
    static::creating(function ($channel) {
        if (!$channel->slug) {
            $channel->slug = Str::slug($channel->name);
        }
        
        // Ensure only one default per site
        if ($channel->is_default) {
            static::where('site_id', $channel->site_id)
                  ->where('is_default', true)
                  ->update(['is_default' => false]);
        }
    });
}
```

---

## 💡 Usage Examples

```php
$channel = Channel::create([
    'site_id' => $site->id,
    'name' => 'Mobile App',
    'slug' => 'mobile-app',
    'type' => ChannelType::Mobile,
    'is_active' => true,
    'url' => 'https://app.cartino.com',
    'api_url' => 'https://api.cartino.com',
]);
```

---

## 🌐 API Resource

```php
class ChannelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'url' => $this->url,
            'site' => new SiteResource($this->whenLoaded('site')),
        ];
    }
}
```

---

## 🔗 Related Models

- [**Site**](/v1/models/site) - Parent site
- [**Product**](/v1/models/product) - Channel products
- [**Order**](/v1/models/order) - Channel orders
