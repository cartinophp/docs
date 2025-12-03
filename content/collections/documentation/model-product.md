---
id: model-product
blueprint: documentation
title: 'Model: Product'
updated_by: system
updated_at: 1738675127
---
# Model: Product

The Product model represents a product in your catalog. Every product in Cartino follows the Shopify pattern where products are containers for variants.

[TOC]

## Overview

In Cartino, a **Product** is a high-level container that groups related variants together. The actual sellable items are **ProductVariants**.

```php
Product {
    title: "Cotton T-Shirt"
    type: "apparel"
    options: [Color, Size]
} → generates → ProductVariant[]
```

**Key Concept**: Even simple products without options have at least one default variant. This ensures consistency across your catalog.

---

## Database Schema

### `products` Table

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('type')->default('physical'); // physical, digital, service
    $table->string('vendor')->nullable();

    // Relations
    $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('product_type_id')->nullable()->constrained()->nullOnDelete();

    // SEO
    $table->string('seo_title', 60)->nullable();
    $table->text('seo_description')->nullable();

    // Status
    $table->string('status')->default('draft'); // draft, active, archived
    $table->timestamp('published_at')->nullable();

    // Custom Fields (JSONB)
    $table->json('data')->nullable();

    // Timestamps
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('status');
    $table->index('published_at');
    $table->index('brand_id');
    $table->index('product_type_id');
    $table->fullText(['title', 'description']);
});
```

---

## Properties

### Core Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | bigint | Primary key |
| `title` | string | Product name (required) |
| `slug` | string | URL-friendly identifier (unique) |
| `description` | text | Full product description |
| `type` | string | `physical`, `digital`, `service` |
| `vendor` | string | Vendor/supplier name |
| `status` | string | `draft`, `active`, `archived` |
| `published_at` | timestamp | Publication date |

### Relationships

| Property | Type | Description |
|----------|------|-------------|
| `brand_id` | foreignId | Brand association |
| `product_type_id` | foreignId | Product type/category |
| `variants` | hasMany | ProductVariant collection |
| `options` | hasMany | ProductOption collection |
| `categories` | morphToMany | Category taxonomy |
| `collections` | belongsToMany | Collection groups |
| `media` | morphMany | Images and files |

### Custom Data (JSONB)

The `data` column stores custom fields defined in blueprints:

```php
$product->data = [
    'meta_title' => 'SEO Title',
    'badge' => 'New Arrival',
    'specifications' => [
        'material' => 'Cotton',
        'care' => 'Machine wash cold'
    ],
    'features' => [
        'Breathable fabric',
        'Moisture-wicking',
        'UPF 50+ protection'
    ]
];
```

---

## Eloquent Model

### Basic Usage

```php
use Shopper\Models\Product;

// Create product
$product = Product::create([
    'title' => 'Cotton T-Shirt',
    'slug' => 'cotton-t-shirt',
    'description' => 'Comfortable cotton t-shirt',
    'type' => 'physical',
    'status' => 'active',
    'brand_id' => 1,
]);

// Find by ID
$product = Product::find(1);

// Find by slug
$product = Product::where('slug', 'cotton-t-shirt')->first();

// Update
$product->update(['title' => 'Premium Cotton T-Shirt']);

// Soft delete
$product->delete();

// Restore
$product->restore();

// Force delete
$product->forceDelete();
```

---

## Relationships

### Variants

Every product has at least one variant:

```php
// Get all variants
$variants = $product->variants;

// Get default variant
$defaultVariant = $product->defaultVariant();

// Create variant manually
$variant = $product->variants()->create([
    'sku' => 'TS-RED-M',
    'title' => 'Cotton T-Shirt - Red / M',
    'option1' => 'Red',
    'option2' => 'M',
    'price' => 19.99,
]);

// Get variant by options
$variant = $product->findVariantByOptions(['Red', 'M']);
```

### Options

Options define the variant matrix:

```php
// Get options
$options = $product->options;

// Create options
$colorOption = $product->options()->create([
    'name' => 'Color',
    'position' => 1,
]);

