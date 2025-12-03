---
id: model-customer
blueprint: documentation
title: 'Model: Customer'
updated_by: system
updated_at: 1738675127
---
# Model: Customer

The Customer model represents registered users who can place orders in your store. Customers can belong to groups, have loyalty cards, and maintain multiple addresses.

[TOC]

## Overview

A **Customer** is a registered user account with full e-commerce capabilities:

```php
Customer {
    email: "john@example.com"
    first_name: "John"
    last_name: "Doe"
    customer_group_id: 2  // Wholesale
    fidelity_points: 1500
    lifetime_value: 5432.50
    status: "active"
}
```

**Key Features**:
- Customer groups (Retail, Wholesale, VIP)
- Loyalty card & points system
- Multiple shipping/billing addresses
- Order history & lifetime value
- Wishlist & saved carts
- Company information (B2B)

---

## Database Schema

### `customers` Table

```php
Schema::create('customers', function (Blueprint $table) {
    $table->id();

    // Personal Information
    $table->string('email')->unique();
    $table->string('first_name');
    $table->string('last_name');
    $table->string('phone')->nullable();
    $table->date('date_of_birth')->nullable();
    $table->string('gender')->nullable(); // male, female, other, prefer_not_to_say

    // Authentication (if using Customer login)
    $table->string('password')->nullable();
    $table->rememberToken();
    $table->timestamp('email_verified_at')->nullable();

    // Company Information (B2B)
    $table->string('company_name')->nullable();
    $table->string('vat_number')->nullable();
    $table->string('tax_code')->nullable();

    // Customer Segmentation
    $table->foreignId('customer_group_id')->nullable()->constrained()->nullOnDelete();

    // Marketing
    $table->boolean('accepts_marketing')->default(false);
    $table->boolean('accepts_sms')->default(false);
    $table->string('marketing_source')->nullable(); // google, facebook, referral

    // Status & Metrics
    $table->string('status')->default('active'); // active, inactive, blocked
    $table->decimal('lifetime_value', 15, 2)->default(0); // Total spent
    $table->integer('orders_count')->default(0);
    $table->timestamp('last_order_at')->nullable();

    // Locale & Currency Preferences
    $table->string('locale')->nullable();
    $table->string('currency', 3)->nullable();

    // Notes & Tags
    $table->text('notes')->nullable();
    $table->json('tags')->nullable();

    // Custom Fields (JSONB)
    $table->json('data')->nullable();

    // Timestamps
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('email');
    $table->index('customer_group_id');
    $table->index('status');
    $table->index('lifetime_value');
    $table->index('last_order_at');
    $table->fullText(['first_name', 'last_name', 'email', 'company_name']);
});
```

---

## Properties

### Core Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | bigint | Primary key |
| `email` | string | Email address (unique) |
| `first_name` | string | First name |
| `last_name` | string | Last name |
| `phone` | string | Phone number |
| `date_of_birth` | date | Date of birth |
| `gender` | string | Gender |

### Authentication

| Property | Type | Description |
|----------|------|-------------|
| `password` | string | Hashed password |
| `remember_token` | string | Remember token |
| `email_verified_at` | timestamp | Email verification |

### Company (B2B)

| Property | Type | Description |
|----------|------|-------------|
| `company_name` | string | Company name |
| `vat_number` | string | VAT/Tax ID |
| `tax_code` | string | Tax code |

### Segmentation

| Property | Type | Description |
|----------|------|-------------|
| `customer_group_id` | foreignId | Customer group |
| `tags` | json | Tags array |

### Marketing

| Property | Type | Description |
|----------|------|-------------|
| `accepts_marketing` | boolean | Email marketing consent |
| `accepts_sms` | boolean | SMS marketing consent |
| `marketing_source` | string | Acquisition source |

### Metrics

| Property | Type | Description |
|----------|------|-------------|
| `status` | string | `active`, `inactive`, `blocked` |
| `lifetime_value` | decimal(15,2) | Total spent |
| `orders_count` | integer | Number of orders |
| `last_order_at` | timestamp | Last order date |

### Relationships

| Relation | Type | Description |
|----------|------|-------------|
| `group` | belongsTo | CustomerGroup |
| `addresses` | hasMany | Address |
| `orders` | hasMany | Order |
| `carts` | hasMany | Cart |
| `fidelityCard` | hasOne | FidelityCard |
| `fidelityTransactions` | hasMany | FidelityTransaction |
| `wishlistItems` | hasMany | WishlistItem |
| `reviews` | hasMany | ProductReview |

