'use strict';

const ovPage = document.querySelector('[data-ov-csrf]');
const ovForm = document.getElementById('form_orden_venta');
const ovOpportunity = document.getElementById('ov-opportunity');
const ovQuote = document.getElementById('ov-quote');
const ovContact = document.getElementById('ov-contact');
const ovAddress = document.getElementById('ov-address');
const ovOrderId = ovPage.dataset.orderId;
const ovPreferredOpportunity = ovPage.dataset.opportunityId;
const ovPreferredQuote = ovPage.dataset.quoteId;
let ovSources = [];
let ovExistingOrder = null;
let ovSyncingSelectors = false;
let ovContacts = [];
let ovAddresses = [];

// Devuelve la fuente seleccionada a partir de la oportunidad y la cotización actuales.
function ovSelectedSource() {
    return ovSources.find(source => String(source.oportunidad_id) === ovOpportunity.value
        && String(source.cotizacion_id) === ovQuote.value);
}

// Permite únicamente ventas ganadas o la fuente histórica de la orden que se está editando.
function ovSourceAvailable(source) {
    return source.eligible || (ovOrderId && String(source.orden_id) === String(ovOrderId));
}

// Actualiza la representación visible de un selector administrado por Select2.
function ovRefreshSelect(select) {
    if (window.jQuery && jQuery.fn.select2) { jQuery(select).trigger('change.select2'); }
}

// Inicializa la búsqueda escrita por número en los selectores comerciales.
function ovInitSourceSelectors() {
    if (!window.jQuery || !jQuery.fn.select2) { return; }
    jQuery(ovQuote).select2({
        width: '100%', allowClear: true, minimumResultsForSearch: 0,
        placeholder: 'Buscar o seleccionar cotización',
        language: {noResults: () => 'No se encontraron cotizaciones'}
    });
    jQuery(ovOpportunity).select2({
        width: '100%', allowClear: true, minimumResultsForSearch: 0,
        placeholder: 'Buscar o seleccionar oportunidad',
        language: {noResults: () => 'No se encontraron oportunidades'}
    });
    jQuery(ovContact).select2({
        width: '100%', minimumResultsForSearch: 0,
        placeholder: 'Seleccionar contacto',
        language: {noResults: () => 'No se encontraron contactos asociados'}
    });
    jQuery(ovAddress).select2({
        width: '100%', minimumResultsForSearch: 0,
        placeholder: 'Seleccionar dirección',
        language: {noResults: () => 'No se encontraron direcciones registradas'}
    });
}

// Carga todas las oportunidades relacionadas, ordenadas desde la generación más reciente.
function ovPopulateOpportunities(selectedOpportunity = '') {
    const current = String(selectedOpportunity || '');
    ovOpportunity.replaceChildren(new Option('Buscar o seleccionar oportunidad', ''));
    const opportunities = new Map();
    [...ovSources]
        .sort((left, right) => Number(right.oportunidad_id) - Number(left.oportunidad_id))
        .forEach(source => {
            if (!ovSourceAvailable(source)) { return; }
            const id = String(source.oportunidad_id);
            if (!opportunities.has(id)) {
                opportunities.set(id, `${source.numero_oportunidad} · ${source.descripcion_corta || source.empresa}`);
            }
        });
    opportunities.forEach((label, id) => ovOpportunity.add(new Option(label, id)));
    if (current && opportunities.has(current)) { ovOpportunity.value = current; }
    ovRefreshSelect(ovOpportunity);
    return opportunities;
}

// Limpia los selectores del cliente cuando todavía no existe un origen comercial completo.
function ovResetClientOptions() {
    ovContacts = [];
    ovAddresses = [];
    ovContact.replaceChildren(new Option('Selecciona primero una cotización', ''));
    ovAddress.replaceChildren(new Option('Selecciona primero una cotización', ''));
    ovContact.disabled = true;
    ovAddress.disabled = true;
    document.getElementById('ov-email').textContent = '—';
    document.getElementById('ov-phone').textContent = '—';
    ovRefreshSelect(ovContact);
    ovRefreshSelect(ovAddress);
}

