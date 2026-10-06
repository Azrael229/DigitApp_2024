<?php
declare(strict_types=1);
require_once __DIR__ . '/backend/auth/bootstrap.php';
$message = 'El enlace de verificación no es válido o ya venció.';
$ok = false;
$parts = auth_token_parts((string) ($_GET['token'] ?? ''));
if ($parts) {
    [$selector, $validator] = $parts;
    $stmt = auth_db()->prepare('SELECT id, usuario_id, correo, token_hash FROM usuario_verificaciones_correo
        WHERE selector = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
    $stmt->bind_param('s', $selector);
    $stmt->execute();
    $token = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($token && hash_equals((string) $token['token_hash'], hash('sha256', $validator))) {
        $db = auth_db();
        $db->begin_transaction();
        try {
            $update = $db->prepare('UPDATE usuarios SET correo_recuperacion = ?,
                correo_recuperacion_verificado_at = NOW(), version = version + 1 WHERE id = ?');
            $verifiedEmail = (string) $token['correo'];
            $verifiedUserId = (int) $token['usuario_id'];
            $update->bind_param('si', $verifiedEmail, $verifiedUserId);
            $update->execute();
            $update->close();
            $used = $db->prepare('UPDATE usuario_verificaciones_correo SET used_at = NOW() WHERE id = ?');
            $verificationId = (int) $token['id'];
            $used->bind_param('i', $verificationId);
            $used->execute();
            $used->close();
            $db->commit();
            auth_log('correo_recuperacion_verificado', 'correcto', (int) $token['usuario_id'], (int) $token['usuario_id']);
            $message = 'El correo de recuperación quedó verificado correctamente.';
            $ok = true;
        } catch (Throwable $error) {
            $db->rollback();
        }
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Verificar correo · DigitApp 2024</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="estilos/style.css?v=20261005-1"></head><body class="auth-page"><main class="container auth-container"><section class="card border-0 shadow-lg auth-card"><div class="card-body p-4 p-md-5"><h1 class="h3">Correo de recuperación</h1><div class="alert alert-<?= $ok ? 'success' : 'danger' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><a class="btn btn-secondary w-100" href="login.php">Continuar</a></div></section></main></body></html>
