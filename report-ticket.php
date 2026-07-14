<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

require_login();
$userId = (int)$_SESSION['user']['id'];
$message = '';
$error = '';

if (!empty($_GET['success'])) $message = 'Segnalazione guasto inoltrata con successo al nostro team tecnico. Ti contatteremo a breve!';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_ticket') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $issue = trim($_POST['issue_description'] ?? '');
    $priority = trim($_POST['priority'] ?? 'Medium');

    if ($roomId <= 0 || $issue === '') {
        $error = 'Seleziona una delle tue stanze e descrivi il problema riscontrato.';
    } else {
        try {
            // Verifica che la stanza corrisponda a un soggiorno del cliente
            $chk = db()->prepare('SELECT id FROM bookings WHERE user_id = ? AND room_id = ? AND status_id != 1');
            $chk->execute([$userId, $roomId]);
            if (!$chk->fetch()) {
                $error = 'Stanza non valida per il tuo account.';
            } else {
                $ins = db()->prepare('INSERT INTO maintenance_tickets (room_id, reported_by, issue_description, priority, status, created_at) VALUES (?, ?, ?, ?, \'Open\', NOW())');
                $ins->execute([$roomId, $userId, $issue, $priority]);
                header('Location: ' . $config['base'] . '/report-ticket.php?success=1');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Errore salvataggio ticket: ' . $e->getMessage();
        }
    }
}

$page = new_page('customers', 'frame-public');
$block = new_block('report-ticket');
$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    $stmtRooms = db()->prepare('
        SELECT DISTINCT r.id, r.room_number, rc.name as category_name
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_categories rc ON r.room_category_id = rc.id
        WHERE b.user_id = ? AND b.status_id != 1
        ORDER BY r.room_number ASC
    ');
    $stmtRooms->execute([$userId]);
    $userRooms = $stmtRooms->fetchAll();

    $hasRooms = !empty($userRooms);
    $block->setContent('has_rooms', $hasRooms ? '1' : '');

    $options = '';
    foreach ($userRooms as $ur) {
        $options .= '<option value="' . $ur['id'] . '">Camera ' . htmlspecialchars($ur['room_number']) . ' (' . htmlspecialchars($ur['category_name']) . ')</option>';
    }
    $block->setContent('room_options', $options);

    // I miei ticket attivi
    $stmtMy = db()->prepare('
        SELECT mt.*, r.room_number
        FROM maintenance_tickets mt
        JOIN rooms r ON mt.room_id = r.id
        WHERE mt.reported_by = ?
        ORDER BY mt.created_at DESC
    ');
    $stmtMy->execute([$userId]);
    $myTickets = $stmtMy->fetchAll();

    $hasTickets = !empty($myTickets);
    $block->setContent('has_tickets', $hasTickets ? '1' : '');

    foreach ($myTickets as $tk) {
        $block->setContent('ticket_list.id', (string)$tk['id']);
        $block->setContent('ticket_list.room_number', htmlspecialchars($tk['room_number']));
        $block->setContent('ticket_list.issue', htmlspecialchars($tk['issue_description']));
        $block->setContent('ticket_list.date', date('d/m/Y H:i', strtotime($tk['created_at'])));
        
        $prio = 'badge badge-info text-dark';
        if ($tk['priority'] === 'High') $prio = 'badge badge-danger';
        if ($tk['priority'] === 'Medium') $prio = 'badge badge-warning text-dark';
        $block->setContent('ticket_list.priority_badge', '<span class="' . $prio . '">' . htmlspecialchars($tk['priority']) . '</span>');
    }
} catch (Exception $e) {
    $block->setContent('has_rooms', '');
    $block->setContent('has_tickets', '');
}

$page->setContent('body', $block->get());
$page->close();
