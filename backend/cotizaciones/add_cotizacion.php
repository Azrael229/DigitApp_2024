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

// Conserva una fecha pendiente como NULL y valida las fechas que sí fueron capturadas.
function cotizacionFechaOpcional($valor, string $etiqueta): ?string
{
    return trim((string) $valor) === '' ? null : cotizacionFecha($valor, $etiqueta);
}

// Valida enteros positivos opcionales usados por las condiciones comerciales.
function cotizacionEnteroOpcional($valor, string $etiqueta, int $maximo): ?int
{
    if (trim((string) $valor) === '') {
        return null;
    }
    $numero = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($numero === false || (int) $numero > $maximo) {
        throw new InvalidArgumentException($etiqueta . ' no es válido.');
    }
    return (int) $numero;
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
    $fecha = cotizacionFechaOpcional($_POST['coti_fecha'] ?? '', 'La fecha de emisión');
    $vigencia = cotizacionFechaOpcional($_POST['coti_vigencia'] ?? '', 'La fecha de vigencia');
    if ($fecha !== null && $vigencia !== null && $vigencia < $fecha) {
        throw new InvalidArgumentException('La vigencia no puede ser anterior a la fecha de emisión.');
    }

    $oportunidadId = cotizacionId($_POST['oportunidad_id'] ?? '', true);
    $empresaId = cotizacionId($_POST['empresa_id'] ?? '', true);
    $contactoEntrada = trim((string) ($_POST['contacto_id'] ?? ''));
    if ($contactoEntrada === '') {
        $contactoEntrada = trim((string) ($_POST['select_contacto'] ?? ''));
    }
    $contactoId = cotizacionId($contactoEntrada, true);
    $direccionId = cotizacionId($_POST['direccion_id'] ?? '', true);

    $empresaNombre = cotizacionTexto($_POST['nombre_empresa'] ?? '', 50, 'El nombre de la empresa');
    $contactoNombre = cotizacionTexto($_POST['nombre_contacto'] ?? '', 50, 'El nombre del contacto');
    $telefono = cotizacionTexto($_POST['cel_contacto'] ?? '', 30, 'El teléfono');
    $correo = cotizacionTexto($_POST['correo_contacto'] ?? '', 150, 'El correo');
    $departamento = cotizacionTexto($_POST['depto_contacto'] ?? '', 100, 'El departamento');
    $direccionTexto = cotizacionTexto($_POST['dir_empresa'] ?? '', 65535, 'La dirección');
    $tiempoEntregaTipo = trim((string) ($_POST['tiempo_entrega_tipo'] ?? ''));
    if ($tiempoEntregaTipo !== '' && !in_array($tiempoEntregaTipo, ['inmediato', 'dias_habiles', 'semanas'], true)) {
        throw new InvalidArgumentException('Selecciona un tiempo de entrega válido.');
    }
    $tiempoEntregaTipo = $tiempoEntregaTipo === '' ? null : $tiempoEntregaTipo;
    $tiempoEntregaCantidad = $tiempoEntregaTipo === 'inmediato' || $tiempoEntregaTipo === null
        ? null
        : cotizacionEnteroOpcional($_POST['tiempo_entrega_cantidad'] ?? '', 'El tiempo de entrega', 65535);
    $condicionEntrega = trim((string) ($_POST['condicion_entrega'] ?? ''));
    if ($condicionEntrega !== '' && !in_array($condicionEntrega, ['instalaciones_cliente', 'paqueteria', 'instalaciones_servicom'], true)) {
        throw new InvalidArgumentException('Selecciona una condición de entrega válida.');
    }
    $condicionEntrega = $condicionEntrega === '' ? null : $condicionEntrega;
    $condicionPagoTipo = trim((string) ($_POST['condicion_pago_tipo'] ?? ''));
    if ($condicionPagoTipo !== '' && !in_array($condicionPagoTipo, ['anticipado', 'anticipado_total', 'anticipo_saldo', 'credito'], true)) {
        throw new InvalidArgumentException('Selecciona una condición de pago válida.');
    }
    $condicionPagoTipo = $condicionPagoTipo === '' ? null : $condicionPagoTipo;
    $pagoAnticipoPorcentaje = null;
    $pagoCreditoDias = null;
    $pagoSaldoMomento = null;
    if ($condicionPagoTipo === 'anticipo_saldo') {
        $porcentajeEntrada = trim((string) ($_POST['pago_anticipo_porcentaje'] ?? ''));
        if ($porcentajeEntrada !== '' && (!preg_match('/^\d{1,2}(?:\.\d{1,2})?$/D', $porcentajeEntrada)
            || (float) $porcentajeEntrada <= 0 || (float) $porcentajeEntrada >= 100)) {
            throw new InvalidArgumentException('El porcentaje de anticipo debe ser mayor que 0 y menor que 100.');
        }
        $pagoAnticipoPorcentaje = $porcentajeEntrada === '' ? null : number_format((float) $porcentajeEntrada, 2, '.', '');
        $pagoSaldoMomento = trim((string) ($_POST['pago_saldo_momento'] ?? ''));
        if ($pagoSaldoMomento !== '' && !in_array($pagoSaldoMomento, ['al_finalizar', 'contra_aviso_entrega'], true)) {
            throw new InvalidArgumentException('Selecciona cuándo se pagará el saldo restante.');
        }
        $pagoSaldoMomento = $pagoSaldoMomento === '' ? null : $pagoSaldoMomento;
    } elseif ($condicionPagoTipo === 'credito') {
        $pagoCreditoDias = cotizacionEnteroOpcional($_POST['pago_credito_dias'] ?? '', 'Los días de crédito', 365);
    }

    $garantiaTipo = trim((string) ($_POST['garantia_tipo'] ?? ''));
    if ($garantiaTipo !== '' && !in_array($garantiaTipo, ['sin_garantia', 'producto', 'mano_obra'], true)) {
        throw new InvalidArgumentException('Selecciona una condición de garantía válida.');
    }
    $garantiaTipo = $garantiaTipo === '' ? null : $garantiaTipo;
    $garantiaVigencia = null;
    $garantiaUnidad = null;
    if (in_array($garantiaTipo, ['producto', 'mano_obra'], true)) {
        $garantiaVigencia = cotizacionEnteroOpcional($_POST['garantia_vigencia'] ?? '', 'La vigencia de la garantía', 65535);
        $garantiaUnidad = trim((string) ($_POST['garantia_unidad'] ?? ''));
        if ($garantiaUnidad !== '' && !in_array($garantiaUnidad, ['dias', 'meses'], true)) {
            throw new InvalidArgumentException('Selecciona días o meses para la garantía.');
        }
        $garantiaUnidad = $garantiaUnidad === '' ? null : $garantiaUnidad;
    }
    $costosEnvio = trim((string) ($_POST['costos_envio'] ?? ''));
    if ($costosEnvio !== '' && !in_array($costosEnvio, ['incluye', 'no_incluye'], true)) {
        throw new InvalidArgumentException('Selecciona si la cotización incluye costos de envío.');
    }
    $costosEnvio = $costosEnvio === '' ? null : $costosEnvio;

    $partidas = [];
    $subtotal = 0.0;
    $cantidades = $_POST['partida_cantidad'] ?? [];
    $unidades = $_POST['partida_unidad'] ?? [];
    $cantidadesSecundarias = $_POST['partida_cantidad_secundaria'] ?? [];
    $unidadesSecundarias = $_POST['partida_unidad_secundaria'] ?? [];
    $descripciones = $_POST['partida_descripcion'] ?? [];
    $valores = $_POST['partida_valor_unitario'] ?? [];
    $catalogoIds = $_POST['partida_catalogo_item_id'] ?? [];
    $catalogoCodigos = $_POST['partida_codigo_catalogo'] ?? [];
    $catalogoTipos = $_POST['partida_tipo_catalogo'] ?? [];
    $descuentoTipos = $_POST['partida_descuento_tipo'] ?? [];
    $descuentoValores = $_POST['partida_descuento_valor'] ?? [];
    if (!is_array($cantidades) || !is_array($unidades) || !is_array($cantidadesSecundarias)
        || !is_array($unidadesSecundarias) || !is_array($descripciones) || !is_array($valores)
        || !is_array($catalogoIds) || !is_array($catalogoCodigos) || !is_array($catalogoTipos)
        || !is_array($descuentoTipos) || !is_array($descuentoValores)
        || count($cantidades) !== count($unidades)
        || count($cantidades) !== count($cantidadesSecundarias)
        || count($cantidades) !== count($unidadesSecundarias)
        || count($cantidades) !== count($descripciones) || count($cantidades) !== count($valores)
        || count($cantidades) !== count($catalogoIds) || count($cantidades) !== count($catalogoCodigos)
        || count($cantidades) !== count($catalogoTipos) || count($cantidades) !== count($descuentoTipos)
        || count($cantidades) !== count($descuentoValores)
        || count($cantidades) > 65535) {
        throw new InvalidArgumentException('Las partidas enviadas no son válidas.');
    }
    foreach ($cantidades as $indice => $cantidadValor) {
        $posicion = $indice + 1;
        $cantidadEntrada = trim((string) $cantidadValor);
        $unidad = cotizacionTexto($unidades[$indice] ?? '', 30, 'La unidad de la partida ' . $posicion);
        $cantidadSecundariaEntrada = trim((string) ($cantidadesSecundarias[$indice] ?? ''));
        $unidadSecundaria = cotizacionTexto($unidadesSecundarias[$indice] ?? '', 30, 'La unidad adicional de la partida ' . $posicion);
        $descripcion = trim((string) ($descripciones[$indice] ?? ''));
        $valorEntrada = trim((string) ($valores[$indice] ?? ''));
        $catalogoItemId = cotizacionId($catalogoIds[$indice] ?? '', true);
        $codigoCatalogo = strtoupper(cotizacionTexto($catalogoCodigos[$indice] ?? '', 32, 'El código de catálogo de la partida ' . $posicion));
        $tipoCatalogo = trim((string) ($catalogoTipos[$indice] ?? ''));
        $descuentoTipo = trim((string) ($descuentoTipos[$indice] ?? 'ninguno'));
        $descuentoEntrada = trim((string) ($descuentoValores[$indice] ?? '0'));
        if ($cantidadEntrada === '' && $unidad === '' && $cantidadSecundariaEntrada === ''
            && $unidadSecundaria === '' && $descripcion === '' && $valorEntrada === '' && $codigoCatalogo === '') {
            continue;
        }
        if (!in_array($descuentoTipo, ['ninguno', 'porcentaje', 'monto'], true)) {
            throw new InvalidArgumentException('El tipo de descuento de la partida ' . $posicion . ' no es válido.');
        }
        if ($descuentoEntrada === '') {
            $descuentoEntrada = '0';
        }
        if (!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D', $descuentoEntrada)) {
            throw new InvalidArgumentException('El descuento de la partida ' . $posicion . ' debe usar hasta dos decimales.');
        }
        if ($descuentoTipo === 'porcentaje' && (float) $descuentoEntrada > 100) {
            throw new InvalidArgumentException('El descuento porcentual de la partida ' . $posicion . ' no puede exceder 100%.');
        }
        if ($tipoCatalogo !== '' && !in_array($tipoCatalogo, ['producto', 'servicio'], true)) {
            throw new InvalidArgumentException('El tipo de catálogo de la partida ' . $posicion . ' no es válido.');
        }
        if ($catalogoItemId !== null) {
            $validarItem = $conexion->prepare('SELECT codigo, tipo FROM catalogo_items_comerciales WHERE id = ?');
            $validarItem->bind_param('i', $catalogoItemId);
            $validarItem->execute();
            $itemCatalogo = $validarItem->get_result()->fetch_assoc();
            $validarItem->close();
            if (!$itemCatalogo || strtoupper((string) $itemCatalogo['codigo']) !== $codigoCatalogo
                || (string) $itemCatalogo['tipo'] !== $tipoCatalogo) {
                throw new InvalidArgumentException('El código de catálogo de la partida ' . $posicion . ' cambió. Selecciónalo nuevamente.');
            }
        } else {
            $tipoCatalogo = $tipoCatalogo === '' ? null : $tipoCatalogo;
        }
        if ($cantidadEntrada !== '' && (!preg_match('/^\d{1,9}(?:\.\d{1,3})?$/D', $cantidadEntrada)
            || (float) $cantidadEntrada <= 0)) {
            throw new InvalidArgumentException('La cantidad de la partida ' . $posicion . ' debe ser mayor que cero y usar hasta tres decimales.');
        }
        $usaCantidadSecundaria = $cantidadSecundariaEntrada !== '' || $unidadSecundaria !== '';
        if ($cantidadSecundariaEntrada !== ''
            && (!preg_match('/^\d{1,9}(?:\.\d{1,3})?$/D', $cantidadSecundariaEntrada)
                || (float) $cantidadSecundariaEntrada <= 0)) {
            throw new InvalidArgumentException('La cantidad adicional de la partida ' . $posicion . ' debe ser mayor que cero y usar hasta tres decimales.');
        }
        if ($valorEntrada !== '' && !preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D', $valorEntrada)) {
            throw new InvalidArgumentException('El valor unitario de la partida ' . $posicion . ' debe usar hasta dos decimales.');
        }
        $cantidad = $cantidadEntrada === '' ? null : number_format((float) $cantidadEntrada, 3, '.', '');
        $cantidadSecundaria = $usaCantidadSecundaria
            ? ($cantidadSecundariaEntrada === '' ? null : number_format((float) $cantidadSecundariaEntrada, 3, '.', ''))
            : '1.000';
        $unidad = $unidad === '' ? null : $unidad;
        // La cadena vacía distingue una segunda cantidad aún sin unidad de un factor no utilizado (NULL).
        $unidadSecundaria = $usaCantidadSecundaria ? $unidadSecundaria : null;
        $descripcion = $descripcion === '' ? null : $descripcion;
        $valorUnitario = $valorEntrada === '' ? null : number_format((float) $valorEntrada, 2, '.', '');
        $importeBrutoNumero = $cantidad !== null && $cantidadSecundaria !== null && $valorUnitario !== null
            ? round((float) $cantidad * (float) $cantidadSecundaria * (float) $valorUnitario, 2)
            : null;
        if ($importeBrutoNumero !== null && $importeBrutoNumero > 999999999999.99) {
            throw new InvalidArgumentException('El importe de la partida ' . $posicion . ' excede el máximo permitido.');
        }
        $descuentoValor = number_format($descuentoTipo === 'ninguno' ? 0 : (float) $descuentoEntrada, 2, '.', '');
        $descuentoImporteNumero = 0.0;
        if ($importeBrutoNumero !== null) {
            $descuentoImporteNumero = $descuentoTipo === 'porcentaje'
                ? round($importeBrutoNumero * (float) $descuentoValor / 100, 2)
                : ($descuentoTipo === 'monto' ? (float) $descuentoValor : 0.0);
            if ($descuentoImporteNumero > $importeBrutoNumero) {
                throw new InvalidArgumentException('El descuento de la partida ' . $posicion . ' no puede exceder su importe.');
            }
        }
        $importeNumero = $importeBrutoNumero === null ? null : round($importeBrutoNumero - $descuentoImporteNumero, 2);
        $importeBruto = $importeBrutoNumero === null ? null : number_format($importeBrutoNumero, 2, '.', '');
        $descuentoImporte = number_format($descuentoImporteNumero, 2, '.', '');
        $importe = $importeNumero === null ? null : number_format($importeNumero, 2, '.', '');
        $subtotal += $importeNumero ?? 0.0;
        $partidas[] = compact('posicion', 'catalogoItemId', 'codigoCatalogo', 'tipoCatalogo', 'cantidad', 'unidad',
            'cantidadSecundaria', 'unidadSecundaria', 'descripcion', 'valorUnitario', 'importeBruto',
            'descuentoTipo', 'descuentoValor', 'descuentoImporte', 'importe');
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

    if ($empresaId !== null) {
        $validarEmpresa = $conexion->prepare('SELECT id_e, empresa FROM empresas WHERE id_e = ?');
        $validarEmpresa->bind_param('i', $empresaId);
        $validarEmpresa->execute();
        $empresaExiste = $validarEmpresa->get_result()->fetch_assoc();
        $validarEmpresa->close();
        if ($empresaExiste === null) {
            throw new InvalidArgumentException('La empresa seleccionada ya no existe.');
        }
        $empresaNombre = trim((string) $empresaExiste['empresa']);
    }

    if ($contactoId !== null) {
        $sqlContacto = 'SELECT c.id, c.nombre, c.celular, c.correo, COALESCE(cd.nombre, c.depto) AS departamento
            FROM contactos c LEFT JOIN catalogo_departamentos cd ON cd.id = c.id_departamento';
        if ($empresaId !== null) {
            $sqlContacto .= ' JOIN empresa_contactos ec ON ec.id_contacto = c.id
                WHERE c.id = ? AND ec.id_empresa = ? AND c.activo = 1 AND ec.activo = 1 LIMIT 1';
            $validarContacto = $conexion->prepare($sqlContacto);
            $validarContacto->bind_param('ii', $contactoId, $empresaId);
        } else {
            $sqlContacto .= ' WHERE c.id = ? AND c.activo = 1 LIMIT 1';
            $validarContacto = $conexion->prepare($sqlContacto);
            $validarContacto->bind_param('i', $contactoId);
        }
        $validarContacto->execute();
        $contactoExiste = $validarContacto->get_result()->fetch_assoc();
        $validarContacto->close();
        if ($contactoExiste === null) {
            throw new InvalidArgumentException($empresaId !== null
                ? 'El contacto no pertenece a la empresa seleccionada.'
                : 'El contacto seleccionado ya no existe.');
        }
        $contactoNombre = trim((string) $contactoExiste['nombre']);
        $telefono = trim((string) ($contactoExiste['celular'] ?? ''));
        $correo = trim((string) ($contactoExiste['correo'] ?? ''));
        $departamento = trim((string) ($contactoExiste['departamento'] ?? ''));
    }

    if ($direccionId !== null) {
        if ($empresaId === null) {
            throw new InvalidArgumentException('Selecciona una empresa antes de indicar la dirección.');
        }
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
        if ($empresaId === null) {
            throw new InvalidArgumentException('La oportunidad debe corresponder a una empresa seleccionada.');
        }
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
    if ($empresaNombre === '' && $contactoNombre === '') {
        throw new InvalidArgumentException('Para guardar el borrador indica al menos una empresa o un contacto.');
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
    $actual = null;
    if ($cotizacionEditarId !== null) {
        $consultarActual = $conexion->prepare(
            'SELECT c.cot_numero, c.version,
                    (SELECT oc.oportunidad_id FROM oportunidad_cotizaciones oc
                     WHERE oc.cotizacion_id = c.id_coti LIMIT 1) AS oportunidad_id,
                    EXISTS(SELECT 1 FROM ordenes_venta ov WHERE ov.cotizacion_id = c.id_coti) AS tiene_orden
             FROM cotizaciones c WHERE c.id_coti = ? FOR UPDATE'
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
        $oportunidadAnterior = $actual['oportunidad_id'] === null ? null : (int) $actual['oportunidad_id'];
        if ((int) $actual['tiene_orden'] === 1 && $oportunidadAnterior !== $oportunidadId) {
            throw new InvalidArgumentException('La oportunidad no puede cambiarse porque la cotización ya tiene una orden de venta.');
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
        $fechaFolio = $fecha ?? (new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City')))->format('Y-m-d');
        $numero = reservarFolioComercial($conexion, 'Q', $fechaFolio);
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
            (cotizacion_id, posicion, catalogo_item_id, codigo_catalogo, tipo_catalogo,
             cantidad, unidad, cantidad_secundaria, unidad_secundaria, descripcion,
             valor_unitario, importe_bruto, descuento_tipo, descuento_valor, descuento_importe, importe)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($partidas as $partida) {
        $insertarPartida->bind_param(
            'iiisssssssssssss',
            $cotizacionId,
            $partida['posicion'],
            $partida['catalogoItemId'],
            $partida['codigoCatalogo'],
            $partida['tipoCatalogo'],
            $partida['cantidad'],
            $partida['unidad'],
            $partida['cantidadSecundaria'],
            $partida['unidadSecundaria'],
            $partida['descripcion'],
            $partida['valorUnitario'],
            $partida['importeBruto'],
            $partida['descuentoTipo'],
            $partida['descuentoValor'],
            $partida['descuentoImporte'],
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

    if ($cotizacionEditarId !== null && (int) ($actual['tiene_orden'] ?? 0) === 0) {
        $desvincular = $conexion->prepare('DELETE FROM oportunidad_cotizaciones WHERE cotizacion_id = ?');
        $desvincular->bind_param('i', $cotizacionId);
        $desvincular->execute();
        $desvincular->close();
    }
    if ($oportunidadId !== null
        && ($cotizacionEditarId === null || (int) ($actual['tiene_orden'] ?? 0) === 0)) {
        $vincular = $conexion->prepare(
            'INSERT INTO oportunidad_cotizaciones (oportunidad_id, cotizacion_id) VALUES (?, ?)'
        );
        $vincular->bind_param('ii', $oportunidadId, $cotizacionId);
        $vincular->execute();
        $vincular->close();
    }

    // Recalcula tanto la oportunidad anterior como la vigente cuando cambia la relación comercial.
    $oportunidadesSincronizar = array_values(array_unique(array_filter([
        $cotizacionEditarId !== null ? ($oportunidadAnterior ?? null) : null,
        $oportunidadId,
    ], static fn($id): bool => $id !== null)));
    foreach ($oportunidadesSincronizar as $oportunidadSincronizarId) {
        sincronizarEstatusOportunidadCotizaciones($conexion, (int) $oportunidadSincronizarId);
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
        'version' => $cotizacionEditarId !== null ? (int) $actual['version'] + 1 : 1,
        'message' => $cotizacionEditarId !== null
            ? 'El borrador de la cotización se actualizó correctamente.'
            : 'El borrador de la cotización se guardó correctamente.',
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
