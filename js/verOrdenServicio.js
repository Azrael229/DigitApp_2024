'use strict';

const osDetailId = new URLSearchParams(location.search).get('id');

// Crea una celda segura para la tabla de equipos del detalle.
function osDetailCell(row, value) {
    const cell = document.createElement('td');
    cell.textContent = value || '—';
    row.appendChild(cell);
}

// Presenta los equipos fotografiados al guardar la orden.
function osRenderDetailEquipment(items) {
    const body = document.getElementById('os-detail-equipment-body');
    body.replaceChildren();
    document.getElementById('os-detail-equipment-empty').classList.toggle('d-none', items.length > 0);
    items.forEach(item => {
        const row = document.createElement('tr');
        osDetailCell(row, item.descripcion);
        osDetailCell(row, item.ubicacion);
        osDetailCell(row, item.marca);
        osDetailCell(row, item.modelo);
        osDetailCell(row, item.identificacion);
        osDetailCell(row, item.numero_serie);
        osDetailCell(row, Number(item.capacidad_maxima || 0) ? `${Number(item.capacidad_maxima).toLocaleString('es-MX')} ${item.unidad || ''}` : '—');
        osDetailCell(row, Number(item.division_real || 0) ? Number(item.division_real).toLocaleString('es-MX', {maximumFractionDigits: 9}) : '—');
        osDetailCell(row, Number(item.division_verificacion || 0) ? Number(item.division_verificacion).toLocaleString('es-MX', {maximumFractionDigits: 9}) : '—');
        osDetailCell(row, item.clase_exactitud);
        body.appendChild(row);
    });
}

// Llena la vista completa de una orden de servicio.
function osRenderDetail(order) {
    document.getElementById('os-number').textContent = order.numero_servicio;
    const status = document.getElementById('os-status');
    status.textContent = OS.statuses[order.estatus] || order.estatus;
    status.className = `badge os-status ov-title-status os-status-${order.estatus}`;
    document.getElementById('os-date').textContent = OS.date(order.fecha_generacion);
    document.getElementById('os-type').textContent = OS.types[order.tipo] || order.tipo;
    const sale = document.getElementById('os-sale');
    sale.textContent = order.numero_venta;
    sale.href = 'ver_orden_venta.php?id=' + Number(order.orden_venta_id);
    const company = document.getElementById('os-company');
    company.textContent = order.empresa_nombre;
    company.href = 'ver_empresa.php?id=' + Number(order.empresa_id);
    document.getElementById('os-fiscal').textContent = [order.fiscal_razon_social, order.fiscal_rfc, order.fiscal_regimen, order.fiscal_direccion].filter(Boolean).join('\n');
    const deliveryRole = [order.entrega_puesto_mostrado, order.entrega_departamento_mostrado].filter(Boolean).join(' · ');
    document.getElementById('os-delivery').textContent = [order.entrega_contacto, deliveryRole, order.entrega_telefono, order.entrega_correo, order.entrega_direccion].filter(Boolean).join('\n');
    document.getElementById('os-instructions').textContent = order.instrucciones || 'Sin instrucciones adicionales.';
    document.getElementById('os-notes').textContent = order.notas || 'Sin notas internas.';
    osRenderDetailEquipment(order.equipos || []);
    const edit = document.getElementById('os-edit');
    edit.href = 'form_orden_servicio.php?id=' + Number(order.id);
    edit.classList.remove('d-none');
    const print = document.getElementById('os-print');
    print.href = '../fpdf/ordenServicioPDF.php?id=' + Number(order.id);
    print.classList.remove('d-none');
    document.getElementById('os-detail').classList.remove('d-none');
}

// Consulta la orden indicada en la dirección actual.
async function osLoadDetail() {
    if (!/^[1-9]\d*$/.test(osDetailId || '')) { throw new Error('Identificador de orden no válido.'); }
    const result = await OS.request('get', {id: osDetailId});
    osRenderDetail(result.order);
}

osLoadDetail().catch(error => OS.message('mensaje_servicio', error.message));
