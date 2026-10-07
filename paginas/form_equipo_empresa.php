<?php
require_once __DIR__ . '/../backend/auth/bootstrap.php';
$prefijoRuta = '../';
$empresaId = filter_input(INPUT_GET, 'empresa_id', FILTER_VALIDATE_INT);
$equipoId = filter_input(INPUT_GET, 'equipo_id', FILTER_VALIDATE_INT);
$returnUrl = (string) ($_GET['return_url'] ?? '');
if ($returnUrl !== '' && !preg_match('/^informe\.php(?:\?.*)?$/', $returnUrl)) {
    $returnUrl = '';
}
if (empty($_SESSION['empresa_equipos_csrf'])) {
    $_SESSION['empresa_equipos_csrf'] = bin2hex(random_bytes(32));
}
?>
<?php require __DIR__ . '/../construct/header.php'; ?>

<div class="container mt-5 mb-5 contain shadow-lg empresa-equipo-form">
    <div class="row align-items-center pt-3 pb-4 mb-4 empresa-detalle-header">
        <div class="col">
            <p class="empresa-header-kicker mb-1">Inventario de equipos</p>
            <h1 id="titulo_equipo" class="h2 mb-1"><?= $equipoId ? 'Editar equipo' : 'Nuevo equipo' ?></h1>
            <p id="subtitulo_equipo" class="empresa-header-subtitle mb-0">Cargando información de la empresa...</p>
        </div>
        <div class="col-12 col-md-auto mt-3 mt-md-0">
            <a href="<?= htmlspecialchars($returnUrl !== '' ? $returnUrl : 'ver_empresa.php?id=' . (string) ($empresaId ?: '') . '#equipos', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> <?= $returnUrl !== '' ? 'Volver al informe' : 'Volver a empresa' ?>
            </a>
        </div>
    </div>

    <?php if ($empresaId === false || $empresaId === null): ?>
        <div class="alert alert-danger">No se indicó una empresa válida.</div>
    <?php else: ?>
        <div id="mensaje_form_equipo" class="alert alert-info" role="status" aria-live="polite">Preparando el formulario...</div>

        <form id="form_equipo_empresa" data-local-draft="1" class="d-none" action="<?= $prefijoRuta ?>backend/empresas/guardar_equipo_empresa.php" method="POST" novalidate>
            <input type="hidden" name="empresa_id" id="empresa_id" value="<?= htmlspecialchars((string) $empresaId) ?>">
            <input type="hidden" name="equipo_id" id="equipo_id" value="<?= htmlspecialchars((string) ($equipoId ?: '')) ?>">
            <input type="hidden" name="version" id="equipo_version" value="">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['empresa_equipos_csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>">

            <div class="card mb-4 empresa-form-card">
                <div class="card-body empresa-card-body p-4">
                    <div class="mb-4">
                        <p class="empresa-section-kicker mb-1">Dirección asociada</p>
                        <h2 class="h4 mb-1">Dirección del equipo</h2>
                        <p class="empresa-notes-help mb-0">Puedes asociar el equipo con un domicilio o alias de la empresa.</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="direccion_id">Dirección</label>
                            <select class="form-select" name="direccion_id" id="direccion_id">
                                <option value="">Sin dirección asignada</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4 empresa-form-card">
                <div class="card-body empresa-card-body p-4">
                    <div class="mb-4">
                        <p class="empresa-section-kicker mb-1">Identificación</p>
                        <h2 class="h4 mb-1">Datos del equipo</h2>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="descripcion_id">Descripción de equipo</label>
                            <select class="form-select" name="descripcion_id" id="descripcion_id">
                                <option value="">Selecciona una descripción</option>
                            </select>
                            <div class="input-group mt-2 empresa-catalogo-rapido">
                                <input class="form-control" type="text" id="nueva_descripcion_equipo" maxlength="100" placeholder="Nueva descripción">
                                <button class="btn btn-secondary" type="button" id="btn_agregar_descripcion">Agregar</button>
                            </div>
                            <div id="estado_descripcion_equipo" class="empresa-notes-status mt-1" role="status" aria-live="polite"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="marca_id">Marca</label>
                            <select class="form-select" name="marca_id" id="marca_id">
                                <option value="">Selecciona una marca</option>
                            </select>
                            <div class="input-group mt-2 empresa-catalogo-rapido">
                                <input class="form-control" type="text" id="nueva_marca_equipo" maxlength="100" placeholder="Nueva marca">
                                <button class="btn btn-secondary" type="button" id="btn_agregar_marca">Agregar</button>
                            </div>
                            <div id="estado_marca_equipo" class="empresa-notes-status mt-1" role="status" aria-live="polite"></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="modelo">Modelo</label>
                            <input class="form-control" type="text" name="modelo" id="modelo" maxlength="100">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="identificacion">Identificación</label>
                            <input class="form-control" type="text" name="identificacion" id="identificacion" maxlength="100">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="numero_serie">Número de serie</label>
                            <input class="form-control" type="text" name="numero_serie" id="numero_serie" maxlength="100">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="ubicacion">Ubicación</label>
                            <input class="form-control" type="text" name="ubicacion" id="ubicacion" maxlength="150" placeholder="Ej. Área de embarques">
                            <div class="form-text">Área de operación del equipo dentro de las instalaciones del cliente.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4 empresa-form-card">
                <div class="card-body empresa-card-body p-4">
                    <div class="mb-4">
                        <p class="empresa-section-kicker mb-1">Características metrológicas</p>
                        <h2 class="h4 mb-1">Capacidad y divisiones</h2>
                        <p class="empresa-notes-help mb-0">Captura los números sin espacios ni comas. Para decimales utiliza punto y cero a la izquierda, por ejemplo: 3000, 0.5 o 0.005.</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="unidad">Unidad <span class="required-mark">*</span></label>
                            <select class="form-select" name="unidad" id="unidad" required>
                                <option value="">Selecciona una unidad</option>
                                <option value="kg">kg</option>
                                <option value="g">g</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="capacidad_maxima">Capacidad máxima <span class="required-mark">*</span></label>
                            <input class="form-control equipo-numero" type="text" inputmode="decimal" name="capacidad_maxima" id="capacidad_maxima" maxlength="30" placeholder="3000" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="division_real">División real <span class="required-mark">*</span></label>
                            <input class="form-control equipo-numero" type="text" inputmode="decimal" name="division_real" id="division_real" maxlength="30" placeholder="0.005" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="division_verificacion">División de verificación</label>
                            <input class="form-control equipo-numero" type="text" inputmode="decimal" name="division_verificacion" id="division_verificacion" maxlength="30" placeholder="0.01">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="clase_exactitud">Clase de exactitud</label>
                            <input class="form-control" type="text" id="clase_exactitud" readonly aria-readonly="true" placeholder="Se calcula con la capacidad y la división de verificación">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="estatus">Estatus</label>
                            <select class="form-select" name="estatus" id="estatus">
                                <option value="activo">Activo</option>
                                <option value="fuera_servicio">Fuera de servicio</option>
                                <option value="inactivo">No activo</option>
                            </select>
                        </div>
                    </div>
                    <div id="mensaje_numeros_equipo" class="empresa-notes-status mt-3" role="status" aria-live="polite"></div>
                </div>
            </div>

            <div class="d-flex flex-column flex-sm-row justify-content-sm-end gap-2 pb-4">
                <a href="<?= htmlspecialchars($returnUrl !== '' ? $returnUrl : 'ver_empresa.php?id=' . (string) $empresaId . '#equipos', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-danger">Cancelar</a>
                <button id="btn_guardar_equipo" class="btn btn-secondary" type="submit">Guardar equipo</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if ($empresaId !== false && $empresaId !== null): ?>
<script src="<?= $prefijoRuta ?>js/duplicate-warning.js?v=20261006-1"></script>
<script src="<?= $prefijoRuta ?>js/form_equipo_empresa.js?v=20261006-1"></script>
<?php endif; ?>

<?php require __DIR__ . '/../construct/footer.html'; ?>