---

## Eloquent Model

### Basic Usage

```php
use Shopper\Models\Customer;

// Create customer
$customer = Customer::create([
    'email' => 'john@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'phone' => '+39 123 456 7890',
    'password' => Hash::make('password'),
    'customer_group_id' => 1,
    'accepts_marketing' => true,
    'status' => 'active',
]);

// Find by ID
$customer = Customer::find(1);

// Find by email
$customer = Customer::where('email', 'john@example.com')->first();

// Update
$customer->update([
    'phone' => '+39 098 765 4321',
    'accepts_marketing' => false,
]);

// Soft delete
$customer->delete();

// Restore
$customer->restore();
```

---

## Relationships

### Customer Group

```php
// Get customer group
$group = $customer->group;

// Check if customer is in group
if ($customer->group_id === 2) {
    // Wholesale customer
}

// Set customer group
$customer->group()->associate($wholesaleGroup);
$customer->save();

// Customers in a group
$wholesaleCustomers = CustomerGroup::find(2)->customers;
```

### Addresses

```php
use Shopper\Models\Address;

// Create address
$address = $customer->addresses()->create([
    'type' => 'shipping', // shipping, billing
    'first_name' => 'John',
    'last_name' => 'Doe',
    'company' => 'ACME Corp',
    'address_line_1' => 'Via Roma 123',
    'address_line_2' => 'Apt 4',
    'city' => 'Milan',
    'state' => 'MI',
    'postal_code' => '20121',
    'country' => 'IT',
    'phone' => '+39 123 456 7890',
    'is_default' => true,
]);

// Get all addresses
$addresses = $customer->addresses;

// Get default shipping address
$shippingAddress = $customer->addresses()
    ->where('type', 'shipping')
    ->where('is_default', true)
    ->first();

// Get default billing address
$billingAddress = $customer->defaultBillingAddress();
```

### Orders

```php
// Get all orders
$orders = $customer->orders;

// Get completed orders
$completedOrders = $customer->orders()
    ->where('status', 'completed')
    ->get();

// Latest order
$lastOrder = $customer->orders()->latest()->first();

// Orders count
$orderCount = $customer->orders()->count();

// Total spent
$totalSpent = $customer->orders()
    ->where('status', 'completed')
    ->sum('total');

// Average order value
$avgOrderValue = $customer->orders()
    ->where('status', 'completed')
    ->avg('total');
```

### Carts

```php
// Get active cart
$cart = $customer->carts()
    ->where('status', 'active')
    ->first();

// Or use helper
$cart = $customer->activeCart();

// Get abandoned carts
$abandonedCarts = $customer->carts()
    ->where('status', 'abandoned')
    ->where('updated_at', '<', now()->subDays(7))
    ->get();
```

### Fidelity Card & Points

```php
// Get fidelity card
$card = $customer->fidelityCard;

// Create fidelity card if doesn't exist
if (!$customer->fidelityCard) {
    $card = $customer->getOrCreateFidelityCard();
}

// Get points balance
$points = $customer->getFidelityPoints();

// Add points
$customer->addFidelityPoints(100, 'Welcome bonus');

// Redeem points
$customer->redeemFidelityPoints(500, 'Discount applied to order #123');

// Get transactions
$transactions = $customer->fidelityTransactions()
    ->orderByDesc('created_at')
    ->get();
```

### Wishlist

```php
// Add to wishlist
$customer->wishlistItems()->create([
    'product_variant_id' => $variantId,
]);

// Get wishlist
$wishlist = $customer->wishlistItems()
    ->with('variant.product')
    ->get();

// Remove from wishlist
$customer->wishlistItems()
    ->where('product_variant_id', $variantId)
    ->delete();

// Check if in wishlist
$inWishlist = $customer->wishlistItems()
    ->where('product_variant_id', $variantId)
    ->exists();
```

### Reviews

```php
// Get customer reviews
$reviews = $customer->reviews;

// Create review
$customer->reviews()->create([
    'product_id' => $productId,
    'order_id' => $orderId,
    'rating' => 5,
    'title' => 'Great product!',
    'comment' => 'Very satisfied with my purchase.',
    'verified_purchase' => true,
]);

// Average rating given by customer
$avgRating = $customer->reviews()->avg('rating');
```

