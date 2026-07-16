<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_service();

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Receptionist', 'receptionist');

$block = new_block('requested_services');

try {
    $stmt = db()->query('
        SELECT ba.id, ba.quantity, ba.price,
               a.name as amenity_name,
               b.id as booking_id, b.check_in_date, b.check_out_date,
               u.first_name, u.last_name, u.email, u.phone,
               r.room_number, rc.name as category_name
        FROM booking_amenities ba
        JOIN amenities a ON ba.amenity_id = a.id
        JOIN bookings b ON ba.booking_id = b.id
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        JOIN room_categories rc ON r.category_id = rc.id
        WHERE b.status_id != 1
        ORDER BY b.check_in_date DESC
    ');
    $requested = $stmt->fetchAll();

    foreach ($requested as $req) {
        $block->setContent('req_rows.id', (string)$req['id']);
        $block->setContent('req_rows.amenity_name', htmlspecialchars($req['amenity_name']));
        $block->setContent('req_rows.price', number_format((float)$req['price'], 2, ',', '.'));
        $block->setContent('req_rows.guest_name', htmlspecialchars($req['first_name'] . ' ' . $req['last_name']));
        $block->setContent('req_rows.guest_phone', htmlspecialchars($req['phone'] ?? '-'));
        $block->setContent('req_rows.booking_id', (string)$req['booking_id']);
        $block->setContent('req_rows.room_number', htmlspecialchars($req['room_number']));
        $block->setContent('req_rows.category_name', htmlspecialchars($req['category_name']));
        
        $chkIn = date('d/m/Y', strtotime($req['check_in_date']));
        $block->setContent('req_rows.check_in', $chkIn);
    }
} catch (Exception $e) {}

$page->setContent('body', $block->get());
$page->close();
