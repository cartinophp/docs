---
id: a9486792-0260-4def-b1c4-81454e23f4f7
blueprint: v1
title: Development
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741267898
---
# Development

Learn how to build with Cartino, including asset management, development workflow, and best practices.

[TOC]

## Development Environment

### Prerequisites

- **PHP 8.3+** with required extensions
- **Composer 2.0+**
- **Node.js 18+** and npm
- **PostgreSQL 13+** or MySQL 8.0+
- **Git** for version control

### Recommended Tools

- **Laravel Valet** (macOS) or **Homestead** for local development
- **VS Code** with PHP Intelephense and Volar (Vue) extensions
- **TablePlus** or **phpMyAdmin** for database management
- **Postman** or **Insomnia** for API testing

---

## Asset Management

Cartino uses a **Statamic-style** asset build system that automatically compiles and publishes frontend assets.

### During Development

When developing Cartino itself or building custom features:

```bash
# Development with hot reload (builds to public/build)
CARTINO_DEV=true npm run dev

# Or use the alias
npm run dev

# Build for development testing
npm run build:dev
```

### For Production Distribution

To build and publish assets for Laravel applications:

```bash
# Production build (outputs to public/vendor/cartino)
npm run build

# Or using Artisan command
php artisan cartino:build

# Build options
php artisan cartino:build --dev     # Development build
php artisan cartino:build --watch   # Build and watch for changes
```

### How Assets Are Loaded

The system automatically checks if assets have been built:

1. **Assets Built**: Uses optimized assets from `public/vendor/cartino/`
2. **Assets Not Built**: Falls back to Vite dev server

**Blade Template Example**:
```blade
@if(\Cartino\Support\Asset::isBuilt())
    {{-- Use built assets --}}
    {!! \Cartino\Support\Asset::styles() !!}
    {!! \Cartino\Support\Asset::scripts() !!}
@else
    {{-- Fallback to Vite dev server --}}
    @vite(['resources/js/app.js', 'resources/css/app.css'])
@endif
```

### Built Asset Structure

```
public/vendor/cartino/
├─ .vite/
│  └─ manifest.json          # Vite manifest with file mapping
└─ assets/
   ├─ app-[hash].js          # Main application
   ├─ app-[hash].css         # Compiled CSS
   ├─ vendor-[hash].js       # Libraries (Vue, Pinia, etc.)
   └─ ui-[hash].js           # UI components
```

---

## Vite Configuration

### Smart Configuration

Cartino uses an intelligent Vite setup:

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

const isPackageDev = process.env.CARTINO_DEV === 'true';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js', 'resources/css/app.css'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    
    publicDir: false,  // Prevent recursion issues
    
    build: {
        // Output directory based on environment
        outDir: isPackageDev ? "public/build" : "public/vendor/cartino",
        
        // Code splitting
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', '@inertiajs/vue3', 'pinia'],
                    ui: ['@heroicons/vue', '@reka-ui/vue'],
                },
            },
        },
    },
    
    resolve: {
        alias: {
            '@': '/resources/js',
            '~': '/resources',
        },
    },
});
```

### Available Commands

**For Package Developers**:
```bash
npm run dev              # Development with CARTINO_DEV=true
npm run dev:package      # Normal development (build to vendor/cartino)
npm run build           # Production build
npm run build:dev       # Development build
npm run build:watch     # Build with file watching
```

**For Application Users**:
```bash
php artisan cartino:install      # Complete installation
php artisan cartino:build        # Build assets
php artisan cartino:build --dev  # Development build
php artisan cartino:build --watch # Build with watching
```

---

## Development Workflow

### 1. Setting Up

```bash
# Clone repository
git clone https://github.com/cartinophp/cartino.git
cd cartino

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --seed

# Start development servers
npm run dev  # Terminal 1
php artisan serve  # Terminal 2
```

### 2. Building Features

**Backend Development**:
```bash
# Create a new model
php artisan make:model Product -mfc

# Create a controller
php artisan make:controller ProductController

# Create a service
php artisan make:class Services/ProductService

# Run tests
php artisan test
```

**Frontend Development**:
```bash
# Vue components are in resources/js/
# Edit components with hot reload
# Access at http://localhost:8000
```

### 3. Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=ProductTest

# Run with coverage
php artisan test --coverage

# Frontend tests (if configured)
npm run test
```

---

## Creating Custom Blueprints

### Step 1: Create Blueprint File

```bash
mkdir -p resources/blueprints/custom
touch resources/blueprints/custom/my_content.yaml
```

### Step 2: Define Structure

