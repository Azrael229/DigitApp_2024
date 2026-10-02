<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

function responderCatalogoEquipo(string $mensaje, int $estado): void
{
    http_response_code($estado);
    echo json_encode(['error' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mysqli_close($conexion);
    responderCatalogoEquipo('Método no permitido.', 405);
}

$token = (string) ($_POST['csrf'] ?? '');
if (empty($_SESSION['empresa_equipos_csrf']) || !hash_equals($_SESSION['empresa_equipos_csrf'], $token)) {
    mysqli_close($conexion);
    responderCatalogoEquipo('La sesión del formulario expiró. Recarga la página.', 403);
}

$tipo = trim((string) ($_POST['tipo'] ?? ''));
$nombre = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['nombre'] ?? '')));
$tablas = [
    'descripcion' => 'catalogo_descripciones_equipo',
    'marca' => 'catalogo_marcas_equipo',
];

if (!isset($tablas[$tipo])) {
    mysqli_close($conexion);
    responderCatalogoEquipo('El catálogo solicitado no es válido.', 422);
}
if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 100) {
    mysqli_close($conexion);
    responderCatalogoEquipo('Escribe un nombre de hasta 100 caracteres.', 422);
}

$tabla = $tablas[$tipo];
try {
    $guardar = $conexion->prepare(
        "INSERT INTO {$tabla} (nombre, activo) VALUES (?, 1)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), nombre = VALUES(nombre), activo = 1"
    );
    $guardar->bind_param('s', $nombre);
    $guardar->execute();
    $id = $conexion->insert_id;
    $guardar->close();

    $consulta = $conexion->prepare("SELECT id, nombre FROM {$tabla} WHERE id = ? LIMIT 1");
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $elemento = $consulta->get_result()->fetch_assoc();
    $consulta->close();
} catch (Throwable $error) {
    mysqli_close($conexion);
    responderCatalogoEquipo('No fue posible guardar el nuevo elemento.', 409);
}

mysqli_close($conexion);
echo json_encode(['elemento' => $elemento], JSON_UNESCAPED_UNICODE);
?>
