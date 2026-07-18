<?php
require_once __DIR__ . '/template2.inc.php';

$page = new Template();
$page->setTemplateCode('
<[if!empty has_results]>
YES RESULTS
<[/if!empty]>
<[ifempty has_results]>
NO RESULTS (placeholder)
<[/ifempty]>
');

$page->setContent('has_results', '1');

$page->parse();
echo $page->buffer;
