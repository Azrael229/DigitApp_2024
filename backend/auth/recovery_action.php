<?php
declare(strict_types=1);

require_once __DIR__ . '/mail_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !auth_verify_csrf($_POST['csrf'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no válida.');
}

$mode = (string) ($_POST['mode'] ?? 'request');
try {
    $db = auth_db();
    if ($mode === 'request') {
        $email = mb_strtolower(trim((string) ($_POST['correo'] ?? '')));
        $stmt = $db->prepare('SELECT id, nombre_completo, correo_recuperacion FROM usuarios
            WHERE correo = ? AND estado = \'activo\' AND correo_recuperacion_verificado_at IS NOT NULL LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user) {
            $selector = auth_random_token(12);
            $validator = auth_random_token(32);
            $hash = hash('sha256', $validator);
            $expires = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');
            $userId = (int) $user['id'];
            $invalidate = $db->prepare('UPDATE usuario_recuperaciones SET used_at = NOW() WHERE usuario_id = ? AND used_at IS NULL');
            $invalidate->bind_param('i', $userId);
            $invalidate->execute();
            $invalidate->close();
            $insert = $db->prepare('INSERT INTO usuario_recuperaciones
                (usuario_id, selector, token_hash, expires_at, requested_ip) VALUES (?, ?, ?, ?, ?)');
            $ip = auth_client_ip();
            $insert->bind_param('issss', $userId, $selector, $hash, $expires, $ip);
            $insert->execute();
            $insert->close();
            $url = auth_absolute_url('restablecer_password.php?token=' . urlencode($selector . '.' . $validator));
            $sent = auth_send_mail((string) $user['correo_recuperacion'], 'Restablecer contraseña de DigitApp 2024',
                "Hola {$user['nombre_completo']},\n\nAbre este enlace para establecer una contraseña nueva:\n{$url}\n\nEl enlace vence en 30 minutos y solo puede utilizarse una vez.");
            auth_log($sent ? 'recuperacion_solicitada' : 'recuperacion_no_enviada', $sent ? 'correcto' : 'fallido', $userId);
        }
        $_SESSION['recovery_message'] = 'Si la cuenta tiene un correo de recuperación verificado, recibirás un enlace válido durante 30 minutos.';
        header('Location: ../../recuperar_password.php');
        exit;
    }
    if ($mode === 'reset') {
        $parts = auth_token_parts((string) ($_POST['token'] ?? ''));
        if (!$parts) {
            throw new InvalidArgumentException('El enlace no es válido.');
        }
        [$selector, $validator] = $parts;
        $stmt = $db->prepare('SELECT id, usuario_id, token_hash FROM usuario_recuperaciones
            WHERE selector = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1 FOR UPDATE');
        $stmt->bind_param('s', $selector);
        $stmt->execute();
        $token = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$token || !hash_equals((string) $token['token_hash'], hash('sha256', $validator))) {
            throw new InvalidArgumentException('El enlace venció o ya fue utilizado.');
        }
        $password = (string) ($_POST['password'] ?? '');
        if ($password !== (string) ($_POST['confirmacion'] ?? '')) {
            throw new InvalidArgumentException('La confirmación de la contraseña no coincide.');
        }
        auth_change_password((int) $token['usuario_id'], $password, null, true, false);
        $used = $db->prepare('UPDATE usuario_recuperaciones SET used_at = NOW() WHERE id = ?');
        $recoveryId = (int) $token['id'];
        $used->bind_param('i', $recoveryId);
        $used->execute();
        $used->close();
        auth_log('password_recuperada', 'correcto', (int) $token['usuario_id']);
        $_SESSION['login_error'] = 'La contraseña se actualizó. Ya puedes iniciar sesión.';
        header('Location: ../../login.php');
        exit;
    }
    throw new InvalidArgumentException('Acción no válida.');
} catch (Throwable $error) {
    $_SESSION['recovery_error'] = $error instanceof InvalidArgumentException ? $error->getMessage() : 'No fue posible completar la recuperación.';
    $target = $mode === 'reset' ? '../../restablecer_password.php?token=' . urlencode((string) ($_POST['token'] ?? '')) : '../../recuperar_password.php';
    header('Location: ' . $target);
    exit;
}
