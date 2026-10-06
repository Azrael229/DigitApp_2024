<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function auth_email(string $value): string
{
    $email = mb_strtolower(trim($value));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        throw new InvalidArgumentException('Escribe un correo válido.');
    }
    return $email;
}

function auth_login_email(string $value): string
{
    return auth_email($value);
}

function auth_validate_password(string $password, array $user = []): void
{
    $length = mb_strlen($password, 'UTF-8');
    if ($length < AUTH_PASSWORD_MIN || $length > AUTH_PASSWORD_MAX || trim($password) === '') {
        throw new InvalidArgumentException('La contraseña debe tener entre 5 y 128 caracteres.');
    }
    $normalized = mb_strtolower(trim($password));
    $common = ['12345', '123456', 'password', 'contraseña', 'qwerty', 'admin', 'digitapp'];
    if (in_array($normalized, $common, true)) {
        throw new InvalidArgumentException('Selecciona una contraseña menos común.');
    }
    foreach (['nombre_completo', 'correo'] as $field) {
        $source = mb_strtolower(trim((string) ($user[$field] ?? '')));
        if ($source !== '' && (str_contains($normalized, $source)
            || ($field === 'correo' && str_contains($normalized, strstr($source, '@', true) ?: $source)))) {
            throw new InvalidArgumentException('La contraseña no debe contener tu nombre o correo de acceso.');
        }
    }
}

function auth_describe_agent(?string $deviceName = null): array
{
    $agent = auth_user_agent();
    $browser = 'Navegador';
    foreach (['Edg/' => 'Microsoft Edge', 'OPR/' => 'Opera', 'Chrome/' => 'Google Chrome', 'Firefox/' => 'Mozilla Firefox', 'Safari/' => 'Safari'] as $needle => $label) {
        if (str_contains($agent, $needle)) {
            $browser = $label;
            break;
        }
    }
    $platform = str_contains($agent, 'Android') ? 'Android'
        : (str_contains($agent, 'Windows') ? 'Windows'
        : (str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') ? 'iOS/iPadOS'
        : (str_contains($agent, 'Macintosh') ? 'macOS' : 'Dispositivo')));
    $name = trim((string) $deviceName);
    if ($name === '') {
        $name = $browser . ' · ' . $platform;
    }
    return [mb_substr($name, 0, 120), $browser, $platform];
}

