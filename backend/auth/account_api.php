<?php
declare(strict_types=1);

require_once __DIR__ . '/mail_service.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $user = auth_require_login();
    $action = (string) ($_REQUEST['action'] ?? 'overview');
    $writes = ['change_password', 'update_recovery', 'revoke_session', 'revoke_device', 'close_all'];
    if (in_array($action, $writes, true)
        && ($_SERVER['REQUEST_METHOD'] !== 'POST' || !auth_verify_csrf($_POST['csrf'] ?? null))) {
        throw new RuntimeException('La sesión del formulario cambió. Recarga la página.');
    }
    $db = auth_db();
    $userId = (int) $user['id'];
    if ($action === 'overview') {
        $stmt = $db->prepare('SELECT s.id, s.created_at, s.ultima_actividad_at, s.expires_at, s.ip,
                d.id AS dispositivo_id, d.nombre, d.navegador, d.plataforma,
                CASE WHEN s.id = ? THEN 1 ELSE 0 END AS actual
            FROM usuario_sesiones s JOIN usuario_dispositivos d ON d.id = s.dispositivo_id
            WHERE s.usuario_id = ? AND s.revoked_at IS NULL AND s.expires_at > NOW()
            ORDER BY s.ultima_actividad_at DESC');
        $currentSession = (int) $_SESSION['auth_session_id'];
        $stmt->bind_param('ii', $currentSession, $userId);
        $stmt->execute();
        $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $devicesStmt = $db->prepare('SELECT d.id, d.nombre, d.navegador, d.plataforma, d.ultima_ip,
                d.confiable_at, d.ultima_actividad_at,
                CASE WHEN s.id = ? THEN 1 ELSE 0 END AS actual
            FROM usuario_dispositivos d
            LEFT JOIN usuario_sesiones s ON s.dispositivo_id = d.id AND s.id = ?
            WHERE d.usuario_id = ? AND d.revoked_at IS NULL ORDER BY d.ultima_actividad_at DESC');
        $devicesStmt->bind_param('iii', $currentSession, $currentSession, $userId);
        $devicesStmt->execute();
        $devices = $devicesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $devicesStmt->close();
        echo json_encode(['user' => $user, 'sessions' => $sessions, 'devices' => $devices, 'csrf' => auth_csrf()], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'change_password') {
        auth_change_password($userId, (string) ($_POST['password_nueva'] ?? ''), (string) ($_POST['password_actual'] ?? ''));
        $_SESSION['auth_user'] = auth_load_user($userId);
        auth_log('password_cambiada', 'correcto', $userId, $userId);
        echo json_encode(['ok' => true, 'message' => 'La contraseña se actualizó correctamente.'], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'update_recovery') {
        $password = (string) ($_POST['password_actual'] ?? '');
        $check = $db->prepare('SELECT password_hash, nombre_completo FROM usuarios WHERE id = ? LIMIT 1');
        $check->bind_param('i', $userId);
        $check->execute();
        $account = $check->get_result()->fetch_assoc();
        $check->close();
        if (!$account || !password_verify($password, (string) $account['password_hash'])) {
            throw new InvalidArgumentException('La contraseña actual no es correcta.');
        }
        $email = auth_email((string) ($_POST['correo_recuperacion'] ?? ''));
        $selector = auth_random_token(12);
        $validator = auth_random_token(32);
        $hash = hash('sha256', $validator);
        $expires = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');
        $expireOld = $db->prepare('UPDATE usuario_verificaciones_correo SET used_at = NOW() WHERE usuario_id = ? AND used_at IS NULL');
        $expireOld->bind_param('i', $userId);
        $expireOld->execute();
        $expireOld->close();
        $insert = $db->prepare('INSERT INTO usuario_verificaciones_correo
            (usuario_id, correo, selector, token_hash, expires_at) VALUES (?, ?, ?, ?, ?)');
        $insert->bind_param('issss', $userId, $email, $selector, $hash, $expires);
        $insert->execute();
        $insert->close();
        $url = auth_absolute_url('verificar_correo.php?token=' . urlencode($selector . '.' . $validator));
        if (!auth_send_mail($email, 'Verificar correo de recuperación de DigitApp 2024',
            "Hola {$account['nombre_completo']},\n\nConfirma tu correo de recuperación con este enlace:\n{$url}\n\nEl enlace vence en 30 minutos.")) {
            throw new RuntimeException('El correo saliente todavía no está configurado. El cambio no pudo verificarse.');
        }
        auth_log('verificacion_correo_solicitada', 'correcto', $userId, $userId);
        echo json_encode(['ok' => true, 'message' => 'Revisa el correo indicado para confirmar la dirección.'], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'revoke_session') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id || (int) $id === (int) $_SESSION['auth_session_id']) {
            throw new InvalidArgumentException('Selecciona otra sesión para cerrarla.');
        }
        $stmt = $db->prepare('UPDATE usuario_sesiones SET revoked_at = NOW() WHERE id = ? AND usuario_id = ? AND revoked_at IS NULL');
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $stmt->close();
        auth_log('sesion_remota_cerrada', 'correcto', $userId, $userId);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'revoke_device') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) {
            throw new InvalidArgumentException('Dispositivo no válido.');
        }
        $stmt = $db->prepare('UPDATE usuario_dispositivos SET revoked_at = NOW() WHERE id = ? AND usuario_id = ?');
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $stmt->close();
        $sessions = $db->prepare('UPDATE usuario_sesiones SET revoked_at = NOW() WHERE dispositivo_id = ? AND usuario_id = ?');
        $sessions->bind_param('ii', $id, $userId);
        $sessions->execute();
        $sessions->close();
        auth_log('dispositivo_revocado', 'correcto', $userId, $userId);
        echo json_encode(['ok' => true, 'reload' => true], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'close_all') {
        $stmt = $db->prepare('UPDATE usuario_sesiones SET revoked_at = NOW() WHERE usuario_id = ? AND revoked_at IS NULL');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        auth_log('sesiones_propias_cerradas', 'correcto', $userId, $userId);
        auth_forget_local_session();
        echo json_encode(['ok' => true, 'redirect' => '../login.php'], JSON_UNESCAPED_UNICODE);
    } else {
        throw new InvalidArgumentException('Acción no válida.');
    }
} catch (Throwable $error) {
    http_response_code($error instanceof InvalidArgumentException ? 422 : ($error instanceof RuntimeException ? 409 : 500));
    echo json_encode(['error' => $error instanceof InvalidArgumentException || $error instanceof RuntimeException
        ? $error->getMessage() : 'No fue posible completar la operación.'], JSON_UNESCAPED_UNICODE);
}
