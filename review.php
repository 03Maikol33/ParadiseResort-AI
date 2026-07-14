<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

require_login();
$userId = (int)$_SESSION['user']['id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_review') {
    $catId = (int)($_POST['room_category_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5 || $comment === '') {
        $error = 'Il punteggio (da 1 a 5 stelle) e il commento sono obbligatori.';
    } else {
        try {
            $ins = db()->prepare('INSERT INTO reviews (user_id, room_category_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())');
            $ins->execute([$userId, $catId > 0 ? $catId : null, $rating, $comment]);
            header('Location: ' . $config['base'] . '/reviews.php?success=1');
            exit;
        } catch (Exception $e) {
            $error = 'Errore durante l\'invio della recensione: ' . $e->getMessage();
        }
    }
}

$page = new_page('customers', 'frame-public');
$block = new_block('review');
$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    $stmtCat = db()->query('SELECT id, name FROM room_categories ORDER BY id ASC');
    $categories = $stmtCat->fetchAll();
    $options = '<option value="0">Generale sul Resort (Nessuna Camera Specifica)</option>';
    foreach ($categories as $c) {
        $options .= '<option value="' . $c['id'] . '">' . htmlspecialchars($c['name']) . '</option>';
    }
    $block->setContent('category_options', $options);
} catch (Exception $e) {
    $block->setContent('category_options', '');
}

$page->setContent('body', $block->get());
$page->close();
