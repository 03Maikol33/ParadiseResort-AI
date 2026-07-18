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

$page = new_page('customers', 'frame-public');
$block = new_block('room_details');

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
    $stmtAmen = db()->prepare('
        SELECT a.name, a.description, rca.is_included, rca.extra_price
        FROM amenities a
        JOIN room_category_amenities rca ON a.id = rca.amenity_id
        WHERE rca.room_category_id = ?
        ORDER BY rca.is_included DESC, a.name ASC
    ');
    $stmtAmen->execute([$categoryId]);
    $amenities = $stmtAmen->fetchAll();

    foreach ($amenities as $am) {
        $block->setContent('amenity_list.name', htmlspecialchars($am['name']));
        $block->setContent('amenity_list.description', htmlspecialchars($am['description'] ?? ''));
        $inc = (int)$am['is_included'] === 1;
        $block->setContent('amenity_list.badge', $inc ? '<span class="badge badge-success">Incluso Gratis</span>' : '<span class="badge badge-warning text-dark">+ € ' . number_format((float)$am['extra_price'], 2, ',', '.') . ' / gg</span>');
    }
} catch (Exception $e) {}

$page->setContent('body', $block->get());
$page->close();
