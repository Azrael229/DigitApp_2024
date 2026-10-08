<?php
declare(strict_types=1);

$prefijoRuta = '../';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    http_response_code(422);
    exit('La cotización no es válida.');
}

require __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../backend/cotizaciones/common.php';
$conexion->set_charset('utf8mb4');
$cotizacion = cotizacionDetalle($conexion, (int) $id);
$conexion->close();
if ($cotizacion === null) {
    http_response_code(404);
    exit('La cotización no existe.');
}

function cotEscapar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function cotFecha($fecha): string
{
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $fecha);
    return $objeto === false ? (string) $fecha : $objeto->format('d/m/Y');
}

function cotFechaHora($fecha): string
{
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $fecha);
    return $objeto === false ? (string) $fecha : $objeto->format('d/m/Y H:i');
}

function cotMoneda($valor): string
{
    return '$ ' . number_format((float) $valor, 2, '.', ' ');
}

function cotEntrega(array $cotizacion): string
{
    $tipo = (string) ($cotizacion['cot_tiempo_entrega_tipo'] ?? '');
    $cantidad = (int) ($cotizacion['cot_tiempo_entrega_cantidad'] ?? 0);
    if ($tipo === 'inmediato') {
        return 'Inmediato';
    }
    if ($tipo === 'semanas') {
        return $cantidad . ($cantidad === 1 ? ' semana' : ' semanas');
    }
    if ($tipo === 'dias_habiles') {
        return $cantidad . ($cantidad === 1 ? ' día hábil' : ' días hábiles');
    }
    return 'No especificado';
}

function cotCondicionEntrega($tipo): string
{
    return match ((string) $tipo) {
        'instalaciones_cliente' => 'Instalaciones del cliente',
        'paqueteria' => 'Paquetería',
        'instalaciones_servicom' => 'Instalaciones de SERVICOM Básculas Digitales',
        default => 'No especificada',
    };
}

function cotCondicionPago(array $cotizacion): string
{
    $tipo = (string) ($cotizacion['cot_condicion_pago_tipo'] ?? '');
    if ($tipo === 'anticipado') {
        return 'Pago anticipado';
    }
    if ($tipo === 'anticipado_total') {
        return 'Pago 100% anticipado';
    }
    if ($tipo === 'anticipo_saldo') {
        $anticipo = (float) ($cotizacion['cot_pago_anticipo_porcentaje'] ?? 0);
        $saldo = 100 - $anticipo;
        $formato = static fn(float $valor): string => rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
        $momento = ($cotizacion['cot_pago_saldo_momento'] ?? '') === 'contra_aviso_entrega'
            ? 'contra aviso de entrega'
            : 'al finalizar';
        return $formato($anticipo) . '% de anticipo y ' . $formato($saldo) . '% ' . $momento;
    }
    if ($tipo === 'credito') {
        $dias = (int) ($cotizacion['cot_pago_credito_dias'] ?? 0);
        return $dias . ($dias === 1 ? ' día de crédito' : ' días de crédito');
    }
    return 'No especificada';
}

function cotGarantia(array $cotizacion): string
{
    $tipo = (string) ($cotizacion['cot_garantia_tipo'] ?? '');
    if ($tipo === 'sin_garantia') {
        return 'Sin garantía especificada';
    }
    if (!in_array($tipo, ['producto', 'mano_obra'], true)) {
        return 'No especificada';
    }
    $cantidad = (int) ($cotizacion['cot_garantia_vigencia'] ?? 0);
    $unidad = ($cotizacion['cot_garantia_unidad'] ?? '') === 'meses'
        ? ($cantidad === 1 ? 'mes' : 'meses')
        : ($cantidad === 1 ? 'día' : 'días');
    $alcance = $tipo === 'producto' ? 'Producto por defectos de fabricación' : 'Mano de obra';
    return $alcance . ': ' . $cantidad . ' ' . $unidad;
}

function cotCostosEnvio($tipo): string
{
    return match ((string) $tipo) {
        'incluye' => 'Incluye costos de envío',
        'no_incluye' => 'No incluye costos de envío',
        default => 'No especificado',
    };
}

require __DIR__ . '/../construct/header.php';
?>

