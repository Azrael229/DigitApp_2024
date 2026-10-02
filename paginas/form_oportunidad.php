<?php
require __DIR__ . '/../backend/oportunidades/common.php';
$prefijoRuta = '../';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$invalid = isset($_GET['id']) && !$id;
require __DIR__ . '/../construct/header.php';
?>
<div class="container mt-5 mb-5 contain shadow-lg oportunidades-page" data-csrf="<?= op_escape($_SESSION['oportunidades_csrf']) ?>" data-id="<?= $id ? (int) $id : '' ?>">
    <div class="contacto-form-header"><p class="contactos-kicker mb-1">Gestión comercial</p><h1 class="h2 mb-0"><?= $id ? 'Editar oportunidad' : 'Nueva oportunidad' ?></h1></div>
    <div id="mensaje_oportunidad" class="alert <?= $invalid ? 'alert-danger' : 'd-none' ?> mt-4" role="status" aria-live="polite"><?= $invalid ? 'El identificador de oportunidad no es válido.' : '' ?></div>
    <form id="form-oportunidad" class="mt-4" <?= $invalid ? 'data-invalid="1"' : '' ?>>
        <input type="hidden" name="id" value="<?= $id ? (int) $id : '' ?>">
        <input type="hidden" name="version" id="op-version" value="">
        <fieldset disabled id="op-fields">
            <section class="card border-0 shadow-sm contacto-form-card"><div class="card-body p-4 contacto-form-card-body">
                <h2 class="h4 mb-1">Datos de la oportunidad</h2><p class="contactos-required-note mb-4">* Campo obligatorio</p>
                <div class="row g-4">
                    <div class="col-md-6"><label for="op-fecha" class="form-label">Fecha *</label><input id="op-fecha" name="fecha" type="date" class="form-control" min="1000-01-01" max="9999-12-31" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-6"><label for="op-estatus" class="form-label">Estatus *</label><select id="op-estatus" name="estatus" class="form-select" required><?php foreach (OP_ESTATUS as $value => $label): ?><option value="<?= op_escape($value) ?>"><?= op_escape($label) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label for="op-empresa" class="form-label">Empresa *</label><select id="op-empresa" name="empresa_id" class="form-select" required><option value="">Seleccionar empresa</option></select></div>
                    <div class="col-md-6"><label for="op-contacto" class="form-label">Contacto</label><select id="op-contacto" name="contacto_id" class="form-select"><option value="">Sin contacto asignado</option></select></div>
                    <div class="col-md-6"><label for="op-direccion" class="form-label">Dirección</label><select id="op-direccion" name="direccion_id" class="form-select"><option value="">Sin dirección asignada</option></select></div>
                    <div class="col-12"><label for="op-corta" class="form-label">Descripción corta *</label><input id="op-corta" name="descripcion_corta" type="text" class="form-control" maxlength="150" placeholder="Ej. Calibración de silos" required></div>
                    <div class="col-12"><label for="op-larga" class="form-label">Descripción detallada</label><textarea id="op-larga" name="descripcion_larga" class="form-control" rows="6" maxlength="15000"></textarea></div>
                    <div class="col-md-6"><label for="op-importe" class="form-label">Importe propuesto sin IVA (MXN) *</label><input id="op-importe" name="importe" type="number" class="form-control" min="0" max="9999999999.99" step="0.01" value="0.00" required></div>
                </div>
            </div></section>
            <section class="card border-0 shadow-sm contacto-form-card mt-4"><div class="card-body p-4 contacto-form-card-body"><h2 class="h4">Control del registro</h2><div id="op-auditoria" class="contactos-muted">Las fechas de registro se asignarán al guardar.</div></div></section>
            <div class="d-flex justify-content-end gap-2 mt-4 contacto-form-actions"><a href="<?= $id ? 'ver_oportunidad.php?id=' . (int) $id : 'oportunidades.php' ?>" class="btn btn-danger">Cancelar</a><button id="op-guardar" type="submit" class="btn btn-secondary">Guardar oportunidad</button></div>
        </fieldset>
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../js/oportunidades-common.js"></script>
<script src="../js/form_oportunidad.js"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
