<?php
require __DIR__ . '/partes/sesion.php';
require __DIR__ . '/conexion.php';

$avisos = [
    'ok'        => 'Evento guardado correctamente.',
    'editado'   => 'Evento actualizado correctamente.',
    'eliminado' => 'Evento eliminado.',
    'noexiste'  => 'Ese evento no existe.',
];
$aviso      = null;
$avisoClase = 'alert--ok';
foreach ($avisos as $clave => $mensaje) {
    if (($_GET[$clave] ?? null) === '1') {
        $aviso = $mensaje;
        if ($clave === 'noexiste') {
            $avisoClase = 'alert--error';
        }
        break;
    }
}

// Lista filtrada por usuario
$stmt = $mysqli->prepare(
    'SELECT e.id, e.titulo, e.fecha, e.hora, e.descripcion,
            c.id AS categoria_id, c.nombre AS categoria
       FROM eventos e
       JOIN categorias c ON c.id = e.categoria_id
      WHERE e.usuario_id = ?
      ORDER BY e.fecha ASC, e.hora ASC'
);
$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Resumen: el filtro va en el ON para que las categorías sin eventos salgan en 0
$stmt = $mysqli->prepare(
    'SELECT c.nombre, COUNT(e.id) AS total
       FROM categorias c
       LEFT JOIN eventos e ON e.categoria_id = c.id AND e.usuario_id = ?
      GROUP BY c.id, c.nombre
      ORDER BY c.nombre'
);
$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaWeb · Mis Eventos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <header class="site-header">
        <div class="contenedor site-header__inner">
            <a href="index.php" class="logo">Agenda<span>Web</span></a>
            <nav class="nav">
                <span class="nav__link">Hola, <?= e($usuarioNombre) ?></span>
                <a href="index.php" class="nav__link is-active">Mis eventos</a>
                <a href="registrar.php" class="nav__link">Nuevo evento</a>
                <a href="logout.php" class="nav__link">Salir</a>
            </nav>
        </div>
    </header>

    <main class="contenedor">
        <?php if ($aviso !== null): ?>
            <div class="alert <?= $avisoClase ?>" role="status">
                <?= $avisoClase === 'alert--ok' ? '&#9989; ' : '' ?><?= e($aviso) ?>
            </div>
        <?php endif; ?>

        <div class="page__header">
            <div>
                <h1 class="page__title">Mis eventos</h1>
                <p class="page__subtitle">Lista de compromisos y actividades registradas</p>
            </div>
            <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
        </div>

        <p class="resumen">
            <?php foreach ($resumen as $r): ?>
                <span class="card__badge badge-azul">
                    <?= e($r['nombre']) ?> · <?= (int) $r['total'] ?>
                </span>
            <?php endforeach; ?>
        </p>

        <?php if (empty($eventos)): ?>
            <div class="empty-state">
                <p>No tienes eventos registrados aún.</p>
                <a href="registrar.php" class="btn-primary">+ Crear el primero</a>
            </div>
        <?php else: ?>
            <section class="card-list">
                <?php foreach ($eventos as $evento): ?>
                    <article class="card">
                        <span class="card__badge <?= in_array((int) $evento['categoria_id'], [1, 3], true) ? 'badge-teal' : 'badge-azul' ?>">
                            <?= e($evento['categoria']) ?>
                        </span>

                        <h3 class="card__title"><?= e($evento['titulo']) ?></h3>

                        <div class="card__meta">
                            📅 <?= e($evento['fecha']) ?>
                            <?php if (!empty($evento['hora'])): ?>
                                · ⏰ <?= e(date('H:i', strtotime($evento['hora']))) ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($evento['descripcion'])): ?>
                            <p class="card__text"><?= nl2br(e($evento['descripcion'])) ?></p>
                        <?php endif; ?>

                        <div class="card__actions">
                            <a href="editar.php?id=<?= (int) $evento['id'] ?>" class="btn-secondary btn-sm">Editar</a>
                            <form action="borrar.php" method="post" class="form-inline"
                                  onsubmit="return confirm('¿Eliminar este evento? Esta acción no se puede deshacer.');">
                                <input type="hidden" name="id" value="<?= (int) $evento['id'] ?>">
                                <button type="submit" class="btn-danger btn-sm">Eliminar</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="contenedor">
            AgendaWeb · 2026
        </div>
    </footer>
</body>
</html>