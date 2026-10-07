<?php
$prefijoRuta = '../';
$oportunidadId = filter_input(INPUT_GET, 'oportunidad_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$cotizacionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$editandoCotizacion = $cotizacionId !== false && $cotizacionId !== null;
$paginaRegreso = $editandoCotizacion
    ? 'ver_cotizacion.php?id=' . (int) $cotizacionId
    : (($oportunidadId !== false && $oportunidadId !== null)
        ? 'ver_oportunidad.php?id=' . (int) $oportunidadId
        : 'tablaCotizaciones.php');
require_once __DIR__ . '/../backend/cotizaciones/common.php';
?>
<?php  require (__DIR__ . "/../construct/header.php")   ?>
<?php  require (__DIR__ . "/../backend/empresas/query_all_empresas.php")  ?>


<div class="container mt-5 mb-5 contain shadow-lg cotizacion-form-page">
     <!-- titulo  -->
     <div class="row align-items-center contacto-form-header g-3 pt-3 mb-4">
        <div class="col">
            <p class="contactos-kicker mb-1">Gestión comercial</p>
            <h1 class="h2 mb-0"><i class="bi bi-cash-coin" id="ico_coti" aria-hidden="true"></i> <?= $editandoCotizacion ? 'Editar cotización' : 'Nueva cotización' ?></h1>
        </div>  
    </div>
    <!-- titulo  -->

    <form action="<?= $prefijoRuta ?>fpdf/cotizacionPDF.php" method="POST" target="cotizacion_pdf" id="form_cotizacion" class="cotizacion-form" data-local-draft="1" data-oportunidad-id="<?= $oportunidadId ? (int) $oportunidadId : '' ?>" data-cotizacion-id="<?= $editandoCotizacion ? (int) $cotizacionId : '' ?>" data-return-url="<?= htmlspecialchars($paginaRegreso, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="oportunidad_id" id="oportunidad_id" value="<?= $oportunidadId ? (int) $oportunidadId : '' ?>">
    <input type="hidden" name="empresa_id" id="cot_empresa_id">
    <input type="hidden" name="contacto_id" id="cot_contacto_id">
    <input type="hidden" name="direccion_id" id="cot_direccion_id">
    <input type="hidden" name="cotizacion_id" id="cotizacion_id" value="<?= $editandoCotizacion ? (int) $cotizacionId : '' ?>">
    <input type="hidden" name="version" id="cotizacion_version" value="">
    
    
    <!-- boton submit del formulario -->
    <div class="row">
        <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mb-4">
            <a href="<?= htmlspecialchars($paginaRegreso, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">Cancelar</a>
            <button id="btn_guardar" type="submit" class="btn btn-success"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> <?= $editandoCotizacion ? 'Guardar cambios y ver PDF' : 'Guardar y ver PDF' ?></button>
        </div>
    </div>
    <!-- boton submit del formulario -->


    <!-- Sección Control de Cotizacion -->
    <div class="row cotizacion-form-section">
        <div class="col">
            <div class="cotizacion-section-heading">
                <h2 class="h4 mb-1">Control de cotización</h2>
                <p class="contactos-muted mb-0">Datos de identificación y vigencia del documento.</p>
            </div>
            <!-- fila de No cotizacion -->
                <div class="row mt-5 mb-4">
                    <div class="col col-lg-2 ">
                        <label >No. Cotización</label>
                    </div>
                    <div class="col ">
                        <input type="text" id="numero_coti" name="numero_coti" placeholder="Se asignará al guardar" readonly aria-readonly="true">
                    </div>
                </div>
            <!-- fila de fecha de cotizacion -->
                <div class="row mb-4">
                    <div class="col col-lg-2 ">
                        <label >Fecha</label>
                    </div>
                    <div class="col  ">
                        <input type="date" id="coti_fecha" name="coti_fecha" required>
                    </div>
                </div>
            <!-- fila de vigencia de cotizacion -->
                <div class="row mb-5">
                    <div class="col col-lg-2 ">
                        <label >Fecha Vigencia</label>
                    </div>
                    <div class="col ">
                        <input type="date" id="coti_vigencia" name="coti_vigencia" required>
                    </div>
                </div>
            <!-- fin de las filas -->
            <?php if ($editandoCotizacion): ?>
                <div class="row mb-5">
                    <div class="col col-lg-2"><label for="cot_status">Estatus</label></div>
                    <div class="col">
                        <select class="form-select" name="cot_status" id="cot_status" required>
                            <?php foreach (COTIZACION_ESTATUS as $clave => $etiqueta): ?>
                                <option value="<?= htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Sección Control de Cotizacion -->
    

    <!-- Seccion datos del cliente -->
        <div class="row cotizacion-form-section">

            <!-- col de datos cliente -->
                <div class="col-12">
                    <div class="cotizacion-section-heading mb-4">
                        <h2 class="h4 mb-1">Datos del cliente</h2>
                        <p class="contactos-muted mb-0">Empresa, contacto y dirección se toman de sus registros maestros. Para corregirlos, actualiza primero el detalle correspondiente.</p>
                    </div>

                    <div class="row text-center mb-3">
                        <div class="col mb-4">
                            <label class="form-label" for="select_empresa_cot">Empresa</label>
                            <select class="form-control" id="select_empresa_cot" required>
                                <option value="">Seleccionar empresa</option>
                                <?php foreach ($result_empresas as $fila): ?>
                                    <option value="<?= (int) $fila['id_e'] ?>"><?= htmlspecialchars($fila['empresa'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Select Contacto -->
                    <div class="row text-center mb-3">
                        <div class="col  mb-4 ">
                            <select class="form-control" name="select_contacto" id="select_contacto" disabled required>
                                <option value="">Selecciona primero una empresa</option>
                            </select>
                        </div>
                    </div>
                    <!-- Select Contacto -->


                    <!-- Datos contacto -->
                        <div class="col ">
                            <div class="row ">
                                <div class="col-3">
                                    <label>Nombre</label>
                                </div>
                                <div class="col">
                                    <textarea name="nombre_contacto" id="nombre_contacto" cols="40" rows="1" readonly aria-readonly="true"></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-3 ">
                                    <label>Teléfono</label>
                                </div>
                                <div class="col">
                                    <textarea name="cel_contacto" id="cel_contacto" cols="40" rows="1" readonly aria-readonly="true"></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-3 ">
                                    <label>Correo</label>
                                </div>
                                <div class="col">
                                    <textarea name="correo_contacto" id="correo_contacto" cols="40" rows="1" readonly aria-readonly="true"></textarea>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-3 ">
                                    <label>Departamento</label>
                                </div>
                                <div class="col">
                                    <textarea name="depto_contacto" id="depto_contacto" cols="40" rows="1" readonly aria-readonly="true"></textarea>
                                </div>
                            </div>
                        </div>
                    <!-- Datos contacto -->

                    <!-- Datos Empresa -->
                        <div class="col ">
                            <div class="row">
                                <div class="col-3 ">
                                    <label>Empresa</label>
                                </div>
                                <div class="col">
                                    <textarea name="nombre_empresa" id="nombre_empresa" cols="40" rows="1" readonly aria-readonly="true"></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-3 ">
                                    <label>Dirección a utilizar</label>
                                </div>
                                <div class="col">
                                    <select class="form-control" name="tipo_direccion_empresa" id="select_direccion" disabled required>
                                        <option value="">Selecciona primero una empresa</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-3 ">
                                    <label>Dirección</label>
                                </div>
                                <div class="col">
                                    <textarea name="dir_empresa" id="dir_empresa" cols="40" rows="3" readonly aria-readonly="true"></textarea>
                                </div>
                            </div>
                        </div>
                    <!-- Datos Empresa -->
                </div>
            <!-- col de datos cliente -->

        </div>
    <!-- Seccion datos del cliente -->

    <!-- Sección Condiciones comerciales -->
    <div class="row cotizacion-form-section">
        <div class="col">
            <div class="cotizacion-section-heading mb-4">
                <h2 class="h4 mb-1">Condiciones comerciales</h2>
                <p class="contactos-muted mb-0">Define la entrega, el pago, la garantía y los costos de envío aplicables.</p>
            </div>
            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="tiempo_entrega_tipo">Tiempo de entrega</label>
                    <select class="form-select" name="tiempo_entrega_tipo" id="tiempo_entrega_tipo" required>
                        <option value="">Selecciona una opción</option>
                        <option value="inmediato">Inmediato</option>
                        <option value="dias_habiles">Días hábiles</option>
                        <option value="semanas">Semanas</option>
                    </select>
                    <div class="row g-3 align-items-end mt-1">
                        <div class="col-8 d-none" id="contenedor_tiempo_entrega_cantidad">
                            <label class="form-label" for="tiempo_entrega_cantidad">Cantidad</label>
                            <input class="form-control" type="number" min="1" max="65535" name="tiempo_entrega_cantidad" id="tiempo_entrega_cantidad">
                        </div>
                        <div class="col-4 d-none pb-2" id="etiqueta_tiempo_entrega"></div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="condicion_entrega">Condición de entrega</label>
                    <select class="form-select" name="condicion_entrega" id="condicion_entrega" required>
                        <option value="">Selecciona una opción</option>
                        <option value="instalaciones_cliente">Instalaciones del cliente</option>
                        <option value="paqueteria">Paquetería</option>
                        <option value="instalaciones_servicom">Instalaciones de SERVICOM Básculas Digitales</option>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="condicion_pago_tipo">Condición de pago</label>
                    <select class="form-select" name="condicion_pago_tipo" id="condicion_pago_tipo" required>
                        <option value="">Selecciona una opción</option>
                        <option value="anticipado">Pago anticipado</option>
                        <option value="anticipado_total">Pago 100% anticipado</option>
                        <option value="anticipo_saldo">Pago con anticipo y saldo</option>
                        <option value="credito">Días de crédito</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3 d-none" id="contenedor_pago_anticipo">
                    <label class="form-label" for="pago_anticipo_porcentaje">Anticipo</label>
                    <div class="input-group">
                        <input class="form-control" type="number" min="1" max="99" step="1" name="pago_anticipo_porcentaje" id="pago_anticipo_porcentaje" value="60">
                        <span class="input-group-text">%</span>
                    </div>
                    <div id="resumen_pago_saldo" class="form-text contactos-muted">40% al finalizar.</div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 d-none" id="contenedor_pago_saldo_momento">
                    <label class="form-label" for="pago_saldo_momento">Pago del saldo</label>
                    <select class="form-select" name="pago_saldo_momento" id="pago_saldo_momento">
                        <option value="al_finalizar">Al finalizar</option>
                        <option value="contra_aviso_entrega">Contra aviso de entrega</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3 d-none" id="contenedor_pago_credito">
                    <label class="form-label" for="pago_credito_dias">Días de crédito</label>
                    <input class="form-control" type="number" min="1" max="365" step="1" name="pago_credito_dias" id="pago_credito_dias" value="30">
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="garantia_tipo">Garantía</label>
                    <select class="form-select" name="garantia_tipo" id="garantia_tipo" required>
                        <option value="">Selecciona una opción</option>
                        <option value="sin_garantia">Sin garantía especificada</option>
                        <option value="producto">Producto por defectos de fabricación</option>
                        <option value="mano_obra">Mano de obra</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-4 d-none" id="contenedor_garantia_vigencia">
                    <label class="form-label" for="garantia_vigencia">Vigencia de la garantía</label>
                    <input class="form-control" type="number" min="1" max="65535" step="1" name="garantia_vigencia" id="garantia_vigencia">
                </div>
                <div class="col-12 col-md-6 col-lg-4 d-none" id="contenedor_garantia_unidad">
                    <label class="form-label" for="garantia_unidad">Unidad de vigencia</label>
                    <select class="form-select" name="garantia_unidad" id="garantia_unidad">
                        <option value="dias">Días</option>
                        <option value="meses">Meses</option>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="costos_envio">Costos de envío</label>
                    <select class="form-select" name="costos_envio" id="costos_envio" required>
                        <option value="">Selecciona una opción</option>
                        <option value="incluye">Incluye costos de envío</option>
                        <option value="no_incluye">No incluye costos de envío</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notas7">Nota adicional (máximo 255 caracteres)</label>
                    <textarea class="form-control" name="notas7" id="notas7" rows="3" maxlength="255" placeholder="Escribe únicamente una condición adicional que deba aparecer en la cotización."></textarea>
                </div>
            </div>
        </div>
    </div>
    <!-- Sección Condiciones comerciales -->


    <!-- Seccion Cotizacion -->
    <div class="row cotizacion-form-section">
        <div class="cotizacion-section-heading d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h2 class="h4 mb-1">Conceptos de la cotización</h2>
                <p class="contactos-muted mb-0">Agrega las partidas que necesites; el PDF ajustará automáticamente sus páginas.</p>
            </div>
            <button type="button" id="btn_agregar_partida" class="btn btn-success flex-shrink-0">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar partida
            </button>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-secondary align-middle" id="tabla_partidas_cotizacion">
                <thead class="text-center">
                    <tr>
                        <th scope="col">Cantidad</th>
                        <th scope="col">Unidad</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">Valor unitario</th>
                        <th scope="col">Acción</th>
                    </tr>
                </thead>
                <tbody id="partidas_cotizacion">
                    <tr id="partidas_vacias">
                        <td colspan="5" class="text-center contactos-muted py-4">Aún no hay partidas. Utiliza “Agregar partida” para comenzar.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Totales -->
        <div class="row justify-content-end mt-4">
            <!-- Tabla de Totales -->
            <div class="col-12 col-lg-5 p-4 totales">
                <div class="row">
                    <div class="col text-center">
                        <h3>Totales</h3>
                    </div>
                </div>
                <div class=" text-center ">
                    <table class="text-center">
                        <tbody>
                            <tr class="">
                                <td scope="row">SUBTOTAL</td>
                                <td><input type="number" step="0.01" name="subtotal" id="subtotal" readonly></td>
                            </tr>
                            <tr class="">
                                <td scope="row">IVA</td>
                                <td><input type="number" step="0.01" name="iva" id="iva" readonly></td>
                            </tr>
                            <tr class="">
                                <td scope="row">TOTAL</td>
                                <td><input type="number" step="0.01" name="total" id="total" readonly></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Tabla de Totales -->

            

        </div>
        <!-- Totales --> 
    </div>
    <!-- Seccion Cotizacion -->

    </form>                   



</div>





<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- SELECT2 combobox -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="<?= $prefijoRuta ?>js/cotizaciones-status.js?v=20261003-1"></script>
<script src="<?= $prefijoRuta ?>js/cotizacion.js?v=20261006-1"></script>

<?php  require (__DIR__ . "/../construct/footer.html")   ?>
