<?php
declare(strict_types=1);

require_once __DIR__ . '/service.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $actor = auth_require_permission('usuarios');
    $action = (string) ($_REQUEST['action'] ?? 'list');
    $writes = ['save', 'reset_password', 'unlock', 'revoke_session', 'revoke_device', 'revoke_all'];
    if (in_array($action, $writes, true)
        && ($_SERVER['REQUEST_METHOD'] !== 'POST' || !auth_verify_csrf($_POST['csrf'] ?? null))) {
        throw new RuntimeException('La sesión del formulario cambió. Recarga la página.');
    }
    $db = auth_db();
    $actorId = (int) $actor['id'];
    if ($action === 'list') {
        $rows = $db->query('SELECT id, nombre_completo, correo, correo_recuperacion,
            correo_recuperacion_verificado_at, rol, estado, intentos_fallidos, bloqueado_hasta,
            ultimo_acceso_at, created_at, updated_at, version FROM usuarios ORDER BY nombre_completo')->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['data' => $rows, 'csrf' => auth_csrf()], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'sessions') {
        $userId = filter_var($_GET['usuario_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$userId) {
            throw new InvalidArgumentException('Usuario no válido.');
        }
        $stmt = $db->prepare('SELECT s.id, s.created_at, s.ultima_actividad_at, s.expires_at, s.ip,
            d.nombre, d.navegador, d.plataforma FROM usuario_sesiones s
            JOIN usuario_dispositivos d ON d.id = s.dispositivo_id
            WHERE s.usuario_id = ? AND s.revoked_at IS NULL AND s.expires_at > NOW()
            ORDER BY s.ultima_actividad_at DESC');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $devices = $db->prepare('SELECT id, nombre, navegador, plataforma, ultima_ip, confiable_at, ultima_actividad_at
            FROM usuario_dispositivos WHERE usuario_id = ? AND revoked_at IS NULL ORDER BY ultima_actividad_at DESC');
        $devices->bind_param('i', $userId);
        $devices->execute();
        $deviceRows = $devices->get_result()->fetch_all(MYSQLI_ASSOC);
        $devices->close();
        echo json_encode(['sessions' => $rows, 'devices' => $deviceRows], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'audit') {
        $rows = $db->query('SELECT b.id, b.evento, b.resultado, b.ip, b.user_agent, b.detalle, b.created_at,
                u.nombre_completo AS usuario, a.nombre_completo AS actor
            FROM seguridad_bitacora b
            LEFT JOIN usuarios u ON u.id = b.usuario_id
            LEFT JOIN usuarios a ON a.id = b.actor_usuario_id
            WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            ORDER BY b.created_at DESC, b.id DESC LIMIT 1000')->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['data' => $rows], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'save') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        $name = trim((string) ($_POST['nombre_completo'] ?? ''));
        if ($name === '' || mb_strlen($name) > 150) {
            throw new InvalidArgumentException('Escribe el nombre completo.');
        }
        $email = auth_login_email((string) ($_POST['correo'] ?? ''));
        $recovery = trim((string) ($_POST['correo_recuperacion'] ?? ''));
        $recovery = $recovery === '' ? null : auth_email($recovery);
        $role = (string) ($_POST['rol'] ?? '');
        $status = (string) ($_POST['estado'] ?? 'activo');
        if (!isset(AUTH_ROLE_LABELS[$role]) || !in_array($status, ['activo', 'inactivo'], true)) {
            throw new InvalidArgumentException('Rol o estado no válido.');
        }
        if ($id === $actorId && ($role !== 'administrador' || $status !== 'activo')) {
            throw new InvalidArgumentException('El administrador principal no puede desactivar ni retirar su propio rol.');
        }
        if ($role === 'administrador') {
            $check = $db->prepare('SELECT id FROM usuarios WHERE rol = \'administrador\' AND id <> ? LIMIT 1');
            $except = $id ?? 0;
            $check->bind_param('i', $except);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();
            if ($exists) {
                throw new RuntimeException('DigitApp 2024 solo permite un administrador.');
            }
        }
        if ($id) {
            $version = filter_var($_POST['version'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $stmt = $db->prepare('UPDATE usuarios SET nombre_completo = ?, correo = ?, correo_recuperacion = ?,
                correo_recuperacion_verificado_at = IF(correo_recuperacion <=> ?, correo_recuperacion_verificado_at, NULL),
                rol = ?, estado = ?, updated_by = ?, version = version + 1
                WHERE id = ? AND version = ?');
            $stmt->bind_param('ssssssiii', $name, $email, $recovery, $recovery, $role, $status, $actorId, $id, $version);
            $stmt->execute();
            if ($stmt->affected_rows !== 1) {
                $stmt->close();
                throw new RuntimeException('Otra edición modificó este usuario. Recarga la información.');
            }
            $stmt->close();
            if ($status === 'inactivo') {
                $revoke = $db->prepare('UPDATE usuario_sesiones SET revoked_at = NOW() WHERE usuario_id = ? AND revoked_at IS NULL');
                $revoke->bind_param('i', $id);
                $revoke->execute();
                $revoke->close();
            }
            auth_log('usuario_actualizado', 'correcto', $id, $actorId);
        } else {
            $password = (string) ($_POST['password_temporal'] ?? '');
            auth_validate_password($password, ['nombre_completo' => $name, 'correo' => $email]);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO usuarios
                (nombre_completo, correo, correo_recuperacion, rol, estado, password_hash,
                 debe_cambiar_password, created_by, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)');
            $stmt->bind_param('ssssssii', $name, $email, $recovery, $role, $status, $hash, $actorId, $actorId);
            $stmt->execute();
            $id = (int) $db->insert_id;
            $stmt->close();
            auth_log('usuario_creado', 'correcto', $id, $actorId);
        }
        echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'reset_password') {
        $userId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$userId) {
            throw new InvalidArgumentException('Usuario no válido.');
        }
        auth_change_password((int) $userId, (string) ($_POST['password_temporal'] ?? ''), null, true, true);
        auth_log('password_restaurada', 'correcto', (int) $userId, $actorId);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'unlock') {
        $userId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $stmt = $db->prepare('UPDATE usuarios SET intentos_fallidos = 0, nivel_bloqueo = 0, bloqueado_hasta = NULL WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        auth_log('usuario_desbloqueado', 'correcto', (int) $userId, $actorId);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'revoke_session') {
        $sessionId = filter_var($_POST['sesion_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $stmt = $db->prepare('UPDATE usuario_sesiones SET revoked_at = NOW() WHERE id = ?');
        $stmt->bind_param('i', $sessionId);
        $stmt->execute();
        $stmt->close();
        auth_log('sesion_cerrada_por_administrador', 'correcto', null, $actorId);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'revoke_device') {
        $deviceId = filter_var($_POST['dispositivo_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$deviceId) {
            throw new InvalidArgumentException('Dispositivo no válido.');
        }
        $stmt = $db->prepare('UPDATE usuario_dispositivos SET revoked_at = NOW() WHERE id = ?');
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();
        $stmt->close();
        $sessions = $db->prepare('UPDATE usuario_sesiones SET revoked_at = NOW() WHERE dispositivo_id = ? AND revoked_at IS NULL');
        $sessions->bind_param('i', $deviceId);
        $sessions->execute();
        $sessions->close();
        auth_log('dispositivo_revocado_por_administrador', 'correcto', null, $actorId);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'revoke_all') {
        $stmt = $db->prepare('UPDATE usuario_sesiones SET revoked_at = NOW() WHERE revoked_at IS NULL');
        $stmt->execute();
        $stmt->close();
        auth_log('todas_las_sesiones_cerradas', 'correcto', null, $actorId);
        echo json_encode(['ok' => true, 'logout' => true], JSON_UNESCAPED_UNICODE);
    } else {
        throw new InvalidArgumentException('Acción no válida.');
    }
} catch (Throwable $error) {
    if ($error instanceof mysqli_sql_exception && $error->getCode() === 1062) {
        $error = new InvalidArgumentException('El correo ya pertenece a otro usuario.');
    }
    http_response_code($error instanceof InvalidArgumentException ? 422 : ($error instanceof RuntimeException ? 409 : 500));
    echo json_encode(['error' => $error instanceof InvalidArgumentException || $error instanceof RuntimeException
        ? $error->getMessage() : 'No fue posible completar la operación.'], JSON_UNESCAPED_UNICODE);
}
