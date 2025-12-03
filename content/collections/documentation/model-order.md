---
id: model-order
blueprint: documentation
title: 'Model: Order'
updated_by: system
updated_at: 1738675127
---
# Model: Order

The Order model represents a completed purchase transaction in your store. Orders contain items, customer information, shipping details, and payment records.

[TOC]

## Overview

An **Order** is created when a customer completes checkout. It contains all the information needed to fulfill and track a purchase:

```php
Order {
    number: "ORD-2025-00001"
    customer_id: 123
    site_id: 1
    channel_id: 2
    status: "completed"
    payment_status: "paid"
    fulfillment_status: "fulfilled"
    subtotal: 99.99
    tax: 22.00
    shipping: 5.99
    discount: -10.00
    total: 117.98
    currency: "EUR"
}
```

**Order Lifecycle**:
1. **Pending** - Order created, awaiting payment
2. **Processing** - Payment received, preparing to ship
3. **Shipped** - Order dispatched
4. **Completed** - Order delivered
5. **Cancelled** - Order cancelled
6. **Refunded** - Payment refunded

---

## Database Schema

### `orders` Table

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();

    // Order Identity
    $table->string('number')->unique(); // ORD-2025-00001
    $table->string('token')->unique(); // For guest checkout tracking

    // Relations
    $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('site_id')->constrained()->cascadeOnDelete();
    $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
    $table->foreignId('cart_id')->nullable()->constrained()->nullOnDelete();

    // Customer Information (denormalized for records)
    $table->string('customer_email');
    $table->string('customer_first_name')->nullable();
    $table->string('customer_last_name')->nullable();
    $table->string('customer_phone')->nullable();

    // Order Status
    $table->string('status')->default('pending');
    // pending, processing, shipped, completed, cancelled, refunded

    $table->string('payment_status')->default('pending');
    // pending, authorized, paid, partially_refunded, refunded, failed

    $table->string('fulfillment_status')->nullable();
    // unfulfilled, partial, fulfilled

    // Financial
    $table->decimal('subtotal', 15, 2)->default(0); // Items total
    $table->decimal('tax', 15, 2)->default(0); // Tax amount
    $table->decimal('shipping', 15, 2)->default(0); // Shipping cost
    $table->decimal('discount', 15, 2)->default(0); // Discount amount
    $table->decimal('total', 15, 2)->default(0); // Grand total
    $table->string('currency', 3); // EUR, USD, GBP

    // Payment
    $table->string('payment_method')->nullable(); // stripe, paypal, bank_transfer
    $table->string('payment_gateway')->nullable(); // Payment processor
    $table->string('payment_transaction_id')->nullable();
    $table->timestamp('paid_at')->nullable();

    // Shipping
    $table->string('shipping_method')->nullable();
    $table->string('tracking_number')->nullable();
    $table->string('tracking_url')->nullable();
    $table->timestamp('shipped_at')->nullable();
    $table->timestamp('delivered_at')->nullable();

    // Coupon/Discount
    $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
    $table->string('coupon_code')->nullable();

    // Fidelity Points
    $table->integer('fidelity_points_earned')->default(0);
    $table->integer('fidelity_points_used')->default(0);

    // Additional Information
    $table->text('customer_note')->nullable();
    $table->text('internal_note')->nullable();
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();

    // Dates
    $table->timestamp('completed_at')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    $table->timestamp('refunded_at')->nullable();

    // Custom Fields (JSONB)
    $table->json('data')->nullable();

    // Timestamps
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('number');
    $table->index('customer_id');
    $table->index('customer_email');
    $table->index('site_id');
    $table->index('channel_id');
    $table->index('status');
    $table->index('payment_status');
    $table->index('created_at');
    $table->index(['site_id', 'status', 'created_at']);
});
```

---

## Properties

### Core Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | bigint | Primary key |
| `number` | string | Order number (unique) |
| `token` | string | Guest tracking token |
| `customer_id` | foreignId | Customer (nullable for guest) |
| `site_id` | foreignId | Site |
| `channel_id` | foreignId | Sales channel |

### Customer Info (Denormalized)

| Property | Type | Description |
|----------|------|-------------|
| `customer_email` | string | Customer email |
| `customer_first_name` | string | First name |
| `customer_last_name` | string | Last name |
| `customer_phone` | string | Phone number |

### Status

| Property | Type | Description |
|----------|------|-------------|
| `status` | string | Order status |
| `payment_status` | string | Payment status |
| `fulfillment_status` | string | Fulfillment status |

### Financial

| Property | Type | Description |
|----------|------|-------------|
| `subtotal` | decimal(15,2) | Items subtotal |
| `tax` | decimal(15,2) | Tax amount |
| `shipping` | decimal(15,2) | Shipping cost |
| `discount` | decimal(15,2) | Discount amount |
| `total` | decimal(15,2) | Grand total |
| `currency` | string | Currency code |

### Payment

| Property | Type | Description |
|----------|------|-------------|
| `payment_method` | string | Payment method |
| `payment_gateway` | string | Gateway used |
| `payment_transaction_id` | string | Transaction ID |
| `paid_at` | timestamp | Payment date |

### Shipping

| Property | Type | Description |
|----------|------|-------------|
| `shipping_method` | string | Shipping method |
| `tracking_number` | string | Tracking number |
| `tracking_url` | string | Tracking URL |
| `shipped_at` | timestamp | Ship date |
| `delivered_at` | timestamp | Delivery date |

### Relationships

| Relation | Type | Description |
|----------|------|-------------|
| `customer` | belongsTo | Customer |
| `site` | belongsTo | Site |
| `channel` | belongsTo | Channel |
| `items` | hasMany | OrderItem |
| `addresses` | morphMany | OrderAddress |
| `shippingAddress` | morphOne | Shipping address |
| `billingAddress` | morphOne | Billing address |
| `payments` | hasMany | Payment |
| `refunds` | hasMany | Refund |
| `shipments` | hasMany | Shipment |
| `statusHistory` | hasMany | OrderStatusHistory |

---

## Eloquent Model

### Basic Usage

```php
use Shopper\Models\Order;

