<?php
$prefijoRuta = $prefijoRuta ?? '';
require_once __DIR__ . '/../backend/auth/bootstrap.php';
$authUser = auth_require_login();
$currentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$navigationPages = [
    'index.php' => ['title' => 'Inicio', 'group' => 'inicio'],
    'empresas.php' => ['title' => 'Empresas', 'group' => 'clientes'],
    'form_empresa.php' => ['title' => 'Empresas', 'group' => 'clientes'],
    'ver_empresa.php' => ['title' => 'Empresas', 'group' => 'clientes'],
    'form_direccion_empresa.php' => ['title' => 'Empresas', 'group' => 'clientes'],
    'form_equipo_empresa.php' => ['title' => 'Empresas', 'group' => 'clientes'],
    'contactos.php' => ['title' => 'Contactos', 'group' => 'clientes'],
    'form_contacto.php' => ['title' => 'Contactos', 'group' => 'clientes'],
    'ver_contacto.php' => ['title' => 'Contactos', 'group' => 'clientes'],
    'oportunidades.php' => ['title' => 'Oportunidades comerciales', 'group' => 'comercial'],
    'form_oportunidad.php' => ['title' => 'Oportunidades comerciales', 'group' => 'comercial'],
    'ver_oportunidad.php' => ['title' => 'Oportunidades comerciales', 'group' => 'comercial'],
    'tablaCotizaciones.php' => ['title' => 'Cotizaciones', 'group' => 'comercial'],
    'nuevaCotizacion.php' => ['title' => 'Cotizaciones', 'group' => 'comercial'],
    'ver_cotizacion.php' => ['title' => 'Cotizaciones', 'group' => 'comercial'],
    'ordenes_venta.php' => ['title' => 'Órdenes de venta', 'group' => 'comercial'],
    'form_orden_venta.php' => ['title' => 'Órdenes de venta', 'group' => 'comercial'],
    'ver_orden_venta.php' => ['title' => 'Órdenes de venta', 'group' => 'comercial'],
    'ordenes_servicio.php' => ['title' => 'Órdenes de servicio', 'group' => 'operacion'],
    'form_orden_servicio.php' => ['title' => 'Órdenes de servicio', 'group' => 'operacion'],
    'ver_orden_servicio.php' => ['title' => 'Órdenes de servicio', 'group' => 'operacion'],
    'informe.php' => ['title' => 'Informes metrológicos', 'group' => 'operacion'],
    'productos.php' => ['title' => 'Productos', 'group' => 'productos'],
    'form_producto.php' => ['title' => 'Productos', 'group' => 'productos'],
    'ver_producto.php' => ['title' => 'Productos', 'group' => 'productos'],
    'servicios.php' => ['title' => 'Servicios', 'group' => 'productos'],
    'form_servicio.php' => ['title' => 'Servicios', 'group' => 'productos'],
    'ver_servicio.php' => ['title' => 'Servicios', 'group' => 'productos'],
    'Render_zonas_servicio.php' => ['title' => 'Zonas de servicio', 'group' => 'productos'],
    'facturacion.php' => ['title' => 'Facturación', 'group' => 'facturacion'],
    'dashboard.php' => ['title' => 'Dashboard', 'group' => 'dashboard'],
    'usuarios.php' => ['title' => 'Administración', 'group' => 'usuarios'],
    'mi_cuenta.php' => ['title' => 'Mi cuenta', 'group' => 'cuenta'],
];
$currentNavigationPage = $navigationPages[$currentScript] ?? ['title' => '', 'group' => ''];
$currentSectionTitle = $currentNavigationPage['title'];
$activeNavigationGroup = $currentNavigationPage['group'];
$isCurrentSection = static fn(string $title): bool => $currentSectionTitle === $title;
if (!empty($authUser['debe_cambiar_password']) && $currentScript !== 'mi_cuenta.php') {
    header('Location: ' . $prefijoRuta . 'paginas/mi_cuenta.php?cambio=obligatorio');
    exit;
}
?>
<!doctype html>
<html lang="es" data-theme="original">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        try { document.documentElement.dataset.theme = localStorage.getItem('digitapp:theme') === 'dark' ? 'dark' : 'original'; } catch (error) {}
    </script>
    <title>DigitApp 2024</title>
    <link rel="icon" href="<?= $prefijoRuta ?>imgs/LogoMakr_0mWRyT-1.png" type="image/x-icon">
    <link rel="shortcut icon" href="<?= $prefijoRuta ?>imgs/LogoMakr_0mWRyT-1.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $prefijoRuta ?>estilos/style.css?v=20261101-3">
    <script src="<?= $prefijoRuta ?>js/date-format.js?v=20261009-1"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script defer src="<?= $prefijoRuta ?>js/app-state.js?v=20261031-14"></script>
