<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

if (!empty($_SESSION['user']['id'])) {
    header('Location: ' . $config['base'] . '/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
        $error = 'I campi Nome, Cognome, Email e Password sono obbligatori.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Formato indirizzo email non valido.';
    } elseif (strlen($password) < 6) {
        $error = 'La password deve contenere almeno 6 caratteri.';
    } elseif ($password !== $confirm) {
        $error = 'Le due password inserite non coincidono.';
    } else {
        try {
            $check = db()->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'Questo indirizzo email è già registrato nel sistema.';
            } else {
                $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                $stmt = db()->prepare('INSERT INTO users (first_name, last_name, email, password, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$firstName, $lastName, $email, $hashedPass, $phone]);
                $userId = (int)db()->lastInsertId();

                // Assegna il ruolo Guest (group_id = 3)
                $insGroup = db()->prepare('INSERT INTO user_gruppi (user_id, group_id) VALUES (?, 3)');
                $insGroup->execute([$userId]);

                // Esegui login immediato
                $_SESSION['user'] = [
                    'id' => $userId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => $firstName . ' ' . $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'image_url' => '',
                    'services' => load_user_services($userId)
                ];

                header('Location: ' . $config['base'] . '/index.php?reg=success');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Si è verificato un errore durante la registrazione: ' . $e->getMessage();
        }
    }
}

$page = new_page('customers', 'frame-public');
$block = new_block('register');
$block->setContent('error', $error);
$block->setContent('success', $success);
$block->setContent('first_name_val', htmlspecialchars($_POST['first_name'] ?? ''));
$block->setContent('last_name_val', htmlspecialchars($_POST['last_name'] ?? ''));
$block->setContent('email_val', htmlspecialchars($_POST['email'] ?? ''));
$block->setContent('phone_val', htmlspecialchars($_POST['phone'] ?? ''));

$page->setContent('body', $block->get());
$page->close();
