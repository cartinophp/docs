---
id: 4d5e6f7a-8b9c-0d1e-2f3a-4b5c6d7e8f9a
blueprint: v1
title: 'Model: Order'
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264233
---
# 📦 Order Model

The `Order` model represents a customer purchase in Cartino. It tracks the complete order lifecycle from creation to fulfillment.

[TOC]

## 📋 Overview

**Namespace**: `App\Models\Order`  
**Table**: `orders`  
**Traits**: `SoftDeletes`, `HasFactory`

Orders are immutable once created - changes create new transactions or adjustments rather than modifying the original order.

---

## 🗄️ Schema

```php
orders {
  id                  bigint
  order_number        string (unique)
  site_id             bigint FK → sites
  channel_id          bigint FK → channels
  customer_id         bigint FK → customers (nullable)
  
  // Contact Info
  email               string
  phone               string (nullable)
  
  // Financial
  currency            string (ISO 4217)
  subtotal            decimal
  tax_total           decimal
  shipping_total      decimal
  discount_total      decimal
  total               decimal
  
  // Payment
  payment_status      enum (pending, paid, partially_paid, refunded, partially_refunded, failed)
  payment_method      string (nullable)
  payment_gateway_id  bigint FK → payment_gateways (nullable)
  
  // Fulfillment
  fulfillment_status  enum (unfulfilled, partially_fulfilled, fulfilled, returned)
  shipping_method_id  bigint FK → shipping_methods (nullable)
  
  // Addresses
  billing_address     jsonb
  shipping_address    jsonb
  
  // Status & Tracking
  status              enum (draft, pending, confirmed, processing, completed, cancelled)
  cancelled_at        timestamp (nullable)
  cancelled_reason    text (nullable)
  
  // Notes
  customer_note       text (nullable)
  internal_note       text (nullable)
  
  // Timestamps
  confirmed_at        timestamp (nullable)
  processed_at        timestamp (nullable)
  completed_at        timestamp (nullable)
  
  // Custom Fields
  data                jsonb (nullable)
  
  timestamps, soft_deletes
}
```

---

## 🔧 Properties

### Fillable

```php
protected $fillable = [
    'order_number',
    'site_id',
    'channel_id',
    'customer_id',
    'email',
    'phone',
    'currency',
    'subtotal',
    'tax_total',
    'shipping_total',
    'discount_total',
    'total',
    'payment_status',
    'payment_method',
    'payment_gateway_id',
    'fulfillment_status',
    'shipping_method_id',
    'billing_address',
    'shipping_address',
    'status',
    'customer_note',
    'internal_note',
    'data',
];
```

### Casts

```php
protected $casts = [
    'subtotal' => 'decimal:2',
    'tax_total' => 'decimal:2',
    'shipping_total' => 'decimal:2',
    'discount_total' => 'decimal:2',
    'total' => 'decimal:2',
    'billing_address' => 'array',
    'shipping_address' => 'array',
    'data' => 'array',
    'status' => OrderStatus::class,
    'payment_status' => PaymentStatus::class,
    'fulfillment_status' => FulfillmentStatus::class,
    'confirmed_at' => 'datetime',
    'processed_at' => 'datetime',
    'completed_at' => 'datetime',
    'cancelled_at' => 'datetime',
];
```

---

## 🔗 Relationships

### BelongsTo

```php
public function site()
{
    return $this->belongsTo(Site::class);
}

public function channel()
{
    return $this->belongsTo(Channel::class);
}

public function customer()
{
    return $this->belongsTo(Customer::class);
}

public function shippingMethod()
{
    return $this->belongsTo(ShippingMethod::class);
}

public function paymentGateway()
{
    return $this->belongsTo(PaymentGateway::class);
}
```

### HasMany

```php
public function lines()
{
    return $this->hasMany(OrderLine::class)->orderBy('position');
}

public function transactions()
{
    return $this->hasMany(Transaction::class);
}

public function discountApplications()
{
    return $this->hasMany(DiscountApplication::class);
}
```

---

## 🔍 Scopes

### Pending

```php
public function scopePending($query)
{
    return $query->where('status', OrderStatus::Pending);
}
```

### Confirmed

