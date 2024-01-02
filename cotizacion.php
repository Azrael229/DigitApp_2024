<?php  require ("construct/header.html")   ?>
<?php  require ("querys/query_all_contactos.php")  ?>


<div class="container mt-5 mb-5 contain shadow-lg">
     <!-- titulo  -->
     <div class="row">
        <div class="col text-center mt-3 mb-5">
            <h1>Cotizaciones</h1>
        </div>  
    </div>
    <!-- titulo  -->

    <form action="fpdf/cotizacionPDF.php" method="POST" target="_blank" id="form_cotizacion">
    <!-- boton submit del formulario -->
    <div class="row">
        <div class="d-grid gap-2 col-4 mx-auto mb-4 ">
            <button name="" id="btn_guardar"  type="submit" class="btn btn-success"> GUARDAR COTIZACION Y VER PDF</button>                
        </div>
    </div>
    <!-- boton submit del formulario -->


    <!-- Sección Control de Cotizacion -->
    <div class="row">
        <div class="col border-top ">
            <div class="row mt-5 mb-5">
                <div class="col-lg-4 mb-4">
                    <label >No. de Cotización</label>
                    <input type="text" id="numero_coti" name="numero_coti">
                </div>
                <div class="col-4">
                    <label >Fecha</label>
                    <input type="date" name="coti_fecha">
                </div>
                <div class="col-4">
                    <label >Fecha Vigencia</label>
                    <input type="date" name="coti_vigencia">
                </div>
            </div>
        </div>
    </div>
    <!-- Sección Control de Cotizacion -->
    

    <!-- Seccion datos del cliente -->
    <div class="row">
        <div class="row text-center mb-3">
            <h3>Datos del Cliente</h3>
        </div>

        <!-- Select Contacto -->
        <div class="row text-center mb-3">
            <div class="col-5 mb-4">
                <select class="select form-control" name="select_contacto" id="select_contacto" onchange="selectContacto()">

                    <option value"">Seleccionar Contacto</option>
                    <?php foreach($result_contactos as $fila):  ?>
                    <option value="<?php echo $fila['id']; ?>"> <?php echo $fila['nombre']; ?>  </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <!-- Select Contacto -->


        <!-- Datos contacto -->
        <div class="col-lg-6">
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
        <div class="col-lg-6 ">
            <div class="row">
                <div class="col-3 ">
                    <label>Empresa</label>
                </div>
                <div class="col">
                    <textarea name="nombre_empresa" id="nombre_empresa" cols="40" rows="1" style="resize: none;" ></textarea>
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
    <!-- Seccion datos del cliente -->
                        


    <!-- Seccion Cotizacion -->
    <div class="row border-top  p-4">
        <!-- titulo -->
        <div class="row text-center mb-4 mt-4">
            <h3>Cotización</h3>
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
                        <th scope="col">Descripcion</th>
                        <th scope="col">Valor Unitario</th>
                        <th scope="col">Total</th>
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
                        <td><textarea name="f1_descrip" id="" cols="60" rows="4" style="resize: none;" maxlength="260"></textarea></td>
                        <!-- ingresar Valor Unitario -->
                        <td><input type="number" step="0.01" name="f1_valUnit" id="f1_valUnit" oninput="operacionTotalF1()"></td>
                        <!-- ingresar Total -->
                        <td><input type="number" step="0.01" name="f1_total" id="f1_total"></td>
                    </tr>
                    <!-- fila 1 -->

                    <!-- fila 2 -->
                    <tr>
                        <!-- ingresar Cantidad -->
                        <td scope="row"><input name="f2_cant" id="f2_cant"  type="number" Class="col-9" oninput="operacionTotalF2()"></td>
                        <!-- ingresar Unidad -->
                        <td>
                            <select class="form-select form-select-sm" name="f2_unidad"id="">       
                                <option value="">selecciona</option>
                                <option value="Servicio">Servicio</option>
                                <option value="Pieza">Pieza</option>
                            </select>
                        </td>
                        <!-- ingresar Descripcion -->
                        <td><textarea name="f2_descrip" id="" cols="60" rows="4" style="resize: none;" maxlength="260"></textarea></td>
                        <!-- ingresar Valor Unitario -->
                        <td><input type="number" step="0.01" name="f2_valUnit" id="f2_valUnit" oninput="operacionTotalF2()"></td>
                        <!-- ingresar Total -->
                        <td><input type="number" step="0.01" name="f2_total" id="f2_total"></td>
                    </tr>
                    <!-- fila 2 -->

                    <!-- fila 3 -->
                    <tr>
                        <!-- ingresar Cantidad -->
                        <td scope="row"><input name="f3_cant" id="f3_cant" type="number" Class="col-9" oninput="operacionTotalF3()"></td>
                        <!-- ingresar Unidad -->
                        <td>
                            <select class="form-select form-select-sm" name="f3_unidad"id="">
                                <option value="">selecciona</option>
                                <option value="Servicio">Servicio</option>
                                <option value="Pieza">Pieza</option>
                            </select>
                        </td>
                        <!-- ingresar Descripcion -->
                        <td><textarea name="f3_descrip" id="" cols="60" rows="4" style="resize: none;" maxlength="260"></textarea></td>
                        <!-- ingresar Valor Unitario -->
                        <td><input type="number" step="0.01" name="f3_valUnit" id="f3_valUnit" oninput="operacionTotalF3()"></td>
                        <!-- ingresar Total -->
                        <td><input type="number" step="0.01" name="f3_total" id="f3_total" ></td>
                    </tr>
                    <!-- fila 3 -->

                    <!-- fila 4 -->
                    <tr>
                        <!-- ingresar Cantidad -->
                        <td scope="row"><input name="f4_cant" id="f4_cant" type="number" Class="col-9" oninput="operacionTotalF4()"></td>
                        <!-- ingresar Unidad -->
                        <td>
                            <select class="form-select form-select-sm" name="f4_unidad"id=""> 
                                <option value="">selecciona</option>
                                <option value="Servicio">Servicio</option>
                                <option value="Pieza">Pieza</option>
                            </select>
                        </td>
                        <!-- ingresar Descripcion -->
                        <td><textarea name="f4_descrip" id="" cols="60" rows="4" style="resize: none;" maxlength="260"></textarea></td>
                        <!-- ingresar Valor Unitario -->
                        <td><input type="number" step="0.01" name="f4_valUnit" id="f4_valUnit" oninput="operacionTotalF4()"></td>
                        <!-- ingresar Total -->
                        <td><input type="text" step="0.01" name="f4_total" id="f4_total"></td>
                    </tr>
                    <!-- fila 4 -->
                                                                                 
                </tbody>
            </table>
        </div>
        <!-- Tabla articulos -->

        <!-- Totales -->
        <div class="row  mt-5 justify-content-end ">

            <!-- tiempo de entrga -->
            <div class="col-lg-6">
                <div class="row mb-5 ">
                    <label for="tiempo_entrega">Tiempo de entrega (días habiles):</label>
                    <input type="number" name="tiempo_entrega" id="tiempo_entrega"class="col-8 col-lg-3">
                </div>
            </div>
            <!-- tiempo de entrga -->

            <div class="col-lg-4 ">
                <div class="table-responsive text-end ">
                    <table class="table table-secondary table-sm ">
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
        </div>
        <!-- Totales --> 
    </div>
    <!-- Seccion Cotizacion -->

    <!-- Seccion Notas  -->
    <div class="row border-top mt-4 p-3">
      <div class="col">
            <div class="row text-center mt-5 mb-4">
                <h3>Notas y Condiciones</h3>
            </div>
            <div class="row text-center mb-5">
                <h3>Máximo 3 Notas (forma de pago y garantía)</h3>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10 ">
                    <!-- nota 1 -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="Pago anticipado mediante transferencia bancaria." id="nota1" name="notas1"/>
                        <label class="form-check-label" for=""> Pago anticipado mediante transferencia bancaria</label>
                    </div>
                    <!-- nota 6 -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="Pago anticipado 60% mediante transferencia bancaria." id="nota6" name="notas6"/>
                        <label class="form-check-label" for=""> Pago anticipado 60% mediante transferencia bancaria.</label>
                    </div>
                    <!-- nota 2 -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="Garantía de 6 meses por defectos de fabricación." id=""  name="notas2" />
                        <label class="form-check-label" for=""> Garantía de 6 meses por defectos de fabricación</label>
                    </div>
                    <!-- nota 3 -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="Garantía de 5 días en mano de obra." id=""  name="notas3"/>
                        <label class="form-check-label" for=""> Garantía de 5 días en mano de obra. </label>
                    </div>
                    <!-- nota 4 -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="No incluye costos de envío." id=""  name="notas4" />
                        <label class="form-check-label" for=""> No incluye costos de envío. </label>
                    </div>
                    
                    <!-- nota 7 -->
                    <div class="form-check mb-5 col-sm-8 col-lg-12">
                        <!-- <input class="form-check-input" type="checkbox" value="" id="" /> -->
                        <label class="form-check-label" for="">Ingresar Texto (máximo 85 caracteres)</label><br>
                        <textarea name="notas7" id="notas7" cols="50" rows="3" style="resize: none;" maxlength="85"></textarea>
                    </div>
                
                    <div class="form-check mb-4 ">
                        <input class="form-check-input" type="checkbox" value="" id=""  name="notas" disabled/>
                        <label class="form-check-label" for=""> La entrega de los productos indicados en la presente cotizacion se realiza en la dirección indicada en la sección Datos del Cliente , no incluyen ningun tipo de instalación.</label>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="" id=""disabled />
                        <label class="form-check-label" for=""> Para abastecer los productos y/o servicios de la presente cotizacion se debe cubrir el 100% de la cantidad total indicada. </label>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="" id=""disabled />
                        <label class="form-check-label" for=""> Para abastecer los productos y/o servicios de la presente cotizacion se requiere Orden de Compra. </label>
                    </div>
                    <!-- nota 5 -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="Todas nuestras basculas, balanzas y sistemas de pesaje son previamente revisados , configurados y ajustados con pesas patrón para que funcione dentro de sus errores máximos tolerados. (NOM-010-SCFI-1994)" id=""  name="notas5" disabled />
                        <label class="form-check-label" for=""> Todas nuestras basculas, balanzas y sistemas de pesaje son previamente revisados , configurados y ajustados con pesas patrón para que funcione dentro de sus errores máximos tolerados. (NOM-010-SCFI-1994)  </label>
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

<script src="js/cotizacion.js"></script>

<?php  require ("construct/footer.html")   ?>
