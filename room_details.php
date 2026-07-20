<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

$categoryId = (int)($_GET['id'] ?? 1);
$checkIn    = trim($_GET['check_in'] ?? '');
$checkOut   = trim($_GET['check_out'] ?? '');

try {
    $stmt = db()->prepare('SELECT * FROM room_categories WHERE id = ?');
    $stmt->execute([$categoryId]);
    $cat = $stmt->fetch();

    if (!$cat) {
        header('Location: ' . $config['base'] . '/rooms.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: ' . $config['base'] . '/rooms.php');
    exit;
}

$error = trim($_GET['error'] ?? '');
$message = trim($_GET['message'] ?? '');

$page = new_page('customers', 'frame-public');
$block = new_block('room_details');

$block->setContent('error', $error !== '' ? '<div class="alert alert-danger mb-4">' . htmlspecialchars($error) . '</div>' : '');
$block->setContent('message', $message !== '' ? '<div class="alert alert-success mb-4">' . htmlspecialchars($message) . '</div>' : '');

$block->setContent('room.id', (string)$cat['id']);
$block->setContent('room.name', htmlspecialchars($cat['name']));
$block->setContent('room.description', htmlspecialchars($cat['description'] ?? ''));
$block->setContent('room.base_price', number_format((float)$cat['base_price'], 2, ',', '.'));
$block->setContent('room.capacity', (string)$cat['capacity']);
$imageUrl = !empty($cat['image_url']) ? $cat['image_url'] : 'deluxe_singola.jpg';
$block->setContent('room.image_url', htmlspecialchars($imageUrl));

$error   = trim($_GET['error'] ?? '');
$message = trim($_GET['msg'] ?? '');
$block->setContent('error', htmlspecialchars($error));
$block->setContent('message', htmlspecialchars($message));
$block->setContent('val_check_in', htmlspecialchars($checkIn));
$block->setContent('val_check_out', htmlspecialchars($checkOut));

// Servizi inclusi e facoltativi per la categoria
try {
    $stmtAmen = db()->query('SELECT * FROM amenities WHERE is_suspended = 0 ORDER BY price ASC');
    $amenities = $stmtAmen->fetchAll();

    foreach ($amenities as $am) {
        $block->setContent('amenity_list.name', htmlspecialchars(get_amenity_emoji($am['name']) . $am['name']));
        $block->setContent('amenity_list.description', htmlspecialchars($am['description'] ?? ''));
        $block->setContent('amenity_list.badge', '<span class="badge badge-warning text-dark">+ € ' . number_format((float)$am['price'], 2, ',', '.') . ' / gg</span>');
        
        $block->setContent('opt_amenity.id', (string)$am['id']);
        $block->setContent('opt_amenity.name', htmlspecialchars(get_amenity_emoji($am['name']) . $am['name']));
        $block->setContent('opt_amenity.price', number_format((float)$am['price'], 2, ',', '.'));
        $block->setContent('opt_amenity.raw_price', (string)$am['price']);
    }
} catch (Exception $e) {}

$page->setContent('body', $block->get());
$page->close();
