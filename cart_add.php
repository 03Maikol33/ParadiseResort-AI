<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

if (empty($_SESSION['user']['id'])) {
    $catId = (int)($_POST['room_category_id'] ?? $_GET['room_category_id'] ?? 1);
    $chkIn = $_POST['check_in_date'] ?? '';
    $chkOut = $_POST['check_out_date'] ?? '';
    $retUrl = $config['base'] . '/room_details.php?id=' . $catId . '&check_in=' . urlencode($chkIn) . '&check_out=' . urlencode($chkOut);
    header('Location: ' . $config['base'] . '/login.php?redirect=' . urlencode($retUrl));
    exit;
}

$catId = (int)($_POST['room_category_id'] ?? 0);
$checkIn = trim($_POST['check_in_date'] ?? '');
$checkOut = trim($_POST['check_out_date'] ?? '');
$guestsCount = (int)($_POST['guests_count'] ?? 2);

if ($catId <= 0 || $checkIn === '' || $checkOut === '') {
    header('Location: ' . $config['base'] . '/rooms.php?error=' . urlencode('Date e categoria sono obbligatorie.'));
    exit;
}

$today = date('Y-m-d');
if ($checkIn < $today || $checkOut <= $checkIn) {
    header('Location: ' . $config['base'] . '/room_details.php?id=' . $catId . '&error=' . urlencode('Date di Check-In e Check-Out non valide. Check-Out deve essere successivo al Check-In.'));
    exit;
}

// US-12: Allocazione automatica della prima camera fisica libera nella categoria
try {
    $sqlAvail = '
        SELECT r.id, rc.base_price
        FROM rooms r
        JOIN room_categories rc ON r.category_id = rc.id
        WHERE r.category_id = :catId
          AND r.status != \'Maintenance\'
          AND r.id NOT IN (
              SELECT b.room_id
              FROM bookings b
              WHERE b.status_id != 4
                AND (b.check_in_date < :checkOut AND b.check_out_date > :checkIn)
          )
        LIMIT 1
    ';
    $stmt = db()->prepare($sqlAvail);
    $stmt->execute([
        ':catId' => $catId,
        ':checkIn' => $checkIn,
        ':checkOut' => $checkOut
    ]);
    $room = $stmt->fetch();

    if (!$room) {
        $msg = 'Spiacenti, non ci sono camere di questa categoria disponibili dal ' . date('d/m/Y', strtotime($checkIn)) . ' al ' . date('d/m/Y', strtotime($checkOut)) . '. Seleziona altre date o un\'altra categoria.';
        header('Location: ' . $config['base'] . '/room_details.php?id=' . $catId . '&error=' . urlencode($msg));
        exit;
    }

    $roomId = (int)$room['id'];
    $basePrice = (float)$room['base_price'];
    $days = max(1, (int)round((strtotime($checkOut) - strtotime($checkIn)) / 86400));
    
    // Calcoliamo il costo degli extra selezionati
    $extras = $_POST['extras'] ?? [];
    $extrasPrice = 0.0;
    $selectedExtras = [];
    if (!empty($extras) && is_array($extras)) {
        $ids = array_map('intval', array_keys($extras));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtEx = db()->prepare("SELECT id, price FROM amenities WHERE id IN ($placeholders)");
        $stmtEx->execute($ids);
        $selectedExtras = $stmtEx->fetchAll();
        foreach ($selectedExtras as $ex) {
            $extrasPrice += (float)$ex['price'];
        }
    }
    
    $totalPrice = ($days * $basePrice) + $extrasPrice;

    // US-13: Inserimento nel carrello (status_id = 1)
    $ins = db()->prepare('
        INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, status_id, created_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ');
    $ins->execute([
        $_SESSION['user']['id'],
        $roomId,
        $checkIn,
        $checkOut,
        $totalPrice
    ]);

    $bookingId = (int)db()->lastInsertId();
    if (!empty($selectedExtras)) {
        $insAmen = db()->prepare('INSERT INTO booking_amenities (booking_id, amenity_id, quantity) VALUES (?, ?, 1)');
        foreach ($selectedExtras as $ex) {
            $insAmen->execute([$bookingId, (int)$ex['id']]);
        }
    }

    header('Location: ' . $config['base'] . '/cart.php?added=1');
    exit;

} catch (Exception $e) {
    header('Location: ' . $config['base'] . '/room_details.php?id=' . $catId . '&error=' . urlencode('Errore durante la verifica disponibilità: ' . $e->getMessage()));
    exit;
}
