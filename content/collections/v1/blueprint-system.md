---
id: 2f8a9c14-6d3e-4b21-9fa2-8ab5c7d9e2f3
blueprint: v1
title: Blueprint System
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264233
---
# Blueprint System

Cartino uses a **Statamic-inspired** file-based blueprint and fieldset system for managing custom fields across different content types.

[TOC]

## Overview

The blueprint system provides a flexible way to define custom fields for your content without database migrations.

### Key Components

- **Blueprints**: Define the complete structure of a content type (Products, Pages, Collections, etc.)
- **Fieldsets**: Reusable groups of fields that can be shared across multiple blueprints
- **Custom Fields**: Stored in the `data` JSONB column on each model
- **File-based**: No database changes needed to add custom fields

---

## Directory Structure

```
resources/
├─ blueprints/              # Blueprint definitions
│  ├─ products/
│  │  └─ product.yaml
│  ├─ pages/
│  │  └─ page.yaml
│  ├─ collections/
│  │  └─ collection.yaml
│  └─ customers/
│     └─ customer.yaml
└─ fieldsets/               # Reusable field groups
   ├─ seo.yaml
   ├─ pricing.yaml
   ├─ inventory.yaml
   └─ shipping.yaml
```

---

## Blueprints

Blueprints are YAML files that define the complete field structure for a content type. They are organized into **sections** (displayed as tabs in the admin UI).

### Example Blueprint

**File**: `resources/blueprints/products/product.yaml`

```yaml
title: Product
sections:
  main:
    display: Main
    fields:
      - handle: title
        field:
          type: text
          display: Title
          validate: required|max:255
          
      - handle: description
        field:
          type: textarea
          display: Description
          rows: 6
          
      - handle: status
        field:
          type: select
          display: Status
          options:
            draft: Draft
            active: Active
            archived: Archived
          default: draft
  
  media:
    display: Media
    fields:
      - handle: hero_image
        field:
          type: assets
          display: Hero Image
          max_files: 1
          container: products
          
      - handle: gallery
        field:
          type: assets
          display: Gallery
          max_files: 10
          container: products
  
  seo:
    display: SEO
    import: seo  # Import from fieldset
```

---

## Fieldsets

Fieldsets are reusable groups of fields that can be imported into multiple blueprints.

### Example Fieldset

**File**: `resources/fieldsets/seo.yaml`

```yaml
title: SEO
fields:
  - handle: meta_title
    field:
      type: text
      display: 'Meta Title'
      character_limit: 60
      instructions: 'Optimal length is 50-60 characters'
      
  - handle: meta_description
    field:
      type: textarea
      display: 'Meta Description'
      character_limit: 160
      rows: 3
      instructions: 'Optimal length is 150-160 characters'
      
  - handle: og_image
    field:
      type: assets
      display: 'Open Graph Image'
      max_files: 1
      instructions: 'Recommended size: 1200x630px'
      
  - handle: canonical_url
    field:
      type: text
      display: 'Canonical URL'
      validate: url
      
  - handle: robots
    field:
      type: select
      display: 'Robots'
      options:
        index_follow: 'Index, Follow'
        noindex_follow: 'No Index, Follow'
        index_nofollow: 'Index, No Follow'
        noindex_nofollow: 'No Index, No Follow'
      default: index_follow
```

### Importing Fieldsets

**Full Import**:
```yaml
sections:
  seo:
    display: SEO
    import: seo  # Imports all fields from seo.yaml
```

**Selective Import**:
```yaml
sections:
  pricing:
    display: Pricing
    fields:
      - import: pricing.price
      - import: pricing.compare_at_price
      - handle: custom_field
        field:
          type: text
```

---

## Field Types

Cartino supports a wide range of field types:

### Basic Fields

**Text**:
```yaml
- handle: title
  field:
    type: text
    display: Title
    validate: required|max:255
    character_limit: 255
```

**Textarea**:
```yaml
- handle: description
  field:
    type: textarea
    display: Description
    rows: 6
    character_limit: 1000
```

**Markdown**:
```yaml
- handle: content
  field:
    type: markdown
    display: Content
    toolbarButtons:
      - bold
      - italic
      - heading
      - link
```

**Code**:
```yaml
- handle: custom_css
  field:
    type: code
    display: Custom CSS
    mode: css
    theme: monokai
```

### Number Fields

**Integer**:
```yaml
- handle: quantity
  field:
    type: integer
    display: Quantity
    min: 0
    max: 9999
    step: 1
```

