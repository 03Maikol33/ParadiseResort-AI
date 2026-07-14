<?php

/*
 * Bootstrap dell'applicazione: punto di ingresso comune ad ogni script.
 * È il file che ogni pagina PHP includerà come prima cosa, per inizializzare l'ambiente.
 * Carica config, sessione, template engine, connessione DB.
 */

session_start();

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [];
}

require_once __DIR__ . '/config.inc.php';
require_once __DIR__ . '/../template2.inc.php';
require_once __DIR__ . '/db.inc.php';
require_once __DIR__ . '/page.inc.php';
require_once __DIR__ . '/auth.inc.php';

// US-03: Auto-login se la sessione è scaduta ma esiste il cookie "Ricordami"
if (empty($_SESSION['user']['id']) && !empty($_COOKIE['paradise_remember'])) {
    $parts = explode(':', $_COOKIE['paradise_remember']);
    if (count($parts) === 2) {
        $userId = (int)$parts[0];
        $tokenHash = $parts[1];
        try {
            $stmtUser = db()->prepare('SELECT * FROM users WHERE id = ?');
            $stmtUser->execute([$userId]);
            $u = $stmtUser->fetch();
            if ($u) {
                $expectedHash = hash_hmac('sha256', $u['id'] . '-' . $u['password'], 'ParadiseResortSecretKey2026');
                if (hash_equals($expectedHash, $tokenHash)) {
                    $_SESSION['user'] = [
                        'id' => (int)$u['id'],
                        'first_name' => $u['first_name'],
                        'last_name' => $u['last_name'],
                        'name' => trim($u['first_name'] . ' ' . $u['last_name']),
                        'email' => $u['email'],
                        'phone' => $u['phone'] ?? '',
                        'image_url' => $u['image_url'] ?? '',
                        'services' => load_user_services((int)$u['id'])
                    ];
                }
            }
        } catch (Exception $e) {
            // Ignora errori durante il controllo del cookie
        }
    }
}


function get_cart_count(): int {
    if (empty($_SESSION['user']['id'])) {
        return 0;
    }
    try {
        $expireTime = date('Y-m-d H:i:s', time() - 30 * 60);
        $stmtExpired = db()->prepare('SELECT id FROM bookings WHERE status_id = 1 AND created_at < ?');
        $stmtExpired->execute([$expireTime]);
        $expiredIds = $stmtExpired->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($expiredIds)) {
            $placeholders = implode(',', array_fill(0, count($expiredIds), '?'));

            $delInv = db()->prepare("DELETE FROM invoices WHERE booking_id IN ($placeholders)");
            $delInv->execute($expiredIds);

            $delAmen = db()->prepare("DELETE FROM booking_amenities WHERE booking_id IN ($placeholders)");
            $delAmen->execute($expiredIds);

            $delBook = db()->prepare("DELETE FROM bookings WHERE id IN ($placeholders)");
            $delBook->execute($expiredIds);
        }

        $stmt = db()->prepare('SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND status_id = 1');
        $stmt->execute([$_SESSION['user']['id']]);
        $row = $stmt->fetch();
        return (int)($row['count'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}
