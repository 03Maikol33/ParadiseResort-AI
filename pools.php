<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

$page = new_page('customers', 'frame-public');
$block = new_block('pools');

$page->setContent('body', $block->get());
$page->close();
