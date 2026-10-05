'use strict';

// Comparte formato, peticiones y etiquetas entre las pantallas de órdenes de venta.
window.OV = {
    statuses: {
        pendiente: 'Pendiente de planificación',
        planificacion: 'En planificación',
        en_ejecucion: 'En ejecución',
        parcial: 'Parcialmente atendida',
        completada: 'Completada',
        cancelada: 'Cancelada'
    },
    quoteStatuses: {
        preparacion: 'En preparación', enviada: 'Enviada', aceptada: 'Aceptada',
        rechazada: 'Rechazada', vencida: 'Vencida', cancelada: 'Cancelada'
    },
    // Escapa texto dinámico antes de insertarlo en fragmentos HTML controlados.
    escape(value) {
        const element = document.createElement('span');
        element.textContent = String(value ?? '');
        return element.innerHTML;
    },
    // Presenta fechas SQL con el formato regional y conserva los valores inválidos para diagnóstico.
    date(value, includeTime = false) {
        if (!value) { return '—'; }
        const normalized = String(value).replace(' ', 'T');
        const date = new Date(includeTime ? normalized : normalized.slice(0, 10) + 'T12:00:00');
        if (Number.isNaN(date.getTime())) { return String(value); }
        return new Intl.DateTimeFormat('es-MX', includeTime
            ? {dateStyle: 'medium', timeStyle: 'short'}
            : {dateStyle: 'medium'}).format(date);
    },
    // Formatea importes de la orden en pesos mexicanos.
    money(value) {
        return new Intl.NumberFormat('es-MX', {style: 'currency', currency: 'MXN'}).format(Number(value || 0));
    },
    // Convierte la clave de una cotización en su etiqueta legible.
    statusLabel(value) {
        const key = String(value || '').trim().toLowerCase();
        return this.quoteStatuses[key] || value || 'Sin estatus';
    },
    // Genera la insignia segura correspondiente al estado de una orden de venta.
    badge(value) {
        const key = Object.prototype.hasOwnProperty.call(this.statuses, value) ? value : 'pendiente';
        return `<span class="badge ov-status ov-status-${key}">${this.escape(this.statuses[key])}</span>`;
    },
    // Ejecuta lecturas o escrituras de la API y normaliza sus errores.
    async request(action, values = {}, write = false) {
        const options = write ? {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: new URLSearchParams({...values, action, csrf: document.querySelector('[data-ov-csrf]')?.dataset.ovCsrf || ''})
        } : {};
        const url = '../backend/ordenes_venta/api.php' + (write ? '' : '?' + new URLSearchParams({...values, action}));
        const response = await fetch(url, options);
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) { throw new Error(data.error || 'No fue posible completar la operación.'); }
        return data;
    },
    // Muestra un aviso accesible de éxito o error dentro de la pantalla actual.
    message(id, text, success = false) {
        const box = document.getElementById(id);
        box.textContent = text;
        box.className = `alert ${success ? 'alert-success' : 'alert-danger'} mt-4`;
    },
    // Resume las fechas y la versión utilizada por el control de concurrencia.
    audit(order) {
        return `Creada ${this.date(order.created_at, true)} · Última actualización ${this.date(order.updated_at, true)} · Versión ${order.version}`;
    }
};
