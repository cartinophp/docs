---
id: 1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d
blueprint: v1
title: Models Overview
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264233
---
# Models Overview

Cartino includes a comprehensive set of Eloquent models organized into logical categories. Each model is fully documented with its properties, relationships, and methods.

[TOC]

## Model Categories

### 🛍️ Products & Catalog
Core models for product management, variants, and catalog organization.

- [**Product**](/v1/models/product) - Product container with options
- [**ProductVariant**](/v1/models/product-variant) - Individual sellable variants
- [**ProductType**](/v1/models/product-type) - Product categorization
- [**ProductOption**](/v1/models/product-option) - Product options (Color, Size, etc.)
- [**Brand**](/v1/models/brand) - Product brands
- [**Collection**](/v1/models/collection) - Product collections
- [**Catalog**](/v1/models/catalog) - Product catalogs (Retail, B2B, etc.)

### 💰 Pricing & Discounts
Models for managing pricing strategies and discount rules.

- [**VariantPrice**](/v1/models/variant-price) - Multi-currency pricing
- [**Discount**](/v1/models/discount) - Discount rules and coupons
- [**DiscountApplication**](/v1/models/discount-application) - Applied discounts

### 📦 Orders & Transactions
Models for order processing and payment management.

- [**Order**](/v1/models/order) - Customer orders
- [**OrderLine**](/v1/models/order-line) - Order line items
- [**Cart**](/v1/models/cart) - Shopping carts
- [**CartLine**](/v1/models/cart-line) - Cart line items
- [**Transaction**](/v1/models/transaction) - Payment transactions

### 👥 Customers & Users
Models for customer and user management.

- [**Customer**](/v1/models/customer) - Customer accounts
- [**CustomerGroup**](/v1/models/customer-group) - Customer grouping
- [**CustomerAddress**](/v1/models/customer-address) - Customer addresses
- [**User**](/v1/models/user) - Admin users
- [**UserGroup**](/v1/models/user-group) - User roles and permissions

### 🌍 Sites & Channels
Models for multi-site and multi-channel commerce.

- [**Site**](/v1/models/site) - Markets/regions
- [**Channel**](/v1/models/channel) - Sales channels
- [**Currency**](/v1/models/currency) - Supported currencies
- [**Country**](/v1/models/country) - Countries and regions

### 🚚 Shipping & Fulfillment
Models for shipping configuration and management.

- [**ShippingZone**](/v1/models/shipping-zone) - Geographic shipping zones
- [**ShippingMethod**](/v1/models/shipping-method) - Shipping methods
- [**ShippingRate**](/v1/models/shipping-rate) - Shipping rates
- [**PurchaseOrder**](/v1/models/purchase-order) - Purchase orders
- [**PurchaseOrderItem**](/v1/models/purchase-order-item) - PO line items

### 🎁 Loyalty & Rewards
Models for customer loyalty programs.

- [**FidelityCard**](/v1/models/fidelity-card) - Loyalty cards
- [**FidelityTransaction**](/v1/models/fidelity-transaction) - Points transactions

### 📄 Content & Pages
Models for content management (Statamic-style).

- [**Page**](/v1/models/page) - CMS pages
- [**ShopperPage**](/v1/models/shopper-page) - Admin pages
- [**Menu**](/v1/models/menu) - Navigation menus
- [**MenuItem**](/v1/models/menu-item) - Menu items

### 🖼️ Media & Assets
Models for asset management.

- [**Asset**](/v1/models/asset) - Files and images
- [**AssetContainer**](/v1/models/asset-container) - Asset containers
- [**AssetFolder**](/v1/models/asset-folder) - Asset folders
- [**AssetTransformation**](/v1/models/asset-transformation) - Image transformations

### ⭐ Reviews & Ratings
Models for product reviews and ratings.

- [**ProductReview**](/v1/models/product-review) - Product reviews
- [**ReviewVote**](/v1/models/review-vote) - Review helpful votes
- [**ReviewMedia**](/v1/models/review-media) - Review images/videos

