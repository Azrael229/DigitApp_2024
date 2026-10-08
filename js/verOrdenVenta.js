'use strict';

const ovDetailId = new URLSearchParams(location.search).get('id');
let ovCurrentOrder = null;
const ovServiceTypes = {
    calibracion: 'Calibración', entrega_equipo: 'Entrega de equipo del cliente',
    recoleccion_equipo: 'Recolección de equipo', recepcion_equipo: 'Recepción de equipo',
    mantenimiento_preventivo: 'Mantenimiento preventivo', ajuste: 'Ajuste',
    inspeccion: 'Inspección', diagnostico: 'Diagnóstico', correctivo: 'Mantenimiento correctivo'
};
const ovServiceStatuses = {
    pendiente: 'Pendiente', programada: 'Programada', en_ejecucion: 'En ejecución',
    ejecutada: 'Ejecutada', cancelada: 'Cancelada'
};

// Crea una celda de tabla sin interpretar el contenido como HTML.
function ovCell(row, value) {
    const cell = document.createElement('td');
    cell.textContent = value || '—';
    row.appendChild(cell);
    return cell;
}

// Presenta las órdenes de servicio relacionadas con la orden de venta.
function ovRenderServiceOrders(items) {
    const body = document.getElementById('ov-service-orders');
    const empty = document.getElementById('ov-service-empty');
    body.replaceChildren();
    empty.classList.toggle('d-none', items.length > 0);
    items.forEach(item => {
        const row = document.createElement('tr');
        const numberCell = ovCell(row, '');
        const number = document.createElement('a');
        number.className = 'entity-link fw-semibold';
        number.textContent = item.numero_servicio;
        number.href = 'ver_orden_servicio.php?id=' + Number(item.id);
        numberCell.replaceChildren(number);
        ovCell(row, OV.date(item.fecha_generacion));
        ovCell(row, ovServiceTypes[item.tipo] || item.tipo);
        ovCell(row, ovServiceStatuses[item.estatus] || item.estatus);
        ovCell(row, OV.date(item.updated_at, true));
        body.appendChild(row);
    });
}

// Presenta la bitácora de creación y cambios de estado del registro.
function ovRenderFollowups(items) {
    const list = document.getElementById('ov-followups');
    list.replaceChildren();
    items.forEach(item => {
        const element = document.createElement('div');
        element.className = 'ov-followup-item';
        const date = document.createElement('div');
        date.className = 'ov-followup-date';
        date.textContent = `${OV.date(item.created_at, true)} · ${item.tipo === 'estatus' ? 'Cambio de estatus' : item.tipo === 'creacion' ? 'Creación' : 'Actualización'}`;
        const note = document.createElement('div');
        note.className = 'ov-multiline';
        note.textContent = item.nota || (item.estatus ? OV.statuses[item.estatus] : 'Sin detalle');
        element.append(date, note);
        list.appendChild(element);
    });
}

// Construye un resumen breve y legible para compartir la orden por correo o WhatsApp.
function ovShareText(order) {
    const descriptions = [order.descripcion_corta, order.descripcion_larga]
        .map(value => String(value || '').trim())
        .filter((value, index, items) => value && items.indexOf(value) === index)
        .join('\n');
    return [
        `ORDEN DE VENTA ${order.numero_venta || '—'}`,
        `Estatus: ${OV.statuses[order.estatus] || order.estatus || 'Sin estatus'}`,
        '',
        `Cliente: ${order.empresa_nombre || '—'}`,
        `Dirección: ${order.direccion_texto || '—'}`,
        `Contacto: ${order.contacto_nombre || '—'}`,
        `Teléfono: ${order.contacto_telefono || '—'}`,
        `Correo: ${order.contacto_correo || '—'}`,
        '',
        'Descripción del proyecto:',
        descriptions || '—',
        '',
        'Instrucciones para realizar el servicio o trabajo:',
        String(order.instrucciones || '').trim() || '—'
    ].join('\n');
}

