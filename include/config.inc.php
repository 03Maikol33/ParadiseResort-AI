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
    'base'         => '/progetto/zParadiseResort',
    'upload_dir'   => 'uploads',

    'cache_folder'  => 'cache',
    'cache_mode'    => NONE,
    'cache_timeout' => 600,

    'languages'        => [],
    'currentlanguage'  => 'it',
    'currenttab'       => '',
];
