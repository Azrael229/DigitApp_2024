
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
            <div class="col table-responsive" >
                <table id="example" class="table table-secondary table-striped" >
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">FECHA</th>
                            <th scope="col">EMPRESA</th>
                            <th scope="col">CONTACTO</th>
                            <th scope="col">IMPORTE</th>
                            <th scope="col">DESCARGAR PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($result_cotizaciones as $row_coti): ?>
                        <tr>
                            <td><?php echo $row_coti['id_coti'] ?></td>
                            <td><?php echo $row_coti['cot_fecha'] ?></td>
                            <td><?php echo $row_coti['cot_empresa'] ?></td>
                            <td><?php echo $row_coti['cot_contacto'] ?></td>
                            <td>$ <?php echo $row_coti['cot_total'] ?></td>
                            <td><a href="filesPDF/<?php echo $row_coti['cot_archivo'] ?>" download><?php echo $row_coti['cot_archivo'] ?></a></td>                          
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
