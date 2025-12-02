# Architettura Prodotti Shopify-Style

## Filosofia di Base

Nel modello Shopify, **ogni prodotto deve avere almeno una variante**, anche se concettualmente non ha variazioni. Questo permette una struttura dati uniforme e flessibile.

## Struttura Dati

### Products Table (Contenitore)
La tabella `products` contiene solo informazioni **a livello di prodotto**:

- **Identità**: `title`, `slug`, `handle`, `description`
- **Classificazione**: `product_type`, `brand_id`, `product_type_id`
- **Opzioni**: `options` (JSON con definizione delle opzioni come Color, Size)
- **SEO**: `meta_title`, `meta_description`, `seo`
- **Publishing**: `status`, `published_at`, `published_scope`
- **Relazioni**: `default_variant_id`, `variants_count`, `price_min`, `price_max`

### Product Variants Table (Dati Reali)
La tabella `product_variants` contiene **tutti i dati operativi**:

- **Pricing**: `price`, `compare_at_price`, `cost`
- **Inventory**: `inventory_quantity`, `track_quantity`, `inventory_management`, `inventory_policy`
- **Physical**: `weight`, `weight_unit`, `dimensions`
- **Shipping & Tax**: `requires_shipping`, `taxable`, `tax_code`
- **Options**: `option1`, `option2`, `option3` (valori delle opzioni del prodotto)
- **Status**: `status`, `available`, `position`

## Esempi Pratici

### 1. Prodotto Semplice (E-book)
```php
Product {
    title: "E-book: Laravel Guide",
    options: null, // Nessuna opzione
    default_variant_id: 1
}

ProductVariant {
    product_id: 1,
    title: "Default Title",
    option1: null, option2: null, option3: null,
    price: 29.99,
    requires_shipping: false,
    track_quantity: false
}
```

### 2. Prodotto con Varianti (T-shirt)
```php
Product {
    title: "Cotton T-Shirt",
    options: [
        {name: "Color", values: ["Red", "Blue", "Black"]},
        {name: "Size", values: ["Small", "Medium", "Large"]}
    ],
    default_variant_id: 5 // Prima variante creata
}

ProductVariant {
    product_id: 2,
    title: "Red / Small",
    option1: "Red", option2: "Small", option3: null,
    price: 19.99,
    position: 1
}

ProductVariant {
    product_id: 2,
    title: "Red / Medium", 
    option1: "Red", option2: "Medium", option3: null,
    price: 21.99,
    position: 2
}
// ... altre 7 varianti
```

### 3. Prodotto con Gestione Stock (Cuffie)
```php
Product {
    title: "Wireless Headphones",
    options: null, // Una sola variante, nessuna opzione
    default_variant_id: 15
}

ProductVariant {
    product_id: 3,
    title: "Default Title",
    option1: null, option2: null, option3: null,
    price: 149.99,
    compare_at_price: 199.99,
    inventory_quantity: 25,
    track_quantity: true,
    inventory_policy: "deny"
}
```

## Vantaggi di Questa Architettura

1. **Uniformità**: Ogni prodotto ha sempre almeno una variante
2. **Flessibilità**: Facile aggiungere/rimuovere varianti senza ristrutturare
3. **Performance**: Query ottimizzate su varianti (dove stanno i dati reali)
4. **Scalabilità**: Supporta da prodotti semplici a complessi senza cambiare struttura
5. **Shopify-Compatible**: Stessa logica di Shopify per facilità di migrazione

## Query di Esempio

```sql
-- Prezzo minimo/massimo di un prodotto
SELECT MIN(price) as min_price, MAX(price) as max_price 
FROM product_variants 
WHERE product_id = 1;

-- Tutte le varianti disponibili di un prodotto
SELECT * FROM product_variants 
WHERE product_id = 1 
AND status = 'active' 
AND available = true
ORDER BY position;

-- Prodotti in una fascia di prezzo
SELECT DISTINCT p.* 
FROM products p
JOIN product_variants pv ON p.id = pv.product_id
WHERE pv.price BETWEEN 20 AND 50
AND pv.status = 'active';
```

## Enum PHP per Type Safety

Utilizziamo enum PHP invece di enum MySQL per maggiore flessibilità:

```php
enum Status: string {
    case Active = 'active';
    case Inactive = 'inactive';
    case Draft = 'draft';
    case Archived = 'archived';
}

enum InventoryPolicy: string {
    case Deny = 'deny';         // Blocca vendita se out of stock
    case Continue = 'continue';  // Permetti vendita anche senza stock
}

enum WeightUnit: string {
    case Kilogram = 'kg';
    case Gram = 'g';
    case Pound = 'lb';
    case Ounce = 'oz';
}
```

Questo approccio garantisce **type safety** a livello di codice PHP mantenendo **flessibilità** a livello di database.
