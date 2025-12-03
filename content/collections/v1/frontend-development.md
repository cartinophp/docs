---
id: 9c0d1e2f-3a4b-5c6d-7e8f-9a0b1c2d3e4f
blueprint: v1
title: 'Frontend Development'
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264237
---
# 🎨 Frontend Development

Cartino's admin interface is built with **Vue 3**, **Inertia.js**, and **Vite**. Learn how to customize and extend the frontend.

[TOC]

## 📋 Overview

### Tech Stack

- **Vue 3** - Progressive JavaScript framework with Composition API
- **Inertia.js** - Modern monolith approach (no API needed)
- **Vite** - Fast build tool with HMR
- **Tailwind CSS** - Utility-first CSS framework
- **TypeScript** - Type-safe JavaScript (optional)

### Architecture

```
resources/
├── js/
│   ├── app.js              # Main entry point
│   ├── Pages/              # Inertia pages
│   │   ├── Products/
│   │   │   ├── Index.vue
│   │   │   ├── Create.vue
│   │   │   └── Edit.vue
│   │   ├── Orders/
│   │   └── Customers/
│   ├── Components/         # Reusable components
│   │   ├── ui/
│   │   ├── forms/
│   │   └── layouts/
│   ├── Composables/        # Vue composables
│   │   ├── useCart.js
│   │   ├── useProducts.js
│   │   └── useAuth.js
│   └── Stores/             # Pinia stores
│       ├── cart.js
│       └── user.js
├── css/
│   └── app.css             # Tailwind + custom styles
└── views/
    └── app.blade.php       # Root template
```

---

## 🚀 Getting Started

### Installation

```bash
# Install dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build
```

### Development Workflow

With Vite HMR, changes are reflected instantly:

```bash
# Terminal 1: Laravel
php artisan serve

# Terminal 2: Vite
npm run dev
```

Visit `http://localhost:8000` - changes auto-reload!

---

## 🧩 Creating Pages

### Inertia Page Component

```vue
<!-- resources/js/Pages/Products/Index.vue -->
<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';

const props = defineProps({
  products: Object, // Paginated products from controller
  filters: Object,
});

const search = ref(props.filters.search || '');

const handleSearch = () => {
  router.get('/products', { search: search.value }, {
    preserveState: true,
    preserveScroll: true,
  });
};
</script>

<template>
  <AppLayout title="Products">
    <!-- Search -->
    <div class="mb-6">
      <input
        v-model="search"
        @keyup.enter="handleSearch"
        type="text"
        placeholder="Search products..."
        class="form-input"
      />
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <ProductCard
        v-for="product in products.data"
        :key="product.id"
        :product="product"
      />
    </div>

    <!-- Pagination -->
    <Pagination :links="products.links" />
  </AppLayout>
</template>
```

### Controller

```php
// app/Http/Controllers/ProductController.php
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
            )
            ->with(['brand', 'images'])
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => $request->only('search'),
        ]);
    }
}
```

---

## 🎯 Composables

Create reusable logic with Vue composables:

### useCart Composable

```javascript
// resources/js/Composables/useCart.js
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const cart = ref(null);
const loading = ref(false);

export function useCart() {
  const fetchCart = async () => {
    loading.value = true;
    try {
      const response = await axios.get('/api/v1/cart');
      cart.value = response.data.data;
    } finally {
      loading.value = false;
    }
  };

  const addItem = async (variantId, quantity = 1) => {
    loading.value = true;
    try {
      await axios.post('/api/v1/cart/add', {
        variant_id: variantId,
        quantity,
      });
      await fetchCart();
    } finally {
      loading.value = false;
    }
  };

  const updateItem = async (lineId, quantity) => {
    loading.value = true;
    try {
      await axios.put(`/api/v1/cart/lines/${lineId}`, { quantity });
      await fetchCart();
    } finally {
      loading.value = false;
    }
  };

  const removeItem = async (lineId) => {
    loading.value = true;
    try {
      await axios.delete(`/api/v1/cart/lines/${lineId}`);
      await fetchCart();
    } finally {
      loading.value = false;
    }
  };

  const itemCount = computed(() => {
    return cart.value?.lines?.reduce((sum, line) => sum + line.quantity, 0) || 0;
  });

  const subtotal = computed(() => {
    return cart.value?.subtotal || 0;
  });

  return {
    cart,
    loading,
    itemCount,
    subtotal,
    fetchCart,
    addItem,
    updateItem,
    removeItem,
  };
}
```

### Usage in Component

```vue
<script setup>
import { useCart } from '@/Composables/useCart';
import { onMounted } from 'vue';

const { cart, itemCount, addItem, fetchCart } = useCart();

onMounted(() => {
  fetchCart();
});

const handleAddToCart = (variantId) => {
  addItem(variantId, 1);
};
</script>

<template>
  <div>
    <p>Cart: {{ itemCount }} items</p>
    <button @click="handleAddToCart(123)">
      Add to Cart
    </button>
  </div>
</template>
```

---

## 🎨 Components

### Reusable Product Card

