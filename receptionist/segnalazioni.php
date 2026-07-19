<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_service();

$userId = (int)$_SESSION['user']['id'];
$message = '';
$error = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'opened') {
    $message = 'Nuova segnalazione tecnica aperta con successo dal Receptionist (Camera in Manutenzione).';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_ticket') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $issue = trim($_POST['issue_description'] ?? '');
    $priority = trim($_POST['priority'] ?? 'Medium');

    if ($roomId <= 0 || $issue === '') {
        $error = 'Seleziona una stanza e inserisci la descrizione del guasto.';
    } else {
        try {
            $ins = db()->prepare('INSERT INTO maintenance_tickets (room_id, reported_by_user_id, status_id, issue_description, created_at) VALUES (?, ?, 1, ?, NOW())');
            $ins->execute([$roomId, $userId, $issue]);
            
            // Imposta la camera in manutenzione se la priorità è alta o media
            if ($priority === 'High' || $priority === 'Medium') {
                $updRoom = db()->prepare('UPDATE rooms SET status = \'Maintenance\' WHERE id = ?');
                $updRoom->execute([$roomId]);
            }

            header('Location: ' . $config['base'] . '/receptionist/segnalazioni.php?msg=opened');
            exit;
        } catch (Exception $e) {
            $error = 'Errore apertura ticket: ' . $e->getMessage();
        }
    }
}

$page = new_page('administration', 'frame-private');
$block = new_block('segnalazioni');
setup_backoffice_page($page, 'Receptionist', 'receptionist', $block);

$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    // Opzioni stanze per il form
    $stmtRooms = db()->query('
        SELECT r.id, r.room_number, rc.name as category_name
        FROM rooms r
        JOIN room_categories rc ON r.category_id = rc.id
        ORDER BY r.room_number ASC
    ');
    $rooms = $stmtRooms->fetchAll();
    $options = '<option value="0">-- Seleziona Camera --</option>';
    foreach ($rooms as $rm) {
        $options .= '<option value="' . $rm['id'] . '">Camera ' . htmlspecialchars($rm['room_number']) . ' (' . htmlspecialchars($rm['category_name']) . ')</option>';
    }
    $block->setContent('room_options', $options);

    // Tabella di tutti i ticket
    $stmtTk = db()->query('
        SELECT mt.*, r.room_number, rc.name as category_name, u.first_name, u.last_name
        FROM maintenance_tickets mt
        JOIN rooms r ON mt.room_id = r.id
        JOIN room_categories rc ON r.category_id = rc.id
        LEFT JOIN users u ON mt.reported_by_user_id = u.id
        ORDER BY mt.created_at DESC
    ');
    $tickets = $stmtTk->fetchAll();

    foreach ($tickets as $tk) {
        $block->setContent('tk_rows.id', (string)$tk['id']);
        $block->setContent('tk_rows.room_number', htmlspecialchars($tk['room_number']));
        $block->setContent('tk_rows.category_name', htmlspecialchars($tk['category_name']));
        $block->setContent('tk_rows.issue', htmlspecialchars($tk['issue_description']));
        $block->setContent('tk_rows.date', date('d/m/Y H:i', strtotime($tk['created_at'])));
        $block->setContent('tk_rows.author', $tk['first_name'] ? htmlspecialchars($tk['first_name'] . ' ' . $tk['last_name']) : 'Sistema / Guest');
        
        $prio = 'badge bg-info text-dark';
        $block->setContent('tk_rows.priority_badge', '<span class="' . $prio . ' px-3 py-1">Normale</span>');
    }
} catch (Exception $e) {
    $block->setContent('error', 'Errore DB: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
