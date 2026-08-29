<?php $prefijoRuta = '../'; ?>
<?php require (__DIR__ . "/../construct/header.php"); ?>

<div class="container mt-5 mb-5 contain shadow-lg empresa-detalle">
    <div class="row align-items-center pt-3 pb-4 mb-4 empresa-detalle-header">
        <div class="col empresa-header-copy">
            <p class="empresa-header-kicker mb-1">Directorio de empresas</p>
            <h1 id="titulo_empresa" class="h2 mb-1">Empresa</h1>
            <p class="empresa-header-subtitle mb-0">Información general y registros asociados</p>
        </div>
        <div class="col-12 col-md-auto mt-3 mt-md-0 d-flex flex-column flex-sm-row gap-2 empresa-header-actions">
            <a id="btn_editar_empresa" href="#" class="btn btn-secondary disabled text-nowrap" aria-disabled="true"><i class="bi bi-pencil"></i> Editar datos generales</a>
            <a href="empresas.php" class="btn btn-secondary text-nowrap"><i class="bi bi-arrow-left"></i> Directorio</a>
        </div>
    </div>

    <div id="mensaje_empresa" class="alert alert-info" role="status" aria-live="polite">Cargando información de la empresa...</div>

    <div id="contenido_empresa" class="d-none">
        <div class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="empresa-section-heading">
                    <div>
                        <p class="empresa-section-kicker mb-1">Identificación</p>
                        <h2 class="h5 card-title mb-0">Datos generales</h2>
                    </div>
                </div>
                <div class="row g-3 mt-1" id="datos_generales"></div>
            </div>
        </div>

        <div class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3 empresa-section-heading">
                    <div>
                        <p class="empresa-section-kicker mb-1">Ubicaciones registradas</p>
                        <h2 class="h5 card-title mb-0">Direcciones</h2>
                    </div>
                    <a id="btn_agregar_direccion" href="#" class="btn btn-secondary btn-sm disabled" aria-disabled="true">
                        <i class="bi bi-plus-lg"></i> Añadir dirección
                    </a>
                </div>
                <div class="table-responsive empresa-table-wrap">
                    <table class="table table-secondary align-middle empresa-detail-table mb-0">
                        <caption class="visually-hidden">Direcciones registradas de la empresa</caption>
                        <thead><tr><th scope="col">Tipo</th><th scope="col">Alias</th><th scope="col">Dirección</th><th scope="col">Principal</th><th scope="col">Editar</th><th scope="col">Eliminar</th></tr></thead>
                        <tbody id="tabla_direcciones"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3 empresa-section-heading">
                    <div>
                        <p class="empresa-section-kicker mb-1">Personas vinculadas</p>
                        <h2 class="h5 card-title mb-0">Contactos</h2>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" disabled title="Disponible en el siguiente paso">
                        <i class="bi bi-plus-lg"></i> Añadir contacto
                    </button>
                </div>
                <div class="table-responsive empresa-table-wrap">
                    <table class="table table-secondary align-middle empresa-detail-table mb-0">
                        <caption class="visually-hidden">Contactos registrados de la empresa</caption>
                        <thead><tr><th scope="col">Nombre</th><th scope="col">Teléfono</th><th scope="col">Correo</th><th scope="col">Departamento</th><th scope="col">Editar</th><th scope="col">Eliminar</th></tr></thead>
                        <tbody id="tabla_contactos"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $prefijoRuta ?>js/ver_empresa.js"></script>

<?php require (__DIR__ . "/../construct/footer.html"); ?>
