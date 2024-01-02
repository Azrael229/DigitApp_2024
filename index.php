
<?php  require ("construct/header.html")   ?>
<?php  require ("querys/query_all_cotizaciones.php")   ?>
  
  

<div class="container mt-5 mb-5 contain shadow-lg" ">
    <div class="row">
        <div class="col text-center mt-3 mb-5">
            <h1>Panel de Administrador</h1>
        </div>
    </div>
    <div class="row border-top justify-content-center ">
        <div class="row">
            <div class="col text-center mt-3 mb-5">
                <h1>Cotizaciones</h1>
            </div>
        </div>
        <div class="col-12 p-3">
            <div class="table-responsive mb-5">
                <table id="example" class="table table-secondary table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>FECHA</th>
                            <th>EMPRESA</th>
                            <th>CONTACTO</th>
                            <th>IMPORTE</th>
                            <th>DESCARGAR PDF</th>
                            <th>NUMERO</th>
                            <th>VIGENCIA</th>
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
                            <td><?php echo $row_coti['cot_numero'] ?></td>
                            <td><?php echo $row_coti['cot_vigencia'] ?></td>
                        </tr>
                        <?php endforeach;  ?>
                        
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>











<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Data Tables 1.13.7 -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>

<!-- Data Tables 1.13.7 boostrap5 -->
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script src="js/main.js"></script>


<?php  require ("construct/footer.html")   ?>
