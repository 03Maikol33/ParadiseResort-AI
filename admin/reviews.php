<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
if (!is_admin()) {
    header('Location: ' . $config['base'] . '/login.php');
    exit;
}

$message = '';
$error = '';

if (!empty($_GET['del_id'])) {
    $delId = (int)$_GET['del_id'];
    try {
        $del = db()->prepare('DELETE FROM reviews WHERE id = ?');
        $del->execute([$delId]);
        $message = 'Recensione #' . $delId . ' eliminata con successo dal sistema.';
    } catch (Exception $e) {
        $error = 'Errore durante l\'eliminazione: ' . $e->getMessage();
    }
}

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Amministratore', 'admin');

$block = new_block('reviews');
$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    $stmt = db()->query('
        SELECT r.*, u.first_name, u.last_name, u.email, rc.name as category_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN room_categories rc ON r.room_category_id = rc.id
        ORDER BY r.created_at DESC
    ');
    $reviews = $stmt->fetchAll();

    foreach ($reviews as $rev) {
        $block->setContent('rev_rows.id', (string)$rev['id']);
        $block->setContent('rev_rows.guest_name', htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']));
        $block->setContent('rev_rows.guest_email', htmlspecialchars($rev['email']));
        $block->setContent('rev_rows.category_name', htmlspecialchars($rev['category_name'] ?? 'Generale'));
        $block->setContent('rev_rows.date', date('d/m/Y H:i', strtotime($rev['created_at'])));
        $block->setContent('rev_rows.comment', htmlspecialchars($rev['comment']));
        $block->setContent('rev_rows.rating', (string)$rev['rating']);
    }
} catch (Exception $e) {
    $block->setContent('error', 'Errore DB: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
