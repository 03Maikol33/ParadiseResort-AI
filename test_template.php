<?php
require_once __DIR__ . '/template2.inc.php';

$page = new Template();
$page->setTemplateCode('
<[foreach]>
<div>
    <h1><[rooms_list.name]></h1>
    <p><[rooms_list.desc]></p>
</div>
<[/foreach]>
');

$page->setContent('rooms_list.name', 'Camera 1');
$page->setContent('rooms_list.desc', 'Descrizione 1');

$page->parse();
echo $page->buffer;
