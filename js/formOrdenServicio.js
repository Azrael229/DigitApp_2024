'use strict';

const osPage = document.querySelector('[data-service-order-id]');
const osSaleOrderId = osPage?.dataset.saleOrderId || '';
const osServiceOrderId = osPage?.dataset.serviceOrderId || '';
const osEquipment = new Map();
const osSelectedEquipment = new Set();
let osAddresses = [];
let osContacts = [];

// Muestra mensajes accesibles dentro del formulario de orden de servicio.
function osMessage(text, success = false) {
    const box = document.getElementById('mensaje_form_servicio');
    box.textContent = text;
    box.className = `alert ${success ? 'alert-success' : 'alert-danger'}`;
}

// Convierte la fecha actual a la forma esperada por el campo HTML.
function osToday() {
    const now = new Date();
    const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 10);
}

// Ejecuta la API de órdenes de servicio y normaliza sus errores.
async function osRequest(action, values = {}, write = false) {
    return OS.request(action, values, write);
}

// Crea una celda de la tabla de equipos sin interpretar HTML externo.
function osEquipmentCell(row, value) {
    const cell = document.createElement('td');
    cell.textContent = value || '—';
    row.appendChild(cell);
}

// Resume capacidad y división del equipo con sus unidades registradas.
function osCapacity(equipment) {
    const capacity = Number(equipment.capacidad_maxima || 0);
    const division = Number(equipment.division_real || 0);
    if (!capacity) { return '—'; }
    return `${capacity.toLocaleString('es-MX')} ${equipment.unidad || ''}${division ? ` · d=${division.toLocaleString('es-MX')}` : ''}`;
}

// Presenta decimales de metrología sin ceros finales innecesarios.
function osDecimal(value) {
    const number = Number(value || 0);
    return number ? number.toLocaleString('es-MX', {maximumFractionDigits: 9}) : '—';
}

// Redibuja los equipos añadidos y actualiza las opciones disponibles.
function osRenderEquipment() {
    const body = document.getElementById('os-equipment-body');
    const select = document.getElementById('os-equipment-select');
    body.replaceChildren();
    select.replaceChildren(new Option('Selecciona un equipo', ''));
    osEquipment.forEach((equipment, id) => {
        if (!osSelectedEquipment.has(id)) {
            select.add(new Option([equipment.descripcion, equipment.marca, equipment.modelo, equipment.identificacion, equipment.numero_serie].filter(Boolean).join(' · '), id));
            return;
        }
        const row = document.createElement('tr');
        osEquipmentCell(row, equipment.descripcion);
        osEquipmentCell(row, equipment.ubicacion);
        osEquipmentCell(row, equipment.marca);
        osEquipmentCell(row, equipment.modelo);
        osEquipmentCell(row, equipment.identificacion);
        osEquipmentCell(row, equipment.numero_serie);
        osEquipmentCell(row, osCapacity(equipment));
        osEquipmentCell(row, osDecimal(equipment.division_real));
        osEquipmentCell(row, osDecimal(equipment.division_verificacion));
        osEquipmentCell(row, equipment.clase_exactitud);
        const action = document.createElement('td');
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-danger';
        remove.textContent = 'Quitar';
        remove.addEventListener('click', () => { osSelectedEquipment.delete(id); osRenderEquipment(); });
        action.appendChild(remove);
        row.appendChild(action);
        osEquipmentCell(row, equipment.estatus === 'fuera_servicio' ? 'Fuera de servicio' : equipment.estatus === 'inactivo' ? 'Inactivo' : 'Activo');
        body.appendChild(row);
    });
    document.getElementById('os-equipment-empty').classList.toggle('d-none', osSelectedEquipment.size > 0);
}

// Actualiza el detalle visible de la dirección seleccionada.
function osShowDeliveryAddress() {
    const id = document.getElementById('os-delivery-address-select').value;
    const address = osAddresses.find(item => String(item.id) === id);
    document.getElementById('os-delivery-address-detail').textContent = address?.direccion_texto || '';
}

// Filtra el selector de contactos de acuerdo con la dirección elegida.
function osRenderDeliveryContacts(selectedContact = '') {
    const addressId = document.getElementById('os-delivery-address-select').value;
    const select = document.getElementById('os-delivery-contact-select');
    const related = osContacts.filter(item => String(item.direccion_id) === addressId);
    select.replaceChildren(new Option(addressId ? 'Selecciona un contacto' : 'Selecciona primero una dirección', ''));
    related.forEach(contact => select.add(new Option([contact.nombre, contact.puesto, contact.departamento].filter(Boolean).join(' · '), contact.id)));
    select.disabled = addressId === '';
    select.value = related.some(contact => String(contact.id) === String(selectedContact)) ? String(selectedContact) : '';
    osShowDeliveryAddress();
    osShowDeliveryContact();
}

// Muestra teléfono, correo y función del contacto seleccionado.
function osShowDeliveryContact() {
    const id = document.getElementById('os-delivery-contact-select').value;
    const addressId = document.getElementById('os-delivery-address-select').value;
    const contact = osContacts.find(item => String(item.id) === id && String(item.direccion_id) === addressId);
    document.getElementById('os-delivery-phone').textContent = contact?.celular || '—';
    document.getElementById('os-delivery-email').textContent = contact?.correo || '—';
    document.getElementById('os-delivery-role').textContent = [contact?.puesto, contact?.departamento].filter(Boolean).join(' · ') || '—';
}

