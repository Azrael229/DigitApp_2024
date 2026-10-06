<?php
declare(strict_types=1);
require_once __DIR__ . '/backend/auth/bootstrap.php';
if (auth_current_user()) {
    header('Location: index.php');
    exit;
}
$error = (string) ($_SESSION['login_error'] ?? '');
unset($_SESSION['login_error']);
$setupAvailable = in_array((string) ($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true)
    && (int) auth_db()->query('SELECT COUNT(*) AS total FROM usuarios')->fetch_assoc()['total'] === 0;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · DigitApp 2024</title>
    <link rel="icon" href="imgs/LogoMakr_0mWRyT-1.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="estilos/style.css?v=20261005-1">
</head>
<body class="auth-page">
<main class="container auth-container">
    <section class="card border-0 shadow-lg auth-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <img src="imgs/LogoMakr_0mWRyT-1.png" alt="SERVICOM Básculas Digitales" class="auth-logo">
                <p class="contactos-kicker mb-1">SERVICOM Básculas Digitales</p>
                <h1 class="h2 mb-2">DigitApp 2024</h1>
                <p class="contactos-muted mb-0">Inicia sesión con tu correo corporativo.</p>
            </div>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="post" action="backend/auth/login_action.php" autocomplete="on">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(auth_csrf(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3">
                    <label for="login-correo" class="form-label">Correo</label>
                    <input id="login-correo" name="correo" type="email" class="form-control" maxlength="190" autocomplete="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="login-password" class="form-label">Contraseña</label>
                    <input id="login-password" name="password" type="password" class="form-control" maxlength="128" autocomplete="current-password" required>
                </div>
                <div class="mb-4">
                    <label for="login-dispositivo" class="form-label">Nombre de este dispositivo <span class="contactos-muted">(opcional)</span></label>
                    <input id="login-dispositivo" name="dispositivo" type="text" class="form-control" maxlength="120" placeholder="Ej. Laptop oficina">
                    <div class="form-text">Este dispositivo quedará identificado como confiable. La sesión vencerá en 30 días.</div>
                </div>
                <button type="submit" class="btn btn-success w-100">Iniciar sesión</button>
            </form>
            <div class="text-center mt-4"><a href="recuperar_password.php">Olvidé mi contraseña</a></div>
            <?php if ($setupAvailable): ?><div class="alert alert-warning mt-4 mb-0">Aún no existe el administrador. <a href="configurar_administrador.php">Completar configuración inicial</a>.</div><?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
