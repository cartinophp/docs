---
id: 7c892f15-8f3e-4d21-a5f9-3cb4a9d8e1f2
blueprint: v1
title: Product Architecture
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264233
---
# Product Architecture

Cartino uses a **Shopify-inspired** product architecture where every product must have at least one variant, even if it conceptually has no variations.

[TOC]

## Philosophy

The core principle: **Every product has at least one variant**. This ensures a uniform data structure and provides maximum flexibility for future changes.

### Why This Approach?

1. **Consistency**: All products use the same data structure
2. **Flexibility**: Easy to add variants later without restructuring
3. **Simplicity**: Single code path for all product operations
4. **Battle-tested**: Used successfully by Shopify for millions of merchants

---

## Database Schema

### Products Table (Container)

The `products` table contains **product-level information only**:

```php
products {
  id              bigint
  site_id         bigint FK → sites
  
  // Identity
  title           string
  slug            string (unique per site)
  handle          string
  description     text
  
  // Classification  
  product_type_id bigint FK → product_types
  brand_id        bigint FK → brands (nullable)
  
  // Product Options
  options         jsonb [
    {name: "Color", values: ["Red", "Blue"]},
    {name: "Size", values: ["S", "M", "L"]}
  ]
  
  // SEO & Meta
  meta_title      string
  meta_description text
  seo             jsonb
  
  // Status & Publishing
  status          enum (draft, active, archived)
  published_at    timestamp
  published_scope  string
  
  // Aggregated Data (computed from variants)
  default_variant_id  bigint FK → product_variants
  variants_count  integer (computed)
  price_min       decimal (computed)
  price_max       decimal (computed)
  
  // Custom Fields
  data            jsonb
  
  timestamps, soft_deletes
}
```

### Product Variants Table (Operational Data)

The `product_variants` table contains **all operational data**:

```php
product_variants {
  id              bigint
  product_id      bigint FK → products
  
  // Identity
  title           string (auto-generated or custom)
  sku             string (unique, nullable)
  barcode         string (nullable)
  
  // Pricing
  price           decimal (required)
  compare_at_price decimal (nullable, for strikethrough)
  cost            decimal (nullable, for margin calculations)
  
  // Inventory
  inventory_quantity     integer
  track_quantity        boolean
  inventory_management  string (cartino, external, none)
  inventory_policy      enum (deny, continue)
  
  // Physical Properties
  weight          decimal
  weight_unit     enum (kg, g, lb, oz)
  dimensions      jsonb {length, width, height, unit}
  
  // Shipping & Tax
  requires_shipping boolean
  taxable          boolean
  tax_code         string (nullable)
  
  // Variant Options (up to 3)
  option1         string (nullable, e.g., "Red")
  option2         string (nullable, e.g., "Small")
  option3         string (nullable)
  
  // Status & Ordering
  status          enum (draft, active, archived)
  available       boolean (computed from inventory)
  position        integer (display order)
  
  // Custom Fields
  data            jsonb
  
  timestamps, soft_deletes
}
```

---

## Product Types

### 1. Simple Product (No Variations)

**Example**: E-book, digital download, or service

```php
Product {
    title: "Laravel E-book Guide"
    options: null  // No options
    default_variant_id: 1
}

ProductVariant {
    product_id: 1
    title: "Default Title"
    option1: null, option2: null, option3: null
    price: 29.99
    requires_shipping: false
    track_quantity: false
}
```

**Key Points**:
- Still has one variant (the "default" variant)
- Options are null
- Simplifies all product queries

### 2. Multi-Variant Product

**Example**: T-shirt with colors and sizes

```php
Product {
    title: "Cotton T-Shirt"
    options: [
        {name: "Color", values: ["Red", "Blue", "Black"]},
        {name: "Size", values: ["Small", "Medium", "Large"]}
    ]
    default_variant_id: 5  // First variant created
}

// Automatically generates 9 variants (3 colors × 3 sizes):

ProductVariant {
    product_id: 2
    title: "Red / Small"
    sku: "TSHIRT-RED-SM"
    option1: "Red", option2: "Small", option3: null
    price: 19.99
    position: 1
}

ProductVariant {
    product_id: 2
    title: "Red / Medium"
    sku: "TSHIRT-RED-MD"
    option1: "Red", option2: "Medium", option3: null
    price: 21.99
    position: 2
}

// ... 7 more variants
```

### 3. Product with Inventory Tracking

**Example**: Wireless Headphones

```php
Product {
    title: "Wireless Headphones"
    options: null  // Single variant
    default_variant_id: 15
}

ProductVariant {
    product_id: 3
    title: "Default Title"
    sku: "WH-001"
    option1: null, option2: null, option3: null
    
    // Pricing
    price: 149.99
    compare_at_price: 199.99  // Shows as "Was $199.99"
    cost: 75.00  // For margin tracking
    
    // Inventory
    inventory_quantity: 25
    track_quantity: true
    inventory_management: "cartino"
    inventory_policy: "deny"  // Don't allow overselling
}
```

---

## Working with Products

### Creating Products

```php
use App\Models\Product;
use App\Models\ProductVariant;

// Create a simple product (auto-creates default variant)
$product = Product::create([
    'site_id' => 1,
    'title' => 'E-book: Laravel Guide',
    'description' => 'Complete guide to Laravel',
    'product_type_id' => 1,
    'status' => 'active',
]);

// The variant is created automatically via observer
$variant = $product->variants()->first();
$variant->update([
    'price' => 29.99,
    'requires_shipping' => false,
]);
```