**Float**:
```yaml
- handle: weight
  field:
    type: float
    display: Weight (kg)
    min: 0
    step: 0.01
```

**Money**:
```yaml
- handle: price
  field:
    type: money
    display: Price
    currency: EUR
    min: 0
```

### Selection Fields

**Select**:
```yaml
- handle: status
  field:
    type: select
    display: Status
    options:
      draft: Draft
      active: Active
      archived: Archived
    default: draft
```

**Toggle** (Boolean):
```yaml
- handle: is_featured
  field:
    type: toggle
    display: Featured
    default: false
```

**Checkboxes**:
```yaml
- handle: features
  field:
    type: checkboxes
    display: Features
    options:
      waterproof: Waterproof
      wireless: Wireless
      rechargeable: Rechargeable
```

**Radio**:
```yaml
- handle: size_unit
  field:
    type: radio
    display: Size Unit
    options:
      cm: Centimeters
      in: Inches
    default: cm
```

### Date & Time

**Date**:
```yaml
- handle: published_at
  field:
    type: date
    display: Publish Date
    mode: single
    validate: after:today
```

**Time**:
```yaml
- handle: event_time
  field:
    type: time
    display: Event Time
    seconds_enabled: false
```

### Relationship Fields

**Relationship** (Link to other models):
```yaml
- handle: related_products
  field:
    type: relationship
    display: Related Products
    resource: products
    max_items: 5
```

**Taxonomy** (Tags/Categories):
```yaml
- handle: tags
  field:
    type: taxonomy
    display: Tags
    taxonomy: product_tags
    max_items: 10
```

### Asset Fields

**Assets** (Files/Images):
```yaml
- handle: images
  field:
    type: assets
    display: Images
    max_files: 10
    allowed_extensions:
      - jpg
      - png
      - webp
    container: products
```

### Advanced Fields

**Replicator** (Repeating blocks):
```yaml
- handle: content_blocks
  field:
    type: replicator
    display: Content Blocks
    sets:
      text_block:
        display: Text Block
        fields:
          - handle: heading
            field:
              type: text
              display: Heading
          - handle: content
            field:
              type: markdown
              display: Content
      
      image_block:
        display: Image Block
        fields:
          - handle: image
            field:
              type: assets
              max_files: 1
          - handle: caption
            field:
              type: text
```

**Grid** (Table-like data):
```yaml
- handle: specifications
  field:
    type: grid
    display: Specifications
    fields:
      - handle: key
        field:
          type: text
          display: Key
      - handle: value
        field:
          type: text
          display: Value
```

**Group** (Nested fields):
```yaml
- handle: dimensions
  field:
    type: group
    display: Dimensions
    fields:
      - handle: length
        field:
          type: float
      - handle: width
        field:
          type: float
      - handle: height
        field:
          type: float
```

---

## Field Configuration

### Common Options

```yaml
handle: field_name        # Unique identifier
field:
  type: text              # Field type
  display: Field Label    # Label shown in UI
  instructions: Help text # Instructions for users
  validate: required      # Laravel validation rules
  default: value          # Default value
  width: 50               # Width percentage (50 = half)
  character_limit: 60     # Character limit
  read_only: false        # Make field read-only
  if:                     # Conditional display
    status: equals active
```

### Conditional Logic

Show/hide fields based on other field values:

```yaml
- handle: scheduled_at
  field:
    type: date
    display: 'Scheduled Date'
    if:
      status: equals scheduled
```

```yaml
- handle: tracking_number
  field:
    type: text
    display: 'Tracking Number'
    if:
      requires_shipping: equals true
```

### Validation Rules

Use Laravel validation rules:

```yaml
# Required field
validate: required

# Email validation
validate: required|email|max:255

# Numeric range
validate: required|numeric|min:0|max:100

# URL validation
validate: required|url

# Date validation
validate: required|date|after:today

# Multiple rules
validate: required|string|min:3|max:50|regex:/^[a-z0-9-]+$/
```

---

## Data Storage

Custom field values are stored in the `data` JSONB column on each model.

### Database Schema

The `data` column is available on:
- `products`
- `product_variants`
- `pages`
- `collections`
- `customers`
- `orders`
- `categories`
- `brands`
- And more...

### Working with Custom Fields