function auth_find_or_create_device(int $userId, ?string $deviceName): int
{
    $db = auth_db();
    $parts = auth_token_parts($_COOKIE[AUTH_DEVICE_COOKIE] ?? null);
    if ($parts) {
        [$selector, $validator] = $parts;
        $stmt = $db->prepare('SELECT id, token_hash FROM usuario_dispositivos
            WHERE usuario_id = ? AND selector = ? AND revoked_at IS NULL LIMIT 1');
        $stmt->bind_param('is', $userId, $selector);
        $stmt->execute();
        $device = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($device && hash_equals((string) $device['token_hash'], hash('sha256', $validator))) {
            [$name, $browser, $platform] = auth_describe_agent($deviceName);
            $ip = auth_client_ip();
            $update = $db->prepare('UPDATE usuario_dispositivos SET nombre = ?, navegador = ?, plataforma = ?,
                ultima_ip = ?, ultima_actividad_at = NOW() WHERE id = ?');
            $deviceId = (int) $device['id'];
            $update->bind_param('ssssi', $name, $browser, $platform, $ip, $deviceId);
            $update->execute();
            $update->close();
            return (int) $device['id'];
        }
    }
    [$name, $browser, $platform] = auth_describe_agent($deviceName);
    $selector = auth_random_token(12);
    $validator = auth_random_token(32);
    $hash = hash('sha256', $validator);
    $ip = auth_client_ip();
    $stmt = $db->prepare('INSERT INTO usuario_dispositivos
        (usuario_id, selector, token_hash, nombre, navegador, plataforma, primera_ip, ultima_ip)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssssss', $userId, $selector, $hash, $name, $browser, $platform, $ip, $ip);
    $stmt->execute();
    $id = (int) $db->insert_id;
    $stmt->close();
    auth_set_cookie(AUTH_DEVICE_COOKIE, $selector . '.' . $validator, time() + 10 * 365 * 86400);
    auth_log('dispositivo_confiable_agregado', 'correcto', $userId, $userId, $name);
    return $id;
}

function auth_create_login_session(array $user, ?string $deviceName): void
{
    $db = auth_db();
    $userId = (int) $user['id'];
    $deviceId = auth_find_or_create_device($userId, $deviceName);
    $selector = auth_random_token(12);
    $validator = auth_random_token(32);
    $hash = hash('sha256', $validator);
    $phpHash = hash('sha256', session_id());
    $ip = auth_client_ip();
    $agent = auth_user_agent();
    $expires = (new DateTimeImmutable('+' . AUTH_SESSION_DAYS . ' days'))->format('Y-m-d H:i:s');
    $stmt = $db->prepare('INSERT INTO usuario_sesiones
        (usuario_id, dispositivo_id, selector, token_hash, php_session_hash, ip, user_agent, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iissssss', $userId, $deviceId, $selector, $hash, $phpHash, $ip, $agent, $expires);
    $stmt->execute();
    $sessionId = (int) $db->insert_id;
    $stmt->close();
    auth_store_user($user, $sessionId);
    auth_set_cookie(AUTH_COOKIE, $selector . '.' . $validator, time() + AUTH_SESSION_DAYS * 86400);
}

function auth_attempt_login(string $emailInput, string $password, ?string $deviceName): array
{
    $email = mb_strtolower(trim($emailInput));
    $db = auth_db();
    $stmt = $db->prepare('SELECT * FROM usuarios WHERE correo = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $generic = 'Correo o contraseña incorrectos.';
    if (!$user || $user['estado'] !== 'activo') {
        auth_log('inicio_sesion', 'fallido', $user ? (int) $user['id'] : null, null, 'Credenciales no válidas.');
        throw new RuntimeException($generic);
    }
    if ($user['bloqueado_hasta'] !== null && strtotime((string) $user['bloqueado_hasta']) > time()) {
        auth_log('inicio_sesion_bloqueado', 'fallido', (int) $user['id'], null, 'Cuenta temporalmente bloqueada.');
        throw new RuntimeException('La cuenta está temporalmente bloqueada. Intenta más tarde.');
    }
    if (!password_verify($password, (string) $user['password_hash'])) {
        $userId = (int) $user['id'];
        $attempts = (int) $user['intentos_fallidos'] + 1;
        $level = (int) $user['nivel_bloqueo'];
        $lockUntil = null;
        if ($attempts >= 5) {
            $minutes = $level >= 1 ? 30 : 15;
            $level++;
            $attempts = 0;
            $lockUntil = (new DateTimeImmutable('+' . $minutes . ' minutes'))->format('Y-m-d H:i:s');
        }
        $update = $db->prepare('UPDATE usuarios SET intentos_fallidos = ?, nivel_bloqueo = ?, bloqueado_hasta = ? WHERE id = ?');
        $update->bind_param('iisi', $attempts, $level, $lockUntil, $userId);
        $update->execute();
        $update->close();
        auth_log('inicio_sesion', 'fallido', (int) $user['id'], null, $lockUntil ? 'Bloqueo temporal aplicado.' : 'Credenciales no válidas.');
        throw new RuntimeException($lockUntil ? 'La cuenta quedó temporalmente bloqueada.' : $generic);
    }
    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $userId = (int) $user['id'];
        $rehash = $db->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
        $rehash->bind_param('si', $newHash, $userId);
        $rehash->execute();
        $rehash->close();
    }
    session_regenerate_id(true);
    $update = $db->prepare('UPDATE usuarios SET intentos_fallidos = 0, nivel_bloqueo = 0,
        bloqueado_hasta = NULL, ultimo_acceso_at = NOW() WHERE id = ?');
    $userId = (int) $user['id'];
    $update->bind_param('i', $userId);
    $update->execute();
    $update->close();
    $safeUser = auth_load_user((int) $user['id']);
    auth_create_login_session($safeUser, $deviceName);
    auth_log('inicio_sesion', 'correcto', (int) $user['id'], (int) $user['id']);
    $db->query('DELETE FROM seguridad_bitacora WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)');
    return $safeUser;
}

function auth_password_was_used(int $userId, string $password, string $currentHash): bool
{
    if (password_verify($password, $currentHash)) {
        return true;
    }
    $stmt = auth_db()->prepare('SELECT password_hash FROM usuario_password_historial
        WHERE usuario_id = ? ORDER BY created_at DESC, id DESC LIMIT 5');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($rows as $row) {
        if (password_verify($password, (string) $row['password_hash'])) {
            return true;
        }
    }
    return false;
}

function auth_change_password(int $userId, string $newPassword, ?string $currentPassword = null, bool $bypassCurrent = false, bool $mustChangeAfter = false): void
{
    $db = auth_db();
    $stmt = $db->prepare('SELECT * FROM usuarios WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) {
        throw new OutOfBoundsException('Usuario no encontrado.');
    }
    if (!$bypassCurrent && !password_verify((string) $currentPassword, (string) $user['password_hash'])) {
        throw new InvalidArgumentException('La contraseña actual no es correcta.');
    }
    auth_validate_password($newPassword, $user);
    if (auth_password_was_used($userId, $newPassword, (string) $user['password_hash'])) {
        throw new InvalidArgumentException('No puedes reutilizar ninguna de tus últimas cinco contraseñas.');
    }
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $db->begin_transaction();
    try {
        $history = $db->prepare('INSERT INTO usuario_password_historial (usuario_id, password_hash) VALUES (?, ?)');
        $currentHash = (string) $user['password_hash'];
        $history->bind_param('is', $userId, $currentHash);
        $history->execute();
        $history->close();
        $mustChange = $mustChangeAfter ? 1 : 0;
        $update = $db->prepare('UPDATE usuarios SET password_hash = ?, debe_cambiar_password = ?,
            password_changed_at = NOW(), updated_at = NOW(), version = version + 1 WHERE id = ?');
        $update->bind_param('sii', $newHash, $mustChange, $userId);
        $update->execute();
        $update->close();
        $sessionId = (int) ($_SESSION['auth_session_id'] ?? 0);
        $revoke = $db->prepare('UPDATE usuario_sesiones SET revoked_at = NOW()
            WHERE usuario_id = ? AND id <> ? AND revoked_at IS NULL');
        $revoke->bind_param('ii', $userId, $sessionId);
        $revoke->execute();
        $revoke->close();
        $trim = $db->prepare('DELETE FROM usuario_password_historial WHERE usuario_id = ? AND id NOT IN
            (SELECT id FROM (SELECT id FROM usuario_password_historial WHERE usuario_id = ? ORDER BY created_at DESC, id DESC LIMIT 5) recientes)');
        $trim->bind_param('ii', $userId, $userId);
        $trim->execute();
        $trim->close();
        $db->commit();
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
}
