<?php
require "include/bootstrap.inc.php";
try {
    $stmt = db()->query("SHOW COLUMNS FROM users");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo "ERR: " . $e->getMessage();
}
