<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_admin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    $resId = (int)($_POST['res_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Confirmed');

    if ($resId > 0) {
        try {
            if ($status === 'Cancelled') {
                $del = db()->prepare('DELETE FROM restaurant_reservations WHERE id = ?');
                $del->execute([$resId]);
                $message = 'Prenotazione tavolo #' . $resId . ' eliminata con successo.';
            } elseif ($status === 'Confirmed') {
                $upd = db()->prepare('UPDATE restaurant_reservations SET status = ? WHERE id = ?');
                $upd->execute(['Confirmed', $resId]);
                $message = 'Stato della prenotazione tavolo #' . $resId . ' aggiornato a Confermata.';
            }
        } catch (Exception $e) {
            $error = 'Errore: ' . $e->getMessage();
        }
    }
}

$filterDate = trim($_GET['date'] ?? '');
$filterSlot = trim($_GET['slot'] ?? '');

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Amministratore', 'admin');

$block = new_block('restaurant_bookings');
$block->setContent('message', $message);
$block->setContent('error', $error);
$block->setContent('val_date', htmlspecialchars($filterDate));
$block->setContent('sel_lunch', $filterSlot === 'Pranzo' ? 'selected' : '');
$block->setContent('sel_dinner', $filterSlot === 'Cena' ? 'selected' : '');

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
        $sql .= ' AND r.meal_type = :fSlot';
        $params[':fSlot'] = $filterSlot;
    }

    $sql .= ' ORDER BY r.meal_type DESC, r.created_at DESC';

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
        $block->setContent('res_rows.slot', $r['meal_type'] === 'Pranzo' ? 'Pranzo' : 'Cena');
        $block->setContent('res_rows.guests', (string)$r['guests']);
        $block->setContent('res_rows.notes', htmlspecialchars($r['special_requests'] ?? '-'));
        
        $badgeText = '';
        if ($r['status'] === 'Confirmed') {
            $badgeText = 'Confermata';
            $badge = 'badge bg-success';
        } elseif ($r['status'] === 'Pending') {
            $badgeText = 'In Attesa';
            $badge = 'badge bg-warning text-dark';
        } else {
            $badgeText = 'Cancellata';
            $badge = 'badge bg-danger';
        }
        $block->setContent('res_rows.status_badge', '<span class="' . $badge . '">' . $badgeText . '</span>');

        if ($r['status'] === 'Confirmed') {
            if ($r['meal_type'] === 'Pranzo') $totalGuestsLunch += (int)$r['guests'];
            if ($r['meal_type'] === 'Cena') $totalGuestsDinner += (int)$r['guests'];
        }
    }

    $block->setContent('total_lunch', (string)$totalGuestsLunch);
    $block->setContent('total_dinner', (string)$totalGuestsDinner);
} catch (Exception $e) {
    $block->setContent('error', 'Errore DB: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
