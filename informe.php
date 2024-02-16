<?php  require ("construct/header.html")   ?>
<?php  require ("querys/query_all_empresas.php")  ?>



<div class="container mt-5 mb-5 contain shadow-lg ">
        <form action="fpdf/informePDF.php" target="_blank" method="post">
        <!-- titulo  -->
            <div class="row">
                <div class="col text-center mt-3 mb-5">
                    <h3><i class="bi bi-patch-check" id="ico_informe"></i></h3>
                    <h1>Informe de Pruebas Metrológicas</h1>
                </div>
            </div>
        <!-- titulo  -->

        <!-- boton submit del formulario -->
        <div class="row">
            <div class="d-grid gap-2 col-4 mx-auto mb-4 ">
                <button name="" id="" type="submit" class="btn btn-success"> GENERAR PDF</button>                
            </div>
        </div>
        <!-- boton submit del formulario -->

        <!-- Sección Control de Informes -->
            <div class="row">
                <div class="col border-top ">
                    <div class="row mt-5 mb-5">
                        <div class="col-4">
                            <label >Fecha</label>
                            <input type="date" name="inf_fecha">
                        </div>
                        <div class="col-6 ">
                            <label >No. de Informe</label>
                            <input type="text" id="input_num_inf" name="input_num_inf">
                        </div>
                    </div>
                </div>
            </div>
        <!-- Sección Control de Informes -->
        

        <!-- Sección Datos del Cliente -->
            <div class="row">
                <div class="row text-center mb-3">
                    <h3>Datos del Cliente</h3>
                </div>

                <!-- Select Empresas -->
                <div class="row text-center mb-3">
                    <div class="col-5 mb-4">
                        <select class="select form-control" name="select_empresa" id="select_empresa" onchange="selectEmpresa()">

                            <option value"">Seleccionar Cliente</option>
                            <?php foreach($result_empresas as $fila):  ?>
                            <option value="<?php echo $fila['id_e']; ?>"> <?php echo $fila['empresa']; ?>  </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>


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

                <!-- Datos contacto -->
                <div class="col-lg-6">
                    <div class="row ">
                        <div class="col-3">
                            <label>Contacto</label>
                        </div>
                        <div class="col">
                            <textarea name="nombre_contacto" id="nombre_contacto" cols="40" rows="1" style="resize: none;" ></textarea>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-3 ">
                            <label>Correo</label>
                        </div>
                        <div class="col">
                            <textarea name="correo_contacto" id="correo_contacto" cols="40" rows="1" style="resize: none;" ></textarea>
                        </div>
                    </div>
                </div>
                <!-- Datos contacto -->

            </div>
        <!-- Sección Datos del Cliente -->
        

        <!-- Seccion Datos de Instrumento -->
            <div class="row text-center border-top ">
                      
                    <h3>Datos del Instrumento</h3>
                
            </div>

            <div class="row">
                <div class="col  p-3">
                    <div class="row">
                        <div class="col-3 ">
                            <label>Descripción</label>
                        </div>
                        <div class="col">
                            <textarea name="desc_inst" id="desc_inst" cols="40" rows="1" style="resize: none;"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-3 ">
                            <label>Marca</label>
                        </div>
                        <div class="col">
                            <textarea name="marca_inst" id="marca_inst" cols="40" rows="1" style="resize: none;"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-3 ">
                            <label>Modelo</label>
                        </div>
                        <div class="col">
                            <textarea name="modelo_inst" id="modelo_inst" cols="40" rows="1" style="resize: none;"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-3">
                            <label>ID</label>
                        </div>
                        <div class="col">
                            <textarea name="id_inst" id="id_inst" cols="40" rows="1" style="resize: none;"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-3">
                            <label>Serie</label>
                        </div>
                        <div class="col">
                            <textarea name="serie_inst" id="serie_inst" cols="40" rows="1" style="resize: none;"></textarea>
                        </div>
                    </div>
                    <div class="row"> 
                        <div class="col-3">               
                            <label for="" class="mb-3">Unidades</label><br>
                        </div>
                        <div class="col">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="unidad" id="unidadeskg" value="kg">
                                <label class="form-check-label" for="unidadeskg">kg</label>
                            </div>
                            <div class="form-check form-check-inline mb-5">
                                <input class="form-check-input" type="radio" name="unidad" id="unidadesg" value="g">
                                <label class="form-check-label" for="unidadesg">g</label>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="col p-3 ">
                    <div class="row mb-2">
                        <div class="col-3">
                            <label>Capacidad</label>
                        </div>
                        <div class="col">
                            <input type="number" id="max" name="max" step="0.01">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-3">
                            <label>Resolución (d)</label>
                        </div>
                        <div class="col">
                            <input type="number" name="d" id="d" step="0.000001">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-3">
                            <label>División (e)</label>
                        </div>
                        <div class="col">
                            <input type="number" name="e" id="e" step="0.000001">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-3">
                            <label>Min</label>
                        </div>
                        <div class="col">
                            <input type="number" name="min" id="min" step="0.001">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-3">
                            <label>Clase</label>
                        </div>
                        <div class="col">
                            <input type="text" name="clase" id="clase">
                        </div>
                    </div>
                </div>
            </div> 
        <!-- Seccion Datos de Instrumento -->
        </form>

        <!-- boton analizar  -->
        <div class="row">
            <div class="d-grid gap-2 col-4 mx-auto mb-4 ">
                <button name="btn_analizar" id="btn_analizar" type="button" class="btn btn-success"> ANALIZAR</button>                
            </div>
        </div>                       
        <!-- boton analizar  -->
        
        <!-- Seccion EMT y Crgas     -->
            <div class="row">    

                    <!-- Seccion EMT -->
                        <div class="col-12 col-md-6 border-top">

                            <div class="row mt-3 text-center">
                                <h3>Error Máximo Tolerado</h3>
                            </div>

                            <div class="row">
                                <div class="col p-5 ">
                                    <!-- ENCABEZADOS TABLA EMT -->
                                    <div class="row text-center border">
                                        <div class="col-8 border">
                                            <h4>Intervalos (<span class="unidades"></span>)</h4>
                                        </div>
                                        <div class="col">
                                            <h4>EMT ± (<span class="unidades"></span>)</h4>
                                        </div>
                                    </div>
                                    <!-- PRIMER INTERVAOL -->
                                    <div class="row text-center border">
                                        <div class="col-8 border">
                                            <p>
                                                0 a 
                                                <span id="sp_interv1_col2"></span>
                                            </p>
                                        </div>
                                        <div class="col">
                                            <p><span id="emt_1"></span></p>
                                        </div>
                                    </div>
                                    <!-- SEGUNDO INTERVALO -->
                                    <div class="row text-center border">
                                        <div class="col-8 border">
                                            <p>
                                                <span id="sp_interv2_col1"></span>
                                                 a 
                                                <span id="sp_interv2_col2"></span>
                                            </p>
                                        </div>
                                        <div class="col">
                                            <p><span id="emt_2"></span></p>
                                        </div>
                                    </div>
                                    <!-- TERCER INTERVALO -->
                                    <div class="row text-center border">
                                        <div class="col-8 border">
                                            <p>
                                                <span id="sp_interv3_col1"></span>
                                                a 
                                                <span id="sp_interv3_col2"></span>
                                            </p>
                                        </div>
                                        <div class="col">
                                            <p><span id="emt_3"></span></p>
                                        </div>
                                    </div>
                                    <!-- FIN DE LA TABLA EMT -->
                                </div>
                            </div>

                        </div>
                    <!-- Seccion EMT -->


                    <!-- Seccion Pesas Patrón y Cargas -->
                        <div class="col-12 col-md-6 border-top">

                            <div class="row mb-3 mt-3 text-center">
                                <h3>Patrones y Cargas</h3>
                            </div>

                            <div class="row">
                                <div class="col p-5">
                                    <div class="row">
                                        <div class="col-4 ">
                                            <label>Cantidad Pesas</label>
                                        </div>
                                        <div class="col">
                                            <textarea name="" id="" cols="20" rows="1" style="resize: none;"></textarea>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-4 ">
                                            <label>Valor Nominal</label>
                                        </div>
                                        <div class="col">
                                            <textarea name="" id="" cols="20" rows="1" style="resize: none;"></textarea>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-4 ">
                                            <label>Valor de Carga</label>
                                        </div>
                                        <div class="col">
                                            <textarea name="" id="" cols="20" rows="1" style="resize: none;"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    <!-- Seccion Pesas Patrón y Cargas -->
            </div>
        <!-- Seccion EMT y Crgas  -->


        <!-- Seccion repetibilidad y Excentricidad-->
            <div class="row">

                <!-- Repetibilidad -->
                    <div class="col-12 col-md-6 border-top">

                        <div class="row mb-2 mt-3 text-center">
                            <!-- titulo -->
                            <h3>Repetibilidad</h3>

                            <!-- Cargas -->
                            <h5>50% de MAX = <span id="sp_repe"></span> ( <span class="unidades"></span> )</h5>

                            
                        </div>

                        <!-- puntos -->
                        <div class="row">
                            <div class="col p-5">
                                <div class="row">
                                    <div class="col-3 text-center">
                                        <h4>1</h4>
                                    </div>
                                    <div class="col">
                                        <input type="number">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-3 text-center">
                                        <h4>2</h4>
                                    </div>
                                    <div class="col">
                                        <input type="number">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-3 text-center">
                                        <h4>3</h4>
                                    </div>
                                    <div class="col">
                                        <input type="number">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-3 text-center">
                                        <h4>4</h4>
                                    </div>
                                    <div class="col">
                                        <input type="number">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-3 text-center">
                                        <h4>5</h4>
                                    </div>
                                    <div class="col">
                                        <input type="number">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- puntos -->

                        <div class="row text-center">
                            <div class="col mb-5">
                                <button class="btn btn-primary col-5">Evaluar</button>
                            </div>
                        </div>
            
                    </div>
                <!-- Repetibilidad -->


                <!-- Excentricidad -->
                    <div class="col-12 col-md-6 border-top">
                        <div class="row mb-5 mt-3 text-center">
                            <h3>Excentricidad</h3>
                            <h5>1/3 de MAX = <span id="sp_exce"></span> ( <span class="unidades"></span> )</h5>

                            <div class="row">
                                <div class="col">
                                    <label for="">Valor de Carga:
                                    <input type="text" class="col-5">
                                    </label>
                                </div>                               
                            </div>
                        </div>

                        <!-- puntos -->
                        <div class="row ">
                            <div class="col-12 col-xl-6 p-4">
                                <div class="row">
                                    <div class="col-2 text-center ">
                                        <h4>1</h4>
                                    </div>
                                    <div class="col text-start">
                                        <input type="number">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-2 text-center">
                                        <h4>2</h4>
                                    </div>
                                    <div class="col text-start">
                                        <input type="number">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-2 text-center">
                                        <h4>3</h4>
                                    </div>
                                    <div class="col text-start">
                                        <input type="number">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-2 text-center">
                                        <h4>4</h4>
                                    </div>
                                    <div class="col text-start">
                                        <input type="number">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-2 text-center">
                                        <h4>5</h4>
                                    </div>
                                    <div class="col text-start">
                                        <input type="number">
                                    </div>
                                </div>

                            </div>

                            <div class="col-12 col-xl-6 p-3 text-center">
                                <img src="imgs/El texto del párrafo.png" alt="" style="height: 200px;">
                            </div>
                        </div>
                        <!-- puntos -->

                    </div>
                <!-- excentricidad -->
            </div>
        <!-- Seccion repetibilidad y Excentricidad--> 
        
        <!-- Seccion Exactitud  -->
            <div class="row ">
                <div class="col border-top">
                    <div class="row mt-4 mb-3 text-center">
                        <h3>Exactitud</h3>
                    </div>

                    <!-- puntos -->
                    <div class="row justify-content-center">

                            <div class="col- p-3">
                                <div class="row row-cols-auto">
                                    <div class="col-1 text-end">
                                        <h4>1</h4>
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number" id="exact1">
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number">
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number" disabled>
                                    </div>
                                </div>

                                <div class="row row-cols-auto">
                                    <div class="col-1 text-end">
                                        <h4>2</h4>
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number" id="exact2">
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number">
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number" disabled>
                                    </div>
                                </div>

                                <div class="row row-cols-auto">
                                    <div class="col-1 text-end">
                                        <h4>3</h4>
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number" id="exact3">
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number">
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number" disabled>
                                    </div>
                                </div>

                                <div class="row row-cols-auto">
                                    <div class="col-1 text-end">
                                        <h4>4</h4>
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number" id="exact4">
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number">
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number" disabled>
                                    </div>
                                </div>

                                <div class="row row-cols-auto">
                                    <div class="col-1 text-end">
                                        <h4>5</h4>
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number" id="exact5">
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number">
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <input class="col-12 col-md-6" type="number" disabled>
                                    </div>
                                </div>
                            </div>
                    </div>
                    <!-- puntos -->
                </div>
                
            </div>
        <!-- Seccion Exactitud  -->

</div>
<!-- container -->


<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- SELECT2 combobox -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="js/informe.js"></script>

<?php  require ("construct/footer.html")   ?>
