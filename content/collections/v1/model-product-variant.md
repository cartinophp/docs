---
id: 3c4d5e6f-7a8b-9c0d-1e2f-3a4b5c6d7e8f
blueprint: v1
title: 'Model: ProductVariant'
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264233
---
# 🏷️ ProductVariant Model

The `ProductVariant` model represents the actual sellable item in Cartino. While `Product` is the container, variants hold all operational data like pricing, inventory, and SKU.

[TOC]

## 📋 Overview

**Namespace**: `App\Models\ProductVariant`  
**Table**: `product_variants`  
**Traits**: `SoftDeletes`, `InteractsWithMedia`, `HasFactory`

Every product has at least one variant, even simple products without options.

---

## 🗄️ Schema

```php
product_variants {
  id                      bigint
  product_id              bigint FK → products
  
  // Identity
  title                   string
  sku                     string (unique, nullable)
  barcode                 string (nullable)
  
  // Pricing
  price                   decimal (required)
  compare_at_price        decimal (nullable)
  cost                    decimal (nullable)
  
  // Inventory
  inventory_quantity      integer (default: 0)
  track_quantity          boolean (default: false)
  inventory_management    string (default: 'cartino')
  inventory_policy        enum (deny, continue)
  
  // Physical Properties
  weight                  decimal (nullable)
  weight_unit             enum (kg, g, lb, oz)
  dimensions              jsonb (nullable)
  
  // Shipping & Tax
  requires_shipping       boolean (default: true)
  taxable                 boolean (default: true)
  tax_code                string (nullable)
  
  // Variant Options (max 3)
  option1                 string (nullable)
  option2                 string (nullable)
  option3                 string (nullable)
  
  // Status & Position
  status                  enum (draft, active, archived)
  available               boolean (computed)
  position                integer (default: 0)
  
  // Custom Fields
  data                    jsonb (nullable)
  
  timestamps, soft_deletes
}
```

---

## 🔧 Properties

### Fillable

```php
protected $fillable = [
    'product_id',
    'title',
    'sku',
    'barcode',
    'price',
    'compare_at_price',
    'cost',
    'inventory_quantity',
    'track_quantity',
    'inventory_management',
    'inventory_policy',
    'weight',
    'weight_unit',
    'dimensions',
    'requires_shipping',
    'taxable',
    'tax_code',
    'option1',
    'option2',
    'option3',
    'status',
    'position',
    'data',
];
```

### Casts

```php
protected $casts = [
    'price' => 'decimal:2',
    'compare_at_price' => 'decimal:2',
    'cost' => 'decimal:2',
    'weight' => 'decimal:2',
    'inventory_quantity' => 'integer',
    'track_quantity' => 'boolean',
    'requires_shipping' => 'boolean',
    'taxable' => 'boolean',
    'dimensions' => 'array',
    'data' => 'array',
    'status' => VariantStatus::class,
    'inventory_policy' => InventoryPolicy::class,
];
```

---

## 🔗 Relationships

### BelongsTo

```php
// Parent Product
public function product()
{
    return $this->belongsTo(Product::class);
}
```

### HasMany

```php
// Prices (multi-currency, multi-channel)
public function prices()
{
    return $this->hasMany(VariantPrice::class, 'product_variant_id');
}

// Cart Lines
public function cartLines()
{
    return $this->hasMany(CartLine::class, 'product_variant_id');
}

// Order Lines
public function orderLines()
{
    return $this->hasMany(OrderLine::class, 'product_variant_id');
}

// Stock Notifications
public function stockNotifications()
{
    return $this->hasMany(StockNotification::class, 'product_variant_id');
}
```

### MorphMany

```php
// Media (variant-specific images)
public function images()
{
    return $this->morphMany(Media::class, 'model')
                ->where('collection_name', 'variant-images');
}
```

---

## 🔍 Scopes

### Active

```php
public function scopeActive($query)
{
    return $query->where('status', VariantStatus::Active);
}
```

### Available

```php
public function scopeAvailable($query)
{
    return $query->where(function ($q) {
        $q->where('track_quantity', false)
          ->orWhere('inventory_quantity', '>', 0);
    })->where('status', VariantStatus::Active);
}
```

