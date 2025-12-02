# Asset Management - Statamic Style

Cartino utilizza un sistema di gestione degli asset simile a Statamic CMS, che costruisce automaticamente il frontend e lo pubblica nella cartella `public/vendor/shopper` dell'applicazione Laravel.

## Come Funziona

### Durante lo Sviluppo del Package

Quando stai sviluppando il package Shopper stesso:

```bash
# Sviluppo con hot reload (build in public/build)
CARTINO_DEV=true npm run dev

# Oppure usa l'alias
npm run dev

# Build per testing del package
npm run build:dev
```

### Per la Distribuzione (come Statamic)

Per buildare e pubblicare gli asset per l'uso nelle applicazioni Laravel:

```bash
# Build di produzione in public/vendor/shopper
npm run build

# Oppure usa il comando Artisan
php artisan shopper:build

# Build con opzioni
php artisan shopper:build --dev     # Build development
php artisan shopper:build --watch   # Build e watch per cambiamenti
```

## Installazione nelle App Laravel

Quando installi Shopper in un'applicazione Laravel:

```bash
# Installazione completa (include build degli asset)
php artisan shopper:install

# Se vuoi solo aggiornare gli asset
php artisan shopper:build
php artisan vendor:publish --tag=shopper-assets-built --force
```

## Come Vengono Caricati gli Asset

Il sistema verifica automaticamente se gli asset sono stati buildati:

1. **Asset Buildati Disponibili**: Usa gli asset ottimizzati da `public/vendor/shopper/`
2. **Asset Non Buildati**: Fallback al development server Vite

```blade
{{-- Nel template Blade, questo viene gestito automaticamente --}}
@if(\Shopper\Support\Asset::isBuilt())
    {{-- Usa asset buildati --}}
    {!! \Shopper\Support\Asset::styles() !!}
    {!! \Shopper\Support\Asset::scripts() !!}
@else
    {{-- Fallback a Vite dev server --}}
    @vite(['resources/js/app.js', 'resources/css/app.css'])
@endif
```

## Struttura degli Asset Buildati

```text
public/vendor/shopper/
├── .vite/
│   └── manifest.json          # Manifest Vite con mapping dei file
└── assets/
    ├── app-[hash].js          # Applicazione principale
    ├── app-[hash].css         # Stili CSS
    ├── vendor-[hash].js       # Librerie vendor (Vue, Pinia, etc.)
    └── ui-[hash].js           # Componenti UI (Heroicons, Reka UI)
```

## Configurazione Vite

Il sistema usa una configurazione Vite intelligente:

```javascript
// vite.config.js
const isPackageDev = process.env.CARTINO_DEV === 'true';

export default defineConfig({
  // ...
  publicDir: false, // Evita problemi di ricorsione
  build: {
    outDir: isPackageDev ? "public/build" : "public/vendor/shopper",
    // ...
  }
})
```

## Comandi Disponibili

### Per Sviluppatori del Package

```bash
npm run dev              # Sviluppo con CARTINO_DEV=true
npm run dev:package      # Sviluppo normale (build in vendor/shopper)
npm run build           # Build di produzione
npm run build:dev       # Build development
npm run build:watch     # Build con watch
```

### Per Utenti del Package

```bash
php artisan shopper:install      # Installazione completa
php artisan shopper:build        # Build asset
php artisan shopper:build --dev  # Build development
php artisan shopper:build --watch # Build con watch
```

## Vantaggi di Questo Approccio

1. **Zero Configurazione**: Gli utenti non devono configurare Vite o build processes
2. **Asset Ottimizzati**: Gli asset sono pre-buildati e ottimizzati per produzione
3. **Fallback Automatico**: Sviluppo facile con fallback a Vite dev server
4. **Compatibilità**: Funziona sia durante lo sviluppo che in produzione
5. **Performance**: Asset ottimizzati con code splitting automatico

## Workflow di Sviluppo

### Come Sviluppatore del Package

1. Lavora con `npm run dev` per hot reload
2. Testa con `npm run build` prima di rilasciare
3. Gli asset buildati vengono inclusi nel package pubblicato

### Come Utente del Package

1. Installa il package: `composer require @vitalijalbu/laravel-shopper`
2. Esegui: `php artisan shopper:install`
3. Gli asset sono automaticamente disponibili e ottimizzati

Questo sistema replica l'approccio di Statamic CMS per una esperienza di sviluppo e distribuzione senza problemi.
