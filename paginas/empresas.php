<?php $prefijoRuta = '../'; ?>
<?php  require (__DIR__ . "/../construct/header.php")   ?>
<?php  require (__DIR__ . "/../backend/empresas/query_all_empresas.php")   ?>


<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg ">
          <!-- titulo container -->
          <div class="row align-items-center">
                    <div class="col p-2 text-center">
                              <h3>Directorio</h3>
                    </div>
                    <div class="col-12 col-md-auto text-center text-md-end pb-2 pb-md-0">
                              <a href="form_empresa.php" class="btn btn-secondary">
                                        <i class="bi bi-plus-lg"></i> Añadir empresa
                              </a>
                    </div>
          </div>

          <!--Fila Tabla Empresas Data Table -->
          <div class="row">
               <div class="col">
                    <!-- col Tabla -->
                    <div class="col table-responsive data-table-shell" >
                         <!-- tabla -->
                         <table id="example" class="table table-secondary table-striped">
                              <thead>
                                   <tr>
                                        <th>Nombre</th>
                                        <th class="empresa-col-ciudad">Ciudad</th>
                                        <th>Estado</th>
                                        <th>Rfc</th>
                                        <th>Rol</th>                                  
                                        <th>Fecha de creación</th>
                                        
                                   </tr>
                              </thead>
                              <tbody>

                                   <?php  foreach ($result_empresas as $row): ?>
                                        <tr>
                                             <td><a href="ver_empresa.php?id=<?php echo $row['id_e'] ?>"><?php echo htmlspecialchars($row['empresa']) ?></a></td>
                                             <td class="empresa-col-ciudad"><?php echo htmlspecialchars($row['ciudad_principal'] ?? '') ?></td>
                                             <td><?php echo htmlspecialchars($row['estado_principal'] ?? '') ?></td>
                                             <td><?php echo $row['rfc'] ?></td>
                                             <td><?php echo $row['rol'] ?></td>
                                             <td><?php echo !empty($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '' ?></td>
                                             
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

<script src="<?= $prefijoRuta ?>js/datatable-filters.js"></script>
<script src="<?= $prefijoRuta ?>js/datatable-config.js"></script>
<script src="<?= $prefijoRuta ?>js/tablaEmpresas.js"></script>

<?php  require (__DIR__ . "/../construct/footer.html")   ?>