```php
public function scopeConfirmed($query)
{
    return $query->whereNotNull('confirmed_at')
                 ->where('status', '!=', OrderStatus::Cancelled);
}
```

### Paid

```php
public function scopePaid($query)
{
    return $query->whereIn('payment_status', [
        PaymentStatus::Paid,
        PaymentStatus::PartiallyPaid,
    ]);
}
```

### Recent

```php
public function scopeRecent($query, $days = 30)
{
    return $query->where('created_at', '>=', now()->subDays($days));
}
```

### ForCustomer

```php
public function scopeForCustomer($query, $customerId)
{
    return $query->where('customer_id', $customerId);
}
```

---

## 🎯 Accessors

### Is Paid Accessor

```php
public function getIsPaidAttribute(): bool
{
    return in_array($this->payment_status, [
        PaymentStatus::Paid,
        PaymentStatus::PartiallyPaid,
    ]);
}
```

### Is Fulfilled Accessor

```php
public function getIsFulfilledAttribute(): bool
{
    return $this->fulfillment_status === FulfillmentStatus::Fulfilled;
}
```

### Outstanding Balance Accessor

```php
public function getOutstandingBalanceAttribute(): float
{
    $paid = $this->transactions()
        ->where('status', 'success')
        ->sum('amount');
    
    return max(0, $this->total - $paid);
}
```

---

## ⚡ Methods

### Calculate Totals

```php
public function calculateTotals(): void
{
    $this->subtotal = $this->lines->sum('line_total');
    $this->tax_total = $this->lines->sum('tax_total');
    $this->discount_total = $this->discountApplications->sum('amount');
    
    $this->total = $this->subtotal 
                 + $this->tax_total 
                 + $this->shipping_total 
                 - $this->discount_total;
    
    $this->saveQuietly();
}
```

### Confirm Order

```php
public function confirm(): bool
{
    if ($this->status !== OrderStatus::Pending) {
        return false;
    }
    
    DB::transaction(function () {
        // Reserve inventory
        foreach ($this->lines as $line) {
            $line->variant->reserveStock($line->quantity, $this->id);
        }
        
        // Update status
        $this->update([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
        
        // Process loyalty points
        if ($this->customer && config('cartino.fidelity.enabled')) {
            app(FidelityService::class)->processOrderForPoints($this);
        }
        
        // Dispatch events
        event(new OrderConfirmed($this));
    });
    
    return true;
}
```

### Cancel Order

```php
public function cancel(string $reason = null): bool
{
    if ($this->status === OrderStatus::Cancelled) {
        return false;
    }
    
    DB::transaction(function () use ($reason) {
        // Release inventory reservations
        foreach ($this->lines as $line) {
            $line->variant->releaseStock($this->id);
        }
        
        // Refund if paid
        if ($this->is_paid) {
            $this->refund($this->total, 'Order cancelled');
        }
        
        // Update status
        $this->update([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_reason' => $reason,
        ]);
        
        event(new OrderCancelled($this));
    });
    
    return true;
}
```

### Process Payment

```php
public function processPayment(float $amount, array $paymentData = []): Transaction
{
    $transaction = $this->transactions()->create([
        'type' => 'payment',
        'amount' => $amount,
        'currency' => $this->currency,
        'payment_method' => $paymentData['method'] ?? $this->payment_method,
        'status' => 'pending',
        'data' => $paymentData,
    ]);
    
    // Process through payment gateway
    $result = app(PaymentService::class)->process($transaction);
    
    if ($result['success']) {
        $transaction->update(['status' => 'success']);
        $this->updatePaymentStatus();
    } else {
        $transaction->update([
            'status' => 'failed',
            'error_message' => $result['error'],
        ]);
    }
    
    return $transaction;
}
```

### Update Payment Status

```php
public function updatePaymentStatus(): void
{
    $paid = $this->transactions()
        ->where('type', 'payment')
        ->where('status', 'success')
        ->sum('amount');
    
    if ($paid >= $this->total) {
        $this->payment_status = PaymentStatus::Paid;
    } elseif ($paid > 0) {
        $this->payment_status = PaymentStatus::PartiallyPaid;
    } else {
        $this->payment_status = PaymentStatus::Pending;
    }
    
    $this->saveQuietly();
}
```

### Refund