### InStock

```php
public function scopeInStock($query)
{
    return $query->where('inventory_quantity', '>', 0);
}
```

### LowStock

```php
public function scopeLowStock($query, $threshold = 10)
{
    return $query->where('track_quantity', true)
                 ->where('inventory_quantity', '>', 0)
                 ->where('inventory_quantity', '<=', $threshold);
}
```

### OutOfStock

```php
public function scopeOutOfStock($query)
{
    return $query->where('track_quantity', true)
                 ->where('inventory_quantity', '<=', 0);
}
```

---

## 🎯 Accessors & Mutators

### Available Accessor

```php
public function getAvailableAttribute(): bool
{
    if (!$this->track_quantity) {
        return true;
    }
    
    return $this->inventory_quantity > 0;
}
```

### Discount Price Accessor

```php
public function getDiscountPriceAttribute(): ?float
{
    if (!$this->compare_at_price) {
        return null;
    }
    
    return $this->compare_at_price - $this->price;
}
```

### Discount Percentage Accessor

```php
public function getDiscountPercentageAttribute(): ?float
{
    if (!$this->compare_at_price || $this->compare_at_price <= $this->price) {
        return null;
    }
    
    return round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100, 2);
}
```

### Profit Margin Accessor

```php
public function getProfitMarginAttribute(): ?float
{
    if (!$this->cost) {
        return null;
    }
    
    return round((($this->price - $this->cost) / $this->price) * 100, 2);
}
```

---

## ⚡ Methods

### Adjust Inventory

```php
public function adjustInventory(int $quantity, string $reason = null, $orderId = null): void
{
    if (!$this->track_quantity) {
        return;
    }
    
    $this->inventory_quantity += $quantity;
    $this->save();
    
    // Log stock movement
    $this->stockMovements()->create([
        'quantity' => $quantity,
        'reason' => $reason ?? 'Manual adjustment',
        'order_id' => $orderId,
        'balance_after' => $this->inventory_quantity,
    ]);
}
```

### Reserve Stock

```php
public function reserveStock(int $quantity, $orderId): bool
{
    if (!$this->track_quantity) {
        return true;
    }
    
    if ($this->inventory_policy === InventoryPolicy::Deny && $this->inventory_quantity < $quantity) {
        return false;
    }
    
    $this->stockReservations()->create([
        'order_id' => $orderId,
        'quantity' => $quantity,
        'expires_at' => now()->addHours(24),
    ]);
    
    return true;
}
```

### Release Stock

```php
public function releaseStock($orderId): void
{
    $reservations = $this->stockReservations()
        ->where('order_id', $orderId)
        ->whereNull('released_at')
        ->get();
    
    foreach ($reservations as $reservation) {
        $reservation->update(['released_at' => now()]);
    }
}
```

### Get Price

```php
public function getPrice(
    ?string $currency = null,
    ?int $siteId = null,
    ?int $channelId = null,
    ?int $customerGroupId = null
): ?VariantPrice
{
    $currency = $currency ?? session('currency');
    $siteId = $siteId ?? currentSite()->id;
    $channelId = $channelId ?? currentChannel()->id;
    
    return app(PricingService::class)->resolvePrice(
        $this->id,
        $siteId,
        $channelId,
        $currency,
        $customerGroupId
    );
}
```

### Check Availability

```php
public function checkAvailability(int $quantity = 1): bool
{
    if (!$this->track_quantity) {
        return true;
    }
    
    if ($this->inventory_policy === InventoryPolicy::Continue) {
        return true;
    }
    
    $availableQuantity = $this->inventory_quantity - $this->getReservedQuantity();
    
    return $availableQuantity >= $quantity;
}
```

### Get Reserved Quantity

```php
public function getReservedQuantity(): int
{
    return $this->stockReservations()
        ->whereNull('released_at')
        ->where('expires_at', '>', now())
        ->sum('quantity');
}
```

---