// Create order from cart
$order = Order::createFromCart($cart);

// Find by ID
$order = Order::find(1);

// Find by number
$order = Order::where('number', 'ORD-2025-00001')->first();

// Find by token (guest orders)
$order = Order::where('token', $token)->first();

// Update status
$order->update(['status' => 'processing']);

// Soft delete
$order->delete();
```

---

## Relationships

### Customer

```php
// Get customer
$customer = $order->customer;

// Guest order check
if (!$order->customer_id) {
    // Guest checkout
}

// Customer name from order
$customerName = $order->customer_full_name;
```

### Site & Channel

```php
// Get site
$site = $order->site;

// Get channel
$channel = $order->channel;

// Orders by site
$siteOrders = Site::find(1)->orders;

// Orders by channel
$channelOrders = Channel::find(2)->orders;
```

### Order Items

```php
// Get all items
$items = $order->items;

// Items count
$itemsCount = $order->items()->count();

// Total items quantity
$totalQty = $order->items()->sum('quantity');

// Add item
$order->items()->create([
    'product_variant_id' => $variantId,
    'sku' => $variant->sku,
    'name' => $variant->title,
    'quantity' => 2,
    'price' => 19.99,
    'total' => 39.98,
]);

// Remove item
$order->items()->where('id', $itemId)->delete();
```

### Addresses

```php
// Get shipping address
$shippingAddress = $order->shippingAddress;

// Get billing address
$billingAddress = $order->billingAddress;

// Create addresses
$order->addresses()->create([
    'type' => 'shipping',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'address_line_1' => 'Via Roma 123',
    'city' => 'Milan',
    'postal_code' => '20121',
    'country' => 'IT',
    'phone' => '+39 123 456 7890',
]);

$order->addresses()->create([
    'type' => 'billing',
    // ... billing address data
]);
```

### Payments

```php
// Get payments
$payments = $order->payments;

// Create payment record
$order->payments()->create([
    'amount' => $order->total,
    'currency' => $order->currency,
    'payment_method' => 'stripe',
    'status' => 'completed',
    'transaction_id' => 'txn_xxxxx',
    'paid_at' => now(),
]);

// Total paid
$totalPaid = $order->payments()
    ->where('status', 'completed')
    ->sum('amount');

// Check if fully paid
$isFullyPaid = $totalPaid >= $order->total;
```

### Refunds

```php
// Get refunds
$refunds = $order->refunds;

