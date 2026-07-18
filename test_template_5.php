<?php
require_once __DIR__ . '/template2.inc.php';

$block = new Template();
$block->setTemplateCode('
<[if!empty has_results]>
<[foreach]>
<div class="card">
   <a href="<[rooms_list.link_params]>">
      <img src="<[rooms_list.image_url]>" alt="<[rooms_list.name]>">
   </a>
   <h3><[rooms_list.name]></h3>
   <p><[rooms_list.description]></p>
</div>
<[/foreach]>
<[/if!empty]>
');

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

$block->parse();
echo "OUTPUT HTML:\n" . $block->buffer . "\n";
