<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_service();

$message = '';
$error = '';

// Mapping: DB English values <-> Italian labels
const STATUS_MAP = [
    'available'   => 'Disponibile',
    'cleaning'    => 'Da Pulire',
    'maintenance' => 'In Manutenzione',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_room_status') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? 'available');   // form sends DB English value now

    $allowed = ['available', 'cleaning'];  // receptionist cannot set maintenance
    if ($roomId > 0 && in_array($newStatus, $allowed, true)) {
        try {
            $chk = db()->prepare('SELECT status FROM rooms WHERE id = ?');
            $chk->execute([$roomId]);
            $currentStatus = $chk->fetchColumn();

            if ($currentStatus === 'maintenance') {
                $error = 'Questa camera è in manutenzione. Solo l\'amministratore può cambiarne lo stato.';
            } else {
                $upd = db()->prepare('UPDATE rooms SET status = ? WHERE id = ?');
                $upd->execute([$newStatus, $roomId]);
                $message = 'Stato operativo della camera #' . $roomId . ' aggiornato con successo.';
            }
        } catch (Exception $e) {
            $error = 'Errore durante l\'aggiornamento: ' . $e->getMessage();
        }
    } else {
        if ($newStatus === 'maintenance') {
            $error = 'Solo l\'amministratore può impostare una camera in manutenzione. Usa "Segnalazione Guasto".';
        }
    }
}

$filterStatus = trim($_GET['status'] ?? '');   // now expected to be DB English value
$filterFloor  = trim($_GET['floor'] ?? '');
$filterCat    = trim($_GET['filter_cat'] ?? '');

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Receptionist', 'receptionist');

$block = new_block('receptionist_rooms');
$block->setContent('message', $message);
$block->setContent('error', $error);

// Selected-state helpers for the status dropdown (now use DB English keys)
$block->setContent('sel_avail', $filterStatus === 'available'     ? 'selected' : '');
$block->setContent('sel_dirty', $filterStatus === 'cleaning'      ? 'selected' : '');
$block->setContent('sel_maint', $filterStatus === 'maintenance'   ? 'selected' : '');

// Floor filter options
$stmtFloors = db()->query('SELECT DISTINCT floor FROM rooms ORDER BY floor ASC');
$floors = $stmtFloors->fetchAll(PDO::FETCH_COLUMN);
$filterFloorOptions = '';
foreach ($floors as $f) {
    $sel = ($filterFloor !== '' && $filterFloor == $f) ? ' selected' : '';
    $filterFloorOptions .= '<option value="' . $f . '"' . $sel . '>Piano ' . htmlspecialchars($f) . '</option>';
}
$block->setContent('filter_floor_options', $filterFloorOptions);

// Category filter options
$stmtCat = db()->query('SELECT id, name FROM room_categories ORDER BY id ASC');
$categories = $stmtCat->fetchAll();
$filterCatOptions = '';
foreach ($categories as $c) {
    $sel = ($filterCat !== '' && $filterCat == $c['id']) ? ' selected' : '';
    $filterCatOptions .= '<option value="' . $c['id'] . '"' . $sel . '>' . htmlspecialchars($c['name']) . '</option>';
}
$block->setContent('filter_category_options', $filterCatOptions);

try {
    $sql = '
        SELECT r.*, rc.name as category_name, rc.capacity
        FROM rooms r
        JOIN room_categories rc ON r.category_id = rc.id
        WHERE 1=1
    ';
    $params = [];

    if ($filterStatus !== '') {
        $sql .= ' AND r.status = :fStatus';
        $params[':fStatus'] = $filterStatus;
    }
    if ($filterFloor !== '') {
        $sql .= ' AND r.floor = :fFloor';
        $params[':fFloor'] = (int)$filterFloor;
    }
    if ($filterCat !== '') {
        $sql .= ' AND r.category_id = :fCat';
        $params[':fCat'] = (int)$filterCat;
    }

    $sql .= ' ORDER BY r.floor ASC, r.room_number ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll();

    $cntAvail = 0;
    $cntDirty = 0;
    $cntMaint = 0;

    foreach ($rooms as $rm) {
        $dbStatus = $rm['status'];   // 'available', 'cleaning', 'maintenance'

        if ($dbStatus === 'available')    $cntAvail++;
        if ($dbStatus === 'cleaning')     $cntDirty++;
        if ($dbStatus === 'maintenance')  $cntMaint++;

        $italianLabel = STATUS_MAP[$dbStatus] ?? ucfirst($dbStatus);

        $block->setContent('room_rows.id', (string)$rm['id']);
        $block->setContent('room_rows.number', htmlspecialchars($rm['room_number']));
        $block->setContent('room_rows.floor', (string)$rm['floor']);
        $block->setContent('room_rows.category_name', htmlspecialchars($rm['category_name']));
        $block->setContent('room_rows.capacity', (string)$rm['capacity']);

        $badge = 'badge bg-success';
        if ($dbStatus === 'cleaning')    $badge = 'badge bg-secondary';
        if ($dbStatus === 'maintenance') $badge = 'badge bg-warning text-dark';

        $block->setContent('room_rows.status_badge', '<span class="' . $badge . ' px-3 py-2">' . htmlspecialchars($italianLabel) . '</span>');

        if ($dbStatus === 'maintenance') {
            $block->setContent('room_rows.action_form', '<span class="text-danger small fw-bold">Solo admin</span>');
        } else {
            $selAvail   = ($dbStatus === 'available') ? 'selected' : '';
            $selDirty   = ($dbStatus === 'cleaning')  ? 'selected' : '';

            $formHTML = '
                <select name="status" class="form-select form-select-sm" style="width: 145px;">
                  <option value="available" ' . $selAvail . '>Disponibile (Pulita)</option>
                  <option value="cleaning"  ' . $selDirty . '>Da Pulire</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary" title="Aggiorna"><i class="bi bi-check2"></i></button>
            ';
            $block->setContent('room_rows.action_form', $formHTML);
        }
    }

    $block->setContent('count_available',    (string)$cntAvail);
    $block->setContent('count_occupied',     '0');   // 'occupied' is not a DB status; rooms are occupied via bookings
    $block->setContent('count_dirty',        (string)$cntDirty);
    $block->setContent('count_maintenance',  (string)$cntMaint);

} catch (Exception $e) {
    $block->setContent('error', 'Errore DB: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