$colorOption->values()->createMany([
    ['value' => 'Red', 'position' => 1],
    ['value' => 'Blue', 'position' => 2],
    ['value' => 'Green', 'position' => 3],
]);

// Generate variants from options
$product->generateVariants();
```

### Brand

```php
// Get brand
$brand = $product->brand;

// Set brand
$product->brand()->associate($brand);
$product->save();

// Products by brand
$products = Brand::find(1)->products;
```

### Categories (Taxonomy)

```php
// Attach categories
$product->categories()->attach([1, 2, 3]);

// Detach categories
$product->categories()->detach([2]);

// Sync categories
$product->categories()->sync([1, 3, 5]);

// Get with categories
$products = Product::with('categories')->get();
```

### Collections

```php
// Attach to collection
$product->collections()->attach($collectionId);

// Get collections
$collections = $product->collections;

// Products in collection
$products = Collection::find(1)->products;
```

### Media (Images)

```php
use Spatie\MediaLibrary\MediaCollections\Models\Media;

// Add image
$product->addMedia($filePath)
    ->toMediaCollection('images');

// Add featured image
$product->addMedia($filePath)
    ->toMediaCollection('featured');

// Get all images
$images = $product->getMedia('images');

// Get featured image
$featuredImage = $product->getFirstMedia('featured');

// Get image URL
$url = $product->getFirstMediaUrl('featured', 'thumb');
```

---

## Scopes

### Query Scopes

```php
// Active products only
Product::active()->get();

// Published products
Product::published()->get();

// By status
Product::status('active')->get();

// By type
Product::ofType('physical')->get();

// By brand
Product::brand($brandId)->get();

// Search
Product::search('cotton')->get();

// With variants
Product::with('variants')->get();
```

### Scope Definitions

```php
// app/Models/Product.php

public function scopeActive($query)
{
    return $query->where('status', 'active');
}

public function scopePublished($query)
{
    return $query->where('status', 'active')
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now());
}

public function scopeStatus($query, $status)
{
    return $query->where('status', $status);
}

public function scopeOfType($query, $type)
{
    return $query->where('type', $type);
}

public function scopeBrand($query, $brandId)
{
    return $query->where('brand_id', $brandId);
}

public function scopeSearch($query, $term)
{
    return $query->whereFullText(['title', 'description'], $term);
}
```

---

## Accessors & Mutators

### Accessors

```php
// Get full title with brand
public function getFullTitleAttribute(): string
{
    return $this->brand
        ? "{$this->brand->name} {$this->title}"
        : $this->title;
}

// Get lowest price from variants
public function getLowestPriceAttribute(): ?float
{
    return $this->variants()->min('price');
}

// Get highest price from variants
public function getHighestPriceAttribute(): ?float
{
    return $this->variants()->max('price');
}

// Check if product has multiple variants
public function getHasMultipleVariantsAttribute(): bool
{
    return $this->variants()->count() > 1;
}

// Check if in stock (any variant)
public function getInStockAttribute(): bool
{
    return $this->variants()->where('inventory_quantity', '>', 0)->exists();
}

// Usage
echo $product->full_title;
echo money($product->lowest_price);
if ($product->in_stock) { ... }
```

### Mutators

```php
// Auto-generate slug from title
public function setTitleAttribute($value)
{
    $this->attributes['title'] = $value;

    if (empty($this->attributes['slug'])) {
        $this->attributes['slug'] = Str::slug($value);
    }
}

// Sanitize description
public function setDescriptionAttribute($value)
{
    $this->attributes['description'] = clean($value);
}

// Usage
$product->title = 'My New Product'; // slug auto-generated
```

---

## Methods

### Publishing

```php
// Publish product
public function publish(): void
{
    $this->update([
        'status' => 'active',
        'published_at' => now(),
    ]);
}

// Unpublish
public function unpublish(): void
{
    $this->update([
        'status' => 'draft',
        'published_at' => null,
    ]);
}

// Archive
public function archive(): void
{
    $this->update(['status' => 'archived']);
}

