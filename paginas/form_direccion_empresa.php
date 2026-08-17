<?php
$prefijoRuta = '../';
$empresaId = filter_input(INPUT_GET, 'empresa_id', FILTER_VALIDATE_INT);
$direccionId = filter_input(INPUT_GET, 'direccion_id', FILTER_VALIDATE_INT);
?>
<?php require (__DIR__ . "/../construct/header.php"); ?>

<div class="container mt-5 mb-5 contain shadow-lg">
    <div class="row align-items-center pt-3 mb-4">
        <div class="col">
            <h3 id="titulo_direccion">Nueva dirección</h3>
        </div>
        <div class="col-12 col-md-auto mt-2 mt-md-0">
            <a href="ver_empresa.php?id=<?= htmlspecialchars((string) $empresaId) ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver a empresa</a>
        </div>
    </div>

    <?php if ($empresaId === false || $empresaId === null): ?>
        <div class="alert alert-danger">No se indicó una empresa válida.</div>
    <?php else: ?>
        <form action="<?= $prefijoRuta ?>backend/empresas/guardar_direccion_empresa.php" method="POST">
            <input type="hidden" name="empresa_id" value="<?= htmlspecialchars((string) $empresaId) ?>">
            <input type="hidden" name="direccion_id" id="direccion_id" value="<?= htmlspecialchars((string) ($direccionId ?: '')) ?>">

            <div class="card mb-4 empresa-form-card">
                <div class="card-body empresa-card-body">
                    <h5 class="card-title">Información de dirección</h5>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="tipo_direccion">Tipo *</label>
                            <select class="form-control" name="tipo_direccion" id="tipo_direccion" required>
                                <option value="entrega">Entrega</option>
                                <option value="fiscal">Fiscal</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label" for="alias">Alias</label>
                            <input class="form-control" type="text" name="alias" id="alias" maxlength="100" placeholder="Ej. Planta Querétaro">
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label" for="calle">Calle *</label>
                            <input class="form-control" type="text" name="calle" id="calle" maxlength="150" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label" for="numero_exterior">No. exterior</label>
                            <input class="form-control" type="text" name="numero_exterior" id="numero_exterior" maxlength="30">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label" for="numero_interior">No. interior</label>
                            <input class="form-control" type="text" name="numero_interior" id="numero_interior" maxlength="30">
                        </div>
                        <div class="col-12 col-md-6"><label class="form-label" for="colonia">Colonia</label><input class="form-control" type="text" name="colonia" id="colonia" maxlength="150"></div>
                        <div class="col-12 col-md-6"><label class="form-label" for="localidad">Localidad</label><input class="form-control" type="text" name="localidad" id="localidad" maxlength="150"></div>
                        <div class="col-12 col-md-4"><label class="form-label" for="municipio">Municipio o alcaldía</label><input class="form-control" type="text" name="municipio" id="municipio" maxlength="150"></div>
                        <div class="col-12 col-md-4"><label class="form-label" for="ciudad">Ciudad</label><input class="form-control" type="text" name="ciudad" id="ciudad" maxlength="150"></div>
                        <div class="col-6 col-md-2"><label class="form-label" for="estado">Estado</label><input class="form-control" type="text" name="estado" id="estado" maxlength="150"></div>
                        <div class="col-6 col-md-2"><label class="form-label" for="codigo_postal">C.P.</label><input class="form-control" type="text" name="codigo_postal" id="codigo_postal" maxlength="10"></div>
                        <div class="col-12 col-md-6"><label class="form-label" for="pais">País</label><input class="form-control" type="text" name="pais" id="pais" value="México" maxlength="100"></div>
                        <div class="col-12 col-md-6"><label class="form-label" for="entre_calles">Entre calles</label><input class="form-control" type="text" name="entre_calles" id="entre_calles" maxlength="255"></div>
                        <div class="col-12"><label class="form-label" for="referencia">Referencia</label><textarea class="form-control" name="referencia" id="referencia" rows="2"></textarea></div>
                        <div class="col-12"><label class="form-label" for="enlace_maps">Enlace de Google Maps</label><input class="form-control" type="url" name="enlace_maps" id="enlace_maps" maxlength="500"></div>
                        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="es_principal" id="es_principal"><label class="form-check-label" for="es_principal">Marcar como dirección principal</label></div></div>
                    </div>
                </div>
            </div>
            <div class="d-grid gap-2 col-12 col-md-4 mx-auto"><button class="btn btn-success" type="submit">Guardar dirección</button></div>
        </form>
    <?php endif; ?>
</div>

<script src="<?= $prefijoRuta ?>js/form_direccion_empresa.js"></script>

<?php require (__DIR__ . "/../construct/footer.html"); ?>
