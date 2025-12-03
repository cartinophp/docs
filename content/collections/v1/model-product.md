---
id: 2b3c4d5e-6f7a-8b9c-0d1e-2f3a4b5c6d7e
blueprint: v1
title: 'Model: Product'
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264233
---
# Product Model

The `Product` model represents the product container in Cartino's Shopify-style architecture. It holds product-level information while variants contain the actual sellable data.

[TOC]

## Overview

**Namespace**: `App\Models\Product`  
**Table**: `products`  
**Traits**: `SoftDeletes`, `InteractsWithMedia`, `HasFactory`

Every product must have at least one variant, even if it conceptually has no variations.

---

## Schema

```php
products {
  id                  bigint
  site_id             bigint FK → sites
  
  // Identity
  title               string
  slug                string (unique per site)
  handle              string
  description         text
  
  // Classification
  product_type_id     bigint FK → product_types (nullable)
  brand_id            bigint FK → brands (nullable)
  
  // Product Options
  options             jsonb  // [{name: "Color", values: ["Red", "Blue"]}]
  
  // SEO & Meta
  meta_title          string (nullable)
  meta_description    text (nullable)
  seo                 jsonb (nullable)
  
  // Status & Publishing
  status              enum (draft, active, archived)
  published_at        timestamp (nullable)
  published_scope     string (nullable)
  
  // Aggregated Data (computed from variants)
  default_variant_id  bigint FK → product_variants (nullable)
  variants_count      integer (default: 0)
  price_min           decimal (nullable)
  price_max           decimal (nullable)
  
  // Custom Fields
  data                jsonb (nullable)
  
  timestamps, soft_deletes
}
```

---

## Properties

### Fillable

```php
protected $fillable = [
    'site_id',
    'title',
    'slug',
    'handle',
    'description',
    'product_type_id',
    'brand_id',
    'options',
    'meta_title',
    'meta_description',
    'seo',
    'status',
    'published_at',
    'published_scope',
    'data',
];
```

### Casts

```php
protected $casts = [
    'options' => 'array',
    'seo' => 'array',
    'data' => 'array',
    'published_at' => 'datetime',
    'status' => ProductStatus::class,
    'variants_count' => 'integer',
    'price_min' => 'decimal:2',
    'price_max' => 'decimal:2',
];
```

### Hidden

```php
protected $hidden = [
    'deleted_at',
];
```

---

## Relationships

### BelongsTo

```php
// Site (market)
public function site()
{
    return $this->belongsTo(Site::class);
}

// Product Type
public function productType()
{
    return $this->belongsTo(ProductType::class);
}

// Brand
public function brand()
{
    return $this->belongsTo(Brand::class);
}

// Default Variant
public function defaultVariant()
{
    return $this->belongsTo(ProductVariant::class, 'default_variant_id');
}
```

### HasMany

```php
// Variants
public function variants()
{
    return $this->hasMany(ProductVariant::class)
                ->orderBy('position');
}

// Reviews
public function reviews()
{
    return $this->hasMany(ProductReview::class);
}

// Suppliers
public function suppliers()
{
    return $this->belongsToMany(Supplier::class, 'product_suppliers')
                ->withPivot(['cost', 'lead_time_days', 'is_primary'])
                ->withTimestamps();
}
```

### MorphToMany

```php
// Collections
public function collections()
{
    return $this->morphToMany(Collection::class, 'collectable');
}

// Categories (via taxonomy)
public function categories()
{
    return $this->morphToMany(Category::class, 'categorizable');
}
```

### HasManyThrough

```php
// Prices (through variants)
public function prices()
{
    return $this->hasManyThrough(
        VariantPrice::class,
        ProductVariant::class
    );
}
```

---

## Scopes

### Active

```php
public function scopeActive($query)
{
    return $query->where('status', ProductStatus::Active)
                 ->whereNotNull('published_at')
                 ->where('published_at', '<=', now());
}
```

### Published

```php
public function scopePublished($query)
{
    return $query->whereNotNull('published_at')
                 ->where('published_at', '<=', now());
}
```

### ForSite

```php
public function scopeForSite($query, $siteId = null)
{
    $siteId = $siteId ?? currentSite()->id;
    return $query->where('site_id', $siteId);
}
```

### Search

```php
public function scopeSearch($query, $search)
{
    return $query->where(function ($q) use ($search) {
        $q->where('title', 'LIKE', "%{$search}%")
          ->orWhere('description', 'LIKE', "%{$search}%")
          ->orWhere('handle', 'LIKE', "%{$search}%");
    });
}
```

### InPriceRange

```php
public function scopeInPriceRange($query, $min, $max)
{
    return $query->where('price_min', '>=', $min)
                 ->where('price_max', '<=', $max);
}
```

---

## Accessors & Mutators

### Title Accessor

```php
public function getTitleAttribute($value)
{
    return ucfirst($value);
}
```

### Slug Mutator

```php
public function setSlugAttribute($value)
{
    $this->attributes['slug'] = Str::slug($value);
}
```

### Is Published Accessor

```php
public function getIsPublishedAttribute()
{
    return $this->published_at && $this->published_at->isPast();
}
```

---

## Methods

### Generate Variants

