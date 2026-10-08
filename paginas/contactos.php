<?php
$prefijoRuta = '../';
require __DIR__ . '/../backend/contactos/query_all_contactos.php';
require_once __DIR__ . '/../backend/helpers/entity_links.php';
require __DIR__ . '/../construct/header.php';

function escaparContacto($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<div class="container mt-5 mb-5 contain shadow-lg contactos-directorio">
    <div class="row align-items-center contactos-header">
        <div class="col p-2 text-center text-md-start">
            <p class="contactos-kicker mb-1">Directorio</p>
            <h1 class="h2 mb-0">Contactos</h1>
        </div>
        <div class="col-12 col-md-auto text-center text-md-end pb-2 pb-md-0">
            <a href="form_contacto.php" class="btn btn-secondary">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Añadir contacto
            </a>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12 col-md-4 col-xl-3">
            <label for="filtro_empresa_contactos" class="form-label">Relación empresarial</label>
            <select id="filtro_empresa_contactos" class="form-select">
                <option value="todos">Todos los contactos</option>
                <option value="con_empresa">Con empresa</option>
                <option value="sin_empresa">Sin empresa</option>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="table-responsive data-table-shell contactos-table-wrap">
                <table id="example" class="table table-secondary table-striped align-middle contactos-table mb-0">
                    <caption class="visually-hidden">Directorio de contactos</caption>
                    <thead>
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col">Teléfono</th>
                            <th scope="col">Correo</th>
                            <th scope="col">Departamento</th>
                            <th scope="col">Razón social principal / empresas</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result_contactos as $contacto): ?>
                            <?php
                            $id = (int) $contacto['id'];
                            $empresa = trim((string) ($contacto['empresa'] ?? ''));
                            $totalEmpresas = (int) ($contacto['empresas_relacionadas'] ?? 0);
                            $empresaTexto = $empresa === '' ? 'Sin empresa' : $empresa;
                            if ($totalEmpresas > 1) {
                                $empresaTexto .= ' (+' . ($totalEmpresas - 1) . ')';
                            }
                            $activo = (int) ($contacto['activo'] ?? 0) === 1;
                            ?>
                            <tr data-company-count="<?= $totalEmpresas ?>">
                                <td>
                                    <?= renderizarEnlaceEntidad('contacto', $id, (string) $contacto['nombre']) ?>
                                </td>
                                <td><?= escaparContacto($contacto['celular'] ?: '—') ?></td>
                                <td class="contactos-email"><?= escaparContacto($contacto['correo'] ?: '—') ?></td>
                                <td><?= escaparContacto($contacto['departamento'] ?: '—') ?></td>
                                <td class="contactos-empresas">
                                    <?php if ($totalEmpresas === 0): ?>
                                        <span class="badge contactos-sin-empresa">Sin empresa</span>
                                    <?php else: ?>
                                        <?= renderizarEnlaceEntidad('empresa', $contacto['empresa_id'] ?? null, $empresaTexto) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge contactos-status <?= $activo ? 'contactos-status-active' : 'contactos-status-inactive' ?>">
                                        <?= $activo ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="<?= $prefijoRuta ?>js/datatable-filters.js?v=20261008-2"></script>
<script src="<?= $prefijoRuta ?>js/datatable-config.js"></script>
<script src="<?= $prefijoRuta ?>js/tablaContactos.js?v=20261006-1"></script>

<?php require __DIR__ . '/../construct/footer.html'; ?>
