<?php
header('Content-Type: application/json; charset=utf-8');

require_once("conexion.php");

/*
|--------------------------------------------------------------------------
| querys/add_orden_servicio.php
|--------------------------------------------------------------------------
| Este archivo:
| 1. Recibe el id_coti por POST desde AJAX
| 2. Busca la cotizacion
| 3. Valida si ya existe una O.S. para esa cotizacion
| 4. Inserta la nueva orden de servicio
| 5. Devuelve JSON con success + id_os
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Metodo no permitido'
    ]);
    exit;
}

$idCoti = isset($_POST['id_coti']) ? (int) $_POST['id_coti'] : 0;

if ($idCoti <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de cotizacion no recibido o invalido'
    ]);
    exit;
}

try {
    mysqli_begin_transaction($conexion);

    /*
    |--------------------------------------------------------------------------
    | 1. Validar si la cotizacion existe
    |--------------------------------------------------------------------------
    */
    $sqlCoti = "SELECT * FROM cotizaciones WHERE id_coti = ? LIMIT 1";
    $stmtCoti = mysqli_prepare($conexion, $sqlCoti);

    if (!$stmtCoti) {
        throw new Exception('Error al preparar consulta de cotizacion: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmtCoti, "i", $idCoti);
    mysqli_stmt_execute($stmtCoti);
    $resultCoti = mysqli_stmt_get_result($stmtCoti);

    if (!$resultCoti || mysqli_num_rows($resultCoti) === 0) {
        throw new Exception('Cotizacion no encontrada');
    }

    $cotizacion = mysqli_fetch_assoc($resultCoti);
    mysqli_stmt_close($stmtCoti);

    /*
    |--------------------------------------------------------------------------
    | 2. Validar si ya existe una orden de servicio para esta cotizacion
    |--------------------------------------------------------------------------
    */
    $sqlExiste = "SELECT id_os FROM ordenes_servicio WHERE id_coti = ? LIMIT 1";
    $stmtExiste = mysqli_prepare($conexion, $sqlExiste);

    if (!$stmtExiste) {
        throw new Exception('Error al preparar validacion de O.S.: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmtExiste, "i", $idCoti);
    mysqli_stmt_execute($stmtExiste);
    $resultExiste = mysqli_stmt_get_result($stmtExiste);

    if ($resultExiste && mysqli_num_rows($resultExiste) > 0) {
        $osExistente = mysqli_fetch_assoc($resultExiste);
        mysqli_stmt_close($stmtExiste);

        mysqli_commit($conexion);

        echo json_encode([
            'success' => true,
            'message' => 'La orden de servicio ya existia',
            'id_os' => (int) $osExistente['id_os'],
            'ya_existia' => true
        ]);
        exit;
    }

    mysqli_stmt_close($stmtExiste);

    /*
    |--------------------------------------------------------------------------
    | 3. Preparar datos para insertar la O.S.
    |--------------------------------------------------------------------------
    */
    $idEmpresa = isset($cotizacion['id_e'])
        ? (int) $cotizacion['id_e']
        : (isset($cotizacion['id_empresa']) ? (int) $cotizacion['id_empresa'] : 0);

    $empresaNombre = isset($cotizacion['empresa_nombre'])
        ? trim((string) $cotizacion['empresa_nombre'])
        : (isset($cotizacion['cot_empresa']) ? trim((string) $cotizacion['cot_empresa']) : '');

    $contactoNombre = isset($cotizacion['contacto_nombre'])
        ? trim((string) $cotizacion['contacto_nombre'])
        : (isset($cotizacion['cot_contacto']) ? trim((string) $cotizacion['cot_contacto']) : '');

    $fechaCreacion = date('Y-m-d H:i:s');
    $fechaServicio = date('Y-m-d');
    $importe = isset($cotizacion['importe'])
        ? (float) $cotizacion['importe']
        : (isset($cotizacion['cot_total']) ? (float) $cotizacion['cot_total'] : 0);
    $estado = 'pendiente';
    $nombreProyecto = isset($cotizacion['nombre_proyecto']) ? trim((string) $cotizacion['nombre_proyecto']) : '';
    $descripcion = isset($cotizacion['descripcion']) ? trim((string) $cotizacion['descripcion']) : '';
    $observaciones = isset($cotizacion['observaciones']) ? trim((string) $cotizacion['observaciones']) : '';
    $activo = 1;

    /*
    |--------------------------------------------------------------------------
    | 4. Insertar la orden de servicio
    |--------------------------------------------------------------------------
    */
    $sqlInsert = "
        INSERT INTO ordenes_servicio (
            id_coti,
            id_e,
            empresa_nombre,
            contacto_nombre,
            fecha_creacion,
            fecha_servicio,
            importe,
            estado,
            nombre_proyecto,
            descripcion,
            observaciones,
            activo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmtInsert = mysqli_prepare($conexion, $sqlInsert);

    if (!$stmtInsert) {
        throw new Exception('Error al preparar INSERT de O.S.: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmtInsert,
        "iissssdssssi",
        $idCoti,
        $idEmpresa,
        $empresaNombre,
        $contactoNombre,
        $fechaCreacion,
        $fechaServicio,
        $importe,
        $estado,
        $nombreProyecto,
        $descripcion,
        $observaciones,
        $activo
    );

    $okInsert = mysqli_stmt_execute($stmtInsert);

    if (!$okInsert) {
        throw new Exception('Error al insertar la orden de servicio: ' . mysqli_stmt_error($stmtInsert));
    }

    $idOs = mysqli_insert_id($conexion);

    mysqli_stmt_close($stmtInsert);

    mysqli_commit($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Orden de servicio generada correctamente',
        'id_os' => (int) $idOs,
        'ya_existia' => false
    ]);
    exit;
} catch (Exception $e) {
    mysqli_rollback($conexion);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
