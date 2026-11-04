document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.auth-section');
    const csrf = root.dataset.csrf;
    const message = document.getElementById('usuarios-mensaje');
    const modalElement = document.getElementById('usuario-modal');
    const modal = new bootstrap.Modal(modalElement);
    const form = document.getElementById('form-usuario');
    const sessionsModal = new bootstrap.Modal(document.getElementById('sesiones-modal'));
    let users = [];
    let auditTable = null;
    const usersTable = new DataTable('#usuarios-table', getDataTableOptions({
        order: [[0, 'asc']],
        orderCellsTop: true,
        columns: [
            { data: 'nombre_completo', render: DataTable.render.text() },
            { data: 'correo', render: DataTable.render.text() },
            { data: 'rol', render: (value, type) => type === 'display' ? escapeTableText(String(value || '').replaceAll('_', ' ')) : value },
            { data: 'estado', render: DataTable.render.text() },
            { data: 'ultimo_acceso_at', defaultContent: '', render: (value, type) => type === 'display' ? (value ? DigitAppDate.dateTime(value) : 'Sin acceso') : (value || '') },
            {
                data: null,
                className: 'auth-user-actions',
                orderable: false,
                searchable: false,
                render: (_value, type, user) => type === 'display'
                    ? `<div class="d-flex flex-nowrap gap-2"><button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" data-user-action="edit" data-user-id="${Number(user.id)}">Editar</button><button type="button" class="btn btn-sm btn-outline-primary text-nowrap" data-user-action="sessions" data-user-id="${Number(user.id)}">Sesiones</button><button type="button" class="btn btn-sm btn-outline-warning text-nowrap" data-user-action="reset" data-user-id="${Number(user.id)}">Restablecer</button><button type="button" class="btn btn-sm btn-outline-success text-nowrap" data-user-action="unlock" data-user-id="${Number(user.id)}">Desbloquear</button></div>`
                    : ''
            }
        ]
    }));
    applyColumnFilters(usersTable);

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

    // Escapa los valores de la bitácora antes de insertarlos como texto visible.
    function escapeTableText(value) {
        const element = document.createElement('span');
        element.textContent = value === null || value === undefined ? '' : String(value);
        return element.innerHTML;
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
            text.textContent = `${session.nombre} · ${DigitAppDate.dateTime(session.ultima_actividad_at)}`;
            item.append(text, actionButton('Cerrar', 'btn-outline-danger', async () => { await adminAction('revoke_session', { sesion_id: session.id }); await openSessions(user); }));
            sessions.append(item);
        });
        const devices = document.getElementById('admin-dispositivos');
        devices.replaceChildren();
        data.devices.forEach((device) => {
            const item = document.createElement('div');
            item.className = 'auth-session-item';
            const text = document.createElement('span');
            text.textContent = `${device.nombre} · ${device.navegador || ''} · ${DigitAppDate.dateTime(device.ultima_actividad_at)}`;
            item.append(text, actionButton('Revocar', 'btn-outline-danger', async () => { await adminAction('revoke_device', { dispositivo_id: device.id }); await openSessions(user); }));
            devices.append(item);
        });
        sessionsModal.show();
    }

    // Carga los usuarios en la tabla paginada sin perder sus acciones por fila.
    async function loadUsers() {
        const response = await fetch('../backend/auth/admin_api.php?action=list', { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'No fue posible consultar los usuarios.');
        users = data.data;
        usersTable.clear().rows.add(users).draw();
    }

    // Recupera la bitácora de seguridad limitada al periodo autorizado.
    async function loadAudit() {
        const response = await fetch('../backend/auth/admin_api.php?action=audit', { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'No fue posible consultar la bitácora.');
        const rows = data.data.map((entry) => [entry.created_at, entry.usuario || 'Sistema', entry.evento, entry.resultado, entry.ip || '', entry.detalle || '']);
        if (!auditTable) {
            auditTable = new DataTable('#bitacora-table', getDataTableOptions({
                data: rows,
                order: [[0, 'desc']],
                orderCellsTop: true,
                columns: [
                    { data: 0, className: 'table-nowrap', render: (value, type) => type === 'display' ? DigitAppDate.dateTime(value) : value },
                    { data: 1, render: DataTable.render.text() },
                    { data: 2, render: DataTable.render.text() },
                    { data: 3, render: DataTable.render.text() },
                    { data: 4, className: 'table-nowrap', render: DataTable.render.text() },
                    {
                        data: 5,
                        className: 'auth-audit-detail',
                        render: (value, type) => type === 'display' ? `<div class="table-text-wrap">${escapeTableText(value)}</div>` : value
                    }
                ]
            }));
            applyColumnFilters(auditTable);
            return;
        }
        auditTable.clear().rows.add(rows).draw();
    }

    document.querySelector('#usuarios-table tbody').addEventListener('click', async (event) => {
        const button = event.target.closest('[data-user-action]');
        if (!button) return;
        const user = users.find((item) => String(item.id) === button.dataset.userId);
        if (!user) return;
        if (button.dataset.userAction === 'edit') { openUser(user); return; }
        if (button.dataset.userAction === 'sessions') {
            openSessions(user).catch((error) => showMessage(error.message, 'danger'));
            return;
        }
        if (button.dataset.userAction === 'reset') {
            const password = window.prompt('Escribe una contraseña temporal de al menos 5 caracteres:');
            if (!password) return;
            try { await adminAction('reset_password', { usuario_id: user.id, password_temporal: password }); showMessage('Contraseña temporal asignada.'); }
            catch (error) { showMessage(error.message, 'danger'); }
            return;
        }
        if (button.dataset.userAction === 'unlock') {
            try { await adminAction('unlock', { usuario_id: user.id }); showMessage('Cuenta desbloqueada.'); await loadUsers(); }
            catch (error) { showMessage(error.message, 'danger'); }
        }
    });

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
