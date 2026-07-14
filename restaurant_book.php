<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

require_login();
$userId = (int)$_SESSION['user']['id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_booking') {
    $resDate = trim($_POST['reservation_date'] ?? '');
    $timeSlot = trim($_POST['time_slot'] ?? 'Dinner');
    $guestsCount = (int)($_POST['guests_count'] ?? 2);
    $specialRequests = trim($_POST['special_requests'] ?? '');

    $today = date('Y-m-d');
    if ($resDate < $today || $guestsCount <= 0 || !in_array($timeSlot, ['Lunch', 'Dinner'])) {
        $error = 'Data o turno non validi. Seleziona una data da oggi in poi e un numero di ospiti corretto.';
    } else {
        try {
            $mealType = ($timeSlot === 'Lunch') ? 'Pranzo' : 'Cena';
            // Controlla il limite di capienza (es. 50 coperti per turno)
            $chk = db()->prepare('SELECT SUM(guests) as total_guests FROM restaurant_reservations WHERE reservation_date = ? AND meal_type = ? AND status != \'Cancelled\'');
            $chk->execute([$resDate, $mealType]);
            $row = $chk->fetch();
            $currentGuests = (int)($row['total_guests'] ?? 0);

            if ($currentGuests + $guestsCount > 60) {
                $error = 'Spiacenti, per il turno di ' . ($timeSlot === 'Lunch' ? 'Pranzo' : 'Cena') . ' del ' . date('d/m/Y', strtotime($resDate)) . ' abbiamo raggiunto la capienza massima (60 coperti). Seleziona un\'altra data o turno.';
            } else {
                $resTime = ($timeSlot === 'Lunch') ? '13:00:00' : '20:00:00';
                $ins = db()->prepare('INSERT INTO restaurant_reservations (user_id, reservation_date, meal_type, reservation_time, guests, status) VALUES (?, ?, ?, ?, ?, \'Confirmed\')');
                $ins->execute([$userId, $resDate, $mealType, $resTime, $guestsCount]);
                $message = 'Tavolo per ' . $guestsCount . ' persone prenotato con successo per il ' . date('d/m/Y', strtotime($resDate)) . ' (' . ($timeSlot === 'Lunch' ? 'Pranzo' : 'Cena') . ')!';
            }
        } catch (Exception $e) {
            $error = 'Errore salvataggio prenotazione tavolo: ' . $e->getMessage();
        }
    }
} elseif (!empty($_GET['cancel_id'])) {
    $cancelId = (int)$_GET['cancel_id'];
    try {
        $upd = db()->prepare('UPDATE restaurant_reservations SET status = \'Cancelled\' WHERE id = ? AND user_id = ?');
        $upd->execute([$cancelId, $userId]);
        $message = 'Prenotazione tavolo annullata.';
    } catch (Exception $e) {}
}

$page = new_page('customers', 'frame-public');
$block = new_block('restaurant_book');
$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    $stmt = db()->prepare('SELECT * FROM restaurant_reservations WHERE user_id = ? ORDER BY reservation_date DESC, created_at DESC');
    $stmt->execute([$userId]);
    $resList = $stmt->fetchAll();

    $hasRes = !empty($resList);
    $block->setContent('has_res', $hasRes ? '1' : '');

    foreach ($resList as $r) {
        $block->setContent('res_rows.id', (string)$r['id']);
        $block->setContent('res_rows.date', date('d/m/Y', strtotime($r['reservation_date'])));
        $block->setContent('res_rows.slot', $r['meal_type'] === 'Pranzo' ? 'Pranzo (12:30 - 14:30)' : 'Cena (19:30 - 22:30)');
        $block->setContent('res_rows.guests', (string)$r['guests']);
        $block->setContent('res_rows.notes', '-');
        
        $badge = $r['status'] === 'Confirmed' ? 'badge badge-success py-2 px-3' : 'badge badge-danger py-2 px-3';
        $block->setContent('res_rows.status_badge', '<span class="' . $badge . '">' . htmlspecialchars($r['status']) . '</span>');

        if ($r['status'] === 'Confirmed' && $r['reservation_date'] >= date('Y-m-d')) {
            $block->setContent('res_rows.cancel_btn', '<a href="' . $config['base'] . '/restaurant_book.php?cancel_id=' . $r['id'] . '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Annullare questa prenotazione tavolo?\');"><i class="fas fa-times"></i></a>');
        } else {
            $block->setContent('res_rows.cancel_btn', '-');
        }
    }
} catch (Exception $e) {
    $block->setContent('has_res', '');
}

$page->setContent('body', $block->get());
$page->close();