// Check if published
public function isPublished(): bool
{
    return $this->status === 'active'
        && $this->published_at !== null
        && $this->published_at <= now();
}
```

### Variant Management

```php
// Generate variants from options
public function generateVariants(): void
{
    $optionsCombinations = $this->getOptionsCombinations();

    foreach ($optionsCombinations as $combination) {
        $this->variants()->firstOrCreate([
            'option1' => $combination[0] ?? null,
            'option2' => $combination[1] ?? null,
            'option3' => $combination[2] ?? null,
        ], [
            'title' => $this->buildVariantTitle($combination),
            'sku' => $this->generateSku($combination),
        ]);
    }
}

// Find variant by options
public function findVariantByOptions(array $options): ?ProductVariant
{
    return $this->variants()
        ->where('option1', $options[0] ?? null)
        ->where('option2', $options[1] ?? null)
        ->where('option3', $options[2] ?? null)
        ->first();
}

// Get default variant
public function defaultVariant(): ?ProductVariant
{
    return $this->variants()->orderBy('position')->first();
}
```

### Stock Management

```php
// Total inventory across all variants
public function totalInventory(): int
{
    return $this->variants()->sum('inventory_quantity');
}

// Check if any variant is in stock
public function hasStock(): bool
{
    return $this->variants()->where('inventory_quantity', '>', 0)->exists();
}

// Check if product is low stock
public function isLowStock(int $threshold = 10): bool
{
    return $this->totalInventory() <= $threshold && $this->totalInventory() > 0;
}

// Check if out of stock
public function isOutOfStock(): bool
{
    return $this->totalInventory() === 0;
}
```

---

## Events

### Model Events

```php
use Shopper\Events\ProductCreated;
use Shopper\Events\ProductUpdated;
use Shopper\Events\ProductDeleted;
use Shopper\Events\ProductPublished;

// Listen to product events
Event::listen(ProductCreated::class, function ($event) {
    $product = $event->product;
    // Handle product creation
});

Event::listen(ProductPublished::class, function ($event) {
    $product = $event->product;
    // Send notifications, update cache, etc.
});
```

### Eloquent Events

```php
// In Product model
protected static function booted()
{
    static::creating(function ($product) {
        // Generate default variant if none exist
        if ($product->variants()->count() === 0) {
            $product->variants()->create([
                'title' => $product->title,
                'sku' => Str::slug($product->title),
            ]);
        }
    });

    static::updating(function ($product) {
        // Clear cache when updated
        Cache::forget("product.{$product->id}");
    });

    static::deleting(function ($product) {
        // Delete variants
        $product->variants()->delete();

        // Delete media
        $product->clearMediaCollection('images');
    });
}
```

---

## API Endpoints

### REST API

```http
# List products
GET /api/products

# Get single product
GET /api/products/{id}

# Create product
POST /api/products

# Update product
PUT /api/products/{id}

# Delete product
DELETE /api/products/{id}

# Publish product
POST /api/products/{id}/publish

# Get product variants
GET /api/products/{id}/variants

# Search products
GET /api/products?search=cotton&status=active
```

### GraphQL API

```graphql
# Query single product
query {
  product(id: 1) {
    id
    title
    slug
    description
    status
    brand {
      id
      name
    }
    variants {
      id
      sku
      price
      inventory_quantity
    }
    categories {
      id
      name
    }
  }
}

