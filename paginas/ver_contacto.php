<?php
$prefijoRuta = '../';
require __DIR__ . '/../construct/header.php';
?>

<div class="container mt-5 mb-5 contain shadow-lg contacto-detalle">
    <div class="row align-items-center pt-3 pb-4 mb-4 contacto-detail-header">
        <div class="col">
            <p class="contactos-kicker mb-1">Directorio de contactos</p>
            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                <h1 id="titulo_contacto" class="h2 mb-0">Contacto</h1>
                <span id="estado_contacto" class="badge contactos-status contactos-status-inactive">Cargando</span>
            </div>
            <p class="contacto-detail-subtitle mb-0 mt-1">Información general y empresas asociadas</p>
        </div>
        <div class="col-12 col-md-auto mt-3 mt-md-0 d-flex flex-column flex-sm-row gap-2 contacto-detail-actions">
            <a id="btn_editar_contacto" href="#" class="btn btn-secondary disabled" aria-disabled="true"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
            <a id="btn_volver_contacto" href="contactos.php" class="btn btn-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver</a>
        </div>
    </div>

    <div id="mensaje_contacto" class="alert alert-info" role="status" aria-live="polite">Cargando información del contacto...</div>

    <div id="contenido_contacto" class="d-none">
        <section class="card mb-4 contacto-detail-card">
            <div class="card-body">
                <div class="contacto-detail-heading mb-3">
                    <p class="contactos-kicker mb-1">Canales disponibles</p>
                    <h2 class="h5 card-title mb-0">Datos de contacto</h2>
                </div>
                <div class="row g-3" id="datos_contacto"></div>
            </div>
        </section>

        <section class="card mb-4 contacto-detail-card">
            <div class="card-body">
                <div class="contacto-detail-heading mb-3">
                    <p class="contactos-kicker mb-1">Perfil interno</p>
                    <h2 class="h5 card-title mb-0">Información laboral</h2>
                </div>
                <div class="row g-3" id="datos_laborales"></div>
            </div>
        </section>

        <section class="card mb-4 contacto-detail-card">
            <div class="card-body">
                <div class="contacto-detail-heading mb-3">
                    <p class="contactos-kicker mb-1">Vínculos vigentes</p>
                    <h2 class="h5 card-title mb-0">Empresas asociadas</h2>
                </div>
                <div id="empresas_contacto"></div>
            </div>
        </section>
    </div>
</div>

<script src="<?= $prefijoRuta ?>js/ver_contacto.js"></script>

<?php require __DIR__ . '/../construct/footer.html'; ?>
