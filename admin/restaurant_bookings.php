<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
if (!is_admin()) {
    header('Location: ' . $config['base'] . '/login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    $resId = (int)($_POST['res_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Confirmed');

    if ($resId > 0 && in_array($status, ['Confirmed', 'Cancelled'])) {
        try {
            $upd = db()->prepare('UPDATE restaurant_reservations SET status = ? WHERE id = ?');
            $upd->execute([$status, $resId]);
            $message = 'Stato della prenotazione tavolo #' . $resId . ' aggiornato con successo.';
        } catch (Exception $e) {
            $error = 'Errore aggiornamento: ' . $e->getMessage();
        }
    }
}

$filterDate = trim($_GET['date'] ?? date('Y-m-d'));
$filterSlot = trim($_GET['slot'] ?? '');

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Amministratore', 'admin');

$block = new_block('restaurant_bookings');
$block->setContent('message', $message);
$block->setContent('error', $error);
$block->setContent('val_date', htmlspecialchars($filterDate));
$block->setContent('sel_lunch', $filterSlot === 'Lunch' ? 'selected' : '');
$block->setContent('sel_dinner', $filterSlot === 'Dinner' ? 'selected' : '');

try {
    $sql = '
        SELECT r.*, u.first_name, u.last_name, u.email, u.phone
        FROM restaurant_reservations r
        JOIN users u ON r.user_id = u.id
        WHERE 1=1
    ';
    $params = [];

    if ($filterDate !== '') {
        $sql .= ' AND r.reservation_date = :fDate';
        $params[':fDate'] = $filterDate;
    }
    if ($filterSlot !== '') {
        $sql .= ' AND r.time_slot = :fSlot';
        $params[':fSlot'] = $filterSlot;
    }

    $sql .= ' ORDER BY r.time_slot DESC, r.created_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $resList = $stmt->fetchAll();

    $totalGuestsLunch = 0;
    $totalGuestsDinner = 0;

    foreach ($resList as $r) {
        $block->setContent('res_rows.id', (string)$r['id']);
        $block->setContent('res_rows.guest_name', htmlspecialchars($r['first_name'] . ' ' . $r['last_name']));
        $block->setContent('res_rows.guest_phone', htmlspecialchars($r['phone'] ?? '-'));
        $block->setContent('res_rows.guest_email', htmlspecialchars($r['email']));
        $block->setContent('res_rows.date', date('d/m/Y', strtotime($r['reservation_date'])));
        $block->setContent('res_rows.slot', $r['time_slot'] === 'Lunch' ? 'Pranzo' : 'Cena');
        $block->setContent('res_rows.guests', (string)$r['guests_count']);
        $block->setContent('res_rows.notes', htmlspecialchars($r['special_requests'] ?? '-'));
        
        $badge = $r['status'] === 'Confirmed' ? 'badge bg-success' : 'badge bg-danger';
        $block->setContent('res_rows.status_badge', '<span class="' . $badge . '">' . htmlspecialchars($r['status']) . '</span>');

        if ($r['status'] === 'Confirmed') {
            if ($r['time_slot'] === 'Lunch') $totalGuestsLunch += (int)$r['guests_count'];
            if ($r['time_slot'] === 'Dinner') $totalGuestsDinner += (int)$r['guests_count'];
        }
    }

    $block->setContent('total_lunch', (string)$totalGuestsLunch);
    $block->setContent('total_dinner', (string)$totalGuestsDinner);
} catch (Exception $e) {
    $block->setContent('error', 'Errore DB: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
