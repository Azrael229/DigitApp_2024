<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
date_default_timezone_set('America/Mexico_City');

function auth_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function auth_cookie_options(int $expires): array
{
    return ['expires' => $expires, 'path' => '/', 'secure' => auth_is_https(), 'httponly' => true, 'samesite' => 'Lax'];
}

function auth_session_cookie_options(): array
{
    return ['lifetime' => AUTH_SESSION_DAYS * 86400, 'path' => '/', 'secure' => auth_is_https(), 'httponly' => true, 'samesite' => 'Lax'];
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_name('DIGITAPPSESSID');
    session_set_cookie_params(auth_session_cookie_options());
    session_start();
}

function auth_db(): mysqli
{
    static $db = null;
    if ($db instanceof mysqli) {
        return $db;
    }
    require __DIR__ . '/../../config/conexion.php';
    if (!isset($conexion) || !$conexion instanceof mysqli) {
        throw new RuntimeException('No fue posible abrir la base de datos de autenticación.');
    }
    $conexion->set_charset('utf8mb4');
    $db = $conexion;
    return $db;
}

function auth_client_ip(): string
{
    return mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function auth_user_agent(): string
{
    return mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
}

function auth_is_json_request(): bool
{
    return str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')
        || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

function auth_random_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

function auth_token_parts(?string $cookie): ?array
{
    if (!$cookie || !preg_match('/^([a-f0-9]{24})\.([a-f0-9]{64})$/D', $cookie, $match)) {
        return null;
    }
    return [$match[1], $match[2]];
}

function auth_set_cookie(string $name, string $value, int $expires): void
{
    setcookie($name, $value, auth_cookie_options($expires));
    $_COOKIE[$name] = $value;
}

function auth_clear_cookie(string $name): void
{
    setcookie($name, '', auth_cookie_options(time() - 3600));
    unset($_COOKIE[$name]);
}

function auth_log(string $event, string $result = 'informativo', ?int $userId = null, ?int $actorId = null, ?string $detail = null): void
{
    try {
        $detail = $detail === null ? null : mb_substr($detail, 0, 500);
        $ip = auth_client_ip();
        $agent = auth_user_agent();
        $stmt = auth_db()->prepare('INSERT INTO seguridad_bitacora
            (usuario_id, actor_usuario_id, evento, resultado, ip, user_agent, detalle)
            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iisssss', $userId, $actorId, $event, $result, $ip, $agent, $detail);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $error) {
        error_log('DigitApp autenticación: no fue posible registrar la bitácora.');
    }
}

function auth_load_user(int $userId): ?array
{
    $stmt = auth_db()->prepare('SELECT id, nombre_completo, correo, correo_recuperacion,
        correo_recuperacion_verificado_at, rol, estado, debe_cambiar_password, version
        FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $user;
}

function auth_store_user(array $user, int $sessionId): void
{
    $_SESSION['auth_user_id'] = (int) $user['id'];
    $_SESSION['auth_session_id'] = $sessionId;
    $_SESSION['auth_user'] = $user;
    $_SESSION['auth_checked_at'] = time();
    $_SESSION['auth_csrf'] ??= auth_random_token();
}

function auth_restore_from_cookie(): ?array
{
    $parts = auth_token_parts($_COOKIE[AUTH_COOKIE] ?? null);
    if (!$parts) {
        return null;
    }
    [$selector, $validator] = $parts;
    $stmt = auth_db()->prepare('SELECT s.id AS session_id, s.token_hash, s.usuario_id,
            d.revoked_at AS device_revoked, u.estado
        FROM usuario_sesiones s
        JOIN usuario_dispositivos d ON d.id = s.dispositivo_id
        JOIN usuarios u ON u.id = s.usuario_id
        WHERE s.selector = ? AND s.revoked_at IS NULL AND s.expires_at > NOW() LIMIT 1');
    $stmt->bind_param('s', $selector);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || $row['estado'] !== 'activo' || $row['device_revoked'] !== null
        || !hash_equals((string) $row['token_hash'], hash('sha256', $validator))) {
        auth_clear_cookie(AUTH_COOKIE);
        return null;
    }
    $user = auth_load_user((int) $row['usuario_id']);
    if (!$user) {
        auth_clear_cookie(AUTH_COOKIE);
        return null;
    }
    session_regenerate_id(true);
    auth_store_user($user, (int) $row['session_id']);
    return $user;
}

function auth_forget_local_session(): void
{
    auth_clear_cookie(AUTH_COOKIE);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000, 'path' => $params['path'], 'domain' => $params['domain'],
            'secure' => $params['secure'], 'httponly' => $params['httponly'], 'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function auth_current_user(): ?array
{
    $userId = (int) ($_SESSION['auth_user_id'] ?? 0);
    $sessionId = (int) ($_SESSION['auth_session_id'] ?? 0);
    if ($userId < 1 || $sessionId < 1) {
        return auth_restore_from_cookie();
    }
    if ((int) ($_SESSION['auth_checked_at'] ?? 0) + 300 <= time()) {
        $stmt = auth_db()->prepare('SELECT s.id FROM usuario_sesiones s
            JOIN usuario_dispositivos d ON d.id = s.dispositivo_id
            JOIN usuarios u ON u.id = s.usuario_id
            WHERE s.id = ? AND s.usuario_id = ? AND s.revoked_at IS NULL
              AND s.expires_at > NOW() AND d.revoked_at IS NULL AND u.estado = \'activo\' LIMIT 1');
        $stmt->bind_param('ii', $sessionId, $userId);
        $stmt->execute();
        $valid = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$valid) {
            auth_forget_local_session();
            return null;
        }
        $user = auth_load_user($userId);
        if (!$user) {
            auth_forget_local_session();
            return null;
        }
        auth_store_user($user, $sessionId);
        $ip = auth_client_ip();
        $update = auth_db()->prepare('UPDATE usuario_sesiones s
            JOIN usuario_dispositivos d ON d.id = s.dispositivo_id
            SET s.ultima_actividad_at = NOW(), s.ip = ?, d.ultima_actividad_at = NOW(), d.ultima_ip = ?
            WHERE s.id = ?');
        $update->bind_param('ssi', $ip, $ip, $sessionId);
        $update->execute();
        $update->close();
    }
    return $_SESSION['auth_user'] ?? null;
}

function auth_has_permission(string $permission, ?array $user = null): bool
{
    $user ??= auth_current_user();
    if (!$user || !isset(AUTH_ROLE_PERMISSIONS[$user['rol']])) {
        return false;
    }
    $permissions = AUTH_ROLE_PERMISSIONS[$user['rol']];
    return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
}

function auth_login_url(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_contains($script, '/backend/')) {
        return '../../login.php';
    }
    return str_contains($script, '/paginas/') || str_contains($script, '/fpdf/') ? '../login.php' : 'login.php';
}

function auth_deny(int $status, string $message): never
{
    http_response_code($status);
    if (auth_is_json_request()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . auth_login_url());
    }
    exit;
}

function auth_require_login(): array
{
    $user = auth_current_user();
    if (!$user) {
        auth_deny(401, 'Inicia sesión para continuar.');
    }
    return $user;
}

function auth_require_permission(string $permission): array
{
    $user = auth_require_login();
    if (!empty($user['debe_cambiar_password'])) {
        auth_deny(403, 'Debes establecer una contraseña personal antes de continuar.');
    }
    if (!auth_has_permission($permission, $user)) {
        auth_deny(403, 'No tienes permiso para realizar esta acción.');
    }
    return $user;
}

function auth_csrf(): string
{
    $_SESSION['auth_csrf'] ??= auth_random_token();
    return (string) $_SESSION['auth_csrf'];
}

function auth_verify_csrf(?string $token): bool
{
    return is_string($token) && hash_equals(auth_csrf(), $token);
}

function auth_logout(bool $allUserSessions = false): void
{
    $userId = (int) ($_SESSION['auth_user_id'] ?? 0);
    $sessionId = (int) ($_SESSION['auth_session_id'] ?? 0);
    if ($userId > 0) {
        if ($allUserSessions) {
            $stmt = auth_db()->prepare('UPDATE usuario_sesiones SET revoked_at = NOW() WHERE usuario_id = ? AND revoked_at IS NULL');
            $stmt->bind_param('i', $userId);
        } else {
            $stmt = auth_db()->prepare('UPDATE usuario_sesiones SET revoked_at = NOW() WHERE id = ? AND usuario_id = ?');
            $stmt->bind_param('ii', $sessionId, $userId);
        }
        $stmt->execute();
        $stmt->close();
        auth_log($allUserSessions ? 'sesiones_propias_cerradas' : 'sesion_cerrada', 'correcto', $userId, $userId);
    }
    auth_forget_local_session();
}
