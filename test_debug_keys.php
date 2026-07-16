<?php
require 'include/bootstrap.inc.php';
$_SESSION['user'] = ['id' => 1, 'group_id' => 1, 'role_path' => 'admin', 'name' => 'Admin User'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/progettoAi/admin/segnalazioni.php';
$_SERVER['SERVER_NAME'] = 'localhost';
$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Admin', 'admin');
$block = new_block('segnalazioni');

$stmtRooms = db()->query('SELECT r.id, r.room_number, rc.name as category_name FROM rooms r JOIN room_categories rc ON r.category_id = rc.id ORDER BY r.room_number ASC');
$rooms = $stmtRooms->fetchAll();
$options = '<option value="0">-- Seleziona Camera --</option>';
foreach ($rooms as $rm) {
    $options .= '<option value="' . $rm['id'] . '">Camera ' . htmlspecialchars($rm['room_number']) . ' (' . htmlspecialchars($rm['category_name']) . ')</option>';
}
$block->setContent('room_options', $options);

$stmtTk = db()->query('SELECT mt.*, r.room_number, rc.name as category_name, u.first_name, u.last_name FROM maintenance_tickets mt JOIN rooms r ON mt.room_id = r.id JOIN room_categories rc ON r.category_id = rc.id LEFT JOIN users u ON mt.reported_by_user_id = u.id ORDER BY mt.created_at DESC');
$tickets = $stmtTk->fetchAll();
foreach ($tickets as $tk) {
    $block->setContent('tk_rows.id', (string)$tk['id']);
    $block->setContent('tk_rows.room_number', htmlspecialchars($tk['room_number']));
    $block->setContent('tk_rows.category_name', htmlspecialchars($tk['category_name']));
    $block->setContent('tk_rows.issue', htmlspecialchars($tk['issue_description']));
    $block->setContent('tk_rows.date', date('d/m/Y H:i', strtotime($tk['created_at'])));
    $block->setContent('tk_rows.author', $tk['first_name'] ? htmlspecialchars($tk['first_name'] . ' ' . $tk['last_name']) : 'Sistema / Guest');
    $block->setContent('tk_rows.priority_badge', '<span class="badge bg-info text-dark px-3 py-1">Normale</span>');
}

$out = $block->get();
echo "TR count inside block->get(): " . substr_count($out, '<tr') . "\n";






