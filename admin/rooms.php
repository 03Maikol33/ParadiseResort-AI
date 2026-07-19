<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_admin();

$statusMap = [
    'Available' => 'available',
    'Occupied' => 'occupied',
    'Dirty' => 'cleaning',
    'Maintenance' => 'maintenance'
];
$reverseMap = [
    'available' => 'Available',
    'occupied' => 'Occupied',
    'cleaning' => 'Dirty',
    'maintenance' => 'Maintenance'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_room') {
    $id = (int)($_POST['id'] ?? 0);
    $roomNumber = trim($_POST['room_number'] ?? '');
    $catId = (int)($_POST['room_category_id'] ?? 1);
    $floor = (int)($_POST['floor'] ?? 1);
    $status = trim($_POST['status'] ?? 'Available');

    if ($roomNumber === '' || $catId <= 0) {
        $error = 'Numero Camera e Categoria sono obbligatori.';
    } else {
        try {
            $dbStatus = $statusMap[$status] ?? 'available';
            if ($id > 0) {
                $upd = db()->prepare('UPDATE rooms SET room_number = ?, category_id = ?, floor = ?, status = ? WHERE id = ?');
                $upd->execute([$roomNumber, $catId, $floor, $dbStatus, $id]);
                $message = 'Camera fis. #' . $id . ' aggiornata con successo.';
            } else {
                $chk = db()->prepare('SELECT id FROM rooms WHERE room_number = ?');
                $chk->execute([$roomNumber]);
                if ($chk->fetch()) {
                    $error = 'Esiste già una camera con questo numero (' . htmlspecialchars($roomNumber) . ').';
                } else {
                    $ins = db()->prepare('INSERT INTO rooms (room_number, category_id, floor, status) VALUES (?, ?, ?, ?)');
                    $ins->execute([$roomNumber, $catId, $floor, $dbStatus]);
                    $message = 'Nuova camera fisica (' . htmlspecialchars($roomNumber) . ') registrata nell\'inventario.';
                }
            }
        } catch (Exception $e) {
            $error = 'Errore durante il salvataggio: ' . $e->getMessage();
        }
    }
} elseif (!empty($_GET['del_id'])) {
    $delId = (int)$_GET['del_id'];
    try {
        $del = db()->prepare('DELETE FROM rooms WHERE id = ?');
        $del->execute([$delId]);
        $message = 'Camera #' . $delId . ' eliminata con successo.';
    } catch (Exception $e) {
        $error = 'Impossibile eliminare: la camera ha prenotazioni o manutenzioni collegate.';
    }
}

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Amministratore', 'admin');

$block = new_block('rooms');
$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    $stmtCat = db()->query('SELECT id, name FROM room_categories ORDER BY id ASC');
    $categories = $stmtCat->fetchAll();
    $catOptions = '';
    foreach ($categories as $c) {
        $catOptions .= '<option value="' . $c['id'] . '">' . htmlspecialchars($c['name']) . '</option>';
    }
    $block->setContent('category_options', $catOptions);

    $stmtRooms = db()->query('
        SELECT r.*, rc.name as category_name
        FROM rooms r
        JOIN room_categories rc ON r.category_id = rc.id
        ORDER BY r.floor ASC, r.room_number ASC
    ');
    $rooms = $stmtRooms->fetchAll();

    foreach ($rooms as $r) {
        $dbStatus = $r['status'];
        $tmplStatus = $reverseMap[$dbStatus] ?? 'Available';

        $block->setContent('room_list.id', (string)$r['id']);
        $block->setContent('room_list.room_number', htmlspecialchars($r['room_number']));
        $block->setContent('room_list.category_name', htmlspecialchars($r['category_name']));
        $block->setContent('room_list.floor', (string)$r['floor']);
        
        $badge = 'badge bg-success';
        if ($tmplStatus === 'Maintenance') $badge = 'badge bg-warning text-dark';
        if ($tmplStatus === 'Occupied') $badge = 'badge bg-danger';
        if ($tmplStatus === 'Dirty') $badge = 'badge bg-secondary';
        
        $block->setContent('room_list.status_badge', '<span class="' . $badge . '">' . htmlspecialchars($tmplStatus) . '</span>');
    }
} catch (Exception $e) {
    $block->setContent('error', 'Errore DB: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
