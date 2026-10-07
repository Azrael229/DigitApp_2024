<?php
require_once __DIR__ . '/../backend/auth/bootstrap.php';
$prefijoRuta = '../';
$returnUrl = (string) ($_GET['return_url'] ?? '');
if ($returnUrl !== '' && !preg_match('/^informe\.php(?:\?.*)?$/', $returnUrl)) {
    $returnUrl = '';
}
if (empty($_SESSION['empresa_notas_csrf'])) {
    $_SESSION['empresa_notas_csrf'] = bin2hex(random_bytes(32));
}
?>
<?php require (__DIR__ . "/../construct/header.php"); ?>

<div class="container mt-5 mb-5 contain shadow-lg empresa-detalle detail-page" data-notas-csrf="<?= htmlspecialchars($_SESSION['empresa_notas_csrf'], ENT_QUOTES, 'UTF-8') ?>" data-return-url="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>">
    <div class="row align-items-center pt-3 pb-4 mb-4 empresa-detalle-header">
        <div class="col empresa-header-copy">
            <p class="empresa-header-kicker mb-1">Directorio de empresas</p>
            <h1 id="titulo_empresa" class="h2 mb-1">Empresa</h1>
            <p class="empresa-header-subtitle mb-0">Información general y registros asociados</p>
        </div>
        <div class="col-12 col-md-auto mt-3 mt-md-0 d-flex flex-column flex-sm-row gap-2 empresa-header-actions">
            <a id="btn_editar_empresa" href="#" class="btn btn-secondary disabled text-nowrap" aria-disabled="true"><i class="bi bi-pencil"></i> Editar datos generales</a>
            <a href="<?= htmlspecialchars($returnUrl !== '' ? $returnUrl : 'empresas.php', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary text-nowrap"><i class="bi bi-arrow-left"></i> <?= $returnUrl !== '' ? 'Volver al informe' : 'Directorio' ?></a>
        </div>
    </div>

    <?php if (isset($_GET['equipo_guardado'])): ?>
        <div class="alert alert-success" role="alert">Equipo registrado correctamente.</div>
    <?php elseif (isset($_GET['equipo_actualizado'])): ?>
        <div class="alert alert-success" role="alert">Equipo actualizado correctamente.</div>
    <?php elseif (!empty($_GET['error_equipo'])): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars((string) $_GET['error_equipo'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div id="mensaje_empresa" class="alert alert-info" role="status" aria-live="polite">Cargando información de la empresa...</div>

    <div id="contenido_empresa" class="d-none">
        <div class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="empresa-section-heading">
                    <div>
                        <p class="empresa-section-kicker mb-1">Identificación</p>
                        <h2 class="h5 card-title mb-0">Datos generales</h2>
                    </div>
                </div>
                <div class="row g-3 mt-1" id="datos_generales"></div>
            </div>
        </div>

        <div class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-3 empresa-section-heading">
                    <div>
                        <p class="empresa-section-kicker mb-1">Información operativa</p>
                        <h2 class="h5 card-title mb-1">Notas de la empresa</h2>
                        <p class="empresa-notes-help mb-0">Procedimientos, requisitos de acceso, facturación y otros datos importantes.</p>
                    </div>
                    <button id="btn_guardar_notas" type="button" class="btn btn-secondary btn-sm" disabled>
                        <i class="bi bi-floppy" aria-hidden="true"></i> Guardar notas
                    </button>
                </div>
                <label for="empresa_notas" class="visually-hidden">Notas de la empresa</label>
                <textarea id="empresa_notas" class="form-control empresa-notes-textarea" rows="8" maxlength="15000" placeholder="Escribe aquí procedimientos, requisitos o indicaciones de esta empresa..." disabled></textarea>
                <div id="estado_notas_empresa" class="empresa-notes-status mt-2" role="status" aria-live="polite">Cargando notas...</div>
            </div>
        </div>

        <div class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3 empresa-section-heading">
                    <div>
                        <p class="empresa-section-kicker mb-1">Domicilios registrados</p>
                        <h2 class="h5 card-title mb-0">Direcciones</h2>
                    </div>
                    <a id="btn_agregar_direccion" href="#" class="btn btn-secondary btn-sm disabled" aria-disabled="true">
                        <i class="bi bi-plus-lg"></i> Añadir dirección
                    </a>
                </div>
                <div class="table-responsive empresa-table-wrap">
                    <table class="table table-secondary align-middle empresa-detail-table empresa-address-table mb-0">
                        <caption class="visually-hidden">Direcciones registradas de la empresa</caption>
                        <thead><tr><th scope="col">Tipo</th><th scope="col">Dirección</th><th scope="col">Editar</th></tr></thead>
                        <tbody id="tabla_direcciones"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3 empresa-section-heading">
                    <div>
                        <p class="empresa-section-kicker mb-1">Personas vinculadas</p>
                        <h2 class="h5 card-title mb-0">Contactos</h2>
                    </div>
                    <a id="btn_agregar_contacto" href="#" class="btn btn-secondary btn-sm disabled" aria-disabled="true">
                        <i class="bi bi-plus-lg"></i> Añadir contacto
                    </a>
                </div>
                <div class="table-responsive empresa-table-wrap">
                    <table class="table table-secondary align-middle empresa-detail-table empresa-contacts-table mb-0">
                        <caption class="visually-hidden">Contactos registrados de la empresa</caption>
                        <thead><tr><th scope="col">Nombre</th><th scope="col">Teléfono</th><th scope="col">Correo</th><th scope="col">Departamento</th><th scope="col">Puesto</th><th scope="col">Estado</th><th scope="col">Editar</th></tr></thead>
                        <tbody id="tabla_contactos"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="equipos" class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3 mb-3 empresa-section-heading">
                    <div>
                        <p class="empresa-section-kicker mb-1">Inventario de equipos</p>
                        <h2 class="h5 card-title mb-1">Equipos</h2>
                        <p class="empresa-notes-help mb-0">Básculas registradas en las direcciones de esta empresa.</p>
                    </div>
                    <a id="btn_agregar_equipo" href="#" class="btn btn-secondary btn-sm disabled" aria-disabled="true">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Añadir equipo
                    </a>
                </div>

                <div class="row g-3 align-items-end mb-3">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label" for="filtro_direccion_equipos">Dirección</label>
                        <select id="filtro_direccion_equipos" class="form-select" disabled>
                            <option value="">Todas las direcciones</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-8">
                        <p id="ayuda_equipos" class="empresa-notes-help mb-0">Cargando direcciones y equipos...</p>
                    </div>
                </div>

                <div class="table-responsive data-table-shell empresa-table-wrap table-wide">
                    <table id="tabla_equipos_empresa" class="table table-secondary table-striped align-middle mb-0 w-100">
                        <caption class="visually-hidden">Equipos registrados en la empresa</caption>
                        <thead>
                            <tr>
                                <th scope="col">Descripción de equipo</th>
                                <th scope="col">Ubicación</th>
                                <th scope="col">Marca</th>
                                <th scope="col">Modelo</th>
                                <th scope="col">Identificación</th>
                                <th scope="col">Serie</th>
                                <th scope="col">Capacidad</th>
                                <th scope="col">División real</th>
                                <th scope="col">División de verificación</th>
                                <th scope="col">Clase de exactitud</th>
                                <th scope="col">Editar</th>
                                <th scope="col">Estatus</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="empresa-section-heading mb-3">
                    <div>
                        <p class="empresa-section-kicker mb-1">Historial comercial</p>
                        <h2 class="h5 card-title mb-1">Actividad relacionada</h2>
                        <p class="empresa-notes-help mb-0">Consulta las oportunidades y cotizaciones vinculadas con esta empresa.</p>
                    </div>
                </div>
                <ul class="nav nav-tabs empresa-history-tabs" id="empresa-historial-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="empresa-oportunidades-tab" data-bs-toggle="tab" data-bs-target="#empresa-oportunidades-pane" type="button" role="tab" aria-controls="empresa-oportunidades-pane" aria-selected="true">Oportunidades</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="empresa-cotizaciones-tab" data-bs-toggle="tab" data-bs-target="#empresa-cotizaciones-pane" type="button" role="tab" aria-controls="empresa-cotizaciones-pane" aria-selected="false">Cotizaciones</button>
                    </li>
                </ul>
                <div class="tab-content" id="empresa-historial-contenido">
                    <div class="tab-pane fade show active empresa-history-pane" id="empresa-oportunidades-pane" role="tabpanel" aria-labelledby="empresa-oportunidades-tab" tabindex="0">
                        <div class="table-responsive data-table-shell empresa-table-wrap table-wide">
                            <table id="tabla_oportunidades_empresa" class="table table-secondary table-striped align-middle mb-0 w-100">
                                <caption class="visually-hidden">Oportunidades comerciales relacionadas con la empresa</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Fecha</th>
                                        <th scope="col">Número</th>
                                        <th scope="col">Contacto</th>
                                        <th scope="col">Proyecto</th>
                                        <th scope="col">Importe sin IVA</th>
                                        <th scope="col">Estatus</th>
                                        <th scope="col">Registro</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade empresa-history-pane" id="empresa-cotizaciones-pane" role="tabpanel" aria-labelledby="empresa-cotizaciones-tab" tabindex="0">
                        <div class="table-responsive data-table-shell empresa-table-wrap table-wide">
                            <table id="tabla_cotizaciones_empresa" class="table table-secondary table-striped align-middle mb-0 w-100">
                                <caption class="visually-hidden">Cotizaciones relacionadas con la empresa</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Fecha</th>
                                        <th scope="col">Número</th>
                                        <th scope="col">Contacto</th>
                                        <th scope="col">Importe</th>
                                        <th scope="col">Estatus</th>
                                        <th scope="col">Registro</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="<?= $prefijoRuta ?>js/datatable-filters.js"></script>
<script src="<?= $prefijoRuta ?>js/datatable-config.js"></script>
<script src="<?= $prefijoRuta ?>js/cotizaciones-status.js?v=20261003-1"></script>
<script src="<?= $prefijoRuta ?>js/ver_empresa.js?v=20261006-1"></script>

<?php require (__DIR__ . "/../construct/footer.html"); ?>
