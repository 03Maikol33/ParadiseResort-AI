<?php
require_once __DIR__ . '/template2.inc.php';
require_once __DIR__ . '/include/page.inc.php';
global $config; $config['skin'] = 'customers';
$block = new_block('rooms');
$block->setContent('has_results', '1');
$block->setContent('base', '');
$block->setContent('rooms_list.name', 'Name');

$templateCode = $block->getTemplateCode();
$templateCode = str_replace('<[base]>/room_details', '/room_details', $templateCode);
$templateCode = str_replace('<[base]>/skins', '/skins', $templateCode);
$block->setTemplateCode($templateCode);

$out = $block->get();
echo "Cards: " . substr_count($out, '<div class="single-room');
