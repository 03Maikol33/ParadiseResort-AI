<?php
require_once __DIR__ . '/../include/bootstrap.inc.php';

require_login();
require_admin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_service') {
        $scriptName = trim($_POST['script_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($scriptName !== '') {
            try {
                $ins = db()->prepare('INSERT INTO services (script_name, description) VALUES (?, ?)');
                $ins->execute([$scriptName, $description]);
                
                // Assegna automaticamente all'Admin
                $newId = db()->lastInsertId();
                $insGrp = db()->prepare('INSERT INTO group_services (group_id, service_id) VALUES (1, ?)');
                $insGrp->execute([$newId]);
                
                $message = 'Nuovo servizio (' . htmlspecialchars($scriptName) . ') registrato con successo.';
            } catch (Exception $e) {
                $error = 'Errore durante l\'inserimento: ' . $e->getMessage();
            }
        } else {
            $error = 'Il nome dello script è obbligatorio.';
        }
    } elseif ($_POST['action'] === 'save_matrix') {
        try {
            db()->beginTransaction();
            db()->exec('DELETE FROM group_services');
            
            // L'amministratore (Group 1) ha SEMPRE accesso a tutti i servizi
            $stmtServices = db()->query('SELECT id FROM services');
            $allServices = $stmtServices->fetchAll(PDO::FETCH_COLUMN);
            $insPerm = db()->prepare('INSERT INTO group_services (group_id, service_id) VALUES (?, ?)');
            foreach ($allServices as $sId) {
                $insPerm->execute([1, $sId]);
            }

            if (!empty($_POST['perm']) && is_array($_POST['perm'])) {
                foreach ($_POST['perm'] as $groupId => $srvList) {
                    if ((int)$groupId === 1) continue; // Salta il gruppo 1 perché l'abbiamo già gestito d'ufficio
                    if (is_array($srvList)) {
                        foreach ($srvList as $serviceId => $val) {
                            $insPerm->execute([(int)$groupId, (int)$serviceId]);
                        }
                    }
                }
            }
            db()->commit();
            
            // Ricarica servizi dell'admin in sessione
            $_SESSION['user']['services'] = load_user_services((int)$_SESSION['user']['id']);
            $message = 'Matrice dei permessi RBAC salvata e aggiornata con successo!';
        } catch (Exception $e) {
            if (db()->inTransaction()) db()->rollBack();
            $error = 'Errore durante il salvataggio della matrice permessi: ' . $e->getMessage();
        }
    }
} elseif (!empty($_GET['del_service_id'])) {
    $delId = (int)$_GET['del_service_id'];
    try {
        db()->beginTransaction();
        $delPerms = db()->prepare('DELETE FROM group_services WHERE service_id = ?');
        $delPerms->execute([$delId]);
        $del = db()->prepare('DELETE FROM services WHERE id = ?');
        $del->execute([$delId]);
        db()->commit();
        $message = 'Servizio #' . $delId . ' eliminato con successo.';
    } catch (Exception $e) {
        if (db()->inTransaction()) db()->rollBack();
        $error = 'Errore durante l\'eliminazione del servizio: ' . $e->getMessage();
    }
}

$page = new_page('administration', 'frame-private');
setup_backoffice_page($page, 'Amministratore', 'admin');

$block = new_block('services');
$block->setContent('message', $message);
$block->setContent('error', $error);

try {
    $stmtGroups = db()->query('SELECT * FROM gruppi ORDER BY id ASC');
    $groups = $stmtGroups->fetchAll();

    $stmtServices = db()->query('SELECT * FROM services ORDER BY id ASC');
    $servicesList = $stmtServices->fetchAll();

    $stmtMatrix = db()->query('SELECT * FROM group_services');
    $matrixRows = $stmtMatrix->fetchAll();
    $matrix = [];
    foreach ($matrixRows as $r) {
        $matrix[$r['group_id']][$r['service_id']] = true;
    }

    // Per ogni servizio, popoliamo la riga
    foreach ($servicesList as $s) {
        $block->setContent('service_rows.id', (string)$s['id']);
        $block->setContent('service_rows.script_name', htmlspecialchars($s['script_name']));
        $block->setContent('service_rows.description', htmlspecialchars($s['description'] ?? ''));

        $chkAdmin = isset($matrix[1][$s['id']]) ? 'checked' : '';
        $chkRecep = isset($matrix[2][$s['id']]) ? 'checked' : '';
        $chkGuest = isset($matrix[3][$s['id']]) ? 'checked' : '';

        $block->setContent('service_rows.check_admin', $chkAdmin);
        $block->setContent('service_rows.check_receptionist', $chkRecep);
        $block->setContent('service_rows.check_guest', $chkGuest);
    }
} catch (Exception $e) {
    $block->setContent('error', 'Errore DB: ' . $e->getMessage());
}

$page->setContent('body', $block->get());
$page->close();
