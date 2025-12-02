# Sistema di Fedeltà Shopper

Il sistema di fedeltà di Shopper permette di gestire carte fedeltà personalizzate e sistemi di punti per i clienti.

## Caratteristiche Principali

### 1. Carte Fedeltà
- **Codice Univoco**: Ogni customer può avere una carta fedeltà con un codice univoco
- **Formato Configurabile**: Il formato del codice è configurabile tramite le impostazioni
- **Gestione Attivazione**: Le carte possono essere attivate o disattivate
- **Tracciamento Attività**: Viene tracciata l'ultima attività della carta

### 2. Sistema Punti
- **Calcolo Automatico**: I punti vengono calcolati automaticamente in base agli ordini
- **Scaglioni di Conversione**: Sistema a tier che premia i clienti più fedeli
- **Scadenza Punti**: I punti possono scadere dopo un periodo configurabile
- **Riscatto Punti**: I punti possono essere riscattati per sconti o premi

## Configurazione

### File di Configurazione: `config/shopper.php`

```php
'fidelity' => [
    'enabled' => env('CARTINO_FIDELITY_ENABLED', true),
    
    // Configurazione carta
    'card' => [
        'prefix' => env('CARTINO_FIDELITY_CARD_PREFIX', 'FID'),
        'length' => env('CARTINO_FIDELITY_CARD_LENGTH', 8),
        'separator' => env('CARTINO_FIDELITY_CARD_SEPARATOR', '-'),
    ],
    
    // Sistema punti
    'points' => [
        'enabled' => env('CARTINO_FIDELITY_POINTS_ENABLED', true),
        'currency_base' => env('CARTINO_FIDELITY_CURRENCY_BASE', 'EUR'),
        
        // Scaglioni di conversione
        'conversion_rules' => [
            'tiers' => [
                0 => 1,      // 0€+ = 1 punto per euro
                100 => 1.5,  // 100€+ = 1.5 punti per euro  
                500 => 2,    // 500€+ = 2 punti per euro
                1000 => 3,   // 1000€+ = 3 punti per euro
            ],
        ],
        
        // Scadenza punti
        'expiration' => [
            'enabled' => env('CARTINO_FIDELITY_POINTS_EXPIRATION', true),
            'months' => env('CARTINO_FIDELITY_POINTS_EXPIRATION_MONTHS', 12),
        ],
        
        // Regole di riscatto
        'redemption' => [
            'min_points' => env('CARTINO_FIDELITY_MIN_REDEMPTION_POINTS', 100),
            'points_to_currency_rate' => env('CARTINO_FIDELITY_POINTS_TO_CURRENCY', 0.01),
        ],
    ],
],
```

### Variabili d'Ambiente

```env
# Sistema Fedeltà
CARTINO_FIDELITY_ENABLED=true
CARTINO_FIDELITY_POINTS_ENABLED=true

# Formato Carta
CARTINO_FIDELITY_CARD_PREFIX=FID
CARTINO_FIDELITY_CARD_LENGTH=8
CARTINO_FIDELITY_CARD_SEPARATOR=-

# Configurazione Punti
CARTINO_FIDELITY_CURRENCY_BASE=EUR
CARTINO_FIDELITY_POINTS_EXPIRATION=true
CARTINO_FIDELITY_POINTS_EXPIRATION_MONTHS=12
CARTINO_FIDELITY_MIN_REDEMPTION_POINTS=100
CARTINO_FIDELITY_POINTS_TO_CURRENCY=0.01
```

## Utilizzo

### 1. Creare una Carta Fedeltà

```php
use Shopper\Services\FidelityService;
use Shopper\Models\Customer;

$fidelityService = app(FidelityService::class);
$customer = Customer::find(1);

// Crea o ottiene la carta esistente
$card = $customer->getOrCreateFidelityCard();

// Oppure usando il servizio
$card = $fidelityService->createFidelityCard($customer);
```

### 2. Aggiungere Punti

```php
// Tramite il customer
$customer->addFidelityPoints(100, 'Bonus registrazione');

// Tramite la carta
$card = $customer->fidelityCard;
$card->addPoints(100, 'Punti da ordine', $orderId);
```

### 3. Riscattare Punti

```php
// Verifica se è possibile riscattare
if ($customer->canRedeemPoints(500)) {
    $transaction = $customer->redeemFidelityPoints(500, 'Sconto applicato');
}

// Tramite la carta
if ($card->canRedeemPoints(500)) {
    $transaction = $card->redeemPoints(500, 'Sconto applicato');
}
```

### 4. Calcolare Punti per un Importo

