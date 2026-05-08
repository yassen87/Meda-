<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pdo = medal_pdo();
if ($pdo === null) {
    die(t('admin_err_db_not_configured'));
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$clientId = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
$name = trim($_POST['name'] ?? '');
$level = trim($_POST['level'] ?? '');
$status = trim($_POST['status'] ?? 'active');
$notes = trim($_POST['notes'] ?? '');

if ($clientId === 0 || $name === '') {
    die(t('admin_err_bad_request'));
}

try {
    if ($id > 0) {
        $st = $pdo->prepare('UPDATE students SET name = ?, level = ?, status = ?, notes = ? WHERE id = ? AND client_id = ?');
        $st->execute([$name, $level, $status, $notes, $id, $clientId]);
    } else {
        $st = $pdo->prepare('INSERT INTO students (client_id, name, level, status, notes) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$clientId, $name, $level, $status, $notes]);
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

header('Location: students.php?client_id=' . $clientId);
exit;
