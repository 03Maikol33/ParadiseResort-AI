<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_service();

$message = '';
$error = '';

// Maps removed since we use native Italian ENUMs

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_room_status') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Disponibile');

    if ($roomId > 0 && in_array($status, ['Disponibile', 'Occupata', 'Da Pulire'])) {
        try {
            $chk = db()->prepare('SELECT status FROM rooms WHERE id = ?');
            $chk->execute([$roomId]);
            $currentStatus = $chk->fetchColumn();
            
            if ($currentStatus === 'In Manutenzione') {
                $error = 'Questa camera è in manutenzione. Solo l\'amministratore può cambiarne lo stato.';
            } else {
                $upd = db()->prepare('UPDATE rooms SET status = ? WHERE id = ?');
                $upd->execute([$status, $roomId]);
                $message = 'Stato operativo della camera #' . $roomId . ' aggiornato con successo.';
            }
        } catch (Exception $e) {
            $error = 'Errore durante l\'aggiornamento: ' . $e->getMessage();
        }
    } else {
        if ($status === 'In Manutenzione') {
            $error = 'Solo l\'amministratore può impostare una camera in manutenzione. Usa "Segnalazione Guasto".';
        }
    }
}

$filterStatus = trim($_GET['status'] ?? '');
$filterFloor = trim($_GET['floor'] ?? '');
$filterCat = trim($_GET['filter_cat'] ?? '');

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Receptionist', 'receptionist');

$block = new_block('receptionist_rooms');
$block->setContent('message', $message);
$block->setContent('error', $error);
$block->setContent('sel_avail', $filterStatus === 'Disponibile' ? 'selected' : '');
$block->setContent('sel_occ', $filterStatus === 'Occupata' ? 'selected' : '');
$block->setContent('sel_dirty', $filterStatus === 'Da Pulire' ? 'selected' : '');
$block->setContent('sel_maint', $filterStatus === 'In Manutenzione' ? 'selected' : '');

$stmtFloors = db()->query('SELECT DISTINCT floor FROM rooms ORDER BY floor ASC');
$floors = $stmtFloors->fetchAll(PDO::FETCH_COLUMN);
$filterFloorOptions = '';
foreach ($floors as $f) {
    $filterSelected = ($filterFloor !== '' && $filterFloor == $f) ? ' selected' : '';
    $filterFloorOptions .= '<option value="' . $f . '"' . $filterSelected . '>Piano ' . htmlspecialchars($f) . '</option>';
}
$block->setContent('filter_floor_options', $filterFloorOptions);

$stmtCat = db()->query('SELECT id, name FROM room_categories ORDER BY id ASC');
$categories = $stmtCat->fetchAll();
$filterCatOptions = '';
foreach ($categories as $c) {
    $filterSelected = ($filterCat !== '' && $filterCat == $c['id']) ? ' selected' : '';
    $filterCatOptions .= '<option value="' . $c['id'] . '"' . $filterSelected . '>' . htmlspecialchars($c['name']) . '</option>';
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
    $cntOcc = 0;
    $cntDirty = 0;
    $cntMaint = 0;

    foreach ($rooms as $rm) {
        $dbStatus = $rm['status'];

        if ($dbStatus === 'Disponibile') $cntAvail++;
        if ($dbStatus === 'Occupata') $cntOcc++;
        if ($dbStatus === 'Da Pulire') $cntDirty++;
        if ($dbStatus === 'In Manutenzione') $cntMaint++;

        $block->setContent('room_rows.id', (string)$rm['id']);
        $block->setContent('room_rows.number', htmlspecialchars($rm['room_number']));
        $block->setContent('room_rows.floor', (string)$rm['floor']);
        $block->setContent('room_rows.category_name', htmlspecialchars($rm['category_name']));
        $block->setContent('room_rows.capacity', (string)$rm['capacity']);
        
        $badge = 'badge bg-success';
        if ($dbStatus === 'Occupata') $badge = 'badge bg-danger';
        if ($dbStatus === 'Da Pulire') $badge = 'badge bg-secondary';
        if ($dbStatus === 'In Manutenzione') $badge = 'badge bg-warning text-dark';
        $block->setContent('room_rows.status_badge', '<span class="' . $badge . ' px-3 py-2">' . htmlspecialchars($dbStatus) . '</span>');

        if ($rm['status'] === 'In Manutenzione') {
            $block->setContent('room_rows.action_form', '<span class="text-danger small fw-bold">Solo admin</span>');
        } else {
            $selAvail = $rm['status'] === 'Disponibile' ? 'selected' : '';
            $selDirty = $rm['status'] === 'Da Pulire' ? 'selected' : '';
            $selOcc = $rm['status'] === 'Occupata' ? 'selected' : '';
            
            $formHTML = '
                <select name="status" class="form-select form-select-sm" style="width: 145px;">
                  <option value="Disponibile" '.$selAvail.'>Disponibile (Pulita)</option>
                  <option value="Da Pulire" '.$selDirty.'>Da Pulire</option>
                  <option value="Occupata" '.$selOcc.'>Occupata</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary" title="Aggiorna"><i class="bi bi-check2"></i></button>
            ';
            $block->setContent('room_rows.action_form', $formHTML);
        }
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
