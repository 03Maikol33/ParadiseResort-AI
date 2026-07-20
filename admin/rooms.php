<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_admin();

$message = '';
$error = '';

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
            if ($id > 0) {
                $chk = db()->prepare('SELECT id FROM rooms WHERE room_number = ? AND id != ?');
                $chk->execute([$roomNumber, $id]);
                if ($chk->fetch()) {
                    $error = 'Esiste già un\'altra camera con questo numero (' . htmlspecialchars($roomNumber) . ').';
                } else {
                    $upd = db()->prepare('UPDATE rooms SET room_number = ?, category_id = ?, floor = ?, status = ? WHERE id = ?');
                    $upd->execute([$roomNumber, $catId, $floor, $status, $id]);
                    $message = 'Camera fis. #' . $id . ' aggiornata con successo.';
                }
            } else {
                $chk = db()->prepare('SELECT id FROM rooms WHERE room_number = ?');
                $chk->execute([$roomNumber]);
                if ($chk->fetch()) {
                    $error = 'Esiste già una camera con questo numero (' . htmlspecialchars($roomNumber) . ').';
                } else {
                    $ins = db()->prepare('INSERT INTO rooms (room_number, category_id, floor, status) VALUES (?, ?, ?, ?)');
                    $ins->execute([$roomNumber, $catId, $floor, $status]);
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

$editRoom = null;
if (!empty($_GET['edit_id'])) {
    $stmt = db()->prepare('SELECT * FROM rooms WHERE id = ?');
    $stmt->execute([(int)$_GET['edit_id']]);
    $editRoom = $stmt->fetch();
}

$block->setContent('form_room_id', $editRoom ? (string)$editRoom['id'] : '');
$block->setContent('edit_id', $editRoom ? (string)$editRoom['id'] : '');

$block->setContent('edit_room_number', $editRoom ? htmlspecialchars($editRoom['room_number']) : '');
$block->setContent('edit_floor', $editRoom ? (string)$editRoom['floor'] : '1');
$block->setContent('form_title', $editRoom ? 'Modifica Camera #' . $editRoom['id'] : 'Registra Nuova Camera');
$block->setContent('btn_label', $editRoom ? 'Salva Modifiche' : 'Registra Stanza');

try {
    $stmtCat = db()->query('SELECT id, name FROM room_categories ORDER BY id ASC');
    $categories = $stmtCat->fetchAll();
    $catOptions = '';
    $filterCatOptions = '';
    foreach ($categories as $c) {
        $selected = ($editRoom && $editRoom['category_id'] == $c['id']) ? ' selected' : '';
        $catOptions .= '<option value="' . $c['id'] . '"' . $selected . '>' . htmlspecialchars($c['name']) . '</option>';
        
        $filterSelected = (isset($_GET['filter_cat']) && $_GET['filter_cat'] == $c['id']) ? ' selected' : '';
        $filterCatOptions .= '<option value="' . $c['id'] . '"' . $filterSelected . '>' . htmlspecialchars($c['name']) . '</option>';
    }
    $block->setContent('category_options', $catOptions);
    $block->setContent('filter_category_options', $filterCatOptions);

    $statuses = [
        'Available' => 'Available (Disponibile / Pulita)',
        'Occupied' => 'Occupied (Occupata dai Clienti)',
        'Dirty' => 'Dirty (Da Pulire)',
        'Maintenance' => 'Maintenance (In Manutenzione / Guasto)'
    ];
    $statusOptions = '';
    $filterStatusOptions = '';
    foreach ($statuses as $val => $label) {
        $selected = ($editRoom && $editRoom['status'] === $val) ? ' selected' : '';
        $statusOptions .= '<option value="' . $val . '"' . $selected . '>' . $label . '</option>';
        
        $filterSelected = (isset($_GET['filter_status']) && $_GET['filter_status'] === $val) ? ' selected' : '';
        $filterStatusOptions .= '<option value="' . $val . '"' . $filterSelected . '>' . htmlspecialchars($val) . '</option>';
    }
    $block->setContent('status_options', $statusOptions);
    $block->setContent('filter_status_options', $filterStatusOptions);

    $stmtFloors = db()->query('SELECT DISTINCT floor FROM rooms ORDER BY floor ASC');
    $floors = $stmtFloors->fetchAll(PDO::FETCH_COLUMN);
    $filterFloorOptions = '';
    foreach ($floors as $f) {
        $filterSelected = (isset($_GET['filter_floor']) && $_GET['filter_floor'] !== '' && $_GET['filter_floor'] == $f) ? ' selected' : '';
        $filterFloorOptions .= '<option value="' . $f . '"' . $filterSelected . '>Piano ' . htmlspecialchars($f) . '</option>';
    }
    $block->setContent('filter_floor_options', $filterFloorOptions);

    $sql = '
        SELECT r.*, rc.name as category_name
        FROM rooms r
        JOIN room_categories rc ON r.category_id = rc.id
        WHERE 1=1
    ';
    $params = [];

    if (!empty($_GET['filter_cat'])) {
        $sql .= ' AND r.category_id = ?';
        $params[] = (int)$_GET['filter_cat'];
    }
    if (!empty($_GET['filter_status'])) {
        $sql .= ' AND r.status = ?';
        $params[] = $_GET['filter_status'];
    }
    if (isset($_GET['filter_floor']) && $_GET['filter_floor'] !== '') {
        $sql .= ' AND r.floor = ?';
        $params[] = (int)$_GET['filter_floor'];
    }

    $sql .= ' ORDER BY r.floor ASC, r.room_number ASC';

    $stmtRooms = db()->prepare($sql);
    $stmtRooms->execute($params);
    $rooms = $stmtRooms->fetchAll();

    foreach ($rooms as $r) {
        $block->setContent('room_list.id', (string)$r['id']);
        $block->setContent('room_list.room_number', htmlspecialchars($r['room_number']));
        $block->setContent('room_list.category_name', htmlspecialchars($r['category_name']));
        $block->setContent('room_list.floor', (string)$r['floor']);
        
        $badge = 'badge bg-success';
        if ($r['status'] === 'Maintenance') $badge = 'badge bg-warning text-dark';
        if ($r['status'] === 'Occupied') $badge = 'badge bg-danger';
        if ($r['status'] === 'Dirty') $badge = 'badge bg-secondary';
        
        $block->setContent('room_list.status_badge', '<span class="' . $badge . '">' . htmlspecialchars($r['status']) . '</span>');
    }
} catch (Exception $e) {
    $block->setContent('error', 'Errore DB: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
