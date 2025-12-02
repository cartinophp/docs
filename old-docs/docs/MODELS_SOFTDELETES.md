# Modelli con SoftDeletes e Relazioni Aggiornate

## Modelli con SoftDeletes Implementati

### 🏷️ Prodotti e Catalogo
- **Product**: SoftDeletes aggiunto - i prodotti cancellati rimangono nel database per mantenere lo storico degli ordini
- **ProductVariant**: SoftDeletes aggiunto - le varianti cancellate non interferiscono con gli ordini esistenti  
- **ProductType**: SoftDeletes aggiunto + `is_enabled` → `status`
- **Brand**: SoftDeletes aggiunto - i brand cancellati mantengono l'associazione con prodotti esistenti
- **Category**: SoftDeletes aggiunto - le categorie cancellate mantengono la struttura gerarchica

### 👥 Clienti e Utenti
- **Customer**: SoftDeletes aggiunto - i clienti cancellati mantengono lo storico degli ordini
- **CustomerGroup**: SoftDeletes aggiunto - i gruppi cancellati non perdono i membri esistenti

### 📋 Ordini e Transazioni
- **Order**: SoftDeletes aggiunto - gli ordini cancellati rimangono per audit e report
- **Discount**: SoftDeletes già presente - mantiene i coupon utilizzati negli ordini

## Benefici del SoftDelete

### 🔐 Integrità dei Dati
- **Storico degli Ordini**: Gli ordini mantengono i riferimenti ai prodotti anche se cancellati
- **Audit Trail**: Possibilità di vedere cosa è stato cancellato e quando
- **Relazioni Mantenute**: I dati correlati rimangono accessibili

### 📊 Business Intelligence
- **Report Accurati**: I report storici includono anche i dati "cancellati"
- **Trend Analysis**: Analisi delle tendenze sui prodotti discontinuati
- **Customer Journey**: Tracciamento completo del percorso cliente

### 🛡️ Sicurezza e Compliance
- **Recupero Dati**: Possibilità di ripristinare dati cancellati per errore
- **Conformità GDPR**: Cancellazione "soft" per audit, cancellazione "hard" su richiesta
- **Backup Logico**: Protezione aggiuntiva contro perdite accidentali

## Relazioni con Cascata

### 🔗 CASCADE DELETE (Hard Delete)
Quando il parent viene cancellato definitivamente, i children vengono cancellati automaticamente:

```sql
-- Esempi dalla migrazione
customer_id -> customers (cascadeOnDelete) -- Se customer viene cancellato hard, gli indirizzi vengono cancellati
product_id -> products (cascadeOnDelete)  -- Se product viene cancellato hard, le varianti vengono cancellate
```

### 🔄 NULL ON DELETE (Soft Reference)
Quando il parent viene cancellato, il riferimento diventa NULL ma il record rimane:

```sql
-- Esempi dalla migrazione  
brand_id -> brands (nullOnDelete)         -- Se brand viene cancellato, product.brand_id = NULL
customer_id -> customers (nullOnDelete)   -- Se customer viene cancellato, order.customer_id = NULL
```

## Gestione Shopify-Style delle Immagini

### 📸 Spatie Media Library
Invece di una tabella `product_images` dedicata, utilizziamo **Spatie Media Library**:

```php
// Nei modelli Product, Category, Brand
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;
    
    // Media collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
            
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
```

### 🎯 Vantaggi Spatie Media
- **Conversioni Automatiche**: Thumbnail, webp, responsive images
- **Organizzazione**: Collections separate per diversi tipi di immagini
- **Performance**: Ottimizzazione automatica delle immagini
- **Flessibilità**: Gestione di qualsiasi tipo di media

## Esempio di Utilizzo

### 📝 Query con SoftDeletes

```php
// Include solo i record attivi (default)
$products = Product::where('status', 'active')->get();

// Include anche i record soft-deleted
$allProducts = Product::withTrashed()->get();

// Solo i record soft-deleted
$deletedProducts = Product::onlyTrashed()->get();

// Ripristina un record soft-deleted
$product = Product::withTrashed()->find(1);
$product->restore();

// Cancellazione definitiva (hard delete)
$product->forceDelete();
```

### 🔄 Gestione delle Relazioni

```php
// Prodotto con varianti (include anche quelle soft-deleted se necessario)
$product = Product::with(['variants' => function($query) {
    $query->withTrashed(); // Include varianti soft-deleted
}])->find(1);

// Ordini con prodotti soft-deleted (per visualizzare lo storico)
$order = Order::with(['lines.product' => function($query) {
    $query->withTrashed(); // Mostra anche prodotti cancellati
}])->find(1);
```

## Configurazione Enum Status

I modelli ora utilizzano enum PHP per type-safety:

```php
use Shopper\Enums\Status;

// Nei modelli
protected $casts = [
    'status' => Status::class,
    'published_at' => 'datetime',
];

// Utilizzo
$product->status = Status::Active;
$product->save();

// Query
Product::where('status', Status::Active)->get();
```

Questa architettura garantisce **data integrity**, **performance** e **flessibilità** per un e-commerce enterprise.
