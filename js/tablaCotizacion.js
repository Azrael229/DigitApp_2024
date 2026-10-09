const cotizacionesTable = new DataTable('#example', getDataTableOptions({
    order: [[0, 'desc']],
    orderCellsTop: true,
    responsive: false,
    scrollX: true,
    columnDefs: document.querySelector('#example thead .admin-delete-column')
        ? [{targets: -1, orderable: false, searchable: false}] : [],
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

// Actualiza el conteo y la suma de importes de todas las filas filtradas.
function actualizarResumenCotizaciones() {
    const filas = cotizacionesTable.rows({search: 'applied'}).nodes().toArray();
    const total = filas.reduce((acumulado, fila) => {
        const celda = fila.querySelector('[data-cot-total]');
        return acumulado + Number(celda ? celda.dataset.cotTotal : 0);
    }, 0);
    document.getElementById('cot-registros').textContent = filas.length;
    document.getElementById('cot-total-filtrado').textContent = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(total);
}

cotizacionesTable.on('draw', actualizarResumenCotizaciones);
actualizarResumenCotizaciones();

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
    } catch (error) {
        select.value = previous;
        window.alert(error.message);
    } finally {
        select.disabled = false;
    }
});

document.querySelectorAll('.cot-status-select').forEach(select => { select.dataset.previous = select.value; });
