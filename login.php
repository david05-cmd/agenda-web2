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

$correo = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $clave  = $_POST['clave'] ?? '';

    $stmt = $mysqli->prepare('SELECT id, nombre, clave_hash FROM usuarios WHERE correo = ?');
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($usuario && password_verify($clave, $usuario['clave_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid']    = (int) $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        header('Location: index.php');
        exit;
    }

    $error = 'Correo o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaWeb · Iniciar sesión</title>
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
                <a href="login.php" class="nav__link is-active">Iniciar sesión</a>
                <a href="registro.php" class="nav__link">Crear cuenta</a>
            </nav>
        </div>
    </header>

    <main class="contenedor">
        <?php if (isset($_GET['registrado'])): ?>
            <div class="alert alert--ok">Cuenta creada. Ya puedes iniciar sesión.</div>
        <?php endif; ?>
        <?php if (isset($_GET['salio'])): ?>
            <div class="alert alert--ok">Cerraste sesión. ¡Hasta pronto!</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <section class="tarjeta">
            <h2>Iniciar sesión</h2>

            <form method="post" action="login.php" class="formulario-evento" novalidate>
                <div class="grupo-campo">
                    <label for="correo">Correo</label>
                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        value="<?= e($correo) ?>"
                        required
                    >
                </div>

                <div class="grupo-campo">
                    <label for="clave">Contraseña</label>
                    <input
                        type="password"
                        id="clave"
                        name="clave"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="acciones-form campo-completo">
                    <button type="submit" class="btn-primary">Entrar</button>
                    <a href="registro.php" class="btn-secondary">Crear cuenta</a>
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