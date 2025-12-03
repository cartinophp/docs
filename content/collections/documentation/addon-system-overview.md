---
id: addon-system-overview
blueprint: documentation
title: 'Addon System - Overview'
updated_by: system
updated_at: 1738675127
---
# Addon System - Overview

Cartino features a powerful addon system inspired by Statamic CMS, allowing you to extend the platform with custom features without modifying the core.

[TOC]

## What is an Addon?

An addon is a modular extension that adds functionality to Cartino. Addons can provide:

- **Custom Pages & Routes** - New admin pages with Inertia.js
- **Custom Field Types** - Specialized inputs for blueprints
- **Widgets** - Dashboard and sidebar components
- **Actions** - Row actions and bulk operations
- **Navigation Items** - Custom menu entries
- **Event Listeners** - React to system events
- **API Endpoints** - Extend the REST API

---

## Architecture Overview

### Stack Tecnologico

```
Backend:
├── Laravel 11 (Framework PHP)
├── MySQL/PostgreSQL (Database)
└── Inertia.js (Server-side routing)

Frontend:
├── Vue 3 Composition API (UI Framework)
├── Vite (Build Tool & HMR)
├── Tailwind CSS (Styling)
└── TypeScript (Optional)

Addon System:
├── YAML Blueprints (Content modeling)
├── Service Providers (Registration)
├── Event-Driven Injection (Hooks)
└── File-Based Discovery (Auto-loading)
```

### Directory Structure

```
your-app/
├── app/
│   └── CMS/
│       ├── Core/
│       │   ├── AddonManager.php
│       │   ├── BlueprintManager.php
│       │   └── FieldTypeRegistry.php
│       └── Events/
├── addons/                    # 📦 Addon directory
│   ├── blog/
│   │   ├── src/
│   │   │   ├── BlogServiceProvider.php
│   │   │   ├── Http/Controllers/
│   │   │   └── Listeners/
│   │   ├── routes/
│   │   │   └── web.php
│   │   ├── resources/
│   │   │   ├── js/
│   │   │   │   ├── Pages/
│   │   │   │   ├── Components/
│   │   │   │   ├── Widgets/
│   │   │   │   ├── Actions/
│   │   │   │   └── Fields/
│   │   │   └── blueprints/
│   │   │       ├── collections/
│   │   │       └── fieldsets/
│   │   └── addon.json
│   └── shop/
│       └── ...
├── resources/
│   ├── js/
│   │   ├── Components/        # Core components
│   │   ├── Fields/           # Core field types
│   │   ├── Layouts/
│   │   └── app.js
│   └── blueprints/           # Core blueprints
└── vite.config.js
```

---

## Quick Start

### Creating Your First Addon

```bash
# Generate addon structure
php artisan cms:make-addon Blog

# Output:
✓ Addon directory created: addons/blog/
✓ Service provider created
✓ Routes file created
✓ Vue components directory created
✓ Addon registered automatically
```

### Addon Structure

The generator creates this structure:

```
addons/blog/
├── src/
│   ├── BlogServiceProvider.php      # Main service provider
│   ├── Http/Controllers/            # Inertia controllers
│   ├── Models/                      # Eloquent models
│   └── Listeners/                   # Event listeners
├── routes/
│   └── web.php                      # Laravel routes
├── resources/
│   ├── js/
│   │   ├── Pages/                   # Inertia pages
│   │   ├── Components/              # Vue components
│   │   ├── Widgets/                 # Dashboard widgets
│   │   ├── Actions/                 # Custom actions
│   │   └── Fields/                  # Custom field types
│   └── blueprints/                  # YAML blueprints
├── database/
│   └── migrations/                  # Database migrations
└── addon.json                       # Addon metadata
```

---

## Addon Metadata (addon.json)

Every addon has an `addon.json` file with metadata and configuration:

```json
{
  "name": "Blog Addon",
  "slug": "blog",
  "version": "1.0.0",
  "description": "Complete blog management with posts, categories, and SEO",
  "author": "Your Name",
  "license": "MIT",

  "requires": {
    "cms": "^1.0",
    "php": "^8.1",
    "laravel": "^11.0"
  },

  "autoload": {
    "fields": "resources/js/Fields/*.vue",
    "widgets": "resources/js/Widgets/*.vue",
    "actions": "resources/js/Actions/*.vue"
  },

  "permissions": [
    "blog.read",
    "blog.create",
    "blog.update",
    "blog.delete",
    "blog.publish"
  ],

  "navigation": [
    {
      "section": "content",
      "label": "Blog",
      "icon": "edit",
      "route": "admin.blog.posts.index",
      "permission": "blog.read"
    }
  ]
}
```

