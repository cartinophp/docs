<?php

// Esempio di utilizzo del Sistema di Fedeltà Shopper

use Shopper\Models\Customer;
use Shopper\Models\Order;
use Shopper\Services\FidelityService;

// 1. Ottenere il servizio
$fidelityService = app(FidelityService::class);

// 2. Creare una carta fedeltà per un customer
$customer = Customer::find(1);
$card = $customer->getOrCreateFidelityCard();

echo "Carta creata: {$card->card_number}\n";
// Output: Carta creata: FID-A1B2C3D4

// 3. Verificare configurazione tier
$currentTier = $card->getCurrentTier();
echo "Tier attuale: {$currentTier['rate']} punti per euro (soglia: €{$currentTier['threshold']})\n";
// Output: Tier attuale: 1 punti per euro (soglia: €0)

// 4. Simulare un ordine e calcolare punti
$amount = 150.00;
$points = $card->calculatePointsForAmount($amount, 'EUR');
echo "Punti calcolati per €{$amount}: {$points} punti\n";
// Output: Punti calcolati per €150.00: 150 punti

// 5. Aggiungere punti manualmente
$card->addPoints(50, 'Bonus benvenuto');
echo "Punti disponibili dopo bonus: {$card->available_points}\n";
// Output: Punti disponibili dopo bonus: 50

// 6. Processare un ordine completo
$order = Order::factory()->create([
    'customer_id' => $customer->id,
    'total' => $amount,
    'currency' => 'EUR',
]);

$transaction = $fidelityService->processOrderForPoints($order);
echo "Transazione creata: {$transaction->points} punti per ordine #{$order->number}\n";
// Output: Transazione creata: 150 punti per ordine #12345

$card->refresh();
echo "Totale punti ora: {$card->available_points}\n";
// Output: Totale punti ora: 200

// 7. Verificare nuovo tier dopo spesa
$newTier = $card->getCurrentTier();
$nextTier = $card->getNextTier();

echo "Nuovo tier: {$newTier['rate']} punti per euro\n";
// Output: Nuovo tier: 1.5 punti per euro (se ha superato €100)

if ($nextTier) {
    echo "Prossimo tier: {$nextTier['rate']} punti per euro (mancano €{$nextTier['amount_needed']})\n";
    // Output: Prossimo tier: 2 punti per euro (mancano €350)
}

// 8. Riscattare punti
if ($card->canRedeemPoints(150)) {
    $redemption = $card->redeemPoints(150, 'Sconto 10%');
    echo "Riscatto effettuato: {$redemption->points} punti\n";
    // Output: Riscatto effettuato: -150 punti

    $card->refresh();
    echo "Punti rimanenti: {$card->available_points}\n";
    // Output: Punti rimanenti: 50
}

// 9. Ottenere statistiche della carta
$stats = $fidelityService->getCardStatistics($card);
echo "Statistiche carta:\n";
echo "- Totale guadagnati: {$stats['total_earned']}\n";
echo "- Totale riscattati: {$stats['total_redeemed']}\n";
echo '- Valore punti attuali: €'.number_format($stats['points_value'], 2)."\n";
echo '- Totale speso: €'.number_format($stats['total_spent'], 2)."\n";

// 10. Trovare carta per numero
$foundCard = $fidelityService->findCardByNumber($card->card_number);
if ($foundCard) {
    echo "Carta trovata per customer: {$foundCard->customer->full_name}\n";
}

// 11. Transazioni recenti
$recentTransactions = $fidelityService->getRecentTransactions($card, 5);
echo "\nUltime 5 transazioni:\n";
foreach ($recentTransactions as $transaction) {
    echo "- {$transaction->type}: {$transaction->points} punti - {$transaction->description}\n";
}

// 12. Scadenza punti (simulazione)
echo "\nSimulazione scadenza punti...\n";
$card->transactions()->create([
    'type' => 'earned',
    'points' => 100,
    'description' => 'Punti in scadenza',
    'expires_at' => now()->subDay(), // Già scaduti
]);

$card->update(['available_points' => $card->available_points + 100]);

echo "Punti prima della scadenza: {$card->available_points}\n";
$card->expirePoints();
$card->refresh();
echo "Punti dopo la scadenza: {$card->available_points}\n";

/* Output esempio completo:
Carta creata: FID-A1B2C3D4
Tier attuale: 1 punti per euro (soglia: €0)
Punti calcolati per €150.00: 150 punti
Punti disponibili dopo bonus: 50
Transazione creata: 150 punti per ordine #12345
Totale punti ora: 200
Nuovo tier: 1.5 punti per euro
Prossimo tier: 2 punti per euro (mancano €350)
Riscatto effettuato: -150 punti
Punti rimanenti: 50
Statistiche carta:
- Totale guadagnati: 200
- Totale riscattati: 150
- Valore punti attuali: €0.50
- Totale speso: €150.00
Carta trovata per customer: Mario Rossi

Ultime 5 transazioni:
- redeemed: -150 punti - Sconto 10%
- earned: 150 punti - Points earned from order #12345
- earned: 50 punti - Bonus benvenuto

Simulazione scadenza punti...
Punti prima della scadenza: 150
Punti dopo la scadenza: 50
*/
