<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $config['base'] . '/cart.php');
    exit;
}

$userId = (int)$_SESSION['user']['id'];
$paymentMethod = trim($_POST['payment_method'] ?? 'Resort');
$extras = $_POST['extras'] ?? [];

try {
    $stmtCart = db()->prepare('
        SELECT b.*, rc.base_price
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_categories rc ON r.category_id = rc.id
        WHERE b.user_id = ? AND b.status_id = 1
    ');
    $stmtCart->execute([$userId]);
    $items = $stmtCart->fetchAll();

    if (empty($items)) {
        header('Location: ' . $config['base'] . '/cart.php?error=' . urlencode('Il tuo carrello è vuoto o la sessione di prenotazione è scaduta (30 minuti).'));
        exit;
    }

    $newStatus = ($paymentMethod === 'Bonifico') ? 2 : 3; // 2=Pending, 3=Confirmed
    $lastInvoiceId = 0;

    db()->beginTransaction();

    // Se l'utente ha scelto degli extra, ne calcoliamo il prezzo
    $extrasPrice = 0.0;
    if (!empty($extras) && is_array($extras)) {
        $ids = array_keys($extras);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtEx = db()->prepare("SELECT id, price FROM amenities WHERE id IN ($placeholders)");
        $stmtEx->execute($ids);
        $amRows = $stmtEx->fetchAll();
    } else {
        $amRows = [];
    }

    // Cancella eventuali extra precedentemente inseriti nel carrello per evitare conflitti
    $cartBookingIds = array_column($items, 'id');
    $delPlaceholders = implode(',', array_fill(0, count($cartBookingIds), '?'));
    $delAmen = db()->prepare("DELETE FROM booking_amenities WHERE booking_id IN ($delPlaceholders)");
    $delAmen->execute($cartBookingIds);

    $updBook = db()->prepare('UPDATE bookings SET status_id = ?, total_price = ? WHERE id = ?');
    $insAmen = db()->prepare('INSERT INTO booking_amenities (booking_id, amenity_id, quantity) VALUES (?, ?, 1)');
    $insInv  = db()->prepare('INSERT INTO invoices (booking_id, total_amount) VALUES (?, ?)');
    $insInv  = db()->prepare('INSERT INTO invoices (booking_id, total_amount, invoice_date, payment_status) VALUES (?, ?, NOW(), \'paid\')');

    foreach ($items as $item) {
        $bookingId = (int)$item['id'];
        $days = max(1, (int)round((strtotime($item['check_out_date']) - strtotime($item['check_in_date'])) / 86400));
        $itemTotal = $days * (float)$item['base_price'];

        // Aggiungiamo i servizi extra a ogni prenotazione del carrello
        foreach ($amRows as $am) {
            $amPrice = (float)$am['price'];
            $insAmen->execute([$bookingId, (int)$am['id']]);
            $itemTotal += $amPrice;
        }

        // Aggiorna prenotazione con nuovo stato e prezzo definitivo
        $updBook->execute([$newStatus, $itemTotal, $bookingId]);

        // Genera fattura
        $insInv->execute([$bookingId, $itemTotal]);
        $lastInvoiceId = (int)db()->lastInsertId();
    }

    db()->commit();

    header('Location: ' . $config['base'] . '/invoice.php?id=' . $lastInvoiceId . '&success=1');
    exit;

} catch (Exception $e) {
    if (db()->inTransaction()) db()->rollBack();
    header('Location: ' . $config['base'] . '/cart.php?error=' . urlencode('Errore durante la conferma dell\'ordine: ' . $e->getMessage()));
    exit;
}
