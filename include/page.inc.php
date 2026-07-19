<?php

/*
 * Helper per creare pagine usando il template engine del docente
 * SENZA passare per le classi Skin/Skinlet (che chiamano Template con
 * un path gia' contenente .html, mentre Template aggiunge un altro .html
 * causando il "file non trovato").
 *
 * Usiamo direttamente la classe Template fornita dal docente,
 * passando il path SENZA estensione: il Template aggiunge .html da solo.
 */

/**
 * Crea il "frame" della pagina (header + footer + buco <[body]>).
 * Equivalente a `new Skin($skinName)`, ma senza il bug.
 */

/**
 * DEFAULT: se non viene specificato, il frame è "frame-public", che contiene header e footer per la parte pubblica del sito.
 * Sennò si specifica frame-private per il backend (bisogna passarlo come secondo argomento).
 * che contiene header e footer per la parte privata del sito (con menu di amministrazione).
 */

function new_page(string $skinName, string $frame = 'frame-public'): Template {

    $GLOBALS['current_skin']     = $skinName;
    $GLOBALS['config']['skin']   = $skinName;

    $root = __DIR__ . '/..';
    $page = new Template("{$root}/skins/{$skinName}/dtml/{$frame}");
    $page->setContent('base', $GLOBALS['config']['base']);
    if (function_exists('get_cart_count')) {
        $cartCount = get_cart_count();
        $page->setContent('cart_count', (string)$cartCount);
        $page->setContent('cart_badge', $cartCount > 0 ? "<span class=\"badge badge-danger cart-badge\">$cartCount</span>" : "");
    }
    if (!empty($_SESSION['user']['id'])) {
        $page->setContent('is_logged', '1');
        $uName = $_SESSION['user']['name'] ?? trim(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? ''));
        $page->setContent('user_name', htmlspecialchars($uName));
        if (function_exists('is_admin') && is_admin()) {
            $page->setContent('user_role_link', '<a href="' . $GLOBALS['config']['base'] . '/admin/index.php" class="dropdown-item"><i class="fas fa-tools me-2"></i> Dashboard Admin</a>');
        } elseif (function_exists('is_receptionist') && is_receptionist()) {
            $page->setContent('user_role_link', '<a href="' . $GLOBALS['config']['base'] . '/receptionist/index.php" class="dropdown-item"><i class="fas fa-concierge-bell me-2"></i> Dashboard Reception</a>');
        } else {
            $page->setContent('user_role_link', '');
        }
    } else {
        $page->setContent('is_logged', '');
        $page->setContent('user_role_link', '');
    }
    return $page;
}

function get_current_role_path(): string {
    if (!empty($_SESSION['user']['role_path'])) {
        return $_SESSION['user']['role_path'];
    }
    if (function_exists('is_admin') && is_admin()) {
        return 'admin';
    }
    if (function_exists('is_receptionist') && is_receptionist()) {
        return 'receptionist';
    }
    return 'receptionist';
}

/**
 * Crea un "blocco" di contenuto da inserire dentro un placeholder del frame.
 * Equiva a `new Skinlet($name)`, ma senza bug.
 */
function new_block(string $template): Template {

    $skinName = $GLOBALS['current_skin'] ?? $GLOBALS['config']['skin'];
    $root = __DIR__ . '/..';
    $block = new Template("{$root}/skins/{$skinName}/dtml/{$template}");
    $block->setContentOnce('base', $GLOBALS['config']['base']);
    
    $rolePath = get_current_role_path();
    $block->setContentOnce('role_path', $rolePath);
    $block->setContentOnce('is_admin_role', ($rolePath === 'admin') ? '1' : '');
    $block->setContentOnce('is_receptionist_role', ($rolePath === 'receptionist') ? '1' : '');

    if (!empty($_SESSION['user']['id'])) {
        $block->setContentOnce('is_logged', '1');
        $uName = $_SESSION['user']['name'] ?? trim(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? ''));
        $block->setContent('user_name', htmlspecialchars($uName));
        if (!empty($_SESSION['user']['role_path'])) {
            $block->setContent('role_path', $_SESSION['user']['role_path']);
        }
    } else {
        $block->setContentOnce('is_logged', '');
    }
    return $block;
}