// Presenta los datos fijos del origen comercial y deja contacto y dirección a los catálogos vigentes.
function ovShowSource(source) {
    const values = {
        'ov-company': source?.empresa || '—',
        'ov-short-description': source?.descripcion_corta || 'Sin descripción breve',
        'ov-long-description': source?.descripcion_larga || 'Sin descripción larga',
        'ov-amount': source ? OV.money(source.importe_sin_iva) : '—'
    };
    Object.entries(values).forEach(([id, value]) => { document.getElementById(id).textContent = value; });
}

// Refleja correo y teléfono del contacto vigente seleccionado.
function ovShowSelectedContact() {
    const contact = ovContacts.find(item => String(item.id) === ovContact.value);
    document.getElementById('ov-email').textContent = contact?.correo || 'Sin correo electrónico';
    document.getElementById('ov-phone').textContent = contact?.celular || 'Sin teléfono';
}

// Llena contacto y dirección con los registros actuales asociados a la empresa seleccionada.
async function ovLoadClientOptions(source, selectedContact = '', selectedAddress = '') {
    ovResetClientOptions();
    if (!source) { return; }
    const result = await OV.request('client_options', {
        oportunidad_id: source.oportunidad_id,
        cotizacion_id: source.cotizacion_id
    });
    ovContacts = result.contacts || [];
    ovAddresses = result.addresses || [];
    ovContact.replaceChildren(new Option('Seleccionar contacto', ''));
    ovContacts.forEach(contact => {
        const role = [contact.departamento, contact.puesto].filter(Boolean).join(' · ');
        const principal = Number(contact.es_principal) === 1 ? ' · Principal' : '';
        ovContact.add(new Option(`${contact.nombre}${role ? ` · ${role}` : ''}${principal}`, contact.id));
    });
    ovAddress.replaceChildren(new Option('Seleccionar dirección', ''));
    ovAddresses.forEach(address => {
        const label = [address.alias || address.tipo_direccion, address.direccion_texto,
            Number(address.es_principal) === 1 ? 'Principal' : ''].filter(Boolean).join(' · ');
        ovAddress.add(new Option(label, address.id));
    });
    const preferredContact = String(selectedContact || source.contacto_id || '');
    const preferredAddress = String(selectedAddress || source.direccion_id || '');
    const contact = ovContacts.find(item => String(item.id) === preferredContact)
        || ovContacts.find(item => Number(item.es_principal) === 1) || ovContacts[0];
    const address = ovAddresses.find(item => String(item.id) === preferredAddress)
        || ovAddresses.find(item => Number(item.es_principal) === 1) || ovAddresses[0];
    ovContact.value = contact ? String(contact.id) : '';
    ovAddress.value = address ? String(address.id) : '';
    ovContact.disabled = ovContacts.length === 0;
    ovAddress.disabled = ovAddresses.length === 0;
    ovRefreshSelect(ovContact);
    ovRefreshSelect(ovAddress);
    ovShowSelectedContact();
}

// Refleja la fecha elegida y resume si la orden existente ha recibido actualizaciones.
function ovUpdateTracking(order = null) {
    const generationDate = document.getElementById('ov-fecha').value;
    document.getElementById('ov-tracking-generation').textContent = generationDate ? OV.date(generationDate) : '—';
    document.getElementById('ov-tracking-updates').textContent = !order
        ? 'Sin actualizaciones; se registrará al guardar.'
        : Number(order.version) > 1
            ? `Última actualización ${OV.date(order.updated_at, true)} · Versión ${order.version}`
            : 'Sin actualizaciones posteriores.';
}

