<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

$sql = 'SELECT * FROM room_categories WHERE id = 1';
$stmt = db()->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll();

$page = new_page('customers', 'frame-public');
$block = new_block('rooms');

foreach ($categories as $cat) {
    $block->setContent('rooms_list.id', (string)$cat['id']);
    $block->setContent('rooms_list.name', htmlspecialchars($cat['name']));
    $block->setContent('rooms_list.description', htmlspecialchars(substr($cat['description'] ?? '', 0, 150) . '...'));
    $block->setContent('rooms_list.base_price', number_format((float)$cat['base_price'], 2, ',', '.'));
    $block->setContent('rooms_list.capacity', (string)$cat['capacity']);
    $block->setContent('rooms_list.image_url', htmlspecialchars($cat['image_url'] ?? 'deluxe_singola.jpg'));
    $block->setContent('rooms_list.link_params', '?id=1');
}

$page->setContent('body', $block->get());
$page->close();

$html = $page->buffer;
if (strpos($html, '<[rooms_list.') !== false) {
    echo "BUG: UNPARSED PLACEHOLDER FOUND!\n";
    $lines = explode("\n", $html);
    foreach ($lines as $i => $line) {
        if (strpos($line, '<[rooms_list.') !== false) {
            echo "Line " . ($i+1) . ": " . trim($line) . "\n";
        }
    }
} else {
    echo "NO BUG.\n";
}
