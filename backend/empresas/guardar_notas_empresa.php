<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('clientes');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$token = (string) ($_POST['csrf'] ?? '');
if (empty($_SESSION['empresa_notas_csrf']) || !hash_equals($_SESSION['empresa_notas_csrf'], $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'La sesión cambió. Recarga la página antes de guardar.']);
    exit;
}

$empresaId = filter_var($_POST['empresa_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$notas = (string) ($_POST['notas'] ?? '');

if ($empresaId === false) {
    http_response_code(422);
    echo json_encode(['error' => 'La empresa indicada no es válida.']);
    exit;
}

if (mb_strlen($notas, 'UTF-8') > 15000 || strlen($notas) > 60000) {
    http_response_code(422);
    echo json_encode(['error' => 'Las notas no pueden superar 15,000 caracteres.']);
    exit;
}

require __DIR__ . '/../../config/conexion.php';
$conexion->set_charset('utf8mb4');

$consulta = $conexion->prepare(
    'UPDATE empresas
     SET observaciones = ?, updated_at = CURRENT_TIMESTAMP
     WHERE id_e = ?'
);
$consulta->bind_param('si', $notas, $empresaId);
$consulta->execute();
$consulta->close();

$consultaEmpresa = $conexion->prepare('SELECT observaciones, updated_at FROM empresas WHERE id_e = ? LIMIT 1');
$consultaEmpresa->bind_param('i', $empresaId);
$consultaEmpresa->execute();
$empresa = $consultaEmpresa->get_result()->fetch_assoc();
$consultaEmpresa->close();
$conexion->close();

if ($empresa === null) {
    http_response_code(404);
    echo json_encode(['error' => 'La empresa no fue encontrada.']);
    exit;
}

echo json_encode([
    'notas' => (string) ($empresa['observaciones'] ?? ''),
    'updated_at' => $empresa['updated_at'],
], JSON_UNESCAPED_UNICODE);