```php
// Calcola punti per un importo
$points = $card->calculatePointsForAmount(100.00, 'EUR');

// Ottieni il tier attuale
$currentTier = $card->getCurrentTier();
// ['threshold' => 100, 'rate' => 1.5]

// Ottieni il prossimo tier
$nextTier = $card->getNextTier();
// ['threshold' => 500, 'rate' => 2, 'amount_needed' => 400]
```

### 5. Processare Ordini per Punti

```php
use Shopper\Models\Order;

$order = Order::find(1);
$transaction = $fidelityService->processOrderForPoints($order);
```

## API Endpoints

### Pubbliche

```
GET    /api/shopper/fidelity/configuration          # Configurazione sistema
POST   /api/shopper/fidelity/calculate-points       # Calcola punti per importo
POST   /api/shopper/fidelity/find-card              # Trova carta per numero
```

### Autenticate (Customer)

```
GET    /api/shopper/fidelity                        # Info carta customer
POST   /api/shopper/fidelity                        # Crea carta
GET    /api/shopper/fidelity/transactions           # Transazioni carta
```

### Amministrative

```
GET    /api/shopper/admin/fidelity/cards            # Lista carte
GET    /api/shopper/admin/fidelity/cards/{card}     # Dettagli carta
PUT    /api/shopper/admin/fidelity/cards/{card}     # Aggiorna carta
POST   /api/shopper/admin/fidelity/cards/{card}/add-points  # Aggiungi punti
POST   /api/shopper/admin/fidelity/redeem-points    # Riscatta punti
GET    /api/shopper/admin/fidelity/statistics       # Statistiche sistema
POST   /api/shopper/admin/fidelity/expire-points    # Forza scadenza punti
```

## Comandi Artisan

### Scadenza Punti Automatica

```bash
# Esegui scadenza punti
php artisan shopper:expire-fidelity-points

# Modalità dry-run (solo visualizzazione)
php artisan shopper:expire-fidelity-points --dry-run

# Con notifiche email
php artisan shopper:expire-fidelity-points --notify
```

### Scheduling (Cron)

Aggiungi al tuo `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Esegui scadenza punti ogni giorno alle 02:00
    $schedule->command('shopper:expire-fidelity-points --notify')
             ->dailyAt('02:00');
}
```

## Eventi e Listener

Il sistema risponde automaticamente agli eventi degli ordini:

```php
// In EventServiceProvider.php
protected $listen = [
    \Shopper\Events\OrderStatusChanged::class => [
        \Shopper\Listeners\ProcessFidelityPointsForOrder::class,
    ],
];
```

## Database

### Tabelle Create

1. **`fidelity_cards`** - Informazioni delle carte fedeltà
2. **`fidelity_transactions`** - Transazioni punti (guadagni, riscatti, scadenze)

### Migration

```bash
php artisan migrate
```

### Seeder

```bash
php artisan db:seed --class=FidelitySeeder
```

## Modelli e Relazioni

### Customer

```php
// Relazioni
$customer->fidelityCard;           // Carta fedeltà
$customer->fidelityTransactions;   // Tutte le transazioni

// Metodi
$customer->getOrCreateFidelityCard();
$customer->getFidelityCardNumber();
$customer->getFidelityPoints();
$customer->addFidelityPoints($points, $reason);
$customer->redeemFidelityPoints($points, $reason);
$customer->canRedeemPoints($points);
$customer->processOrderForFidelity($order);
```

### FidelityCard

```php
// Relazioni
$card->customer;                   // Cliente proprietario
$card->transactions;               // Tutte le transazioni
$card->pointsTransactions;         // Solo transazioni punti
$card->redemptionTransactions;     // Solo riscatti

// Metodi
$card->addPoints($points, $reason, $orderId);
$card->redeemPoints($points, $reason, $orderId);
$card->calculatePointsForAmount($amount, $currency);
$card->getCurrentTier();
$card->getNextTier();
$card->getPointsValue();
$card->canRedeemPoints($points);
$card->expirePoints();
```

### FidelityTransaction

```php
// Scopes
FidelityTransaction::earned();     // Solo punti guadagnati
FidelityTransaction::redeemed();   // Solo riscatti
FidelityTransaction::expired();    // Solo scaduti
FidelityTransaction::active();     // Transazioni attive
FidelityTransaction::expiring(30); // In scadenza nei prossimi 30 giorni

// Metodi
$transaction->isExpired();
$transaction->isActive();
$transaction->getDaysUntilExpiration();
```

## Testing

Il sistema include test completi:

```bash
php artisan test --filter=FidelitySystemTest
```

## Sicurezza

- Le API amministrative richiedono autenticazione e permessi appropriati
- I dati sensibili delle carte sono protetti
- Le transazioni sono tracciabili e non modificabili
- Validazione rigorosa degli input

## Performance

- Indici database ottimizzati
- Cache per operazioni frequenti  
- Paginazione per liste lunghe
- Elaborazione batch per operazioni massive
