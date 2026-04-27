<?php
require("querys/conexion.php");

$empresaEdicion = [];
$direccionFiscalEdicion = [];
$modoFormulario = 'crear';
$idEmpresaEdicion = isset($_GET['id_e']) ? (int) $_GET['id_e'] : 0;

if ($idEmpresaEdicion > 0) {
    $stmtEmpresa = $conexion->prepare("SELECT * FROM empresas WHERE id_e = ? LIMIT 1");
    $stmtEmpresa->bind_param("i", $idEmpresaEdicion);
    $stmtEmpresa->execute();
    $resultadoEmpresa = $stmtEmpresa->get_result();
    $empresaEdicion = $resultadoEmpresa->fetch_assoc() ?: [];
    $stmtEmpresa->close();

    if (!empty($empresaEdicion)) {
        $modoFormulario = 'editar';

        $stmtDireccion = $conexion->prepare(
            "SELECT * FROM empresa_direcciones
             WHERE empresa_id = ? AND tipo_direccion = ?
             ORDER BY es_principal DESC, id ASC
             LIMIT 1"
        );
        $tipoDireccionFiscal = 'fiscal';
        $stmtDireccion->bind_param("is", $idEmpresaEdicion, $tipoDireccionFiscal);
        $stmtDireccion->execute();
        $resultadoDireccion = $stmtDireccion->get_result();
        $direccionFiscalEdicion = $resultadoDireccion->fetch_assoc() ?: [];
        $stmtDireccion->close();
    }
}

$conexion->close();

require("construct/header.html");
?>

