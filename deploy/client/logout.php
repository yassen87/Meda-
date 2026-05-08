<?php
declare(strict_types=1);

session_start();
unset($_SESSION['client_id']);
unset($_SESSION['client_name']);
session_destroy();

header('Location: login.php');
exit;
