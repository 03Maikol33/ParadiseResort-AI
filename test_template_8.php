<?php
require_once __DIR__ . '/template2.inc.php';
require_once __DIR__ . '/include/page.inc.php';

$fields = [
    'rooms_list.id' => '1',
    'rooms_list.name' => 'Deluxe Singola',
    'rooms_list.description' => 'Test',
    'rooms_list.base_price' => '150',
    'rooms_list.capacity' => '1',
    'rooms_list.image_url' => 'deluxe_singola.jpg',
    'rooms_list.link_params' => '?id=1'
];

foreach ($fields as $key_to_skip => $val_to_skip) {
    global $config;
    $config['skin'] = 'customers';
    $block = new_block('rooms');
    $block->setContent('has_results', '1');

    foreach ($fields as $k => $v) {
        if ($k !== $key_to_skip) {
            $block->setContent($k, $v);
        }
    }
    
    $out = $block->get();
    $card_count = substr_count($out, '<div class="single-room');
    echo "Skipping $key_to_skip -> cards: $card_count\n";
}
