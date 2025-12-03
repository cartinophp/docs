---
id: model-product-variant
blueprint: documentation
title: 'Model: ProductVariant'
updated_by: system
updated_at: 1738675127
---
# Model: ProductVariant

ProductVariant represents the actual sellable SKU in your catalog. Every product has at least one variant, following the Shopify pattern.

[TOC]

## Overview

In Cartino's architecture, **ProductVariant** is the core inventory and pricing unit. While Products are containers, Variants are what customers actually purchase.

```php
ProductVariant {
    product_id: 1
    sku: "TS-RED-M"
    title: "Cotton T-Shirt - Red / M"
    option1: "Red"    // Color
    option2: "M"      // Size
    option3: null
    price: 19.99
    inventory_quantity: 50
}
```

**Key Concept**: Even simple products without options have one default variant. This ensures cart, pricing, and inventory systems work consistently.

---

## Database Schema

### `product_variants` Table

```php
Schema::create('product_variants', function (Blueprint $table) {
    $table->id();

    // Parent Product
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();

    // Variant Identity
    $table->string('title');
    $table->string('sku')->unique();
    $table->string('barcode')->nullable()->unique();

    // Options (up to 3, Shopify-style)
    $table->string('option1')->nullable(); // e.g., "Red"
    $table->string('option2')->nullable(); // e.g., "M"
    $table->string('option3')->nullable(); // e.g., null

    // Pricing (base price, can be overridden by variant_prices table)
    $table->decimal('price', 15, 2)->default(0);
    $table->decimal('compare_at_price', 15, 2)->nullable();
    $table->decimal('cost_per_item', 15, 2)->nullable();

    // Inventory
    $table->integer('inventory_quantity')->default(0);
    $table->string('inventory_policy')->default('deny'); // deny, continue
    $table->boolean('track_inventory')->default(true);

    // Physical Properties
    $table->decimal('weight', 10, 2)->nullable();
    $table->string('weight_unit')->default('kg'); // kg, lb
    $table->decimal('length', 10, 2)->nullable();
    $table->decimal('width', 10, 2)->nullable();
    $table->decimal('height', 10, 2)->nullable();
    $table->string('dimension_unit')->default('cm'); // cm, in

    // Shipping
    $table->boolean('requires_shipping')->default(true);
    $table->boolean('taxable')->default(true);

    // Meta
    $table->integer('position')->default(0);
    $table->string('image_url')->nullable();

    // Custom Fields (JSONB)
    $table->json('data')->nullable();

    // Timestamps
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('product_id');
    $table->index('sku');
    $table->index('barcode');
    $table->index(['product_id', 'option1', 'option2', 'option3']);
    $table->index('inventory_quantity');
});
```

---

## Properties

### Core Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | bigint | Primary key |
| `product_id` | foreignId | Parent product |
| `title` | string | Variant display name |
| `sku` | string | Stock keeping unit (unique) |
| `barcode` | string | UPC/EAN barcode |
| `position` | integer | Sort order |

### Options (Shopify Pattern)

| Property | Type | Description |
|----------|------|-------------|
| `option1` | string | First option value (e.g., "Red") |
| `option2` | string | Second option value (e.g., "M") |
| `option3` | string | Third option value (optional) |

### Pricing

| Property | Type | Description |
|----------|------|-------------|
| `price` | decimal(15,2) | Base price |
| `compare_at_price` | decimal(15,2) | Original price (for discounts) |
| `cost_per_item` | decimal(15,2) | Cost/COGS |

### Inventory

| Property | Type | Description |
|----------|------|-------------|
| `inventory_quantity` | integer | Stock on hand |
| `inventory_policy` | string | `deny` or `continue` (overselling) |
| `track_inventory` | boolean | Enable inventory tracking |

### Physical Properties

