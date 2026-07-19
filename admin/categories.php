<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_admin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_category') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $basePrice = (float)($_POST['base_price'] ?? 0);
    $capacity = (int)($_POST['capacity'] ?? 2);
    $imageUrl = trim($_POST['image_url'] ?? 'deluxe_singola.jpg');

    if ($name === '' || $basePrice <= 0 || $capacity <= 0) {
        $error = 'Nome, Prezzo Base (>0) e Capienza (>0) sono obbligatori.';
    } else {
        try {
            if ($id > 0) {
                $upd = db()->prepare('UPDATE room_categories SET name = ?, description = ?, base_price = ?, capacity = ?, image_url = ? WHERE id = ?');
                $upd->execute([$name, $description, $basePrice, $capacity, $imageUrl, $id]);
                $message = 'Categoria #' . $id . ' aggiornata con successo.';
            } else {
                $ins = db()->prepare('INSERT INTO room_categories (name, description, base_price, capacity, image_url) VALUES (?, ?, ?, ?, ?)');
                $ins->execute([$name, $description, $basePrice, $capacity, $imageUrl]);
                $message = 'Nuova categoria (' . htmlspecialchars($name) . ') creata con successo.';
            }
        } catch (Exception $e) {
            $error = 'Errore durante il salvataggio: ' . $e->getMessage();
        }
    }
} elseif (!empty($_GET['del_id'])) {
    $delId = (int)$_GET['del_id'];
    try {
        $del = db()->prepare('DELETE FROM room_categories WHERE id = ?');
        $del->execute([$delId]);
        $message = 'Categoria #' . $delId . ' eliminata con successo.';
    } catch (Exception $e) {
        $error = 'Impossibile eliminare: ci sono camere fisiche o prenotazioni collegate a questa categoria.';
    }
}

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Amministratore', 'admin');

$block = new_block('categories');
$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    $stmtCat = db()->query('SELECT * FROM room_categories ORDER BY id ASC');
    $categories = $stmtCat->fetchAll();
    foreach ($categories as $cat) {
        $block->setContent('cat_list.id', (string)$cat['id']);
        $block->setContent('cat_list.name', htmlspecialchars($cat['name']));
        $block->setContent('cat_list.description', htmlspecialchars(substr($cat['description'] ?? '', 0, 90) . '...'));
        $block->setContent('cat_list.base_price', number_format((float)$cat['base_price'], 2, ',', '.'));
        $block->setContent('cat_list.capacity', (string)$cat['capacity']);
        $block->setContent('cat_list.image_url', htmlspecialchars($cat['image_url'] ?? 'deluxe_singola.jpg'));
    }
} catch (Exception $e) {
    $block->setContent('error', 'Errore DB: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
