<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pdo = medal_pdo();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

if ($id > 0 && $clientId > 0 && $pdo !== null) {
    try {
        $st = $pdo->prepare('DELETE FROM students WHERE id = ? AND client_id = ?');
        $st->execute([$id, $clientId]);
    } catch (Throwable $e) {}
}

header('Location: students.php?client_id=' . $clientId);
exit;
