---
id: 5e6f7a8b-9c0d-1e2f-3a4b-5c6d7e8f9a0b
blueprint: v1
title: 'Model: Customer'
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264233
---
# 👤 Customer Model

The `Customer` model represents a registered customer account in Cartino. Customers can place orders, manage addresses, earn loyalty points, and more.

[TOC]

## 📋 Overview

**Namespace**: `App\Models\Customer`  
**Table**: `customers`  
**Traits**: `SoftDeletes`, `HasFactory`, `Notifiable`

Customers are separate from `User` models - Users are admin/staff, Customers are shoppers.

---

## 🗄️ Schema

```php
customers {
  id                  bigint
  site_id             bigint FK → sites
  customer_group_id   bigint FK → customer_groups (nullable)
  
  // Identity
  first_name          string
  last_name           string
  email               string (unique)
  phone               string (nullable)
  
  // Authentication
  password            string (hashed)
  remember_token      string (nullable)
  
  // Profile
  avatar              string (nullable)
  birth_date          date (nullable)
  gender              enum (male, female, other, prefer_not_to_say)
  
  // Preferences
  locale              string (default: 'en')
  currency            string (default: 'EUR')
  timezone            string (nullable)
  
  // Marketing
  accepts_marketing   boolean (default: false)
  marketing_consent_at timestamp (nullable)
  
  // Status
  status              enum (active, inactive, blocked)
  email_verified_at   timestamp (nullable)
  last_login_at       timestamp (nullable)
  last_login_ip       string (nullable)
  
  // Statistics (computed)
  orders_count        integer (default: 0)
  total_spent         decimal (default: 0)
  lifetime_value      decimal (default: 0)
  
  // Notes
  note                text (nullable)
  
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
    'site_id',
    'customer_group_id',
    'first_name',
    'last_name',
    'email',
    'phone',
    'password',
    'avatar',
    'birth_date',
    'gender',
    'locale',
    'currency',
    'timezone',
    'accepts_marketing',
    'status',
    'note',
    'data',
];
```

### Hidden

```php
protected $hidden = [
    'password',
    'remember_token',
    'deleted_at',
];
```

### Casts

```php
protected $casts = [
    'birth_date' => 'date',
    'accepts_marketing' => 'boolean',
    'email_verified_at' => 'datetime',
    'marketing_consent_at' => 'datetime',
    'last_login_at' => 'datetime',
    'status' => CustomerStatus::class,
    'gender' => Gender::class,
    'orders_count' => 'integer',
    'total_spent' => 'decimal:2',
    'lifetime_value' => 'decimal:2',
    'data' => 'array',
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

public function customerGroup()
{
    return $this->belongsTo(CustomerGroup::class);
}
```

### HasMany

```php
public function orders()
{
    return $this->hasMany(Order::class)
                ->orderByDesc('created_at');
}

public function addresses()
{
    return $this->hasMany(CustomerAddress::class)
                ->orderBy('is_default', 'desc');
}

public function carts()
{
    return $this->hasMany(Cart::class);
}

public function wishlists()
{
    return $this->hasMany(Wishlist::class);
}

public function reviews()
{
    return $this->hasMany(ProductReview::class);
}

public function stockNotifications()
{
    return $this->hasMany(StockNotification::class);
}
```

### HasOne

```php
public function fidelityCard()
{
    return $this->hasOne(FidelityCard::class);
}

public function defaultBillingAddress()
{
    return $this->hasOne(CustomerAddress::class)
                ->where('type', 'billing')
                ->where('is_default', true);
}

public function defaultShippingAddress()
{
    return $this->hasOne(CustomerAddress::class)
                ->where('type', 'shipping')
                ->where('is_default', true);
}
```

### MorphMany

```php
public function socialAccounts()
{
    return $this->morphMany(SocialAccount::class, 'sociable');
}
```

---

## 🔍 Scopes

### Active

```php
public function scopeActive($query)
{
    return $query->where('status', CustomerStatus::Active);
}
```

### Verified

```php
public function scopeVerified($query)
{
    return $query->whereNotNull('email_verified_at');
}
```

### AcceptsMarketing

```php
public function scopeAcceptsMarketing($query)
{
    return $query->where('accepts_marketing', true);
}
```

### VIP

```php
public function scopeVip($query, $threshold = 1000)
{
    return $query->where('total_spent', '>=', $threshold);
}
```

### RecentlyActive

```php
public function scopeRecentlyActive($query, $days = 30)
{
    return $query->where('last_login_at', '>=', now()->subDays($days));
}
```

---

## 🎯 Accessors

### Full Name

```php
public function getFullNameAttribute(): string
{
    return "{$this->first_name} {$this->last_name}";
}
```

### Is VIP

```php
public function getIsVipAttribute(): bool
{
    return $this->total_spent >= config('cartino.customer.vip_threshold', 1000);
}
```

### Average Order Value

```php
public function getAverageOrderValueAttribute(): float
{
    if ($this->orders_count === 0) {
        return 0;
    }
    
    return round($this->total_spent / $this->orders_count, 2);
}
```

---

## ⚡ Methods

### Update Statistics

```php
public function updateStatistics(): void
{
    $this->orders_count = $this->orders()->count();
    
    $paidOrders = $this->orders()
        ->whereIn('payment_status', ['paid', 'partially_paid'])
        ->get();
    
    $this->total_spent = $paidOrders->sum('total');
    $this->lifetime_value = $this->total_spent + $this->estimateFutureValue();
    
    $this->saveQuietly();
}

private function estimateFutureValue(): float
{
    // Simple LTV calculation: average order value * estimated future orders
    if ($this->orders_count < 2) {
        return 0;
    }
    
    $avgOrderValue = $this->average_order_value;
    $estimatedFutureOrders = 3; // Can be more sophisticated
    
    return $avgOrderValue * $estimatedFutureOrders;
}
```