---

## Scopes

### Query Scopes

```php
// Active customers
Customer::active()->get();

// By status
Customer::status('active')->get();

// By group
Customer::inGroup($groupId)->get();

// With orders
Customer::has('orders')->get();

// VIP customers (high lifetime value)
Customer::vip()->get();

// Recent customers
Customer::recent()->get();

// Search customers
Customer::search('john')->get();

// With marketing consent
Customer::marketingEnabled()->get();
```

### Scope Definitions

```php
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

public function scopeStatus($query, string $status)
{
    return $query->where('status', $status);
}

public function scopeInGroup($query, int $groupId)
{
    return $query->where('customer_group_id', $groupId);
}

public function scopeVip($query, float $threshold = 10000)
{
    return $query->where('lifetime_value', '>=', $threshold);
}

public function scopeRecent($query, int $days = 30)
{
    return $query->where('created_at', '>=', now()->subDays($days));
}

public function scopeSearch($query, string $term)
{
    return $query->whereFullText(
        ['first_name', 'last_name', 'email', 'company_name'],
        $term
    );
}

public function scopeMarketingEnabled($query)
{
    return $query->where('accepts_marketing', true);
}
```

---

## Accessors & Mutators

### Accessors

```php
// Get full name
public function getFullNameAttribute(): string
{
    return trim("{$this->first_name} {$this->last_name}");
}

// Get display name (with company if B2B)
public function getDisplayNameAttribute(): string
{
    if ($this->company_name) {
        return "{$this->full_name} ({$this->company_name})";
    }
    return $this->full_name;
}

// Check if verified
public function getIsVerifiedAttribute(): bool
{
    return $this->email_verified_at !== null;
}

// Check if B2B customer
public function getIsB2bAttribute(): bool
{
    return !empty($this->company_name) || !empty($this->vat_number);
}

// Get avatar URL (from Gravatar or uploaded)
public function getAvatarUrlAttribute(): string
{
    if ($this->hasMedia('avatar')) {
        return $this->getFirstMediaUrl('avatar');
    }

    // Fallback to Gravatar
    $hash = md5(strtolower(trim($this->email)));
    return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
}

// Get customer tier based on lifetime value
public function getTierAttribute(): string
{
    return match(true) {
        $this->lifetime_value >= 10000 => 'platinum',
        $this->lifetime_value >= 5000 => 'gold',
        $this->lifetime_value >= 1000 => 'silver',
        default => 'bronze',
    };
}

// Usage
echo $customer->full_name;
echo $customer->display_name;
if ($customer->is_verified) { ... }
if ($customer->is_b2b) { ... }
echo $customer->tier;
```

### Mutators

```php
// Normalize email
public function setEmailAttribute($value)
{
    $this->attributes['email'] = strtolower(trim($value));
}

// Hash password automatically
public function setPasswordAttribute($value)
{
    if ($value) {
        $this->attributes['password'] = Hash::make($value);
    }
}

// Format phone
public function setPhoneAttribute($value)
{
    $this->attributes['phone'] = preg_replace('/[^0-9+]/', '', $value);
}
```

---

## Methods

### Address Management

```php
// Get default shipping address
public function defaultShippingAddress(): ?Address
{
    return $this->addresses()
        ->where('type', 'shipping')
        ->where('is_default', true)
        ->first();
}

// Get default billing address
public function defaultBillingAddress(): ?Address
{
    return $this->addresses()
        ->where('type', 'billing')
        ->where('is_default', true)
        ->first()
        ?? $this->defaultShippingAddress();
}

// Set default address
public function setDefaultAddress(Address $address): void
{
    // Remove default from other addresses of same type
    $this->addresses()
        ->where('type', $address->type)
        ->where('id', '!=', $address->id)
        ->update(['is_default' => false]);

    $address->update(['is_default' => true]);
}
```

### Order Management