```php
public function refund(float $amount, string $reason = null): Transaction
{
    $transaction = $this->transactions()->create([
        'type' => 'refund',
        'amount' => -abs($amount),
        'currency' => $this->currency,
        'status' => 'pending',
        'data' => ['reason' => $reason],
    ]);
    
    // Process refund through gateway
    $result = app(PaymentService::class)->refund($transaction);
    
    if ($result['success']) {
        $transaction->update(['status' => 'success']);
        
        // Return inventory
        foreach ($this->lines as $line) {
            $line->variant->adjustInventory(
                $line->quantity,
                "Refund for order #{$this->order_number}"
            );
        }
        
        $this->updatePaymentStatus();
    }
    
    return $transaction;
}
```

---

## 🎪 Events

```php
protected static function booted()
{
    static::creating(function ($order) {
        if (!$order->order_number) {
            $order->order_number = $order->generateOrderNumber();
        }
        
        if (!$order->currency) {
            $order->currency = currentSite()->default_currency;
        }
    });
    
    static::created(function ($order) {
        event(new OrderCreated($order));
    });
    
    static::updating(function ($order) {
        if ($order->isDirty('status')) {
            event(new OrderStatusChanged($order, $order->getOriginal('status')));
        }
    });
}

protected function generateOrderNumber(): string
{
    $prefix = currentSite()->handle;
    $date = now()->format('Ymd');
    $sequence = Order::whereDate('created_at', today())->count() + 1;
    
    return strtoupper("{$prefix}-{$date}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT));
}
```

---

## 💡 Usage Examples

### Creating an Order

```php
$order = Order::create([
    'site_id' => currentSite()->id,
    'channel_id' => currentChannel()->id,
    'customer_id' => $customer->id,
    'email' => $customer->email,
    'currency' => 'EUR',
    'billing_address' => $billingAddress,
    'shipping_address' => $shippingAddress,
    'status' => OrderStatus::Pending,
]);

// Add line items
foreach ($cartLines as $cartLine) {
    $order->lines()->create([
        'product_variant_id' => $cartLine->product_variant_id,
        'quantity' => $cartLine->quantity,
        'price' => $cartLine->price,
        'line_total' => $cartLine->quantity * $cartLine->price,
    ]);
}

$order->calculateTotals();
```

### Processing an Order

```php
// Confirm order
$order->confirm();

// Process payment
$transaction = $order->processPayment($order->total, [
    'method' => 'stripe',
    'token' => $paymentToken,
]);

if ($transaction->status === 'success') {
    $order->update(['status' => OrderStatus::Processing]);
}
```

### Querying Orders

```php
// Recent paid orders
$orders = Order::confirmed()
    ->paid()
    ->recent(7)
    ->with(['lines.variant', 'customer'])
    ->get();

// Customer orders
$customerOrders = Order::forCustomer($customerId)
    ->orderByDesc('created_at')
    ->paginate(20);

// Orders needing fulfillment
$toFulfill = Order::paid()
    ->where('fulfillment_status', FulfillmentStatus::Unfulfilled)
    ->get();
```

---

## 🌐 API Resource

```php
class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'fulfillment_status' => $this->fulfillment_status->value,
            'currency' => $this->currency,
            'totals' => [
                'subtotal' => $this->subtotal,
                'tax' => $this->tax_total,
                'shipping' => $this->shipping_total,
                'discount' => $this->discount_total,
                'total' => $this->total,
                'outstanding' => $this->outstanding_balance,
            ],
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'lines' => OrderLineResource::collection($this->whenLoaded('lines')),
            'addresses' => [
                'billing' => $this->billing_address,
                'shipping' => $this->shipping_address,
            ],
            'created_at' => $this->created_at,
            'confirmed_at' => $this->confirmed_at,
        ];
    }
}
```

---

## 🔗 Related Models

- [**OrderLine**](/v1/models/order-line) - Order items
- [**Customer**](/v1/models/customer) - Customer account
- [**Transaction**](/v1/models/transaction) - Payments & refunds
- [**Cart**](/v1/models/cart) - Shopping cart

---

## 📚 See Also

- [**Order Processing**](/v1/orders) - Complete workflow
- [**Payment Gateway**](/v1/payments) - Payment integration
- [**REST API - Orders**](/v1/rest-api/orders) - API endpoints
