'use strict';

// Centraliza las etiquetas y estilos de estatus utilizados por las tablas de cotizaciones.
window.CotStatus = {
    labels: {
        preparacion: 'En preparación',
        enviada: 'Enviada',
        aceptada: 'Aceptada',
        rechazada: 'Rechazada',
        vencida: 'Vencida',
        cancelada: 'Cancelada'
    },
    key(value) {
        const text = String(value || '').replace(/<[^>]*>/g, '').trim().toLocaleLowerCase('es-MX');
        if (Object.prototype.hasOwnProperty.call(this.labels, text)) {
            return text;
        }
        if (text.includes('aceptada')) { return 'aceptada'; }
        if (text.includes('cancelada')) { return 'cancelada'; }
        if (text.includes('enviada')) { return 'enviada'; }
        if (text.includes('rechazada')) { return 'rechazada'; }
        if (text.includes('vencida')) { return 'vencida'; }
        return 'preparacion';
    },
    escape(value) {
        const span = document.createElement('span');
        span.textContent = String(value || '');
        return span.innerHTML;
    },
    badge(value) {
        const key = this.key(value);
        return '<span class="badge cot-status cot-status-' + key + '">'
            + this.escape(this.labels[key]) + '</span>';
    }
};
