'use strict';
window.Op = {
    statuses: {preparacion: 'En preparación', cotizada: 'Cotizada', negociacion: 'En negociación', ganada: 'Ganada', perdida: 'Perdida', cancelada: 'Cancelada'},
    csrf: document.querySelector('.oportunidades-page').dataset.csrf,
    // Escapa texto antes de insertarlo en plantillas HTML de la interfaz.
    escape(value) {
        const span = document.createElement('span');
        span.textContent = value == null ? '' : String(value);
        return span.innerHTML;
    },
    // Formatea un importe para mostrarlo como moneda mexicana.
    money(value) { return Number(value || 0).toLocaleString('es-MX', {style: 'currency', currency: 'MXN'}); },
    // Suma importes en centavos exactos para evitar errores de punto flotante.
    total(rows) {
        const cents = rows.reduce((sum, row) => {
            const [whole, decimals = ''] = String(row.importe).split('.');
            return sum + BigInt(whole) * 100n + BigInt(decimals.padEnd(2, '0'));
        }, 0n);
        return '$' + (cents / 100n).toLocaleString('es-MX') + '.' + String(cents % 100n).padStart(2, '0');
    },
    // Indica si una fecha se encuentra dentro del rango inclusivo seleccionado.
    inDateRange(date, from, to) { return (!from || date >= from) && (!to || date <= to); },
    // Convierte una fecha SQL al formato general AAAA-MM-DD.
    date(value) { return DigitAppDate.date(value); },
    // Muestra u oculta mensajes de resultado en el contenedor indicado.
    message(id, message, success = false) {
        const element = document.getElementById(id);
        element.textContent = message;
        element.className = 'alert mt-4 ' + (message ? (success ? 'alert-success' : 'alert-danger') : 'd-none');
    },
    // Ejecuta una operación del API y normaliza sus errores para la interfaz.
    async request(action, params = {}, write = false) {
        const options = {credentials: 'same-origin'};
        let url = '../backend/oportunidades/api.php';
        if (write) {
            options.method = 'POST';
            options.body = new URLSearchParams({...params, action, csrf: this.csrf});
        } else {
            url += '?' + new URLSearchParams({...params, action});
        }
        const response = await fetch(url, options);
        let data;
        try { data = await response.json(); } catch (_) { throw new Error('No se pudo leer la respuesta del servidor.'); }
        if (!response.ok || data.error) { throw new Error(data.error || 'No se pudo completar la operación.'); }
        return data;
    },
    // Construye una dirección legible a partir de sus campos normalizados.
    address(d) {
        const street = [d.calle, d.numero_exterior, d.numero_interior ? 'Int. ' + d.numero_interior : ''].filter(Boolean).join(' ');
        const location = [street, d.colonia, d.localidad, d.municipio !== d.ciudad ? d.municipio : '', d.ciudad, d.estado, d.codigo_postal].filter(Boolean);
        return location.length ? [...location, d.pais].filter(Boolean).join(', ') : (d.direccion_original || d.pais || 'Sin dirección asignada');
    },
    // Resume las fechas y usuarios de control de una oportunidad.
    audit(op) {
        return 'Registro: ' + DigitAppDate.dateTime(op.created_at) + ' · Última modificación: ' + DigitAppDate.dateTime(op.updated_at) + ' · Usuario creador: pendiente de asignación · Último editor: pendiente de asignación';
    },
    // Reemplaza las opciones de un selector y conserva una selección válida.
    select(element, options, placeholder, selected = '') {
        element.replaceChildren(new Option(placeholder, ''));
        options.forEach(option => element.add(new Option(option.text, option.id)));
        if (selected && !options.some(option => String(option.id) === String(selected))) {
            element.add(new Option('Selección anterior no disponible; elige otra opción', selected));
        }
        element.value = selected || '';
        if (window.jQuery && jQuery.fn.select2) { jQuery(element).trigger('change.select2'); }
    }
};
