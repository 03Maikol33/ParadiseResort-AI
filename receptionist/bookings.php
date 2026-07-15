<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_service();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    $bkId = (int)($_POST['booking_id'] ?? 0);
    $newStatus = (int)($_POST['status_id'] ?? 2);

    if ($bkId > 0 && in_array($newStatus, [2, 3, 4, 5])) {
        try {
            $stmtGet = db()->prepare('SELECT room_id FROM bookings WHERE id = ?');
            $stmtGet->execute([$bkId]);
            $bkRow = $stmtGet->fetch();

            $upd = db()->prepare('UPDATE bookings SET status_id = ? WHERE id = ?');
            $upd->execute([$newStatus, $bkId]);
            $message = 'Stato della prenotazione #' . $bkId . ' aggiornato con successo dal Receptionist.';

            // Se completata (Check-out avvenuto), segna la camera fisica come Dirty
            if ($newStatus === 5 && !empty($bkRow['room_id'])) {
                $updRoom = db()->prepare('UPDATE rooms SET status = \'Dirty\' WHERE id = ?');
                $updRoom->execute([$bkRow['room_id']]);
            }
        } catch (Exception $e) {
            $error = 'Errore durante l\'aggiornamento dello stato: ' . $e->getMessage();
        }
    } else {
        $error = 'Parametri non validi.';
    }
}

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Receptionist', 'receptionist');

$block = new_block('bookings');
$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    $stmt = db()->query('
        SELECT b.*, r.room_number, rc.name as category_name,
               u.first_name, u.last_name, u.email, u.phone,
               bs.name as status_name, i.id as invoice_id, i.invoice_number
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_categories rc ON r.room_category_id = rc.id
        JOIN users u ON b.user_id = u.id
        JOIN booking_statuses bs ON b.status_id = bs.id
        LEFT JOIN invoices i ON b.id = i.booking_id
        WHERE b.status_id != 1
        ORDER BY b.check_in_date DESC
    ');
    $bookings = $stmt->fetchAll();

    foreach ($bookings as $bk) {
        $block->setContent('booking_rows.id', (string)$bk['id']);
        $block->setContent('booking_rows.guest_name', htmlspecialchars($bk['first_name'] . ' ' . $bk['last_name']));
        $block->setContent('booking_rows.guest_email', htmlspecialchars($bk['email']));
        $block->setContent('booking_rows.guest_phone', htmlspecialchars($bk['phone'] ?? '-'));
        $block->setContent('booking_rows.category_name', htmlspecialchars($bk['category_name']));
        $block->setContent('booking_rows.room_number', htmlspecialchars($bk['room_number']));
        
        $chkIn = date('d/m/Y', strtotime($bk['check_in_date']));
        $chkOut = date('d/m/Y', strtotime($bk['check_out_date']));
        $block->setContent('booking_rows.dates', "$chkIn -> $chkOut");
        $block->setContent('booking_rows.total_price', number_format((float)$bk['total_price'], 2, ',', '.'));
        
        $badge = 'badge bg-secondary';
        if ($bk['status_id'] == 2) $badge = 'badge bg-warning text-dark';
        if ($bk['status_id'] == 3) $badge = 'badge bg-primary';
        if ($bk['status_id'] == 4) $badge = 'badge bg-danger';
        if ($bk['status_id'] == 5) $badge = 'badge bg-success';
        $block->setContent('booking_rows.status_badge', '<span class="' . $badge . '">' . htmlspecialchars($bk['status_name']) . '</span>');

        $block->setContent('booking_rows.sel_2', $bk['status_id'] == 2 ? 'selected' : '');
        $block->setContent('booking_rows.sel_3', $bk['status_id'] == 3 ? 'selected' : '');
        $block->setContent('booking_rows.sel_4', $bk['status_id'] == 4 ? 'selected' : '');
        $block->setContent('booking_rows.sel_5', $bk['status_id'] == 5 ? 'selected' : '');

        if (!empty($bk['invoice_id'])) {
            $block->setContent('booking_rows.invoice_link', '<a href="' . $config['base'] . '/invoice.php?id=' . $bk['invoice_id'] . '" target="_blank" class="btn btn-sm btn-outline-dark" title="Apri Fattura"><i class="bi bi-file-earmark-text"></i></a>');
        } else {
            $block->setContent('booking_rows.invoice_link', '-');
        }
    }
} catch (Exception $e) {
    $block->setContent('error', 'Errore DB: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
