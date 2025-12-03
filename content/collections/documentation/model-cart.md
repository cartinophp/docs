---
id: model-cart
blueprint: documentation
title: 'Model: Cart'
updated_by: system
updated_at: 1738675127
---
# Model: Cart

The Cart model represents a customer's shopping cart. Carts contain items, calculate totals, and can be converted to orders at checkout.

[TOC]

## Overview

A **Cart** is a temporary container for products before checkout. It handles pricing, discounts, shipping, and tax calculations:

```php
Cart {
    customer_id: 123
    site_id: 1
    channel_id: 2
    status: "active"
    subtotal: 99.99
    tax: 22.00
    shipping: 5.99
    discount: -10.00
    total: 117.98
    currency: "EUR"
}
```

**Cart Lifecycle**:
1. **Active** - Current shopping cart
2. **Abandoned** - Cart not updated in X days
3. **Completed** - Converted to order
4. **Expired** - Deleted after retention period

---

## Database Schema

```php
Schema::create('carts', function (Blueprint $table) {
    $table->id();

    // Ownership
    $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
    $table->string('session_id')->nullable()->index(); // For guest carts

    // Context
    $table->foreignId('site_id')->constrained()->cascadeOnDelete();
    $table->foreignId('channel_id')->constrained()->cascadeOnDelete();

    // Status
    $table->string('status')->default('active'); // active, abandoned, completed, expired

    // Financial
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->decimal('tax', 15, 2)->default(0);
    $table->decimal('shipping', 15, 2)->default(0);
    $table->decimal('discount', 15, 2)->default(0);
    $table->decimal('total', 15, 2)->default(0);
    $table->string('currency', 3);

    // Shipping
    $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
    $table->string('shipping_address_id')->nullable();
    $table->string('billing_address_id')->nullable();

    // Coupon
    $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
    $table->string('coupon_code')->nullable();

    // Fidelity
    $table->integer('fidelity_points_used')->default(0);

    // Guest Information
    $table->string('guest_email')->nullable();
    $table->text('notes')->nullable();

    // Metadata
    $table->json('data')->nullable();
    $table->timestamp('converted_to_order_at')->nullable();
    $table->timestamp('abandoned_at')->nullable();

    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('customer_id');
    $table->index('session_id');
    $table->index(['site_id', 'channel_id']);
    $table->index('status');
    $table->index('updated_at');
});
```

---

## Eloquent Model

```php
use Shopper\Models\Cart;

// Get or create active cart
$cart = Cart::getOrCreate($customer, $site, $channel);

// Add item
$cart->addItem($variant, 2);

// Update quantity
$cart->updateItem($cartItemId, 5);

// Remove item
$cart->removeItem($cartItemId);

// Apply coupon
$cart->applyCoupon('SUMMER20');

// Calculate totals
$cart->calculate();

// Convert to order
$order = $cart->convertToOrder();
```

---

## Relationships

```php
// Customer
$customer = $cart->customer;

// Items
$items = $cart->items;

// Addresses
$shippingAddress = $cart->shippingAddress;
$billingAddress = $cart->billingAddress;

// Coupon
$coupon = $cart->coupon;
```

---

## Methods

```php
// Add item to cart
public function addItem(ProductVariant $variant, int $quantity = 1): CartItem
{
    // Check stock availability
    if (!$variant->isAvailable($quantity)) {
        throw new InsufficientStockException();
    }

    // Get price for current context
    $price = $variant->getPriceFor(
        siteId: $this->site_id,
        channelId: $this->channel_id,
        currency: $this->currency,
        quantity: $quantity,
        customerGroupId: $this->customer?->customer_group_id
    );

    // Check if item already in cart
    $existingItem = $this->items()
        ->where('product_variant_id', $variant->id)
        ->first();

    if ($existingItem) {
        return $existingItem->increment('quantity', $quantity);
    }

    // Create new cart item
    $item = $this->items()->create([
        'product_variant_id' => $variant->id,
        'quantity' => $quantity,
        'price' => $price->price,
        'compare_at_price' => $price->compare_at_price,
    ]);

    $this->calculate();

    return $item;
}

// Calculate totals
public function calculate(): void
{
    $this->subtotal = $this->items()->sum('total');
    $this->tax = $this->calculateTax();
    $this->shipping = $this->calculateShipping();
    $this->discount = $this->calculateDiscount();
    $this->total = $this->subtotal + $this->tax + $this->shipping - abs($this->discount);
    $this->save();
}

// Apply coupon
public function applyCoupon(string $code): bool
{
    $coupon = Coupon::where('code', $code)
        ->active()
        ->first();

    if (!$coupon || !$coupon->canBeUsed($this)) {
        return false;
    }

    $this->update([
        'coupon_id' => $coupon->id,
        'coupon_code' => $code,
    ]);

    $this->calculate();

    return true;
}

// Convert to order
public function convertToOrder(): Order
{
    if ($this->items->isEmpty()) {
        throw new \Exception('Cannot create order from empty cart');
    }

    $order = Order::createFromCart($this);

    $this->update([
        'status' => 'completed',
        'converted_to_order_at' => now(),
    ]);

    return $order;
}

// Check if abandoned
public function isAbandoned(): bool
{
    $threshold = config('shopper.cart.abandoned_after_hours', 24);
    return $this->updated_at < now()->subHours($threshold);
}

// Merge carts (guest to customer)
public function merge(Cart $guestCart): void
{
    foreach ($guestCart->items as $item) {
        $this->addItem($item->variant, $item->quantity);
    }

    $guestCart->delete();
}
```

---

## Related Documentation

- [CartItem Model](/docs/model-cart-item)
- [Order Model](/docs/model-order)
- [ProductVariant Model](/docs/model-product-variant)
- [Coupon Model](/docs/model-coupon)
- [Checkout Process](/docs/checkout-process)
- [REST API - Cart](/docs/api-cart)
- [GraphQL API - Cart](/docs/graphql-cart)
