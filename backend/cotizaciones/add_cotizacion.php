<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$numero = trim((string) ($_POST['numero_coti'] ?? ''));
$fecha = trim((string) ($_POST['coti_fecha'] ?? ''));
$vigencia = trim((string) ($_POST['coti_vigencia'] ?? ''));
$contactoNombre = trim((string) ($_POST['nombre_contacto'] ?? ''));
$empresaNombre = trim((string) ($_POST['nombre_empresa'] ?? ''));
$total = trim((string) ($_POST['total'] ?? ''));
$utilidad = trim((string) ($_POST['total_utilidad'] ?? ''));
$costos = trim((string) ($_POST['costos'] ?? ''));
$contactoEntrada = trim((string) ($_POST['select_contacto'] ?? ''));

if ($numero === '' || strpos($numero, '/') !== false || strpos($numero, '\\') !== false) {
    http_response_code(422);
    echo json_encode(['error' => 'El número de cotización no es válido.']);
    exit;
}

$contactoId = null;
if ($contactoEntrada !== '') {
    $contactoValidado = filter_var($contactoEntrada, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    if ($contactoValidado === false) {
        http_response_code(422);
        echo json_encode(['error' => 'El contacto seleccionado no es válido.']);
        exit;
    }
    $contactoId = (int) $contactoValidado;
}

require __DIR__ . '/../../config/conexion.php';
$conexion->set_charset('utf8mb4');
$empresaId = null;

if ($contactoId !== null) {
    $consultaContacto = $conexion->prepare(
        'SELECT c.id, c.nombre, ec.id_empresa, e.empresa
         FROM contactos AS c
         LEFT JOIN empresa_contactos AS ec
           ON ec.id_contacto = c.id AND ec.activo = 1
         LEFT JOIN empresas AS e ON e.id_e = ec.id_empresa
         WHERE c.id = ?
         ORDER BY ec.es_principal DESC, ec.id ASC
         LIMIT 1'
    );
    $consultaContacto->bind_param('i', $contactoId);
    $consultaContacto->execute();
    $contacto = $consultaContacto->get_result()->fetch_assoc();
    $consultaContacto->close();

    if ($contacto === null) {
        $conexion->close();
        http_response_code(422);
        echo json_encode(['error' => 'El contacto seleccionado ya no existe.']);
        exit;
    }

    $contactoNombre = (string) ($contacto['nombre'] ?? $contactoNombre);
    if ($contacto['id_empresa'] !== null) {
        $empresaId = (int) $contacto['id_empresa'];
        $empresaNombre = (string) ($contacto['empresa'] ?? $empresaNombre);
    }
}

$archivo = 'COT SERVICOM ' . $numero . '.pdf';
$incompleta = $fecha === '' || $vigencia === '' || $contactoNombre === '' || $empresaNombre === '' || $total === '';
$estatus = $incompleta
    ? '<i class="bi bi-circle-fill" style="color: black;"> Vacía </i>'
    : '<i class="bi bi-circle-fill" style="color: blue;"> Esperar </i>';

$consulta = $conexion->prepare(
    'INSERT INTO cotizaciones
        (empresa_id, contacto_id, cot_fecha, cot_empresa, cot_contacto, cot_total,
         cot_archivo, cot_numero, cot_vigencia, cot_utilidad, cot_costos, cot_status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$consulta->bind_param(
    'iissssssssss',
    $empresaId,
    $contactoId,
    $fecha,
    $empresaNombre,
    $contactoNombre,
    $total,
    $archivo,
    $numero,
    $vigencia,
    $utilidad,
    $costos,
    $estatus
);
$consulta->execute();
$cotizacionId = (int) $conexion->insert_id;
$consulta->close();
$conexion->close();

echo json_encode([
    'ok' => true,
    'id' => $cotizacionId,
    'message' => 'La cotización se registró correctamente.',
], JSON_UNESCAPED_UNICODE);
