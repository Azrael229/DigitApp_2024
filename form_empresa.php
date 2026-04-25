<?php  require ("construct/header.html")   ?>

<style>
    #constancia_pdf {
        background: rgba(4, 22, 52, 0.98) !important;
        border-color: rgba(89, 182, 255, 0.26) !important;
        color: #ffffff !important;
    }

    #constancia_pdf::file-selector-button {
        background: linear-gradient(135deg, rgba(13, 71, 138, 0.95), rgba(8, 31, 70, 0.98));
        color: #ffffff;
        border: 1px solid rgba(89, 182, 255, 0.22);
        border-radius: 10px;
        margin-right: 12px;
        padding: 0.55rem 0.9rem;
    }

    #mensajeConstancia .alert {
        background: linear-gradient(135deg, rgba(6, 24, 55, 0.98), rgba(10, 35, 76, 0.98)) !important;
        border: 1px solid rgba(89, 182, 255, 0.22) !important;
        color: #ffffff !important;
        box-shadow: 0 18px 36px rgba(1, 10, 28, 0.24);
    }

    #mensajeConstancia .alert-info {
        border-color: rgba(89, 182, 255, 0.34) !important;
    }

    #mensajeConstancia .alert-warning {
        border-color: rgba(255, 193, 7, 0.38) !important;
    }

    #mensajeConstancia .alert-danger {
        border-color: rgba(228, 0, 124, 0.38) !important;
    }
</style>

