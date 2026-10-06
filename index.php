<?php
$prefijoRuta = '';
require __DIR__ . '/construct/header.php';
$rolLabel = AUTH_ROLE_LABELS[$authUser['rol']] ?? $authUser['rol'];
?>
<main class="container mt-5 mb-5 contain shadow-lg dashboard-home">
    <section class="dashboard-hero text-center">
        <span class="dashboard-kicker">DigitApp 2024</span>
        <h1>Bienvenido, <?= htmlspecialchars($authUser['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="dashboard-subtitle mb-0">Tu sesión está activa como <?= htmlspecialchars($rolLabel, ENT_QUOTES, 'UTF-8') ?>.</p>
    </section>
    <section class="row g-4 mt-2" aria-label="Información de la sesión">
        <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4"><p class="contactos-kicker mb-2">Correo de acceso</p><strong><?= htmlspecialchars($authUser['correo'], ENT_QUOTES, 'UTF-8') ?></strong></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4"><p class="contactos-kicker mb-2">Rol</p><strong><?= htmlspecialchars($rolLabel, ENT_QUOTES, 'UTF-8') ?></strong></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4"><p class="contactos-kicker mb-2">Sesión</p><strong>Protegida durante 30 días</strong><p class="contactos-muted mb-0 mt-2">Administra tus dispositivos desde Mi cuenta.</p></div></div></div>
    </section>
    <section class="card border-0 shadow-sm mt-4"><div class="card-body p-4"><h2 class="h4">Flujo de trabajo</h2><p class="contactos-muted mb-0">Utiliza la barra superior para avanzar desde Clientes hacia Comercial, Operación y las funciones autorizadas para tu perfil.</p></div></section>
</main>
<?php require __DIR__ . '/construct/footer.html'; ?>
