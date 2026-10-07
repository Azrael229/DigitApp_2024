<?php
$prefijoRuta = $prefijoRuta ?? '';
require_once __DIR__ . '/../backend/auth/bootstrap.php';
$authUser = auth_require_login();
$currentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (!empty($authUser['debe_cambiar_password']) && $currentScript !== 'mi_cuenta.php') {
    header('Location: ' . $prefijoRuta . 'paginas/mi_cuenta.php?cambio=obligatorio');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DigitApp 2024</title>
    <link rel="icon" href="<?= $prefijoRuta ?>imgs/LogoMakr_0mWRyT-1.png" type="image/x-icon">
    <link rel="shortcut icon" href="<?= $prefijoRuta ?>imgs/LogoMakr_0mWRyT-1.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $prefijoRuta ?>estilos/style.css?v=20261006-7">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script defer src="<?= $prefijoRuta ?>js/app-state.js?v=20261005-1"></script>
</head>
<body data-app-version="<?= htmlspecialchars(DIGITAPP_VERSION, ENT_QUOTES, 'UTF-8') ?>" data-user-id="<?= (int) $authUser['id'] ?>">
<nav class="navbar fixed-top navbar-expand-xl navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $prefijoRuta ?>index.php"><img src="<?= $prefijoRuta ?>imgs/LogoMakr_0mWRyT-1.png" alt="" width="30"><span>DigitApp</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarDigitApp" aria-controls="navbarDigitApp" aria-expanded="false" aria-label="Mostrar navegación"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarDigitApp">
            <ul class="navbar-nav me-auto mb-2 mb-xl-0">
                <li class="nav-item"><a class="nav-link" href="<?= $prefijoRuta ?>index.php">Inicio</a></li>
                <?php if (auth_has_permission('clientes', $authUser)): ?>
                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Clientes</a><ul class="dropdown-menu"><li><a class="dropdown-item" href="<?= $prefijoRuta ?>paginas/empresas.php">Empresas</a></li><li><a class="dropdown-item" href="<?= $prefijoRuta ?>paginas/contactos.php">Contactos</a></li></ul></li>
                <?php endif; ?>
                <?php if (auth_has_permission('comercial', $authUser)): ?>
                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Comercial</a><ul class="dropdown-menu"><li><a class="dropdown-item" href="<?= $prefijoRuta ?>paginas/oportunidades.php">Oportunidades comerciales</a></li><li><a class="dropdown-item" href="<?= $prefijoRuta ?>paginas/tablaCotizaciones.php">Cotizaciones</a></li><li><a class="dropdown-item" href="<?= $prefijoRuta ?>paginas/ordenes_venta.php">Órdenes de venta</a></li></ul></li>
                <?php endif; ?>
                <?php if (auth_has_permission('operacion', $authUser)): ?>
                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Operación</a><ul class="dropdown-menu"><li><a class="dropdown-item" href="<?= $prefijoRuta ?>paginas/ordenes_servicio.php">Órdenes de servicio</a></li><li><a class="dropdown-item" href="<?= $prefijoRuta ?>paginas/informe.php">Informes metrológicos</a></li></ul></li>
                <?php endif; ?>
                <?php if (auth_has_permission('productos', $authUser)): ?>
                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Catálogos</a><ul class="dropdown-menu"><li><a class="dropdown-item" href="<?= $prefijoRuta ?>paginas/Render_zonas_servicio.php">Zonas de servicio</a></li></ul></li>
                <?php endif; ?>
                <?php if (auth_has_permission('facturacion', $authUser)): ?><li class="nav-item"><a class="nav-link" href="<?= $prefijoRuta ?>paginas/facturacion.php">Facturación</a></li><?php endif; ?>
                <?php if (auth_has_permission('usuarios', $authUser)): ?><li class="nav-item"><a class="nav-link" href="<?= $prefijoRuta ?>paginas/usuarios.php">Administración</a></li><?php endif; ?>
            </ul>
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="bi bi-person-circle" aria-hidden="true"></i> <?= htmlspecialchars($authUser['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></button>
                <ul class="dropdown-menu dropdown-menu-end"><li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars(AUTH_ROLE_LABELS[$authUser['rol']] ?? $authUser['rol'], ENT_QUOTES, 'UTF-8') ?></span></li><li><a class="dropdown-item" href="<?= $prefijoRuta ?>paginas/mi_cuenta.php">Mi cuenta y dispositivos</a></li><li><hr class="dropdown-divider"></li><li><form method="post" action="<?= $prefijoRuta ?>backend/auth/logout.php"><input type="hidden" name="csrf" value="<?= htmlspecialchars(auth_csrf(), ENT_QUOTES, 'UTF-8') ?>"><button class="dropdown-item text-danger" type="submit">Cerrar sesión</button></form></li></ul>
            </div>
        </div>
    </div>
</nav>
