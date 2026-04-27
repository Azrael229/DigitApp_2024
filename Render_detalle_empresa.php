<?php  require ("construct/header.html")   ?>

<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg" ">

    <!-- row de titulo -->
    <div class="row">
        <div class="col text-center mt-3 mb-5">
            <h1><span>Titulo Render</span></h1>
        </div>
    </div>
    <!-- row de titulo -->

    <?php if (isset($_GET["guardado"]) && $_GET["guardado"] == "1"): ?>
        <div class="alert alert-success" role="alert">
            Empresa guardada correctamente.
        </div>
    <?php endif; ?>

    <!-- row de Contenido -->
    <div class="row border-top justify-content-center ">
        <!-- fila contenido  -->
        <div class="row">
            <div class="col text-center mt-3 mb-5">
                <!-- crear aqui -->
            </div>
        </div>
        <!-- fila contenido  -->
    </div>
    <!-- row de Contenido -->

</div>
<!-- container -->
