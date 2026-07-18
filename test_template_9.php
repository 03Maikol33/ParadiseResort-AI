<?php
require_once __DIR__ . '/template2.inc.php';
require_once __DIR__ . '/include/page.inc.php';
global $config; $config['skin'] = 'customers';
$block = new_block('rooms');
$block->setContent('rooms_list.id', '1');
$block->setContent('rooms_list.name', 'Name');
$block->setContent('rooms_list.link_params', '?id=1');
$out = $block->get();
echo "Cards: " . substr_count($out, '<div class="single-room');
