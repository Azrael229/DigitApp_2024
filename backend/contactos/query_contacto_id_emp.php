<?php

$idEmpresa = filter_var(trim(file_get_contents('php://input')), FILTER_VALIDATE_INT);

require __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($idEmpresa === false || $idEmpresa < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'Empresa no válida', 'contactos' => []]);
    mysqli_close($conexion);
    exit;
}

$consultaContactos = $conexion->prepare(
    'SELECT c.id, c.nombre, c.celular, c.correo, c.depto, c.id_departamento,
            COALESCE(d.nombre, c.depto) AS departamento, c.puesto, c.activo, ec.es_principal
     FROM empresa_contactos AS ec
     INNER JOIN contactos AS c ON c.id = ec.id_contacto
     LEFT JOIN catalogo_departamentos AS d ON d.id = c.id_departamento
     WHERE ec.id_empresa = ? AND ec.activo = 1
     ORDER BY ec.es_principal DESC, c.nombre ASC, c.id ASC'
);
$consultaContactos->bind_param('i', $idEmpresa);
$consultaContactos->execute();
$contactos = $consultaContactos->get_result()->fetch_all(MYSQLI_ASSOC);
$consultaContactos->close();

// Informe actual consume nombre/correo en el objeto raiz. Se conserva el primer
// contacto ordenado como compatibilidad y se expone el arreglo completo para el nuevo flujo.
$respuesta = $contactos[0] ?? [];
$respuesta['contactos'] = $contactos;

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>
