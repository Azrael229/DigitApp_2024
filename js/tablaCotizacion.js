const cotizacionesTable = new DataTable('#example', getDataTableOptions({
    order: [[0, 'desc']],
    orderCellsTop: true,
    responsive: false,
    scrollX: true,
    columnDefs: [{
        targets: document.querySelector('#example thead .admin-delete-column') ? [-2, -1] : [-1],
        orderable: false,
        searchable: false
    }],
}));

applyColumnFilters(cotizacionesTable);

const cotizacionesFechaCell = document.querySelector('#example .dt-filter-row').children[0];
cotizacionesFechaCell.replaceChildren();
const cotizacionesDesde = document.createElement('input');
const cotizacionesHasta = document.createElement('input');

// Construye el rango de fechas con el mismo patrón visual de Oportunidades.
[[cotizacionesDesde, 'Desde'], [cotizacionesHasta, 'Hasta']].forEach(([input, label]) => {
    input.type = 'date';
    input.className = 'dt-filter-input mb-1';
    input.setAttribute('aria-label', label);
    const text = document.createElement('span');
    text.className = 'op-filter-label';
    text.textContent = label;
    cotizacionesFechaCell.append(text, input);
    input.addEventListener('click', event => event.stopPropagation());
    input.addEventListener('change', () => {
        cotizacionesHasta.min = cotizacionesDesde.value;
        cotizacionesDesde.max = cotizacionesHasta.value;
        cotizacionesTable.draw();
    });
});

// Limita las cotizaciones a las fechas inclusivas indicadas por el usuario.
jQuery.fn.dataTable.ext.search.push(function (settings, data) {
    if (settings.nTable.id !== 'example') {
        return true;
    }
    const fecha = String(data[0] || '').slice(0, 10);
    return (!cotizacionesDesde.value || fecha >= cotizacionesDesde.value)
        && (!cotizacionesHasta.value || fecha <= cotizacionesHasta.value);
});

// Recarga las filas renderizadas por PHP para reflejar nombres y correos maestros sin recargar la página.
async function actualizarDatosMaestrosCotizaciones() {
    const response = await fetch(location.href, {cache: 'no-store', credentials: 'same-origin'});
    if (!response.ok) { throw new Error('No se pudieron actualizar los datos de cotizaciones.'); }
    const html = await response.text();
    const documentActualizado = new DOMParser().parseFromString(html, 'text/html');
    const filas = Array.from(documentActualizado.querySelectorAll('#example tbody tr'));
    cotizacionesTable.clear().rows.add(filas).draw();
}
window.DigitAppMasterSync?.subscribe(() => actualizarDatosMaestrosCotizaciones().catch(error => console.error(error)));

// Guarda de inmediato el estatus seleccionado y conserva las reglas comerciales del servidor.
document.querySelector('#example tbody').addEventListener('change', async function (event) {
    const select = event.target.closest('.cot-status-select');
    if (!select) { return; }
    const previous = select.dataset.previous || select.value;
    select.disabled = true;
    try {
        const response = await fetch('../backend/cotizaciones/update_coti_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: new URLSearchParams({
                id: select.dataset.id,
                estatus: select.value,
                csrf: document.querySelector('[data-cot-status-csrf]').dataset.cotStatusCsrf
            })
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) { throw new Error(data.error || 'No fue posible actualizar el estatus.'); }
        select.dataset.previous = data.estatus;
        window.DigitAppMasterSync?.publish('cotizacion', select.dataset.id);
    } catch (error) {
        select.value = previous;
        window.alert(error.message);
    } finally {
        select.disabled = false;
    }
});

document.querySelectorAll('.cot-status-select').forEach(select => { select.dataset.previous = select.value; });