```vue
<!-- resources/js/Components/ProductCard.vue -->
<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  product: Object,
});

const formatPrice = (price) => {
  return new Intl.NumberFormat('it-IT', {
    style: 'currency',
    currency: 'EUR',
  }).format(price);
};
</script>

<template>
  <Link
    :href="`/products/${product.id}`"
    class="group block bg-white rounded-lg shadow hover:shadow-lg transition"
  >
    <!-- Image -->
    <div class="aspect-square overflow-hidden rounded-t-lg">
      <img
        :src="product.featured_image?.url"
        :alt="product.name"
        class="w-full h-full object-cover group-hover:scale-105 transition"
      />
    </div>

    <!-- Content -->
    <div class="p-4">
      <h3 class="font-semibold text-lg mb-2">{{ product.name }}</h3>
      <p class="text-gray-600 text-sm mb-3">{{ product.brand?.name }}</p>
      
      <!-- Price -->
      <div class="flex items-center justify-between">
        <span class="text-xl font-bold text-blue-600">
          {{ formatPrice(product.price) }}
        </span>
        <span
          v-if="product.compare_at_price"
          class="text-sm text-gray-400 line-through"
        >
          {{ formatPrice(product.compare_at_price) }}
        </span>
      </div>

      <!-- Badge -->
      <div class="mt-3">
        <span
          v-if="product.is_new"
          class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded"
        >
          New
        </span>
        <span
          v-if="product.on_sale"
          class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded ml-2"
        >
          Sale
        </span>
      </div>
    </div>
  </Link>
</template>
```

---

## 🗂️ State Management (Pinia)

### Install Pinia

```bash
npm install pinia
```

### Setup

```javascript
// resources/js/app.js
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';

const pinia = createPinia();

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
    return pages[`./Pages/${name}.vue`];
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(pinia)
      .mount(el);
  },
});
```

### Create Store

```javascript
// resources/js/Stores/cart.js
import { defineStore } from 'pinia';
import axios from 'axios';

export const useCartStore = defineStore('cart', {
  state: () => ({
    cart: null,
    loading: false,
  }),

  getters: {
    itemCount: (state) => {
      return state.cart?.lines?.reduce((sum, line) => sum + line.quantity, 0) || 0;
    },

    subtotal: (state) => {
      return state.cart?.subtotal || 0;
    },
  },

  actions: {
    async fetchCart() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/v1/cart');
        this.cart = data.data;
      } finally {
        this.loading = false;
      }
    },

    async addItem(variantId, quantity = 1) {
      this.loading = true;
      try {
        await axios.post('/api/v1/cart/add', { variant_id: variantId, quantity });
        await this.fetchCart();
      } finally {
        this.loading = false;
      }
    },
  },
});
```

### Use in Component

```vue
<script setup>
import { useCartStore } from '@/Stores/cart';
import { onMounted } from 'vue';

const cartStore = useCartStore();

onMounted(() => {
  cartStore.fetchCart();
});
</script>

<template>
  <div>
    <p>Items: {{ cartStore.itemCount }}</p>
    <button @click="cartStore.addItem(123)">Add</button>
  </div>
</template>
```

---

## 🎯 Forms & Validation

### Inertia Form Helper

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/customers/register', {
    onSuccess: () => {
      // Redirect happens automatically
    },
  });
};
</script>

<template>
  <form @submit.prevent="submit">
    <div>
      <label>Name</label>
      <input v-model="form.name" type="text" />
      <div v-if="form.errors.name" class="text-red-600">
        {{ form.errors.name }}
      </div>
    </div>

    <div>
      <label>Email</label>
      <input v-model="form.email" type="email" />
      <div v-if="form.errors.email" class="text-red-600">
        {{ form.errors.email }}
      </div>
    </div>

    <button type="submit" :disabled="form.processing">
      Register
    </button>
  </form>
</template>
```

---

## 🌐 Internationalization (i18n)

### Install Vue I18n

```bash
npm install vue-i18n
```

### Setup

```javascript
// resources/js/i18n.js
import { createI18n } from 'vue-i18n';

const messages = {
  en: {
    welcome: 'Welcome',
    products: 'Products',
  },
  it: {
    welcome: 'Benvenuto',
    products: 'Prodotti',
  },
};

export default createI18n({
  locale: 'en',
  fallbackLocale: 'en',
  messages,
});
```

```javascript
// resources/js/app.js
import i18n from './i18n';

createApp({ render: () => h(App, props) })
  .use(i18n)
  .mount(el);
```

### Usage

```vue
<template>
  <h1>{{ $t('welcome') }}</h1>
  <p>{{ $t('products') }}</p>
</template>
```

---

## 🔗 Related Documentation

- [**Development Guide**](/v1/development) - Complete workflow
- [**REST API**](/v1/rest-api-overview) - API integration
- [**Components Library**](/v1/components) - UI components

---

## 📚 External Resources

- [Vue 3 Documentation](https://vuejs.org)
- [Inertia.js Documentation](https://inertiajs.com)
- [Vite Documentation](https://vitejs.dev)
- [Tailwind CSS](https://tailwindcss.com)
