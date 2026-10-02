<?php $prefijoRuta = '../'; ?>
<?php  require (__DIR__ . "/../construct/header.php")   ?>
<?php  require (__DIR__ . "/../backend/contactos/query_all_contactos.php")  ?>


<div class="container mt-5 mb-5 contain shadow-lg cotizacion-form-page">
     <!-- titulo  -->
     <div class="row align-items-center contacto-form-header g-3 pt-3 mb-4">
        <div class="col">
            <p class="contactos-kicker mb-1">Gestión comercial</p>
            <h1 class="h2 mb-0"><i class="bi bi-cash-coin" id="ico_coti" aria-hidden="true"></i> Nueva cotización</h1>
        </div>  
    </div>
    <!-- titulo  -->

    <form action="<?= $prefijoRuta ?>fpdf/cotizacionPDF.php" method="POST" target="_blank" id="form_cotizacion" class="cotizacion-form">
    
    
    <!-- boton submit del formulario -->
    <div class="row">
        <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mb-4">
            <a href="tablaCotizaciones.php" class="btn btn-secondary">Cancelar</a>
            <button id="btn_guardar" type="submit" class="btn btn-success"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Guardar y ver PDF</button>
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
                        <input type="text" id="numero_coti" name="numero_coti">
                    </div>
                </div>
            <!-- fila de fecha de cotizacion -->
                <div class="row mb-4">
                    <div class="col col-lg-2 ">
                        <label >Fecha</label>
                    </div>
                    <div class="col  ">
                        <input type="date" name="coti_fecha">
                    </div>
                </div>
            <!-- fila de vigencia de cotizacion -->
                <div class="row mb-5">
                    <div class="col col-lg-2 ">
                        <label >Fecha Vigencia</label>
                    </div>
                    <div class="col ">
                        <input type="date" name="coti_vigencia">
                    </div>
                </div>
            <!-- fin de las filas -->
        </div>
    </div>
    <!-- Sección Control de Cotizacion -->
    

    <!-- Seccion datos del cliente -->
        <div class="row cotizacion-form-section">

            <!-- col de datos cliente -->
                <div class="col-12">
                    <div class="cotizacion-section-heading mb-4">
                        <h2 class="h4 mb-1">Datos del cliente</h2>
                        <p class="contactos-muted mb-0">Selecciona el contacto y confirma los datos que se incluirán en la cotización.</p>
                    </div>

                    <!-- Select Contacto -->
                    <div class="row text-center mb-3">
                        <div class="col  mb-4 ">
                            <select class=" form-control" name="select_contacto" id="select_contacto" onchange="selectContacto()">

                                <option value="">Seleccionar Contacto</option>
                                <?php foreach($result_contactos as $fila):  ?>
                                <option value="<?php echo $fila['id']; ?>"> <?php echo $fila['nombre']; ?>  </option>
                                <?php endforeach; ?>
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
                                    <textarea name="nombre_contacto" id="nombre_contacto" cols="40" rows="1" style="resize: none;" ></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-3 ">
                                    <label>Teléfono</label>
                                </div>
                                <div class="col">
                                    <textarea name="cel_contacto" id="cel_contacto" cols="40" rows="1" style="resize: none;" ></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-3 ">
                                    <label>Correo</label>
                                </div>
                                <div class="col">
                                    <textarea name="correo_contacto" id="correo_contacto" cols="40" rows="1" style="resize: none;" ></textarea>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-3 ">
                                    <label>Departamento</label>
                                </div>
                                <div class="col">
                                    <textarea name="depto_contacto" id="depto_contacto" cols="40" rows="1" style="resize: none;" ></textarea>
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
                                    <textarea name="nombre_empresa" id="nombre_empresa" cols="40" rows="1" style="resize: none;" ></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-3 ">
                                    <label>Dirección a utilizar</label>
                                </div>
                                <div class="col">
                                    <select class="form-control" name="tipo_direccion_empresa" id="select_direccion">
                                        <option value="">Selecciona un contacto primero</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-3 ">
                                    <label>Dirección</label>
                                </div>
                                <div class="col">
                                    <textarea name="dir_empresa" id="dir_empresa" cols="40" rows="3" style="resize: none;" ></textarea>
                                </div>
                            </div>
                        </div>
                    <!-- Datos Empresa -->
                </div>
            <!-- col de datos cliente -->

        </div>
    <!-- Seccion datos del cliente -->
                        


    <!-- Seccion Cotizacion -->
    <div class="row cotizacion-form-section">
        <!-- titulo -->
        <div class="cotizacion-section-heading mb-4">
            <h2 class="h4 mb-1">Conceptos de la cotización</h2>
            <p class="contactos-muted mb-0">Captura hasta cuatro conceptos con su cantidad y valor unitario.</p>
        </div>  
        <!-- titulo -->
        
        <!-- Tabla articulos -->
        <div class="table-responsive mb-4">
            <table class="table table-secondary">
                <!-- encabezados -->
                <thead class="text-center">
                    <tr>
                        <th scope="col">Cantidad</th>
                        <th scope="col">Unidad</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">Valor unitario</th>
                    </tr>
                </thead>
                <!-- encabezados -->

                <tbody>
                    <!-- fila 1 -->
                    <tr>
                        <!-- ingresar Cantidad -->
                        <td scope="row"><input name="f1_cant" id="f1_cant" type="number" Class="col-9" oninput="operacionTotalF1()"></td>

                        <!-- ingresar Unidad -->
                        <td>
                            <select class="form-select form-select-sm" name="f1_unidad"id="">
                                <option value="">selecciona</option>
                                <option value="Servicio">Servicio</option>
                                <option value="Pieza">Pieza</option>
                            </select>
                        </td>

                        <!-- ingresar Descripcion -->
                        <td><textarea  name="f1_descrip" id="" cols="60" rows="4" style="resize: none;" maxlength="260" placeholder="Máximo 250 caracteres"></textarea></td>

                        <!-- ingresar Valor Unitario -->
                        <td>
                            <label class="visually-hidden" for="f1_valUnit">Valor unitario del concepto 1</label>
                            <input type="number" step="0.01" min="0" name="f1_valUnit" id="f1_valUnit" oninput="operacionTotalF1()">
                            <input type="hidden" name="f1_total" id="f1_total" value="0.00">
                        </td>
                    </tr>
                    <!-- fila 1 -->

                    <!-- fila 2 -->
                    <tr>
                        <!-- ingresar Cantidad -->
                        <td scope="row">
                            <input name="f2_cant" id="f2_cant"  type="number" Class="col-9" oninput="operacionTotalF2()">
                        </td>

                        <!-- ingresar Unidad -->
                        <td>
                            <select class="form-select form-select-sm" name="f2_unidad"id="">       
                                <option value="">selecciona</option>
                                <option value="Servicio">Servicio</option>
                                <option value="Pieza">Pieza</option>
                            </select>
                        </td>

                        <!-- ingresar Descripcion -->
                        <td>
                            <textarea name="f2_descrip" id="" cols="60" rows="4" style="resize: none;" maxlength="260" placeholder="Máximo 250 caracteres"></textarea>
                        </td>

                        <!-- ingresar Valor Unitario -->
                        <td>
                            <label class="visually-hidden" for="f2_valUnit">Valor unitario del concepto 2</label>
                            <input type="number" step="0.01" min="0" name="f2_valUnit" id="f2_valUnit" oninput="operacionTotalF2()">
                            <input type="hidden" name="f2_total" id="f2_total" value="0.00">
                        </td>
                    </tr>
                    <!-- fila 2 -->

                    <!-- fila 3 -->
                    <tr>
                        <!-- ingresar Cantidad -->
                        <td scope="row">
                            <input name="f3_cant" id="f3_cant" type="number" Class="col-9" oninput="operacionTotalF3()">
                        </td>

                        <!-- ingresar Unidad -->
                        <td>
                            <select class="form-select form-select-sm" name="f3_unidad"id="">
                                <option value="">selecciona</option>
                                <option value="Servicio">Servicio</option>
                                <option value="Pieza">Pieza</option>
                            </select>
                        </td>

                        <!-- ingresar Descripcion -->
                        <td>
                            <textarea name="f3_descrip" id="" cols="60" rows="4" style="resize: none;" maxlength="260" placeholder="Máximo 250 caracteres"></textarea>
                        </td>

                        <!-- ingresar Valor Unitario -->
                        <td>
                            <label class="visually-hidden" for="f3_valUnit">Valor unitario del concepto 3</label>
                            <input type="number" step="0.01" min="0" name="f3_valUnit" id="f3_valUnit" oninput="operacionTotalF3()">
                            <input type="hidden" name="f3_total" id="f3_total" value="0.00">
                        </td>
                    </tr>
                    <!-- fila 3 -->

                    <!-- fila 4 -->
                    <tr>
                        <!-- ingresar Cantidad -->
                        <td scope="row">
                            <input name="f4_cant" id="f4_cant" type="number" Class="col-9" oninput="operacionTotalF4()">
                        </td>

                        <!-- ingresar Unidad -->
                        <td>
                            <select class="form-select form-select-sm" name="f4_unidad"id=""> 
                                <option value="">selecciona</option>
                                <option value="Servicio">Servicio</option>
                                <option value="Pieza">Pieza</option>
                            </select>
                        </td>
                        <!-- ingresar Descripcion -->
                        <td>
                            <textarea name="f4_descrip" id="" cols="60" rows="4" style="resize: none;" maxlength="260" placeholder="Máximo 250 caracteres"></textarea>
                        </td>

                        <!-- ingresar Valor Unitario -->
                        <td>
                            <label class="visually-hidden" for="f4_valUnit">Valor unitario del concepto 4</label>
                            <input type="number" step="0.01" min="0" name="f4_valUnit" id="f4_valUnit" oninput="operacionTotalF4()">
                            <input type="hidden" name="f4_total" id="f4_total" value="0.00">
                        </td>
                    </tr>
                    <!-- fila 4 -->
                                                                                 
                </tbody>
            </table>
        </div>
        <!-- Tabla articulos -->


        <!--seccion tiempo de entrga -->
        <div class="row">
            <div class="col col-lg-3">
                <div class="row mb-5 p-2">
                    <label for="tiempo_entrega">Tiempo de entrega (días habiles):</label>
                    <input type="number" name="tiempo_entrega" id="tiempo_entrega"class="col-8 col-lg-10">
                </div>
            </div>
        </div>
        <!--seccion tiempo de entrga -->


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
                                <td><input type="number" step="0.01" name="subtotal" id="subtotal"></td>
                            </tr>
                            <tr class="">
                                <td scope="row">IVA</td>
                                <td><input type="number" step="0.01" name="iva" id="iva"></td>
                            </tr>
                            <tr class="">
                                <td scope="row">TOTAL</td>
                                <td><input type="number" step="0.01" name="total" id="total"></td>
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

    <!-- Seccion Notas  -->
    <div class="row cotizacion-form-section">
      <div class="col">
            <div class="cotizacion-section-heading mb-2">
                <h2 class="h4 mb-1">Notas y condiciones</h2>
                <p class="contactos-muted mb-0">Selecciona hasta tres condiciones o escribe una indicación personalizada.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10 ">
                    <!-- nota 1 -->
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" value="Pago anticipado mediante transferencia bancaria." id="nota1" name="notas1"/>
                        <label class="form-check-label" for="nota1">Pago anticipado mediante transferencia bancaria</label>
                    </div>
                    <!-- nota 6 -->
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" value="Pago anticipado 60% mediante transferencia bancaria." id="nota6" name="notas6"/>
                        <label class="form-check-label" for="nota6">Pago anticipado 60% mediante transferencia bancaria.</label>
                    </div>
                    <!-- nota 2 -->
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" value="Garantía de 6 meses por defectos de fabricación." id="nota2" name="notas2" />
                        <label class="form-check-label" for="nota2">Garantía de 6 meses por defectos de fabricación</label>
                    </div>
                    <!-- nota 3 -->
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" value="Garantía de 5 días en mano de obra." id="nota3" name="notas3"/>
                        <label class="form-check-label" for="nota3">Garantía de 5 días en mano de obra.</label>
                    </div>
                    <!-- nota 4 -->
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" value="No incluye costos de envío." id="nota4" name="notas4" />
                        <label class="form-check-label" for="nota4">No incluye costos de envío.</label>
                    </div>
                    
                    <!-- nota 7 text area-->
                    <div class="mb-2">
                        <label class="form-label" for="notas7">Ingresar texto (máximo 85 caracteres)</label>
                        <textarea class="form-control" name="notas7" id="notas7" rows="3" maxlength="85"></textarea>
                    </div>
                    
                </div>
            </div>
      </div>                  
    </div> 
    </form>                   
    <!-- Seccion Notas  -->



</div>





<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- SELECT2 combobox -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="<?= $prefijoRuta ?>js/cotizacion.js?v=20261002-1"></script>

<?php  require (__DIR__ . "/../construct/footer.html")   ?>
