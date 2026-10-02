'use strict';
// Inicializa el listado, los filtros y la edición rápida de importes.
(async function () {
    const table = new DataTable('#tabla-oportunidades', getDataTableOptions({
        data: [], order: [[8, 'desc']], orderCellsTop: true,
        columns: [
            {data: 'fecha', render: (value, type) => type === 'display' ? Op.date(value) : value},
            {data: 'empresa', render: (value, type, row) => type === 'display' ? `<a class="entity-link" href="ver_empresa.php?id=${Number(row.empresa_id)}">${Op.escape(value)}</a>` : value},
            {data: 'contacto', defaultContent: '', render: (value, type, row) => type === 'display' ? (row.contacto_id ? `<a class="entity-link" href="ver_contacto.php?id=${Number(row.contacto_id)}">${Op.escape(value)}</a>` : '—') : (value || '')},
            {data: 'descripcion_corta', render: DataTable.render.text()},
            {data: 'importe', render: (value, type, row) => type === 'display' ? `<div class="op-amount"><input type="number" class="form-control form-control-sm op-importe" aria-label="Importe sin IVA de oportunidad ${Number(row.id)}" min="0" max="9999999999.99" step="0.01" value="${Number(value).toFixed(2)}"><button type="button" class="btn btn-outline-success btn-sm op-save-amount" aria-label="Guardar importe" title="Guardar importe"><i class="bi bi-check-lg" aria-hidden="true"></i></button></div>` : value},
            {data: 'estatus', render: (value, type) => type === 'display' ? `<span class="badge op-status op-status-${Op.statuses[value] ? value : 'preparacion'}">${Op.escape(Op.statuses[value] || value)}</span>` : (Op.statuses[value] || value)},
            {data: 'id', orderable: false, searchable: false, render: value => `<a class="btn btn-outline-secondary btn-sm" href="ver_oportunidad.php?id=${Number(value)}">Ver detalle</a>`},
            {data: 'id', orderable: false, searchable: false, render: value => `<a class="btn btn-secondary btn-sm" href="form_oportunidad.php?id=${Number(value)}">Editar</a>`},
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
    // Actualiza el número de coincidencias y el subtotal de todas las páginas filtradas.
    function subtotal() {
        const rows = table.rows({search: 'applied'}).data().toArray();
        document.getElementById('op-registros').textContent = rows.length;
        document.getElementById('op-subtotal').textContent = Op.total(rows);
    }
    table.on('draw', subtotal);
    document.querySelector('#tabla-oportunidades tbody').addEventListener('click', async event => {
        const button = event.target.closest('.op-save-amount');
        if (!button) { return; }
        const row = table.row(button.closest('tr'));
        const data = row.data();
        const input = button.parentNode.querySelector('input');
        if (!input.reportValidity() || input.value === '') { return; }
        button.disabled = true;
        try {
            const result = await Op.request('amount', {id: data.id, version: data.version, importe: input.value}, true);
            row.data({...data, ...result.opportunity}).draw(false);
            Op.message('mensaje_oportunidades', 'Importe actualizado.', true);
        } catch (error) { Op.message('mensaje_oportunidades', error.message); button.disabled = false; }
    });
    try {
        const result = await Op.request('list');
        table.rows.add(result.data).draw();
    } catch (error) { Op.message('mensaje_oportunidades', error.message); }
})();