### Creating Multi-Variant Products

```php
// Create product with options
$product = Product::create([
    'site_id' => 1,
    'title' => 'Cotton T-Shirt',
    'product_type_id' => 2,
    'options' => [
        ['name' => 'Color', 'values' => ['Red', 'Blue', 'Black']],
        ['name' => 'Size', 'values' => ['Small', 'Medium', 'Large']],
    ],
    'status' => 'active',
]);

// Generate all variant combinations
$product->generateVariants();

// Or create variants manually
foreach (['Red', 'Blue', 'Black'] as $color) {
    foreach (['Small', 'Medium', 'Large'] as $size) {
        ProductVariant::create([
            'product_id' => $product->id,
            'title' => "{$color} / {$size}",
            'sku' => "TSHIRT-" . strtoupper(substr($color, 0, 3)) . "-" . substr($size, 0, 1),
            'option1' => $color,
            'option2' => $size,
            'price' => 19.99,
            'inventory_quantity' => 100,
            'track_quantity' => true,
        ]);
    }
}
```

### Querying Products

```php
// Get all active products with their variants
$products = Product::where('status', 'active')
    ->with(['variants' => function($query) {
        $query->where('status', 'active')
              ->orderBy('position');
    }])
    ->get();

// Get product with default variant
$product = Product::with('defaultVariant')->find(1);
$price = $product->defaultVariant->price;

// Find product by variant SKU
$variant = ProductVariant::where('sku', 'TSHIRT-RED-S')->first();
$product = $variant->product;

// Get products in price range
$products = Product::whereBetween('price_min', [10, 50])->get();

// Include soft-deleted variants (for order history)
$product = Product::with(['variants' => function($query) {
    $query->withTrashed();
}])->find(1);
```

---

## Variant Management

### Auto-Generated Variant Titles

Variant titles are auto-generated from options:

```php
// Single option
option1: "Red" → title: "Red"

// Two options  
option1: "Red", option2: "Small" → title: "Red / Small"

// Three options
option1: "Red", option2: "Small", option3: "Cotton" → title: "Red / Small / Cotton"

// No options (simple product)
option1: null → title: "Default Title"
```

### Variant Pricing Strategies

```php
// Same price for all variants
foreach ($product->variants as $variant) {
    $variant->update(['price' => 29.99]);
}

// Size-based pricing
$sizePrices = ['Small' => 19.99, 'Medium' => 21.99, 'Large' => 23.99];
foreach ($product->variants as $variant) {
    $variant->update(['price' => $sizePrices[$variant->option2]]);
}

// Volume-based pricing (handled separately in price rules)
```

### Inventory Updates

```php
// Track inventory for a variant
$variant->update([
    'track_quantity' => true,
    'inventory_management' => 'cartino',
    'inventory_policy' => 'deny',  // Don't allow overselling
]);

// Adjust stock
$variant->adjustInventory(50, 'Initial stock');
$variant->adjustInventory(-5, 'Sold via order #123');

// Reserve stock for order
$variant->reserveStock(2, $orderId);

// Release reserved stock
$variant->releaseStock(2, $orderId);
```

---

## Benefits of This Architecture

### ✅ Consistency
- Single code path for all product types
- Predictable data structure
- Easier to maintain and test

### ✅ Flexibility
- Add variants to simple products without migration
- Change option structure without breaking orders
- Support unlimited product variations

### ✅ Performance
- Optimized queries with indexed foreign keys
- Computed price_min/price_max for filtering
- JSONB for flexible custom fields

### ✅ Data Integrity
- Soft deletes preserve order history
- Variants maintain product relationships
- Inventory tracking with full audit trail

---

## Best Practices

### DO ✅

- Always create at least one variant per product
- Use `option1`, `option2`, `option3` for variant differences
- Track inventory at variant level, not product level
- Store custom data in `data` JSONB column
- Use soft deletes to maintain order history

### DON'T ❌

- Don't store operational data (price, inventory) on product table
- Don't skip creating variants for "simple" products
- Don't hard delete products with existing orders
- Don't mix product-level and variant-level data

---

## Advanced Topics

### Multi-Site Products

Products can exist in multiple sites with different pricing:

```php
// Same product, different sites
$productIT = Product::create([
    'site_id' => 1,  // Italy
    'title' => 'Maglietta Cotone',
    'status' => 'active',
]);

$productUS = Product::create([
    'site_id' => 2,  // USA
    'title' => 'Cotton T-Shirt',
    'status' => 'active',
]);
```

### Product with Media (Spatie)

```php
// Add images to product
$product->addMedia($request->file('image'))
    ->toMediaCollection('images');

// Add images to specific variant
$variant->addMedia($request->file('image'))
    ->toMediaCollection('variant-images');

// Get all product images
$images = $product->getMedia('images');
```

### Custom Fields via Blueprints

```php
// Set custom fields (defined in YAML blueprints)
$product->data = [
    'fabric_type' => 'Cotton',
    'care_instructions' => 'Machine wash cold',
    'sustainability_badge' => 'Organic',
];
$product->save();

// Access custom fields
$fabricType = $product->data['fabric_type'] ?? null;
```

---

## Next Steps

- [**Inventory Management**](/v1/inventory-management) - Track stock across locations
- [**Pricing Strategies**](/v1/pricing) - Multi-currency & customer group pricing
- [**Sites & Markets**](/v1/sites-architecture) - Multi-site setup
- [**Blueprint System**](/v1/blueprint-system) - Customize product fields
