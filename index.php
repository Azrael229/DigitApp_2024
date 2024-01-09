
<?php  require ("construct/header.html")   ?>
<?php  require ("querys/query_all_cotizaciones.php")   ?>
<?php  require ("querys/query_all_notas.php")   ?>

  
<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg" ">

    <!-- row de titulo -->
    <div class="row">
        <div class="col text-center mt-3 mb-5">
            <h1>Panel de Administrador</h1>
        </div>
    </div>
    <!-- row de titulo -->

    <!-- row de Tabla cotizaciones -->
    <div class="row border-top justify-content-center ">
        <!-- titulo de tabla  -->
        <div class="row">
            <div class="col text-center mt-3 mb-5">
                <h1>Cotizaciones</h1>
            </div>
        </div>
        <!-- titulo de tabla  -->
        <!-- tabla  -->
        <div class="row">
            <div class="col  table-responsive" style="white-space: nowrap; " >
                <table id="example" class="table table-secondary table-striped" >
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col"></th>
                            <th scope="col">ESTATUS</th>
                            <th scope="col">FECHA</th>
                            <th scope="col">EMPRESA</th>
                            <th scope="col">CONTACTO</th>
                            <th scope="col">IMPORTE</th>
                            <th scope="col">UTILIDAD</th>
                            <th scope="col">COSTOS</th>
                            <th scope="col">DESCARGAR PDF</th>
                            <th scope="col">NUMERO</th>
                            <th scope="col">VIGENCIA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($result_cotizaciones as $row_coti): ?>
                        <tr>
                            <td><?php echo $row_coti['id_coti'] ?></td>
                            <td><?php echo $row_coti['cot_status'] ?></td>
                            <td> 
                                <select name="sel_status_coti" onchange="selStatus(this,<?php echo $row_coti['id_coti'] ?>)">
                                    <option >Seleccionar</option>   
                                    <option value="1" >Espera</option>   
                                    <option value="2" >Aceptada</option>   
                                    <option value="3" >Cancelada</option> 
                                </select>  
                            </td>
                            <td ><?php echo $row_coti['cot_fecha'] ?></td>
                            <td><?php echo $row_coti['cot_empresa'] ?></td>
                            <td><?php echo $row_coti['cot_contacto'] ?></td>
                            <td>$ <?php echo $row_coti['cot_total'] ?></td>
                            <td>$ <?php echo $row_coti['cot_utilidad'] ?></td>
                            <td>$ <?php echo $row_coti['cot_costos'] ?></td>
                            <td><a href="filesPDF/<?php echo $row_coti['cot_archivo'] ?>" download><?php echo $row_coti['cot_archivo'] ?></a></td>
                            <td><?php echo $row_coti['cot_numero'] ?></td>
                            <td><?php echo $row_coti['cot_vigencia'] ?></td>
                        </tr>
                        <?php endforeach;  ?>
                        
                    </tbody>
                </table>
            </div>
            
        </div>
        <!-- tabla  -->
    </div>
    <!-- row de Tabla cotizaciones -->

    <!--Seccion calendario -->
    <div class="row border-top mt-4 justify-content-center">
        <!-- titulo -->
        <div class="row text-center mt-5">
            <h1>Calendario 2024</h1>
        </div>
        <!-- titulo -->

        <!-- calendario cuadricula -->
        <h1 class="text-center mt-4 mb-3">Meses del Año</h1>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-5" >
            <div class="col">
                <div class="month">Enero
                    <?php foreach($res_notas_enero as $nota_enero): ?>
                        <p><i class="bi bi-record-fill" id="dot"></i> <?php echo $nota_enero['registro']   ?>    </p>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col">
                <div class="month">Febrero</div>
            </div>
            <div class="col">
                <div class="month">Marzo</div>
            </div>
            <div class="col">
                <div class="month">Abril</div>
            </div>
            <div class="col">
                <div class="month">Mayo</div>
            </div>
            <div class="col">
                <div class="month">Junio</div>
            </div>
            <div class="col">
                <div class="month">Julio</div>
            </div>
            <div class="col">
                <div class="month">Agosto</div>
            </div>
            <div class="col">
                <div class="month">Septiembre</div>
            </div>
            <div class="col">
                <div class="month">Octubre</div>
            </div>
            <div class="col">
                <div class="month">Noviembre</div>
            </div>
            <div class="col">
                <div class="month">Diciembre</div>
            </div>
        </div>
        <!-- calendario cuadricula -->

        


        <!--Seccion Inputs -->
        <div class="row mt-5 mb-5">

            <!-- col SUBIR NOTAS Y ARCHIVOS -->
            <div class="col col-lg-5  p-4 shadow-lg rounded" id="card_subir_archivos">
                <!-- formulario para subir notas a los meses del calendario -->
                <!-- action="querys/add_nota_calendar.php" method="POST" enctype="multipart/form-data" -->
                <form id="form_notas_calendar" >
                    <!-- fila del SELECT             -->
                    <div class="row mb-4 justify-content-center">
                        <div class="col col-lg-10">
                            <label for="">Selccionar Mes</label>
                            <select name="sel_id_mes" id="" class="form-control" required>
                                <option value="">Mes</option>
                                <option value="1">Enero</option>
                                <option value="2">Febrero</option>
                                <option value="3">Marzo</option>
                                <option value="4">Abril</option>
                                <option value="5">Mayo</option>
                                <option value="6">Junio</option>
                                <option value="7">Julio</option>
                                <option value="8">Agosto</option>
                                <option value="9">Septiembre</option>
                                <option value="10">Octubre</option>
                                <option value="11">Noviembre</option>
                                <option value="12">Diciembre</option>
                            </select>
                        </div>
                    </div>
                    <!-- fila del input nota  -->
                    <div class="row mb-4 justify-content-center">
                        <div class="col col-lg-10">
                            <label for="">Ingresar Nota</label>
                            <input type="text" name="input_nota" class="col-12" >
                        </div>
                    </div>
                    <!-- fila del input file -->
                    <div class="row mb-4 justify-content-center">
                        <div class="col col-lg-10"  >
                            <label for="" >Cargar archivos
                            <input type="file" name="input_file" class="col-12 ">
                            </label>
                        </div>
                    </div>
                    <!-- fila del boton -->
                    <div class="row justify-content-center">
                        <button type="button" class="btn btn-warning col-10" id="btn_notas_calendar">Subir</button>
                    </div>
                </form>  
                <!-- formulario para subir notas a los meses del calendario -->
            </div>
            <!-- col SUBIR NOTAS Y ARCHIVOS -->

        </div>
        <!--Seccion Inputs -->

    </div>                   
    <!--Seccion calendario -->
</div>
<!-- container -->










<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Data Tables 1.13.7 -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>

<!-- Data Tables 1.13.7 boostrap5 -->
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script src="js/main.js"></script>


<?php  require ("construct/footer.html")   ?>