| Property | Type | Description |
|----------|------|-------------|
| `weight` | decimal(10,2) | Weight value |
| `weight_unit` | string | `kg`, `lb`, `g`, `oz` |
| `length` | decimal(10,2) | Length value |
| `width` | decimal(10,2) | Width value |
| `height` | decimal(10,2) | Height value |
| `dimension_unit` | string | `cm`, `in`, `m` |

### Shipping & Tax

| Property | Type | Description |
|----------|------|-------------|
| `requires_shipping` | boolean | Needs shipping |
| `taxable` | boolean | Subject to tax |

### Relationships

| Relation | Type | Description |
|----------|------|-------------|
| `product` | belongsTo | Parent product |
| `prices` | hasMany | VariantPrice (multi-currency, tier) |
| `inventoryItems` | hasMany | InventoryItem (multi-location) |
| `inventoryLevels` | hasMany | InventoryLevel (stock per location) |
| `cartItems` | hasMany | CartItem |
| `orderItems` | hasMany | OrderItem |
| `media` | morphMany | Variant-specific images |

---

## Eloquent Model

### Basic Usage

```php
use Shopper\Models\ProductVariant;

// Create variant
$variant = ProductVariant::create([
    'product_id' => 1,
    'title' => 'Cotton T-Shirt - Red / M',
    'sku' => 'TS-RED-M',
    'option1' => 'Red',
    'option2' => 'M',
    'price' => 19.99,
    'inventory_quantity' => 50,
]);

// Find by ID
$variant = ProductVariant::find(1);

// Find by SKU
$variant = ProductVariant::where('sku', 'TS-RED-M')->first();

// Update price
$variant->update(['price' => 24.99]);

// Update inventory
$variant->increment('inventory_quantity', 10);
$variant->decrement('inventory_quantity', 5);

// Soft delete
$variant->delete();
```

---

## Relationships

### Product

```php
// Get parent product
$product = $variant->product;

// Get variant's product options
$options = $variant->product->options;

// Check if default variant
$isDefault = $variant->id === $variant->product->defaultVariant()->id;
```

### Prices (Multi-Currency & Tier Pricing)

```php
use Shopper\Models\VariantPrice;

// Create price for specific site/channel/currency
$variant->prices()->create([
    'site_id' => 1,
    'channel_id' => 1,
    'currency' => 'EUR',
    'price' => 19.99,
    'compare_at_price' => 29.99,
]);

// Create tier pricing (B2B)
$variant->prices()->create([
    'site_id' => 1,
    'channel_id' => 4, // B2B channel
    'currency' => 'EUR',
    'price' => 15.99,
    'min_quantity' => 10,
    'max_quantity' => 49,
]);

$variant->prices()->create([
    'site_id' => 1,
    'channel_id' => 4,
    'currency' => 'EUR',
    'price' => 12.99,
    'min_quantity' => 50,
]);

// Get price for context
$price = $variant->getPriceFor(
    siteId: currentSite()->id,
    channelId: currentChannel()->id,
    currency: session('currency'),
    quantity: 1,
);
```

### Inventory (Multi-Location)

```php
// Get inventory item
$inventoryItem = $variant->inventoryItem;

// Get inventory levels (per location)
$levels = $variant->inventoryLevels;

// Stock at specific location
$warehouse = Location::find(1);
$stock = $variant->inventoryLevelAt($warehouse);

// Total inventory across all locations
$totalStock = $variant->totalInventory();

// Reserve inventory
$variant->reserveInventory(5, $orderId);

// Release inventory
$variant->releaseInventory(5, $orderId);
```

### Cart & Order Items

```php
// Find cart items
$cartItems = $variant->cartItems;

// Find order items
$orderItems = $variant->orderItems;

// Check if variant is in any active carts
$inCarts = $variant->cartItems()->whereHas('cart', function ($q) {
    $q->where('status', 'active');
})->exists();

// Revenue from this variant
$revenue = $variant->orderItems()
    ->whereHas('order', fn($q) => $q->where('status', 'completed'))
    ->sum('total');
```

### Media (Images)

