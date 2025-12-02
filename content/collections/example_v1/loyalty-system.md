---
id: 4e7b8f23-5c2d-4a19-b3f8-9cb6d8e3f1a4
blueprint: example_v1
title: Loyalty System
updated_by: cbf6fa94-2658-4dec-9152-30c80d3c652c
updated_at: 1741264233
---
# Loyalty System

Cartino includes a comprehensive loyalty card and points system for rewarding customer engagement and purchases.

[TOC]

## Overview

The fidelity (loyalty) system enables:
- **Loyalty Cards** with unique codes
- **Points Accumulation** based on order amounts
- **Tiered Rewards** with increasing benefits
- **Points Expiration** with configurable periods
- **Points Redemption** for discounts

---

## Features

### 1. Loyalty Cards
- Unique card codes per customer
- Configurable code format (prefix, length, separator)
- Activation/deactivation management
- Last activity tracking

### 2. Points System
- Automatic calculation based on order amounts
- Tier-based conversion rates (rewards loyal customers)
- Configurable point expiration
- Points redemption for discounts or rewards

### 3. Transaction History
- Full audit trail of all point movements
- Reason tracking for each transaction
- Order association for purchases
- Expiration management

---

## Configuration

### Environment Variables

```env
# Loyalty System
CARTINO_FIDELITY_ENABLED=true
CARTINO_FIDELITY_POINTS_ENABLED=true

# Card Format
CARTINO_FIDELITY_CARD_PREFIX=FID
CARTINO_FIDELITY_CARD_LENGTH=8
CARTINO_FIDELITY_CARD_SEPARATOR=-
# Example generated code: FID-12345678

# Points Configuration
CARTINO_FIDELITY_CURRENCY_BASE=EUR
CARTINO_FIDELITY_POINTS_EXPIRATION=true
CARTINO_FIDELITY_POINTS_EXPIRATION_MONTHS=12
CARTINO_FIDELITY_MIN_REDEMPTION_POINTS=100
CARTINO_FIDELITY_POINTS_TO_CURRENCY=0.01
# 100 points = €1.00
```

### Config File

**File**: `config/cartino.php`

```php
'fidelity' => [
    'enabled' => env('CARTINO_FIDELITY_ENABLED', true),
    
    // Card Configuration
    'card' => [
        'prefix' => env('CARTINO_FIDELITY_CARD_PREFIX', 'FID'),
        'length' => env('CARTINO_FIDELITY_CARD_LENGTH', 8),
        'separator' => env('CARTINO_FIDELITY_CARD_SEPARATOR', '-'),
    ],
    
    // Points System
    'points' => [
        'enabled' => env('CARTINO_FIDELITY_POINTS_ENABLED', true),
        'currency_base' => env('CARTINO_FIDELITY_CURRENCY_BASE', 'EUR'),
        
        // Tier-based conversion rates
        'conversion_rules' => [
            'tiers' => [
                0 => 1,       // 0€+ = 1 point per euro
                100 => 1.5,   // 100€+ lifetime = 1.5 points per euro  
                500 => 2,     // 500€+ lifetime = 2 points per euro
                1000 => 3,    // 1000€+ lifetime = 3 points per euro
            ],
        ],
        
        // Point Expiration
        'expiration' => [
            'enabled' => env('CARTINO_FIDELITY_POINTS_EXPIRATION', true),
            'months' => env('CARTINO_FIDELITY_POINTS_EXPIRATION_MONTHS', 12),
        ],
        
        // Redemption Rules
        'redemption' => [
            'min_points' => env('CARTINO_FIDELITY_MIN_REDEMPTION_POINTS', 100),
            'points_to_currency_rate' => env('CARTINO_FIDELITY_POINTS_TO_CURRENCY', 0.01),
            // 100 points = €1.00 (0.01 * 100)
        ],
    ],
],
```

---

## Usage

### Creating Loyalty Cards

```php
use App\Services\FidelityService;
use App\Models\Customer;

$fidelityService = app(FidelityService::class);
$customer = Customer::find(1);

// Create or get existing card
$card = $customer->getOrCreateFidelityCard();

// Or using service
$card = $fidelityService->createFidelityCard($customer);

// Card properties
echo $card->card_number;  // FID-12345678
echo $card->points_balance;  // 0
echo $card->lifetime_points;  // 0
```

