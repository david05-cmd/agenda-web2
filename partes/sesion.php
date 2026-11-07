<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

$usuarioId     = (int) $_SESSION['uid'];
$usuarioNombre = $_SESSION['nombre'] ?? '';

if (!function_exists('e')) {
    function e(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}