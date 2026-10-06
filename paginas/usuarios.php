<?php
$prefijoRuta = '../';
require __DIR__ . '/../backend/auth/bootstrap.php';
auth_require_permission('usuarios');
require __DIR__ . '/../construct/header.php';
?>
<div class="container mt-5 mb-5 contain shadow-lg auth-section" data-csrf="<?= htmlspecialchars(auth_csrf(), ENT_QUOTES, 'UTF-8') ?>">
    <div class="contacto-form-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><p class="contactos-kicker mb-1">Administración</p><h1 class="h2 mb-0">Usuarios y seguridad</h1></div>
        <div class="d-flex gap-2"><button id="cerrar-todas-global" type="button" class="btn btn-outline-danger">Cerrar todas las sesiones</button><button id="nuevo-usuario" type="button" class="btn btn-success">Nuevo usuario</button></div>
    </div>
    <div id="usuarios-mensaje" class="alert d-none mt-4" role="status"></div>
    <ul class="nav nav-tabs mt-4" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-usuarios" type="button">Usuarios</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bitacora" type="button">Bitácora</button></li>
    </ul>
    <div class="tab-content pt-4">
        <div id="tab-usuarios" class="tab-pane fade show active">
            <div class="table-responsive data-table-shell"><table class="table table-secondary table-striped align-middle"><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Último acceso</th><th>Acciones</th></tr></thead><tbody id="usuarios-body"></tbody></table></div>
        </div>
        <div id="tab-bitacora" class="tab-pane fade">
            <p class="contactos-muted">Eventos de seguridad de los últimos 90 días.</p>
            <div class="table-responsive data-table-shell"><table class="table table-secondary table-striped align-middle"><thead><tr><th>Fecha</th><th>Usuario</th><th>Evento</th><th>Resultado</th><th>IP</th><th>Detalle</th></tr></thead><tbody id="bitacora-body"></tbody></table></div>
        </div>
    </div>
</div>

<div class="modal fade" id="usuario-modal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="form-usuario">
    <div class="modal-header"><h2 class="modal-title h5">Usuario</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <div class="modal-body"><input type="hidden" name="id"><input type="hidden" name="version"><div class="row g-3">
        <div class="col-12"><label class="form-label">Nombre completo</label><input name="nombre_completo" class="form-control" maxlength="150" required></div>
        <div class="col-md-6"><label class="form-label">Correo de acceso</label><input name="correo" type="email" class="form-control" maxlength="190" required></div>
        <div class="col-md-6"><label class="form-label">Correo de recuperación</label><input name="correo_recuperacion" type="email" class="form-control" maxlength="190"></div>
        <div class="col-md-6"><label class="form-label">Rol</label><select name="rol" class="form-select" required><option value="administrativo">Administrativo</option><option value="tecnico_administrativo">Técnico-administrativo</option><option value="administrador">Administrador</option></select></div>
        <div class="col-md-6"><label class="form-label">Estado</label><select name="estado" class="form-select"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
        <div class="col-12" id="password-temporal-wrap"><label class="form-label">Contraseña temporal</label><input name="password_temporal" type="password" class="form-control" minlength="5" maxlength="128"><div class="form-text">El usuario deberá cambiarla al iniciar sesión.</div></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-secondary">Guardar usuario</button></div>
</form></div></div></div>
<div class="modal fade" id="sesiones-modal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5">Sesiones y dispositivos</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><h3 class="h6">Sesiones activas</h3><div id="admin-sesiones"></div><h3 class="h6 mt-4">Dispositivos confiables</h3><div id="admin-dispositivos"></div></div></div></div></div>
<script src="../js/usuarios.js"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
