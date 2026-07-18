<?php
require_once __DIR__ . '/template2.inc.php';
require_once __DIR__ . '/include/page.inc.php';

global $config;
$config['skin'] = 'customers';

$block = new_block('rooms');

$block->setContent('val_capacity', '1');
$block->setContent('val_max_price', '300');
$block->setContent('val_check_in', '2023-01-01');
$block->setContent('val_check_out', '2023-01-05');
$block->setContent('room_options', '<option>Test</option>');
$block->setContent('has_results', '1');

$cat = [
    'id' => '1',
    'name' => 'Deluxe Singola',
    'description' => 'Test',
    'base_price' => '150',
    'capacity' => '1',
    'image_url' => 'deluxe_singola.jpg'
];

$block->setContent('rooms_list.id', (string)$cat['id']);
$block->setContent('rooms_list.name', htmlspecialchars($cat['name']));
$block->setContent('rooms_list.description', htmlspecialchars(substr($cat['description'] ?? '', 0, 150) . '...'));
$block->setContent('rooms_list.base_price', number_format((float)$cat['base_price'], 2, ',', '.'));
$block->setContent('rooms_list.capacity', (string)$cat['capacity']);
$block->setContent('rooms_list.image_url', htmlspecialchars($cat['image_url'] ?? 'deluxe_singola.jpg'));
$block->setContent('rooms_list.link_params', '?id=1');

echo $block->get();