**Reading Custom Fields**:
```php
// Access via data attribute
$metaTitle = $product->data['meta_title'] ?? null;
$customBadge = $product->data['custom_badge'] ?? null;

// Using array access
$price = $product->data['custom_price'] ?? $product->price;

// Accessing nested data
$length = $product->data['dimensions']['length'] ?? null;
```

**Writing Custom Fields**:
```php
// Set custom fields
$product->data = [
    'meta_title' => 'Custom SEO Title',
    'custom_badge' => 'New Arrival',
    'fabric_type' => 'Cotton',
    'sustainability_rating' => 'A+'
];
$product->save();

// Merge with existing data
$product->data = array_merge($product->data ?? [], [
    'new_field' => 'value'
]);
$product->save();

// Update specific field
$data = $product->data ?? [];
$data['meta_title'] = 'Updated Title';
$product->data = $data;
$product->save();
```

**Querying JSONB Fields**:

PostgreSQL:
```php
// Contains check
Product::whereJsonContains('data->tags', 'featured')->get();

// Exact match
Product::where('data->meta_title', 'My Title')->get();

// Nested path
Product::where('data->dimensions->length', '>', 100)->get();
```

MySQL:
```php
// JSON_CONTAINS
Product::whereRaw("JSON_CONTAINS(data, '\"featured\"', '$.tags')")->get();

// JSON_EXTRACT
Product::whereRaw("JSON_EXTRACT(data, '$.meta_title') = ?", ['My Title'])->get();
```

---

## Creating Custom Blueprints

### Step 1: Create Blueprint File

Create a new YAML file:
```bash
touch resources/blueprints/products/custom_product.yaml
```

### Step 2: Define Structure

```yaml
title: Custom Product
sections:
  details:
    display: Product Details
    fields:
      - handle: custom_field
        field:
          type: text
          display: Custom Field
          
  advanced:
    display: Advanced
    import: seo
```

### Step 3: Use in Controller

```php
use App\Services\BlueprintManager;

$blueprint = app(BlueprintManager::class)
    ->get('products/custom_product');

return view('admin.products.create', [
    'blueprint' => $blueprint
]);
```

---

## Best Practices

### DO ✅

- **Keep blueprints focused** - One blueprint per content type
- **Reuse fieldsets** - Create fieldsets for common fields (SEO, pricing, etc.)
- **Use clear handles** - Field handles should be descriptive (`meta_title` not `mt`)
- **Add instructions** - Help users understand each field
- **Validate inputs** - Always add validation rules
- **Index JSON fields** - Add database indexes on frequently queried paths

### DON'T ❌

- Don't store large binary data in JSONB
- Don't use JSONB for fields requiring complex queries
- Don't nest JSONB data too deeply (max 3-4 levels)
- Don't forget to validate custom field input
- Don't use special characters in handles (use snake_case)

---

## Performance Optimization

### Database Indexes

Add GIN indexes for JSONB queries (PostgreSQL):

```sql
-- Index entire JSONB column
CREATE INDEX idx_products_data ON products USING GIN (data);

-- Index specific path
CREATE INDEX idx_products_meta_title ON products ((data->>'meta_title'));

-- Partial index
CREATE INDEX idx_featured_products 
ON products ((data->>'is_featured')) 
WHERE (data->>'is_featured')::boolean = true;
```

### Caching Blueprints

```php
// Cache blueprint definitions
$blueprint = Cache::remember(
    "blueprint.products.product",
    3600,
    fn() => BlueprintManager::get('products/product')
);
```

---

## Advanced Features

### Revisions & Versioning

Track changes to custom fields:

```php
// Create revision
$product->createRevision('Updated custom fields', auth()->user());

// Restore from revision
$product->restoreFromRevision($revisionId);

// View revision history
$revisions = $product->revisions()
    ->orderByDesc('created_at')
    ->get();
```

### Taxonomies

Flexible tagging system:

```php
// Create taxonomy
$taxonomy = Taxonomy::create([
    'handle' => 'product_tags',
    'title' => 'Product Tags'
]);

// Create term
$term = $taxonomy->terms()->create([
    'slug' => 'new-arrival',
    'title' => 'New Arrival'
]);

// Attach to product
$product->attachTerm($term);

// Query products with term
Product::withTerm('new-arrival')->get();
```

---

## Next Steps

- [**Product Architecture**](/v1/product-architecture) - Understand product/variant structure
- [**Development**](/v1/development) - Build custom fields and blueprints
- [**API Reference**](/v1/api) - Programmatic blueprint access