```php
// Get active cart or create new one
public function activeCart(): Cart
{
    return $this->carts()->firstOrCreate([
        'status' => 'active',
        'site_id' => currentSite()->id,
        'channel_id' => currentChannel()->id,
    ]);
}

// Calculate lifetime value
public function calculateLifetimeValue(): float
{
    return $this->orders()
        ->where('status', 'completed')
        ->sum('total');
}

// Update metrics
public function updateMetrics(): void
{
    $this->update([
        'lifetime_value' => $this->calculateLifetimeValue(),
        'orders_count' => $this->orders()->count(),
        'last_order_at' => $this->orders()->latest()->first()?->created_at,
    ]);
}

// Check if first time buyer
public function isFirstTimeBuyer(): bool
{
    return $this->orders_count === 0;
}

// Check if returning customer
public function isReturningCustomer(): bool
{
    return $this->orders_count > 1;
}
```

### Fidelity System

```php
// Get or create fidelity card
public function getOrCreateFidelityCard(): FidelityCard
{
    return $this->fidelityCard ?? $this->fidelityCard()->create([
        'card_number' => $this->generateFidelityCardNumber(),
        'is_active' => true,
    ]);
}

// Get fidelity points balance
public function getFidelityPoints(): int
{
    return $this->fidelityCard?->points_balance ?? 0;
}

// Add fidelity points
public function addFidelityPoints(int $points, string $reason, ?int $orderId = null): void
{
    $card = $this->getOrCreateFidelityCard();
    $card->addPoints($points, $reason, $orderId);
}

// Redeem fidelity points
public function redeemFidelityPoints(int $points, string $reason, ?int $orderId = null): void
{
    $card = $this->fidelityCard;

    if (!$card) {
        throw new \Exception('Customer does not have a fidelity card');
    }

    if (!$card->canRedeemPoints($points)) {
        throw new \Exception('Insufficient points');
    }

    $card->redeemPoints($points, $reason, $orderId);
}

// Check if can redeem points
public function canRedeemPoints(int $points): bool
{
    return $this->getFidelityPoints() >= $points;
}
```

### Marketing & Preferences

```php
// Subscribe to marketing
public function subscribeToMarketing(): void
{
    $this->update(['accepts_marketing' => true]);

    event(new CustomerSubscribedToMarketing($this));
}

// Unsubscribe from marketing
public function unsubscribeFromMarketing(): void
{
    $this->update(['accepts_marketing' => false]);

    event(new CustomerUnsubscribedFromMarketing($this));
}

// Set preferences
public function setPreferences(array $preferences): void
{
    $data = $this->data ?? [];
    $data['preferences'] = array_merge($data['preferences'] ?? [], $preferences);
    $this->update(['data' => $data]);
}

// Get preference
public function getPreference(string $key, $default = null)
{
    return data_get($this->data, "preferences.{$key}", $default);
}
```

### Status Management

```php
// Block customer
public function block(string $reason = null): void
{
    $this->update([
        'status' => 'blocked',
        'notes' => $reason ? "Blocked: {$reason}" : $this->notes,
    ]);

    event(new CustomerBlocked($this));
}

// Unblock customer
public function unblock(): void
{
    $this->update(['status' => 'active']);

    event(new CustomerUnblocked($this));
}

// Deactivate customer
public function deactivate(): void
{
    $this->update(['status' => 'inactive']);
}

// Activate customer
public function activate(): void
{
    $this->update(['status' => 'active']);
}

// Check if active
public function isActive(): bool
{
    return $this->status === 'active';
}

// Check if blocked
public function isBlocked(): bool
{
    return $this->status === 'blocked';
}
```

### Analytics

```php
// Get customer statistics
public function getStatistics(): array
{
    return [
        'orders_count' => $this->orders_count,
        'lifetime_value' => $this->lifetime_value,
        'average_order_value' => $this->orders_count > 0
            ? $this->lifetime_value / $this->orders_count
            : 0,
        'fidelity_points' => $this->getFidelityPoints(),
        'last_order_date' => $this->last_order_at,
        'customer_since' => $this->created_at,
        'tier' => $this->tier,
    ];
}

// Get purchase frequency
public function getPurchaseFrequency(): float
{
    if ($this->orders_count <= 1) {
        return 0;
    }

    $daysSinceFirst = $this->created_at->diffInDays(now());
    return $daysSinceFirst > 0 ? $this->orders_count / $daysSinceFirst : 0;
}
```

---

## Events

### Model Events

