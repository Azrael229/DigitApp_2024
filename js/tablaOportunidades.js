'use strict';
// Inicializa el listado y los filtros de oportunidades comerciales.
(async function () {
    const table = new DataTable('#tabla-oportunidades', getDataTableOptions({
        data: [], order: [[8, 'desc']], orderCellsTop: true,
        responsive: false, scrollX: true, scrollCollapse: true,
        columns: [
            {data: 'fecha', render: (value, type) => type === 'display' ? Op.date(value) : value},
            {data: 'numero_oportunidad', render: (value, type, row) => type === 'display' ? `<a class="entity-link fw-semibold" href="ver_oportunidad.php?id=${Number(row.id)}">${Op.escape(value || '#' + row.id)}</a>` : (value || '')},
            {data: 'empresa', className: 'table-razon-social-column', render: (value, type, row) => type === 'display' ? `<a class="entity-link" href="ver_empresa.php?id=${Number(row.empresa_id)}">${Op.escape(value)}</a>` : value},
            {data: 'contacto', defaultContent: '', render: (value, type, row) => type === 'display' ? (row.contacto_id ? `<a class="entity-link" href="ver_contacto.php?id=${Number(row.contacto_id)}">${Op.escape(value)}</a>` : '—') : (value || '')},
            {data: 'descripcion_corta', render: (value, type) => type === 'display' ? `<span class="op-multiline">${Op.escape(value || '')}</span>` : (value || '')},
            {data: 'importe_vigente', render: (value, type) => type === 'display' ? Op.money(value) : Number(value || 0)},
            {data: 'colaborador', defaultContent: '', render: value => Op.escape(value || '—')},
            {data: 'estatus', render: (value, type) => type === 'display'
                ? `<span class="badge op-status op-status-${Op.statuses[value] ? value : 'preparacion'}">${Op.escape(Op.statuses[value] || value)}</span>`
                : (Op.statuses[value] || value)},
            {data: 'created_at', visible: false, searchable: false, render: (value, type, row) => value + String(row.id).padStart(10, '0')}
        ]
    }));
    applyColumnFilters(table);
    const dateCell = document.querySelector('#tabla-oportunidades .dt-filter-row').children[0];
    dateCell.replaceChildren();
    const from = document.createElement('input');
    const to = document.createElement('input');
    [[from, 'Desde'], [to, 'Hasta']].forEach(([input, label]) => {
        input.type = 'date'; input.className = 'dt-filter-input mb-1'; input.setAttribute('aria-label', label);
        const text = document.createElement('span'); text.className = 'op-filter-label'; text.textContent = label;
        dateCell.append(text, input);
        input.addEventListener('click', event => event.stopPropagation());
        input.addEventListener('change', () => { to.min = from.value; from.max = to.value; table.draw(); });
    });
    jQuery.fn.dataTable.ext.search.push(function (settings, data) {
        if (settings.nTable.id !== 'tabla-oportunidades') { return true; }
        return Op.inDateRange(data[0], from.value, to.value);
    });
    // Actualiza el número de coincidencias y el subtotal de los importes vigentes filtrados.
    function subtotal() {
        const rows = table.rows({search: 'applied'}).data().toArray();
        document.getElementById('op-registros').textContent = rows.length;
        document.getElementById('op-subtotal').textContent = Op.total(rows, 'importe_vigente');
    }
    table.on('draw', subtotal);
    // Actualiza empresa, contacto e importe calculado sin reconstruir la página.
    async function refreshOpportunities() {
        const result = await Op.request('list');
        table.clear().rows.add(result.data).draw();
    }
    window.DigitAppMasterSync?.subscribe(() => refreshOpportunities().catch(error => Op.message('mensaje_oportunidades', error.message)));
    try { await refreshOpportunities(); }
    catch (error) { Op.message('mensaje_oportunidades', error.message); }
})();