// Create refund
$refund = $order->refunds()->create([
    'amount' => 50.00,
    'reason' => 'Customer request',
    'status' => 'pending',
]);

// Total refunded
$totalRefunded = $order->refunds()
    ->where('status', 'completed')
    ->sum('amount');

// Check if fully refunded
$isFullyRefunded = $totalRefunded >= $order->total;
```

### Shipments

```php
// Get shipments
$shipments = $order->shipments;

// Create shipment
$shipment = $order->shipments()->create([
    'tracking_number' => 'TRK123456789',
    'carrier' => 'DHL',
    'status' => 'in_transit',
    'shipped_at' => now(),
]);

// Add items to shipment
$shipment->items()->attach($orderItemId, [
    'quantity' => 2,
]);
```

### Status History

```php
// Get status history
$history = $order->statusHistory()
    ->orderBy('created_at')
    ->get();

// Log status change
$order->statusHistory()->create([
    'from_status' => 'pending',
    'to_status' => 'processing',
    'comment' => 'Payment received',
    'user_id' => auth()->id(),
]);
```

---

## Scopes

### Query Scopes

```php
// By status
Order::status('completed')->get();

// Pending orders
Order::pending()->get();

// Completed orders
Order::completed()->get();

// Paid orders
Order::paid()->get();

// Unpaid orders
Order::unpaid()->get();

// Recent orders
Order::recent()->get();

// Today's orders
Order::today()->get();

// By customer
Order::forCustomer($customerId)->get();

// By site
Order::forSite($siteId)->get();

// By date range
Order::betweenDates($startDate, $endDate)->get();
```

### Scope Definitions

```php
public function scopeStatus($query, string $status)
{
    return $query->where('status', $status);
}

public function scopePending($query)
{
    return $query->where('status', 'pending');
}

public function scopeCompleted($query)
{
    return $query->where('status', 'completed');
}

public function scopePaid($query)
{
    return $query->where('payment_status', 'paid');
}

public function scopeUnpaid($query)
{
    return $query->whereIn('payment_status', ['pending', 'failed']);
}

public function scopeRecent($query, int $days = 30)
{
    return $query->where('created_at', '>=', now()->subDays($days));
}

public function scopeToday($query)
{
    return $query->whereDate('created_at', today());
}

public function scopeForCustomer($query, int $customerId)
{
    return $query->where('customer_id', $customerId);
}

public function scopeForSite($query, int $siteId)
{
    return $query->where('site_id', $siteId);
}

public function scopeBetweenDates($query, $startDate, $endDate)
{
    return $query->whereBetween('created_at', [$startDate, $endDate]);
}
```

---

## Accessors & Mutators

### Accessors

```php
// Get customer full name
public function getCustomerFullNameAttribute(): string
{
    return trim("{$this->customer_first_name} {$this->customer_last_name}");
}

// Check if paid
public function getIsPaidAttribute(): bool
{
    return $this->payment_status === 'paid';
}

// Check if fulfilled
public function getIsFulfilledAttribute(): bool
{
    return $this->fulfillment_status === 'fulfilled';
}

// Check if refundable
public function getIsRefundableAttribute(): bool
{
    return $this->payment_status === 'paid'
        && $this->status !== 'refunded'
        && $this->created_at > now()->subDays(30);
}

// Get formatted total
public function getFormattedTotalAttribute(): string
{
    return money($this->total, $this->currency);
}

// Get status badge color
public function getStatusColorAttribute(): string
{
    return match($this->status) {
        'pending' => 'yellow',
        'processing' => 'blue',
        'shipped' => 'purple',
        'completed' => 'green',
        'cancelled' => 'gray',
        'refunded' => 'red',
        default => 'gray',
    };
}

// Usage
echo $order->customer_full_name;
if ($order->is_paid) { ... }
echo $order->formatted_total;
```

### Mutators

```php
// Normalize email
public function setCustomerEmailAttribute($value)
{
    $this->attributes['customer_email'] = strtolower(trim($value));
}