```php
// Add variant-specific image
$variant->addMedia($imagePath)->toMediaCollection('images');

// Get variant image
$image = $variant->getFirstMediaUrl('images');

// Fallback to product image if variant has none
$image = $variant->getFirstMediaUrl('images')
    ?? $variant->product->getFirstMediaUrl('images');
```

---

## Scopes

### Query Scopes

```php
// In stock
ProductVariant::inStock()->get();

// Out of stock
ProductVariant::outOfStock()->get();

// Low stock (customizable threshold)
ProductVariant::lowStock(10)->get();

// By product
ProductVariant::forProduct($productId)->get();

// By SKU pattern
ProductVariant::whereSku('TS-%')->get();

// Trackable inventory
ProductVariant::trackInventory()->get();

// Requires shipping
ProductVariant::requiresShipping()->get();
```

### Scope Definitions

```php
public function scopeInStock($query)
{
    return $query->where('inventory_quantity', '>', 0);
}

public function scopeOutOfStock($query)
{
    return $query->where('inventory_quantity', '<=', 0);
}

public function scopeLowStock($query, int $threshold = 10)
{
    return $query->where('inventory_quantity', '>', 0)
        ->where('inventory_quantity', '<=', $threshold);
}

public function scopeForProduct($query, int $productId)
{
    return $query->where('product_id', $productId);
}

public function scopeTrackInventory($query)
{
    return $query->where('track_inventory', true);
}

public function scopeRequiresShipping($query)
{
    return $query->where('requires_shipping', true);
}
```

---

## Accessors & Mutators

### Accessors

```php
// Get display title with options
public function getDisplayTitleAttribute(): string
{
    $parts = array_filter([
        $this->product->title,
        $this->option1,
        $this->option2,
        $this->option3,
    ]);

    return implode(' - ', $parts);
}

// Check if variant is on sale
public function getOnSaleAttribute(): bool
{
    return $this->compare_at_price && $this->price < $this->compare_at_price;
}

// Get discount percentage
public function getDiscountPercentAttribute(): ?float
{
    if (!$this->on_sale) {
        return null;
    }

    return round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100, 2);
}

// Get formatted price
public function getFormattedPriceAttribute(): string
{
    return money($this->price, session('currency'));
}

// Check if in stock
public function getInStockAttribute(): bool
{
    return $this->inventory_quantity > 0 || $this->inventory_policy === 'continue';
}

// Check if available
public function getAvailableAttribute(): bool
{
    return $this->in_stock && !$this->trashed();
}

// Get total volume (for shipping)
public function getVolumeAttribute(): ?float
{
    if (!$this->length || !$this->width || !$this->height) {
        return null;
    }

    return $this->length * $this->width * $this->height;
}

// Usage
echo $variant->display_title;
echo $variant->formatted_price;
if ($variant->on_sale) {
    echo "Save {$variant->discount_percent}%";
}
```

### Mutators

```php
// Normalize SKU to uppercase
public function setSkuAttribute($value)
{
    $this->attributes['sku'] = strtoupper($value);
}

// Auto-generate title from options
public function setOption1Attribute($value)
{
    $this->attributes['option1'] = $value;
    $this->updateTitle();
}

protected function updateTitle(): void
{
    if (!$this->product_id) return;

    $parts = array_filter([
        $this->product->title ?? '',
        $this->option1,
        $this->option2,
        $this->option3,
    ]);

    $this->attributes['title'] = implode(' - ', $parts);
}
```

---

## Methods

### Pricing

```php
// Get price for specific context
public function getPriceFor(
    ?int $siteId = null,
    ?int $channelId = null,
    ?string $currency = null,
    int $quantity = 1,
    ?int $customerGroupId = null,
): ?VariantPrice {
    return app(PricingService::class)->resolvePrice(
        variantId: $this->id,
        siteId: $siteId,
        channelId: $channelId,
        currency: $currency,
        quantity: $quantity,
        customerGroupId: $customerGroupId,
    );
}

// Get effective price (considering discounts)
public function getEffectivePrice(?string $currency = null): float
{
    $price = $this->getPriceFor(currency: $currency);
    return $price?->price ?? $this->price;
}

// Check if price is available
public function hasPriceFor(?string $currency = null): bool
{
    return $this->getPriceFor(currency: $currency) !== null;
}
```

