<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

$capacity = (int)($_GET['capacity'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);
$roomType = trim($_GET['room_type'] ?? '');
$checkIn  = trim($_GET['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? '');

$checkInDb = preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $checkIn) ? DateTime::createFromFormat('d/m/Y', $checkIn)->format('Y-m-d') : $checkIn;
$checkOutDb = preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $checkOut) ? DateTime::createFromFormat('d/m/Y', $checkOut)->format('Y-m-d') : $checkOut;

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
if ($roomType !== '') {
    $sql .= ' AND id = :roomType';
    $params[':roomType'] = $roomType;
}
if ($checkIn !== '' && $checkOut !== '') {
    $sql .= ' AND id IN (
        SELECT category_id FROM rooms WHERE status = "available" AND id NOT IN (
            SELECT room_id FROM bookings 
            WHERE status_id IN (1, 2, 3) 
            AND (check_in_date < :checkOut AND check_out_date > :checkIn)
        )
    )';
    $params[':checkIn'] = $checkInDb;
    $params[':checkOut'] = $checkOutDb;
}

$sql .= ' ORDER BY base_price ASC';

$page = new_page('customers', 'frame-public');
$block = new_block('rooms');

$block->setContent('val_capacity', $capacity > 0 ? (string)$capacity : '');
$block->setContent('val_max_price', $maxPrice > 0 ? (string)$maxPrice : '');
$block->setContent('val_check_in', htmlspecialchars($checkIn));
$block->setContent('val_check_out', htmlspecialchars($checkOut));

try {
    $stmtTypes = db()->query('SELECT id, name FROM room_categories ORDER BY name');
    $optionsHtml = '';
    foreach ($stmtTypes->fetchAll() as $rt) {
        $selected = ($roomType === (string)$rt['id']) ? 'selected' : '';
        $optionsHtml .= '<option value="' . htmlspecialchars($rt['id']) . '" ' . $selected . '>' . htmlspecialchars($rt['name']) . '</option>';
    }
    $block->setContent('room_options', $optionsHtml);

    $categories = [];
    if (!isset($_GET['room_type']) || ($roomType !== '' && $checkIn !== '' && $checkOut !== '')) {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $categories = $stmt->fetchAll();
    }
    
    if (count($categories) > 0) {
        $block->setContent('has_results', '1');
    }

    foreach ($categories as $cat) {
        $block->setContent('rooms_list.base', $GLOBALS['config']['base'] ?? '');
        $block->setContent('rooms_list.id', (string)$cat['id']);
        $block->setContent('rooms_list.name', htmlspecialchars($cat['name']));
        $block->setContent('rooms_list.description', htmlspecialchars(substr($cat['description'] ?? '', 0, 150) . '...'));
        $block->setContent('rooms_list.base_price', number_format((float)$cat['base_price'], 2, ',', '.'));
        $block->setContent('rooms_list.capacity', (string)$cat['capacity']);
        $imageUrl = !empty($cat['image_url']) ? $cat['image_url'] : 'deluxe_singola.jpg';
        $block->setContent('rooms_list.image_url', htmlspecialchars($imageUrl));
        
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
