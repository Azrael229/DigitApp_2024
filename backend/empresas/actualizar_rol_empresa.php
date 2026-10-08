<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('clientes');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $csrf = (string) ($_SESSION['empresas_tabla_csrf'] ?? '');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $csrf === ''
        || !hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        throw new RuntimeException('La sesión cambió. Recarga la página.');
    }
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $version = filter_var($_POST['version'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $rol = trim((string) ($_POST['rol'] ?? ''));
    if (!$id || !$version || !in_array($rol, ['Cliente', 'Proveedor', 'Prospecto', 'Cliente-Proveedor', 'Otro'], true)) {
        throw new InvalidArgumentException('Selecciona un rol válido.');
    }
    require __DIR__ . '/../../config/conexion.php';
    $stmt = $conexion->prepare('UPDATE empresas SET rol = ?, version = version + 1, updated_at = CURRENT_TIMESTAMP WHERE id_e = ? AND version = ?');
    $stmt->bind_param('sii', $rol, $id, $version);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException('La empresa fue modificada por otro usuario. Recarga la página.');
    }
    echo json_encode(['rol' => $rol, 'version' => $version + 1], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    http_response_code($error instanceof InvalidArgumentException ? 422 : 409);
    echo json_encode(['error' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($stmt)) { $stmt->close(); }
    if (isset($conexion)) { $conexion->close(); }
}
