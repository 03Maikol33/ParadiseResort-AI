<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

$_SESSION = [];
if (session_id() !== '' || isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();

if (isset($_COOKIE['paradise_remember'])) {
    setcookie('paradise_remember', '', time() - 3600, '/');
}

header('Location: ' . $config['base'] . '/login.php?logged_out=1');
exit;