// Calculate total when subtotal changes
public function setSubtotalAttribute($value)
{
    $this->attributes['subtotal'] = $value;
    $this->recalculateTotal();
}
```

---

## Methods

### Order Creation

```php
// Create from cart
public static function createFromCart(Cart $cart): self
{
    DB::transaction(function () use ($cart) {
        $order = static::create([
            'number' => static::generateOrderNumber(),
            'token' => Str::random(32),
            'customer_id' => $cart->customer_id,
            'customer_email' => $cart->customer->email ?? $cart->guest_email,
            'site_id' => $cart->site_id,
            'channel_id' => $cart->channel_id,
            'cart_id' => $cart->id,
            'subtotal' => $cart->subtotal,
            'tax' => $cart->tax,
            'shipping' => $cart->shipping,
            'discount' => $cart->discount,
            'total' => $cart->total,
            'currency' => $cart->currency,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        // Copy cart items to order items
        foreach ($cart->items as $cartItem) {
            $order->items()->create([
                'product_variant_id' => $cartItem->product_variant_id,
                'sku' => $cartItem->variant->sku,
                'name' => $cartItem->variant->title,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
                'total' => $cartItem->total,
            ]);
        }

        // Copy addresses
        if ($cart->shippingAddress) {
            $order->addresses()->create(
                $cart->shippingAddress->toArray() + ['type' => 'shipping']
            );
        }

        if ($cart->billingAddress) {
            $order->addresses()->create(
                $cart->billingAddress->toArray() + ['type' => 'billing']
            );
        }

        return $order;
    });
}

// Generate order number
public static function generateOrderNumber(): string
{
    $prefix = config('shopper.order_number_prefix', 'ORD');
    $year = date('Y');
    $lastOrder = static::whereYear('created_at', $year)
        ->orderByDesc('id')
        ->first();

    $sequence = $lastOrder ? (int) substr($lastOrder->number, -5) + 1 : 1;

    return sprintf('%s-%s-%05d', $prefix, $year, $sequence);
}
```

### Financial Calculations

```php
// Recalculate totals
public function recalculateTotal(): void
{
    $this->total = $this->subtotal + $this->tax + $this->shipping - abs($this->discount);
    $this->save();
}

// Calculate tax
public function calculateTax(): float
{
    $taxRate = TaxRate::forAddress($this->shippingAddress)->first();

    if (!$taxRate) {
        return 0;
    }

    $taxableAmount = $this->subtotal;
    return round($taxableAmount * ($taxRate->rate / 100), 2);
}

// Apply discount
public function applyDiscount(float $amount, ?string $reason = null): void
{
    $this->discount = $amount;
    $this->recalculateTotal();

    if ($reason) {
        $this->data = array_merge($this->data ?? [], [
            'discount_reason' => $reason,
        ]);
        $this->save();
    }
}
```

### Status Management

```php
// Mark as paid
public function markAsPaid(string $transactionId = null): void
{
    $this->update([
        'payment_status' => 'paid',
        'paid_at' => now(),
        'payment_transaction_id' => $transactionId,
        'status' => 'processing',
    ]);

    $this->logStatusChange('pending', 'processing', 'Payment received');

    event(new OrderPaid($this));
}

// Mark as shipped
public function markAsShipped(string $trackingNumber = null): void
{
    $this->update([
        'status' => 'shipped',
        'fulfillment_status' => 'fulfilled',
        'shipped_at' => now(),
        'tracking_number' => $trackingNumber,
    ]);

    $this->logStatusChange('processing', 'shipped', 'Order shipped');

    event(new OrderShipped($this));
}

// Mark as completed
public function markAsCompleted(): void
{
    $this->update([
        'status' => 'completed',
        'completed_at' => now(),
        'delivered_at' => now(),
    ]);

    $this->logStatusChange('shipped', 'completed', 'Order completed');

    // Award fidelity points
    if ($this->customer_id && $this->fidelity_points_earned > 0) {
        $this->customer->addFidelityPoints(
            $this->fidelity_points_earned,
            "Points from order {$this->number}",
            $this->id
        );
    }

    event(new OrderCompleted($this));
}

// Cancel order
public function cancel(string $reason = null): void
{
    if (!$this->canBeCancelled()) {
        throw new \Exception('Order cannot be cancelled');
    }

    $this->update([
        'status' => 'cancelled',
        'cancelled_at' => now(),
        'internal_note' => $reason,
    ]);

    // Release inventory
    foreach ($this->items as $item) {
        $item->variant->releaseInventory($item->quantity, $this->id);
    }

    // Refund payment if already paid
    if ($this->is_paid) {
        $this->refund($this->total, 'Order cancelled');
    }

    $this->logStatusChange($this->getOriginal('status'), 'cancelled', $reason);

    event(new OrderCancelled($this));
}

// Check if can be cancelled
public function canBeCancelled(): bool
{
    return in_array($this->status, ['pending', 'processing'])
        && $this->fulfillment_status !== 'fulfilled';
}
```

### Refund Management

```php
// Refund order
public function refund(float $amount, string $reason = null): Refund
{
    if (!$this->is_paid) {
        throw new \Exception('Cannot refund unpaid order');
    }

    if ($amount > $this->total) {
        throw new \Exception('Refund amount exceeds order total');
    }

    $refund = $this->refunds()->create([
        'amount' => $amount,
        'reason' => $reason,
        'status' => 'pending',
    ]);

    // Process refund with payment gateway
    $this->processRefundWithGateway($refund);

    // Update order status
    $totalRefunded = $this->refunds()->sum('amount');

    if ($totalRefunded >= $this->total) {
        $this->update([
            'payment_status' => 'refunded',
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);
    } else {
        $this->update(['payment_status' => 'partially_refunded']);
    }

    event(new OrderRefunded($this, $refund));

    return $refund;
}

// Process refund with gateway
protected function processRefundWithGateway(Refund $refund): void
{
    // Implementation depends on payment gateway
    // Example: Stripe
    if ($this->payment_gateway === 'stripe') {
        // Stripe::refund($this->payment_transaction_id, $refund->amount);
    }

    $refund->update([
        'status' => 'completed',
        'processed_at' => now(),
    ]);
}
```

### Inventory Management

```php
// Reserve inventory
public function reserveInventory(): void
{
    foreach ($this->items as $item) {
        $item->variant->reserveInventory($item->quantity, $this->id);
    }
}

// Release inventory
public function releaseInventory(): void
{
    foreach ($this->items as $item) {
        $item->variant->releaseInventory($item->quantity, $this->id);
    }
}
```

### Status History

```php
// Log status change
protected function logStatusChange(
    string $fromStatus,
    string $toStatus,
    ?string $comment = null
): void {
    $this->statusHistory()->create([
        'from_status' => $fromStatus,
        'to_status' => $toStatus,
        'comment' => $comment,
        'user_id' => auth()->id(),
    ]);
}
```

---

## Events

### Model Events

```php
use Shopper\Events\OrderCreated;
use Shopper\Events\OrderPaid;
use Shopper\Events\OrderShipped;
use Shopper\Events\OrderCompleted;
use Shopper\Events\OrderCancelled;
use Shopper\Events\OrderRefunded;

Event::listen(OrderCreated::class, function ($event) {
    $order = $event->order;

    // Send confirmation email
    // Reserve inventory
    // Create invoice
});

Event::listen(OrderPaid::class, function ($event) {
    $order = $event->order;

    // Update customer metrics
    // Process fulfillment
    // Notify warehouse
});

Event::listen(OrderCompleted::class, function ($event) {
    $order = $event->order;

    // Award fidelity points
    // Request review
    // Update analytics
});
```

### Eloquent Events

```php
protected static function booted()
{
    static::creating(function ($order) {
        // Generate order number if not set
        if (empty($order->number)) {
            $order->number = static::generateOrderNumber();
        }

        // Generate token for guest tracking
        if (empty($order->token)) {
            $order->token = Str::random(32);
        }
    });

    static::created(function ($order) {
        // Reserve inventory
        $order->reserveInventory();

        // Calculate fidelity points
        if ($order->customer_id) {
            $order->calculateFidelityPoints();
        }
    });

    static::updating(function ($order) {
        // Recalculate total if financial fields changed
        if ($order->isDirty(['subtotal', 'tax', 'shipping', 'discount'])) {
            $order->recalculateTotal();
        }
    });

    static::updated(function ($order) {
        // Update customer metrics
        if ($order->customer_id && $order->status === 'completed') {
            $order->customer->updateMetrics();
        }
    });
}
```

---

## API Endpoints

### REST API

```http
# List orders
GET /api/orders

# Get single order
GET /api/orders/{id}

# Get order by number
GET /api/orders/number/{number}

# Create order
POST /api/orders

# Update order
PUT /api/orders/{id}

# Cancel order
POST /api/orders/{id}/cancel

# Mark as paid
POST /api/orders/{id}/mark-paid

# Mark as shipped
POST /api/orders/{id}/mark-shipped

# Refund order
POST /api/orders/{id}/refund

# Get order items
GET /api/orders/{id}/items

# Get order status history
GET /api/orders/{id}/history
```

### GraphQL API

```graphql
# Query order
query {
  order(id: 1) {
    id
    number
    status
    payment_status
    fulfillment_status
    subtotal
    tax
    shipping
    discount
    total
    currency
    customer {
      id
      email
      full_name
    }
    items {
      id
      name
      sku
      quantity
      price
      total
    }
    shippingAddress {
      address_line_1
      city
      country
    }
    statusHistory {
      from_status
      to_status
      comment
      created_at
    }
  }
}

# Create order
mutation {
  createOrder(input: {
    cart_id: 123
    payment_method: "stripe"
  }) {
    order {
      id
      number
      total
    }
  }
}

# Mark as paid
mutation {
  markOrderAsPaid(
    id: 1
    transaction_id: "txn_xxxxx"
  ) {
    order {
      id
      payment_status
      paid_at
    }
  }
}
```

---

## Examples

### Creating Orders

```php
// Create from cart (checkout)
$order = Order::createFromCart($cart);

// Manual order creation
$order = Order::create([
    'number' => Order::generateOrderNumber(),
    'customer_id' => $customer->id,
    'customer_email' => $customer->email,
    'customer_first_name' => $customer->first_name,
    'customer_last_name' => $customer->last_name,
    'site_id' => currentSite()->id,
    'channel_id' => currentChannel()->id,
    'subtotal' => 99.99,
    'tax' => 22.00,
    'shipping' => 5.99,
    'total' => 127.98,
    'currency' => 'EUR',
]);

// Add items
$order->items()->create([
    'product_variant_id' => $variant->id,
    'sku' => $variant->sku,
    'name' => $variant->title,
    'quantity' => 2,
    'price' => 49.99,
    'total' => 99.98,
]);
```

### Order Fulfillment Workflow

```php
// 1. Order created (pending)
$order = Order::createFromCart($cart);

// 2. Payment received
$order->markAsPaid('txn_stripe_xxxxx');

// 3. Prepare shipment
$shipment = $order->shipments()->create([
    'carrier' => 'DHL',
    'service' => 'Express',
]);

foreach ($order->items as $item) {
    $shipment->items()->attach($item->id, [
        'quantity' => $item->quantity,
    ]);
}

// 4. Ship order
$order->markAsShipped('TRK123456789');

// 5. Delivered
$order->markAsCompleted();
```

### Order Cancellation

```php
// Cancel with reason
try {
    $order->cancel('Customer requested cancellation');
} catch (\Exception $e) {
    // Handle error
}

// Automatic refund if paid
if ($order->is_paid) {
    // Refund will be processed automatically
}
```

### Refund Processing

```php
// Full refund
$refund = $order->refund(
    $order->total,
    'Product defective'
);

// Partial refund
$refund = $order->refund(
    50.00,
    'Partial return - 1 item'
);

// Check refund status
if ($order->payment_status === 'refunded') {
    // Fully refunded
}
```

---

## Performance Tips

### Eager Loading

```php
// Load relations
$orders = Order::with([
    'customer',
    'items.variant.product',
    'shippingAddress',
    'billingAddress',
    'payments',
])->get();
```

### Caching

```php
// Cache order data
$order = Cache::remember("order.{$id}", 3600, function () use ($id) {
    return Order::with(['items', 'customer', 'addresses'])->find($id);
});
```

### Indexing

```php
// Ensure proper indexes
$table->index(['site_id', 'status', 'created_at']);
$table->index(['customer_id', 'status']);
$table->index('payment_status');
```

---

## Related Documentation

- [OrderItem Model](/docs/model-order-item)
- [Cart Model](/docs/model-cart)
- [Customer Model](/docs/model-customer)
- [Payment Processing](/docs/payment-processing)
- [Shipping System](/docs/shipping-system)
- [Order Fulfillment](/docs/order-fulfillment)
- [REST API - Orders](/docs/api-orders)
- [GraphQL API - Orders](/docs/graphql-orders)
