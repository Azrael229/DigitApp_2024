<?php  require ("construct/header.html")   ?>
<?php  require ("querys/query_all_empresas.php")   ?>


<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg ">
          <!-- titulo container -->
          <div class="row text-center">
                    <div class="col p-2 ">
                              <h3>Directorio</h3>
                    </div>
          </div>

          <!-- fila de datos de  empresa -->
          <div class="row">
                    
                    <!--col datos Empresa -->
                         <div class="col-12 border-top p-3" >

                                   <!-- titulo -->
                                   <div class="row text-center p-3 mb-3">
                                             <h5><i class="bi bi-buildings" id="ico_buildings"></i></h5>
                                             <h5> Empresas</h5>
                                   </div>
                                   <!-- formulario Empresa -->
                                   <form action="querys/add_empresa.php" method="POST">

                                        <!-- input oculto empresa id -->
                                        <input type="hidden" id="empresa_id" name="empresa_id">
                                        
                                        <div class="row">
                                                  <div class="col-3 ">
                                                            <label>Nombre</label>
                                                  </div>
                                                  <div class="col">
                                                            <textarea name="emp_nombre" id="empresa_nombre" cols="40" rows="1" style="resize: none;" maxlength="50" required></textarea>
                                                  </div>
                                        </div>

                                        <div class="row">
                                                  <div class="col-3 ">
                                                            <label>Dirección Entrega</label>
                                                  </div>
                                                  <div class="col">
                                                            <textarea name="emp_dir_entrega" id="emp_dir_entrega" cols="40" rows="3" style="resize: none;" maxlength="500" required></textarea>
                                                  </div>
                                        </div>

                                        <div class="row">
                                                  <div class="col-3 ">
                                                            <label>Razón Social</label>
                                                  </div>
                                                  <div class="col">
                                                            <textarea name="emp_razon_soc" id="emp_razon_soc" cols="40" rows="1" style="resize: none;" maxlength="50"></textarea>
                                                  </div>
                                        </div>

                                        <div class="row">
                                                  <div class="col-3 ">
                                                            <label>RFC</label>
                                                  </div>
                                                  <div class="col">
                                                            <textarea name="emp_rfc" id="emp_rfc" cols="40" rows="1" style="resize: none;" maxlength="20"></textarea>
                                                  </div>
                                        </div>

                                        <div class="row">
                                                  <div class="col-3 ">
                                                            <label>Dirección Fiscal</label>
                                                  </div>
                                                  <div class="col">
                                                            <textarea name="emp_dir_fiscal" id="emp_dir_fiscal" cols="40" rows="3" style="resize: none;" maxlength="500"></textarea>
                                                  </div>
                                        </div>

                                        <!-- rol de empresa Proveedor / Cliente -->
                                        <div class="row">
                                                  <div class="col mt-3">
                                                       <div class="form-switch">
                                                            <input class="form-check-input" type="radio" value="Proveedor" id="emp_proveedor" name="emp_rol" role="switch" />
                                                            <label class="form-check-label" for=""> Proveedor </label>
                                                       </div>
                                                       <div class="form-switch mt-2">
                                                            <input
                                                                 class="form-check-input"
                                                                 type="radio"
                                                                 value="Cliente"
                                                                 id="emp_cliente"
                                                                 name="emp_rol"
                                                                 role="switch"
                                                                 checked
                                                            />
                                                            <label class="form-check-label" for=""> Cliente </label>
                                                       </div>
                                                       
                                                  </div>
                                        </div>                                       

                                        <!-- boton submit -->
                                        <div class="row">
                                                  <div class="col-6 mx-auto d-grid mt-5 mb-3">
                                                            <button
                                                                      type="submit"
                                                                      class="btn btn-secondary"
                                                                      id="btn_submit_emp"
                                                                      
                                                            >
                                                                      Guardar
                                                            </button>
                                                            
                                                  </div>
                                        </div>

                                   </form>
                                   <!-- formulario Empresa -->                        
                         </div>
                    <!--col datos Empresa -->
          </div>
          <!-- fila de datos de empresa -->

          
          <!--Fila Tabla Data Table -->
          <div class="row">
               <!-- col Tabla -->
               <div class="col p-5 table-responsive border-top">
                    <!-- tabla -->
                    <table id="example" class="table table-secondary table-striped" style="width:100%;">
                         <thead>
                              <tr>
                                   <th>Nombre</th>
                                   <th>Dirección de Entrega</th>
                                   <th>Rfc</th>
                                   <th>Rol</th>                                  
                                   <th></th>
                                   
                              </tr>
                         </thead>
                         <tbody>

                              <?php  foreach ($result_empresas as $row): ?>
                                   <tr>
                                        <td><?php echo $row['empresa'] ?></td>
                                        <td><?php echo $row['dir_entrega'] ?></td>
                                        <td><?php echo $row['rfc'] ?></td>
                                        <td><?php echo $row['rol'] ?></td>
                                        <td onclick="editar(<?php echo $row['id_e'] ?>)" style="cursor: pointer;">Editar</td>
                                        
                                   </tr>
                              <?php  endforeach;    ?>
                              
                         </tbody>
                         
                    </table>
                    <!-- tabla -->
               </div>
               <!-- col Tabla -->
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

<script src="js/empresas.js"></script>

<?php  require ("construct/footer.html")   ?>
