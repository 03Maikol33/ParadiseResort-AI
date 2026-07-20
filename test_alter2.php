<?php
require "include/bootstrap.inc.php";
try {
    db()->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
    db()->exec("ALTER TABLE users ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
    echo "OK ALTER";
} catch(Exception $e) {
    echo "ERR: " . $e->getMessage();
}
