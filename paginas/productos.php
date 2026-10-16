<?php
require __DIR__ . '/../backend/catalogo/common.php';
$authUser = auth_require_permission('productos');
$isCatalogAdmin = ($authUser['rol'] ?? '') === 'administrador';
require __DIR__ . '/../config/conexion.php';
$conexion->set_charset('utf8mb4');
$items = catalogo_items($conexion, 'producto');
$conexion->close();
$prefijoRuta = '../';
require __DIR__ . '/../construct/header.php';
?>
<div class="container mt-5 mb-5 contain shadow-lg oportunidades-page">
    <div class="row align-items-center contacto-form-header g-3"><div class="col"><p class="contactos-kicker mb-1">Catálogo comercial</p><h1 class="h2 mb-0">Productos</h1></div><?php if ($isCatalogAdmin): ?><div class="col-auto"><a href="form_producto.php" class="btn btn-success">Agregar producto</a></div><?php endif; ?></div>
    <p class="contactos-muted mt-4">Directorio de productos disponibles para cotización. Las existencias físicas se administrarán posteriormente desde almacén.</p>
    <div class="factura-resumen mt-4 mb-4"><div class="factura-resumen-item"><span class="factura-resumen-label">Productos filtrados</span><strong id="catalog-records">0</strong></div></div>
    <div class="table-responsive data-table-shell table-wide mt-4 empresa-table-wrap"><table id="catalogo-table" class="table table-secondary table-striped align-middle mb-0 w-100 catalogo-directory-table catalogo-productos-table"><thead><tr><th class="table-date-column catalogo-col-date">Actualización<br>de precio</th><th class="catalogo-col-code">Código</th><th class="catalogo-col-category">Categoría</th><th class="catalogo-col-description">Descripción</th><th class="catalogo-col-money">Costo real<br>en MXN</th><th class="catalogo-col-money">Utilidad</th><th class="catalogo-col-money">Precio calculado</th><th class="catalogo-col-money">Precio al público<br>final</th><th class="catalogo-col-status">Estatus</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?><tr><td class="table-date-column catalogo-col-date" data-order="<?= catalogo_escape($item['precio_actualizado_at'] ?? '') ?>"><?= $item['precio_actualizado_at'] ? catalogo_escape((new DateTimeImmutable($item['precio_actualizado_at']))->format('Y-m-d')) : '—' ?></td><td class="catalogo-col-code"><a class="entity-link fw-semibold" href="ver_producto.php?id=<?= (int) $item['id'] ?>"><?= catalogo_escape($item['codigo']) ?></a></td><td class="catalogo-col-category"><?= catalogo_escape($item['categoria']) ?></td><td class="catalogo-col-description"><div class="table-text-two-lines"><?= catalogo_escape($item['descripcion']) ?></div></td><td class="catalogo-col-money" data-order="<?= catalogo_escape($item['costo_real'] ?? 0) ?>">$<?= number_format((float) ($item['costo_real'] ?? 0), 2) ?></td><td class="catalogo-col-money"><?= $item['utilidad_tipo'] === 'porcentaje' ? number_format((float) $item['utilidad_valor'], 2) . '%' : '$' . number_format((float) $item['utilidad_valor'], 2) ?></td><td class="catalogo-col-money">$<?= number_format((float) $item['precio_calculado'], 2) ?></td><td class="catalogo-col-money">$<?= number_format((float) $item['precio_publico'], 2) ?></td><td class="catalogo-col-status"><span class="badge <?= (int) $item['activo'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $item['activo'] ? 'Activo' : 'Inactivo' ?></span></td></tr><?php endforeach; ?>
    </tbody></table></div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script><script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="../js/datatable-filters.js?v=20261008-2"></script><script src="../js/datatable-config.js"></script><script src="../js/catalogo-list.js?v=20261015-4"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
