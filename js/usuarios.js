document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.auth-section');
    const csrf = root.dataset.csrf;
    const message = document.getElementById('usuarios-mensaje');
    const modalElement = document.getElementById('usuario-modal');
    const modal = new bootstrap.Modal(modalElement);
    const form = document.getElementById('form-usuario');
    const sessionsModal = new bootstrap.Modal(document.getElementById('sesiones-modal'));
    let users = [];

    // Presenta mensajes de administración sin insertar contenido HTML recibido.
    function showMessage(text, type = 'success') {
        message.textContent = text;
        message.className = `alert alert-${type} mt-4`;
    }

    // Envía operaciones administrativas protegidas por el token de sesión.
    async function adminAction(action, values = {}) {
        const body = new URLSearchParams({ action, csrf, ...values });
        const response = await fetch('../backend/auth/admin_api.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'No fue posible completar la operación.');
        return data;
    }

    // Crea un botón compacto con una acción segura y reutilizable.
    function actionButton(label, className, handler) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `btn btn-sm ${className}`;
        button.textContent = label;
        button.addEventListener('click', handler);
        return button;
    }

    // Abre el formulario para alta o edición sin duplicar una segunda vista.
    function openUser(user = null) {
        form.reset();
        form.elements.id.value = user?.id || '';
        form.elements.version.value = user?.version || '';
        form.elements.nombre_completo.value = user?.nombre_completo || '';
        form.elements.correo.value = user?.correo || '';
        form.elements.correo_recuperacion.value = user?.correo_recuperacion || '';
        form.elements.rol.value = user?.rol || 'administrativo';
        form.elements.estado.value = user?.estado || 'activo';
        const wrap = document.getElementById('password-temporal-wrap');
        wrap.classList.toggle('d-none', Boolean(user));
        form.elements.password_temporal.required = !user;
        modal.show();
    }

    // Consulta las sesiones y dispositivos de un usuario sin exponer sus tokens.
    async function openSessions(user) {
        const response = await fetch(`../backend/auth/admin_api.php?action=sessions&usuario_id=${encodeURIComponent(user.id)}`, { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'No fue posible consultar las sesiones.');
        const sessions = document.getElementById('admin-sesiones');
        sessions.replaceChildren();
        data.sessions.forEach((session) => {
            const item = document.createElement('div');
            item.className = 'auth-session-item';
            const text = document.createElement('span');
            text.textContent = `${session.nombre} · ${session.ultima_actividad_at}`;
            item.append(text, actionButton('Cerrar', 'btn-outline-danger', async () => { await adminAction('revoke_session', { sesion_id: session.id }); await openSessions(user); }));
            sessions.append(item);
        });
        const devices = document.getElementById('admin-dispositivos');
        devices.replaceChildren();
        data.devices.forEach((device) => {
            const item = document.createElement('div');
            item.className = 'auth-session-item';
            const text = document.createElement('span');
            text.textContent = `${device.nombre} · ${device.navegador || ''} · ${device.ultima_actividad_at}`;
            item.append(text, actionButton('Revocar', 'btn-outline-danger', async () => { await adminAction('revoke_device', { dispositivo_id: device.id }); await openSessions(user); }));
            devices.append(item);
        });
        sessionsModal.show();
    }

    // Carga la tabla de usuarios y enlaza sus acciones administrativas.
    async function loadUsers() {
        const response = await fetch('../backend/auth/admin_api.php?action=list', { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'No fue posible consultar los usuarios.');
        users = data.data;
        const body = document.getElementById('usuarios-body');
        body.replaceChildren();
        users.forEach((user) => {
            const row = document.createElement('tr');
            [user.nombre_completo, user.correo, user.rol.replaceAll('_', ' '), user.estado, user.ultimo_acceso_at || 'Sin acceso'].forEach((value) => {
                const cell = document.createElement('td');
                cell.textContent = value;
                row.append(cell);
            });
            const actions = document.createElement('td');
            actions.className = 'd-flex flex-wrap gap-2';
            actions.append(actionButton('Editar', 'btn-outline-secondary', () => openUser(user)));
            actions.append(actionButton('Sesiones', 'btn-outline-primary', () => openSessions(user).catch((error) => showMessage(error.message, 'danger'))));
            actions.append(actionButton('Restablecer', 'btn-outline-warning', async () => {
                const password = window.prompt('Escribe una contraseña temporal de al menos 5 caracteres:');
                if (!password) return;
                try { await adminAction('reset_password', { usuario_id: user.id, password_temporal: password }); showMessage('Contraseña temporal asignada.'); }
                catch (error) { showMessage(error.message, 'danger'); }
            }));
            actions.append(actionButton('Desbloquear', 'btn-outline-success', async () => {
                try { await adminAction('unlock', { usuario_id: user.id }); showMessage('Cuenta desbloqueada.'); await loadUsers(); }
                catch (error) { showMessage(error.message, 'danger'); }
            }));
            row.append(actions);
            body.append(row);
        });
    }

    // Recupera la bitácora de seguridad limitada al periodo autorizado.
    async function loadAudit() {
        const response = await fetch('../backend/auth/admin_api.php?action=audit', { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'No fue posible consultar la bitácora.');
        const body = document.getElementById('bitacora-body');
        body.replaceChildren();
        data.data.forEach((entry) => {
            const row = document.createElement('tr');
            [entry.created_at, entry.usuario || 'Sistema', entry.evento, entry.resultado, entry.ip || '', entry.detalle || ''].forEach((value) => {
                const cell = document.createElement('td');
                cell.textContent = value;
                row.append(cell);
            });
            body.append(row);
        });
    }

    document.getElementById('nuevo-usuario').addEventListener('click', () => openUser());
    document.querySelector('[data-bs-target="#tab-bitacora"]').addEventListener('shown.bs.tab', () => loadAudit().catch((error) => showMessage(error.message, 'danger')));
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            await adminAction('save', Object.fromEntries(new FormData(form)));
            modal.hide();
            showMessage('Usuario guardado correctamente.');
            await loadUsers();
        } catch (error) {
            showMessage(error.message, 'danger');
        }
    });
    document.getElementById('cerrar-todas-global').addEventListener('click', async () => {
        if (!window.confirm('Se cerrarán las sesiones de todos los usuarios, incluida la actual. ¿Continuar?')) return;
        try { await adminAction('revoke_all'); window.location.href = '../login.php'; }
        catch (error) { showMessage(error.message, 'danger'); }
    });
    loadUsers().catch((error) => showMessage(error.message, 'danger'));
});
