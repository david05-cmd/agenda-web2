<?php
require __DIR__ . '/partes/sesion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/conexion.php';

$stmt = $mysqli->prepare('DELETE FROM eventos WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $id, $usuarioId);
$stmt->execute();
$stmt->close();
$mysqli->close();

header('Location: index.php?eliminado=1', true, 303);
exit;