function setup_backoffice_page(Template $page, string $roleName, string $rolePath, ?Template $block = null): void {
    global $config;

    $_SESSION['user']['role_path'] = $rolePath;

    $name = $_SESSION['user']['name'] ?? '';
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $w) {
        $initials .= strtoupper(substr($w, 0, 1));
    }
    $initials = substr($initials, 0, 2);
    if ($initials === '') {
        $initials = 'U';
    }

    $page->setContentOnce('base', $config['base']);
    $page->setContentOnce('skin', 'administration');
    $page->setContentOnce('user_name', htmlspecialchars($name));
    $page->setContentOnce('user_role', $roleName);
    $page->setContentOnce('role_path', $rolePath);
    $page->setContentOnce('is_admin_role', ($rolePath === 'admin') ? '1' : '');
    $page->setContentOnce('is_receptionist_role', ($rolePath === 'receptionist') ? '1' : '');
    $page->setContentOnce('user_initials', htmlspecialchars($initials));

    if ($block !== null) {
        $block->setContentOnce('base', $config['base']);
        $block->setContentOnce('role_path', $rolePath);
        $block->setContentOnce('is_admin_role', ($rolePath === 'admin') ? '1' : '');
        $block->setContentOnce('is_receptionist_role', ($rolePath === 'receptionist') ? '1' : '');
    }

    // Notifiche
    $notif = get_backoffice_notifications_html();
    $page->setContent('notifications_list', $notif['html']);
    $page->setContent('notification_badge', $notif['count'] > 0 ? '<span class="notification-dot"></span>' : '');
}

function get_backoffice_notifications_html(): array {
    $db = db();
    $items = [];
    $count = 0;

    try {
        $stmt = $db->query("
            SELECT b.id, u.first_name, u.last_name, b.created_at
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.status_id = 2
            ORDER BY b.created_at DESC
            LIMIT 5
        ");
        $pending = $stmt->fetchAll();
        foreach ($pending as $p) {
            $count++;
            $timeStr = date('d/m H:i', strtotime($p['created_at']));
            $guest = htmlspecialchars($p['first_name'] . ' ' . $p['last_name']);
            $url = $GLOBALS['config']['base'] . '/' . get_current_role_path() . '/bookings.php?status=2';
            $items[] = '
                <a class="dropdown-item" href="' . $url . '">
                  <span class="notification-title text-wrap" style="white-space: normal;"><i class="bi bi-hourglass-split text-warning me-2"></i>Da confermare: #' . $p['id'] . '</span>
                  <span class="notification-time text-wrap" style="white-space: normal;">Cliente: ' . $guest . ' (' . $timeStr . ')</span>
                </a>';
        }
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("
            SELECT mt.id, r.room_number, mt.issue_description, mt.created_at
            FROM maintenance_tickets mt
            LEFT JOIN rooms r ON mt.room_id = r.id
            WHERE mt.status_id IN (1, 2)
            ORDER BY mt.created_at DESC
            LIMIT 5
        ");
        $tickets = $stmt->fetchAll();
        foreach ($tickets as $t) {
            $count++;
            $timeStr = date('d/m H:i', strtotime($t['created_at']));
            $desc = htmlspecialchars(mb_strimwidth($t['issue_description'], 0, 30, "..."));
            $roomLabel = $t['room_number'] ? 'Cam. ' . htmlspecialchars($t['room_number']) : 'Generica';
            $urlTicket = $GLOBALS['config']['base'] . '/' . get_current_role_path() . '/segnalazioni.php';
            $items[] = '
                <a class="dropdown-item" href="' . $urlTicket . '">
                  <span class="notification-title text-wrap" style="white-space: normal;"><i class="bi bi-tools text-danger me-2"></i>Manutenzione ' . $roomLabel . '</span>
                  <span class="notification-time text-wrap" style="white-space: normal;">' . $desc . ' (' . $timeStr . ')</span>
                </a>';
        }
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("
            SELECT b.id as booking_id, a.name as amenity_name, u.first_name, u.last_name
            FROM booking_amenities ba
            JOIN amenities a ON ba.amenity_id = a.id
            JOIN bookings b ON ba.booking_id = b.id
            JOIN users u ON b.user_id = u.id
            WHERE a.is_suspended = 1 AND b.status_id IN (2, 3)
            ORDER BY b.id DESC
            LIMIT 5
        ");
        $suspendedAlerts = $stmt->fetchAll();
        foreach ($suspendedAlerts as $sa) {
            $count++;
            $guest = htmlspecialchars($sa['first_name'] . ' ' . $sa['last_name']);
            $srvName = htmlspecialchars($sa['amenity_name']);
            $url = $GLOBALS['config']['base'] . '/' . get_current_role_path() . '/bookings.php?search=' . $sa['booking_id'];
            $items[] = '
                <a class="dropdown-item" href="' . $url . '">
                  <span class="notification-title text-wrap" style="white-space: normal; color: #dc3545;"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Servizio Sospeso: ' . $srvName . '</span>
                  <span class="notification-time text-wrap" style="white-space: normal;">Richiesto da: ' . $guest . ' (Pren. #' . $sa['booking_id'] . ')</span>
                </a>';
        }
    } catch (Exception $e) {}

    $html = '';
    if (empty($items)) {
        $html = '<div class="dropdown-item text-center text-muted py-3">Nessuna nuova notifica</div>';
    } else {
        $html = implode('', $items);
    }

    return [
        'html' => $html,
        'count' => $count
    ];
}
