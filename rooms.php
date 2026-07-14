<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

$capacity = (int)($_GET['capacity'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);
$filter   = trim($_GET['filter'] ?? '');
$checkIn  = trim($_GET['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? '');

$sql = 'SELECT * FROM room_categories WHERE 1=1';
$params = [];

if ($capacity > 0) {
    $sql .= ' AND capacity >= :capacity';
    $params[':capacity'] = $capacity;
}
if ($maxPrice > 0) {
    $sql .= ' AND base_price <= :maxPrice';
    $params[':maxPrice'] = $maxPrice;
}
if ($filter !== '') {
    $sql .= ' AND (name LIKE :filter OR description LIKE :filter)';
    $params[':filter'] = "%$filter%";
}

$sql .= ' ORDER BY base_price ASC';

$page = new_page('customers', 'frame-public');
$block = new_block('rooms');

$block->setContent('val_capacity', $capacity > 0 ? (string)$capacity : '');
$block->setContent('val_max_price', $maxPrice > 0 ? (string)$maxPrice : '');
$block->setContent('val_filter', htmlspecialchars($filter));
$block->setContent('val_check_in', htmlspecialchars($checkIn));
$block->setContent('val_check_out', htmlspecialchars($checkOut));

try {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();

    foreach ($categories as $cat) {
        $block->setContent('rooms_list.id', (string)$cat['id']);
        $block->setContent('rooms_list.name', htmlspecialchars($cat['name']));
        $block->setContent('rooms_list.description', htmlspecialchars(substr($cat['description'] ?? '', 0, 150) . '...'));
        $block->setContent('rooms_list.base_price', number_format((float)$cat['base_price'], 2, ',', '.'));
        $block->setContent('rooms_list.capacity', (string)$cat['capacity']);
        $block->setContent('rooms_list.image_url', htmlspecialchars($cat['image_url'] ?? 'deluxe_singola.jpg'));
        
        $paramsStr = '?id=' . $cat['id'];
        if ($checkIn !== '') $paramsStr .= '&check_in=' . urlencode($checkIn);
        if ($checkOut !== '') $paramsStr .= '&check_out=' . urlencode($checkOut);
        $block->setContent('rooms_list.link_params', $paramsStr);
    }
} catch (Exception $e) {
    // Gestione errore
}

$page->setContent('body', $block->get());
$page->close();
