<?php
require __DIR__ . '/partes/sesion.php';
require __DIR__ . '/conexion.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    header('Location: index.php?noexiste=1');
    exit;
}

// Cargar evento solo si le pertenece al usuario actual
$stmt = $mysqli->prepare(
    'SELECT titulo, fecha, hora, categoria_id, descripcion FROM eventos WHERE id = ? AND usuario_id = ?'
);
$stmt->bind_param('ii', $id, $usuarioId);
$stmt->execute();
$evento = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($evento === null) {
    header('Location: index.php?noexiste=1');
    exit;
}

$resultado  = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY nombre');
$categorias = $resultado->fetch_all(MYSQLI_ASSOC);
$resultado->free();
$idsValidos = array_map('intval', array_column($categorias, 'id'));

$errores     = [];
$titulo      = $evento['titulo'];
$fecha       = $evento['fecha'];
$hora        = $evento['hora'] !== null ? substr($evento['hora'], 0, 5) : '';
$categoriaId = (int) $evento['categoria_id'];
$descripcion = $evento['descripcion'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
    $titulo      = $leer('titulo');
    $fecha       = $leer('fecha');
    $hora        = $leer('hora');
    $categoriaId = (int) $leer('categoria_id');
    $descripcion = $leer('descripcion');

    if ($titulo === '') {
        $errores['titulo'] = 'El título es obligatorio.';
    } elseif (mb_strlen($titulo) > 120) {
        $errores['titulo'] = 'El título no puede exceder los 120 caracteres.';
    }

    if ($fecha === '') {
        $errores['fecha'] = 'La fecha es obligatoria.';
    } else {
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!($fechaObj && $fechaObj->format('Y-m-d') === $fecha)) {
            $errores['fecha'] = 'Proporciona una fecha válida (AAAA-MM-DD).';
        }
    }

    if ($hora !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) {
        $errores['hora'] = 'Proporciona una hora válida (HH:MM).';
    }

    if (!in_array($categoriaId, $idsValidos, true)) {
        $errores['categoria_id'] = 'Elige una categoría válida.';
    }

    if (mb_strlen($descripcion) > 500) {
        $errores['descripcion'] = 'La descripción no puede superar los 500 caracteres.';
    }

    if (empty($errores)) {
        $hora_db        = ($hora !== '') ? $hora : null;
        $descripcion_db = ($descripcion !== '') ? $descripcion : null;

        $stmt = $mysqli->prepare(
            'UPDATE eventos
                SET titulo = ?, fecha = ?, hora = ?, categoria_id = ?, descripcion = ?
              WHERE id = ? AND usuario_id = ?'
        );
        $stmt->bind_param('sssisii', $titulo, $fecha, $hora_db, $categoriaId, $descripcion_db, $id, $usuarioId);
        $stmt->execute();
        $stmt->close();
        $mysqli->close();

        header('Location: index.php?editado=1', true, 303);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaWeb · Editar Evento</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="layout">

    <header class="site-header">
        <div class="contenedor site-header__inner">
            <a href="index.php" class="logo">Agenda<span>Web</span></a>
            <nav class="nav">
                <span class="nav__link">Hola, <?= e($usuarioNombre) ?></span>
                <a href="index.php" class="nav__link">Mis eventos</a>
                <a href="registrar.php" class="nav__link">Nuevo evento</a>
                <a href="logout.php" class="nav__link">Salir</a>
            </nav>
        </div>
    </header>

    <main class="contenedor">
        <section class="tarjeta">
            <h2>Editar Evento</h2>

            <form method="post" action="editar.php?id=<?= (int) $id ?>" class="formulario-evento" novalidate>

                <div class="grupo-campo">
                    <label for="titulo">Título *</label>
                    <input
                        type="text"
                        id="titulo"
                        name="titulo"
                        value="<?= e($titulo) ?>"
                        class="<?= isset($errores['titulo']) ? 'input-error' : '' ?>"
                    >
                    <?php if (isset($errores['titulo'])): ?>
                        <span class="error-texto"><?= e($errores['titulo']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo">
                    <label for="fecha">Fecha *</label>
                    <input
                        type="date"
                        id="fecha"
                        name="fecha"
                        value="<?= e($fecha) ?>"
                        class="<?= isset($errores['fecha']) ? 'input-error' : '' ?>"
                    >
                    <?php if (isset($errores['fecha'])): ?>
                        <span class="error-texto"><?= e($errores['fecha']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo">
                    <label for="hora">Hora</label>
                    <input
                        type="time"
                        id="hora"
                        name="hora"
                        value="<?= e($hora) ?>"
                        class="<?= isset($errores['hora']) ? 'input-error' : '' ?>"
                    >
                    <?php if (isset($errores['hora'])): ?>
                        <span class="error-texto"><?= e($errores['hora']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo">
                    <label for="categoria_id">Categoría *</label>
                    <select
                        id="categoria_id"
                        name="categoria_id"
                        class="<?= isset($errores['categoria_id']) ? 'input-error' : '' ?>"
                    >
                        <option value="">— Elige una —</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>"
                                <?= $categoriaId === (int) $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errores['categoria_id'])): ?>
                        <span class="error-texto"><?= e($errores['categoria_id']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo campo-completo">
                    <label for="descripcion">Descripción</label>
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="3"
                        class="<?= isset($errores['descripcion']) ? 'input-error' : '' ?>"
                    ><?= e($descripcion) ?></textarea>
                    <?php if (isset($errores['descripcion'])): ?>
                        <span class="error-texto"><?= e($errores['descripcion']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="acciones-form campo-completo">
                    <button type="submit" class="btn-primary">Guardar Cambios</button>
                    <a href="index.php" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </section>
    </main>

    <footer class="site-footer">
        <div class="contenedor">
            AgendaWeb · Jose Luis · 2026
        </div>
    </footer>

</body>
</html>