```php
public function generateVariants(): Collection
{
    if (!$this->options) {
        return collect();
    }
    
    $combinations = $this->generateOptionCombinations();
    
    foreach ($combinations as $combination) {
        $this->variants()->create([
            'title' => implode(' / ', array_filter($combination)),
            'option1' => $combination[0] ?? null,
            'option2' => $combination[1] ?? null,
            'option3' => $combination[2] ?? null,
            'position' => $this->variants()->count() + 1,
        ]);
    }
    
    return $this->variants()->get();
}

private function generateOptionCombinations(): array
{
    $options = collect($this->options)->pluck('values')->toArray();
    
    if (empty($options)) {
        return [];
    }
    
    $result = [[]];
    foreach ($options as $values) {
        $temp = [];
        foreach ($result as $item) {
            foreach ($values as $value) {
                $temp[] = array_merge($item, [$value]);
            }
        }
        $result = $temp;
    }
    
    return $result;
}
```

### Update Price Range

```php
public function updatePriceRange(): void
{
    $prices = $this->variants()
        ->pluck('price')
        ->filter();
    
    if ($prices->isEmpty()) {
        $this->price_min = null;
        $this->price_max = null;
    } else {
        $this->price_min = $prices->min();
        $this->price_max = $prices->max();
    }
    
    $this->saveQuietly();
}
```

### Update Variants Count

```php
public function updateVariantsCount(): void
{
    $this->variants_count = $this->variants()->count();
    $this->saveQuietly();
}
```

### Duplicate

```php
public function duplicate(array $overrides = []): Product
{
    $newProduct = $this->replicate();
    $newProduct->fill($overrides);
    $newProduct->save();
    
    // Duplicate variants
    foreach ($this->variants as $variant) {
        $newVariant = $variant->replicate();
        $newVariant->product_id = $newProduct->id;
        $newVariant->save();
    }
    
    // Copy media
    foreach ($this->getMedia('images') as $media) {
        $media->copy($newProduct, 'images');
    }
    
    return $newProduct->fresh();
}
```

---

## Media Collections

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('images')
         ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
         ->registerMediaConversions(function (Media $media) {
             $this->addMediaConversion('thumb')
                  ->width(200)
                  ->height(200)
                  ->sharpen(10);
             
             $this->addMediaConversion('large')
                  ->width(1200)
                  ->height(1200)
                  ->optimize();
         });
         
    $this->addMediaCollection('documents')
         ->acceptsMimeTypes(['application/pdf']);
}
```

---

## Events

### Model Events

```php
protected static function booted()
{
    static::creating(function ($product) {
        if (!$product->slug) {
            $product->slug = Str::slug($product->title);
        }
        
        if (!$product->handle) {
            $product->handle = Str::slug($product->title);
        }
    });
    
    static::created(function ($product) {
        // Create default variant if no options
        if (!$product->options) {
            $variant = $product->variants()->create([
                'title' => 'Default Title',
                'position' => 1,
            ]);
            
            $product->default_variant_id = $variant->id;
            $product->saveQuietly();
        }
    });
    
    static::deleting(function ($product) {
        // Delete associated variants
        $product->variants()->delete();
    });
}
```

---

## Usage Examples

### Creating a Simple Product

```php
$product = Product::create([
    'site_id' => 1,
    'title' => 'Laravel E-book',
    'description' => 'Complete Laravel guide',
    'product_type_id' => 1,
    'status' => ProductStatus::Active,
    'published_at' => now(),
]);

// Default variant is created automatically
```

### Creating a Multi-Variant Product

```php
$product = Product::create([
    'site_id' => 1,
    'title' => 'Cotton T-Shirt',
    'product_type_id' => 2,
    'options' => [
        ['name' => 'Color', 'values' => ['Red', 'Blue', 'Black']],
        ['name' => 'Size', 'values' => ['Small', 'Medium', 'Large']],
    ],
    'status' => ProductStatus::Active,
]);

// Generate all variant combinations
$product->generateVariants();
```

### Querying Products

```php
// Active products for current site
$products = Product::active()
    ->forSite()
    ->with(['variants', 'brand'])
    ->paginate(20);

// Search products
$results = Product::search('t-shirt')
    ->active()
    ->get();

// Products in price range
$affordable = Product::inPriceRange(10, 50)
    ->active()
    ->get();

// Products with reviews
$products = Product::has('reviews', '>', 5)
    ->withAvg('reviews', 'rating')
    ->get();
```

### Updating Product

```php
$product->update([
    'title' => 'Updated Title',
    'description' => 'New description',
]);

// Update custom fields
$product->data = array_merge($product->data ?? [], [
    'fabric' => 'Cotton',
    'sustainability_badge' => 'Organic',
]);
$product->save();
```

---

## API Resource

```php
namespace App\Http\Resources;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status->value,
            'price_range' => [
                'min' => $this->price_min,
                'max' => $this->price_max,
            ],
            'variants_count' => $this->variants_count,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'type' => new ProductTypeResource($this->whenLoaded('productType')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'images' => $this->getMedia('images')->map(fn($m) => [
                'url' => $m->getUrl(),
                'thumb' => $m->getUrl('thumb'),
            ]),
            'custom_fields' => $this->data,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

---

## Related Models

- [**ProductVariant**](/v1/models/product-variant) - Sellable variants
- [**ProductType**](/v1/models/product-type) - Product categorization
- [**Brand**](/v1/models/brand) - Product brands
- [**VariantPrice**](/v1/models/variant-price) - Multi-currency pricing
- [**ProductReview**](/v1/models/product-review) - Customer reviews

---

## See Also

- [**Product Architecture**](/v1/product-architecture) - System overview
- [**REST API - Products**](/v1/rest-api/products) - API endpoints
- [**GraphQL - Products**](/v1/graphql/products) - GraphQL queries
