<?php

/*
  Configurazione globale dell'applicazione.
  Contiene tutte le impostazioni che possono essere modificate per adattare l'applicazione al proprio ambiente.

  Le costanti NONE / FILE / MEMORY servono al template engine
  (template2.inc.php) per la modalita' di cache.
 */

define('NONE',   0);
define('FILE',   1);
define('MEMORY', 2);

$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$projRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$basePath = '';
if (!empty($docRoot) && strpos(strtolower($projRoot), strtolower($docRoot)) === 0) {
    $basePath = substr($projRoot, strlen($docRoot));
}
if ($basePath === false || $basePath === null) {
    $basePath = '/progettoAi';
}
$basePath = rtrim($basePath, '/');

$config = [

    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'paradiseresort',
        'user'    => 'root',
        'pass'    => 'root',
        'charset' => 'utf8mb4',
    ],

    'skin'         => 'customers',
    'admin_skin'   => 'administration',
    'base'         => $basePath,
    'upload_dir'   => 'uploads',

    'cache_folder'  => 'cache',
    'cache_mode'    => NONE,
    'cache_timeout' => 600,

    'languages'        => [],
    'currentlanguage'  => 'it',
    'currenttab'       => '',
];

