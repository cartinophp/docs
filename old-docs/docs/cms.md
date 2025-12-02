# 📚 CMS Addon System - Documentazione Completa

> Sistema di estensioni per CMS Laravel + Inertia + Vue con YAML Blueprints

## 🎯 Indice

1. [Architettura](#architettura)
2. [Quick Start](#quick-start)
3. [Creare un Addon](#creare-addon)
4. [YAML Blueprints](#yaml-blueprints)
5. [Custom Field Types](#custom-fields)
6. [Injection Points](#injection-points)
7. [Navigation Extension](#navigation)
8. [Actions & Bulk Operations](#actions)
9. [Widgets & Panels](#widgets)
10. [Event System](#events)
11. [CLI Commands](#cli)
12. [Best Practices](#best-practices)

---

## 🏗️ Architettura {#architettura}

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
your-cms/
├── app/
│   └── CMS/
│       ├── Core/
│       │   ├── AddonManager.php
│       │   ├── BlueprintManager.php
│       │   └── FieldTypeRegistry.php
│       └── Events/
├── addons/                    # 📦 Directory addon
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

## 🚀 Quick Start {#quick-start}

### Installazione CMS

```bash
# 1. Installa dipendenze
composer install
npm install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Database
php artisan migrate

# 4. Start development server
php artisan cms:dev
```

Il comando `cms:dev` avvia automaticamente:
- Laravel dev server (http://localhost:8000)
- Vite dev server con HMR (http://localhost:5173)
- Watch mode per addon

### Creare il Primo Addon

```bash
# Genera struttura addon completa
php artisan cms:make-addon Blog

# Output:
✓ Addon directory created: addons/blog/
✓ Service provider created
✓ Routes file created
✓ Vue components directory created
✓ Addon registered automatically
```

---

## 📦 Creare un Addon {#creare-addon}

### 1. Struttura Addon

```
addons/blog/
├── src/
│   ├── BlogServiceProvider.php      # Service provider principale
│   ├── Http/Controllers/
│   │   ├── PostController.php       # Controller Inertia
│   │   └── CategoryController.php
│   ├── Models/
│   │   ├── Post.php
│   │   └── Category.php
│   └── Listeners/
│       ├── AddBlogNavigation.php    # Event listener
│       └── RegisterBlogWidgets.php
├── routes/
│   └── web.php                      # Laravel routes
├── resources/
│   ├── js/
│   │   ├── Pages/                   # Inertia pages
│   │   │   ├── Posts/
│   │   │   │   ├── Index.vue
│   │   │   │   ├── Create.vue
│   │   │   │   └── Edit.vue
│   │   │   └── Categories/
│   │   ├── Components/              # Vue components
│   │   │   ├── PostTable.vue
│   │   │   └── PostForm.vue
│   │   ├── Widgets/                 # Dashboard widgets
│   │   │   ├── BlogStats.vue
│   │   │   └── RecentPosts.vue
│   │   ├── Actions/                 # Custom actions
│   │   │   ├── PublishAction.vue
│   │   │   └── ExportAction.vue
│   │   └── Fields/                  # Custom field types
│   │       └── AuthorField.vue
│   └── blueprints/                  # YAML blueprints
│       ├── collections/
│       │   └── posts.yaml
│       └── fieldsets/
│           └── post_meta.yaml
├── database/
│   └── migrations/
│       └── 2024_01_01_create_posts_table.php
└── addon.json                       # Addon metadata
```

### 2. Service Provider

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
        // Event listeners in src/Listeners/ sono auto-registrati
        
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

### 3. addon.json (Metadata)

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

### 4. Routes (Laravel Standard)

```php
<?php
// addons/blog/routes/web.php

use Addons\Blog\Http\Controllers\PostController;
use Addons\Blog\Http\Controllers\CategoryController;

Route::middleware(['web', 'auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Posts CRUD
    Route::prefix('blog')->name('blog.')->group(function () {
        Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
        Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}', [PostController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        
        // Bulk actions
        Route::post('/posts/bulk/publish', [PostController::class, 'bulkPublish'])->name('posts.bulk.publish');
    });
    
});
```

### 5. Controller Inertia

```php
<?php
// addons/blog/src/Http/Controllers/PostController.php

namespace Addons\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Addons\Blog\Models\Post;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index()
    {
        return Inertia::render('Blog/Posts/Index', [
            'posts' => Post::with('author', 'categories')
                ->latest()
                ->paginate(20),
            
            // Injection points data
            'widgets' => $this->getInjectedWidgets('blog.posts.index'),
            'actions' => $this->getInjectedActions('blog.posts.list'),
            'filters' => request()->only(['search', 'status', 'category']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Blog/Posts/Create', [
            'blueprint' => $this->getBlueprint('collections.posts'),
            'categories' => Category::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'status' => 'required|in:draft,published',
        ]);

        $post = Post::create($validated);

        return redirect()
            ->route('admin.blog.posts.edit', $post)
            ->with('success', 'Post created successfully');
    }
}
```

---

## 📋 YAML Blueprints {#yaml-blueprints}

### Cos'è un Blueprint?

Un blueprint definisce la struttura dei dati e come vengono presentati nell'admin panel. Simile a Statamic, usa YAML per configurazione dichiarativa.

### Esempio Blueprint Completo

```yaml
# addons/blog/resources/blueprints/collections/posts.yaml

title: Blog Post
handle: posts

sections:
  
  # Sezione principale - Contenuto
  main:
    display: Content
    fields:
      - 
        handle: title
        field:
          type: text
          display: Title
          instructions: The title of your blog post
          validate: required|max:255
          width: 100
          
      -
        handle: slug
        field:
          type: slug
          display: URL Slug
          from: title
          validate: required
          
      -
        handle: content
        field:
          type: bard
          display: Content
          instructions: Main post content
          validate: required
          buttons:
            - h2
            - h3
            - bold
            - italic
            - unorderedlist
            - orderedlist
            - quote
            - link
            - image
          toolbar_mode: floating
          
      -
        handle: excerpt
        field:
          type: textarea
          display: Excerpt
          instructions: Short summary for listings
          character_limit: 160
  
  # Sezione sidebar - Metadata
  sidebar:
    display: Sidebar
    fields:
      -
        handle: status
        field:
          type: select
          display: Status
          default: draft
          options:
            draft: Draft
            published: Published
            scheduled: Scheduled
          validate: required
          
      -
        handle: publish_date
        field:
          type: datetime
          display: Publish Date
          default: now
          mode: single
          
      -
        handle: featured_image
        field:
          type: assets
          display: Featured Image
          container: uploads
          max_files: 1
          mode: grid
          
      -
        handle: categories
        field:
          type: terms
          display: Categories
          taxonomies:
            - categories
          mode: select
          
      -
        handle: tags
        field:
          type: tags
          display: Tags
  
  # Sezione SEO - Importato da fieldset
  seo:
    display: SEO
    import: seo_fieldset
```

### Fieldset Riutilizzabile

```yaml
# resources/blueprints/fieldsets/seo_fieldset.yaml

title: SEO Meta
handle: seo_fieldset

fields:
  -
    handle: seo_title
    field:
      type: text
      display: Meta Title
      instructions: SEO title (max 60 chars)
      character_limit: 60
      
  -
    handle: seo_description
    field:
      type: textarea
      display: Meta Description
      instructions: SEO description (max 160 chars)
      character_limit: 160
      rows: 3
      
  -
    handle: og_image
    field:
      type: assets
      display: Open Graph Image
      container: uploads
      max_files: 1
```

### Usare Blueprint in Vue

```vue
<!-- Blog/Posts/Create.vue -->
<template>
  <AdminLayout title="Create Post">
    <FormBuilder
      :blueprint="blueprint"
      v-model="form"
      @submit="submit"
    >
      <template #actions>
        <button type="submit" :disabled="form.processing">
          Create Post
        </button>
      </template>
    </FormBuilder>
  </AdminLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import FormBuilder from '@/Components/FormBuilder.vue'

const props = defineProps(['blueprint', 'categories'])

const form = useForm({
  title: '',
  slug: '',
  content: '',
  status: 'draft',
  categories: [],
  // ... altri campi dal blueprint
})

const submit = () => {
  form.post(route('admin.blog.posts.store'))
}
</script>
```

---

## 🎨 Custom Field Types {#custom-fields}

### Creare un Custom Field

```bash
# Generate custom field
php artisan cms:make-field RatingField --addon=blog

# Genera: addons/blog/resources/js/Fields/RatingField.vue
```

### Implementazione Field Type

```vue
<!-- addons/blog/resources/js/Fields/RatingField.vue -->
<template>
  <div class="field-wrapper">
    <label v-if="field.display" class="field-label">
      {{ field.display }}
      <span v-if="field.validate?.includes('required')" class="required">*</span>
    </label>
    
    <p v-if="field.instructions" class="field-instructions">
      {{ field.instructions }}
    </p>
    
    <div class="rating-field">
      <button
        v-for="n in maxStars"
        :key="n"
        type="button"
        @click="setRating(n)"
        @mouseover="hoverRating = n"
        @mouseleave="hoverRating = 0"
        :class="[
          'star-button',
          { 
            'active': n <= (hoverRating || modelValue),
            'half': field.allow_half && isHalfStar(n)
          }
        ]"
      >
        <StarIcon :filled="n <= (hoverRating || modelValue)" />
      </button>
      
      <span v-if="field.show_value" class="rating-value">
        {{ modelValue || 0 }} / {{ maxStars }}
      </span>
    </div>
    
    <p v-if="error" class="field-error">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import StarIcon from './StarIcon.vue'

const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: Number,
    default: 0
  },
  error: String
})

const emit = defineEmits(['update:modelValue'])

const maxStars = computed(() => props.field.max_stars || 5)
const hoverRating = ref(0)

const setRating = (value) => {
  if (props.field.allow_half && value === props.modelValue) {
    // Click again on same star for half star
    emit('update:modelValue', value - 0.5)
  } else {
    emit('update:modelValue', value)
  }
}

const isHalfStar = (n) => {
  return props.modelValue && (n - 0.5) === props.modelValue
}
</script>

<style scoped>
.rating-field {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.star-button {
  background: none;
  border: none;
  padding: 0.25rem;
  cursor: pointer;
  transition: transform 0.2s;
}

.star-button:hover {
  transform: scale(1.1);
}

.rating-value {
  margin-left: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}
</style>
```

### Registrare Field Type

```php
<?php
// addons/blog/src/BlogServiceProvider.php

use App\CMS\Core\FieldTypeRegistry;

public function boot(FieldTypeRegistry $fields)
{
    // Register custom field type
    $fields->register('rating', [
        'component' => 'RatingField',
        'path' => __DIR__.'/../resources/js/Fields/RatingField.vue',
        'config_schema' => [
            'max_stars' => ['type' => 'integer', 'default' => 5],
            'allow_half' => ['type' => 'boolean', 'default' => false],
            'show_value' => ['type' => 'boolean', 'default' => true],
        ]
    ]);
}
```

### Usare nel Blueprint

```yaml
# blueprints/collections/products.yaml
fields:
  -
    handle: customer_rating
    field:
      type: rating
      display: Customer Rating
      max_stars: 5
      allow_half: true
      show_value: true
      validate: required|numeric|min:1|max:5
```

---

## 🎯 Injection Points {#injection-points}

### Sistema di Injection Points

Gli injection points permettono agli addon di inserire contenuto in punti specifici dell'interfaccia admin.

### Punti Disponibili

```
Navigation:
├── navigation.main.before
├── navigation.main.after
├── navigation.sections.{section}
└── navigation.user.dropdown

Pages:
├── page.header.breadcrumbs
├── page.header.title.before
├── page.header.title.after
├── page.header.actions
├── page.sidebar.top
├── page.sidebar.middle
└── page.sidebar.bottom

Collection Lists:
├── collection.list.toolbar.left
├── collection.list.toolbar.right
├── collection.list.filters
├── collection.list.bulk_actions
└── collection.list.row.actions

Item Details:
├── item.detail.header.actions
├── item.detail.tabs
├── item.detail.sidebar.top
├── item.detail.sidebar.middle
├── item.detail.sidebar.bottom
├── item.detail.content.before
└── item.detail.content.after

Forms:
├── form.fields.before
├── form.fields.after
├── form.actions.primary
└── form.actions.secondary

Dashboard:
├── dashboard.widgets.overview
├── dashboard.widgets.activity
└── dashboard.sidebar.tools
```

### Usare Injection Points nelle Pagine

```vue
<!-- Core CMS - Collection List Page -->
<template>
  <AdminLayout>
    <div class="page-header">
      <!-- Injection: Header Actions -->
      <InjectionZone
        point="page.header.actions"
        :context="{ collection, items }"
      />
    </div>

    <div class="page-content">
      <div class="main-content">
        <!-- Toolbar with injections -->
        <div class="toolbar">
          <InjectionZone
            point="collection.list.toolbar.left"
            :context="{ collection, selectedItems }"
          />

          <div class="toolbar-center">
            <SearchInput v-model="search" />
          </div>

          <InjectionZone
            point="collection.list.toolbar.right"
            :context="{ collection, selectedItems }"
          />
        </div>

        <!-- Table -->
        <DataTable :items="items" />

        <!-- Bulk Actions -->
        <InjectionZone
          v-if="selectedItems.length > 0"
          point="collection.list.bulk_actions"
          :context="{ collection, selectedItems }"
        />
      </div>

      <!-- Sidebar with injections -->
      <aside class="page-sidebar">
        <InjectionZone
          point="page.sidebar.top"
          :context="{ collection, page: 'list' }"
        />

        <div class="default-sidebar-content">
          <!-- Filters, stats, etc -->
        </div>

        <InjectionZone
          point="page.sidebar.bottom"
          :context="{ collection, page: 'list' }"
        />
      </aside>
    </div>
  </AdminLayout>
</template>

<script setup>
import InjectionZone from '@/Components/InjectionZone.vue'

defineProps(['collection', 'items'])
</script>
```

### InjectionZone Component

```vue
<!-- Core CMS - InjectionZone.vue -->
<template>
  <div
    v-if="components.length > 0"
    class="injection-zone"
    :data-point="point"
  >
    <component
      v-for="(comp, index) in components"
      :is="comp.component"
      :key="comp.id"
      v-bind="comp.props"
      :context="context"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  point: {
    type: String,
    required: true
  },
  context: {
    type: Object,
    default: () => ({})
  }
})

const components = ref([])
const page = usePage()

onMounted(async () => {
  // Get injected components for this point
  const injections = page.props.injections?.[props.point] || []
  
  // Load components dynamically
  const loaded = await Promise.all(
    injections.map(async (injection) => {
      const module = await import(
        /* @vite-ignore */
        injection.component_path
      )
      
      return {
        id: injection.id,
        component: module.default,
        props: injection.props || {}
      }
    })
  )
  
  components.value = loaded
})
</script>
```

---

## 🧭 Navigation Extension {#navigation}

### Aggiungere Items al Menu

```php
<?php
// addons/blog/src/Listeners/AddBlogNavigation.php

namespace Addons\Blog\Listeners;

use App\CMS\Events\NavigationBuilding;

class AddBlogNavigation
{
    public function handle(NavigationBuilding $event)
    {
        $event->navigation->addToSection('content', [
            'id' => 'blog',
            'label' => 'Blog',
            'icon' => 'edit',
            'route' => 'admin.blog.posts.index',
            'permission' => 'blog.read',
            'order' => 10,
            'active' => 'admin.blog.*',
            'children' => [
                [
                    'label' => 'All Posts',
                    'route' => 'admin.blog.posts.index',
                    'icon' => 'list',
                ],
                [
                    'label' => 'New Post',
                    'route' => 'admin.blog.posts.create',
                    'icon' => 'plus',
                    'permission' => 'blog.create',
                ],
                [
                    'label' => 'Categories',
                    'route' => 'admin.blog.categories.index',
                    'icon' => 'folder',
                ],
            ]
        ]);
    }
}
```

### Navigation via addon.json

```json
{
  "navigation": [
    {
      "section": "content",
      "id": "blog",
      "label": "Blog",
      "icon": "edit",
      "route": "admin.blog.posts.index",
      "permission": "blog.read",
      "order": 10,
      "children": [
        {
          "label": "All Posts",
          "route": "admin.blog.posts.index"
        },
        {
          "label": "Categories",
          "route": "admin.blog.categories.index"
        }
      ]
    }
  ]
}
```

---

## ⚡ Actions & Bulk Operations {#actions}

### Row Action (Dropdown Item)

```bash
php artisan cms:make-action ExportPostAction --addon=blog --type=row
```

```vue
<!-- addons/blog/resources/js/Actions/ExportPostAction.vue -->
<template>
  <DropdownItem
    @click="exportPost"
    :disabled="loading"
  >
    <DownloadIcon class="w-4 h-4" />
    <span>Export to PDF</span>
  </DropdownItem>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import DropdownItem from '@/Components/DropdownItem.vue'
import DownloadIcon from '@/Icons/DownloadIcon.vue'

const props = defineProps({
  context: Object  // { collection, item }
})

const loading = ref(false)

const exportPost = async () => {
  loading.value = true
  
  try {
    const response = await fetch(
      route('admin.blog.posts.export', props.context.item.id)
    )
    
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${props.context.item.title}.pdf`
    a.click()
  } catch (error) {
    alert('Export failed')
  } finally {
    loading.value = false
  }
}
</script>
```

### Registrare Action

```php
<?php
// addons/blog/src/BlogServiceProvider.php

use App\CMS\Core\ActionRegistry;

public function boot(ActionRegistry $actions)
{
    $actions->register('collection.list.row.actions', [
        'id' => 'export-post',
        'component' => 'ExportPostAction',
        'path' => __DIR__.'/../resources/js/Actions/ExportPostAction.vue',
        'conditions' => [
            'collection' => 'posts',
        ],
        'permissions' => ['blog.export'],
        'order' => 20,
    ]);
}
```

### Bulk Action

```vue
<!-- addons/blog/resources/js/Actions/BulkPublishAction.vue -->
<template>
  <button
    @click="publishSelected"
    :disabled="loading || context.selectedItems.length === 0"
    class="btn btn-primary"
  >
    <CheckIcon class="w-4 h-4" />
    <span>Publish Selected ({{ context.selectedItems.length }})</span>
  </button>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  context: Object  // { collection, selectedItems }
})

const loading = ref(false)

const publishSelected = () => {
  if (!confirm(`Publish ${props.context.selectedItems.length} posts?`)) {
    return
  }
  
  loading.value = true
  
  router.post(
    route('admin.blog.posts.bulk.publish'),
    {
      ids: props.context.selectedItems.map(item => item.id)
    },
    {
      onFinish: () => loading.value = false,
      onSuccess: () => {
        // Clear selection
        window.dispatchEvent(new CustomEvent('clear-selection'))
      }
    }
  )
}
</script>
```

---

## 📊 Widgets & Panels {#widgets}

### Dashboard Widget

```bash
php artisan cms:make-widget BlogStatsWidget --addon=blog
```

```vue
<!-- addons/blog/resources/js/Widgets/BlogStatsWidget.vue -->
<template>
  <div class="widget">
    <div class="widget-header">
      <h3>Blog Statistics</h3>
      <select v-model="period" @change="loadStats">
        <option value="week">This Week</option>
        <option value="month">This Month</option>
        <option value="year">This Year</option>
      </select>
    </div>

    <div class="widget-content">
      <div v-if="loading" class="loading">
        <Spinner />
      </div>

      <div v-else class="stats-grid">
        <StatCard
          label="Total Posts"
          :value="stats.total_posts"
          icon="edit"
          trend="+12%"
        />
        <StatCard
          label="Published"
          :value="stats.published"
          icon="check"
          :trend="stats.published_trend"
        />
        <StatCard
          label="Drafts"
          :value="stats.drafts"
          icon="clock"
        />
        <StatCard
          label="Views"
          :value="formatNumber(stats.views)"
          icon="eye"
          :trend="stats.views_trend"
        />
      </div>

      <div class="chart-container">
        <LineChart
          :data="stats.chart_data"
          :labels="stats.chart_labels"
        />
      </div>
    </div>

    <div class="widget-footer">
      <router-link
        :href="route('admin.blog.posts.index')"
        class="widget-link"
      >
        View All Posts →
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import StatCard from '@/Components/StatCard.vue'
import LineChart from '@/Components/LineChart.vue'

const props = defineProps({
  context: Object  // { dashboard, user }
})

const period = ref('month')
const stats = ref({})
const loading = ref(true)

const loadStats = async () => {
  loading.value = true
  
  try {
    const response = await fetch(
      route('admin.blog.stats', { period: period.value })
    )
    stats.value = await response.json()
  } catch (error) {
    console.error('Failed to load stats', error)
  } finally {
    loading.value = false
  }
}

const formatNumber = (num) => {
  return new Intl.NumberFormat().format(num)
}

onMounted(() => {
  loadStats()
})
</script>

<style scoped>
.widget {
  @apply bg-white rounded-lg shadow-sm border border-gray-200;
}

.stats-grid {
  @apply grid grid-cols-2 md:grid-cols-4 gap-4 mb-6;
}

.chart-container {
  @apply h-64;
}
</style>
```

### Registrare Widget

```php
<?php
// addons/blog/src/Listeners/RegisterBlogWidgets.php

namespace Addons\Blog\Listeners;

use App\CMS\Events\DashboardBuilding;

class RegisterBlogWidgets
{
    public function handle(DashboardBuilding $event)
    {
        $event->dashboard->addWidget([
            'id' => 'blog-stats',
            'component' => 'BlogStatsWidget',
            'path' => __DIR__.'/../../resources/js/Widgets/BlogStatsWidget.vue',
            'title' => 'Blog Statistics',
            'size' => 'large',  // small, medium, large, full
            'order' => 10,
            'permissions' => ['blog.read'],
        ]);
    }
}
```

### Sidebar Widget (Item Detail)

```vue
<!-- addons/blog/resources/js/Widgets/PostAnalyticsWidget.vue -->
<template>
  <div class="sidebar-widget">
    <h4 class="widget-title">Analytics</h4>

    <div class="analytics-summary">
      <div class="metric">
        <label>Views</label>
        <span class="value">{{ analytics.views || 0 }}</span>
      </div>
      <div class="metric">
        <label>Unique Visitors</label>
        <span class="value">{{ analytics.unique_visitors || 0 }}</span>
      </div>
      <div class="metric">
        <label>Avg. Time</label>
        <span class="value">{{ formatTime(analytics.avg_time) }}</span>
      </div>
    </div>

    <div class="mini-chart">
      <SparklineChart :data="analytics.daily_views" />
    </div>

    <button @click="viewFullReport" class="btn-link">
      View Full Report →
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import SparklineChart from '@/Components/SparklineChart.vue'

const props = defineProps({
  context: Object  // { collection, item }
})

const analytics = ref({})

onMounted(async () => {
  const response = await fetch(
    route('admin.blog.analytics', props.context.item.id)
  )
  analytics.value = await response.json()
})

const formatTime = (seconds) => {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

const viewFullReport = () => {
  window.open(
    route('admin.analytics.post', props.context.item.id),
    '_blank'
  )
}
</script>
```

---

## 🔔 Event System {#events}

### Eventi Disponibili

```php
// Navigation
NavigationBuilding::class
NavigationBuilt::class

// Content
EntryCreating::class
EntryCreated::class
EntryUpdating::class
EntryUpdated::class
EntryDeleting::class
EntryDeleted::class

// Collections
CollectionCreated::class
CollectionUpdated::class
CollectionDeleted::class

// Blueprints
BlueprintLoading::class
BlueprintLoaded::class

// Dashboard
DashboardBuilding::class
DashboardBuilt::class

// Forms
FormSubmitting::class
FormSubmitted::class

// Assets
AssetUploading::class
AssetUploaded::class
AssetDeleting::class
AssetDeleted::class
```

### Creare Event Listener

```php
<?php
// addons/blog/src/Listeners/HandlePostPublished.php

namespace Addons\Blog\Listeners;

use App\CMS\Events\EntryUpdated;
use Addons\Blog\Notifications\PostPublishedNotification;

class HandlePostPublished
{
    public function handle(EntryUpdated $event)
    {
        // Only for posts collection
        if ($event->entry->collection !== 'posts') {
            return;
        }

        // Only when status changes to published
        if ($event->entry->status !== 'published') {
            return;
        }

        if ($event->entry->wasRecentlyCreated || 
            $event->entry->wasChanged('status')) {
            
            // Send notifications
            $this->notifySubscribers($event->entry);
            
            // Clear cache
            cache()->forget("post.{$event->entry->id}");
            
            // Update sitemap
            dispatch(new UpdateSitemapJob());
            
            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($event->entry)
                ->log('Post published');
        }
    }

    protected function notifySubscribers($post)
    {
        $subscribers = User::whereHas('subscriptions', function($q) use ($post) {
            $q->where('subscribed_to', 'blog')
              ->orWhereIn('category_id', $post->categories->pluck('id'));
        })->get();

        foreach ($subscribers as $subscriber) {
            $subscriber->notify(new PostPublishedNotification($post));
        }
    }
}
```

### Registrare Event Listener

```php
<?php
// addons/blog/src/BlogServiceProvider.php

use Illuminate\Support\Facades\Event;
use App\CMS\Events\EntryUpdated;
use Addons\Blog\Listeners\HandlePostPublished;

public function boot()
{
    // Option 1: Manual registration
    Event::listen(
        EntryUpdated::class,
        HandlePostPublished::class
    );

    // Option 2: Auto-discovery (recommended)
    // Place listener in src/Listeners/ with proper type hint
    // Will be auto-discovered by CMS
}
```

### Hook System (Alternative)

```php
<?php
// Usare hook direttamente nel codice

use App\CMS\Facades\Hook;

// Registrare hook
Hook::listen('entry.saving', function ($entry) {
    // Modify entry before saving
    if ($entry->collection === 'posts' && empty($entry->slug)) {
        $entry->slug = Str::slug($entry->title);
    }
});

// Filter hook (modify and return)
Hook::filter('entry.data', function ($data, $entry) {
    // Add computed fields
    $data['read_time'] = $this->calculateReadTime($data['content']);
    return $data;
});

// Action hook (trigger side effects)
Hook::action('entry.deleted', function ($entry) {
    // Cleanup related data
    if ($entry->collection === 'posts') {
        Comment::where('post_id', $entry->id)->delete();
    }
});
```

---

## 🛠️ CLI Commands {#cli}

### Comandi Disponibili

```bash
# Development
php artisan cms:dev                    # Start dev environment
php artisan cms:dev --addon=blog       # Watch specific addon

# Addon Management
php artisan cms:make-addon Blog        # Create new addon
php artisan cms:list-addons            # List all addons
php artisan cms:enable blog            # Enable addon
php artisan cms:disable blog           # Disable addon
php artisan cms:publish blog           # Publish addon assets

# Components
php artisan cms:make-field RatingField --addon=blog
php artisan cms:make-widget StatsWidget --addon=blog
php artisan cms:make-action ExportAction --addon=blog --type=bulk
php artisan cms:make-page BlogIndex --addon=blog

# Blueprints
php artisan cms:make-blueprint posts --addon=blog --type=collection
php artisan cms:make-fieldset seo --addon=blog

# Database
php artisan cms:migrate blog           # Run addon migrations
php artisan cms:seed blog              # Run addon seeders

# Build & Assets
php artisan cms:build                  # Build all assets
php artisan cms:build --addon=blog     # Build specific addon
php artisan cms:watch                  # Watch for changes

# Cache
php artisan cms:cache:clear            # Clear CMS caches
php artisan cms:cache:blueprints       # Cache blueprints
```

### Creare Custom Command

```php
<?php
// addons/blog/src/Console/GenerateSitemapCommand.php

namespace Addons\Blog\Console;

use Illuminate\Console\Command;
use Addons\Blog\Models\Post;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'blog:sitemap:generate
                            {--collection=posts : Collection to include}';

    protected $description = 'Generate sitemap for blog posts';

    public function handle()
    {
        $this->info('Generating sitemap...');

        $posts = Post::where('status', 'published')
            ->orderBy('updated_at', 'desc')
            ->get();

        $sitemap = $this->generateSitemap($posts);
        
        file_put_contents(
            public_path('blog-sitemap.xml'),
            $sitemap
        );

        $this->info("✓ Sitemap generated with {$posts->count()} posts");
    }

    protected function generateSitemap($posts)
    {
        // Implementation...
    }
}
```

### Registrare Command

```php
<?php
// addons/blog/src/BlogServiceProvider.php

public function boot()
{
    if ($this->app->runningInConsole()) {
        $this->commands([
            Console\GenerateSitemapCommand::class,
        ]);
    }
}
```

---

## ✨ Best Practices {#best-practices}

### 1. Struttura Addon

✅ **DO**:
- Mantieni addon focussati su una feature specifica
- Usa namespace chiari (`Addons\Blog\...`)
- Segui PSR-4 autoloading
- Documenta pubblic API

❌ **DON'T**:
- Creare "mega-addon" che fanno tutto
- Modificare file del core CMS
- Fare hardcoded references ad altri addon
- Dipendere da addon non dichiarati

### 2. Blueprints

✅ **DO**:
- Usa fieldsets riutilizzabili per campi comuni
- Valida input con `validate` rules
- Fornisci `instructions` chiare per utenti
- Usa `default` values quando appropriato

❌ **DON'T**:
- Duplicare field definitions
- Dimenticare validation
- Usare `handle` non SEO-friendly
- Sovraccaricare una sezione con troppi field

### 3. Performance

✅ **DO**:
- Lazy load componenti pesanti
- Cache blueprint parsed data
- Usa eager loading per relazioni
- Optimize database queries

```vue
<!-- Lazy load heavy component -->
<script setup>
const HeavyChart = defineAsyncComponent(() =>
  import('./HeavyChart.vue')
)
</script>
```

❌ **DON'T**:
- Load tutti i componenti eagerly
- N+1 query problems
- Fetch troppi dati non necessari

### 4. Security

✅ **DO**:
- Valida sempre user input
- Usa Laravel's authorization (Gates/Policies)
- Sanitize output quando render HTML
- Check permissions in controllers E componenti

```php
// Controller
public function update(Request $request, Post $post)
{
    $this->authorize('update', $post);
    
    $validated = $request->validate([
        'title' => 'required|max:255',
        // ...
    ]);
    
    $post->update($validated);
}
```

❌ **DON'T**:
- Fidare del client-side validation solo
- Esporre API endpoints senza auth
- Usare `{!! !!}` senza sanitization

### 5. Vue Best Practices

✅ **DO**:
- Usa Composition API (script setup)
- Definisci props con types
- Emit events per parent communication
- Use computed per derived state

```vue
<script setup>
import { computed } from 'vue'

const props = defineProps({
  items: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['update', 'delete'])

const totalItems = computed(() => props.items.length)
</script>
```

❌ **DON'T**:
- Mutare props direttamente
- Usare Options API per nuovi componenti
- Duplicare logica tra componenti

### 6. Testing

✅ **DO**:
- Test controllers con Feature tests
- Test components con Vue Test Utils
- Test event listeners
- Test validations

```php
// tests/Feature/Blog/PostControllerTest.php
public function test_can_create_post()
{
    $this->actingAs($this->admin)
        ->post(route('admin.blog.posts.store'), [
            'title' => 'Test Post',
            'content' => 'Content',
            'status' => 'published',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
        
    $this->assertDatabaseHas('posts', [
        'title' => 'Test Post',
    ]);
}
```

### 7. Documentation

✅ **DO**:
- Documenta custom field types
- Provide usage examples
- Document public API methods
- Include README in addon

❌ **DON'T**:
- Assumere che utenti conoscano tutto
- Dimenticare di aggiornare docs

---

## 🎓 Examples Repository

### Addon Completi di Esempio

```bash
# Clone examples repository
git clone https://github.com/your-cms/addon-examples.git addons/examples

# Available examples:
examples/
├── basic-widget/          # Simple dashboard widget
├── custom-field/          # Custom field type example
├── crud-complete/         # Full CRUD with all features
├── api-integration/       # External API integration
├── payment-gateway/       # Payment processing example
└── advanced-permissions/  # Complex permission system
```

### Quick Start Templates

```bash
# Start from template
php artisan cms:make-addon MyAddon --template=crud
# or
php artisan cms:make-addon MyAddon --template=api-integration
# or
php artisan cms:make-addon MyAddon --template=custom-fields

# Available templates:
- crud: Complete CRUD functionality
- api-integration: External API integration
- custom-fields: Custom field types pack
- dashboard: Dashboard widgets
- reports: Reporting & analytics
```

---

## 📞 Support & Community

### Resources

- **Documentation**: https://docs.your-cms.com
- **API Reference**: https://api-docs.your-cms.com
- **Forum**: https://forum.your-cms.com
- **Discord**: https://discord.gg/your-cms
- **GitHub**: https://github.com/your-cms

### Getting Help

1. Check documentation first
2. Search existing issues/forum
3. Ask in Discord #help channel
4. Create GitHub issue
5. Contact support (enterprise customers)

### Contributing

We welcome contributions! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

## 📄 License

This CMS and addon system is open-sourced software licensed under the [MIT license](LICENSE.md).

---

**Happy building! 🚀**