### Inventory Management

```php
// Check availability
public function isAvailable(int $quantity = 1): bool
{
    if (!$this->track_inventory) {
        return true;
    }

    if ($this->inventory_policy === 'continue') {
        return true;
    }

    return $this->inventory_quantity >= $quantity;
}

// Reserve stock
public function reserveInventory(int $quantity, ?int $orderId = null): void
{
    if (!$this->track_inventory) {
        return;
    }

    if ($this->inventory_quantity < $quantity && $this->inventory_policy === 'deny') {
        throw new InsufficientInventoryException("Not enough stock for SKU: {$this->sku}");
    }

    $this->decrement('inventory_quantity', $quantity);

    // Create reservation record
    $this->inventoryReservations()->create([
        'quantity' => $quantity,
        'order_id' => $orderId,
        'expires_at' => now()->addHours(2),
    ]);
}

// Release reserved stock
public function releaseInventory(int $quantity, ?int $orderId = null): void
{
    if (!$this->track_inventory) {
        return;
    }

    $this->increment('inventory_quantity', $quantity);

    // Remove reservation
    $this->inventoryReservations()
        ->where('order_id', $orderId)
        ->delete();
}

// Adjust inventory
public function adjustInventory(int $quantity, string $reason = null): void
{
    $old = $this->inventory_quantity;
    $new = $old + $quantity;

    $this->update(['inventory_quantity' => $new]);

    // Log movement
    $this->inventoryMovements()->create([
        'from_quantity' => $old,
        'to_quantity' => $new,
        'quantity' => $quantity,
        'reason' => $reason,
        'user_id' => auth()->id(),
    ]);
}
```

### Physical Properties

```php
// Get weight in specific unit
public function getWeightIn(string $unit): ?float
{
    if (!$this->weight) {
        return null;
    }

    return match ([$this->weight_unit, $unit]) {
        ['kg', 'lb'] => $this->weight * 2.20462,
        ['lb', 'kg'] => $this->weight * 0.453592,
        ['kg', 'g'] => $this->weight * 1000,
        ['g', 'kg'] => $this->weight / 1000,
        default => $this->weight,
    };
}

// Get dimensions in specific unit
public function getDimensionsIn(string $unit): array
{
    $convert = fn($value) => match ([$this->dimension_unit, $unit]) {
        ['cm', 'in'] => $value * 0.393701,
        ['in', 'cm'] => $value * 2.54,
        ['cm', 'm'] => $value / 100,
        ['m', 'cm'] => $value * 100,
        default => $value,
    };

    return [
        'length' => $convert($this->length),
        'width' => $convert($this->width),
        'height' => $convert($this->height),
        'unit' => $unit,
    ];
}

// Calculate volumetric weight
public function getVolumetricWeight(int $divisor = 5000): ?float
{
    if (!$this->volume) {
        return null;
    }

    // Convert to cm³ if needed
    $volumeCm3 = $this->dimension_unit === 'cm'
        ? $this->volume
        : $this->volume * 16387.064; // in³ to cm³

    return $volumeCm3 / $divisor;
}
```

### Option Helpers

```php
// Get all options as array
public function getOptions(): array
{
    return array_filter([
        $this->option1,
        $this->option2,
        $this->option3,
    ]);
}

// Match options
public function matchesOptions(array $options): bool
{
    return $this->option1 === ($options[0] ?? null)
        && $this->option2 === ($options[1] ?? null)
        && $this->option3 === ($options[2] ?? null);
}

// Get option by position
public function getOption(int $position): ?string
{
    return match ($position) {
        1 => $this->option1,
        2 => $this->option2,
        3 => $this->option3,
        default => null,
    };
}
```

---

## Events

### Model Events

