'use strict';

// Comparte catálogos, formato y peticiones entre las pantallas de órdenes de servicio.
window.OS = {
    types: {
        calibracion: 'Calibración', entrega_equipo: 'Entrega de equipo del cliente',
        recoleccion_equipo: 'Recolección de equipo', recepcion_equipo: 'Recepción de equipo',
        mantenimiento_preventivo: 'Mantenimiento preventivo', ajuste: 'Ajuste',
        inspeccion: 'Inspección', diagnostico: 'Diagnóstico', correctivo: 'Mantenimiento correctivo'
    },
    statuses: {pendiente: 'Pendiente', programada: 'Programada', en_ejecucion: 'En ejecución', ejecutada: 'Ejecutada', cancelada: 'Cancelada'},
    escape(value) {
        const element = document.createElement('span');
        element.textContent = String(value ?? '');
        return element.innerHTML;
    },
    date(value, includeTime = false) {
        if (!value) { return '—'; }
        const normalized = String(value).replace(' ', 'T');
        const date = new Date(includeTime ? normalized : normalized.slice(0, 10) + 'T12:00:00');
        if (Number.isNaN(date.getTime())) { return String(value); }
        return new Intl.DateTimeFormat('es-MX', includeTime ? {dateStyle: 'medium', timeStyle: 'short'} : {dateStyle: 'medium'}).format(date);
    },
    badge(value) {
        const key = Object.prototype.hasOwnProperty.call(this.statuses, value) ? value : 'pendiente';
        return `<span class="badge os-status os-status-${key}">${this.escape(this.statuses[key])}</span>`;
    },
    async request(action, values = {}, write = false) {
        const options = write ? {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body: new URLSearchParams({...values, action, csrf: document.querySelector('[data-ov-csrf]')?.dataset.ovCsrf || ''})} : {};
        const url = '../backend/ordenes_servicio/api.php' + (write ? '' : '?' + new URLSearchParams({...values, action}));
        const response = await fetch(url, options);
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) { throw new Error(data.error || 'No fue posible completar la operación.'); }
        return data;
    },
    message(id, text) {
        const box = document.getElementById(id);
        box.textContent = text;
        box.className = 'alert alert-danger mt-4';
    }
};
