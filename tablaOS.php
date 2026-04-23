<?php require("construct/header.html"); ?>
<?php require("querys/query_all_ordenes_servicio.php"); ?>

<?php
function osValue($value, $fallback = '-')
{
    if ($value === null) {
        return $fallback;
    }

    $value = trim((string) $value);
    return $value === '' ? $fallback : $value;
}

function osDateValue($value, $fallback = 'Pendiente')
{
    if ($value === null) {
        return $fallback;
    }

    $value = trim((string) $value);
    if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return $fallback;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('Y-m-d', $timestamp);
}

function osMoney($value)
{
    return number_format((float) $value, 2, '.', ',');
}

$alertMap = [
    'orden_creada' => ['success', 'La orden de servicio fue creada correctamente.'],
    'eliminada' => ['warning', 'La orden de servicio fue eliminada correctamente.'],
    'error' => ['danger', 'Ocurrio un error al procesar la orden de servicio.'],
];

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$alert = isset($alertMap[$msg]) ? $alertMap[$msg] : null;
?>

<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg">
    <div class="row">
        <div class="col text-center mt-3 mb-5">
            <h1>Panel de Administrador</h1>
        </div>
    </div>

    <div class="row border-top justify-content-center">
        <div class="row">
            <div class="col text-center mt-3 mb-4">
                <h1>Ordenes de Servicio</h1>
            </div>
        </div>

        <?php if ($alert !== null): ?>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="alert alert-<?= htmlspecialchars($alert[0], ENT_QUOTES, 'UTF-8') ?> py-2 px-3" role="alert">
                        <?= htmlspecialchars($alert[1], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col">
                <div class="col p-4 table-responsive border-top" style="white-space: nowrap;">
                    <table id="tablaOrdenesServicio" class="table table-secondary table-striped w-100 align-middle">
                        <thead>
                            <tr>
                                <th>Fecha creacion</th>
                                <th>Fecha servicio</th>
                                <th>Empresa</th>
                                <th>Contacto</th>
                                <th>Nombre del proyecto</th>
                                <th>Importe</th>
                                <th>Visualizar</th>                               
                                <th>Imprimir</th>
                                <th>Eliminar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_ordenes_servicio): ?>
                                <?php foreach ($result_ordenes_servicio as $row_os): ?>
                                    <?php
                                    $idOs = (int) $row_os['id_os'];
                                    $fechaCreacionOrden = osDateValue($row_os['fecha_creacion']);
                                    $fechaServicioOrden = osDateValue($row_os['fecha_servicio']);
                                    $empresaNombre = osValue($row_os['empresa_nombre']);
                                    $contactoNombre = osValue($row_os['contacto_nombre']);
                                    $nombreProyecto = osValue($row_os['nombre_proyecto'], 'Pendiente');
                                    $importeOrden = osMoney($row_os['importe']);
                                    ?>
                                    <tr>
                                        <td data-order="<?= htmlspecialchars((string) $row_os['fecha_creacion'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($fechaCreacionOrden, ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td data-order="<?= htmlspecialchars((string) $row_os['fecha_servicio'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($fechaServicioOrden, ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td><?= htmlspecialchars($empresaNombre, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($contactoNombre, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($nombreProyecto, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end">$ <?= htmlspecialchars($importeOrden, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <a href="Render_OS.php?id_os=<?= $idOs ?>" class="btn btn-info btn-sm">
                                                Visualizar
                                            </a>
                                        </td>
                                        
                                        <td>
                                            <a href="orden_servicio_print.php?id_os=<?= $idOs ?>"
                                               class="btn btn-secondary btn-sm"
                                               target="_blank"
                                               rel="noopener noreferrer">
                                                Imprimir
                                            </a>
                                        </td>
                                        <td>
                                            <a href="querys/delete_orden_servicio.php?id_os=<?= $idOs ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Eliminar esta orden de servicio?');">
                                                Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- container -->

<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Data Tables 1.13.7 -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>

<!-- Data Tables 1.13.7 boostrap5 -->
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script src="js/datatable-filters.js"></script>
<script src="js/tablaOrdenesServicio.js"></script>

<?php require("construct/footer.html"); ?>