</head>
<body class="app-sidebar-layout" data-app-version="<?= htmlspecialchars(DIGITAPP_VERSION, ENT_QUOTES, 'UTF-8') ?>" data-user-id="<?= (int) $authUser['id'] ?>" data-section-title="<?= htmlspecialchars($currentSectionTitle, ENT_QUOTES, 'UTF-8') ?>">
<nav class="navbar fixed-top navbar-expand-xl navbar-dark bg-dark app-sidebar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $prefijoRuta ?>index.php"><img src="<?= $prefijoRuta ?>imgs/LogoMakr_0mWRyT-1.png" alt="" width="30"><span>DigitApp</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarDigitApp" aria-controls="navbarDigitApp" aria-expanded="false" aria-label="Mostrar navegación"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarDigitApp">
            <ul class="navbar-nav me-auto mb-2 mb-xl-0">
                <li class="nav-item<?= $activeNavigationGroup === 'inicio' ? ' active' : '' ?>"><a class="nav-link<?= $isCurrentSection('Inicio') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>index.php"<?= $isCurrentSection('Inicio') ? ' aria-current="page"' : '' ?>>Inicio</a></li>
                <?php if (auth_has_permission('clientes', $authUser)): ?>
                <li class="nav-item dropdown<?= $activeNavigationGroup === 'clientes' ? ' active' : '' ?>"><a class="nav-link dropdown-toggle<?= $activeNavigationGroup === 'clientes' ? ' active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown"<?= $activeNavigationGroup === 'clientes' ? ' aria-current="true"' : '' ?>>Clientes</a><ul class="dropdown-menu"><li><a class="dropdown-item<?= $isCurrentSection('Empresas') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/empresas.php"<?= $isCurrentSection('Empresas') ? ' aria-current="page"' : '' ?>>Empresas</a></li><li><a class="dropdown-item<?= $isCurrentSection('Contactos') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/contactos.php"<?= $isCurrentSection('Contactos') ? ' aria-current="page"' : '' ?>>Contactos</a></li></ul></li>
                <?php endif; ?>
                <?php if (auth_has_permission('comercial', $authUser)): ?>
                <li class="nav-item dropdown<?= $activeNavigationGroup === 'comercial' ? ' active' : '' ?>"><a class="nav-link dropdown-toggle<?= $activeNavigationGroup === 'comercial' ? ' active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown"<?= $activeNavigationGroup === 'comercial' ? ' aria-current="true"' : '' ?>>Comercial</a><ul class="dropdown-menu"><li><a class="dropdown-item<?= $isCurrentSection('Oportunidades comerciales') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/oportunidades.php"<?= $isCurrentSection('Oportunidades comerciales') ? ' aria-current="page"' : '' ?>>Oportunidades comerciales</a></li><li><a class="dropdown-item<?= $isCurrentSection('Cotizaciones') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/tablaCotizaciones.php"<?= $isCurrentSection('Cotizaciones') ? ' aria-current="page"' : '' ?>>Cotizaciones</a></li><li><a class="dropdown-item<?= $isCurrentSection('Órdenes de venta') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/ordenes_venta.php"<?= $isCurrentSection('Órdenes de venta') ? ' aria-current="page"' : '' ?>>Órdenes de venta</a></li></ul></li>
                <?php endif; ?>
                <?php if (auth_has_permission('operacion', $authUser)): ?>
                <li class="nav-item dropdown<?= $activeNavigationGroup === 'operacion' ? ' active' : '' ?>"><a class="nav-link dropdown-toggle<?= $activeNavigationGroup === 'operacion' ? ' active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown"<?= $activeNavigationGroup === 'operacion' ? ' aria-current="true"' : '' ?>>Operación</a><ul class="dropdown-menu"><li><a class="dropdown-item<?= $isCurrentSection('Órdenes de servicio') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/ordenes_servicio.php"<?= $isCurrentSection('Órdenes de servicio') ? ' aria-current="page"' : '' ?>>Órdenes de servicio</a></li><li><a class="dropdown-item<?= $isCurrentSection('Informes metrológicos') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/informe.php"<?= $isCurrentSection('Informes metrológicos') ? ' aria-current="page"' : '' ?>>Informes metrológicos</a></li></ul></li>
                <?php endif; ?>
                <?php if (auth_has_permission('productos', $authUser)): ?>
                <li class="nav-item dropdown<?= $activeNavigationGroup === 'productos' ? ' active' : '' ?>"><a class="nav-link dropdown-toggle<?= $activeNavigationGroup === 'productos' ? ' active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown"<?= $activeNavigationGroup === 'productos' ? ' aria-current="true"' : '' ?>>Catálogo comercial</a><ul class="dropdown-menu"><li><a class="dropdown-item<?= $isCurrentSection('Productos') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/productos.php"<?= $isCurrentSection('Productos') ? ' aria-current="page"' : '' ?>>Productos</a></li><li><a class="dropdown-item<?= $isCurrentSection('Servicios') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/servicios.php"<?= $isCurrentSection('Servicios') ? ' aria-current="page"' : '' ?>>Servicios</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item<?= $isCurrentSection('Zonas de servicio') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/Render_zonas_servicio.php"<?= $isCurrentSection('Zonas de servicio') ? ' aria-current="page"' : '' ?>>Zonas de servicio</a></li></ul></li>
                <?php endif; ?>
                <?php if (auth_has_permission('facturacion', $authUser)): ?><li class="nav-item<?= $activeNavigationGroup === 'facturacion' ? ' active' : '' ?>"><a class="nav-link<?= $isCurrentSection('Facturación') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/facturacion.php"<?= $isCurrentSection('Facturación') ? ' aria-current="page"' : '' ?>>Facturación</a></li><?php endif; ?>
                <?php if (auth_has_permission('dashboard', $authUser)): ?><li class="nav-item<?= $activeNavigationGroup === 'dashboard' ? ' active' : '' ?>"><a class="nav-link<?= $isCurrentSection('Dashboard') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/dashboard.php"<?= $isCurrentSection('Dashboard') ? ' aria-current="page"' : '' ?>><i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>Dashboard</a></li><?php endif; ?>
                <?php if (auth_has_permission('usuarios', $authUser)): ?><li class="nav-item<?= $activeNavigationGroup === 'usuarios' ? ' active' : '' ?>"><a class="nav-link<?= $isCurrentSection('Administración') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/usuarios.php"<?= $isCurrentSection('Administración') ? ' aria-current="page"' : '' ?>>Administración</a></li><?php endif; ?>
            </ul>
            <div class="dropdown app-sidebar-user">
                <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true"><i class="bi bi-person-circle" aria-hidden="true"></i> <?= htmlspecialchars($authUser['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></button>
                <ul class="dropdown-menu dropdown-menu-end user-menu">
                    <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars(AUTH_ROLE_LABELS[$authUser['rol']] ?? $authUser['rol'], ENT_QUOTES, 'UTF-8') ?></span></li>
                    <li><a class="dropdown-item<?= $isCurrentSection('Mi cuenta') ? ' active' : '' ?>" href="<?= $prefijoRuta ?>paginas/mi_cuenta.php"<?= $isCurrentSection('Mi cuenta') ? ' aria-current="page"' : '' ?>>Mi cuenta y dispositivos</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <div class="dropdown-item-text theme-switch-row">
                            <label class="form-check-label" for="digitapp-theme-switch"><i class="bi bi-moon-stars" aria-hidden="true"></i> Tema oscuro</label>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="digitapp-theme-switch" aria-label="Cambiar entre tema original y tema oscuro">
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><form method="post" action="<?= $prefijoRuta ?>backend/auth/logout.php"><input type="hidden" name="csrf" value="<?= htmlspecialchars(auth_csrf(), ENT_QUOTES, 'UTF-8') ?>"><button class="dropdown-item text-danger" type="submit">Cerrar sesión</button></form></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
