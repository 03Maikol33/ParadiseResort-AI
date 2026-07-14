<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

if (!empty($_SESSION['user']['id'])) {
    if (is_admin()) {
        header('Location: ' . $config['base'] . '/admin/index.php');
    } elseif (is_receptionist()) {
        header('Location: ' . $config['base'] . '/receptionist/index.php');
    } else {
        header('Location: ' . $config['base'] . '/index.php');
    }
    exit;
}

$error = '';
$message = '';
if (!empty($_GET['logged_out'])) {
    $message = 'Hai effettuato il logout con successo.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if ($email === '' || $password === '') {
        $error = 'Inserisci email e password per accedere.';
    } else {
        try {
            $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $u = $stmt->fetch();

            if ($u && password_verify($password, $u['password'])) {
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

                if ($remember) {
                    $token = $u['id'] . ':' . hash_hmac('sha256', $u['id'] . '-' . $u['password'], 'ParadiseResortSecretKey2026');
                    setcookie('paradise_remember', $token, time() + 86400 * 30, '/');
                }

                if (is_admin()) {
                    header('Location: ' . $config['base'] . '/admin/index.php');
                } elseif (is_receptionist()) {
                    header('Location: ' . $config['base'] . '/receptionist/index.php');
                } else {
                    $redirect = $_GET['redirect'] ?? ($config['base'] . '/index.php');
                    header('Location: ' . $redirect);
                }
                exit;
            } else {
                $error = 'Credenziali non valide. Controlla email e password.';
            }
        } catch (Exception $e) {
            $error = 'Errore di connessione durante l\'autenticazione.';
        }
    }
}

$page = new_page('customers', 'frame-public');
$block = new_block('login');
$block->setContent('error', $error);
$block->setContent('message', $message);
$block->setContent('email_val', htmlspecialchars($_POST['email'] ?? ''));

$page->setContent('body', $block->get());
$page->close();
