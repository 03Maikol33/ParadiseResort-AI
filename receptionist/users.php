<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_service();

$search = trim($_GET['search'] ?? '');

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Receptionist', 'receptionist');

$block = new_block('receptionist_users');
$block->setContent('val_search', htmlspecialchars($search));

try {
    $sql = '
        SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at,
               (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.status_id IN (2, 3)) as active_bookings_count,
               (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.status_id != 1) as total_bookings_count
        FROM users u
        JOIN user_gruppi ug ON u.id = ug.user_id
        WHERE ug.group_id = 3
    ';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (u.first_name LIKE :s1 OR u.last_name LIKE :s2 OR u.email LIKE :s3 OR u.phone LIKE :s4)';
        $params[':s1'] = "%$search%";
        $params[':s2'] = "%$search%";
        $params[':s3'] = "%$search%";
        $params[':s4'] = "%$search%";
    }

    $sql .= ' ORDER BY u.last_name ASC, u.first_name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    foreach ($users as $usr) {
        $block->setContent('user_rows.id', (string)$usr['id']);
        $block->setContent('user_rows.first_name', htmlspecialchars($usr['first_name']));
        $block->setContent('user_rows.last_name', htmlspecialchars($usr['last_name']));
        $block->setContent('user_rows.email', htmlspecialchars($usr['email']));
        $block->setContent('user_rows.phone', htmlspecialchars($usr['phone'] ?? '-'));
        
        $actCount = (int)$usr['active_bookings_count'];
        $badge = $actCount > 0 ? 'badge bg-success' : 'badge bg-secondary';
        $block->setContent('user_rows.active_badge', '<span class="' . $badge . ' px-3 py-2 fs-6"><i class="bi bi-calendar-check me-1"></i> ' . $actCount . ' attive</span>');
        $block->setContent('user_rows.total_count', (string)$usr['total_bookings_count']);
    }
} catch (Exception $e) {
    // Gestione errore
}

$page->setContent('body', $block->get());
$page->close();