```php
use Shopper\Events\VariantCreated;
use Shopper\Events\VariantUpdated;
use Shopper\Events\VariantInventoryChanged;

Event::listen(VariantCreated::class, function ($event) {
    $variant = $event->variant;
    // Create inventory item
    // Set up default prices
});

Event::listen(VariantInventoryChanged::class, function ($event) {
    $variant = $event->variant;
    $oldQuantity = $event->oldQuantity;
    $newQuantity = $event->newQuantity;

    // Send low stock alerts
    // Update analytics
});
```

### Eloquent Events

```php
protected static function booted()
{
    static::creating(function ($variant) {
        // Auto-generate SKU if empty
        if (empty($variant->sku)) {
            $variant->sku = Str::upper(Str::random(8));
        }

        // Set position
        if ($variant->position === 0) {
            $variant->position = ProductVariant::where('product_id', $variant->product_id)->count() + 1;
        }
    });

    static::created(function ($variant) {
        // Create inventory item
        $variant->createInventoryItem();
    });

    static::updating(function ($variant) {
        // Track inventory changes
        if ($variant->isDirty('inventory_quantity')) {
            event(new VariantInventoryChanged(
                $variant,
                $variant->getOriginal('inventory_quantity'),
                $variant->inventory_quantity
            ));
        }
    });

    static::deleting(function ($variant) {
        // Delete prices
        $variant->prices()->delete();

        // Delete inventory items
        $variant->inventoryItems()->delete();

        // Delete media
        $variant->clearMediaCollection('images');
    });
}
```

---

## API Endpoints

### REST API

```http
# List variants for product
GET /api/products/{productId}/variants

# Get single variant
GET /api/variants/{id}

# Create variant
POST /api/products/{productId}/variants

# Update variant
PUT /api/variants/{id}

# Delete variant
DELETE /api/variants/{id}

# Adjust inventory
POST /api/variants/{id}/inventory/adjust

# Get inventory levels
GET /api/variants/{id}/inventory/levels
```

### Request/Response Examples

```http
# Create variant
POST /api/products/1/variants
Content-Type: application/json

{
  "title": "Cotton T-Shirt - Blue / L",
  "sku": "TS-BLUE-L",
  "option1": "Blue",
  "option2": "L",
  "price": 21.99,
  "compare_at_price": 29.99,
  "inventory_quantity": 100,
  "weight": 0.3,
  "weight_unit": "kg"
}

# Response
{
  "data": {
    "id": 42,
    "product_id": 1,
    "title": "Cotton T-Shirt - Blue / L",
    "sku": "TS-BLUE-L",
    "option1": "Blue",
    "option2": "L",
    "price": "21.99",
    "compare_at_price": "29.99",
    "inventory_quantity": 100,
    "in_stock": true,
    "on_sale": true,
    "discount_percent": 24.18
  }
}
```

### GraphQL API

```graphql
# Query variant
query {
  variant(id: 42) {
    id
    sku
    title
    price
    compare_at_price
    on_sale
    discount_percent
    inventory_quantity
    in_stock
    available
    weight
    dimensions {
      length
      width
      height
      unit
    }
    product {
      id
      title
    }
    prices {
      currency
      price
      site { name }
      channel { name }
    }
  }
}

# Mutation - Update inventory
mutation {
  adjustVariantInventory(
    id: 42
    quantity: -5
    reason: "Sold via POS"
  ) {
    variant {
      id
      inventory_quantity
    }
    movement {
      id
      quantity
      reason
    }
  }
}
```

---

## Examples

### Creating Variants

