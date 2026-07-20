<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

$page = new_page('customers', 'frame-public');
$block = new_block('services');

try {
    $stmt = db()->query('SELECT * FROM amenities ORDER BY price ASC');
    $amenities = $stmt->fetchAll();

    foreach ($amenities as $am) {
        $block->setContent('amenity_list.id', (string)$am['id']);
        $block->setContent('amenity_list.name', htmlspecialchars($am['name']));
        $block->setContent('amenity_list.description', htmlspecialchars($am['description'] ?? ''));
        $block->setContent('amenity_list.price', number_format((float)$am['price'], 2, ',', '.'));
        $localImage = 'spa.jpg';
        if (stripos($am['name'], 'Colazione') !== false) $localImage = 'colazione.jpg';
        if (stripos($am['name'], 'Navetta') !== false) $localImage = 'navetta.jpg';
        $block->setContent('amenity_list.image_url', $localImage);
    }
} catch (Exception $e) {
    // Gestione errore
}

$page->setContent('body', $block->get());
$page->close();
