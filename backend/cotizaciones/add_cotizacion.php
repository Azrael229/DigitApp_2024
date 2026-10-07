<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

require __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/../helpers/folio_comercial.php';
require_once __DIR__ . '/../helpers/cotizacion_terminos.php';
$conexion->set_charset('utf8mb4');
$transaccion = false;

// Obtiene un identificador positivo y permite vacío solo en relaciones opcionales.
function cotizacionId($valor, bool $opcional = false): ?int
{
    if ($opcional && ($valor === null || trim((string) $valor) === '')) {
        return null;
    }
    $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        throw new InvalidArgumentException('Uno de los identificadores seleccionados no es válido.');
    }
    return (int) $id;
}

// Valida una fecha real en el formato utilizado por los formularios HTML.
function cotizacionFecha($valor, string $etiqueta): string
{
    $fecha = trim((string) $valor);
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    $errores = DateTimeImmutable::getLastErrors();
    if ($objeto === false || ($errores !== false
        && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) {
        throw new InvalidArgumentException($etiqueta . ' no es válida.');
    }
    return $fecha;
}

// Limita los textos antes de conservar la fotografía histórica de la cotización.
function cotizacionTexto($valor, int $maximo, string $etiqueta, bool $obligatorio = false): string
{
    $texto = trim((string) $valor);
    if (($obligatorio && $texto === '') || mb_strlen($texto, 'UTF-8') > $maximo) {
        throw new InvalidArgumentException($etiqueta . ' no es válido.');
    }
    return $texto;
}

try {
    $cotizacionEditarId = cotizacionId($_POST['cotizacion_id'] ?? '', true);
    $version = cotizacionId($_POST['version'] ?? '', true);
    $fecha = cotizacionFecha($_POST['coti_fecha'] ?? '', 'La fecha de emisión');
    $vigencia = cotizacionFecha($_POST['coti_vigencia'] ?? '', 'La fecha de vigencia');
    if ($vigencia < $fecha) {
        throw new InvalidArgumentException('La vigencia no puede ser anterior a la fecha de emisión.');
    }

    $oportunidadId = cotizacionId($_POST['oportunidad_id'] ?? '', true);
    $empresaId = cotizacionId($_POST['empresa_id'] ?? '');
    $contactoEntrada = trim((string) ($_POST['contacto_id'] ?? ''));
    if ($contactoEntrada === '') {
        $contactoEntrada = trim((string) ($_POST['select_contacto'] ?? ''));
    }
    $contactoId = cotizacionId($contactoEntrada);
    $direccionId = cotizacionId($_POST['direccion_id'] ?? '');

    $empresaNombre = '';
    $contactoNombre = '';
    $telefono = '';
    $correo = '';
    $departamento = '';
    $direccionTexto = '';
    $tiempoEntregaTipo = trim((string) ($_POST['tiempo_entrega_tipo'] ?? ''));
    if (!in_array($tiempoEntregaTipo, ['inmediato', 'dias_habiles', 'semanas'], true)) {
        throw new InvalidArgumentException('Selecciona un tiempo de entrega válido.');
    }
    $tiempoEntregaCantidad = $tiempoEntregaTipo === 'inmediato'
        ? null
        : cotizacionId($_POST['tiempo_entrega_cantidad'] ?? '');
    if ($tiempoEntregaCantidad !== null && $tiempoEntregaCantidad > 65535) {
        throw new InvalidArgumentException('El tiempo de entrega es demasiado grande.');
    }
    $condicionEntrega = trim((string) ($_POST['condicion_entrega'] ?? ''));
    if (!in_array($condicionEntrega, ['instalaciones_cliente', 'paqueteria', 'instalaciones_servicom'], true)) {
        throw new InvalidArgumentException('Selecciona una condición de entrega válida.');
    }
    $condicionPagoTipo = trim((string) ($_POST['condicion_pago_tipo'] ?? ''));
    if (!in_array($condicionPagoTipo, ['anticipado', 'anticipado_total', 'anticipo_saldo', 'credito'], true)) {
        throw new InvalidArgumentException('Selecciona una condición de pago válida.');
    }
    $pagoAnticipoPorcentaje = null;
    $pagoCreditoDias = null;
    $pagoSaldoMomento = null;
    if ($condicionPagoTipo === 'anticipo_saldo') {
        $porcentajeEntrada = trim((string) ($_POST['pago_anticipo_porcentaje'] ?? ''));
        if (!preg_match('/^\d{1,2}(?:\.\d{1,2})?$/D', $porcentajeEntrada)
            || (float) $porcentajeEntrada <= 0 || (float) $porcentajeEntrada >= 100) {
            throw new InvalidArgumentException('El porcentaje de anticipo debe ser mayor que 0 y menor que 100.');
        }
        $pagoAnticipoPorcentaje = number_format((float) $porcentajeEntrada, 2, '.', '');
        $pagoSaldoMomento = trim((string) ($_POST['pago_saldo_momento'] ?? ''));
        if (!in_array($pagoSaldoMomento, ['al_finalizar', 'contra_aviso_entrega'], true)) {
            throw new InvalidArgumentException('Selecciona cuándo se pagará el saldo restante.');
        }
    } elseif ($condicionPagoTipo === 'credito') {
        $pagoCreditoDias = cotizacionId($_POST['pago_credito_dias'] ?? '');
        if ($pagoCreditoDias > 365) {
            throw new InvalidArgumentException('Los días de crédito no pueden exceder 365.');
        }
    }

    $garantiaTipo = trim((string) ($_POST['garantia_tipo'] ?? ''));
    if (!in_array($garantiaTipo, ['sin_garantia', 'producto', 'mano_obra'], true)) {
        throw new InvalidArgumentException('Selecciona una condición de garantía válida.');
    }
    $garantiaVigencia = null;
    $garantiaUnidad = null;
    if ($garantiaTipo !== 'sin_garantia') {
        $garantiaVigencia = cotizacionId($_POST['garantia_vigencia'] ?? '');
        if ($garantiaVigencia > 65535) {
            throw new InvalidArgumentException('La vigencia de la garantía es demasiado grande.');
        }
        $garantiaUnidad = trim((string) ($_POST['garantia_unidad'] ?? ''));
        if (!in_array($garantiaUnidad, ['dias', 'meses'], true)) {
            throw new InvalidArgumentException('Selecciona días o meses para la garantía.');
        }
    }
    $costosEnvio = trim((string) ($_POST['costos_envio'] ?? ''));
    if (!in_array($costosEnvio, ['incluye', 'no_incluye'], true)) {
        throw new InvalidArgumentException('Selecciona si la cotización incluye costos de envío.');
    }

    $partidas = [];
    $subtotal = 0.0;
    $cantidades = $_POST['partida_cantidad'] ?? [];
    $unidades = $_POST['partida_unidad'] ?? [];
    $descripciones = $_POST['partida_descripcion'] ?? [];
    $valores = $_POST['partida_valor_unitario'] ?? [];
    if (!is_array($cantidades) || !is_array($unidades) || !is_array($descripciones) || !is_array($valores)
        || count($cantidades) === 0 || count($cantidades) !== count($unidades)
        || count($cantidades) !== count($descripciones) || count($cantidades) !== count($valores)
        || count($cantidades) > 65535) {
        throw new InvalidArgumentException('Las partidas enviadas no son válidas.');
    }
    foreach ($cantidades as $indice => $cantidadValor) {
        $posicion = $indice + 1;
        $cantidadEntrada = trim((string) $cantidadValor);
        $unidad = cotizacionTexto($unidades[$indice] ?? '', 30, 'La unidad de la partida ' . $posicion);
        $descripcion = trim((string) ($descripciones[$indice] ?? ''));
        $valorEntrada = trim((string) ($valores[$indice] ?? ''));
        if (!preg_match('/^\d{1,9}(?:\.\d{1,3})?$/D', $cantidadEntrada) || (float) $cantidadEntrada <= 0) {
            throw new InvalidArgumentException('La cantidad de la partida ' . $posicion . ' debe ser mayor que cero y usar hasta tres decimales.');
        }
        if ($unidad === '' || $descripcion === '') {
            throw new InvalidArgumentException('Completa la unidad y la descripción de la partida ' . $posicion . '.');
        }
        if (!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D', $valorEntrada)) {
            throw new InvalidArgumentException('El valor unitario de la partida ' . $posicion . ' debe usar hasta dos decimales.');
        }
        $cantidad = number_format((float) $cantidadEntrada, 3, '.', '');
        $valorUnitario = number_format((float) $valorEntrada, 2, '.', '');
        $importeNumero = round((float) $cantidad * (float) $valorUnitario, 2);
        if ($importeNumero > 999999999999.99) {
            throw new InvalidArgumentException('El importe de la partida ' . $posicion . ' excede el máximo permitido.');
        }
        $importe = number_format($importeNumero, 2, '.', '');
        $subtotal += $importeNumero;
        $partidas[] = compact('posicion', 'cantidad', 'unidad', 'descripcion', 'valorUnitario', 'importe');
    }
    $subtotal = round($subtotal, 2);
    $iva = round($subtotal * 0.16, 2);
    $total = round($subtotal + $iva, 2);
    if ($total > 999999999999.99) {
        throw new InvalidArgumentException('El total de la cotización excede el máximo permitido.');
    }
    $subtotalTexto = number_format($subtotal, 2, '.', '');
    $ivaTexto = number_format($iva, 2, '.', '');
    $totalTexto = number_format($total, 2, '.', '');

    $notas = [];
    for ($posicion = 1; $posicion <= 7; $posicion++) {
        $nota = cotizacionTexto($_POST['notas' . $posicion] ?? '', 255, 'La nota ' . $posicion);
        if ($nota !== '') {
            $notas[] = ['posicion' => $posicion, 'texto' => $nota];
        }
    }

    $conexion->begin_transaction();
    $transaccion = true;

    $validarEmpresa = $conexion->prepare('SELECT id_e, empresa FROM empresas WHERE id_e = ?');
    $validarEmpresa->bind_param('i', $empresaId);
    $validarEmpresa->execute();
    $empresaExiste = $validarEmpresa->get_result()->fetch_assoc();
    $validarEmpresa->close();
    if ($empresaExiste === null) {
        throw new InvalidArgumentException('La empresa seleccionada ya no existe.');
    }
    $empresaNombre = trim((string) $empresaExiste['empresa']);

    if ($contactoId !== null) {
        $validarContacto = $conexion->prepare(
            'SELECT c.id, c.nombre, c.celular, c.correo, COALESCE(cd.nombre, c.depto) AS departamento FROM contactos c
             JOIN empresa_contactos ec ON ec.id_contacto = c.id
             LEFT JOIN catalogo_departamentos cd ON cd.id = c.id_departamento
             WHERE c.id = ? AND ec.id_empresa = ? AND c.activo = 1 AND ec.activo = 1 LIMIT 1'
        );
        $validarContacto->bind_param('ii', $contactoId, $empresaId);
        $validarContacto->execute();
        $contactoExiste = $validarContacto->get_result()->fetch_assoc();
        $validarContacto->close();
        if ($contactoExiste === null) {
            throw new InvalidArgumentException('El contacto no pertenece a la empresa seleccionada.');
        }
        $contactoNombre = trim((string) $contactoExiste['nombre']);
        $telefono = trim((string) ($contactoExiste['celular'] ?? ''));
        $correo = trim((string) ($contactoExiste['correo'] ?? ''));
        $departamento = trim((string) ($contactoExiste['departamento'] ?? ''));
    }

    if ($direccionId !== null) {
        $validarDireccion = $conexion->prepare(
            'SELECT * FROM empresa_direcciones WHERE id = ? AND empresa_id = ? LIMIT 1'
        );
        $validarDireccion->bind_param('ii', $direccionId, $empresaId);
        $validarDireccion->execute();
        $direccionExiste = $validarDireccion->get_result()->fetch_assoc();
        $validarDireccion->close();
        if ($direccionExiste === null) {
            throw new InvalidArgumentException('La dirección no pertenece a la empresa seleccionada.');
        }
        $direccionTexto = cotizacionDireccionActual($direccionExiste);
    }

    if ($oportunidadId !== null) {
        $validarOportunidad = $conexion->prepare(
            'SELECT empresa_id, contacto_id, direccion_id
             FROM oportunidades_comerciales WHERE id = ? FOR UPDATE'
        );
        $validarOportunidad->bind_param('i', $oportunidadId);
        $validarOportunidad->execute();
        $oportunidad = $validarOportunidad->get_result()->fetch_assoc();
        $validarOportunidad->close();
        if ($oportunidad === null || (int) $oportunidad['empresa_id'] !== $empresaId) {
            throw new InvalidArgumentException('La oportunidad no corresponde a la empresa seleccionada.');
        }
    }

    $moneda = 'MXN';
    $formatoVersion = COTIZACION_FORMATO_VERSION;
    $terminosVersion = COTIZACION_TERMINOS_VERSION;
    $terminosSnapshot = cotizacionTerminosSerializados();
    $estatus = 'preparacion';
    if ($cotizacionEditarId !== null) {
        $estatusEntrada = trim((string) ($_POST['cot_status'] ?? ''));
        if (!array_key_exists($estatusEntrada, COTIZACION_ESTATUS)) {
            throw new InvalidArgumentException('Selecciona un estatus válido para la cotización.');
        }
        $estatus = $estatusEntrada;
    }
    if ($cotizacionEditarId !== null) {
        $consultarActual = $conexion->prepare(
            'SELECT cot_numero, version FROM cotizaciones WHERE id_coti = ? FOR UPDATE'
        );
        $consultarActual->bind_param('i', $cotizacionEditarId);
        $consultarActual->execute();
        $actual = $consultarActual->get_result()->fetch_assoc();
        $consultarActual->close();
        if ($actual === null) {
            throw new InvalidArgumentException('La cotización que deseas editar ya no existe.');
        }
        if ($version === null || (int) $actual['version'] !== $version) {
            throw new RuntimeException('Otra edición modificó esta cotización. Recarga la página antes de guardar.');
        }
        $numero = (string) $actual['cot_numero'];
        $cotizacionId = $cotizacionEditarId;

        $actualizar = $conexion->prepare(
            'UPDATE cotizaciones SET
                empresa_id = ?, contacto_id = ?, direccion_id = ?, cot_fecha = ?,
                cot_empresa = ?, cot_contacto = ?, cot_telefono = ?, cot_correo = ?,
                cot_departamento = ?, cot_direccion = ?, cot_total = ?, cot_moneda = ?,
                cot_subtotal = ?, cot_iva = ?, cot_vigencia = ?,
                cot_tiempo_entrega_tipo = ?, cot_tiempo_entrega_cantidad = ?,
                cot_condicion_entrega = ?, cot_condicion_pago_tipo = ?,
                cot_pago_anticipo_porcentaje = ?, cot_pago_credito_dias = ?,
                cot_pago_saldo_momento = ?, cot_garantia_tipo = ?,
                cot_garantia_vigencia = ?, cot_garantia_unidad = ?, cot_costos_envio = ?,
                cot_formato_version = ?, cot_terminos_version = ?, cot_terminos_snapshot = ?,
                cot_status = ?, version = version + 1
             WHERE id_coti = ? AND version = ?'
        );
        $tiposActualizar = 'iii' . str_repeat('s', 13) . 'i' . str_repeat('s', 3) . 'i'
            . str_repeat('s', 2) . 'i' . str_repeat('s', 6) . 'ii';
        $actualizar->bind_param(
            $tiposActualizar,
            $empresaId,
            $contactoId,
            $direccionId,
            $fecha,
            $empresaNombre,
            $contactoNombre,
            $telefono,
            $correo,
            $departamento,
            $direccionTexto,
            $totalTexto,
            $moneda,
            $subtotalTexto,
            $ivaTexto,
            $vigencia,
            $tiempoEntregaTipo,
            $tiempoEntregaCantidad,
            $condicionEntrega,
            $condicionPagoTipo,
            $pagoAnticipoPorcentaje,
            $pagoCreditoDias,
            $pagoSaldoMomento,
            $garantiaTipo,
            $garantiaVigencia,
            $garantiaUnidad,
            $costosEnvio,
            $formatoVersion,
            $terminosVersion,
            $terminosSnapshot,
            $estatus,
            $cotizacionId,
            $version
        );
        $actualizar->execute();
        if ($actualizar->affected_rows !== 1) {
            throw new RuntimeException('Otra edición modificó esta cotización. Recarga la página antes de guardar.');
        }
        $actualizar->close();

        $eliminarPartidas = $conexion->prepare('DELETE FROM cotizacion_partidas WHERE cotizacion_id = ?');
        $eliminarPartidas->bind_param('i', $cotizacionId);
        $eliminarPartidas->execute();
        $eliminarPartidas->close();
        $eliminarNotas = $conexion->prepare('DELETE FROM cotizacion_notas WHERE cotizacion_id = ?');
        $eliminarNotas->bind_param('i', $cotizacionId);
        $eliminarNotas->execute();
        $eliminarNotas->close();
    } else {
        $numero = reservarFolioComercial($conexion, 'Q', $fecha);
        $archivo = 'COT SERVICOM ' . $numero . '.pdf';
        $utilidad = cotizacionTexto($_POST['total_utilidad'] ?? '', 50, 'La utilidad');
        $costos = cotizacionTexto($_POST['costos'] ?? '', 50, 'Los costos');
        $insertar = $conexion->prepare(
            'INSERT INTO cotizaciones
                (empresa_id, contacto_id, direccion_id, cot_fecha, cot_empresa, cot_contacto,
                 cot_telefono, cot_correo, cot_departamento, cot_direccion, cot_total, cot_moneda,
                 cot_subtotal, cot_iva, cot_archivo, cot_numero, cot_vigencia,
                 cot_tiempo_entrega_tipo, cot_tiempo_entrega_cantidad,
                 cot_condicion_entrega, cot_condicion_pago_tipo,
                 cot_pago_anticipo_porcentaje, cot_pago_credito_dias,
                 cot_pago_saldo_momento, cot_garantia_tipo, cot_garantia_vigencia,
                 cot_garantia_unidad, cot_costos_envio,
                 cot_formato_version, cot_terminos_version, cot_terminos_snapshot,
                 cot_utilidad, cot_costos, cot_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $tipos = 'iii' . str_repeat('s', 15) . 'i' . str_repeat('s', 3) . 'i'
            . str_repeat('s', 2) . 'i' . str_repeat('s', 8);
        $insertar->bind_param(
            $tipos,
            $empresaId,
            $contactoId,
            $direccionId,
            $fecha,
            $empresaNombre,
            $contactoNombre,
            $telefono,
            $correo,
            $departamento,
            $direccionTexto,
            $totalTexto,
            $moneda,
            $subtotalTexto,
            $ivaTexto,
            $archivo,
            $numero,
            $vigencia,
            $tiempoEntregaTipo,
            $tiempoEntregaCantidad,
            $condicionEntrega,
            $condicionPagoTipo,
            $pagoAnticipoPorcentaje,
            $pagoCreditoDias,
            $pagoSaldoMomento,
            $garantiaTipo,
            $garantiaVigencia,
            $garantiaUnidad,
            $costosEnvio,
            $formatoVersion,
            $terminosVersion,
            $terminosSnapshot,
            $utilidad,
            $costos,
            $estatus
        );
        $insertar->execute();
        $cotizacionId = (int) $conexion->insert_id;
        $insertar->close();
    }

    $insertarPartida = $conexion->prepare(
        'INSERT INTO cotizacion_partidas
            (cotizacion_id, posicion, cantidad, unidad, descripcion, valor_unitario, importe)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($partidas as $partida) {
        $insertarPartida->bind_param(
            'iisssss',
            $cotizacionId,
            $partida['posicion'],
            $partida['cantidad'],
            $partida['unidad'],
            $partida['descripcion'],
            $partida['valorUnitario'],
            $partida['importe']
        );
        $insertarPartida->execute();
    }
    $insertarPartida->close();

    if ($notas) {
        $insertarNota = $conexion->prepare(
            'INSERT INTO cotizacion_notas (cotizacion_id, posicion, texto) VALUES (?, ?, ?)'
        );
        foreach ($notas as $nota) {
            $insertarNota->bind_param('iis', $cotizacionId, $nota['posicion'], $nota['texto']);
            $insertarNota->execute();
        }
        $insertarNota->close();
    }

    if ($oportunidadId !== null && $cotizacionEditarId === null) {
        $vincular = $conexion->prepare(
            'INSERT INTO oportunidad_cotizaciones (oportunidad_id, cotizacion_id) VALUES (?, ?)'
        );
        $vincular->bind_param('ii', $oportunidadId, $cotizacionId);
        $vincular->execute();
        $vincular->close();
    }

    $conexion->commit();
    $transaccion = false;
    echo json_encode([
        'ok' => true,
        'id' => $cotizacionId,
        'numero' => $numero,
        'subtotal' => $subtotalTexto,
        'iva' => $ivaTexto,
        'total' => $totalTexto,
        'message' => $cotizacionEditarId !== null
            ? 'La cotización se actualizó correctamente.'
            : 'La cotización se registró correctamente.',
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    if ($transaccion) {
        $conexion->rollback();
    }
    $estado = 500;
    $mensaje = 'No fue posible guardar la cotización. Intenta nuevamente.';
    if ($error instanceof InvalidArgumentException) {
        $estado = 422;
        $mensaje = $error->getMessage();
    } elseif ($error instanceof RuntimeException) {
        $estado = 409;
        $mensaje = $error->getMessage();
    } else {
        error_log('Cotizaciones: ' . $error->getMessage());
    }
    http_response_code($estado);
    echo json_encode(['error' => $mensaje], JSON_UNESCAPED_UNICODE);
} finally {
    $conexion->close();
}
