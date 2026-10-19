<?php
/* ════════════════════════════════════════════════════════
   index.php · Página principal
   Lista de eventos, resumen por categoría, Editar y Borrar
   ════════════════════════════════════════════════════════ */

require_once __DIR__ . '/conexion.php';

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

// --- Borrar un evento (cada tarjeta envía un formulario POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar'])) {
    $idBorrar = (int) $_POST['borrar'];

    $stmt = $mysqli->prepare('DELETE FROM eventos WHERE id = ?');
    $stmt->bind_param('i', $idBorrar);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();

    header('Location: index.php?borrado=1');
    exit;
}

// Mensajes que llegan por la URL después de guardar, editar o borrar
$guardado = isset($_GET['ok']);
$borrado  = isset($_GET['borrado']);
$editado  = isset($_GET['editado']);

// 1. Lista de eventos con el nombre de su categoría (JOIN)
$resultado = $mysqli->query(
    'SELECT e.id, e.titulo, e.fecha, e.hora, e.descripcion, c.nombre AS categoria
       FROM eventos e
       JOIN categorias c ON c.id = e.categoria_id
      ORDER BY e.fecha, e.hora'
);
$eventos = $resultado->fetch_all(MYSQLI_ASSOC);

// 2. Conteo de eventos por categoría (incluye las categorías vacías)
$resumen = $mysqli->query(
    'SELECT c.nombre, COUNT(e.id) AS total
       FROM categorias c
       LEFT JOIN eventos e ON e.categoria_id = c.id
      GROUP BY c.id, c.nombre
      ORDER BY c.nombre'
)->fetch_all(MYSQLI_ASSOC);

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
    <title>Mis eventos · AgendaWeb</title>
</head>
<body>
<div class="card tarjeta-ancha">
    <nav class="page-tabs" aria-label="Navegación principal">
        <a class="page-tabs__link is-active" href="index.php" aria-current="page">Ver eventos</a>
        <a class="page-tabs__link" href="registrar.php">Registrar evento</a>
    </nav>

    <h1 class="card__title">Mis eventos</h1>
    <p class="card__meta">AgendaWeb</p>

    <!-- Resumen de conteo por categoría -->
    <p class="resumen">
        <?php foreach ($resumen as $r): ?>
            <span class="card__badge"><?= e($r['nombre']) ?> · <?= (int) $r['total'] ?></span>
        <?php endforeach; ?>
    </p>

    <!-- Alertas de éxito -->
    <?php if ($guardado): ?>
        <div class="alert alert--ok" role="status">✅ Evento guardado correctamente.</div>
    <?php elseif ($borrado): ?>
        <div class="alert alert--ok" role="status">🗑️ Evento eliminado permanentemente.</div>
    <?php elseif ($editado): ?>
        <div class="alert alert--ok" role="status">✏️ Evento actualizado correctamente.</div>
    <?php endif; ?>

    <!-- Lista de eventos -->
    <?php if (empty($eventos)): ?>
        <p style="text-align: center; color: var(--text-soft); margin: 40px 0;">
            No tienes eventos registrados aún. 📝
        </p>
    <?php else: ?>
        <ul class="evento-lista">
            <?php foreach ($eventos as $evento): ?>
                <li class="evento-item">
                    <div class="evento-titulo"><?= e($evento['titulo']) ?></div>
                    <div class="evento-detalles">
                        <span>📅 <?= e($evento['fecha']) ?>
                            <?= $evento['hora'] ? '🕒 ' . e(substr($evento['hora'], 0, 5)) : '' ?></span>
                        <span class="categoria-tag">🏷️ <?= e($evento['categoria']) ?></span>
                    </div>
                    <?php if ($evento['descripcion']): ?>
                        <p style="font-size: 0.85rem; color: var(--text-soft); margin: 8px 0 0;"><?= e($evento['descripcion']) ?></p>
                    <?php endif; ?>

                    <!-- Botones de acción con confirmación -->
                    <div class="evento-acciones">
                        <a href="registrar.php?id=<?= (int) $evento['id'] ?>"
                           class="btn-small btn-edit"
                           onclick="return confirm('¿Estás seguro de que deseas editar este evento?');">✏️ Editar</a>

                        <form method="post" action="index.php" style="margin: 0;"
                              onsubmit="return confirm('¿Estás seguro de que deseas eliminar este evento permanentemente?');">
                            <input type="hidden" name="borrar" value="<?= (int) $evento['id'] ?>">
                            <button type="submit" class="btn-small btn-delete">🗑️ Borrar</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div style="margin-top: 24px;">
        <a href="registrar.php" class="btn-primary" style="display: block; text-align: center;">+ Nuevo evento</a>
    </div>
</div>
</body>
</html>