```yaml
# resources/blueprints/custom/my_content.yaml
title: My Custom Content
sections:
  main:
    display: Main Content
    fields:
      - handle: title
        field:
          type: text
          display: Title
          validate: required
          
      - handle: description
        field:
          type: textarea
          display: Description
  
  meta:
    display: Metadata
    import: seo
```

### Step 3: Use in Code

```php
use App\Services\BlueprintManager;

$blueprint = app(BlueprintManager::class)
    ->get('custom/my_content');

return Inertia::render('Admin/CustomContent/Create', [
    'blueprint' => $blueprint
]);
```

---

## Working with Models

### Creating Models

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;
    
    protected $fillable = [
        'site_id',
        'title',
        'slug',
        'description',
        'status',
        'data',
    ];
    
    protected $casts = [
        'data' => 'array',
        'published_at' => 'datetime',
        'status' => ProductStatus::class,
    ];
    
    // Relationships
    public function site()
    {
        return $this->belongsTo(Site::class);
    }
    
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    
    // Custom fields accessor
    public function getCustomField(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
    
    public function setCustomField(string $key, $value): void
    {
        $data = $this->data ?? [];
        $data[$key] = $value;
        $this->data = $data;
    }
}
```

### Using Enums

```php
namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
    
    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Archived => 'Archived',
        };
    }
}

// Usage
$product->status = ProductStatus::Active;
$product->save();
```

---

## API Development

### Creating API Endpoints

```php
// routes/api.php
Route::prefix('api')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
});
```

### API Resources

```php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
            'price' => $this->defaultVariant?->price,
            'images' => $this->getMedia('images')->map(fn($media) => [
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
            ]),
            'custom_fields' => $this->data,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

---

## Database Management

### Creating Migrations

```bash
php artisan make:migration create_custom_table
```

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('custom_table', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->jsonb('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['site_id', 'status']);
            $table->index('created_at');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('custom_table');
    }
};
```

### Seeders

```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        Product::factory()
            ->count(50)
            ->has(ProductVariant::factory()->count(3))
            ->create();
    }
}
```

---

## Event System

### Creating Events

```php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductCreated
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public Product $product
    ) {}
}
```

### Creating Listeners

```php
namespace App\Listeners;

use App\Events\ProductCreated;

class ProcessProductCreation
{
    public function handle(ProductCreated $event)
    {
        $product = $event->product;
        
        // Generate default variant if none exist
        if ($product->variants()->count() === 0) {
            $product->variants()->create([
                'title' => 'Default Title',
                'price' => 0,
            ]);
        }
    }
}
```

### Register Events

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    ProductCreated::class => [
        ProcessProductCreation::class,
        SendProductNotification::class,
    ],
];
```

---

## Best Practices

### DO ✅

- **Use type hints** and return types for all methods
- **Write tests** for all new features
- **Follow PSR-12** coding standards
- **Use enums** for fixed value sets
- **Implement soft deletes** for important data
- **Index JSONB fields** used in queries
- **Use transactions** for multi-step operations
- **Validate all inputs** with Form Requests
- **Cache expensive queries**
- **Document complex logic**

### DON'T ❌

- Don't skip migrations for schema changes
- Don't hardcode values (use config files)
- Don't expose sensitive data in API responses
- Don't forget to clear caches after changes
- Don't use raw queries without parameterization
- Don't skip validation on user inputs

---

## Debugging

### Laravel Debugbar

```bash
composer require barryvdh/laravel-debugbar --dev
```

### Logging

```php
use Illuminate\Support\Facades\Log;

Log::debug('Product created', ['product_id' => $product->id]);
Log::info('Order processed', ['order_id' => $order->id]);
Log::warning('Low stock', ['variant_id' => $variant->id]);
Log::error('Payment failed', ['error' => $e->getMessage()]);
```

### Query Debugging

```php
// Enable query log
\DB::enableQueryLog();

// Your queries here
Product::where('status', 'active')->get();

// Dump queries
dd(\DB::getQueryLog());
```

---

## Deployment

### Production Checklist

```bash
# 1. Optimize autoloader
composer install --no-dev --optimize-autoloader

# 2. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Build assets
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and optimize
php artisan optimize
```

### Environment Configuration

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourstore.com

# Database
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_DATABASE=your-db-name

# Caching
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## Next Steps

- [**Product Architecture**](/v1/product-architecture) - Build product features
- [**Blueprint System**](/v1/blueprint-system) - Create custom fields
- [**Sites & Markets**](/v1/sites-architecture) - Multi-site development
- [**API Reference**](/v1/api) - Complete API documentation
