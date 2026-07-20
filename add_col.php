<?php
require_once 'c:/xampp/htdocs/ParadiseResortAI/ParadiseResort-AI/include/bootstrap.inc.php';
try {
    db()->exec('ALTER TABLE restaurant_reservations ADD COLUMN special_requests TEXT DEFAULT NULL;');
    echo 'OK';
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo 'OK';
    } else {
        echo $e->getMessage();
    }
}
