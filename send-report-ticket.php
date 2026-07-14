<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST['action'] = 'save_ticket';
    require __DIR__ . '/report-ticket.php';
    exit;
}

header('Location: ' . $config['base'] . '/report-ticket.php');
exit;