// Carga cotizaciones recientes y, cuando se elige una oportunidad, limita la lista a su relación.
function ovPopulateQuotes(selectedQuote = '', opportunityId = ovOpportunity.value) {
    ovQuote.replaceChildren(new Option('Buscar o seleccionar cotización', ''));
    const candidates = [...ovSources]
        .filter(source => !opportunityId || String(source.oportunidad_id) === String(opportunityId))
        .filter(ovSourceAvailable)
        .sort((left, right) => Number(right.cotizacion_id) - Number(left.cotizacion_id));
    candidates.forEach(source => {
        const label = [source.cot_numero, source.numero_oportunidad, OV.statusLabel(source.cot_status), OV.money(source.importe_sin_iva)].filter(Boolean).join(' · ');
        ovQuote.add(new Option(label, source.cotizacion_id));
    });
    ovQuote.disabled = Boolean(ovOrderId);
    if (selectedQuote && candidates.some(source => String(source.cotizacion_id) === String(selectedQuote))) {
        ovQuote.value = String(selectedQuote);
    }
    ovRefreshSelect(ovQuote);
}

// Habilita el guardado solo cuando la pareja comercial cumple las reglas de venta.
function ovUpdateSourceAvailability(source) {
    const message = document.getElementById('mensaje_form_orden');
    const save = document.getElementById('ov-save');
    if (ovExistingOrder || source?.eligible) {
        if (ovContacts.length === 0 || ovAddresses.length === 0) {
            save.disabled = true;
            message.textContent = ovContacts.length === 0
                ? 'La empresa necesita al menos un contacto activo asociado antes de guardar la orden.'
                : 'La empresa necesita al menos una dirección registrada antes de guardar la orden.';
            message.className = 'alert alert-warning';
            return;
        }
        save.disabled = false;
        message.classList.add('d-none');
        return;
    }
    save.disabled = true;
    if (!source) {
        message.classList.add('d-none');
        return;
    }
    message.textContent = 'Para guardar la orden, la oportunidad debe estar Ganada y la cotización debe estar Aceptada.';
    message.className = 'alert alert-warning';
}

// Llena todos los controles cuando se edita una orden de venta existente.
async function ovFillOrder(order) {
    ovExistingOrder = order;
    ovOpportunity.value = String(order.oportunidad_id);
    ovRefreshSelect(ovOpportunity);
    ovPopulateQuotes(order.cotizacion_id, order.oportunidad_id);
    ovOpportunity.disabled = true;
    ovQuote.disabled = true;
    ovRefreshSelect(ovOpportunity);
    ovRefreshSelect(ovQuote);
    document.getElementById('ov-version').value = order.version;
    document.getElementById('ov-fecha').value = order.fecha_generacion;
    document.getElementById('ov-estatus').value = order.estatus;
    document.getElementById('ov-instructions').value = order.instrucciones || '';
    document.getElementById('ov-notas').value = order.notas || '';
    const source = ovSelectedSource();
    ovShowSource(source);
    await ovLoadClientOptions(source, order.contacto_id, order.direccion_id);
    ovUpdateSourceAvailability(source);
    ovUpdateTracking(order);
}