```php
// Simple variant (no options)
$variant = ProductVariant::create([
    'product_id' => $product->id,
    'title' => $product->title,
    'sku' => 'MUG-001',
    'price' => 12.99,
    'inventory_quantity' => 100,
]);

// Variant with options
$variant = ProductVariant::create([
    'product_id' => $product->id,
    'title' => 'Cotton T-Shirt - Red / M',
    'sku' => 'TS-RED-M',
    'option1' => 'Red',
    'option2' => 'M',
    'price' => 19.99,
    'compare_at_price' => 29.99,
    'weight' => 0.25,
    'weight_unit' => 'kg',
    'inventory_quantity' => 50,
]);

// Bulk create variants
$variants = [
    ['sku' => 'TS-RED-S', 'option1' => 'Red', 'option2' => 'S', 'price' => 18.99],
    ['sku' => 'TS-RED-M', 'option1' => 'Red', 'option2' => 'M', 'price' => 19.99],
    ['sku' => 'TS-RED-L', 'option1' => 'Red', 'option2' => 'L', 'price' => 21.99],
];

foreach ($variants as $data) {
    $product->variants()->create(array_merge($data, [
        'title' => "{$product->title} - {$data['option1']} / {$data['option2']}",
        'inventory_quantity' => 50,
    ]));
}
```

### Inventory Management

```php
// Check availability
if ($variant->isAvailable(5)) {
    // Add to cart
    $cart->addItem($variant, 5);

    // Reserve inventory
    $variant->reserveInventory(5, $order->id);
}

// Low stock alert
if ($variant->inventory_quantity <= 10) {
    Notification::send($admin, new LowStockAlert($variant));
}

// Adjust inventory with reason
$variant->adjustInventory(
    quantity: -10,
    reason: 'Damaged goods - removed from stock'
);

// Transfer stock between locations
$fromWarehouse = Location::find(1);
$toStore = Location::find(2);

$variant->transferInventory(
    from: $fromWarehouse,
    to: $toStore,
    quantity: 20
);
```

### Pricing Examples

```php
// Set base price
$variant->update(['price' => 19.99]);

// Set sale price
$variant->update([
    'compare_at_price' => 29.99,
    'price' => 19.99, // 33% off
]);

// Multi-currency pricing
$variant->prices()->create([
    'site_id' => 1,
    'currency' => 'EUR',
    'price' => 19.99,
]);

$variant->prices()->create([
    'site_id' => 1,
    'currency' => 'USD',
    'price' => 22.99,
]);

// B2B tier pricing
$b2bChannel = Channel::where('type', 'b2b_portal')->first();

$variant->prices()->create([
    'channel_id' => $b2bChannel->id,
    'currency' => 'EUR',
    'price' => 15.99,
    'min_quantity' => 10,
    'max_quantity' => 49,
]);

$variant->prices()->create([
    'channel_id' => $b2bChannel->id,
    'currency' => 'EUR',
    'price' => 12.99,
    'min_quantity' => 50,
]);

// Get price for customer
$price = $variant->getPriceFor(
    siteId: currentSite()->id,
    channelId: currentChannel()->id,
    currency: 'EUR',
    quantity: 25,
    customerGroupId: $customer->group_id,
);

echo money($price->price, 'EUR'); // €15.99 (tier pricing applied)
```

---

## Performance Tips

### Eager Loading

```php
// Load relations to avoid N+1
$variants = ProductVariant::with([
    'product.brand',
    'prices',
    'inventoryItem.levels.location',
    'media',
])->get();
```

### Caching

```php
// Cache variant data
$variant = Cache::remember("variant.{$sku}", 3600, function () use ($sku) {
    return ProductVariant::with(['product', 'prices'])
        ->where('sku', $sku)
        ->first();
});
```

### Indexing

```php
// Ensure proper indexes
$table->index('sku');
$table->index('product_id');
$table->index(['product_id', 'option1', 'option2', 'option3']);
$table->index('inventory_quantity');
```

---

## Related Documentation

- [Product Model](/docs/model-product)
- [VariantPrice Model](/docs/model-variant-price)
- [InventoryItem Model](/docs/model-inventory-item)
- [Pricing System](/docs/pricing-system)
- [Inventory Management](/docs/inventory-management)
- [REST API - Variants](/docs/api-variants)
- [GraphQL API - Variants](/docs/graphql-variants)
