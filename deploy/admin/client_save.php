<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pdo = medal_pdo();
if ($pdo === null) {
    die(t('admin_err_db_not_configured'));
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if ($name === '' || $email === '') {
    die(t('admin_err_names_required'));
}

try {
    if ($id > 0) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = $pdo->prepare('UPDATE clients SET name = ?, email = ?, phone = ?, password_hash = ? WHERE id = ?');
            $st->execute([$name, $email, $phone, $hash, $id]);
        } else {
            $st = $pdo->prepare('UPDATE clients SET name = ?, email = ?, phone = ? WHERE id = ?');
            $st->execute([$name, $email, $phone, $id]);
        }
    } else {
        if ($password === '') {
            die(t('admin_err_enter_credentials'));
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO clients (name, email, phone, password_hash) VALUES (?, ?, ?, ?)');
        $st->execute([$name, $email, $phone, $hash]);
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        die(t('admin_err_slug_in_use')); // Reuse error for duplicate email
    }
    die("Database Error: " . $e->getMessage());
}

header('Location: clients.php');
exit;