### Adding Points

```php
// Via customer model
$customer->addFidelityPoints(100, 'Welcome bonus');

// Via card model
$card = $customer->fidelityCard;
$card->addPoints(100, 'Order #123', $orderId);

// Automatic points from order
$order = Order::find(1);
$transaction = $fidelityService->processOrderForPoints($order);
```

### Redeeming Points

```php
// Check if redemption is allowed
if ($customer->canRedeemPoints(500)) {
    $transaction = $customer->redeemFidelityPoints(
        500,
        'Applied to order #456',
        $orderId
    );
    
    // Calculate discount value
    $discountValue = $card->getPointsValue(500);
    // 500 points * 0.01 = €5.00
}

// Via card model
if ($card->canRedeemPoints(500)) {
    $transaction = $card->redeemPoints(500, 'Discount applied');
}
```

### Calculating Points for Amount

```php
// Calculate points for purchase amount
$amount = 150.00;  // €150.00
$points = $card->calculatePointsForAmount($amount, 'EUR');

// Get current tier
$currentTier = $card->getCurrentTier();
// ['threshold' => 100, 'rate' => 1.5]

// Get next tier info
$nextTier = $card->getNextTier();
// [
//   'threshold' => 500,
//   'rate' => 2,
//   'amount_needed' => 350.00  // €350 more to reach tier
// ]
```

### Transaction History

```php
// Get all transactions
$transactions = $card->transactions()
    ->orderByDesc('created_at')
    ->get();

// Filter by type
$earned = $card->transactions()->earned()->get();
$redeemed = $card->transactions()->redeemed()->get();
$expired = $card->transactions()->expired()->get();

// Active points (not expired)
$activePoints = $card->transactions()->active()->sum('points');

// Points expiring soon (next 30 days)
$expiringSoon = $card->transactions()
    ->expiring(30)
    ->sum('points');
```

---

## Tier System

### How Tiers Work

Customers automatically move to higher tiers based on **lifetime purchase amount**:

```php
'tiers' => [
    0 => 1,       // Bronze: 1 point per €1
    100 => 1.5,   // Silver: 1.5 points per €1 (after €100 lifetime)
    500 => 2,     // Gold: 2 points per €1 (after €500 lifetime)
    1000 => 3,    // Platinum: 3 points per €1 (after €1000 lifetime)
]
```

### Example

Customer with €250 lifetime purchases:
- Current tier: **Gold** (€500+ tier not yet reached)
- Points per €1: **2 points**
- On €50 purchase: **100 points** (50 × 2)
- Needs €250 more to reach Platinum tier

### Querying Tier

```php
$tier = $card->getCurrentTier();

echo "Tier threshold: €{$tier['threshold']}";
echo "Points rate: {$tier['rate']} per euro";

$next = $card->getNextTier();
if ($next) {
    echo "Next tier at €{$next['threshold']}";
    echo "Amount needed: €{$next['amount_needed']}";
}
```

---

## Points Expiration

### Automatic Expiration

Points expire after a configurable period (default: 12 months):

```php
// Manual expiration check
$card->expirePoints();

// Via Artisan command
php artisan cartino:expire-fidelity-points

// Dry run (preview only)
php artisan cartino:expire-fidelity-points --dry-run

// With email notifications
php artisan cartino:expire-fidelity-points --notify
```

### Scheduled Expiration

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Run expiration daily at 2:00 AM
    $schedule->command('cartino:expire-fidelity-points --notify')
             ->dailyAt('02:00');
}
```

### Expiration Notifications

```php
// Points expiring in 30 days
$expiring = $card->transactions()
    ->active()
    ->expiring(30)
    ->get();

foreach ($expiring as $transaction) {
    $daysLeft = $transaction->getDaysUntilExpiration();
    
    // Send notification to customer
    $customer->notify(new PointsExpiringNotification($transaction, $daysLeft));
}
```

---

## API Endpoints

### Public Endpoints

```php
// Get system configuration
GET /api/fidelity/configuration

