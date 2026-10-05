'use strict';

const ovPage = document.querySelector('[data-ov-csrf]');
const ovForm = document.getElementById('form_orden_venta');
const ovOpportunity = document.getElementById('ov-opportunity');
const ovQuote = document.getElementById('ov-quote');
const ovOrderId = ovPage.dataset.orderId;
const ovPreferredOpportunity = ovPage.dataset.opportunityId;
const ovPreferredQuote = ovPage.dataset.quoteId;
let ovSources = [];
let ovExistingOrder = null;
let ovSyncingSelectors = false;

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

// Construye la dirección visible usando primero la fotografía de la cotización.
function ovSourceAddress(source) {
    if (!source) { return '—'; }
    if (String(source.cot_direccion || '').trim()) { return source.cot_direccion.trim(); }
    return [
        [source.calle, source.numero_exterior].filter(Boolean).join(' '), source.numero_interior,
        source.colonia, source.ciudad, source.estado, source.codigo_postal, source.pais
    ].filter(value => String(value || '').trim()).join(', ') || 'Sin dirección';
}

// Presenta en una sección independiente los datos relacionados con el origen comercial.
function ovShowSource(source) {
    const values = {
        'ov-company': source?.empresa || '—',
        'ov-contact': source ? (source.cot_contacto || source.contacto || 'Sin contacto') : '—',
        'ov-email': source ? (source.cot_correo || source.correo || 'Sin correo electrónico') : '—',
        'ov-phone': source ? (source.cot_telefono || source.celular || 'Sin teléfono') : '—',
        'ov-address': ovSourceAddress(source),
        'ov-short-description': source?.descripcion_corta || 'Sin descripción breve',
        'ov-long-description': source?.descripcion_larga || 'Sin descripción larga',
        'ov-amount': source ? OV.money(source.importe_sin_iva) : '—'
    };
    Object.entries(values).forEach(([id, value]) => { document.getElementById(id).textContent = value; });
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
    document.getElementById('ov-notas').value = order.notas || '';
    const source = ovSelectedSource();
    ovShowSource(source);
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
        ovUpdateSourceAvailability(preferred);
    } else if (ovPreferredOpportunity) {
        if (!opportunities.has(String(ovPreferredOpportunity))) {
            throw new Error('La oportunidad seleccionada no tiene cotizaciones Aceptadas disponibles.');
        }
        ovOpportunity.value = String(ovPreferredOpportunity);
        ovRefreshSelect(ovOpportunity);
        ovPopulateQuotes('', ovPreferredOpportunity);
        ovShowSource(null);
        ovUpdateSourceAvailability(null);
    } else {
        ovShowSource(null);
        ovUpdateSourceAvailability(null);
    }
    ovForm.classList.remove('d-none');
}

// Reinicia la cotización y los datos relacionados cuando cambia la oportunidad de origen.
function ovHandleOpportunityChange() {
    if (ovSyncingSelectors) { return; }
    ovPopulateQuotes('', ovOpportunity.value);
    ovShowSource(null);
    ovUpdateSourceAvailability(null);
}

// Selecciona la oportunidad relacionada y carga los datos al elegir una cotización.
function ovHandleQuoteChange() {
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
            fecha_generacion: document.getElementById('ov-fecha').value,
            estatus: document.getElementById('ov-estatus').value,
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
