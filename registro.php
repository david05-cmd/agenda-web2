<?php
session_start();
if (isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
require __DIR__ . '/conexion.php';

if (!function_exists('e')) {
    function e(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

$nombre  = '';
$correo  = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $clave  = $_POST['clave']  ?? '';
    $clave2 = $_POST['clave2'] ?? '';

    if ($nombre === '' || mb_strlen($nombre) > 80) {
        $errores['nombre'] = 'Escribe tu nombre (máximo 80 caracteres).';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Escribe un correo válido.';
    }
    if (mb_strlen($clave) < 8) {
        $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        try {
            $stmt = $mysqli->prepare('INSERT INTO usuarios (nombre, correo, clave_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $nombre, $correo, $hash);
            $stmt->execute();
            $stmt->close();
            $mysqli->close();
            header('Location: login.php?registrado=1');
            exit;
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {
                $errores['correo'] = 'Ese correo ya tiene una cuenta.';
            } else {
                $errores['general'] = 'No se pudo crear la cuenta. Intenta de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaWeb · Crear cuenta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="layout">
    <header class="site-header">
        <div class="contenedor site-header__inner">
            <a href="login.php" class="logo">Agenda<span>Web</span></a>
            <nav class="nav">
                <a href="login.php" class="nav__link">Iniciar sesión</a>
                <a href="registro.php" class="nav__link is-active">Crear cuenta</a>
            </nav>
        </div>
    </header>

    <main class="contenedor">
        <?php if (isset($errores['general'])): ?>
            <div class="alert alert--error"><?= e($errores['general']) ?></div>
        <?php endif; ?>

        <section class="tarjeta">
            <h2>Crear cuenta</h2>

            <form method="post" action="registro.php" class="formulario-evento" novalidate>
                <div class="grupo-campo">
                    <label for="nombre">Nombre</label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        maxlength="80"
                        value="<?= e($nombre) ?>"
                        class="<?= isset($errores['nombre']) ? 'input-error' : '' ?>"
                        required
                    >
                    <?php if (isset($errores['nombre'])): ?>
                        <span class="error-texto"><?= e($errores['nombre']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo">
                    <label for="correo">Correo</label>
                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        value="<?= e($correo) ?>"
                        class="<?= isset($errores['correo']) ? 'input-error' : '' ?>"
                        required
                    >
                    <?php if (isset($errores['correo'])): ?>
                        <span class="error-texto"><?= e($errores['correo']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo">
                    <label for="clave">Contraseña</label>
                    <input
                        type="password"
                        id="clave"
                        name="clave"
                        minlength="8"
                        autocomplete="new-password"
                        class="<?= isset($errores['clave']) ? 'input-error' : '' ?>"
                        required
                    >
                    <?php if (isset($errores['clave'])): ?>
                        <span class="error-texto"><?= e($errores['clave']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo">
                    <label for="clave2">Repítela</label>
                    <input
                        type="password"
                        id="clave2"
                        name="clave2"
                        minlength="8"
                        autocomplete="new-password"
                        class="<?= isset($errores['clave2']) ? 'input-error' : '' ?>"
                        required
                    >
                    <?php if (isset($errores['clave2'])): ?>
                        <span class="error-texto"><?= e($errores['clave2']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="acciones-form campo-completo">
                    <button type="submit" class="btn-primary">Crear cuenta</button>
                    <a href="login.php" class="btn-secondary">Ya tengo cuenta</a>
                </div>
            </form>
        </section>
    </main>

    <footer class="site-footer">
        <div class="contenedor">
            AgendaWeb · 2026
        </div>
    </footer>
</body>
</html>