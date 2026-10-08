<?php
$prefijoRuta = '../';
require_once __DIR__ . '/../backend/auth/bootstrap.php';
if (empty($_SESSION['empresas_tabla_csrf'])) {
    $_SESSION['empresas_tabla_csrf'] = bin2hex(random_bytes(32));
}
?>
<?php require_once __DIR__ . '/../backend/helpers/entity_links.php'; ?>
<?php  require (__DIR__ . "/../construct/header.php")   ?>
<?php  require (__DIR__ . "/../backend/empresas/query_all_empresas.php")   ?>


<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg oportunidades-page" data-empresas-csrf="<?= htmlspecialchars($_SESSION['empresas_tabla_csrf'], ENT_QUOTES, 'UTF-8') ?>">
          <!-- titulo container -->
          <div class="row align-items-center contacto-form-header g-3">
                    <div class="col">
                              <p class="contactos-kicker mb-1">Clientes y relaciones</p>
                              <h1 class="h2 mb-0">Empresas</h1>
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
                    <div class="col table-responsive table-wide data-table-shell empresa-directory-table-wrap" >
                         <!-- tabla -->
                         <table id="example" class="table table-secondary table-striped empresa-directory-table">
                              <thead>
                                   <tr>
                                        <th class="empresa-col-fecha">Fecha de creación</th>
                                        <th class="empresa-col-nombre">Nombre comercial</th>
                                        <th class="empresa-col-razon">Razón social</th>
                                        <th class="empresa-col-ciudad">Ciudad</th>
                                        <th class="empresa-col-municipio">Municipio</th>
                                        <th class="empresa-col-estado">Estado</th>
                                        <th class="empresa-col-rfc">RFC</th>
                                        <th>Rol</th>
                                        
                                   </tr>
                              </thead>
                              <tbody>

                                   <?php  foreach ($result_empresas as $row): ?>
                                        <tr>
                                             <td class="empresa-col-fecha"><?php echo !empty($row['created_at']) ? date('d/m/Y', strtotime($row['created_at'])) : '' ?></td>
                                             <td class="empresa-col-nombre"><div class="empresa-texto-dos-lineas"><?= renderizarEnlaceEntidad('empresa', $row['id_e'] ?? null, (string) ($row['empresa'] ?? '')) ?></div></td>
                                             <td class="empresa-col-razon"><div class="empresa-texto-dos-lineas"><?= htmlspecialchars(trim(implode(' ', array_filter([($row['razon_social'] ?? '') ?: ($row['empresa'] ?? ''), $row['regimen_capital'] ?? '']))), ENT_QUOTES, 'UTF-8') ?></div></td>
                                             <td class="empresa-col-ciudad"><?php echo htmlspecialchars($row['ciudad_principal'] ?? '') ?></td>
                                             <td class="empresa-col-municipio"><?php echo htmlspecialchars($row['municipio_principal'] ?? '') ?></td>
                                             <td class="empresa-col-estado"><?php echo htmlspecialchars($row['estado_principal'] ?? '') ?></td>
                                             <td class="empresa-col-rfc"><?php echo htmlspecialchars($row['rfc'] ?? '') ?></td>
                                             <td><select class="form-select form-select-sm empresa-role-select" data-id="<?= (int) $row['id_e'] ?>" data-version="<?= (int) ($row['version'] ?? 1) ?>" aria-label="Rol de <?= htmlspecialchars((string) ($row['empresa'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?php foreach (['Cliente','Proveedor','Prospecto','Cliente-Proveedor','Otro'] as $rol): ?><option value="<?= $rol ?>"<?= ($row['rol'] ?? '') === $rol ? ' selected' : '' ?>><?= $rol ?></option><?php endforeach; ?></select></td>
                                             
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

<script src="<?= $prefijoRuta ?>js/datatable-filters.js?v=20261008-2"></script>
<script src="<?= $prefijoRuta ?>js/datatable-config.js"></script>
<script src="<?= $prefijoRuta ?>js/tablaEmpresas.js?v=20261007-2"></script>

<?php  require (__DIR__ . "/../construct/footer.html")   ?>
