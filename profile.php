<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

require_login();

$userId = (int)$_SESSION['user']['id'];
$message = '';
$error = '';

if (!empty($_GET['cancelled'])) $message = 'La tua prenotazione futura è stata annullata con successo.';
if (!empty($_GET['updated'])) $message = 'Profilo personale aggiornato correttamente.';
if (!empty($_GET['error'])) $error = $_GET['error'];

// US-16: Annullamento prenotazione futura
if (!empty($_GET['cancel_id'])) {
    $cancelId = (int)$_GET['cancel_id'];
    try {
        $chk = db()->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status_id IN (2, 3)');
        $chk->execute([$cancelId, $userId]);
        $bk = $chk->fetch();

        if ($bk) {
            if ($bk['check_in_date'] > date('Y-m-d')) {
                $upd = db()->prepare('UPDATE bookings SET status_id = 4 WHERE id = ?');
                $upd->execute([$cancelId]);
                header('Location: ' . $config['base'] . '/profile.php?cancelled=1');
                exit;
            } else {
                $error = 'Non puoi annullare una prenotazione in corso o già passata.';
            }
        } else {
            $error = 'Prenotazione non trovata o non annullabile.';
        }
    } catch (Exception $e) {
        $error = 'Errore di cancellazione: ' . $e->getMessage();
    }
}

// Aggiornamento profilo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($firstName !== '' && $lastName !== '') {
        try {
            if ($password !== '') {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $upd = db()->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ?, password = ? WHERE id = ?');
                $upd->execute([$firstName, $lastName, $phone, $hashed, $userId]);
            } else {
                $upd = db()->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?');
                $upd->execute([$firstName, $lastName, $phone, $userId]);
            }
            $_SESSION['user']['first_name'] = $firstName;
            $_SESSION['user']['last_name'] = $lastName;
            $_SESSION['user']['name'] = $firstName . ' ' . $lastName;
            $_SESSION['user']['phone'] = $phone;

            header('Location: ' . $config['base'] . '/profile.php?updated=1');
            exit;
        } catch (Exception $e) {
            $error = 'Errore salvataggio profilo: ' . $e->getMessage();
        }
    } else {
        $error = 'Nome e Cognome sono obbligatori.';
    }
}

$page = new_page('customers', 'frame-public');
$block = new_block('profile');
$block->setContent('message', $message);
$block->setContent('error', $error);

$block->setContent('user.first_name', htmlspecialchars($_SESSION['user']['first_name'] ?? ''));
$block->setContent('user.last_name', htmlspecialchars($_SESSION['user']['last_name'] ?? ''));
$block->setContent('user.email', htmlspecialchars($_SESSION['user']['email'] ?? ''));
$block->setContent('user.phone', htmlspecialchars($_SESSION['user']['phone'] ?? ''));

// US-15: Storico prenotazioni (escluse quelle in carrello status_id = 1)
try {
    $stmtBk = db()->prepare('
        SELECT b.*, r.room_number, rc.name as category_name, rc.image_url, bs.name as status_name, i.id as invoice_id
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_categories rc ON r.category_id = rc.id
        JOIN booking_statuses bs ON b.status_id = bs.id
        LEFT JOIN invoices i ON b.id = i.booking_id
        WHERE b.user_id = ? AND b.status_id != 1
        ORDER BY b.check_in_date DESC
    ');
    $stmtBk->execute([$userId]);
    $history = $stmtBk->fetchAll();

    $hasHistory = !empty($history);
    $block->setContent('has_history', $hasHistory ? '1' : '');

    foreach ($history as $h) {
        $block->setContent('booking_list.id', (string)$h['id']);
        $block->setContent('booking_list.category_name', htmlspecialchars($h['category_name']));
        $block->setContent('booking_list.room_number', htmlspecialchars($h['room_number']));
        $block->setContent('booking_list.image_url', htmlspecialchars($h['image_url'] ?? 'deluxe_singola.jpg'));
        
        $chkIn = date('d/m/Y', strtotime($h['check_in_date']));
        $chkOut = date('d/m/Y', strtotime($h['check_out_date']));
        $block->setContent('booking_list.dates', "$chkIn -> $chkOut");
        $block->setContent('booking_list.price', number_format((float)$h['total_price'], 2, ',', '.'));
        
        $badge = 'badge badge-secondary';
        if ($h['status_id'] == 2) $badge = 'badge badge-warning text-dark';
        if ($h['status_id'] == 3) $badge = 'badge badge-primary';
        if ($h['status_id'] == 4) $badge = 'badge badge-danger';
        if ($h['status_id'] == 5) $badge = 'badge badge-success';
        
        $block->setContent('booking_list.status_badge', '<span class="' . $badge . ' py-2 px-3">' . htmlspecialchars($h['status_name']) . '</span>');

        // Pulsante Annulla (US-16) solo se futuro e in stato 2 o 3
        if ($h['check_in_date'] > date('Y-m-d') && in_array((int)$h['status_id'], [2, 3])) {
            $block->setContent('booking_list.cancel_btn', '<a href="' . $config['base'] . '/profile.php?cancel_id=' . $h['id'] . '" class="btn btn-sm btn-outline-danger ml-2" onclick="return confirm(\'Sei sicuro di voler annullare questa prenotazione futura?\');"><i class="fas fa-times me-1"></i> Annulla</a>');
        } else {
            $block->setContent('booking_list.cancel_btn', '');
        }

        // Pulsante Ricevuta/Fattura (US-18) se esiste id fattura
        if (!empty($h['invoice_id'])) {
            $block->setContent('booking_list.invoice_btn', '<a href="' . $config['base'] . '/invoice.php?id=' . $h['invoice_id'] . '" class="btn btn-sm btn1"><i class="fas fa-file-invoice me-1"></i> Ricevuta / PDF</a>');
        } else {
            $block->setContent('booking_list.invoice_btn', '');
        }
    }
} catch (Exception $e) {
    $block->setContent('has_history', '');
}

$page->setContent('body', $block->get());
$page->close();
