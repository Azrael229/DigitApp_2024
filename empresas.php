<?php  require ("construct/header.html")   ?>
<?php  require ("querys/query_all_empresas.php")   ?>

<style>
     @media (min-width: 992px) {
          #example {
               width: 100% !important;
               table-layout: auto;
          }

          #example th,
          #example td {
               white-space: normal;
               word-break: break-word;
               vertical-align: middle;
          }

          #example th:nth-child(2),
          #example td:nth-child(2) {
               white-space: normal;
               word-break: break-word;
          }

          #example th:nth-child(3),
          #example th:nth-child(4),
          #example th:nth-child(5),
          #example th:nth-child(6),
          #example td:nth-child(3),
          #example td:nth-child(4),
          #example td:nth-child(5),
          #example td:nth-child(6) {
               white-space: nowrap;
               word-break: normal;
          }

          #example td:last-child .btn {
               white-space: nowrap;
          }
     }
</style>


<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg ">
          <!-- titulo container -->
          <div class="row text-center">
                    <div class="col p-2 ">
                              <h1>Directorio de Empresas</h1>
                    </div>
          </div>

          <!-- row de botones de tabla empresas -->
          <div class="row border-top justify-content-center">
               <!-- bloque de botones -->
               <div class="row">
                    <div class="col mt-4 mb-4">
                         <!-- crear aqui los botones-->
                         <div class="d-flex flex-column flex-md-row justify-content-md-end gap-2">
                              <a href="form_empresa.php" class="btn btn-outline-success px-4">
                              Nueva empresa
                              </a>                       
                         </div>
                    </div>
               </div>
          </div>
          <!-- row de botones de tabla empresas  -->
          
          <!--Fila Tabla Empresas Data Table -->
          <div class="row">
               <div class="col">
                    <!-- col Tabla -->
                    <div class="col p-5 table-responsive-lg border-top">
                         <!-- tabla -->
                         <table id="example" class="table table-secondary table-striped w-100">
                              <thead>
                                   <tr>
                                        <th>Empresa</th>
                                        <th>Razón social</th>
                                        <th>RFC</th>
                                        <th>Estado</th>
                                        <th>Ciudad</th>
                                        <th>Rol</th>
                                        <th>Mercado</th>
                                        <th>Estatus</th>
                                        <th>Visualizar</th>
                                        
                                   </tr>
                              </thead>
                              <tbody>

                                   <?php  foreach ($result_empresas as $row): ?>
                                        <?php
                                             $estadoMostrar = trim((string)($row['estado_fiscal'] ?? ''));
                                             if ($estadoMostrar === '') {
                                                  $estadoMostrar = 'Sin dato';
                                             }

                                             $ciudadMostrar = trim((string)($row['ciudad_fiscal'] ?? ''));
                                             if ($ciudadMostrar === '') {
                                                  $ciudadMostrar = trim((string)($row['municipio_fiscal'] ?? ''));
                                             }
                                             if ($ciudadMostrar === '') {
                                                  $ciudadMostrar = 'Sin dato';
                                             }
                                        ?>
                                        <tr>
                                             <td><?php echo $row['empresa'] ?></td>
                                             <td><?php echo $row['razon_social'] ?></td>
                                             <td><?php echo $row['rfc'] ?></td>
                                             <td><?= htmlspecialchars($estadoMostrar, ENT_QUOTES, 'UTF-8') ?></td>
                                             <td><?= htmlspecialchars($ciudadMostrar, ENT_QUOTES, 'UTF-8') ?></td>
                                             <td><?php echo $row['rol'] ?></td>
                                             <td><?php echo $row['mercado'] ?></td>
                                             <td><?php echo $row['estatus'] ?></td>
                                             <td>
                                                  <a href="empresa_detalle.php?id_e=<?= $row['id_e'] ?>" class="btn btn-info btn-sm">
                                                       Visualizar
                                                  </a>
                                             </td>
                                             
                                        </tr>
                                   <?php  endforeach;    ?>
                                   
                              </tbody>
                              
                         </table>
                         <!-- tabla -->
                    </div>
                    <!-- col Tabla -->
               </div>
          </div>
          <!-- Fila Tabla Data TAble -->
          
</div>
<!-- container -->



<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Data Tables 1.13.7 -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>

<!-- Data Tables 1.13.7 boostrap5 -->
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script src="js/datatable-filters.js"></script>
<script src="js/empresas.js"></script>

<?php  require ("construct/footer.html")   ?>