// Prepara selectores dependientes y respeta el origen recibido desde una cotización.
async function ovPrepareForm() {
    const data = await OV.request('sources');
    ovSources = data.sources;
    const opportunities = ovPopulateOpportunities();
    ovPopulateQuotes();
    ovInitSourceSelectors();
    document.getElementById('ov-fecha').value = new Date().toLocaleDateString('en-CA');
    ovUpdateTracking();
    if (ovOrderId) {
        const result = await OV.request('get', {id: ovOrderId});
        await ovFillOrder(result.order);
    } else if (ovPreferredQuote) {
        const preferred = ovSources.find(source => source.eligible
            && String(source.cotizacion_id) === String(ovPreferredQuote)
            && (!ovPreferredOpportunity || String(source.oportunidad_id) === String(ovPreferredOpportunity)));
        if (!preferred) {
            throw new Error('La cotización seleccionada debe estar Aceptada, pertenecer a una oportunidad Ganada y no tener otra orden de venta.');
        }
        ovOpportunity.value = String(preferred.oportunidad_id);
        ovRefreshSelect(ovOpportunity);
        ovPopulateQuotes(preferred.cotizacion_id, preferred.oportunidad_id);
        ovShowSource(preferred);
        await ovLoadClientOptions(preferred);
        ovUpdateSourceAvailability(preferred);
    } else if (ovPreferredOpportunity) {
        if (!opportunities.has(String(ovPreferredOpportunity))) {
            throw new Error('La oportunidad seleccionada no tiene cotizaciones Aceptadas disponibles.');
        }
        ovOpportunity.value = String(ovPreferredOpportunity);
        ovRefreshSelect(ovOpportunity);
        ovPopulateQuotes('', ovPreferredOpportunity);
        ovShowSource(null);
        ovResetClientOptions();
        ovUpdateSourceAvailability(null);
    } else {
        ovShowSource(null);
        ovResetClientOptions();
        ovUpdateSourceAvailability(null);
    }
    ovForm.classList.remove('d-none');
}

// Reinicia la cotización y los datos relacionados cuando cambia la oportunidad de origen.
function ovHandleOpportunityChange() {
    if (ovSyncingSelectors) { return; }
    ovPopulateQuotes('', ovOpportunity.value);
    ovShowSource(null);
    ovResetClientOptions();
    ovUpdateSourceAvailability(null);
}

// Selecciona la oportunidad relacionada y carga los datos al elegir una cotización.
async function ovHandleQuoteChange() {
    if (ovSyncingSelectors) { return; }
    const source = ovSources.find(item => String(item.cotizacion_id) === ovQuote.value);
    if (source) {
        ovSyncingSelectors = true;
        ovOpportunity.value = String(source.oportunidad_id);
        ovRefreshSelect(ovOpportunity);
        ovPopulateQuotes(source.cotizacion_id, source.oportunidad_id);
        ovSyncingSelectors = false;
    }
    ovShowSource(source || null);
    await ovLoadClientOptions(source || null);
    ovUpdateSourceAvailability(source || null);
}

// Enlaza los cambios mediante jQuery para Select2 y conserva un respaldo nativo.
function ovBindSourceSelectorEvents() {
    if (window.jQuery) {
        jQuery(ovOpportunity).on('change.ovForm', ovHandleOpportunityChange);
        jQuery(ovQuote).on('change.ovForm', ovHandleQuoteChange);
        return;
    }
    ovOpportunity.addEventListener('change', ovHandleOpportunityChange);
    ovQuote.addEventListener('change', ovHandleQuoteChange);
}

// Mantiene el resumen de seguimiento sincronizado con la fecha configurable.
document.getElementById('ov-fecha').addEventListener('change', function () {
    ovUpdateTracking(ovExistingOrder);
});

// Mantiene visibles los datos actuales del contacto elegido por el usuario.
ovContact.addEventListener('change', ovShowSelectedContact);

// Valida y envía la creación o edición completa de la orden de venta.
ovForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (!ovForm.reportValidity()) { return; }
    const button = document.getElementById('ov-save');
    button.disabled = true;
    try {
        const result = await OV.request('save', {
            id: ovOrderId, version: document.getElementById('ov-version').value,
            oportunidad_id: ovOpportunity.value, cotizacion_id: ovQuote.value,
            contacto_id: ovContact.value, direccion_id: ovAddress.value,
            fecha_generacion: document.getElementById('ov-fecha').value,
            estatus: document.getElementById('ov-estatus').value,
            instrucciones: document.getElementById('ov-instructions').value,
            notas: document.getElementById('ov-notas').value
        }, true);
        location.href = 'ver_orden_venta.php?id=' + encodeURIComponent(result.order.id);
    } catch (error) {
        OV.message('mensaje_form_orden', error.message);
        button.disabled = false;
    }
});

ovBindSourceSelectorEvents();
ovPrepareForm().catch(error => OV.message('mensaje_form_orden', error.message));
