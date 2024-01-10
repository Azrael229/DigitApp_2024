<?php  require ("construct/header.html")  ?>
<?php  require ("querys/query_all_contactos.php")  ?>
<?php  require ("querys/query_all_empresas.php")  ?>


<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg ">
          <!-- titulo container -->
          <div class="row text-center">
                    <div class="col p-2">
                              <h3>Directorio</h3>
                    </div>
          </div>

          <!-- fila de datos de contacto y empresa -->
          <div class="row">
               <div class="col border-top">

                    <!-- col datos del contacto -->
                         <div class="col col-lg-6  p-3">
                                   <!-- titulo -->
                                   <div class="row text-center p-3 mb-3">
                                             <h5><i class="bi bi-person-fill" id="ico_contacto"></i></h5>
                                             <h5>Contactos</h5>
                                   </div>

                                   <!-- formulario contacto -->
                                   <form action="querys/add_contacto.php" method="POST">

                                   <div class="row">
                                             <div class="col-3  ">
                                                  <label>Nombre</label>
                                             </div>
                                             <div class="col  ">
                                                  <textarea name="contacto_nombre" id="contacto_nombre" cols="40" rows="1" style="resize: none;" maxlength="50" required></textarea>
                                             </div>
                                   </div>

                                   <div class="row">
                                             <div class="col-3 ">
                                                       <label>Celular</label>
                                             </div>
                                             <div class="col">
                                                       <textarea name="contacto_cel" id="contacto_cel" cols="40" rows="1" style="resize: none;" maxlength="10" required></textarea>
                                             </div>
                                   </div>

                                   <div class="row">
                                             <div class="col-3 ">
                                                       <label>Correo</label>
                                             </div>
                                             <div class="col">
                                                       <textarea name="contacto_email" id="contacto_email" cols="40" rows="1" style="resize: none;" maxlength="50" required></textarea>
                                             </div>
                                   </div>

                                   <div class="row mb-5">
                                             <div class="col-3 ">
                                                       <label>Departamento</label>
                                             </div>
                                             <div class="col">
                                                       <textarea name="contacto_depto" id="contacto_depto" cols="40" rows="1" style="resize: none;" maxlength="50" required></textarea>
                                             </div>
                                   </div>

                                   <div class="row" id="sp_empresa">
                                             
                                   </div>

                                   <!-- Asociar Empresa-->
                                   <div class="row">
                                        <div class="col ">      
                                             <!-- select Empresas -->
                                             <select class="form-control" name="contacto_nombre_empresa" id="contacto_nombre_empresa" >

                                                       <option value="6" selected>Sin Empresa Asociada</option>
                                                       <?php foreach($result_empresas as $fila):  ?>
                                                       <option value="<?php echo $fila['id_e']; ?>"> <?php echo $fila['empresa']; ?>  </option>
                                                       <?php endforeach; ?>
                                             </select>                   
                                        </div>
                                   </div>

                                   <!-- input oculto contacto id -->
                                   <input type="hidden" id="contacto_id" name="contacto_id" placeholder="id contacato">

                                   <!-- boton guardar contacto -->
                                   <div class="row">
                                             <div class="col-9 col-lg-8 mx-auto d-grid mt-5 mb-3">
                                                       <button
                                                                 type="submit"
                                                                 class="btn btn-secondary"
                                                                 id="btn_sumbit_contacto"
                                                       >
                                                                 Guardar
                                                       </button>
                                                       
                                             </div>
                                   </div>
                                   </form>
                                   <!-- formulario contacto -->

                         </div>
                    <!--col datos del contacto -->
               </div>    
          </div>
          <!-- fila de datos de contacto y empresa -->

                    
          <!--Fila Tabla Contactos Data Table -->
          <div class="row mb-5">
               <div class="col">
                    <!-- col Tabla -->
                    <div class="col  p-5 table-responsive border-top" style="white-space: nowrap; ">
                         <!-- tabla -->
                         <table id="example" class="table table-secondary table-striped mb-5">
                              <thead>
                                   <tr>
                                        <th>Nombre</th>
                                        <th>Empresa</th>
                                        <th>Celular</th>
                                        <th>Email</th>
                                        <th>Departamento</th>
                                        <th></th>
                                   </tr>
                              </thead>
                              <tbody>

                                   <?php  foreach ($result_contactos as $row): ?>
                                        <tr>
                                             <td><?php echo $row['nombre'] ?></td>
                                             <td><?php echo $row['empresa'] ?></td>
                                             <td><?php echo $row['celular'] ?></td>
                                             <td><?php echo $row['correo'] ?></td>
                                             <td><?php echo $row['depto'] ?></td>
                                             <td onclick="editar(<?php echo $row['id'] ?>)" style="cursor: pointer;">Editar</td>
                                        </tr>
                                   <?php  endforeach;    ?>
                                   
                              </tbody>
                              
                         </table>
                         <!-- tabla -->
                    </div>
                    <!-- col Tabla -->
               </div>
          </div>
          <!-- Fila Tabla Contactos Data TAble -->
          
</div>
<!-- container -->



<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Data Tables 1.13.7 -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>

<!-- Data Tables 1.13.7 boostrap5 -->
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- SELECT2 combobox -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="js/contactos.js"></script>

<?php  require ("construct/footer.html")   ?>
