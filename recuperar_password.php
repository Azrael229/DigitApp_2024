<?php
declare(strict_types=1);
require_once __DIR__ . '/backend/auth/bootstrap.php';
$message = (string) ($_SESSION['recovery_message'] ?? '');
$error = (string) ($_SESSION['recovery_error'] ?? '');
unset($_SESSION['recovery_message'], $_SESSION['recovery_error']);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Recuperar contraseña · DigitApp 2024</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="estilos/style.css?v=20261005-1"></head><body class="auth-page"><main class="container auth-container"><section class="card border-0 shadow-lg auth-card"><div class="card-body p-4 p-md-5"><h1 class="h3">Recuperar contraseña</h1><p class="contactos-muted">Escribe tu correo de acceso. El enlace se enviará al correo de recuperación verificado.</p><?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?><form method="post" action="backend/auth/recovery_action.php"><input type="hidden" name="csrf" value="<?= htmlspecialchars(auth_csrf(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="mode" value="request"><label class="form-label" for="rec-correo">Correo de acceso</label><input id="rec-correo" name="correo" type="email" class="form-control mb-4" required><button class="btn btn-success w-100">Enviar enlace</button></form><div class="text-center mt-4"><a href="login.php">Volver al inicio de sesión</a></div></div></section></main></body></html>