## 📸 Media Collections

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('variant-images')
         ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
         ->registerMediaConversions(function (Media $media) {
             $this->addMediaConversion('thumb')
                  ->width(150)
                  ->height(150)
                  ->sharpen(10);
         });
}
```

---

## 🎪 Events

```php
protected static function booted()
{
    static::creating(function ($variant) {
        // Auto-generate title from options
        if (!$variant->title) {
            $variant->title = collect([
                $variant->option1,
                $variant->option2,
                $variant->option3,
            ])->filter()->implode(' / ') ?: 'Default Title';
        }
        
        // Set position if not set
        if (!$variant->position) {
            $variant->position = $variant->product->variants()->count() + 1;
        }
    });
    
    static::created(function ($variant) {
        // Update product aggregates
        $variant->product->updatePriceRange();
        $variant->product->updateVariantsCount();
    });
    
    static::updated(function ($variant) {
        // Update product price range if price changed
        if ($variant->wasChanged('price')) {
            $variant->product->updatePriceRange();
        }
    });
    
    static::deleting(function ($variant) {
        // Prevent deletion if variant has orders
        if ($variant->orderLines()->exists()) {
            throw new \Exception('Cannot delete variant with existing orders');
        }
    });
}
```

---

## 💡 Usage Examples

### Creating a Variant

```php
$variant = ProductVariant::create([
    'product_id' => $product->id,
    'title' => 'Red / Small',
    'sku' => 'TSHIRT-RED-SM',
    'option1' => 'Red',
    'option2' => 'Small',
    'price' => 19.99,
    'compare_at_price' => 29.99,
    'cost' => 8.50,
    'inventory_quantity' => 100,
    'track_quantity' => true,
    'weight' => 0.2,
    'weight_unit' => 'kg',
]);
```

### Updating Inventory

```php
// Increase stock
$variant->adjustInventory(50, 'Restock from supplier');

// Decrease stock (e.g., sold)
$variant->adjustInventory(-2, 'Sold via order #123', $orderId);

// Check stock
if ($variant->checkAvailability(5)) {
    // Can sell 5 units
}
```

### Querying Variants

```php
// Active variants with stock
$variants = ProductVariant::active()
    ->inStock()
    ->with('product')
    ->get();

// Low stock alerts
$lowStock = ProductVariant::lowStock(10)
    ->with('product')
    ->get();

// Variants by option
$redVariants = ProductVariant::where('option1', 'Red')
    ->active()
    ->get();
```

### Pricing

```php
// Get variant price for current context
$price = $variant->getPrice();
echo $price->price;  // 19.99 EUR

// Get price for specific currency
$usdPrice = $variant->getPrice('USD');

// Check if on sale
if ($variant->compare_at_price > $variant->price) {
    echo "Save {$variant->discount_percentage}%";
}
```

---

## 🌐 API Resource

```php
class ProductVariantResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'title' => $this->title,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'discount_percentage' => $this->discount_percentage,
            'options' => [
                'option1' => $this->option1,
                'option2' => $this->option2,
                'option3' => $this->option3,
            ],
            'inventory' => [
                'quantity' => $this->inventory_quantity,
                'track_quantity' => $this->track_quantity,
                'available' => $this->available,
                'policy' => $this->inventory_policy->value,
            ],
            'physical' => [
                'weight' => $this->weight,
                'weight_unit' => $this->weight_unit,
                'dimensions' => $this->dimensions,
                'requires_shipping' => $this->requires_shipping,
            ],
            'images' => $this->getMedia('variant-images')->map(fn($m) => [
                'url' => $m->getUrl(),
                'thumb' => $m->getUrl('thumb'),
            ]),
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

---

## 🔗 Related Models

- [**Product**](/v1/models/product) - Parent product
- [**VariantPrice**](/v1/models/variant-price) - Multi-currency pricing
- [**OrderLine**](/v1/models/order-line) - Order items
- [**CartLine**](/v1/models/cart-line) - Cart items

---

## 📚 See Also

- [**Product Architecture**](/v1/product-architecture) - System overview
- [**Inventory Management**](/v1/inventory) - Stock tracking
- [**Pricing Strategies**](/v1/pricing) - Multi-currency pricing