// Calculate points for amount
POST /api/fidelity/calculate-points
{
    "amount": 150.00,
    "currency": "EUR"
}

// Find card by number
POST /api/fidelity/find-card
{
    "card_number": "FID-12345678"
}
```

### Customer Endpoints (Authenticated)

```php
// Get customer's card info
GET /api/fidelity

// Create loyalty card
POST /api/fidelity

// Get transaction history
GET /api/fidelity/transactions?page=1
```

### Admin Endpoints

```php
// List all cards
GET /api/admin/fidelity/cards

// Get card details
GET /api/admin/fidelity/cards/{card}

// Update card
PUT /api/admin/fidelity/cards/{card}

// Add points manually
POST /api/admin/fidelity/cards/{card}/add-points
{
    "points": 100,
    "reason": "Customer service bonus"
}

// Redeem points
POST /api/admin/fidelity/redeem-points
{
    "card_id": 1,
    "points": 500,
    "reason": "Manager approval"
}

// Get statistics
GET /api/admin/fidelity/statistics

// Force expiration
POST /api/admin/fidelity/expire-points
```

---

## Events & Listeners

### Automatic Order Processing

The system automatically processes orders when their status changes:

```php
// In EventServiceProvider.php
protected $listen = [
    \App\Events\OrderStatusChanged::class => [
        \App\Listeners\ProcessFidelityPointsForOrder::class,
    ],
];
```

### Custom Events

Dispatch custom fidelity events:

```php
use App\Events\FidelityPointsAdded;
use App\Events\FidelityPointsRedeemed;

// Points added
event(new FidelityPointsAdded($card, $points, $reason));

// Points redeemed  
event(new FidelityPointsRedeemed($card, $points, $reason));
```

---

## Database Schema

### fidelity_cards Table

```php
fidelity_cards {
  id              bigint
  customer_id     bigint FK → customers (unique)
  card_number     string (unique)
  points_balance  integer (default: 0)
  lifetime_points integer (default: 0)
  lifetime_spent  decimal (default: 0)
  is_active       boolean (default: true)
  activated_at    timestamp
  last_activity_at timestamp
  
  timestamps, soft_deletes
}
```

### fidelity_transactions Table

```php
fidelity_transactions {
  id              bigint
  fidelity_card_id bigint FK → fidelity_cards
  type            enum (earned, redeemed, expired, adjusted)
  points          integer
  reason          text
  order_id        bigint FK → orders (nullable)
  expires_at      timestamp (nullable)
  expired_at      timestamp (nullable)
  
  timestamps
}
```

---

## Testing

```bash
# Run fidelity system tests
php artisan test --filter=FidelitySystemTest

# Test specific functionality
php artisan test --filter=FidelityCardTest
php artisan test --filter=FidelityPointsTest
php artisan test --filter=FidelityTierTest
```

---

## Best Practices

### DO ✅

- Always validate point amounts before transactions
- Track all transactions with clear reasons
- Send notifications before points expire
- Use tier system to encourage loyalty
- Monitor redemption patterns
- Regularly run expiration command

### DON'T ❌

- Don't allow negative point balances
- Don't delete transaction history
- Don't skip expiration checks
- Don't forget to notify customers of changes
- Don't allow redemption below minimum threshold

---

## Analytics & Reporting

```php
// Total active points in system
$totalPoints = FidelityCard::sum('points_balance');

// Average points per customer
$avgPoints = FidelityCard::avg('points_balance');

// Top customers by lifetime points
$topCustomers = FidelityCard::orderByDesc('lifetime_points')
    ->limit(10)
    ->get();

// Points earned this month
$earnedThisMonth = FidelityTransaction::earned()
    ->whereMonth('created_at', now()->month)
    ->sum('points');

// Redemption rate
$redeemed = FidelityTransaction::redeemed()->sum('points');
$redemptionRate = ($redeemed / $totalPoints) * 100;
```

---

## Next Steps

- [**Product Architecture**](/example/1.x/product-architecture) - Understand product/variant system
- [**Sites & Markets**](/example/1.x/sites-architecture) - Multi-site configuration
- [**Development**](/example/1.x/development) - Extend the loyalty system
