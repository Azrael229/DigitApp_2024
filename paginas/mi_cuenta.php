<?php
$prefijoRuta = '../';
require __DIR__ . '/../construct/header.php';
?>
<div class="container mt-5 mb-5 contain shadow-lg auth-section" data-csrf="<?= htmlspecialchars(auth_csrf(), ENT_QUOTES, 'UTF-8') ?>">
    <div class="contacto-form-header">
        <p class="contactos-kicker mb-1">Seguridad de la cuenta</p>
        <h1 class="h2 mb-0">Mi cuenta</h1>
    </div>
    <div id="cuenta-mensaje" class="alert d-none mt-4" role="status"></div>
    <?php if (isset($_GET['cambio'])): ?>
        <div class="alert alert-warning mt-4">Debes establecer una contraseña personal para continuar.</div>
    <?php endif; ?>
    <div class="row g-4 mt-1">
        <div class="col-lg-5">
            <section class="card border-0 shadow-sm contacto-form-card h-100">
                <div class="card-body p-4">
                    <h2 class="h4">Datos de acceso</h2>
                    <dl class="row mb-0 mt-4">
                        <dt class="col-sm-4">Nombre</dt><dd class="col-sm-8"><?= htmlspecialchars($authUser['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt class="col-sm-4">Correo</dt><dd class="col-sm-8"><?= htmlspecialchars($authUser['correo'], ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt class="col-sm-4">Rol</dt><dd class="col-sm-8"><?= htmlspecialchars(AUTH_ROLE_LABELS[$authUser['rol']] ?? $authUser['rol'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </dl>
                    <hr>
                    <h2 class="h5">Correo de recuperación</h2>
                    <p class="contactos-muted"><?= $authUser['correo_recuperacion'] ? htmlspecialchars($authUser['correo_recuperacion'], ENT_QUOTES, 'UTF-8') : 'Sin correo registrado' ?> · <?= $authUser['correo_recuperacion_verificado_at'] ? 'Verificado' : 'Pendiente de verificación' ?></p>
                    <form id="form-recuperacion" class="mb-4">
                        <div class="mb-3"><label class="form-label" for="correo-recuperacion">Correo alternativo</label><input id="correo-recuperacion" name="correo_recuperacion" type="email" class="form-control" maxlength="190" value="<?= htmlspecialchars((string) $authUser['correo_recuperacion'], ENT_QUOTES, 'UTF-8') ?>" required></div>
                        <div class="mb-3"><label class="form-label" for="recuperacion-password">Contraseña actual</label><input id="recuperacion-password" name="password_actual" type="password" class="form-control" maxlength="128" required></div>
                        <button class="btn btn-outline-secondary" type="submit">Verificar correo</button>
                    </form>
                    <hr>
                    <h2 class="h5">Cambiar contraseña</h2>
                    <form id="form-password">
                        <div class="mb-3"><label class="form-label" for="password-actual">Contraseña actual</label><input id="password-actual" name="password_actual" type="password" class="form-control" maxlength="128" required></div>
                        <div class="mb-3"><label class="form-label" for="password-nueva">Contraseña nueva</label><input id="password-nueva" name="password_nueva" type="password" class="form-control" minlength="5" maxlength="128" required><div class="form-text">Entre 5 y 128 caracteres. Puedes utilizar una frase.</div></div>
                        <div class="mb-3"><label class="form-label" for="password-confirmar">Confirmar contraseña</label><input id="password-confirmar" type="password" class="form-control" minlength="5" maxlength="128" required></div>
                        <button class="btn btn-secondary" type="submit">Cambiar contraseña</button>
                    </form>
                </div>
            </section>
        </div>
        <div class="col-lg-7">
            <section class="card border-0 shadow-sm contacto-form-card">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div><h2 class="h4 mb-1">Dispositivos y sesiones</h2><p class="contactos-muted mb-0">Las sesiones vencen 30 días después de iniciarse.</p></div>
                        <button id="cerrar-todas-propias" class="btn btn-outline-danger" type="button">Cerrar todas mis sesiones</button>
                    </div>
                    <div id="lista-sesiones" class="mt-4"></div>
                    <h3 class="h5 mt-4">Dispositivos confiables</h3>
                    <div id="lista-dispositivos" class="mt-2"></div>
                </div>
            </section>
        </div>
    </div>
</div>
<script src="../js/mi-cuenta.js?v=20261009-1"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
