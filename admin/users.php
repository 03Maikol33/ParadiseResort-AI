<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_admin();

$message = '';
$error = '';

if (!empty($_GET['del_id'])) {
    $delId = (int)$_GET['del_id'];
    if ($delId === (int)$_SESSION['user']['id']) {
        $error = 'Non puoi eliminare il tuo stesso account mentre sei loggato.';
    } else {
        try {
            $stmtDel = db()->prepare('DELETE FROM users WHERE id = ?');
            $stmtDel->execute([$delId]);
            $message = 'Utente ID #' . $delId . ' eliminato con successo.';
        } catch (Exception $e) {
            $error = 'Impossibile eliminare l\'utente. Potrebbe avere prenotazioni collegate.';
        }
    }
}

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Amministratore', 'admin');

$block = new_block('users');
$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    $stmtUsers = db()->query('
        SELECT u.*, COALESCE(GROUP_CONCAT(g.name SEPARATOR \', \'), \'Nessun Gruppo\') as group_names
        FROM users u
        LEFT JOIN user_gruppi ug ON u.id = ug.user_id
        LEFT JOIN gruppi g ON ug.group_id = g.id
        GROUP BY u.id
        ORDER BY u.id DESC
    ');
    $users = $stmtUsers->fetchAll();
    foreach ($users as $u) {
        $block->setContent('user_list.id', (string)$u['id']);
        $block->setContent('user_list.name', htmlspecialchars($u['first_name'] . ' ' . $u['last_name']));
        $block->setContent('user_list.email', htmlspecialchars($u['email']));
        $block->setContent('user_list.phone', htmlspecialchars($u['phone'] ?? '-'));
        $block->setContent('user_list.groups', htmlspecialchars($u['group_names']));
        $block->setContent('user_list.created_at', date('d/m/Y H:i', strtotime($u['created_at'])));
    }
} catch (Exception $e) {
    $block->setContent('error', 'Errore nel caricamento utenti: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
