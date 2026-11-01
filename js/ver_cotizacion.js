'use strict';

// Activa paginación, búsqueda y filtros por columna en las partidas de la cotización.
const partidasCotizacionTable = new DataTable('#tabla-partidas-cotizacion', getDataTableOptions({
    order: [[0, 'asc']],
    orderCellsTop: true,
}));

applyColumnFilters(partidasCotizacionTable);

const cotDetailPage = document.querySelector('[data-cotizacion-id]');
const cotStatusSelect = document.getElementById('cot-detail-status');
const cotStatusButton = document.getElementById('cot-save-status');
const cotCreateSale = document.getElementById('cot-create-sale');

// Presenta mensajes del cambio de estatus sin recargar la vista de cotización.
function cotStatusMessage(text, success = false) {
    const box = document.getElementById('cot-status-message');
    box.textContent = text;
    box.className = `alert ${success ? 'alert-success' : 'alert-danger'} mb-3`;
}

// Sincroniza la insignia, la ayuda y la disponibilidad de la nueva orden de venta.
function cotApplyStatus(result) {
    const badge = document.querySelector('.cot-title-status');
    badge.textContent = result.etiqueta;
    badge.className = `badge cot-status cot-title-status cot-status-${result.estatus}`;
    if (cotCreateSale) {
        cotCreateSale.classList.toggle('disabled', !result.puede_crear_orden);
        cotCreateSale.setAttribute('aria-disabled', result.puede_crear_orden ? 'false' : 'true');
        if (result.puede_crear_orden && result.oportunidad_id) {
            cotCreateSale.href = `form_orden_venta.php?oportunidad_id=${encodeURIComponent(result.oportunidad_id)}&cotizacion_id=${encodeURIComponent(cotDetailPage.dataset.cotizacionId)}`;
        }
    }
    document.getElementById('cot-sale-help').textContent = result.puede_crear_orden
        ? 'La cotización está lista para generar una orden de venta con sus datos precargados.'
        : 'Cambia el estatus a Aceptada para generar la orden de venta. La oportunidad relacionada quedará marcada como Ganada.';
}

if (cotCreateSale) {
    cotCreateSale.addEventListener('click', event => {
        if (cotCreateSale.classList.contains('disabled')) { event.preventDefault(); }
    });
}

if (cotStatusButton) {
    cotStatusButton.addEventListener('click', async function () {
        this.disabled = true;
        try {
            const response = await fetch('../backend/cotizaciones/update_coti_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                body: new URLSearchParams({
                    id: cotDetailPage.dataset.cotizacionId,
                    estatus: cotStatusSelect.value,
                    csrf: cotDetailPage.dataset.cotizacionStatusCsrf
                })
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok || result.error) { throw new Error(result.error || 'No fue posible actualizar el estatus.'); }
            cotApplyStatus(result);
            window.DigitAppMasterSync?.publish('cotizacion', cotDetailPage.dataset.cotizacionId);
            cotStatusMessage(result.oportunidad_ganada
                ? 'Estatus actualizado. La oportunidad relacionada quedó marcada como Ganada.'
                : 'Estatus actualizado.', true);
        } catch (error) {
            cotStatusMessage(error.message);
        } finally {
            this.disabled = false;
        }
    });
}

// Refresca los datos visibles de cliente desde el registro maestro sin recargar la cotización.
async function cotRefreshMasterData() {
    const response = await fetch(`../backend/cotizaciones/query_cotizacion.php?id=${encodeURIComponent(cotDetailPage.dataset.cotizacionId)}`, {cache: 'no-store'});
    const result = await response.json();
    if (!response.ok || result.error) { throw new Error(result.error || 'No se pudieron actualizar los datos del cliente.'); }
    const quote = result.cotizacion;
    const statusKey = window.CotStatus?.key(quote.cot_status) || quote.cot_status;
    const statusBadge = document.querySelector('.cot-title-status');
    if (statusBadge) {
        statusBadge.textContent = window.CotStatus?.labels[statusKey] || statusKey;
        statusBadge.className = `badge cot-status cot-title-status cot-status-${statusKey}`;
    }
    if (cotStatusSelect && !cotStatusSelect.disabled) { cotStatusSelect.value = statusKey; }
    const company = document.querySelector('.cotizacion-company-title');
    if (company) {
        company.textContent = quote.cot_empresa || 'Empresa sin nombre';
        if (quote.empresa_id) { company.href = 'ver_empresa.php?id=' + Number(quote.empresa_id); }
    }
    document.querySelectorAll('.empresa-data-field').forEach(field => {
        const label = field.querySelector('.empresa-data-label')?.textContent.trim();
        const value = label === 'Dirección' ? quote.cot_direccion
            : label === 'Contacto' ? quote.cot_contacto
                : label === 'Teléfono' ? quote.cot_telefono
                    : label === 'Correo electrónico' ? quote.cot_correo
                        : label === 'Departamento' ? quote.cot_departamento : null;
        if (value === null) { return; }
        const display = field.querySelector('.empresa-data-value');
        if (display) { display.textContent = value || (label === 'Dirección' ? 'Sin dirección' : 'Sin dato'); }
        const contact = field.querySelector('a.empresa-data-value');
        if (label === 'Contacto' && contact) {
            if (quote.contacto_id) { contact.href = 'ver_contacto.php?id=' + Number(quote.contacto_id); }
            else { contact.removeAttribute('href'); }
        }
    });
    const currency = value => '$ ' + Number(value || 0).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const gross = quote.partidas.reduce((sum, item) => sum + Number(item.importe_bruto || item.importe || 0), 0);
    const discount = quote.partidas.reduce((sum, item) => sum + Number(item.descuento_importe || 0), 0);
    const totals = document.querySelectorAll('.cotizacion-detail-totals strong');
    [gross, discount, quote.cot_subtotal, quote.cot_iva, quote.cot_total].forEach((value, index) => {
        if (totals[index]) { totals[index].textContent = (index === 1 ? '-' : '') + currency(value) + (index === 4 ? ' ' + (quote.cot_moneda || 'MXN') : ''); }
    });
    const rows = quote.partidas.map(item => {
        const row = document.createElement('tr');
        const quantity = value => value == null ? '—' : Number(value).toLocaleString('es-MX', {maximumFractionDigits: 3});
        const cell = (value, className = '') => {
            const element = document.createElement('td');
            element.textContent = value;
            if (className) { element.className = className; }
            row.appendChild(element);
        };
        cell(String(item.posicion));
        cell(item.codigo_catalogo || '—', 'text-nowrap');
        cell([quantity(item.cantidad), item.cantidad_secundaria == null ? '' : quantity(item.cantidad_secundaria)].filter(Boolean).join('\n'));
        cell([item.unidad || '—', item.unidad_secundaria || ''].filter(Boolean).join('\n'));
        cell(item.descripcion || '—', 'cotizacion-detail-description');
        cell(item.valor_unitario == null ? '—' : currency(item.valor_unitario), 'text-end text-nowrap');
        const reduction = Number(item.descuento_importe || 0);
        const discountLabel = !reduction ? '—' : item.descuento_tipo === 'porcentaje'
            ? `${Number(item.descuento_valor || 0).toLocaleString('es-MX')}% · ${currency(reduction)}` : currency(reduction);
        cell(discountLabel, 'text-end text-nowrap');
        cell(item.importe == null ? '—' : currency(item.importe), 'text-end text-nowrap fw-semibold');
        return row;
    });
    partidasCotizacionTable.clear().rows.add(rows).draw();
}
window.DigitAppMasterSync?.subscribe(() => cotRefreshMasterData().catch(error => cotStatusMessage(error.message)));
