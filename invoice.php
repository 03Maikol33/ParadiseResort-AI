<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

require_login();

$invId = (int)($_GET['id'] ?? 0);
$success = !empty($_GET['success']) ? 'Prenotazione confermata con successo! Ecco la tua ricevuta di soggiorno.' : '';

try {
    $stmt = db()->prepare('
        SELECT i.*, b.user_id, b.check_in_date, b.check_out_date, b.total_price as booking_total,
               r.room_number, rc.name as category_name, rc.base_price,
               u.first_name, u.last_name, u.email, u.phone,
               bs.name as status_name
        FROM invoices i
        JOIN bookings b ON i.booking_id = b.id
        JOIN rooms r ON b.room_id = r.id
        JOIN room_categories rc ON r.category_id = rc.id
        JOIN users u ON b.user_id = u.id
        JOIN booking_statuses bs ON b.status_id = bs.id
        WHERE i.id = ?
    ');
    $stmt->execute([$invId]);
    $inv = $stmt->fetch();

    if (!$inv) {
        header('Location: ' . $config['base'] . '/profile.php?error=' . urlencode('Fattura non trovata.'));
        exit;
    }

    if (!is_admin() && !is_receptionist() && (int)$inv['user_id'] !== (int)$_SESSION['user']['id']) {
        header('Location: ' . $config['base'] . '/index.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: ' . $config['base'] . '/profile.php?error=' . urlencode('Errore DB: ' . $e->getMessage()));
    exit;
}

$page = new_page('customers', 'frame-public');
$block = new_block('invoice');

$block->setContent('success', $success);
$invNumber = 'INV-' . date('Y', strtotime($inv['invoice_date'])) . '-' . str_pad($inv['booking_id'], 5, '0', STR_PAD_LEFT);
$block->setContent('invoice.id', (string)$inv['id']);
$block->setContent('invoice.number', htmlspecialchars($invNumber));
$block->setContent('invoice.issued_date', date('d/m/Y H:i', strtotime($inv['invoice_date'])));
$block->setContent('invoice.status_name', htmlspecialchars($inv['status_name']));

$block->setContent('guest.name', htmlspecialchars($inv['first_name'] . ' ' . $inv['last_name']));
$block->setContent('guest.email', htmlspecialchars($inv['email']));
$block->setContent('guest.phone', htmlspecialchars($inv['phone'] ?? '-'));

$block->setContent('room.category_name', htmlspecialchars($inv['category_name']));
$block->setContent('room.number', htmlspecialchars($inv['room_number']));
$chkIn = date('d/m/Y', strtotime($inv['check_in_date']));
$chkOut = date('d/m/Y', strtotime($inv['check_out_date']));
$days = max(1, (int)round((strtotime($inv['check_out_date']) - strtotime($inv['check_in_date'])) / 86400));
$block->setContent('room.dates', "$chkIn -> $chkOut ($days notti)");

$roomSubtotal = $days * (float)$inv['base_price'];
$block->setContent('room.subtotal', number_format($roomSubtotal, 2, ',', '.'));

// Amenities aggiunti
try {
    $stmtAm = db()->prepare('
        SELECT a.name, a.price, ba.quantity
        FROM booking_amenities ba
        JOIN amenities a ON ba.amenity_id = a.id
        WHERE ba.booking_id = ?
    ');
    $stmtAm->execute([$inv['booking_id']]);
    $amenities = $stmtAm->fetchAll();
    foreach ($amenities as $am) {
        $block->setContent('extras_list.name', htmlspecialchars($am['name']));
        $block->setContent('extras_list.price', number_format((float)$am['price'], 2, ',', '.'));
    }
} catch (Exception $e) {}

$block->setContent('invoice.grand_total', number_format((float)$inv['total_amount'], 2, ',', '.'));

$page->setContent('body', $block->get());
$page->close();
