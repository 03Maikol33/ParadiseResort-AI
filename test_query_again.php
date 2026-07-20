<?php
require "include/bootstrap.inc.php";
try {
    $stmt = db()->query("SELECT r.*, u.first_name, u.last_name, u.email, u.phone FROM restaurant_reservations r JOIN users u ON r.user_id = u.id");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo "ERR: " . $e->getMessage();
}
