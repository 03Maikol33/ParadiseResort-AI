<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_service();

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Receptionist', 'receptionist');

$block = new_block('dashboard');

// Statistiche rapide Receptionist
try {
    $stmtBook = db()->query('SELECT COUNT(*) as cnt FROM bookings WHERE status_id != 1');
    $block->setContent('stat_bookings', number_format((int)$stmtBook->fetch()['cnt']));

    $stmtRev = db()->query('SELECT COUNT(*) as cnt FROM bookings WHERE status_id = 2');
    $block->setContent('stat_revenue', number_format((int)$stmtRev->fetch()['cnt']) . ' In attesa');

    $stmtUsers = db()->query('SELECT COUNT(*) as cnt FROM user_gruppi WHERE group_id = 3');
    $block->setContent('stat_guests', number_format((int)$stmtUsers->fetch()['cnt']));

    $stmtTickets = db()->query('SELECT COUNT(*) as cnt FROM maintenance_tickets WHERE status_id IN (1, 2)');
    $block->setContent('stat_tickets', number_format((int)$stmtTickets->fetch()['cnt']));
} catch (Exception $e) {
    $block->setContent('stat_bookings', '0');
    $block->setContent('stat_revenue', '0 In attesa');
    $block->setContent('stat_guests', '0');
    $block->setContent('stat_tickets', '0');
}

// Ultime 5 prenotazioni
try {
    $stmtRecent = db()->query('
        SELECT b.*, u.first_name, u.last_name, r.room_number, s.name as status_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        JOIN booking_statuses s ON b.status_id = s.id
        WHERE b.status_id != 1
        ORDER BY b.created_at DESC
        LIMIT 5
    ');
    $recents = $stmtRecent->fetchAll();
    foreach ($recents as $rec) {
        $block->setContent('recent_bookings.id', (string)$rec['id']);
        $block->setContent('recent_bookings.guest', htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']));
        $block->setContent('recent_bookings.room', htmlspecialchars($rec['room_number']));
        $block->setContent('recent_bookings.dates', date('d/m/Y', strtotime($rec['check_in_date'])) . ' -> ' . date('d/m/Y', strtotime($rec['check_out_date'])));
        $block->setContent('recent_bookings.price', '€ ' . number_format((float)$rec['total_price'], 2, ',', '.'));
        
        $badgeClass = 'badge bg-secondary';
        if ($rec['status_id'] == 2) $badgeClass = 'badge bg-warning text-dark';
        if ($rec['status_id'] == 3) $badgeClass = 'badge bg-primary';
        if ($rec['status_id'] == 4) $badgeClass = 'badge bg-danger';
        if ($rec['status_id'] == 5) $badgeClass = 'badge bg-success';
        
        $block->setContent('recent_bookings.badge_class', $badgeClass);
        $block->setContent('recent_bookings.status_name', htmlspecialchars($rec['status_name']));
    }
} catch (Exception $e) {}

$page->setContent('body', $block->get());
$page->close();
