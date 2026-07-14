<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

require_login();

$userId = (int)$_SESSION['user']['id'];
$message = '';
$error = '';

if (!empty($_GET['added'])) $message = 'Camera allocata e aggiunta al tuo carrello con successo! Hai 30 minuti per completare la prenotazione.';
if (!empty($_GET['removed'])) $message = 'Elemento rimosso dal carrello.';
if (!empty($_GET['emptied'])) $message = 'Carrello svuotato completamente.';
if (!empty($_GET['error'])) $error = $_GET['error'];

// US-15: Rimozione singolo elemento
if (!empty($_GET['remove_id'])) {
    $remId = (int)$_GET['remove_id'];
    try {
        $chk = db()->prepare('SELECT id FROM bookings WHERE id = ? AND user_id = ? AND status_id = 1');
        $chk->execute([$remId, $userId]);
        if ($chk->fetch()) {
            $delA = db()->prepare('DELETE FROM booking_amenities WHERE booking_id = ?');
            $delA->execute([$remId]);
            $delB = db()->prepare('DELETE FROM bookings WHERE id = ?');
            $delB->execute([$remId]);
            header('Location: ' . $config['base'] . '/cart.php?removed=1');
            exit;
        }
    } catch (Exception $e) {
        $error = 'Errore nella rimozione: ' . $e->getMessage();
    }
}

// US-15: Svuota intero carrello
if (!empty($_GET['empty'])) {
    try {
        $stmtIds = db()->prepare('SELECT id FROM bookings WHERE user_id = ? AND status_id = 1');
        $stmtIds->execute([$userId]);
        $ids = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $delA = db()->prepare("DELETE FROM booking_amenities WHERE booking_id IN ($placeholders)");
            $delA->execute($ids);
            $delB = db()->prepare("DELETE FROM bookings WHERE id IN ($placeholders)");
            $delB->execute($ids);
        }
        header('Location: ' . $config['base'] . '/cart.php?emptied=1');
        exit;
    } catch (Exception $e) {
        $error = 'Errore durante lo svuotamento: ' . $e->getMessage();
    }
}

$page = new_page('customers', 'frame-public');
$block = new_block('cart');
$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    $stmtCart = db()->prepare('
        SELECT b.*, r.room_number, rc.name as category_name, rc.image_url, rc.base_price
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_categories rc ON r.room_category_id = rc.id
        WHERE b.user_id = ? AND b.status_id = 1
        ORDER BY b.created_at DESC
    ');
    $stmtCart->execute([$userId]);
    $cartItems = $stmtCart->fetchAll();

    $grandTotal = 0.0;
    $hasItems = !empty($cartItems);

    foreach ($cartItems as $item) {
        $block->setContent('cart_list.id', (string)$item['id']);
        $block->setContent('cart_list.category_name', htmlspecialchars($item['category_name']));
        $block->setContent('cart_list.room_number', htmlspecialchars($item['room_number']));
        $block->setContent('cart_list.image_url', htmlspecialchars($item['image_url'] ?? 'deluxe_singola.jpg'));
        
        $chkIn = date('d/m/Y', strtotime($item['check_in_date']));
        $chkOut = date('d/m/Y', strtotime($item['check_out_date']));
        $days = max(1, (int)round((strtotime($item['check_out_date']) - strtotime($item['check_in_date'])) / 86400));
        
        $block->setContent('cart_list.dates', "$chkIn -> $chkOut ($days notti)");
        $block->setContent('cart_list.price', number_format((float)$item['total_price'], 2, ',', '.'));
        
        // Calcola tempo rimanente (30 min dalla creazione)
        $expireTimestamp = strtotime($item['created_at']) + (30 * 60);
        $diffMin = max(0, (int)round(($expireTimestamp - time()) / 60));
        $block->setContent('cart_list.expires_in', (string)$diffMin);

        $grandTotal += (float)$item['total_price'];
    }

    $block->setContent('has_items', $hasItems ? '1' : '');
    $block->setContent('grand_total', number_format($grandTotal, 2, ',', '.'));

    // Carica anche gli amenities acquistabili extra durante il checkout
    $stmtAm = db()->query('SELECT * FROM amenities ORDER BY price ASC');
    $allAmen = $stmtAm->fetchAll();
    foreach ($allAmen as $am) {
        $block->setContent('extra_amenities.id', (string)$am['id']);
        $block->setContent('extra_amenities.name', htmlspecialchars($am['name']));
        $block->setContent('extra_amenities.description', htmlspecialchars($am['description'] ?? ''));
        $block->setContent('extra_amenities.price', number_format((float)$am['price'], 2, ',', '.'));
    }

} catch (Exception $e) {
    $block->setContent('error', 'Errore caricamento carrello: ' . $e->getMessage());
    $block->setContent('has_items', '');
    $block->setContent('grand_total', '0,00');
}

$page->setContent('body', $block->get());
$page->close();
