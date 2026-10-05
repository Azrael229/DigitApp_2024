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
