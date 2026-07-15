<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_service();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_room_status') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Available');

    if ($roomId > 0 && in_array($status, ['Available', 'Occupied', 'Dirty', 'Maintenance'])) {
        try {
            $upd = db()->prepare('UPDATE rooms SET status = ? WHERE id = ?');
            $upd->execute([$status, $roomId]);
            $message = 'Stato operativo della camera #' . $roomId . ' aggiornato con successo.';
        } catch (Exception $e) {
            $error = 'Errore durante l\'aggiornamento: ' . $e->getMessage();
        }
    }
}

$search = trim($_GET['search'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Receptionist', 'receptionist');

$block = new_block('receptionist_rooms');
$block->setContent('message', $message);
$block->setContent('error', $error);
$block->setContent('val_search', htmlspecialchars($search));
$block->setContent('sel_avail', $filterStatus === 'Available' ? 'selected' : '');
$block->setContent('sel_occ', $filterStatus === 'Occupied' ? 'selected' : '');
$block->setContent('sel_dirty', $filterStatus === 'Dirty' ? 'selected' : '');
$block->setContent('sel_maint', $filterStatus === 'Maintenance' ? 'selected' : '');

try {
    $sql = '
        SELECT r.*, rc.name as category_name, rc.capacity
        FROM rooms r
        JOIN room_categories rc ON r.room_category_id = rc.id
        WHERE 1=1
    ';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (r.room_number LIKE :s OR rc.name LIKE :s)';
        $params[':s'] = "%$search%";
    }
    if ($filterStatus !== '') {
        $sql .= ' AND r.status = :fStatus';
        $params[':fStatus'] = $filterStatus;
    }

    $sql .= ' ORDER BY r.floor ASC, r.room_number ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll();

    $cntAvail = 0;
    $cntOcc = 0;
    $cntDirty = 0;
    $cntMaint = 0;

    foreach ($rooms as $rm) {
        if ($rm['status'] === 'Available') $cntAvail++;
        if ($rm['status'] === 'Occupied') $cntOcc++;
        if ($rm['status'] === 'Dirty') $cntDirty++;
        if ($rm['status'] === 'Maintenance') $cntMaint++;

        $block->setContent('room_rows.id', (string)$rm['id']);
        $block->setContent('room_rows.number', htmlspecialchars($rm['room_number']));
        $block->setContent('room_rows.floor', (string)$rm['floor']);
        $block->setContent('room_rows.category_name', htmlspecialchars($rm['category_name']));
        $block->setContent('room_rows.capacity', (string)$rm['capacity']);
        
        $badge = 'badge bg-success';
        if ($rm['status'] === 'Occupied') $badge = 'badge bg-danger';
        if ($rm['status'] === 'Dirty') $badge = 'badge bg-secondary';
        if ($rm['status'] === 'Maintenance') $badge = 'badge bg-warning text-dark';
        $block->setContent('room_rows.status_badge', '<span class="' . $badge . ' px-3 py-2">' . htmlspecialchars($rm['status']) . '</span>');

        $block->setContent('room_rows.sel_avail', $rm['status'] === 'Available' ? 'selected' : '');
        $block->setContent('room_rows.sel_occ', $rm['status'] === 'Occupied' ? 'selected' : '');
        $block->setContent('room_rows.sel_dirty', $rm['status'] === 'Dirty' ? 'selected' : '');
        $block->setContent('room_rows.sel_maint', $rm['status'] === 'Maintenance' ? 'selected' : '');
    }

    $block->setContent('count_available', (string)$cntAvail);
    $block->setContent('count_occupied', (string)$cntOcc);
    $block->setContent('count_dirty', (string)$cntDirty);
    $block->setContent('count_maintenance', (string)$cntMaint);
} catch (Exception $e) {
    $block->setContent('error', 'Errore DB: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
