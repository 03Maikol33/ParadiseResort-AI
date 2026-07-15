<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_admin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_amenity') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);

    if ($name === '' || $price < 0) {
        $error = 'Nome Servizio e Prezzo (>= 0) sono obbligatori.';
    } else {
        try {
            if ($id > 0) {
                $upd = db()->prepare('UPDATE amenities SET name = ?, description = ?, price = ? WHERE id = ?');
                $upd->execute([$name, $description, $price, $id]);
                $message = 'Servizio #' . $id . ' aggiornato con successo.';
            } else {
                $ins = db()->prepare('INSERT INTO amenities (name, description, price) VALUES (?, ?, ?)');
                $ins->execute([$name, $description, $price]);
                $message = 'Nuovo servizio facoltativo (' . htmlspecialchars($name) . ') creato nel listino.';
            }
        } catch (Exception $e) {
            $error = 'Errore salvataggio servizio: ' . $e->getMessage();
        }
    }
} elseif (!empty($_GET['del_id'])) {
    $delId = (int)$_GET['del_id'];
    try {
        $del = db()->prepare('DELETE FROM amenities WHERE id = ?');
        $del->execute([$delId]);
        $message = 'Servizio eliminato dal listino.';
    } catch (Exception $e) {
        $error = 'Impossibile eliminare: ci sono prenotazioni storiche o categorie collegate a questo servizio.';
    }
}

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Amministratore', 'admin');

$block = new_block('amenities');
$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    $stmt = db()->query('SELECT * FROM amenities ORDER BY name ASC');
    $amenities = $stmt->fetchAll();

    foreach ($amenities as $am) {
        $block->setContent('amenity_rows.id', (string)$am['id']);
        $block->setContent('amenity_rows.name', htmlspecialchars($am['name']));
        $block->setContent('amenity_rows.description', htmlspecialchars($am['description'] ?? ''));
        $block->setContent('amenity_rows.price', number_format((float)$am['price'], 2, ',', '.'));
    }
} catch (Exception $e) {
    $block->setContent('error', 'Errore DB: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