<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg" ">

    <!-- row de titulo -->
    <div class="row">
        <div class="col text-center mt-3 mb-5">
            <h1><span>Nueva Empresa</span></h1>
        </div>
    </div>
    <!-- row de titulo -->

    <!-- row de botones de formulario empresas  -->
    <div class="row border-top justify-content-center">
        <!-- bloque de botones -->
        <div class="row">
            <div class="col mt-4 mb-4">
                <!-- crear aqui los botones-->
                <div class="d-flex flex-column flex-md-row justify-content-md-end gap-2">
                    <button type="button" class="btn btn-outline-success px-4">
                        Guardar
                    </button>
                    <button type="button" class="btn btn-danger px-4">
                        Cancelar
                    </button>
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
                <form>
                    <div class="d-flex flex-column gap-4 text-start">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(110, 181, 255, 0.18);">
                                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                                    <div>
                                        <h2 class="h4 mb-1">Carga de constancia fiscal PDF</h2>
                                        <p class="text-body-secondary mb-1">El procesamiento automatico de la constancia fiscal se implementara con el nuevo metodo.</p>
                                        <p class="text-body-secondary mb-0">El PDF se valida de forma temporal y no se guarda en el servidor.</p>
                                    </div>
                                </div>
                                <div class="row g-4 align-items-end">
                                    <div class="col-12 col-lg-8">
                                        <label for="constancia_pdf" class="form-label fw-semibold">Constancia de situacion fiscal</label>
                                        <input type="file" class="form-control" id="constancia_pdf" name="constancia_pdf" accept="application/pdf">
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <label for="constancia_fiscal_pdf_nombre" class="form-label fw-semibold">Archivo seleccionado</label>
                                        <input type="text" class="form-control" id="constancia_fiscal_pdf_nombre" name="constancia_fiscal_pdf_nombre" value="Ningun archivo seleccionado" readonly>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <button type="button" id="btnLeerConstancia" class="btn btn-secondary px-4 w-100">
                                            Leer constancia y llenar datos
                                        </button>
                                    </div>
                                    <div class="col-12 col-lg-8">
                                        <div id="mensajeConstancia"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(110, 181, 255, 0.18);">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Datos principales</h2>
                                    <p class="text-body-secondary mb-0">Captura manual base para el alta de la empresa.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-6">
                                        <label for="empresa" class="form-label fw-semibold">Empresa</label>
                                        <input type="text" class="form-control" id="empresa" name="empresa">
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
                                        <label for="rol" class="form-label fw-semibold">Rol</label>
                                        <select class="form-select" id="rol" name="rol">
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
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(110, 181, 255, 0.18);">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Datos fiscales</h2>
                                    <p class="text-body-secondary mb-0">Informacion fiscal principal de la empresa.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-4">
                                        <label for="regimen_fiscal_codigo" class="form-label fw-semibold">Regimen fiscal codigo</label>
                                        <input type="text" class="form-control" id="regimen_fiscal_codigo" name="regimen_fiscal_codigo">
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <label for="regimen_fiscal_descripcion" class="form-label fw-semibold">Regimen fiscal descripcion</label>
                                        <input type="text" class="form-control" id="regimen_fiscal_descripcion" name="regimen_fiscal_descripcion">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="regimen_capital" class="form-label fw-semibold">Regimen capital</label>
                                        <input type="text" class="form-control" id="regimen_capital" name="regimen_capital">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="tipo_persona" class="form-label fw-semibold">Tipo persona</label>
                                        <select class="form-select" id="tipo_persona" name="tipo_persona">
                                            <option value="">Selecciona una opcion</option>
                                            <option value="Física">Fisica</option>
                                            <option value="Moral">Moral</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(110, 181, 255, 0.18);">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Direccion fiscal</h2>
                                    <p class="text-body-secondary mb-0">Seccion visual para captura de la direccion fiscal que despues se almacenara en empresa_direcciones.</p>
                                </div>
                                <input type="hidden" id="tipo_direccion" name="tipo_direccion" value="fiscal">
                                <input type="hidden" id="es_principal" name="es_principal" value="1">
                                <div class="row g-4">
                                    <div class="col-12 col-md-4">
                                        <label for="tipo_direccion_visible" class="form-label fw-semibold">Tipo de direccion</label>
                                        <input type="text" class="form-control" id="tipo_direccion_visible" value="fiscal" readonly>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="alias" class="form-label fw-semibold">Alias</label>
                                        <input type="text" class="form-control" id="alias" name="alias" value="Fiscal">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="pais" class="form-label fw-semibold">Pais</label>
                                        <input type="text" class="form-control" id="pais" name="pais" value="Mexico">
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <label for="calle" class="form-label fw-semibold">Calle</label>
                                        <input type="text" class="form-control" id="calle" name="calle">
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <label for="numero_exterior" class="form-label fw-semibold">Numero exterior</label>
                                        <input type="text" class="form-control" id="numero_exterior" name="numero_exterior">
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <label for="numero_interior" class="form-label fw-semibold">Numero interior</label>
                                        <input type="text" class="form-control" id="numero_interior" name="numero_interior">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="colonia" class="form-label fw-semibold">Colonia</label>
                                        <input type="text" class="form-control" id="colonia" name="colonia">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="localidad" class="form-label fw-semibold">Localidad</label>
                                        <input type="text" class="form-control" id="localidad" name="localidad">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="municipio" class="form-label fw-semibold">Municipio</label>
                                        <input type="text" class="form-control" id="municipio" name="municipio">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="ciudad" class="form-label fw-semibold">Ciudad</label>
                                        <input type="text" class="form-control" id="ciudad" name="ciudad">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="estado" class="form-label fw-semibold">Estado</label>
                                        <input type="text" class="form-control" id="estado" name="estado">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="codigo_postal" class="form-label fw-semibold">Codigo postal</label>
                                        <input type="text" class="form-control" id="codigo_postal" name="codigo_postal">
                                    </div>
                                    <div class="col-12">
                                        <label for="entre_calles" class="form-label fw-semibold">Entre calles</label>
                                        <input type="text" class="form-control" id="entre_calles" name="entre_calles">
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label for="enlace_maps" class="form-label fw-semibold">Enlace Maps</label>
                                        <input type="url" class="form-control" id="enlace_maps" name="enlace_maps">
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label for="es_principal_visible" class="form-label fw-semibold">Direccion principal</label>
                                        <input type="text" class="form-control" id="es_principal_visible" value="1" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label for="referencia" class="form-label fw-semibold">Referencia</label>
                                        <textarea class="form-control" id="referencia" name="referencia" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(110, 181, 255, 0.18);">
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

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(110, 181, 255, 0.18);">
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
                                        <input type="url" class="form-control" id="pagina_web" name="pagina_web">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(110, 181, 255, 0.18);">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Control interno</h2>
                                    <p class="text-body-secondary mb-0">Campos operativos para seguimiento del registro.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-4">
                                        <label for="estatus" class="form-label fw-semibold">Estatus</label>
                                        <select class="form-select" id="estatus" name="estatus">
                                            <option value="">Selecciona una opcion</option>
                                            <option value="activo">activo</option>
                                            <option value="inactivo">inactivo</option>
                                            <option value="bloqueado">bloqueado</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="origen_registro" class="form-label fw-semibold">Origen de registro</label>
                                        <select class="form-select" id="origen_registro" name="origen_registro">
                                            <option value="">Selecciona una opcion</option>
                                            <option value="manual">manual</option>
                                            <option value="constancia_fiscal">constancia_fiscal</option>
                                            <option value="importacion">importacion</option>
                                            <option value="otro">otro</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="es_principal_resumen" class="form-label fw-semibold">Fiscal principal</label>
                                        <input type="text" class="form-control" id="es_principal_resumen" value="1" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="4"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(110, 181, 255, 0.18);">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Informacion del registro</h2>
                                    <p class="text-body-secondary mb-0">Campos informativos no editables.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-4">
                                        <label for="id_e" class="form-label fw-semibold">ID empresa</label>
                                        <input type="text" class="form-control" id="id_e" name="id_e" readonly>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="created_at" class="form-label fw-semibold">Creado en</label>
                                        <input type="text" class="form-control" id="created_at" name="created_at" readonly>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="updated_at" class="form-label fw-semibold">Actualizado en</label>
                                        <input type="text" class="form-control" id="updated_at" name="updated_at" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- formulario empresa -->

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
<script src="js/empresas_constancia.js"></script>


<?php  require ("construct/footer.html")   ?>