<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg" ">

    <!-- row de titulo -->
    <div class="row">
        <div class="col text-center mt-3 mb-5">
            <h1><span><?= $modoFormulario === 'editar' ? 'Editar Empresa' : 'Nueva Empresa' ?></span></h1>
        </div>
    </div>

    <?php if (isset($_GET['guardado'])): ?>
        <div class="alert alert-success" role="alert">
            Empresa y dirección fiscal guardadas correctamente.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['actualizado'])): ?>
        <div class="alert alert-success" role="alert">
            Empresa y dirección fiscal actualizadas correctamente.
        </div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <!-- row de titulo -->

    <form id="formEmpresa" action="querys/add_empresa.php" method="POST" data-modo-formulario="<?= $modoFormulario ?>">
    <!-- row de botones de formulario empresas  -->
    <div class="row border-top justify-content-center">
        <!-- bloque de botones -->
        <div class="row">
            <div class="col mt-4 mb-4">
                <!-- crear aqui los botones-->
                <div class="d-flex flex-column flex-md-row justify-content-md-end gap-2">
                    <button type="submit" class="btn btn-outline-success btn-empresa-guardar px-4">
                        Guardar
                    </button>
                    <a href="empresas.php" class="btn btn-danger px-4">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- row de botones de OS  -->



    <!-- row de Contenido -->
    <div class="row border-top justify-content-center ">
        <!-- formulario empresa  -->
        <div class="row">
            <div class="col text-center mt-3 mb-5">
                <!-- crear aqui -->
                    <div class="d-flex flex-column gap-4 text-start">
                        <div class="card border-0 shadow-sm empresa-form-card">
                            <div class="card-header empresa-card-header">
                                Carga inteligente de datos de empresa
                            </div>

                            <div class="card-body p-4 empresa-card-body">
                                <label for="archivo_empresa_ai" class="form-label fw-semibold">
                                    Archivo
                                </label>

                                <input
                                    type="file"
                                    id="archivo_empresa_ai"
                                    name="archivo_empresa_ai"
                                    class="form-control"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.xml,.json,.doc,.docx,.rtf,.csv">

                                <div class="form-text">
                                    Puedes cargar constancia fiscal, PDF, imagen, archivo TXT, XML o documento compatible.
                                    El archivo se usara solo para extraer datos y no se guardara en el servidor.
                                </div>

                                <label for="texto_empresa_ai" class="form-label fw-semibold mt-3">
                                    O pegar texto manualmente
                                </label>

                                <textarea
                                    id="texto_empresa_ai"
                                    name="texto_empresa_ai"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Pega aqui datos fiscales, texto de una constancia, correo, mensaje o informacion de cliente..."></textarea>

                                <button
                                    type="button"
                                    id="btnProcesarEmpresaAI"
                                    class="btn btn-primary mt-3">
                                    Detectar datos de empresa
                                </button>

                                <div id="mensajeEmpresaAI" class="mt-3"></div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4 empresa-card-body">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Datos principales</h2>
                                    <p class="text-body-secondary mb-0">Captura manual base para el alta de la empresa.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-6">
                                        <label for="empresa" class="form-label fw-semibold">Empresa</label>
                                        <input type="text" class="form-control" id="empresa" name="empresa">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="razon_social" class="form-label fw-semibold">Razon social</label>
                                        <input type="text" class="form-control" id="razon_social" name="razon_social">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="rfc" class="form-label fw-semibold">RFC</label>
                                        <input type="text" class="form-control" id="rfc" name="rfc">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="rol" class="form-label fw-semibold">Rol</label>
                                        <select class="form-select" id="rol" name="rol">
                                            <option value="">Selecciona una opcion</option>
                                            <option value="Cliente">Cliente</option>
                                            <option value="Proveedor">Proveedor</option>
                                            <option value="Prospecto">Prospecto</option>
                                            <option value="Cliente-Proveedor">Cliente-Proveedor</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="actividad_economica" class="form-label fw-semibold">Actividad economica</label>
                                        <input type="text" class="form-control" id="actividad_economica" name="actividad_economica">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4 empresa-card-body">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Datos fiscales</h2>
                                    <p class="text-body-secondary mb-0">Informacion fiscal principal de la empresa.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-4">
                                        <label for="regimen_fiscal_codigo" class="form-label fw-semibold">Regimen fiscal codigo</label>
                                        <input type="text" class="form-control" id="regimen_fiscal_codigo" name="regimen_fiscal_codigo">
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <label for="regimen_fiscal_descripcion" class="form-label fw-semibold">Regimen fiscal descripcion</label>
                                        <input type="text" class="form-control" id="regimen_fiscal_descripcion" name="regimen_fiscal_descripcion">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="regimen_capital" class="form-label fw-semibold">Regimen capital</label>
                                        <input type="text" class="form-control" id="regimen_capital" name="regimen_capital">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="tipo_persona" class="form-label fw-semibold">Tipo persona</label>
                                        <select class="form-select" id="tipo_persona" name="tipo_persona">
                                            <option value="">Selecciona una opcion</option>
                                            <option value="Física">Fisica</option>
                                            <option value="Moral">Moral</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4 empresa-card-body">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Direccion fiscal</h2>
                                    <p class="text-body-secondary mb-0">Seccion visual para captura de la direccion fiscal que despues se almacenara en empresa_direcciones.</p>
                                </div>
                                <input type="hidden" id="tipo_direccion" name="tipo_direccion" value="fiscal">
                                <input type="hidden" id="es_principal" name="es_principal" value="1">
                                <div class="row g-4">
                                    <div class="col-12 col-md-4">
                                        <label for="tipo_direccion_visible" class="form-label fw-semibold">Tipo de direccion</label>
                                        <input type="text" class="form-control" id="tipo_direccion_visible" value="fiscal" readonly>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="alias" class="form-label fw-semibold">Alias</label>
                                        <input type="text" class="form-control" id="alias" name="alias" value="Fiscal">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="pais" class="form-label fw-semibold">Pais</label>
                                        <input type="text" class="form-control" id="pais" name="pais" value="Mexico">
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <label for="calle" class="form-label fw-semibold">Calle</label>
                                        <input type="text" class="form-control" id="calle" name="calle">
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <label for="numero_exterior" class="form-label fw-semibold">Numero exterior</label>
                                        <input type="text" class="form-control" id="numero_exterior" name="numero_exterior">
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <label for="numero_interior" class="form-label fw-semibold">Numero interior</label>
                                        <input type="text" class="form-control" id="numero_interior" name="numero_interior">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="colonia" class="form-label fw-semibold">Colonia</label>
                                        <input type="text" class="form-control" id="colonia" name="colonia">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="localidad" class="form-label fw-semibold">Localidad</label>
                                        <input type="text" class="form-control" id="localidad" name="localidad">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="municipio" class="form-label fw-semibold">Municipio</label>
                                        <input type="text" class="form-control" id="municipio" name="municipio">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="ciudad" class="form-label fw-semibold">Ciudad</label>
                                        <input type="text" class="form-control" id="ciudad" name="ciudad">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="estado" class="form-label fw-semibold">Estado</label>
                                        <input type="text" class="form-control" id="estado" name="estado">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="codigo_postal" class="form-label fw-semibold">Codigo postal</label>
                                        <input type="text" class="form-control" id="codigo_postal" name="codigo_postal">
                                    </div>
                                    <div class="col-12">
                                        <label for="entre_calles" class="form-label fw-semibold">Entre calles</label>
                                        <input type="text" class="form-control" id="entre_calles" name="entre_calles">
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label for="enlace_maps" class="form-label fw-semibold">Enlace Maps</label>
                                        <input type="url" class="form-control" id="enlace_maps" name="enlace_maps">
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label for="es_principal_visible" class="form-label fw-semibold">Direccion principal</label>
                                        <input type="text" class="form-control" id="es_principal_visible" value="1" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label for="referencia" class="form-label fw-semibold">Referencia</label>
                                        <textarea class="form-control" id="referencia" name="referencia" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4 empresa-card-body">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Clasificacion comercial</h2>
                                    <p class="text-body-secondary mb-0">Datos internos para clasificacion de negocio.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-6">
                                        <label for="giro_mercantil" class="form-label fw-semibold">Giro mercantil</label>
                                        <input type="text" class="form-control" id="giro_mercantil" name="giro_mercantil">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="mercado" class="form-label fw-semibold">Mercado</label>
                                        <input type="text" class="form-control" id="mercado" name="mercado">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4 empresa-card-body">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Contacto general</h2>
                                    <p class="text-body-secondary mb-0">Canales principales de contacto de la empresa.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-4">
                                        <label for="telefono_principal" class="form-label fw-semibold">Telefono principal</label>
                                        <input type="tel" class="form-control" id="telefono_principal" name="telefono_principal">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="email_principal" class="form-label fw-semibold">Email principal</label>
                                        <input type="email" class="form-control" id="email_principal" name="email_principal">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="pagina_web" class="form-label fw-semibold">Pagina web</label>
                                        <input type="url" class="form-control" id="pagina_web" name="pagina_web">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4 empresa-card-body">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Control interno</h2>
                                    <p class="text-body-secondary mb-0">Campos operativos para seguimiento del registro.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-4">
                                        <label for="estatus" class="form-label fw-semibold">Estatus</label>
                                        <select class="form-select" id="estatus" name="estatus">
                                            <option value="">Selecciona una opcion</option>
                                            <option value="activo">activo</option>
                                            <option value="inactivo">inactivo</option>
                                            <option value="bloqueado">bloqueado</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="origen_registro" class="form-label fw-semibold">Origen de registro</label>
                                        <select class="form-select" id="origen_registro" name="origen_registro">
                                            <option value="">Selecciona una opcion</option>
                                            <option value="manual">manual</option>
                                            <option value="constancia_fiscal">constancia_fiscal</option>
                                            <option value="importacion">importacion</option>
                                            <option value="otro">otro</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="es_principal_resumen" class="form-label fw-semibold">Fiscal principal</label>
                                        <input type="text" class="form-control" id="es_principal_resumen" value="1" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="4"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 rounded-4 empresa-card-body">
                                <div class="mb-4">
                                    <h2 class="h4 mb-1">Informacion del registro</h2>
                                    <p class="text-body-secondary mb-0">Campos informativos no editables.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12 col-md-4">
                                        <label for="id_e" class="form-label fw-semibold">ID empresa</label>
                                        <input type="text" class="form-control" id="id_e" name="id_e" readonly>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="created_at" class="form-label fw-semibold">Creado en</label>
                                        <input type="text" class="form-control" id="created_at" name="created_at" readonly>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="updated_at" class="form-label fw-semibold">Actualizado en</label>
                                        <input type="text" class="form-control" id="updated_at" name="updated_at" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
    </form>
    <!-- formulario empresa -->

