<?php  require ("construct/header.html")   ?>
<?php  require ("querys/query_all_contactos.php")  ?>


<div class="container mt-5 mb-5 contain shadow-lg">
     <!-- titulo  -->
     <div class="row">
        <div class="col text-center mt-3 mb-5">
            <h5><i class="bi bi-cash-coin" id="ico_coti"></i></h5>
            <h1>Cotizaciones</h1>
        </div>  
    </div>
    <!-- titulo  -->

    <form action="fpdf/cotizacionPDF.php" method="POST" target="_blank" id="form_cotizacion">
    
    
    <!-- boton submit del formulario -->
    <div class="row">
        <div class="d-grid gap-2 col-4 mx-auto mb-4 ">
            <button name="" id="btn_guardar"  type="submit" class="btn btn-success"> GUARDAR Y VER PDF</button>                
        </div>
    </div>
    <!-- boton submit del formulario -->


    <!-- Sección Control de Cotizacion -->
    <div class="row">
        <div class="col border-top ">
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
        <div class="row">

            <!-- col de datos cliente -->
                <div class="col-12 col-lg-6 ">
                    <div class="row text-center mb-3">
                        <h3>Datos del Cliente</h3>
                    </div>

                    <!-- Select Contacto -->
                    <div class="row text-center mb-3">
                        <div class="col  mb-4 ">
                            <select class=" form-control" name="select_contacto" id="select_contacto" onchange="selectContacto()">

                                <option value"">Seleccionar Contacto</option>
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

            <!-- col de tabla productos -->
                <div class="col-12 col-lg-6">

                    <div class="row text-center mb-4">
                        <h3>Tabla de Productos y Servicios</h3>
                    </div>

                    <div class="row " >
                        <div class="col p-3" >
                             <div class="col" style="overflow-x: auto; overflow-y: auto;">
                                <table class="table" style="width: 900px;" >
                                    <thead>
                                        <tr>
                                            <th scope="col">Descripción</th>
                                            <th scope="col">Precio Distribuidor</th>
                                            <th scope="col">Precio Público</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="">
                                            <td scope="row">Reparación y Ajuste con pesas patrón a básculas de mediano alcance en Cerro El Gavilán 302-12 col. Ex Hacienda Santan querétaro Qro. El equipo de entrega al día siguiente. Las refacciones se cotizan por separado.</td>
                                            <td>0</td>
                                            <td>$ 950.00</td>
                                        </tr>
                                        <tr class="">
                                            <td scope="row">Reparación y Ajuste con pesas patrón a básculas de mediano alcance en instalaciones del cliente. Las refacciones se cotizan por separado.</td>
                                            <td>0</td>
                                            <td>$ 1300.00</td>
                                        </tr>
                                        <tr class="">
                                            <td scope="row">Mantenimiento preventivo y ajuste con pesas patrón para básculas de mediano alcance. Realizamos pruebas de funcionamiento y pesaje, medición de batería, revisión del adaptador de corriente y ajuste de topes de seguridad. Incluye informe de pruebas metrológicas en PDF y la instalación de componentes.</td>
                                            <td>0</td>
                                            <td>$ 1300.00</td>
                                        </tr>
                                    </tbody>
                                </table>
                             </div>
                                    
                        </div>
                    </div> 
                                
                </div>                                                                                                              
            <!-- col de tabla productos -->

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
                        <td><textarea  name="f1_descrip" id="" cols="60" rows="4" style="resize: none;" maxlength="260" placeholder="Máximo 250 caracteres"></textarea></td>

                        <!-- ingresar Valor Unitario -->
                        <td>
                            <!-- input valor unitario cliente -->
                            <label for="f1_valUnit">Precio Cliente</label>
                            <input type="number"  step="0.01" name="f1_valUnit" id="f1_valUnit" oninput="operacionTotalF1()"> <br> 

                            <!-- input vslor unitario distribuidor -->
                            <label for="f1_unitario_dist">Precio Distribuidor</label>
                            <input type="number" step="0.01" name="f1_unitario_dist" id="f1_unitario_dist" oninput="operacionTotalF1()">  
                        </td>

                        <!-- ingresar Total -->
                        <td>
                            <!-- input total cliente -->
                            <label for="f1_total">Total Cliente</label>
                            <input type="number" step="0.01" name="f1_total" id="f1_total"> <br> 

                            <!-- input total distribuidor -->
                            <label for="f1_total_dist">Total Distribuidor</label>
                            <input type="number" step="0.01" name="f1_total_dist" id="f1_total_dist">  
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
                            <!-- input valor unitario Cliente -->
                            <label for="f2_valUnit">Precio Cliente</label>
                            <input type="number" step="0.01" name="f2_valUnit" id="f2_valUnit" oninput="operacionTotalF2()"> <br>

                            <!-- input valor unitario distribuidor -->
                            <label for="f2_unitario_dist">Precio Distribuidor</label>
                            <input type="number" step="0.01" name="f2_unitario_dist" id="f2_unitario_dist" oninput="operacionTotalF2()"> 
                        </td>

                        <!-- ingresar Total -->
                        <td>
                            <!-- input total cliente -->
                            <label for="f2_total">Total Cliente</label>
                            <input type="number" step="0.01" name="f2_total" id="f2_total"> <br> 

                            <!-- input total distribuidor -->
                            <label for="f2_total_dist">Total Distribuidor</label>
                            <input type="number" step="0.01" name="f2_total_dist" id="f2_total_dist"> 
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
                            <!-- input valor unitario Cliente -->
                            <label for="f3_valUnit">Precio Cliente</label>
                            <input type="number" step="0.01" name="f3_valUnit" id="f3_valUnit" oninput="operacionTotalF3()"> <br>

                            <!-- input valor unitario distribuidor -->
                            <label for="f3_unitario_dist">Precio Distribuidor</label>
                            <input type="number" step="0.01" name="f3_unitario_dist" id="f3_unitario_dist" oninput="operacionTotalF3()">
                        </td>

                        <!-- ingresar Total -->
                        <td>
                            <!-- input total cliente -->
                            <label for="f3_total">Total Cliente</label>
                            <input type="number" step="0.01" name="f3_total" id="f3_total"> <br> 

                            <!-- input total distribuidor -->
                            <label for="f3_total_dist">Total Distribuidor</label>
                            <input type="number" step="0.01" name="f3_total_dist" id="f3_total_dist">
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
                            <!-- input valor unitario Cliente -->
                            <label for="f4_valUnit">Precio Cliente</label>
                            <input type="number" step="0.01" name="f4_valUnit" id="f4_valUnit" oninput="operacionTotalF4()"> <br>

                            <!-- input valor unitario distribuidor -->
                            <label for="f4_unitario_dist">Precio Distribuidor</label>
                            <input type="number" step="0.01" name="f4_unitario_dist" id="f4_unitario_dist" oninput="operacionTotalF4()">
                        </td>

                        <!-- ingresar Total -->
                        <td>
                            <!-- input total cliente -->
                            <label for="f4_total">Total Cliente</label>
                            <input type="number" step="0.01" name="f4_total" id="f4_total"> <br> 

                            <!-- input total distribuidor -->
                            <label for="f4_total_dist">Total Distribuidor</label>
                            <input type="number" step="0.01" name="f4_total_dist" id="f4_total_dist">
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
        <div class="row  mt-5 ">

            <!--Tabla de Utilidad -->
            <div class="col-lg-4  shadow-lg p-4  totales">
                <div class="row">
                    <div class="col text-center">
                        <h3>Utilidad</h3>
                    </div>
                </div>
                <div class=" text-center ">
                    <table class="text-center">
                        <tbody>
                            <tr class="">
                                <td scope="row">SUBUTILIDAD</td>
                                <td><input type="number" step="0.01" name="subutilidad" id="subutilidad"></td>
                            </tr>
                            <tr class="">
                                <td scope="row">OPERACIÓN</td>
                                <td><input type="number" step="0.01" name="gastos_operacion" id="gastos_operacion" oninput="resultUtilidad()"></td>
                            </tr>
                            <tr class="">
                                <td scope="row">TOTAL</td>
                                <td><input type="number" step="0.01" name="total_utilidad" id="total_utilidad"></td>
                            </tr>
                            <tr class="">
                                <td scope="row">COSTOS</td>
                                <td><input type="number" step="0.01" name="costos" id="costos"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!--Tabla de Utilidad -->
            

            <!-- Tabla de Totales Distribuidor -->
            <div class="col-lg-4  shadow-lg p-4  totales"  >
                <div class="row">
                    <div class="col text-center">
                        <h3>Totales Distribuidor</h3>
                    </div>
                </div>
                <div class=" text-center ">
                    <table class="text-center">
                        <tbody>
                            <tr class="">
                                <td scope="row">SUBTOTAL</td>
                                <td><input type="number" step="0.01" name="subtotal_dist" id="subtotal_dist"></td>
                            </tr>
                            <tr class="">
                                <td scope="row">IVA</td>
                                <td><input type="number" step="0.01" name="iva_dist" id="iva_dist"></td>
                            </tr>
                            <tr class="">
                                <td scope="row">TOTAL</td>
                                <td><input type="number" step="0.01" name="total_dist" id="total_dist"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Tabla de Totales Distribuidor -->


            <!-- Tabla de Totales Cliente -->
            <div class="col-lg-4  shadow-lg p-4  totales"  >
                <div class="row">
                    <div class="col text-center">
                        <h3>Totales Cliente</h3>
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
            <!-- Tabla de Totales Cliente -->

            

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
                    
                    <!-- nota 7 text area-->
                    <div class="form-check mb-5 col-sm-8 col-lg-12">
                        <!-- <input class="form-check-input" type="checkbox" value="" id="" /> -->
                        <label class="form-check-label" for="">Ingresar Texto (máximo 85 caracteres)</label><br>
                        <textarea class="col-12" name="notas7" id="notas7" cols="50" rows="3" style="resize: none;" maxlength="85"></textarea>
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