```php
use Shopper\Events\CustomerCreated;
use Shopper\Events\CustomerUpdated;
use Shopper\Events\CustomerBlocked;

Event::listen(CustomerCreated::class, function ($event) {
    $customer = $event->customer;

    // Send welcome email
    // Create fidelity card
    // Add to mailing list
});

Event::listen(CustomerBlocked::class, function ($event) {
    $customer = $event->customer;

    // Cancel pending orders
    // Send notification
    // Log action
});
```

### Eloquent Events

```php
protected static function booted()
{
    static::created(function ($customer) {
        // Create fidelity card if enabled
        if (config('shopper.fidelity.enabled')) {
            $customer->getOrCreateFidelityCard();
        }

        // Create active cart
        $customer->activeCart();
    });

    static::updating(function ($customer) {
        // Update metrics if order completed
        if ($customer->isDirty('orders_count')) {
            $customer->updateMetrics();
        }
    });

    static::deleting(function ($customer) {
        // Soft delete related data
        $customer->addresses()->delete();
        $customer->carts()->delete();
        $customer->wishlistItems()->delete();
    });
}
```

---

## API Endpoints

### REST API

```http
# List customers
GET /api/customers

# Get single customer
GET /api/customers/{id}

# Create customer
POST /api/customers

# Update customer
PUT /api/customers/{id}

# Delete customer
DELETE /api/customers/{id}

# Get customer orders
GET /api/customers/{id}/orders

# Get customer addresses
GET /api/customers/{id}/addresses

# Get customer statistics
GET /api/customers/{id}/statistics

# Subscribe to marketing
POST /api/customers/{id}/subscribe

# Block customer
POST /api/customers/{id}/block
```

### GraphQL API

```graphql
# Query customer
query {
  customer(id: 1) {
    id
    email
    full_name
    display_name
    is_verified
    is_b2b
    tier
    lifetime_value
    orders_count
    fidelity_points
    group {
      id
      name
    }
    addresses {
      id
      type
      address_line_1
      city
      country
      is_default
    }
    orders(first: 10) {
      edges {
        node {
          id
          number
          total
          status
        }
      }
    }
  }
}

# Create customer
mutation {
  createCustomer(input: {
    email: "jane@example.com"
    first_name: "Jane"
    last_name: "Smith"
    phone: "+39 123 456 7890"
    accepts_marketing: true
  }) {
    customer {
      id
      email
      full_name
    }
  }
}
```

---

## Examples

### Creating Customers

```php
// B2C Customer
$customer = Customer::create([
    'email' => 'john@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'phone' => '+39 123 456 7890',
    'date_of_birth' => '1990-05-15',
    'accepts_marketing' => true,
    'status' => 'active',
]);

// B2B Customer
$b2bCustomer = Customer::create([
    'email' => 'purchasing@acme.com',
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'company_name' => 'ACME Corporation',
    'vat_number' => 'IT12345678901',
    'phone' => '+39 098 765 4321',
    'customer_group_id' => CustomerGroup::where('name', 'Wholesale')->first()->id,
    'accepts_marketing' => true,
    'status' => 'active',
]);

// Add addresses
$customer->addresses()->create([
    'type' => 'shipping',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'address_line_1' => 'Via Roma 123',
    'city' => 'Milan',
    'postal_code' => '20121',
    'country' => 'IT',
    'phone' => '+39 123 456 7890',
    'is_default' => true,
]);
```

### Customer Segmentation

```php
// Get VIP customers
$vipCustomers = Customer::vip(10000)->get();

// Get customers with no orders
$noOrders = Customer::doesntHave('orders')
    ->where('created_at', '<', now()->subMonths(3))
    ->get();

// Get customers by tier
$goldCustomers = Customer::active()
    ->whereBetween('lifetime_value', [5000, 9999.99])
    ->get();

// Get customers who haven't ordered recently
$inactive = Customer::active()
    ->where('last_order_at', '<', now()->subMonths(6))
    ->get();
```

---

## Related Documentation

- [CustomerGroup Model](/docs/model-customer-group)
- [Address Model](/docs/model-address)
- [Order Model](/docs/model-order)
- [FidelityCard Model](/docs/model-fidelity-card)
- [Fidelity System](/docs/fidelity-system)
- [Customer Groups](/docs/customer-groups)
- [REST API - Customers](/docs/api-customers)
- [GraphQL API - Customers](/docs/graphql-customers)