<div class="container mt-5 mb-5 contain shadow-lg empresa-detalle cotizacion-detail-page detail-page"
     data-cotizacion-id="<?= (int) $cotizacion['id_coti'] ?>"
     data-oportunidad-id="<?= (int) ($cotizacion['oportunidad_id'] ?? 0) ?>"
     data-cotizacion-status-csrf="<?= cotEscapar($_SESSION['cotizacion_status_csrf']) ?>">
    <div class="row align-items-center pt-3 pb-4 mb-4 empresa-detalle-header">
        <div class="col empresa-header-copy">
            <p class="empresa-header-kicker mb-1">Gestión comercial</p>
            <h1 class="h2 mb-1 d-flex flex-wrap align-items-baseline gap-2">
                <span>Cotización <?= cotEscapar($cotizacion['cot_numero'] ?: '#' . $cotizacion['id_coti']) ?></span>
                <span class="badge cot-status cot-title-status cot-status-<?= cotEscapar(cotizacionEstatusClave($cotizacion['cot_status'] ?? '')) ?>"><?= cotEscapar(cotizacionEstatusEtiqueta($cotizacion['cot_status'] ?? '')) ?></span>
            </h1>
            <p class="empresa-header-subtitle mb-0">Consulta el documento registrado, sus partidas y condiciones comerciales</p>
        </div>
        <div class="col-12 col-md-auto mt-3 mt-md-0 d-flex flex-column flex-sm-row gap-2 empresa-header-actions">
            <a href="nuevaCotizacion.php?id=<?= (int) $cotizacion['id_coti'] ?>" class="btn btn-secondary text-nowrap"><i class="bi bi-pencil" aria-hidden="true"></i> Editar cotización</a>
            <form action="../fpdf/cotizacionPDF.php" method="post" target="_blank" class="m-0">
                <input type="hidden" name="cotizacion_id" value="<?= (int) $cotizacion['id_coti'] ?>">
                <button type="submit" class="btn btn-secondary w-100 text-nowrap"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir o guardar PDF</button>
            </form>
            <a href="tablaCotizaciones.php" class="btn btn-secondary text-nowrap"><i class="bi bi-arrow-left" aria-hidden="true"></i> Cotizaciones</a>
        </div>
    </div>

    <section class="card mb-4 empresa-form-card empresa-detail-section cotizacion-sale-panel">
      <div class="card-body empresa-card-body">
        <div class="empresa-section-heading mb-3">
            <p class="empresa-section-kicker mb-1">Formalización comercial</p>
            <h2 class="h5 card-title mb-0">Estatus y orden de venta</h2>
        </div>
        <div id="cot-status-message" class="alert d-none" role="status" aria-live="polite"></div>
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg">
                <label class="form-label" for="cot-detail-status">Estatus de la cotización</label>
                <select id="cot-detail-status" class="form-select"<?= !empty($cotizacion['orden_venta_id']) ? ' disabled' : '' ?>>
                    <?php foreach (COTIZACION_ESTATUS as $clave => $etiqueta): ?>
                        <option value="<?= cotEscapar($clave) ?>"<?= cotizacionEstatusClave($cotizacion['cot_status'] ?? '') === $clave ? ' selected' : '' ?>><?= cotEscapar($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-auto d-grid">
                <button id="cot-save-status" class="btn btn-secondary" type="button"<?= !empty($cotizacion['orden_venta_id']) ? ' disabled' : '' ?>><i class="bi bi-check2-circle" aria-hidden="true"></i> Guardar estatus</button>
            </div>
            <div class="col-12 col-lg-auto d-grid">
                <?php if (!empty($cotizacion['orden_venta_id'])): ?>
                    <a class="btn btn-success" href="ver_orden_venta.php?id=<?= (int) $cotizacion['orden_venta_id'] ?>"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Ver <?= cotEscapar($cotizacion['numero_venta']) ?></a>
                <?php else: ?>
                    <?php $puedeCrearOrden = !empty($cotizacion['oportunidad_id']) && cotizacionEstatusClave($cotizacion['cot_status'] ?? '') === 'aceptada'; ?>
                    <a id="cot-create-sale" class="btn btn-success<?= $puedeCrearOrden ? '' : ' disabled' ?>"
                       href="form_orden_venta.php?oportunidad_id=<?= (int) $cotizacion['oportunidad_id'] ?>&amp;cotizacion_id=<?= (int) $cotizacion['id_coti'] ?>"
                       aria-disabled="<?= $puedeCrearOrden ? 'false' : 'true' ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva orden de venta</a>
                <?php endif; ?>
            </div>
        </div>
        <p id="cot-sale-help" class="empresa-notes-help mt-3 mb-0">
            <?php if (!empty($cotizacion['orden_venta_id'])): ?>
                Esta cotización ya está vinculada con una orden de venta.
            <?php elseif (empty($cotizacion['oportunidad_id'])): ?>
                Vincula primero la cotización con una oportunidad comercial.
            <?php elseif (cotizacionEstatusClave($cotizacion['cot_status'] ?? '') !== 'aceptada'): ?>
                Cambia el estatus a Aceptada para generar la orden de venta. La oportunidad relacionada quedará marcada como Ganada.
            <?php else: ?>
                La cotización está lista para generar una orden de venta con sus datos precargados.
            <?php endif; ?>
        </p>
      </div>
    </section>

    <section class="card mb-4 empresa-form-card empresa-detail-section">
      <div class="card-body empresa-card-body">
        <div class="empresa-section-heading mb-3">
            <p class="empresa-section-kicker mb-1">Empresa de la cotización</p>
            <?php if (!empty($cotizacion['empresa_id'])): ?>
                <a class="cotizacion-company-title entity-link" href="ver_empresa.php?id=<?= (int) $cotizacion['empresa_id'] ?>"><?= cotEscapar($cotizacion['cot_empresa'] ?: 'Empresa sin nombre') ?></a>
            <?php else: ?>
                <h2 class="cotizacion-company-title mb-0"><?= cotEscapar($cotizacion['cot_empresa'] ?: 'Empresa sin nombre') ?></h2>
            <?php endif; ?>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-12 empresa-data-field"><div class="empresa-data-label">Dirección</div><div class="empresa-data-value cotizacion-detail-multiline"><?= cotEscapar($cotizacion['cot_direccion'] ?: 'Sin dirección') ?></div></div>
            <div class="col-md-6 col-xl-3 empresa-data-field">
                <div class="empresa-data-label">Contacto</div>
                <?php if (!empty($cotizacion['contacto_id'])): ?>
                    <a class="empresa-data-value entity-link" href="ver_contacto.php?id=<?= (int) $cotizacion['contacto_id'] ?>"><?= cotEscapar($cotizacion['cot_contacto'] ?: 'Sin contacto') ?></a>
                <?php else: ?>
                    <div class="empresa-data-value"><?= cotEscapar($cotizacion['cot_contacto'] ?: 'Sin contacto') ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 col-xl-3 empresa-data-field"><div class="empresa-data-label">Teléfono</div><div class="empresa-data-value"><?= cotEscapar($cotizacion['cot_telefono'] ?: 'Sin teléfono') ?></div></div>
            <div class="col-md-6 col-xl-3 empresa-data-field"><div class="empresa-data-label">Correo electrónico</div><div class="empresa-data-value"><?= cotEscapar($cotizacion['cot_correo'] ?: 'Sin correo') ?></div></div>
            <div class="col-md-6 col-xl-3 empresa-data-field"><div class="empresa-data-label">Departamento</div><div class="empresa-data-value"><?= cotEscapar($cotizacion['cot_departamento'] ?: 'Sin departamento') ?></div></div>
        </div>
      </div>
    </section>

    <section class="card mb-4 empresa-form-card empresa-detail-section">
      <div class="card-body empresa-card-body">
        <div class="empresa-section-heading mb-3"><p class="empresa-section-kicker mb-1">Condiciones comerciales</p><h2 class="h5 card-title mb-0">Condiciones de la cotización</h2></div>
        <div class="row g-3 mt-1">
            <div class="col-md-6 col-xl-3 empresa-data-field"><div class="empresa-data-label">Fecha</div><div class="empresa-data-value"><?= cotEscapar(cotFecha($cotizacion['cot_fecha'])) ?></div></div>
            <div class="col-md-6 col-xl-3 empresa-data-field"><div class="empresa-data-label">Vigencia</div><div class="empresa-data-value"><?= cotEscapar(cotFecha($cotizacion['cot_vigencia'])) ?></div></div>
            <div class="col-md-6 col-xl-3 empresa-data-field"><div class="empresa-data-label">Tiempo de entrega</div><div class="empresa-data-value"><?= cotEscapar(cotEntrega($cotizacion)) ?></div></div>
            <div class="col-md-6 col-xl-3 empresa-data-field"><div class="empresa-data-label">Moneda</div><div class="empresa-data-value"><?= cotEscapar($cotizacion['cot_moneda'] ?: 'MXN') ?></div></div>
            <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Condición de entrega</div><div class="empresa-data-value"><?= cotEscapar(cotCondicionEntrega($cotizacion['cot_condicion_entrega'] ?? null)) ?></div></div>
            <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Condición de pago</div><div class="empresa-data-value"><?= cotEscapar(cotCondicionPago($cotizacion)) ?></div></div>
            <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Garantía</div><div class="empresa-data-value"><?= cotEscapar(cotGarantia($cotizacion)) ?></div></div>
            <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Costos de envío</div><div class="empresa-data-value"><?= cotEscapar(cotCostosEnvio($cotizacion['cot_costos_envio'] ?? null)) ?></div></div>
            <div class="col-md-6 empresa-data-field">
                <div class="empresa-data-label">Oportunidad comercial</div>
                <?php if (!empty($cotizacion['oportunidad_id'])): ?>
                    <a class="empresa-data-value entity-link" href="ver_oportunidad.php?id=<?= (int) $cotizacion['oportunidad_id'] ?>"><?= cotEscapar($cotizacion['numero_oportunidad'] ?: '#' . $cotizacion['oportunidad_id']) ?></a>
                <?php else: ?>
                    <div class="empresa-data-value">Sin oportunidad relacionada</div>
                <?php endif; ?>
            </div>
            <div class="col-12 empresa-data-field">
                <div class="empresa-data-label">Nota adicional</div>
                <?php if ($cotizacion['notas']): ?>
                    <ul class="cotizacion-detail-notes mb-0">
                        <?php foreach ($cotizacion['notas'] as $nota): ?><li><?= cotEscapar($nota['texto']) ?></li><?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empresa-data-value">Sin nota adicional</div>
                <?php endif; ?>
            </div>
        </div>
      </div>
    </section>

    <section class="card mb-4 empresa-form-card empresa-detail-section">
      <div class="card-body empresa-card-body">
        <div class="empresa-section-heading mb-3"><p class="empresa-section-kicker mb-1">Conceptos cotizados</p><h2 class="h5 card-title mb-1">Partidas</h2><p class="empresa-notes-help mb-0"><?= count($cotizacion['partidas']) ?> concepto<?= count($cotizacion['partidas']) === 1 ? '' : 's' ?> registrado<?= count($cotizacion['partidas']) === 1 ? '' : 's' ?>.</p></div>
        <div class="table-responsive data-table-shell empresa-table-wrap">
            <table id="tabla-partidas-cotizacion" class="table table-secondary table-striped align-middle mb-0 w-100">
                <thead><tr><th>Posición</th><th>Cantidad</th><th>Unidad</th><th>Descripción</th><th class="text-end">Valor unitario</th><th class="text-end">Importe</th></tr></thead>
                <tbody>
                    <?php foreach ($cotizacion['partidas'] as $partida): ?>
                        <tr>
                            <td><?= (int) $partida['posicion'] ?></td>
                            <td><?= cotEscapar(rtrim(rtrim(number_format((float) $partida['cantidad'], 3, '.', ''), '0'), '.')) ?></td>
                            <td><?= cotEscapar($partida['unidad']) ?></td>
                            <td class="cotizacion-detail-description"><?= nl2br(cotEscapar($partida['descripcion'])) ?></td>
                            <td class="text-end text-nowrap"><?= cotEscapar(cotMoneda($partida['valor_unitario'])) ?></td>
                            <td class="text-end text-nowrap fw-semibold"><?= cotEscapar(cotMoneda($partida['importe'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="row justify-content-end mt-4">
            <div class="col-12 col-md-6 col-xl-4 cotizacion-detail-totals">
                <div><span>Subtotal</span><strong><?= cotEscapar(cotMoneda($cotizacion['cot_subtotal'])) ?></strong></div>
                <div><span>IVA 16%</span><strong><?= cotEscapar(cotMoneda($cotizacion['cot_iva'])) ?></strong></div>
                <div class="cotizacion-detail-total"><span>Total</span><strong><?= cotEscapar(cotMoneda($cotizacion['cot_total'])) ?> <?= cotEscapar($cotizacion['cot_moneda']) ?></strong></div>
            </div>
        </div>
      </div>
    </section>

    <section class="card mb-4 empresa-form-card empresa-detail-section">
      <div class="card-body empresa-card-body">
        <div class="empresa-section-heading mb-3"><p class="empresa-section-kicker mb-1">Seguimiento</p><h2 class="h5 card-title mb-0">Control del registro</h2></div>
        <div class="row g-3 mt-1">
            <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Fecha de creación</div><div class="empresa-data-value"><?= cotEscapar(cotFechaHora($cotizacion['created_at'] ?? '')) ?></div></div>
            <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Última actualización</div><div class="empresa-data-value"><?= cotEscapar(cotFechaHora($cotizacion['updated_at'] ?? '')) ?></div></div>
            <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Formato</div><div class="empresa-data-value"><?= cotEscapar($cotizacion['cot_formato_version'] ?: 'Sin versión') ?></div></div>
            <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Términos y condiciones</div><div class="empresa-data-value"><?= cotEscapar($cotizacion['cot_terminos_version'] ?: 'Versión vigente') ?></div></div>
        </div>
      </div>
    </section>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="../js/datatable-filters.js?v=20261008-2"></script>
<script src="../js/datatable-config.js"></script>
<script src="../js/ver_cotizacion.js?v=20261004-2"></script>

<?php require __DIR__ . '/../construct/footer.html'; ?>
