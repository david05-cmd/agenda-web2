<?php
/* ════════════════════════════════════════════════════════
   registrar.php · AgendaWeb
   Categorías dinámicas desde la BD (P5)
   ════════════════════════════════════════════════════════ */

require 'conexion.php';

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

// Obtener categorías para el <select> y validación
$resultado  = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY nombre');
$categorias = $resultado->fetch_all(MYSQLI_ASSOC);

// Crear lista blanca dinámica: [1, 2, 3, 4]
$idsValidos = array_map('intval', array_column($categorias, 'id'));

// Variables por defecto
$titulo = '';
$fecha = '';
$hora = '';
$descripcion = '';
$categoriaId = 0;
$errores = [];

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

    // Guardado
    if (empty($errores)) {
        $sql = 'INSERT INTO eventos (titulo, fecha, hora, categoria_id, descripcion) VALUES (?, ?, ?, ?, ?)';
        $stmt = $mysqli->prepare($sql);

        $horaBD = $hora === '' ? null : $hora;
        $descBD = $descripcion === '' ? null : $descripcion;

        // "sssis" -> string, string, string, integer, string
        $stmt->bind_param('sssis', $titulo, $fecha, $horaBD, $categoriaId, $descBD);
        
        if ($stmt->execute()) {
            $stmt->close();
            $mysqli->close();
            header('Location: index.php?ok=1');
            exit;
        } else {
            $errores['general'] = "Hubo un error al guardar en la base de datos.";
        }
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
    <title>Nuevo evento · AgendaWeb</title>
</head>
<body>
<div class="card">
    <h1 class="card__title">Nuevo evento</h1>
    <p class="card__meta">AgendaWeb de David Moreno Orozco</p>

    <form method="post" action="">
        <?php if (isset($errores['general'])): ?>
            <div class="alert alert--error"><?= e($errores['general']) ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label" for="titulo">Título</label>
            <input type="text" class="form-input <?= isset($errores['titulo']) ? 'is-invalid' : '' ?>" name="titulo" id="titulo" value="<?= e($titulo) ?>" required>
            <?php if (isset($errores['titulo'])): ?>
                <p class="form-error"><?= e($errores['titulo']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="fecha">Fecha</label>
                <input type="date" class="form-input <?= isset($errores['fecha']) ? 'is-invalid' : '' ?>" name="fecha" id="fecha" value="<?= e($fecha) ?>" required>
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
            <select class="form-select <?= isset($errores['categoria_id']) ? 'is-invalid' : '' ?>" name="categoria_id" id="categoria_id" required>
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
            <textarea name="descripcion" id="descripcion" rows="3" class="form-textarea <?= isset($errores['descripcion']) ? 'is-invalid' : '' ?>"><?= e($descripcion) ?></textarea>
            <?php if (isset($errores['descripcion'])): ?>
                <p class="form-error"><?= e($errores['descripcion']) ?></p>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-primary">Guardar evento</button>
    </form>

    <p class="card__link"><a href="index.php">Ver mis eventos</a></p>
</div>
</body>
</html>