---

## Service Provider

The service provider is the heart of your addon:

```php
<?php
// addons/blog/src/BlogServiceProvider.php

namespace Addons\Blog;

use Illuminate\Support\ServiceProvider;
use App\CMS\Core\AddonManager;

class BlogServiceProvider extends ServiceProvider
{
    public function boot(AddonManager $addons)
    {
        // 1. Load routes (auto-discovered)
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // 2. Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // 3. Load blueprints
        $this->loadBlueprintsFrom(__DIR__.'/../resources/blueprints');

        // 4. Register events (auto-discovered from Listeners/)
        // Event listeners in src/Listeners/ are auto-registered

        // 5. Register addon metadata
        $addons->register('blog', [
            'name' => 'Blog Addon',
            'description' => 'Complete blog management system',
            'version' => '1.0.0',
            'author' => 'Your Name',
        ]);
    }

    public function register()
    {
        // Register addon services
        $this->app->singleton(BlogService::class);
    }
}
```

---

## Key Features

### 1. File-Based Discovery

Addons are automatically discovered from the `addons/` directory. No manual registration needed.

### 2. Event-Driven Integration

Use Laravel events to integrate with the core:

```php
// Add navigation items
Event::listen(NavigationBuilding::class, function ($event) {
    $event->navigation->addToSection('content', [
        'label' => 'Blog',
        'route' => 'admin.blog.posts.index',
    ]);
});
```

### 3. Injection Points

Inject UI components at strategic points:

```yaml
# Injection points available:
navigation.main.before
page.header.actions
collection.list.toolbar.left
item.detail.sidebar.top
dashboard.widgets.overview
```

### 4. YAML Blueprints

Define content structures in YAML files:

```yaml
# addons/blog/resources/blueprints/collections/posts.yaml
title: Blog Post
sections:
  main:
    fields:
      - handle: title
        field:
          type: text
          validate: required
```

### 5. Inertia.js Pages

Build SPA experiences with Vue 3:

```vue
<!-- addons/blog/resources/js/Pages/Posts/Index.vue -->
<template>
  <AdminLayout title="Blog Posts">
    <DataTable :items="posts" />
  </AdminLayout>
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps({
  posts: Array,
});
</script>
```

---

## Addon Capabilities

### What You Can Build

- **Content Types** - Posts, products, custom entities
- **Admin Interfaces** - Full CRUD with Inertia
- **Custom Fields** - Specialized inputs for blueprints
- **Dashboard Widgets** - Analytics and quick actions
- **Bulk Actions** - Process multiple items
- **API Extensions** - Add REST endpoints
- **Event Listeners** - React to system events
- **Custom Permissions** - Fine-grained access control

### What's Provided by Core

- **Authentication** - User management and sessions
- **Authorization** - Permissions and roles
- **Routing** - Laravel routing
- **Database** - Eloquent ORM
- **Queue System** - Background jobs
- **File Storage** - Asset management
- **Caching** - Redis/File cache
- **Events** - Event system
- **Validation** - Input validation

---

## CLI Commands

### Creating Components

```bash
# Create addon
php artisan cms:make-addon Blog

# Create custom field
php artisan cms:make-field RatingField --addon=blog

# Create widget
php artisan cms:make-widget StatsWidget --addon=blog

# Create action
php artisan cms:make-action ExportAction --addon=blog --type=bulk

# Create page
php artisan cms:make-page BlogIndex --addon=blog
```

### Managing Addons

```bash
# List all addons
php artisan cms:list-addons

# Enable addon
php artisan cms:enable blog

# Disable addon
php artisan cms:disable blog

# Publish addon assets
php artisan cms:publish blog
```

---

## Next Steps

Explore these guides to learn more:

- [Creating Addons](/docs/addon-creating) - Build your first addon
- [Custom Fields](/docs/addon-custom-fields) - Create field types
- [Widgets](/docs/addon-widgets) - Build dashboard widgets
- [Actions](/docs/addon-actions) - Add custom actions
- [Events](/docs/addon-events) - Listen to system events
- [Best Practices](/docs/addon-best-practices) - Tips and patterns
