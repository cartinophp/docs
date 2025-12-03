---
id: 8b9c0d1e-2f3a-4b5c-6d7e-8f9a0b1c2d3e
blueprint: v1
title: 'REST API Overview'
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264236
---
# 🌐 REST API Overview

Cartino provides a comprehensive RESTful API for building custom storefronts, mobile apps, and integrations.

[TOC]

## 📋 Introduction

The Cartino REST API allows you to:
- Build custom storefronts with Vue, React, or any frontend framework
- Create mobile applications (iOS, Android, Flutter)
- Integrate with third-party services (ERPs, CRMs, marketplaces)
- Build custom admin tools and dashboards
- Automate workflows and data synchronization

---

## 🔐 Authentication

### API Keys

Each request must include an API key in the header:

```bash
Authorization: Bearer your-api-key-here
```

### Creating API Keys

```php
// In admin panel or via Artisan
php artisan cartino:api-key:create "My Storefront App"
```

### Customer Authentication

For customer-specific operations, use JWT tokens:

```bash
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "customer@example.com",
  "password": "password123"
}
```

Response:

```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "customer": {
    "id": 123,
    "email": "customer@example.com",
    "full_name": "Mario Rossi"
  }
}
```

Use the token in subsequent requests:

```bash
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

---

## 🌍 Base URL & Versioning

All API endpoints are versioned:

```
https://your-domain.com/api/v1/
```

Current version: **v1**

---

## 📦 Response Format

All responses follow a consistent JSON structure:

### Success Response

```json
{
  "data": {
    "id": 1,
    "name": "Product Name",
    "price": 99.99
  },
  "meta": {
    "timestamp": "2024-01-15T10:30:00Z"
  }
}
```

### Collection Response

```json
{
  "data": [
    { "id": 1, "name": "Product 1" },
    { "id": 2, "name": "Product 2" }
  ],
  "links": {
    "first": "https://api.cartino.com/v1/products?page=1",
    "last": "https://api.cartino.com/v1/products?page=10",
    "prev": null,
    "next": "https://api.cartino.com/v1/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

### Error Response

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

---

## 🔍 Filtering & Sorting

### Filtering

Use query parameters to filter results:

```bash
GET /api/v1/products?filter[status]=active&filter[brand_id]=5
```

Multiple values:

```bash
GET /api/v1/products?filter[brand_id]=5,6,7
```

### Sorting

Sort by one or more fields:

```bash
GET /api/v1/products?sort=-created_at,name
```

- Prefix with `-` for descending order
- Default: ascending

### Pagination

```bash
GET /api/v1/products?page=2&per_page=20
```

- Default `per_page`: 15
- Maximum `per_page`: 100

### Including Relationships

Load related data with `include`:

```bash
GET /api/v1/products/123?include=variants,brand,categories
```

Multiple relationships:

```bash
GET /api/v1/products?include=variants.prices,brand,images
```

### Field Selection

Request only specific fields:

```bash
GET /api/v1/products?fields[products]=id,name,price&fields[variants]=id,sku
```

---

## 🛒 API Endpoints by Resource

### 🛍️ Products

- `GET /api/v1/products` - List products
- `GET /api/v1/products/{id}` - Get product details
- `GET /api/v1/products/{id}/variants` - Get product variants
- `GET /api/v1/products/{id}/reviews` - Get product reviews
- `POST /api/v1/products` - Create product (Admin)
- `PUT /api/v1/products/{id}` - Update product (Admin)
- `DELETE /api/v1/products/{id}` - Delete product (Admin)

[**Full Products API Documentation →**](/v1/rest-api/products)

### 📦 Orders

- `GET /api/v1/orders` - List orders (Customer)
- `GET /api/v1/orders/{id}` - Get order details
- `POST /api/v1/orders` - Create order
- `PUT /api/v1/orders/{id}` - Update order (Admin)
- `POST /api/v1/orders/{id}/cancel` - Cancel order
- `POST /api/v1/orders/{id}/refund` - Refund order (Admin)

[**Full Orders API Documentation →**](/v1/rest-api/orders)

### 🛒 Cart

- `GET /api/v1/cart` - Get current cart
- `POST /api/v1/cart/add` - Add item to cart
- `PUT /api/v1/cart/lines/{id}` - Update cart line
- `DELETE /api/v1/cart/lines/{id}` - Remove cart line
- `POST /api/v1/cart/clear` - Clear cart
- `POST /api/v1/cart/apply-coupon` - Apply discount code

[**Full Cart API Documentation →**](/v1/rest-api/cart)

### 👤 Customers

- `POST /api/v1/customers/register` - Register customer
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/logout` - Logout
- `GET /api/v1/customers/me` - Get current customer
- `PUT /api/v1/customers/me` - Update profile
- `GET /api/v1/customers/me/addresses` - List addresses
- `POST /api/v1/customers/me/addresses` - Add address

[**Full Customers API Documentation →**](/v1/rest-api/customers)

### 🏷️ Categories & Brands

- `GET /api/v1/categories` - List categories
- `GET /api/v1/categories/{id}` - Get category
- `GET /api/v1/categories/{id}/products` - Get category products
- `GET /api/v1/brands` - List brands
- `GET /api/v1/brands/{id}` - Get brand

[**Full Categories API Documentation →**](/v1/rest-api/categories)

### 🎁 Discounts & Coupons

- `GET /api/v1/discounts` - List active discounts
- `POST /api/v1/discounts/validate` - Validate discount code
- `GET /api/v1/coupons/{code}` - Get coupon details

[**Full Discounts API Documentation →**](/v1/rest-api/discounts)

### 💳 Payments

- `POST /api/v1/payments/intents` - Create payment intent
- `POST /api/v1/payments/{id}/confirm` - Confirm payment
- `GET /api/v1/payment-methods` - List available payment methods

[**Full Payments API Documentation →**](/v1/rest-api/payments)

### 🚚 Shipping

- `POST /api/v1/shipping/calculate` - Calculate shipping rates
- `GET /api/v1/shipping/methods` - List shipping methods

[**Full Shipping API Documentation →**](/v1/rest-api/shipping)

### 💎 Loyalty

- `GET /api/v1/loyalty/card` - Get loyalty card
- `GET /api/v1/loyalty/transactions` - Get points history
- `POST /api/v1/loyalty/redeem` - Redeem points

[**Full Loyalty API Documentation →**](/v1/rest-api/loyalty)

---

## ⚡ Rate Limiting

API requests are rate-limited to ensure fair usage:

- **Unauthenticated**: 60 requests/minute
- **Authenticated**: 120 requests/minute
- **Admin**: 300 requests/minute

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 115
X-RateLimit-Reset: 1610000000
```

---

## 🔔 Webhooks

Subscribe to events via webhooks:

```php
// Configure in admin panel or via API
POST /api/v1/webhooks

{
  "url": "https://your-app.com/webhooks/cartino",
  "events": [
    "order.created",
    "order.paid",
    "product.updated",
    "customer.registered"
  ],
  "secret": "your-webhook-secret"
}
```

Webhook payload example:

```json
{
  "event": "order.created",
  "data": {
    "id": 123,
    "order_number": "ORD-2024-00123",
    "total": 159.99
  },
  "timestamp": "2024-01-15T10:30:00Z",
  "signature": "sha256=..."
}
```

Verify webhook signatures:

```php
$signature = hash_hmac('sha256', $payload, $secret);
if (!hash_equals($signature, $receivedSignature)) {
    abort(403, 'Invalid signature');
}
```

---

## 🛠️ SDKs & Libraries

### JavaScript/TypeScript

```bash
npm install @cartino/sdk
```

```typescript
import { CartinoClient } from '@cartino/sdk';

const cartino = new CartinoClient({
  baseUrl: 'https://your-domain.com',
  apiKey: 'your-api-key',
});

// Fetch products
const products = await cartino.products.list({
  filter: { status: 'active' },
  include: ['variants', 'brand'],
});

// Add to cart
await cartino.cart.add({
  variant_id: 123,
  quantity: 2,
});
```

### PHP

```bash
composer require cartino/php-sdk
```

```php
use Cartino\SDK\CartinoClient;

$cartino = new CartinoClient([
    'base_url' => 'https://your-domain.com',
    'api_key' => 'your-api-key',
]);

// Fetch products
$products = $cartino->products()->list([
    'filter' => ['status' => 'active'],
    'include' => ['variants', 'brand'],
]);
```

### Python

```bash
pip install cartino-sdk
```

```python
from cartino import CartinoClient

cartino = CartinoClient(
    base_url='https://your-domain.com',
    api_key='your-api-key'
)

# Fetch products
products = cartino.products.list(
    filter={'status': 'active'},
    include=['variants', 'brand']
)
```

---

## 🧪 Testing

Use sandbox mode for testing:

```bash
X-Cartino-Sandbox: true
```

Sandbox features:
- Test payment processing without real charges
- Mock shipping calculations
- Isolated data (doesn't affect production)

---

## 📚 API Documentation Tools

### Postman Collection

Import our Postman collection:

```bash
https://api.cartino.com/postman/collection.json
```

### OpenAPI Specification

Download OpenAPI (Swagger) spec:

```bash
https://api.cartino.com/openapi.json
```

### Interactive Documentation

Explore the API interactively:

```
https://your-domain.com/api/docs
```

---

## 🔗 Next Steps

- [**Products API**](/v1/rest-api/products) - Complete product endpoints
- [**Orders API**](/v1/rest-api/orders) - Order management
- [**Cart API**](/v1/rest-api/cart) - Shopping cart
- [**Customers API**](/v1/rest-api/customers) - Customer accounts
- [**GraphQL API**](/v1/graphql) - GraphQL alternative

---

## 💡 Example: Complete Checkout Flow

```javascript
// 1. Add products to cart
await cartino.cart.add({ variant_id: 123, quantity: 2 });
await cartino.cart.add({ variant_id: 456, quantity: 1 });

// 2. Apply discount code
await cartino.cart.applyCoupon({ code: 'WELCOME10' });

// 3. Calculate shipping
const shippingRates = await cartino.shipping.calculate({
  address: {
    country: 'IT',
    postal_code: '20100',
  }
});

// 4. Create order
const order = await cartino.orders.create({
  shipping_address: { /* ... */ },
  billing_address: { /* ... */ },
  shipping_method_id: shippingRates[0].id,
  payment_method: 'stripe',
});

// 5. Create payment intent
const paymentIntent = await cartino.payments.createIntent({
  order_id: order.id,
});

// 6. Confirm payment (client-side with Stripe.js)
const { error } = await stripe.confirmCardPayment(
  paymentIntent.client_secret,
  { payment_method: cardElement }
);

// 7. Confirm order
if (!error) {
  await cartino.payments.confirm(paymentIntent.id);
}
```

---

## 📞 Support

- **Documentation**: [https://docs.cartino.com](https://docs.cartino.com)
- **GitHub**: [https://github.com/cartino/cartino](https://github.com/cartino/cartino)
- **Discord**: [https://discord.gg/cartino](https://discord.gg/cartino)
- **Email**: support@cartino.com
