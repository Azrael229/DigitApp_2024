document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.auth-section');
    if (!root) return;
    const csrf = root.dataset.csrf;
    const message = document.getElementById('cuenta-mensaje');

    // Muestra una respuesta uniforme para las acciones de seguridad de la cuenta.
    function showMessage(text, type = 'success') {
        message.textContent = text;
        message.className = `alert alert-${type} mt-4`;
    }

    // Ejecuta una acción autenticada de la cuenta y normaliza los errores JSON.
    async function accountAction(action, values = {}) {
        const body = new URLSearchParams({ action, csrf, ...values });
        const response = await fetch('../backend/auth/account_api.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'No fue posible completar la operación.');
        return data;
    }

    // Carga y representa únicamente las sesiones activas del usuario actual.
    async function loadSessions() {
        const response = await fetch('../backend/auth/account_api.php?action=overview', { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'No fue posible consultar las sesiones.');
        const container = document.getElementById('lista-sesiones');
        container.replaceChildren();
        data.sessions.forEach((session) => {
            const item = document.createElement('div');
            item.className = 'auth-session-item';
            const info = document.createElement('div');
            const title = document.createElement('strong');
            title.textContent = `${session.nombre}${Number(session.actual) ? ' · Sesión actual' : ''}`;
            const meta = document.createElement('div');
            meta.className = 'auth-session-meta';
            meta.textContent = `${session.navegador || ''} · ${session.plataforma || ''} · Última actividad: ${DigitAppDate.dateTime(session.ultima_actividad_at)}`;
            info.append(title, meta);
            item.append(info);
            if (!Number(session.actual)) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-outline-danger';
                button.textContent = 'Cerrar sesión';
                button.addEventListener('click', async () => {
                    await accountAction('revoke_session', { id: session.id });
                    await loadSessions();
                });
                item.append(button);
            }
            container.append(item);
        });
        const devices = document.getElementById('lista-dispositivos');
        devices.replaceChildren();
        data.devices.forEach((device) => {
            const item = document.createElement('div');
            item.className = 'auth-session-item';
            const info = document.createElement('div');
            const title = document.createElement('strong');
            title.textContent = `${device.nombre}${Number(device.actual) ? ' · Dispositivo actual' : ''}`;
            const meta = document.createElement('div');
            meta.className = 'auth-session-meta';
            meta.textContent = `${device.navegador || ''} · ${device.plataforma || ''} · Último uso: ${DigitAppDate.dateTime(device.ultima_actividad_at)}`;
            info.append(title, meta);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-outline-danger';
            button.textContent = 'Quitar confianza';
            button.addEventListener('click', async () => {
                if (!window.confirm('Este dispositivo deberá iniciar sesión nuevamente. ¿Continuar?')) return;
                const result = await accountAction('revoke_device', { id: device.id });
                if (result.reload && Number(device.actual)) window.location.href = '../login.php';
                else await loadSessions();
            });
            item.append(info, button);
            devices.append(item);
        });
    }

    document.getElementById('form-password').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        if (form.querySelector('#password-nueva').value !== form.querySelector('#password-confirmar').value) {
            showMessage('La confirmación de la contraseña no coincide.', 'danger');
            return;
        }
        try {
            const data = await accountAction('change_password', Object.fromEntries(new FormData(form)));
            form.reset();
            showMessage(data.message);
        } catch (error) {
            showMessage(error.message, 'danger');
        }
    });

    document.getElementById('form-recuperacion').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        try {
            const data = await accountAction('update_recovery', Object.fromEntries(new FormData(form)));
            form.querySelector('[name="password_actual"]').value = '';
            showMessage(data.message);
        } catch (error) {
            showMessage(error.message, 'danger');
        }
    });

    document.getElementById('cerrar-todas-propias').addEventListener('click', async () => {
        if (!window.confirm('Se cerrarán todas tus sesiones, incluida la actual. ¿Continuar?')) return;
        try {
            const data = await accountAction('close_all');
            window.location.href = data.redirect;
        } catch (error) {
            showMessage(error.message, 'danger');
        }
    });

    loadSessions().catch((error) => showMessage(error.message, 'danger'));
});