// Copia el resumen al portapapeles y conserva un respaldo para navegadores sin Clipboard API.
async function ovCopyOrderInformation() {
    if (!ovCurrentOrder) { return; }
    const text = ovShareText(ovCurrentOrder);
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
    } else {
        const temporary = document.createElement('textarea');
        temporary.value = text;
        temporary.setAttribute('readonly', '');
        temporary.style.position = 'fixed';
        temporary.style.opacity = '0';
        document.body.appendChild(temporary);
        temporary.select();
        const copied = document.execCommand('copy');
        temporary.remove();
        if (!copied) { throw new Error('El navegador no permitió copiar la información.'); }
    }
    OV.message('mensaje_orden', 'Información de la orden de venta copiada al portapapeles.', true);
}

// Llena la pantalla con una orden de venta devuelta por el servidor.
function ovRenderOrder(order) {
    ovCurrentOrder = order;
    document.getElementById('ov-number').textContent = order.numero_venta;
    const status = document.getElementById('ov-status');
    status.textContent = OV.statuses[order.estatus] || order.estatus;
    status.className = `badge ov-status ov-title-status ov-status-${order.estatus}`;
    const company = document.getElementById('ov-company');
    company.textContent = order.empresa_nombre;
    company.href = 'ver_empresa.php?id=' + Number(order.empresa_id);
    const contact = document.getElementById('ov-contact');
    contact.textContent = order.contacto_nombre || 'Sin contacto';
    if (order.contacto_id) { contact.href = 'ver_contacto.php?id=' + Number(order.contacto_id); } else { contact.removeAttribute('href'); }
    document.getElementById('ov-address').textContent = order.direccion_texto || 'Sin dirección';
    document.getElementById('ov-phone').textContent = order.contacto_telefono || 'Sin teléfono';
    document.getElementById('ov-email').textContent = order.contacto_correo || 'Sin correo electrónico';
    document.getElementById('ov-date').textContent = OV.date(order.fecha_generacion);
    document.getElementById('ov-scheduled').textContent = OV.date(order.fecha_programada, true);
    const opportunity = document.getElementById('ov-opportunity');
    opportunity.textContent = order.numero_oportunidad;
    opportunity.href = 'ver_oportunidad.php?id=' + Number(order.oportunidad_id);
    const quote = document.getElementById('ov-quote');
    quote.textContent = order.cot_numero || '#' + order.cotizacion_id;
    quote.href = 'ver_cotizacion.php?id=' + Number(order.cotizacion_id);
    document.getElementById('ov-short-description').textContent = order.descripcion_corta || 'Sin descripción breve';
    document.getElementById('ov-long-description').textContent = order.descripcion_larga || 'Sin descripción larga';
    document.getElementById('ov-instructions').textContent = order.instrucciones || 'Sin instrucciones registradas';
    document.getElementById('ov-notes').textContent = order.notas || 'Sin notas internas';
    document.getElementById('ov-audit').textContent = OV.audit(order);
    ovRenderServiceOrders(order.ordenes_servicio || []);
    ovRenderFollowups(order.seguimiento || []);
    document.getElementById('ov-new-service').href = 'form_orden_servicio.php?orden_venta_id=' + Number(order.id);
    const edit = document.getElementById('ov-edit');
    edit.href = 'form_orden_venta.php?id=' + Number(order.id);
    edit.classList.remove('d-none');
    document.getElementById('ov-copy').classList.remove('d-none');
    document.getElementById('ov-detail').classList.remove('d-none');
}

// Consulta la orden de venta actual y refresca todos sus apartados.
async function ovLoadOrder() {
    if (!/^[1-9]\d*$/.test(ovDetailId || '')) { throw new Error('Identificador de orden no válido.'); }
    const result = await OV.request('get', {id: ovDetailId});
    ovRenderOrder(result.order);
}

document.getElementById('ov-copy').addEventListener('click', function () {
    ovCopyOrderInformation().catch(error => OV.message('mensaje_orden', error.message));
});

ovLoadOrder().catch(error => OV.message('mensaje_orden', error.message));
