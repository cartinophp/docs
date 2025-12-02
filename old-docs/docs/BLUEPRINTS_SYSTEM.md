# Blueprints & Fieldsets System

This Cartino implementation uses a **Statamic-inspired** file-based blueprint and fieldset system for managing custom fields across different content types.

## Overview

- **Blueprints**: Define the complete structure of a content type (Products, Pages, Collections, etc.)
- **Fieldsets**: Reusable groups of fields that can be shared across multiple blueprints
- **Custom Fields**: Stored in the `data` JSONB column on each model

## Directory Structure

```
resources/
├── blueprints/           # Blueprint definitions
│   ├── products/
│   │   └── product.yaml
│   ├── pages/
│   │   └── page.yaml
│   └── collections/
│       └── collection.yaml
└── fieldsets/           # Reusable field groups
    ├── seo.yaml
    ├── pricing.yaml
    ├── inventory.yaml
    └── shipping.yaml
```

## How It Works

### 1. Blueprints (Content Structure)

Blueprints are YAML files that define the complete field structure for a content type. They are organized into **sections** (tabs in the UI).

**Example: `resources/blueprints/products/product.yaml`**

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
          validate: required
```

### 2. Fieldsets (Reusable Fields)

Fieldsets are YAML files containing groups of fields that can be imported into multiple blueprints.

**Example: `resources/fieldsets/seo.yaml`**

```yaml
title: SEO
fields:
  - handle: meta_title
    field:
      type: text
      display: 'Meta Title'
      character_limit: 60
```

### 3. Data Storage

Custom field values are stored in the `data` JSONB column:

```php
// Save custom data
$product->data = [
    'meta_title' => 'My Product Title',
    'og_image' => '/images/product.jpg',
    'custom_badge' => 'New Arrival'
];
$product->save();

// Retrieve custom data
$metaTitle = $product->data['meta_title'] ?? null;
```

## Field Types

### Basic Fields
- `text` - Single line text input
- `textarea` - Multi-line text input
- `markdown` - Markdown editor
- `code` - Code editor with syntax highlighting
- `integer` - Numeric integer input
- `float` - Decimal number input
- `money` - Currency input

### Selection Fields
- `select` - Dropdown selection
- `toggle` - Boolean on/off switch
- `checkboxes` - Multiple checkboxes
- `radio` - Radio buttons

### Relationship Fields
- `relationship` - Link to other resources
- `taxonomy` - Tag/category selection
- `users` - User selection

### Asset Fields
- `assets` - File/image upload
- `media` - Media library selection

### Advanced Fields
- `replicator` - Repeating content blocks (page builder)
- `grid` - Table-like data entry
- `bard` - Rich text editor
- `group` - Nested field group
- `seo` - SEO meta fields

## Blueprint Sections

Sections organize fields into tabs in the admin UI:

```yaml
sections:
  main:
    display: Main
    fields: [...]
  
  media:
    display: Media
    fields: [...]
  
  seo:
    display: SEO
    fields: [...]
```

## Field Configuration

Each field supports various configuration options:

```yaml
handle: title              # Unique field identifier
field:
  type: text              # Field type
  display: Title          # Label shown in UI
  instructions: 'Help text for users'
  validate: required|max:255
  default: 'Default value'
  width: 50               # Percentage width (50 = half)
  character_limit: 60
  read_only: false
  if:                     # Conditional logic
    status: equals published
```

## Conditional Logic

Show/hide fields based on other field values:

```yaml
- handle: scheduled_at
  field:
    type: date
    display: 'Scheduled Date'
    if:
      status: equals scheduled
```

## Validation Rules

Use Laravel validation rules:

```yaml
validate: required|email|max:255
validate: required|numeric|min:0|max:100
validate: required|url
validate: required|date|after:today
```

## Importing Fieldsets

Reference fieldsets in blueprints using imports:

```yaml
sections:
  seo:
    display: SEO
    import: seo
```

Or manually include specific fields:

```yaml
sections:
  pricing:
    display: Pricing
    fields:
      - import: pricing.price
      - import: pricing.compare_at_price
```

## Database Schema

The `data` JSONB column is added to all major tables:

- products
- product_variants
- pages
- collections
- customers
- orders
- categories
- brands
- and more...

## Usage in Code

### Reading Custom Fields

```php
// Access via data attribute
$product->data['meta_title'];
$product->data['custom_badge'];

// Using array access
$metaTitle = $product->data['meta_title'] ?? 'Default Title';
```

### Writing Custom Fields

```php
$product->data = array_merge($product->data ?? [], [
    'meta_title' => 'New Title',
    'custom_field' => 'Value'
]);
$product->save();
```

### Querying JSON Fields

```php
// PostgreSQL
Product::whereJsonContains('data->tags', 'featured')->get();

// MySQL
Product::whereRaw("JSON_CONTAINS(data, '\"featured\"', '$.tags')")->get();
```

## Creating New Blueprints

1. Create a new YAML file in `resources/blueprints/{type}/`
2. Define sections and fields
3. Reference in your controller or admin panel
4. Custom field data automatically saves to the `data` column

## Best Practices

1. **Keep blueprints focused** - One blueprint per content type
2. **Reuse fieldsets** - Create fieldsets for common field groups (SEO, pricing, etc.)
3. **Use clear handles** - Field handles should be descriptive and unique
4. **Add instructions** - Help users understand what each field does
5. **Validate inputs** - Always add validation rules for data integrity
6. **Index JSON fields** - Add GIN indexes on frequently queried JSON paths (PostgreSQL)

## Inspiration

This system draws inspiration from:
- **Statamic CMS** - File-based blueprints and fieldsets
- **Shopify** - Product variants and metafields
- **Shopware** - Custom entity fields
- **Medusa** - Flexible product attributes
- **Sylius** - Product attributes system

## Revisions & Versioning

Content changes are tracked in the `revisions` table:
- Full snapshots of model attributes
- Delta tracking (what changed)
- Publishing workflow support
- User attribution
- Restore capability

```php
// Create a revision
$product->createRevision('Updated pricing', $user);

// Restore from revision
$product->restoreFromRevision($revisionId);
```

## Taxonomies

Taxonomies provide a flexible tagging/categorization system:
- Create custom taxonomies (Tags, Categories, Collections, etc.)
- Attach terms to any model via polymorphic relationship
- Hierarchical terms support
- Per-site taxonomy configuration

```php
// Create taxonomy
$taxonomy = Taxonomy::create([
    'handle' => 'product_tags',
    'title' => 'Product Tags'
]);

// Create terms
$term = $taxonomy->terms()->create([
    'slug' => 'new-arrival',
    'title' => 'New Arrival'
]);

// Attach to product
$product->attachTerm($term);
```

## Advanced Inventory

Multi-location inventory management inspired by Shopify:
- Inventory items (SKU-level tracking)
- Inventory levels (stock per location)
- Stock movements (full audit trail)
- Stock reservations (hold stock for orders)
- Stock transfers (move between locations)
- Stock adjustments (corrections & counts)

This provides enterprise-level inventory control for complex operations.