### Get Or Create Fidelity Card

```php
public function getOrCreateFidelityCard(): FidelityCard
{
    return $this->fidelityCard ?? $this->fidelityCard()->create([
        'card_number' => $this->generateFidelityCardNumber(),
        'is_active' => true,
        'activated_at' => now(),
    ]);
}

private function generateFidelityCardNumber(): string
{
    $prefix = config('cartino.fidelity.card.prefix', 'FID');
    $length = config('cartino.fidelity.card.length', 8);
    $separator = config('cartino.fidelity.card.separator', '-');
    
    do {
        $number = $prefix . $separator . strtoupper(Str::random($length));
    } while (FidelityCard::where('card_number', $number)->exists());
    
    return $number;
}
```

### Add Fidelity Points

```php
public function addFidelityPoints(int $points, string $reason, $orderId = null): FidelityTransaction
{
    $card = $this->getOrCreateFidelityCard();
    
    return $card->addPoints($points, $reason, $orderId);
}
```

### Send Email Verification

```php
public function sendEmailVerification(): void
{
    $this->notify(new VerifyEmailNotification());
}
```

### Merge With

```php
public function mergeWith(Customer $otherCustomer): void
{
    DB::transaction(function () use ($otherCustomer) {
        // Merge orders
        $otherCustomer->orders()->update(['customer_id' => $this->id]);
        
        // Merge addresses
        foreach ($otherCustomer->addresses as $address) {
            if (!$this->addresses()->where('formatted_address', $address->formatted_address)->exists()) {
                $address->update(['customer_id' => $this->id]);
            }
        }
        
        // Merge loyalty points
        if ($otherCustomer->fidelityCard && $this->fidelityCard) {
            $this->addFidelityPoints(
                $otherCustomer->fidelityCard->points_balance,
                'Merged from account #' . $otherCustomer->id
            );
        }
        
        // Update statistics
        $this->updateStatistics();
        
        // Soft delete old account
        $otherCustomer->delete();
    });
}
```

---

## 🎪 Events

```php
protected static function booted()
{
    static::creating(function ($customer) {
        if (!$customer->locale) {
            $customer->locale = app()->getLocale();
        }
        
        if (!$customer->currency) {
            $customer->currency = currentSite()->default_currency;
        }
    });
    
    static::created(function ($customer) {
        event(new CustomerRegistered($customer));
        
        if (!$customer->email_verified_at) {
            $customer->sendEmailVerification();
        }
    });
    
    static::updated(function ($customer) {
        if ($customer->wasChanged('email')) {
            $customer->update(['email_verified_at' => null]);
            $customer->sendEmailVerification();
        }
    });
}
```

---

## 💡 Usage Examples

### Creating a Customer

```php
$customer = Customer::create([
    'site_id' => currentSite()->id,
    'first_name' => 'Mario',
    'last_name' => 'Rossi',
    'email' => 'mario.rossi@example.com',
    'password' => Hash::make('password123'),
    'phone' => '+39 123 456 7890',
    'accepts_marketing' => true,
    'status' => CustomerStatus::Active,
]);

// Add default address
$customer->addresses()->create([
    'type' => 'both',
    'first_name' => 'Mario',
    'last_name' => 'Rossi',
    'address_line1' => 'Via Roma 123',
    'city' => 'Milan',
    'postal_code' => '20100',
    'country' => 'IT',
    'is_default' => true,
]);
```

### Authentication

```php
// Login
$customer = Customer::where('email', $email)->first();

if ($customer && Hash::check($password, $customer->password)) {
    $customer->update([
        'last_login_at' => now(),
        'last_login_ip' => request()->ip(),
    ]);
    
    auth('customer')->login($customer);
}
```

### Querying Customers

```php
// VIP customers
$vipCustomers = Customer::vip(1000)
    ->active()
    ->withCount('orders')
    ->get();

// Customers with abandoned carts
$abandoned = Customer::whereHas('carts', function ($q) {
    $q->where('updated_at', '<', now()->subDays(3))
      ->whereNull('completed_at');
})->get();

// Recently registered
$newCustomers = Customer::where('created_at', '>=', now()->subDays(7))
    ->verified()
    ->get();
```

---

## 🌐 API Resource

```php
class CustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'group' => new CustomerGroupResource($this->whenLoaded('customerGroup')),
            'statistics' => [
                'orders_count' => $this->orders_count,
                'total_spent' => $this->total_spent,
                'average_order_value' => $this->average_order_value,
                'lifetime_value' => $this->lifetime_value,
                'is_vip' => $this->is_vip,
            ],
            'fidelity' => [
                'card_number' => $this->fidelityCard?->card_number,
                'points_balance' => $this->fidelityCard?->points_balance ?? 0,
            ],
            'preferences' => [
                'locale' => $this->locale,
                'currency' => $this->currency,
                'accepts_marketing' => $this->accepts_marketing,
            ],
            'status' => $this->status->value,
            'email_verified' => $this->email_verified_at !== null,
            'created_at' => $this->created_at,
            'last_login_at' => $this->last_login_at,
        ];
    }
}
```

---

## 🔗 Related Models

- [**CustomerAddress**](/v1/models/customer-address) - Customer addresses
- [**CustomerGroup**](/v1/models/customer-group) - Customer groups
- [**Order**](/v1/models/order) - Customer orders
- [**FidelityCard**](/v1/models/fidelity-card) - Loyalty card

---

## 📚 See Also

- [**Customer Management**](/v1/customers) - Complete guide
- [**Loyalty System**](/v1/loyalty-system) - Points & rewards
- [**REST API - Customers**](/v1/rest-api/customers) - API endpoints
