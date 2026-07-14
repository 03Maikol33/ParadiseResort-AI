<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST['action'] = 'save_review';
    require __DIR__ . '/review.php';
    exit;
}

header('Location: ' . $config['base'] . '/review.php');
exit;
