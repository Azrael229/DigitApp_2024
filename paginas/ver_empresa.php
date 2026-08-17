<?php $prefijoRuta = '../'; ?>
<?php require (__DIR__ . "/../construct/header.php"); ?>

<div class="container mt-5 mb-5 contain shadow-lg">
    <div class="row align-items-center pt-3 mb-4">
        <div class="col">
            <h3 id="titulo_empresa">Empresa</h3>
        </div>
        <div class="col-12 col-md-auto mt-2 mt-md-0 d-flex gap-2">
            <a id="btn_editar_empresa" href="#" class="btn btn-secondary disabled" aria-disabled="true"><i class="bi bi-pencil"></i> Editar datos generales</a>
            <a href="empresas.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Directorio</a>
        </div>
    </div>

    <div id="mensaje_empresa" class="alert alert-info">Cargando información de la empresa...</div>

    <div id="contenido_empresa" class="d-none">
        <div class="card mb-4 empresa-form-card">
            <div class="card-body empresa-card-body">
                <h5 class="card-title">Datos generales</h5>
                <div class="row" id="datos_generales"></div>
            </div>
        </div>

        <div class="card mb-4 empresa-form-card">
            <div class="card-body empresa-card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Direcciones</h5>
                    <a id="btn_agregar_direccion" href="#" class="btn btn-secondary btn-sm disabled" aria-disabled="true">
                        <i class="bi bi-plus-lg"></i> Añadir dirección
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-secondary mb-0">
                        <thead><tr><th>Tipo</th><th>Alias</th><th>Dirección</th><th>Principal</th><th>Editar</th><th>Eliminar</th></tr></thead>
                        <tbody id="tabla_direcciones"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4 empresa-form-card">
            <div class="card-body empresa-card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Contactos</h5>
                    <button type="button" class="btn btn-secondary btn-sm" disabled title="Disponible en el siguiente paso">
                        <i class="bi bi-plus-lg"></i> Añadir contacto
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-secondary mb-0">
                        <thead><tr><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Departamento</th><th>Editar</th><th>Eliminar</th></tr></thead>
                        <tbody id="tabla_contactos"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $prefijoRuta ?>js/ver_empresa.js"></script>

<?php require (__DIR__ . "/../construct/footer.html"); ?>