// Carga las direcciones de la empresa y selecciona la relación de entrega inicial.
function osLoadDelivery(addresses, contacts, selectedAddress = '', selectedContact = '') {
    osAddresses = addresses;
    osContacts = contacts;
    const select = document.getElementById('os-delivery-address-select');
    select.replaceChildren(new Option('Selecciona una dirección', ''));
    addresses.forEach(address => select.add(new Option([address.alias, address.tipo_direccion, address.direccion_texto].filter(Boolean).join(' · '), address.id)));
    select.value = addresses.some(address => String(address.id) === String(selectedAddress)) ? String(selectedAddress) : '';
    osRenderDeliveryContacts(selectedContact);
}

// Registra el catálogo de equipos y conserva la selección ya guardada.
function osLoadEquipment(available, selected = []) {
    osEquipment.clear();
    osSelectedEquipment.clear();
    available.forEach(item => osEquipment.set(String(item.id), item));
    selected.forEach(item => {
        const key = String(item.empresa_equipo_id);
        if (!osEquipment.has(key)) { osEquipment.set(key, item); }
        osSelectedEquipment.add(key);
    });
    osRenderEquipment();
}

// Llena la información comercial y fiscal que no se edita en esta orden.
function osFillSource(order, fiscal) {
    document.getElementById('os-sale-number').textContent = order.numero_venta;
    document.getElementById('os-company').textContent = order.empresa_nombre;
    document.getElementById('os-opportunity').textContent = order.numero_oportunidad;
    document.getElementById('os-quote').textContent = order.cot_numero || '#' + order.cotizacion_id;
    document.getElementById('os-short-description').textContent = order.descripcion_corta || 'Sin descripción breve';
    document.getElementById('os-long-description').textContent = order.descripcion_larga || 'Sin descripción larga';
    document.getElementById('os-fiscal-name').textContent = fiscal.razon_social || 'Sin razón social';
    document.getElementById('os-fiscal-rfc').textContent = fiscal.rfc || 'Sin RFC';
    document.getElementById('os-fiscal-regime').textContent = fiscal.regimen || 'Sin régimen fiscal';
    document.getElementById('os-fiscal-address').textContent = fiscal.direccion || 'Sin dirección fiscal';
}

// Precarga una orden nueva o una edición existente y habilita el formulario.
async function osLoadForm() {
    let returnUrl = 'ordenes_servicio.php';
    if (/^[1-9]\d*$/.test(osServiceOrderId)) {
        const result = await osRequest('get', {id: osServiceOrderId});
        const order = result.order;
        osFillSource(order, {razon_social: order.fiscal_razon_social, rfc: order.fiscal_rfc, regimen: order.fiscal_regimen, direccion: order.fiscal_direccion});
        document.getElementById('os-version').value = order.version;
        document.getElementById('os-date').value = order.fecha_generacion;
        document.getElementById('os-type').value = order.tipo;
        document.getElementById('os-status').value = order.estatus;
        osLoadDelivery(result.addresses || [], result.contacts || [],
            order.entrega_direccion_id || order.direccion_id,
            order.entrega_contacto_id || order.contacto_id);
        document.getElementById('os-instructions').value = order.instrucciones || '';
        document.getElementById('os-notes').value = order.notas || '';
        osLoadEquipment(result.available_equipment || [], order.equipos || []);
        returnUrl = 'ver_orden_servicio.php?id=' + Number(order.id);
    } else {
        if (!/^[1-9]\d*$/.test(osSaleOrderId)) { throw new Error('La orden de venta de origen no es válida.'); }
        const result = await osRequest('source', {orden_venta_id: osSaleOrderId});
        osFillSource(result.order, result.fiscal);
        document.getElementById('os-date').value = osToday();
        osLoadDelivery(result.addresses || [], result.contacts || [], result.delivery.direccion_id, result.delivery.contacto_id);
        osLoadEquipment(result.equipment || []);
        returnUrl = 'ver_orden_venta.php?id=' + Number(result.order.id);
    }
    document.getElementById('os-back-top').href = returnUrl;
    document.getElementById('os-cancel').href = returnUrl;
    document.getElementById('mensaje_form_servicio').classList.add('d-none');
    document.getElementById('form_orden_servicio').classList.remove('d-none');
}

// Añade al servicio el equipo seleccionado en el catálogo de la empresa.
document.getElementById('os-add-equipment').addEventListener('click', function () {
    const select = document.getElementById('os-equipment-select');
    if (!select.value) { select.focus(); return; }
    osSelectedEquipment.add(select.value);
    osRenderEquipment();
});

// Refresca los contactos cuando cambia la dirección de entrega.
document.getElementById('os-delivery-address-select').addEventListener('change', () => osRenderDeliveryContacts());

// Refresca los datos informativos cuando cambia el contacto de entrega.
document.getElementById('os-delivery-contact-select').addEventListener('change', osShowDeliveryContact);

// Guarda la orden y dirige al usuario a su vista de detalle.
document.getElementById('form_orden_servicio').addEventListener('submit', async function (event) {
    event.preventDefault();
    if (!this.reportValidity()) { return; }
    const button = document.getElementById('os-save');
    button.disabled = true;
    try {
        const result = await osRequest('save', {
            id: osServiceOrderId, version: document.getElementById('os-version').value,
            orden_venta_id: osSaleOrderId, fecha_generacion: document.getElementById('os-date').value,
            tipo: document.getElementById('os-type').value, estatus: document.getElementById('os-status').value,
            entrega_direccion_id: document.getElementById('os-delivery-address-select').value,
            entrega_contacto_id: document.getElementById('os-delivery-contact-select').value,
            instrucciones: document.getElementById('os-instructions').value,
            notas: document.getElementById('os-notes').value,
            equipos: JSON.stringify([...osSelectedEquipment])
        }, true);
        location.href = 'ver_orden_servicio.php?id=' + Number(result.order.id);
    } catch (error) {
        osMessage(error.message);
        button.disabled = false;
    }
});

osLoadForm().catch(error => osMessage(error.message));
