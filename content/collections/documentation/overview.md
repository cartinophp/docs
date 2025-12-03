---
id: overview-cartino
blueprint: documentation
title: Overview
updated_by: system
updated_at: 1738675127
---
# Overview

Cartino is a powerful headless e-commerce platform combining Shopify-style commerce architecture with Statamic CMS flexibility, built on Laravel.

[TOC]

## What is Cartino?

Cartino is an **open-source headless e-commerce platform** that merges the best practices from leading e-commerce systems:

- **Shopify-style** product/variant architecture and multi-variant management
- **Statamic CMS** file-based content system, blueprints, and fieldsets
- **Laravel 11** modern PHP framework with Inertia.js and Vue 3
- **Enterprise-ready** multi-site, multi-currency, multi-locale capabilities

Built for developers who need flexibility, merchants who need power, and teams who value clean architecture.

---

## Architecture Stack

### Backend
- **Laravel 11** - Modern PHP framework
- **MySQL/PostgreSQL** - Primary database with JSONB support
- **Inertia.js** - Server-side routing for SPA experience
- **Spatie Media Library** - Asset management

### Frontend
- **Vue 3 Composition API** - Reactive UI framework
- **Vite** - Lightning-fast build tool with HMR
- **Tailwind CSS** - Utility-first styling
- **TypeScript** - Optional type safety

### Commerce Core
- **Shopify-inspired** product/variant model
- **Multi-site architecture** (Sites as Markets)
- **Blueprint system** for custom fields (Statamic-style)
- **Advanced inventory** management with locations
- **Loyalty & rewards** system

---

## Core Features

### 🛍️ Product Management
- **Multi-variant products** with unlimited options
- Every product has at least one variant (Shopify approach)
- Advanced inventory tracking with multiple locations
- Stock movements, transfers, and reservations
- Product types, brands, and categories with soft deletes

### 🌍 Multi-Site Architecture
- **Sites as Markets** - Geographic/strategic markets
- **Channels** - Sales methods (Web, Mobile, POS, B2B, Marketplaces)
- **Session-based** currency and locale selection
- **Flexible pricing** per site, channel, and customer group
- Tax configuration per region

### 💰 Pricing & Discounts
- **Multi-currency** support with automatic conversion
- **Customer group pricing** with tier discounts
- **Advanced discount system** (percentage, fixed, BOGO)
- **Coupon management** with usage limits
- Price rules based on quantity, customer, or date

### 📦 Inventory Management
- **Multi-location** inventory tracking
- **Stock movements** with full audit trail
- **Stock reservations** for pending orders
- **Stock transfers** between locations
- **Stock adjustments** with reason tracking

### 👥 Customer Management
- **Customer groups** with custom pricing
- **Loyalty card system** with unique codes
- **Points & rewards** with tiered conversion
- **Multiple addresses** per customer
- Customer lifetime value tracking

### 🎨 Content Management
- **YAML Blueprints** for content modeling (Statamic-inspired)
- **Fieldsets** - Reusable field groups
- **Custom fields** stored in JSONB columns
- **File-based** blueprint discovery
- Flexible content types (Pages, Collections, etc.)

### 🔧 Developer Experience
- **Vite integration** with hot module replacement
- **Statamic-style** asset building and distribution
- **Type-safe** Eloquent models with PHP enums
- **Comprehensive** API with GraphQL support
- **Event-driven** architecture

---

## Key Concepts

### Products & Variants

Every product in Cartino follows the **Shopify model**:

```php
Product {
    title: "Cotton T-Shirt"
    options: [
        {name: "Color", values: ["Red", "Blue"]},
        {name: "Size", values: ["S", "M", "L"]}
    ]
}

// Automatically generates 6 variants:
ProductVariant { option1: "Red", option2: "S", price: 19.99 }
ProductVariant { option1: "Red", option2: "M", price: 21.99 }
// ... and so on
```

Even simple products have one default variant, ensuring consistent data structure.

### Sites as Markets

Sites represent **geographic or strategic markets**:

```php
Site: "Italy" {
    countries: ['IT', 'SM', 'VA']
    default_currency: 'EUR'
    tax_included_in_prices: true
    channels: [Web, Mobile, POS]
    catalogs: [Retail, B2B]
}
```

Users select currency/locale via session, enabling flexible browsing.

### Blueprint System

Content structure defined in **YAML files** (Statamic-style):

```yaml
# resources/blueprints/products/product.yaml
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

Custom field values stored in `data` JSONB column for ultimate flexibility.

---

## Use Cases

### Retail E-commerce
- Multi-country stores with localized pricing
- Seasonal catalogs and promotions
- Loyalty programs and rewards
- Mobile app integration

### B2B Commerce
- Customer-specific pricing and catalogs
- Bulk ordering with quantity discounts
- Multiple billing addresses per account
- Purchase order management

### Multi-Brand Marketplace
- Separate sites per brand or region
- Vendor-specific channels
- Shared inventory across brands
- Centralized admin panel

### Omnichannel Retail
- Web, mobile, POS integration
- Real-time inventory sync
- Click & collect functionality
- Store-specific pricing

---

## Why Cartino?

### ✅ Built on Proven Patterns
- Shopify's battle-tested product model
- Statamic's elegant content system
- Laravel's robust foundation

### ✅ Developer Friendly
- Modern stack (Laravel 11, Vue 3, Vite)
- Type-safe with PHP 8.3+ and TypeScript
- Clean architecture with SOLID principles
- Comprehensive documentation

### ✅ Merchant Focused
- Intuitive admin interface
- Flexible pricing strategies
- Advanced inventory control
- Built-in loyalty system

### ✅ Enterprise Ready
- Multi-site, multi-currency, multi-locale
- Soft deletes for data integrity
- Full audit trail on inventory
- GraphQL API for integrations

---

## Next Steps

Ready to get started? Check out these guides:

- [Installation](/docs/installation) - Get Cartino up and running
- [Sites Architecture](/docs/sites-architecture) - Understand multi-site setup
- [Product Architecture](/docs/product-architecture) - Learn the product model
- [Blueprint System](/docs/blueprint-system) - Master custom fields
- [Addon System](/docs/addon-system) - Extend with custom features
