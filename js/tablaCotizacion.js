const cotizacionesTable = new DataTable('#example', getDataTableOptions({
    order: [[1, 'desc']],
    orderCellsTop: true,
}));

applyColumnFilters(cotizacionesTable);

const cotizacionesFechaCell = document.querySelector('#example .dt-filter-row').children[1];
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
    const fecha = String(data[1] || '').slice(0, 10);
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
