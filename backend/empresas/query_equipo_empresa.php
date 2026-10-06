<?php
require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('productos');
require __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$empresaId = filter_input(INPUT_GET, 'empresa_id', FILTER_VALIDATE_INT);
$equipoId = filter_input(INPUT_GET, 'equipo_id', FILTER_VALIDATE_INT);

if ($empresaId === false || $empresaId === null || $empresaId < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'La empresa no es válida.'], JSON_UNESCAPED_UNICODE);
    mysqli_close($conexion);
    exit;
}

$consultaEmpresa = $conexion->prepare('SELECT id_e, empresa FROM empresas WHERE id_e = ? LIMIT 1');
$consultaEmpresa->bind_param('i', $empresaId);
$consultaEmpresa->execute();
$empresa = $consultaEmpresa->get_result()->fetch_assoc();
$consultaEmpresa->close();

if ($empresa === null) {
    http_response_code(404);
    echo json_encode(['error' => 'La empresa no existe.'], JSON_UNESCAPED_UNICODE);
    mysqli_close($conexion);
    exit;
}

$consultaDirecciones = $conexion->prepare(
    'SELECT id, alias, tipo_direccion, calle, numero_exterior, colonia, ciudad, estado
     FROM empresa_direcciones
     WHERE empresa_id = ?
     ORDER BY es_principal DESC, alias ASC, id ASC'
);
$consultaDirecciones->bind_param('i', $empresaId);
$consultaDirecciones->execute();
$direcciones = $consultaDirecciones->get_result()->fetch_all(MYSQLI_ASSOC);
$consultaDirecciones->close();

$descripciones = $conexion->query(
    'SELECT id, nombre FROM catalogo_descripciones_equipo WHERE activo = 1 ORDER BY nombre ASC'
)->fetch_all(MYSQLI_ASSOC);
$marcas = $conexion->query(
    'SELECT id, nombre FROM catalogo_marcas_equipo WHERE activo = 1 ORDER BY nombre ASC'
)->fetch_all(MYSQLI_ASSOC);

$equipo = null;
if ($equipoId !== false && $equipoId !== null && $equipoId > 0) {
    $consultaEquipo = $conexion->prepare(
        'SELECT id, empresa_id, direccion_id, ubicacion, descripcion_id, marca_id, modelo,
                identificacion, numero_serie, unidad, capacidad_maxima,
                division_real, division_verificacion, clase_exactitud, estatus, version
         FROM empresa_equipos
         WHERE id = ? AND empresa_id = ?
         LIMIT 1'
    );
    $consultaEquipo->bind_param('ii', $equipoId, $empresaId);
    $consultaEquipo->execute();
    $equipo = $consultaEquipo->get_result()->fetch_assoc();
    $consultaEquipo->close();

    if ($equipo === null) {
        http_response_code(404);
        echo json_encode(['error' => 'El equipo no existe en esta empresa.'], JSON_UNESCAPED_UNICODE);
        mysqli_close($conexion);
        exit;
    }
}

echo json_encode([
    'empresa' => $empresa,
    'direcciones' => $direcciones,
    'descripciones' => $descripciones,
    'marcas' => $marcas,
    'equipo' => $equipo,
], JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>
