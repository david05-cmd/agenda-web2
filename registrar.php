<?php
/* ════════════════════════════════════════════════════════
   registrar.php · AgendaWeb
   Formulario multipropósito: Crear (INSERT) y Editar (UPDATE)
   ════════════════════════════════════════════════════════ */

require_once __DIR__ . '/conexion.php';

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

// 1. Cargar las categorías desde la base de datos
$resultado  = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY nombre');
$categorias = $resultado->fetch_all(MYSQLI_ASSOC);
$idsValidos = array_map('intval', array_column($categorias, 'id'));

// 2. ¿Estamos en MODO EDICIÓN (hay ?id=...) o en MODO CREACIÓN?
$idEditar    = (int) ($_GET['id'] ?? 0);
$modoEdicion = $idEditar > 0;

// Valores por defecto (vacíos para crear)
$titulo      = '';
$fecha       = '';
$hora        = '';
$descripcion = '';
$categoriaId = 0;
$errores     = [];

// 3. Modo edición y todavía no se envió el formulario: precargar datos
if ($modoEdicion && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $mysqli->prepare('SELECT titulo, fecha, hora, categoria_id, descripcion FROM eventos WHERE id = ?');
    $stmt->bind_param('i', $idEditar);
    $stmt->execute();
    $eventoDb = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($eventoDb) {
        $titulo      = $eventoDb['titulo'];
        $fecha       = $eventoDb['fecha'];
        $hora        = $eventoDb['hora'] ? substr($eventoDb['hora'], 0, 5) : '';
        $categoriaId = (int) $eventoDb['categoria_id'];
        $descripcion = $eventoDb['descripcion'] ?? '';
    } else {
        // ID inexistente en la URL: pasamos a modo crear
        $modoEdicion = false;
    }
}

// 4. Validar y guardar / actualizar al enviar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo']      ?? '');
    $fecha       = trim($_POST['fecha']       ?? '');
    $hora        = trim($_POST['hora']        ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoriaId = (int) ($_POST['categoria_id'] ?? 0);

    // Validaciones
    if ($titulo === '') {
        $errores['titulo'] = 'El título es obligatorio.';
    } elseif (mb_strlen($titulo) > 120) {
        $errores['titulo'] = 'Máximo 120 caracteres.';
    }

    if ($fecha === '') {
        $errores['fecha'] = 'La fecha es obligatoria.';
    } elseif (!DateTime::createFromFormat('Y-m-d', $fecha)) {
        $errores['fecha'] = 'La fecha no es válida.';
    }

    if (!in_array($categoriaId, $idsValidos, true)) {
        $errores['categoria_id'] = 'Elige una categoría válida.';
    }

    if (mb_strlen($descripcion) > 500) {
        $errores['descripcion'] = 'Máximo 500 caracteres para la descripción.';
    }

    // 5. Guardar en la base de datos
    if (empty($errores)) {
        $horaBD = $hora === '' ? null : $hora;
        $descBD = $descripcion === '' ? null : $descripcion;

        if ($modoEdicion) {
            $sql = 'UPDATE eventos SET titulo = ?, fecha = ?, hora = ?, categoria_id = ?, descripcion = ? WHERE id = ?';
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('sssisi', $titulo, $fecha, $horaBD, $categoriaId, $descBD, $idEditar);
            $urlRedireccion = 'index.php?editado=1';
        } else {
            $sql = 'INSERT INTO eventos (titulo, fecha, hora, categoria_id, descripcion) VALUES (?, ?, ?, ?, ?)';
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('sssis', $titulo, $fecha, $horaBD, $categoriaId, $descBD);
            $urlRedireccion = 'index.php?ok=1';
        }

        if ($stmt->execute()) {
            $stmt->close();
            $mysqli->close();
            header('Location: ' . $urlRedireccion);
            exit;
        }

        $errores['general'] = 'Hubo un error al guardar en la base de datos.';
    }
}
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

    <!-- El título de la pestaña cambia según el modo -->
    <title><?= $modoEdicion ? 'Editar evento' : 'Nuevo evento' ?> · AgendaWeb</title>
</head>
<body>
<div class="card">
    <nav class="page-tabs" aria-label="Navegación principal">
        <a class="page-tabs__link" href="index.php">Ver eventos</a>
        <a class="page-tabs__link is-active" href="registrar.php" aria-current="page">Registrar evento</a>
    </nav>

    <!-- El título de la tarjeta cambia según el modo -->
    <h1 class="card__title"><?= $modoEdicion ? 'Editar evento' : 'Nuevo evento' ?></h1>
    <p class="card__meta">AgendaWeb</p>

    <form method="post" action="">
        <?php if (isset($errores['general'])): ?>
            <div class="alert alert--error"><?= e($errores['general']) ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label" for="titulo">Título</label>
            <input type="text"
                   class="form-input <?= isset($errores['titulo']) ? 'is-invalid' : '' ?>"
                   name="titulo" id="titulo" value="<?= e($titulo) ?>" required>
            <?php if (isset($errores['titulo'])): ?>
                <p class="form-error"><?= e($errores['titulo']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="fecha">Fecha</label>
                <input type="date"
                       class="form-input <?= isset($errores['fecha']) ? 'is-invalid' : '' ?>"
                       name="fecha" id="fecha" value="<?= e($fecha) ?>" required>
                <?php if (isset($errores['fecha'])): ?>
                    <p class="form-error"><?= e($errores['fecha']) ?></p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="hora">Hora (opcional)</label>
                <input type="time" class="form-input" name="hora" id="hora" value="<?= e($hora) ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="categoria_id">Categoría</label>
            <select class="form-select <?= isset($errores['categoria_id']) ? 'is-invalid' : '' ?>"
                    name="categoria_id" id="categoria_id" required>
                <option value="">— Elige una —</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= $categoriaId === (int) $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errores['categoria_id'])): ?>
                <p class="form-error"><?= e($errores['categoria_id']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="descripcion">Descripción (opcional)</label>
            <textarea name="descripcion" id="descripcion" rows="3"
                      class="form-textarea <?= isset($errores['descripcion']) ? 'is-invalid' : '' ?>"><?= e($descripcion) ?></textarea>
            <?php if (isset($errores['descripcion'])): ?>
                <p class="form-error"><?= e($errores['descripcion']) ?></p>
            <?php endif; ?>
        </div>

        <!-- El texto del botón cambia según el modo -->
        <button type="submit" class="btn-primary"><?= $modoEdicion ? 'Actualizar evento' : 'Guardar evento' ?></button>
    </form>

    <p class="card__link"><a href="index.php">Ver mis eventos</a></p>
</div>
</body>
</html>
