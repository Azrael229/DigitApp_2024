<?php
$id = filter_var(trim(file_get_contents('php://input')), FILTER_VALIDATE_INT);

require (__DIR__ . "/../../config/conexion.php");

header('Content-Type: application/json; charset=utf-8');

if ($id === false || $id < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'Empresa no valida']);
    mysqli_close($conexion);
    exit;
}

$consultaEmpresa = $conexion->prepare(
    'SELECT id_e, empresa, razon_social, rfc, rol, actividad_economica,
            regimen_fiscal_codigo, regimen_fiscal_descripcion, regimen_capital,
            tipo_persona, giro_mercantil, mercado, telefono_principal,
            email_principal, pagina_web, estatus, origen_registro, observaciones,
            created_at, updated_at
     FROM empresas
     WHERE id_e = ?
     LIMIT 1'
);
$consultaEmpresa->bind_param('i', $id);
$consultaEmpresa->execute();
$empresa = $consultaEmpresa->get_result()->fetch_assoc();
$consultaEmpresa->close();

if ($empresa === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Empresa no encontrada']);
    mysqli_close($conexion);
    exit;
}

$consultaDirecciones = $conexion->prepare(
    'SELECT id, tipo_direccion, alias, es_principal, calle, numero_exterior,
            numero_interior, colonia, localidad, municipio, ciudad, estado,
            codigo_postal, pais, entre_calles, referencia, enlace_maps
     FROM empresa_direcciones
     WHERE empresa_id = ?
     ORDER BY tipo_direccion, es_principal DESC, id ASC'
);
$consultaDirecciones->bind_param('i', $id);
$consultaDirecciones->execute();
$direcciones = $consultaDirecciones->get_result()->fetch_all(MYSQLI_ASSOC);
$consultaDirecciones->close();

$consultaContactos = $conexion->prepare(
    'SELECT id, nombre, celular, correo, depto
     FROM contactos
     WHERE id_empresa = ?
     ORDER BY nombre ASC'
);
$consultaContactos->bind_param('i', $id);
$consultaContactos->execute();
$contactos = $consultaContactos->get_result()->fetch_all(MYSQLI_ASSOC);
$consultaContactos->close();

echo json_encode([
    'empresa' => $empresa,
    'direcciones' => $direcciones,
    'contactos' => $contactos,
], JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>
