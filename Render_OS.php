<?php require("construct/header.html"); ?>
<?php require("querys/query_id_os.php");?>

<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg">

    <!-- row de titulo -->
    <div class="row">
        <div class="col text-center mt-3 mb-5">
            <h1><span>Orden de Servicio</span></h1>
        </div>
    </div>
    <!-- row de titulo -->

    <!-- row de botones de OS  -->
    <div class="row border-top justify-content-center">
        <!-- bloque de botones -->
        <div class="row">
            <div class="col mt-4 mb-4">
                <!-- crear aqui los botones-->
                <div class="d-flex flex-column flex-md-row justify-content-md-end gap-2">
                    <button type="button" id="btnEditarOrden" class="btn btn-secondary px-4">
                        Editar
                    </button>
                    <button type="button" class="btn btn-outline-success px-4">
                        Imprimir
                    </button>
                    <button type="button" class="btn btn-danger px-4">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- row de botones de OS  -->

    <!-- row de datos de OS  -->
    <div class="row border-top justify-content-center">
        <!-- bloque de datos -->
        <div class="row">
            <div class="col mt-4 mb-5">
                <!-- crear aqui los datos-->
                <div class="p-4 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(110, 181, 255, 0.18);">
                    <!-- datos generales -->
                    <div class="row g-4 " >
                        <div class="col-12 col-lg-6">
                            <!-- columna izquierda -->
                            <div class="d-flex flex-column gap-4 h-100">
                                <div>
                                    <label for="empresa" class="form-label fw-semibold">Empresa</label>
                                    <input
                                        type="text"
                                        id="empresa"
                                        class="form-control"
                                        value="<?= htmlspecialchars($orden['empresa_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        readonly>
                                </div>

                                <div>
                                    <label for="direccion_entrega" class="form-label fw-semibold">Direccion de entrega</label>
                                    <input
                                        type="text"
                                        id="direccion_entrega"
                                        class="form-control"
                                        value="<?= htmlspecialchars($orden['direccion_entrega'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        readonly>
                                </div>

                                <div>
                                    <label for="ubicacion" class="form-label fw-semibold">Ubicacion</label>
                                    <input
                                        type="url"
                                        id="ubicacion"
                                        class="form-control campo-editable"
                                        value="<?= htmlspecialchars($orden['ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        disabled>
                                </div>

                                <div>
                                    <label for="estado" class="form-label fw-semibold">Estado</label>
                                    <div class="d-flex align-items-center h-100">
                                        <span class="badge text-bg-warning px-3 py-2 fs-6">
                                            <?= htmlspecialchars($orden['estado'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6 ">
                            <!-- columna derecha -->
                            <div class="d-flex flex-column gap-4 h-100">
                                
                                <div class="col-12 col-md-6">
                                    <label for="contacto" class="form-label fw-semibold">Contacto</label>
                                    <input
                                        type="text"
                                        id="contacto"
                                        class="form-control"
                                        value="<?= htmlspecialchars($orden['contacto_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        readonly>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="telefono_contacto" class="form-label fw-semibold">Telefono de contacto</label>
                                    <input
                                        type="text"
                                        id="telefono_contacto"
                                        class="form-control"
                                        value="<?= htmlspecialchars($orden['telefono_contacto'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        readonly>
                                </div>

                                <div class="col-12">
                                    <label for="correo_contacto" class="form-label fw-semibold">Correo del contacto</label>
                                    <input
                                        type="email"
                                        id="correo_contacto"
                                        class="form-control"
                                        value="<?= htmlspecialchars($orden['correo_contacto'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        readonly>
                                </div>                               
                            </div>
                        </div>

                        <div class="row g-4" >
                            <hr class="my-0" style="border-color: rgba(110, 181, 255, 0.22); opacity: 1;">

                            <div>
                                <label for="fecha_servicio" class="form-label fw-semibold">Fecha de servicio</label>
                                <input
                                    type="date"
                                    id="fecha_servicio"
                                    class="form-control campo-editable"
                                    value="<?= htmlspecialchars($orden['fecha_servicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    disabled>
                            </div>

                            <div>
                                <label for="nombre_proyecto" class="form-label fw-semibold">Nombre del proyecto</label>
                                <input
                                    type="text"
                                    id="nombre_proyecto"
                                    class="form-control campo-editable"
                                    value="<?= htmlspecialchars($orden['nombre_proyecto'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    disabled>
                            </div>

                            <div>
                                <label for="descripcion" class="form-label fw-semibold">Descripcion</label>
                                <textarea
                                    id="descripcion"
                                    class="form-control campo-editable"
                                    rows="5"
                                    disabled><?= htmlspecialchars($orden['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div>
                                <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                                <textarea
                                    id="observaciones"
                                    class="form-control campo-editable"
                                    rows="4"
                                    disabled><?= htmlspecialchars($orden['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                        </div>
                        
                        <div class="row g-4 border border-danger" >
                            <hr class="my-0" style="border-color: rgba(110, 181, 255, 0.22); opacity: 1;">

                            

                        </div>  




                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- row de datos de OS  -->

</div>
<!-- container -->

<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Data Tables 1.13.7 -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>

<!-- Data Tables 1.13.7 boostrap5 -->
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script src="js/datatable-filters.js"></script>
<script src="js/main.js"></script>

<script>
    // interaccion visual
    document.addEventListener('DOMContentLoaded', function () {
        const btnEditarOrden = document.getElementById('btnEditarOrden');
        const camposEditables = document.querySelectorAll('.campo-editable');

        if (!btnEditarOrden) {
            return;
        }

        btnEditarOrden.addEventListener('click', function () {
            const modoEdicion = btnEditarOrden.dataset.editando === 'true';

            camposEditables.forEach(function (campo) {
                campo.disabled = modoEdicion;
            });

            btnEditarOrden.textContent = modoEdicion ? 'Editar' : 'Actualizar';
            btnEditarOrden.dataset.editando = modoEdicion ? 'false' : 'true';
        });
    });
</script>

<?php require("construct/footer.html"); ?>