</div>
<!-- container -->

<?php
$valoresInicialesFormulario = [
    'id_e' => $empresaEdicion['id_e'] ?? '',
    'empresa' => $empresaEdicion['empresa'] ?? '',
    'razon_social' => $empresaEdicion['razon_social'] ?? '',
    'rfc' => $empresaEdicion['rfc'] ?? '',
    'rol' => $empresaEdicion['rol'] ?? '',
    'actividad_economica' => $empresaEdicion['actividad_economica'] ?? '',
    'regimen_fiscal_codigo' => $empresaEdicion['regimen_fiscal_codigo'] ?? '',
    'regimen_fiscal_descripcion' => $empresaEdicion['regimen_fiscal_descripcion'] ?? '',
    'regimen_capital' => $empresaEdicion['regimen_capital'] ?? '',
    'tipo_persona' => $empresaEdicion['tipo_persona'] ?? '',
    'giro_mercantil' => $empresaEdicion['giro_mercantil'] ?? '',
    'mercado' => $empresaEdicion['mercado'] ?? '',
    'telefono_principal' => $empresaEdicion['telefono_principal'] ?? '',
    'email_principal' => $empresaEdicion['email_principal'] ?? '',
    'pagina_web' => $empresaEdicion['pagina_web'] ?? '',
    'estatus' => $empresaEdicion['estatus'] ?? '',
    'origen_registro' => $empresaEdicion['origen_registro'] ?? '',
    'observaciones' => $empresaEdicion['observaciones'] ?? '',
    'created_at' => $empresaEdicion['created_at'] ?? '',
    'updated_at' => $empresaEdicion['updated_at'] ?? '',

    'tipo_direccion' => $direccionFiscalEdicion['tipo_direccion'] ?? 'fiscal',
    'tipo_direccion_visible' => $direccionFiscalEdicion['tipo_direccion'] ?? 'fiscal',
    'alias' => $direccionFiscalEdicion['alias'] ?? 'Fiscal',
    'calle' => $direccionFiscalEdicion['calle'] ?? '',
    'numero_exterior' => $direccionFiscalEdicion['numero_exterior'] ?? '',
    'numero_interior' => $direccionFiscalEdicion['numero_interior'] ?? '',
    'colonia' => $direccionFiscalEdicion['colonia'] ?? '',
    'localidad' => $direccionFiscalEdicion['localidad'] ?? '',
    'municipio' => $direccionFiscalEdicion['municipio'] ?? '',
    'ciudad' => $direccionFiscalEdicion['ciudad'] ?? '',
    'estado' => $direccionFiscalEdicion['estado'] ?? '',
    'codigo_postal' => $direccionFiscalEdicion['codigo_postal'] ?? '',
    'pais' => $direccionFiscalEdicion['pais'] ?? 'Mexico',
    'enlace_maps' => $direccionFiscalEdicion['enlace_maps'] ?? '',
    'referencia' => $direccionFiscalEdicion['referencia'] ?? '',
    'entre_calles' => $direccionFiscalEdicion['entre_calles'] ?? '',
    'es_principal' => isset($direccionFiscalEdicion['es_principal']) ? (string) $direccionFiscalEdicion['es_principal'] : '1',
    'es_principal_visible' => isset($direccionFiscalEdicion['es_principal']) ? (string) $direccionFiscalEdicion['es_principal'] : '1',
    'es_principal_resumen' => isset($direccionFiscalEdicion['es_principal']) ? (string) $direccionFiscalEdicion['es_principal'] : '1',
];
?>

<script>
    (function () {
        const valoresIniciales = <?= json_encode($valoresInicialesFormulario, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        Object.keys(valoresIniciales).forEach(function (id) {
            const elemento = document.getElementById(id);
            if (!elemento) {
                return;
            }
            const valor = valoresIniciales[id];
            if (valor === null || valor === undefined) {
                return;
            }
            if (Object.prototype.hasOwnProperty.call(elemento, 'value')) {
                elemento.value = String(valor);
            }
        });
    })();
</script>







<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Data Tables 1.13.7 -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>

<!-- Data Tables 1.13.7 boostrap5 -->
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script src="js/datatable-filters.js"></script>
<script src="js/main.js"></script>
<script src="js/empresas_ai.js?v=20260425_6"></script>


<?php  require ("construct/footer.html")   ?>
