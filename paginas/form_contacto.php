<?php
$prefijoRuta = '../';
$idContacto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$idInvalido = isset($_GET['id']) && ($idContacto === false || $idContacto < 1);
$empresaParametroId = filter_input(INPUT_GET, 'empresa_id', FILTER_VALIDATE_INT);
$origenEmpresa = ($_GET['from'] ?? '') === 'empresa';
$empresaContextoId = $idContacto ? null : $empresaParametroId;
$empresaRetornoId = $origenEmpresa ? $empresaParametroId : null;
$empresaContextoInvalida = ($empresaContextoId !== null || $empresaRetornoId !== null)
    && ($empresaParametroId === false || $empresaParametroId < 1);

if ($idContacto === false || $idContacto < 1) {
    $idContacto = null;
}

if ($empresaParametroId !== false && $empresaParametroId !== null && $empresaParametroId > 0
    && ($empresaContextoId !== null || $empresaRetornoId !== null)) {
    require __DIR__ . '/../config/conexion.php';
    $consultaEmpresaContexto = $conexion->prepare('SELECT id_e FROM empresas WHERE id_e = ? LIMIT 1');
    $consultaEmpresaContexto->bind_param('i', $empresaParametroId);
    $consultaEmpresaContexto->execute();
    $empresaContextoValida = $consultaEmpresaContexto->get_result()->fetch_assoc() !== null;
    $consultaEmpresaContexto->close();
    mysqli_close($conexion);
    $empresaContextoInvalida = !$empresaContextoValida;
} else {
    $empresaContextoId = null;
    $empresaRetornoId = null;
}

require __DIR__ . '/../backend/empresas/query_all_empresas.php';
require __DIR__ . '/../construct/header.php';
?>

<div class="container mt-5 mb-5 contain shadow-lg contacto-form-page" data-contact-id="<?= $idContacto ? (int) $idContacto : '' ?>" data-context-company-id="<?= !$empresaContextoInvalida && $empresaContextoId ? (int) $empresaContextoId : '' ?>" data-return-company-id="<?= !$empresaContextoInvalida && $empresaRetornoId ? (int) $empresaRetornoId : '' ?>">
    <div class="row align-items-center contacto-form-header">
        <div class="col">
            <p class="contactos-kicker mb-1">Directorio de contactos</p>
            <h1 id="titulo_form_contacto" class="h2 mb-0"><?= $idContacto && $idContacto > 0 ? 'Editar contacto' : 'Añadir contacto' ?></h1>
        </div>
    </div>

    <?php if ($idInvalido): ?>
        <div class="alert alert-danger mt-4" role="alert">El identificador del contacto no es válido.</div>
    <?php endif; ?>
    <?php if ($empresaContextoInvalida): ?>
        <div class="alert alert-warning mt-4" role="alert">La empresa de origen no pudo cargarse. Puedes seleccionar una empresa manualmente.</div>
    <?php endif; ?>
    <div id="mensaje_form_contacto" class="alert d-none mt-4" role="status" aria-live="polite"></div>

    <form id="form_contacto" action="<?= $prefijoRuta ?>backend/contactos/add_contacto.php" method="POST" novalidate>
        <input type="hidden" id="contacto_id" name="contacto_id" value="<?= $idContacto && $idContacto > 0 ? (int) $idContacto : '' ?>">
        <input type="hidden" name="empresas_presentes" value="1">
        <?php if (!$empresaContextoInvalida && $empresaContextoId): ?>
            <input type="hidden" name="empresa_contexto" value="<?= (int) $empresaContextoId ?>">
        <?php endif; ?>
        <?php if (!$empresaContextoInvalida && $empresaRetornoId): ?>
            <input type="hidden" name="return_empresa_id" value="<?= (int) $empresaRetornoId ?>">
        <?php endif; ?>

        <div class="d-flex flex-column gap-4 mt-4">
            <section class="card border-0 shadow-sm contacto-form-card">
                <div class="card-body p-4 contacto-form-card-body">
                    <div class="mb-4">
                        <h2 class="h4 mb-1">Datos generales</h2>
                        <p class="contactos-muted mb-0">Información principal de la persona.</p>
                        <p class="contactos-required-note mb-0"><span class="required-mark" aria-hidden="true">*</span> Campo obligatorio</p>
                    </div>
                    <div class="row g-4">
                        <div class="col-12">
                            <label for="contacto_nombre" class="form-label fw-semibold">Nombre <span class="required-mark" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control" id="contacto_nombre" name="contacto_nombre" maxlength="50" required autocomplete="name">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="contacto_cel" class="form-label fw-semibold">Teléfono <span class="required-mark" aria-hidden="true">*</span></label>
                            <input type="tel" class="form-control" id="contacto_cel" name="contacto_cel" maxlength="20" required autocomplete="tel">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="contacto_email" class="form-label fw-semibold">Correo</label>
                            <input type="email" class="form-control" id="contacto_email" name="contacto_email" maxlength="50" autocomplete="email">
                        </div>
                    </div>
                </div>
            </section>

            <section class="card border-0 shadow-sm contacto-form-card">
                <div class="card-body p-4 contacto-form-card-body">
                    <div class="mb-4">
                        <h2 class="h4 mb-1">Información laboral</h2>
                        <p class="contactos-muted mb-0">Departamento y puesto actuales.</p>
                    </div>
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <label for="id_departamento" class="form-label fw-semibold">Departamento</label>
                            <select class="form-select" id="id_departamento" name="id_departamento">
                                <option value="">Sin departamento asignado</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="puesto" class="form-label fw-semibold">Puesto</label>
                            <input type="text" class="form-control" id="puesto" name="puesto" maxlength="100" autocomplete="organization-title">
                        </div>
                    </div>
                </div>
            </section>

            <section class="card border-0 shadow-sm contacto-form-card">
                <div class="card-body p-4 contacto-form-card-body">
                    <div class="mb-4">
                        <h2 class="h4 mb-1">Empresas asociadas</h2>
                        <p class="contactos-muted mb-0">La asociación con empresas es opcional.</p>
                    </div>
                    <div class="row g-4">
                        <div class="col-12 contacto-empresas-select">
                            <label for="empresas" class="form-label fw-semibold">Empresas</label>
                            <select class="form-select" id="empresas" name="empresas[]" multiple>
                                <?php foreach ($result_empresas as $empresa): ?>
                                    <option value="<?= (int) $empresa['id_e'] ?>"><?= htmlspecialchars((string) $empresa['empresa'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="empresa_principal" class="form-label fw-semibold">Empresa principal</label>
                            <select class="form-select" id="empresa_principal" name="empresa_principal" disabled>
                                <option value="">Sin empresa principal</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="activo" class="form-label fw-semibold">Estado</label>
                            <select class="form-select" id="activo" name="activo">
                                <option value="1" selected>Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="d-flex flex-column flex-sm-row justify-content-sm-end gap-2 mt-4 contacto-form-actions">
            <a id="btn_cancelar_contacto" href="contactos.php" class="btn btn-danger">Cancelar</a>
            <button id="btn_guardar_contacto" type="submit" class="btn btn-secondary">Guardar contacto</button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= $prefijoRuta ?>js/form_contacto.js"></script>

<?php require __DIR__ . '/../construct/footer.html'; ?>
