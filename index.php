<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

$page = new_page('customers', 'frame-public');
$block = new_block('home');

try {
    $stmtRooms = db()->query('SELECT * FROM room_categories ORDER BY base_price ASC LIMIT 4');
    $featuredRooms = $stmtRooms->fetchAll();
    
    foreach ($featuredRooms as $i => $room) {
        $block->setContent('featured_rooms.base', $GLOBALS['config']['base'] ?? '');
        $block->setContent('featured_rooms.id', (string)$room['id']);
        $block->setContent('featured_rooms.name', htmlspecialchars($room['name']));
        $block->setContent('featured_rooms.base_price', number_format((float)$room['base_price'], 2, ',', '.'));
        $block->setContent('featured_rooms.capacity', (string)$room['capacity']);
        $imageUrl = !empty($room['image_url']) ? $room['image_url'] : 'deluxe_singola.jpg';
        $block->setContent('featured_rooms.image_url', htmlspecialchars($imageUrl));
        $block->setContent('featured_rooms.description', htmlspecialchars($room['description'] ?? ''));
    }
} catch (Exception $e) {
    // Fallback se il DB non avesse ancora le categorie
}

try {
    $stmtReviews = db()->query('
        SELECT r.rating, r.comment, u.first_name, u.last_name, rc.name as room_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        JOIN room_categories rc ON r.room_category_id = rc.id
        ORDER BY r.created_at DESC
        LIMIT 3
    ');
    $reviews = $stmtReviews->fetchAll();
    foreach ($reviews as $rev) {
        $block->setContent('reviews.author', htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']));
        $block->setContent('reviews.room_name', htmlspecialchars($rev['room_name']));
        $block->setContent('reviews.comment', htmlspecialchars($rev['comment']));
        $stars = str_repeat('★', (int)$rev['rating']) . str_repeat('☆', 5 - (int)$rev['rating']);
        $block->setContent('reviews.stars', $stars);
    }
} catch (Exception $e) {
    // Ignora errori se la tabella recensioni è vuota
}

$page->setContent('body', $block->get());
$page->close();