### 🔌 Apps & Extensions
Models for app marketplace and integrations.

- [**App**](/v1/models/app) - Marketplace apps
- [**AppInstallation**](/v1/models/app-installation) - Installed apps
- [**AppReview**](/v1/models/app-review) - App reviews
- [**AppWebhook**](/v1/models/app-webhook) - App webhooks
- [**AppApiToken**](/v1/models/app-api-token) - App API tokens

### 💳 Payment Gateways
Models for payment processing.

- [**PaymentGateway**](/v1/models/payment-gateway) - Payment gateways
- [**TaxRate**](/v1/models/tax-rate) - Tax rates

### 🏪 Suppliers & Inventory
Models for supplier management.

- [**Supplier**](/v1/models/supplier) - Suppliers
- [**ProductSupplier**](/v1/models/product-supplier) - Product-supplier relations
- [**StockNotification**](/v1/models/stock-notification) - Stock alerts

### 🎨 Storefront
Models for storefront customization.

- [**StorefrontTemplate**](/v1/models/storefront-template) - Page templates
- [**StorefrontSection**](/v1/models/storefront-section) - Template sections
- [**StorefrontTemplateSection**](/v1/models/storefront-template-section) - Section assignments

### 💝 Wishlists & Favorites
Models for customer wishlists.

- [**Wishlist**](/v1/models/wishlist) - Customer wishlists
- [**WishlistItem**](/v1/models/wishlist-item) - Wishlist items
- [**Favorite**](/v1/models/favorite) - Favorited products

### ⚙️ Settings & Configuration
Models for system configuration.

- [**Setting**](/v1/models/setting) - System settings
- [**UserPreference**](/v1/models/user-preference) - User preferences
- [**SocialAccount**](/v1/models/social-account) - Social login accounts

### 📊 Analytics
Models for analytics and tracking.

- [**AnalyticsEvent**](/v1/models/analytics-event) - Tracked events

---

## Common Model Features

### Soft Deletes

Most models use soft deletes to preserve data integrity:

```php
// Include soft-deleted records
Product::withTrashed()->get();

// Only soft-deleted records
Product::onlyTrashed()->get();

// Restore soft-deleted record
$product->restore();

// Permanently delete
$product->forceDelete();
```

### Custom Fields (JSONB)

Many models have a `data` JSONB column for custom fields:

```php
// Set custom field
$product->data = ['custom_field' => 'value'];
$product->save();

// Access custom field
$value = $product->data['custom_field'] ?? null;

// Query custom fields (PostgreSQL)
Product::whereJsonContains('data->tags', 'featured')->get();
```

### Timestamps

All models track creation and updates:

```php
$product->created_at;  // Carbon instance
$product->updated_at;  // Carbon instance
```

### Relationships

Models use Eloquent relationships extensively:

```php
// Eager loading
$products = Product::with(['variants', 'brand', 'type'])->get();

// Lazy eager loading
$products->load('variants.prices');

// Relationship counts
$products = Product::withCount('variants')->get();
```

---

## Model Conventions

### Naming
- **Singular** names (Product, not Products)
- **PascalCase** for class names
- **snake_case** for database tables

### Properties
- Use `$fillable` or `$guarded` for mass assignment
- Define `$casts` for type casting
- Specify `$dates` for date fields
- Use `$hidden` for sensitive data

### Methods
- **Accessors**: `get{Attribute}Attribute()`
- **Mutators**: `set{Attribute}Attribute()`
- **Scopes**: `scope{Name}()`
- **Relationships**: descriptive method names

---

## Next Steps

Explore specific model documentation or continue with:

- [**REST API**](/v1/rest-api) - API endpoints and authentication
- [**GraphQL**](/v1/graphql) - GraphQL schema and queries
- [**Frontend**](/v1/frontend) - Vue components and Inertia pages
- [**Development**](/v1/development) - Building with Cartino
