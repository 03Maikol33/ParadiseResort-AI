<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_admin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_staff') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $groupId   = (int)($_POST['group_id'] ?? 2); // default 2 (Receptionist)

    if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
        $error = 'Nome, Cognome, Email e Password sono obbligatori.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Formato email non valido.';
    } elseif (!in_array($groupId, [1, 2])) {
        $error = 'Ruolo specificato non valido.';
    } else {
        try {
            $chk = db()->prepare('SELECT id FROM users WHERE email = ?');
            $chk->execute([$email]);
            $existing = $chk->fetch();

            if ($existing) {
                // Se l'utente esiste già, assegniamo semplicemente il nuovo gruppo se non lo ha già
                $userId = (int)$existing['id'];
                $insGrp = db()->prepare('INSERT IGNORE INTO user_gruppi (user_id, group_id) VALUES (?, ?)');
                $insGrp->execute([$userId, $groupId]);
                $message = 'Ruolo assegnato con successo all\'utente esistente (' . htmlspecialchars($email) . ').';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $ins = db()->prepare('INSERT INTO users (first_name, last_name, email, password, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $ins->execute([$firstName, $lastName, $email, $hashed, $phone]);
                $userId = (int)db()->lastInsertId();

                $insGrp = db()->prepare('INSERT INTO user_gruppi (user_id, group_id) VALUES (?, ?)');
                $insGrp->execute([$userId, $groupId]);
                $message = 'Nuovo membro dello staff creato e assegnato al ruolo selezionato.';
            }
        } catch (Exception $e) {
            $error = 'Errore durante la creazione dello staff: ' . $e->getMessage();
        }
    }
} elseif (!empty($_GET['remove_role_user']) && !empty($_GET['remove_role_group'])) {
    $rUser = (int)$_GET['remove_role_user'];
    $rGroup = (int)$_GET['remove_role_group'];
    if ($rUser === (int)$_SESSION['user']['id'] && $rGroup === 1) {
        $error = 'Non puoi rimuovere il tuo stesso ruolo di Amministratore.';
    } else {
        try {
            $delGrp = db()->prepare('DELETE FROM user_gruppi WHERE user_id = ? AND group_id = ?');
            $delGrp->execute([$rUser, $rGroup]);
            
            // Se l'utente non ha più nessun gruppo, gli assegniamo il ruolo Guest (3)
            $chkGrp = db()->prepare('SELECT COUNT(*) FROM user_gruppi WHERE user_id = ?');
            $chkGrp->execute([$rUser]);
            if ($chkGrp->fetchColumn() == 0) {
                $insGuest = db()->prepare('INSERT INTO user_gruppi (user_id, group_id) VALUES (?, 3)');
                $insGuest->execute([$rUser]);
            }

            $message = 'Ruolo rimosso con successo per l\'utente #' . $rUser . '.';
        } catch (Exception $e) {
            $error = 'Errore durante la rimozione del ruolo.';
        }
    }
}

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Amministratore', 'admin');

$block = new_block('staff');
$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    $stmtStaff = db()->query('
        SELECT u.*, g.id as group_id, g.name as group_name
        FROM users u
        JOIN user_gruppi ug ON u.id = ug.user_id
        JOIN gruppi g ON ug.group_id = g.id
        WHERE g.id IN (1, 2)
        ORDER BY g.id ASC, u.last_name ASC
    ');
    $staffList = $stmtStaff->fetchAll();
    foreach ($staffList as $st) {
        $block->setContent('staff_list.id', (string)$st['id']);
        $block->setContent('staff_list.name', htmlspecialchars($st['first_name'] . ' ' . $st['last_name']));
        $block->setContent('staff_list.email', htmlspecialchars($st['email']));
        $block->setContent('staff_list.phone', htmlspecialchars($st['phone'] ?? '-'));
        $block->setContent('staff_list.group_id', (string)$st['group_id']);
        $block->setContent('staff_list.group_name', htmlspecialchars($st['group_name']));
        $block->setContent('staff_list.badge_color', $st['group_id'] == 1 ? 'bg-danger' : 'bg-primary');
    }
} catch (Exception $e) {
    $block->setContent('error', 'Errore nel caricamento staff: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
