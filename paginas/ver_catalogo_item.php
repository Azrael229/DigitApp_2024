<?php
require __DIR__ . '/../backend/catalogo/common.php';
$authUser = auth_require_permission('productos');
$isCatalogAdmin = ($authUser['rol'] ?? '') === 'administrador';
$catalogId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$catalogId) {
    http_response_code(404);
    exit('El registro solicitado no existe.');
}
require __DIR__ . '/../config/conexion.php';
$conexion->set_charset('utf8mb4');
$item = catalogo_item($conexion, (int) $catalogId);
$conexion->close();
if (!$item || $item['tipo'] !== $catalogType) {
    http_response_code(404);
    exit('El registro solicitado no existe.');
}
$money = static fn($value): string => '$' . number_format((float) ($value ?? 0), 2);
$discount = $item['descuento_costo_tipo'] === 'porcentaje'
    ? number_format((float) $item['descuento_costo'], 2) . '%'
    : $money($item['descuento_costo']);
$utility = $item['utilidad_tipo'] === 'porcentaje'
    ? number_format((float) $item['utilidad_valor'], 2) . '%'
    : $money($item['utilidad_valor']);
$thirdPartyReal = null;
if ($catalogType === 'servicio' && (int) $item['es_tercero']) {
    $thirdPartyReal = $item['descuento_costo_tipo'] === 'porcentaje'
        ? (float) $item['costo_tercero'] * (1 - (float) $item['descuento_costo'] / 100)
        : (float) $item['costo_tercero'] - (float) $item['descuento_costo'];
}
$prefijoRuta = '../';
require __DIR__ . '/../construct/header.php';
?>
<div class="container mt-5 mb-5 contain shadow-lg empresa-detail-page">
    <div class="row align-items-center empresa-detalle-header g-3">
        <div class="col"><p class="empresa-header-kicker mb-1">Catálogo comercial</p><h1 class="h2 mb-1">Detalle de <?= catalogo_escape($catalogTitle) ?></h1><p class="empresa-header-subtitle mb-0"><?= catalogo_escape($item['codigo']) ?></p></div>
        <div class="col-auto d-flex gap-2"><?php if ($isCatalogAdmin): ?><a class="btn btn-success" href="<?= catalogo_escape($catalogForm) ?>?id=<?= (int) $item['id'] ?>">Editar</a><?php endif; ?><a class="btn btn-secondary" href="<?= catalogo_escape($catalogReturn) ?>">Volver</a></div>
    </div>
    <section class="card mb-4 empresa-form-card empresa-detail-section"><div class="card-body empresa-card-body p-4"><div class="mb-4"><p class="empresa-section-kicker mb-1">Identificación</p><h2 class="h4 mb-1">Datos generales</h2></div><div class="row g-3">
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Código</div><div class="empresa-data-value"><?= catalogo_escape($item['codigo']) ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Categoría</div><div class="empresa-data-value"><?= catalogo_escape($item['categoria']) ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Unidad</div><div class="empresa-data-value"><?= catalogo_escape($item['unidad']) ?></div></div>
        <div class="col-12 empresa-data-field"><div class="empresa-data-label">Descripción</div><div class="empresa-data-value"><?= nl2br(catalogo_escape($item['descripcion'])) ?></div></div>
        <div class="col-md-8 empresa-data-field"><div class="empresa-data-label">Proveedor directo</div><div class="empresa-data-value"><?= catalogo_escape($item['proveedor'] ?: 'Sin proveedor relacionado') ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Estatus</div><div class="empresa-data-value"><span class="badge <?= (int) $item['activo'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $item['activo'] ? 'Activo' : 'Inactivo' ?></span></div></div>
    </div></div></section>
    <section class="card mb-4 empresa-form-card empresa-detail-section"><div class="card-body empresa-card-body p-4"><div class="mb-4"><p class="empresa-section-kicker mb-1">Precios</p><h2 class="h4 mb-1">Información comercial</h2></div><div class="row g-3">
        <?php if ($catalogType === 'producto'): ?>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Moneda del proveedor</div><div class="empresa-data-value"><?= catalogo_escape($item['moneda_costo']) ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Tipo de cambio</div><div class="empresa-data-value"><?= $item['tipo_cambio'] === null ? 'No aplica' : number_format((float) $item['tipo_cambio'], 6) ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Costo del proveedor</div><div class="empresa-data-value"><?= $item['moneda_costo'] === 'USD' ? 'US$' : '$' ?><?= number_format((float) $item['costo_base'], 2) ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Descuento del proveedor</div><div class="empresa-data-value"><?= catalogo_escape($discount) ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Costo de envío</div><div class="empresa-data-value"><?= $money($item['costo_envio']) ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Costo real en MXN</div><div class="empresa-data-value"><?= $money($item['costo_real']) ?></div></div>
        <?php else: ?>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Origen</div><div class="empresa-data-value"><?= (int) $item['es_tercero'] ? 'Servicio de un tercero' : 'Servicio propio' ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Costo del tercero</div><div class="empresa-data-value"><?= $item['costo_tercero'] === null ? 'No aplica' : $money($item['costo_tercero']) ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Descuento del tercero</div><div class="empresa-data-value"><?= (int) $item['es_tercero'] ? catalogo_escape($discount) : 'No aplica' ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Costo real del tercero</div><div class="empresa-data-value"><?= $thirdPartyReal === null ? 'No aplica' : $money(max(0, $thirdPartyReal)) ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Costo de operación global</div><div class="empresa-data-value"><?= $money($item['costo_operacion_total']) ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Costo global del servicio</div><div class="empresa-data-value"><?= $money($item['costo_real']) ?></div></div>
        <?php endif; ?>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Utilidad</div><div class="empresa-data-value"><?= catalogo_escape($utility) ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label"><?= $catalogType === 'producto' ? 'Precio calculado' : 'Precio base calculado' ?></div><div class="empresa-data-value"><?= $money($item['precio_calculado']) ?></div></div>
        <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Precio al público final</div><div class="empresa-data-value"><?= $money($item['precio_publico']) ?></div></div>
        <div class="col-12 empresa-data-field"><div class="empresa-data-label">Última actualización del precio</div><div class="empresa-data-value"><?= $item['precio_actualizado_at'] ? catalogo_escape((new DateTimeImmutable($item['precio_actualizado_at']))->format('d/m/Y H:i')) : 'Sin registro' ?></div></div>
    </div></div></section>
    <?php if ($catalogType === 'servicio'): ?><section class="card mb-4 empresa-form-card empresa-detail-section"><div class="card-body empresa-card-body p-4"><div class="mb-4"><p class="empresa-section-kicker mb-1">Operación</p><h2 class="h4 mb-1">Costos incluidos</h2></div><?php if ($item['costos_operacion']): ?><div class="table-responsive data-table-shell"><table class="table table-secondary table-striped align-middle mb-0"><thead><tr><th>Concepto</th><th>Importe</th></tr></thead><tbody><?php foreach ($item['costos_operacion'] as $cost): ?><tr><td><?= catalogo_escape($cost['concepto']) ?></td><td><?= $money($cost['importe']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="alert alert-info mb-0">Este servicio no tiene costos de operación desglosados.</div><?php endif; ?></div></section><?php endif; ?>
</div>
<?php require __DIR__ . '/../construct/footer.html'; ?>
