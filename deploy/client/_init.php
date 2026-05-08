<?php
declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/locale.php';
require_once dirname(__DIR__) . '/includes/translations.php';
require_once dirname(__DIR__) . '/includes/product_translations.php';

function is_client_logged_in(): bool {
    return isset($_SESSION['client_id']);
}

function require_client(): void {
    if (!is_client_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function client_id(): int {
    return (int)($_SESSION['client_id'] ?? 0);
}

function client_name(): string {
    return (string)($_SESSION['client_name'] ?? '');
}