# Query products list
query {
  products(status: "active", first: 10) {
    edges {
      node {
        id
        title
        slug
        lowest_price
        in_stock
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}

# Create product
mutation {
  createProduct(input: {
    title: "New Product"
    description: "Product description"
    type: "physical"
    status: "draft"
  }) {
    product {
      id
      title
      slug
    }
  }
}

# Update product
mutation {
  updateProduct(id: 1, input: {
    title: "Updated Title"
    status: "active"
  }) {
    product {
      id
      title
      status
    }
  }
}
```

---

## Examples

### Creating a Simple Product

```php
// Simple product (no options)
$product = Product::create([
    'title' => 'Coffee Mug',
    'slug' => 'coffee-mug',
    'description' => 'Ceramic coffee mug',
    'type' => 'physical',
    'status' => 'active',
    'published_at' => now(),
]);

// Add image
$product->addMedia(storage_path('temp/mug.jpg'))
    ->toMediaCollection('featured');

// Default variant is auto-created
$variant = $product->defaultVariant();
$variant->update([
    'sku' => 'MUG-001',
    'price' => 12.99,
    'inventory_quantity' => 100,
]);
```

### Creating a Product with Options

```php
// Product with Color and Size options
$product = Product::create([
    'title' => 'Cotton T-Shirt',
    'slug' => 'cotton-t-shirt',
    'description' => 'Comfortable cotton t-shirt',
    'type' => 'physical',
    'status' => 'active',
]);

// Add Color option
$colorOption = $product->options()->create([
    'name' => 'Color',
    'position' => 1,
]);

$colorOption->values()->createMany([
    ['value' => 'Red', 'position' => 1],
    ['value' => 'Blue', 'position' => 2],
    ['value' => 'Black', 'position' => 3],
]);

// Add Size option
$sizeOption = $product->options()->create([
    'name' => 'Size',
    'position' => 2,
]);

$sizeOption->values()->createMany([
    ['value' => 'S', 'position' => 1],
    ['value' => 'M', 'position' => 2],
    ['value' => 'L', 'position' => 3],
]);

// Generate variants (3 colors × 3 sizes = 9 variants)
$product->generateVariants();

// Set prices for each variant
foreach ($product->variants as $variant) {
    $variant->update([
        'sku' => "TS-{$variant->option1}-{$variant->option2}",
        'price' => 19.99,
        'inventory_quantity' => 50,
    ]);
}
```

### Querying Products

```php
// Get active products with brand and variants
$products = Product::active()
    ->with(['brand', 'variants', 'media'])
    ->published()
    ->paginate(20);

// Search products
$results = Product::search('cotton')
    ->active()
    ->get();

// Products by brand
$products = Product::where('brand_id', 1)
    ->with('variants')
    ->get();

// Products in stock
$products = Product::whereHas('variants', function ($query) {
    $query->where('inventory_quantity', '>', 0);
})->get();

// Low stock products
$lowStock = Product::with('variants')
    ->get()
    ->filter(fn($p) => $p->isLowStock());
```

---

## Blueprint Integration

Products use blueprints for custom fields:

```yaml
# resources/blueprints/products/product.yaml
title: Product
sections:
  main:
    fields:
      - handle: title
        field: { type: text, validate: required }
      - handle: description
        field: { type: markdown }

  custom:
    display: Custom Fields
    fields:
      - handle: material
        field: { type: text }
      - handle: care_instructions
        field: { type: textarea }
      - handle: features
        field: { type: list }
```

Access custom fields:

```php
// Set custom fields
$product->data = [
    'material' => 'Cotton',
    'care_instructions' => 'Machine wash cold',
    'features' => ['Breathable', 'Soft', 'Durable'],
];
$product->save();

// Get custom fields
$material = $product->data['material'] ?? 'Unknown';
```

---

## Performance Tips

### Eager Loading

```php
// Load relations to avoid N+1 queries
$products = Product::with([
    'brand',
    'variants.prices',
    'categories',
    'media',
])->get();
```

### Caching

```php
// Cache product data
$product = Cache::remember("product.{$id}", 3600, function () use ($id) {
    return Product::with(['brand', 'variants', 'media'])->find($id);
});

// Clear cache on update
$product->save();
Cache::forget("product.{$product->id}");
```

### Indexing

Make sure you have proper database indexes:

```php
// In migration
$table->index('status');
$table->index('brand_id');
$table->index(['status', 'published_at']);
$table->fullText(['title', 'description']);
```

---

## Related Documentation

- [ProductVariant Model](/docs/model-product-variant)
- [ProductOption Model](/docs/model-product-option)
- [Brand Model](/docs/model-brand)
- [Inventory Management](/docs/inventory-management)
- [Pricing System](/docs/pricing-system)
- [REST API - Products](/docs/api-products)
- [GraphQL API - Products](/docs/graphql-products)
