<?php

$prefijoRuta = '../';
$empresaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$contactoRetornoId = filter_input(INPUT_GET, 'contacto_id', FILTER_VALIDATE_INT);
if ($contactoRetornoId === false || $contactoRetornoId < 1) {
    $contactoRetornoId = null;
}

require(__DIR__ . "/../construct/header.php");
?>

<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg" ">

    <!-- row de titulo -->
    <div class="row">
        <div class="col text-center mt-3 mb-5">
            <h1 id="titulo_form_empresa"><span>Nueva Empresa</span></h1>
        </div>
    </div>

    <?php if (isset($_GET['guardado'])): ?>
        <div class="alert alert-success" role="alert">
            Empresa guardada correctamente.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['actualizado'])): ?>
        <div class="alert alert-success" role="alert">
            Datos generales actualizados correctamente.
        </div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <!-- row de titulo -->

    <form id="formEmpresa" data-local-draft="1" action="<?= $prefijoRuta ?>backend/empresas/guardar_empresa_completa.php" method="POST">
    <input type="hidden" name="empresa_id" id="empresa_id" value="<?= htmlspecialchars((string) ($empresaId ?: '')) ?>">
    <input type="hidden" name="version" id="empresa_version" value="">
    <input type="hidden" name="contacto_retorno_id" id="contacto_retorno_id" value="<?= $contactoRetornoId ? (int) $contactoRetornoId : '' ?>">
    <!-- row de botones de formulario empresas  -->
    <div class="row border-top justify-content-center">
        <!-- bloque de botones -->
        <div class="row">
            <div class="col mt-4 mb-4">
                <!-- crear aqui los botones-->
                <div class="d-flex flex-column flex-md-row justify-content-md-end gap-2">
                    <button id="btn_guardar_empresa" type="submit" class="btn btn-outline-success btn-empresa-guardar px-4">
                        Guardar
                    </button>
                    <a id="btn_cancelar_empresa" href="<?= $contactoRetornoId ? 'ver_contacto.php?id=' . (int) $contactoRetornoId : 'empresas.php' ?>" class="btn btn-danger px-4">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- row de botones de OS  -->



    <!-- row de Contenido -->
    <div class="row border-top justify-content-center ">
        <!-- formulario empresa  -->
        <div class="row">
            <div class="col text-center mt-3 mb-5">
                <!-- crear aqui -->
                    <div class="d-flex flex-column gap-4 text-start">
                        <div class="card border-0 shadow-sm empresa-form-card">
                            <div class="card-body p-4 rounded-4 empresa-card-body">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Datos principales</h2>
                                    <p class="text-body-secondary mb-0">Captura manual base para el alta de la empresa.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-6">
                                        <label for="empresa" class="form-label fw-semibold">Empresa <span class="required-mark" aria-hidden="true">*</span></label>
                                        <input type="text" class="form-control" id="empresa" name="empresa" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="razon_social" class="form-label fw-semibold">Razon social</label>
                                        <input type="text" class="form-control" id="razon_social" name="razon_social">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="rfc" class="form-label fw-semibold">RFC</label>
                                        <input type="text" class="form-control" id="rfc" name="rfc">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="rol" class="form-label fw-semibold">Rol <span class="required-mark" aria-hidden="true">*</span></label>
                                        <select class="form-select" id="rol" name="rol" required>
                                            <option value="">Selecciona una opcion</option>
                                            <option value="Cliente">Cliente</option>
                                            <option value="Proveedor">Proveedor</option>
                                            <option value="Prospecto">Prospecto</option>
                                            <option value="Cliente-Proveedor">Cliente-Proveedor</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="actividad_economica" class="form-label fw-semibold">Actividad economica</label>
                                        <input type="text" class="form-control" id="actividad_economica" name="actividad_economica">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="regimen_capital" class="form-label fw-semibold">Regimen de capital</label>
                                        <input type="text" class="form-control" id="regimen_capital" name="regimen_capital">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="tipo_persona" class="form-label fw-semibold">Tipo de persona fiscal</label>
                                        <select class="form-select" id="tipo_persona" name="tipo_persona">
                                            <option value="">Selecciona una opcion</option>
                                            <option value="Física">Fisica</option>
                                            <option value="Moral">Moral</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm empresa-form-card">
                            <div class="card-body p-4 rounded-4 empresa-card-body">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Contacto general</h2>
                                    <p class="text-body-secondary mb-0">Canales principales de contacto de la empresa.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-4">
                                        <label for="telefono_principal" class="form-label fw-semibold">Telefono principal</label>
                                        <input type="tel" class="form-control" id="telefono_principal" name="telefono_principal">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="email_principal" class="form-label fw-semibold">Email principal</label>
                                        <input type="email" class="form-control" id="email_principal" name="email_principal">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="pagina_web" class="form-label fw-semibold">Pagina web</label>
                                        <input type="text" inputmode="url" autocomplete="url" class="form-control" id="pagina_web" name="pagina_web" maxlength="255" placeholder="https://empresa.com o empresa.com">
                                        <div class="form-text">Puedes escribir el sitio con o sin https://.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm empresa-form-card">
                            <div class="card-body p-4 rounded-4 empresa-card-body">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Clasificacion comercial</h2>
                                    <p class="text-body-secondary mb-0">Datos internos para clasificacion de negocio.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-6">
                                        <label for="giro_mercantil" class="form-label fw-semibold">Giro mercantil</label>
                                        <input type="text" class="form-control" id="giro_mercantil" name="giro_mercantil">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="mercado" class="form-label fw-semibold">Mercado</label>
                                        <input type="text" class="form-control" id="mercado" name="mercado">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm empresa-form-card">
                            <div class="card-body p-4 rounded-4 empresa-card-body">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Control interno</h2>
                                    <p class="text-body-secondary mb-0">Campos operativos para seguimiento del registro.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-6">
                                        <label for="estatus" class="form-label fw-semibold">Estatus <span class="required-mark" aria-hidden="true">*</span></label>
                                        <select class="form-select" id="estatus" name="estatus" required>
                                            <option value="activo" selected>activo</option>
                                            <option value="inactivo">inactivo</option>
                                            <option value="bloqueado">bloqueado</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm empresa-form-card">
                            <div class="card-body p-4 rounded-4 empresa-card-body">
                                <div class="mb-3">
                                    <h2 class="h5 mb-1">Informacion del registro</h2>
                                    <p class="text-body-secondary small mb-0">Referencia generada automáticamente por el sistema.</p>
                                </div>
                                <div class="empresa-record-summary" aria-label="Información automática del registro">
                                    <span>ID: <strong id="id_e">Se asignará al guardar</strong></span>
                                    <span>Creación: <strong id="created_at">—</strong></span>
                                    <span>Actualización: <strong id="updated_at">—</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
    </form>
    <!-- formulario empresa -->

</div>
<!-- container -->


<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Data Tables 1.13.7 -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>

<!-- Data Tables 1.13.7 boostrap5 -->
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script src="../js/datatable-filters.js"></script>
<script src="../js/main.js"></script>
<script src="<?= $prefijoRuta ?>js/duplicate-warning.js?v=20261006-1"></script>
<script src="<?= $prefijoRuta ?>js/form_empresa.js"></script>


<?php require(__DIR__ . "/../construct/footer.html") ?>
