'use strict';

// Inicializa el listado general de órdenes de servicio y sus filtros.
(async function () {
    const table = new DataTable('#tabla-ordenes-servicio', getDataTableOptions({
        data: [], order: [[7, 'desc']], orderCellsTop: true,
        columns: [
            {data: 'fecha_generacion', render: (value, type) => type === 'display' ? OS.date(value) : value},
            {data: 'numero_servicio', render: (value, type, row) => type === 'display' ? `<a class="entity-link fw-semibold" href="ver_orden_servicio.php?id=${Number(row.id)}">${OS.escape(value)}</a>` : value},
            {data: 'numero_venta', render: (value, type, row) => type === 'display' ? `<a class="entity-link" href="ver_orden_venta.php?id=${Number(row.orden_venta_id)}">${OS.escape(value)}</a>` : value},
            {data: 'empresa_nombre', render: (value, type, row) => type === 'display' ? `<a class="entity-link" href="ver_empresa.php?id=${Number(row.empresa_id)}">${OS.escape(value)}</a>` : value},
            {data: 'entrega_contacto', defaultContent: '', render: DataTable.render.text()},
            {data: 'tipo', render: (value, type) => type === 'display' ? OS.escape(OS.types[value] || value) : (OS.types[value] || value)},
            {data: 'estatus', render: (value, type, row) => type === 'display'
                ? `<select class="form-select form-select-sm os-status-select" aria-label="Estatus de ${OS.escape(row.numero_servicio)}">${Object.entries(OS.statuses).map(([key, label]) => `<option value="${key}"${key === value ? ' selected' : ''}>${OS.escape(label)}</option>`).join('')}</select>`
                : (OS.statuses[value] || value)},
            {data: 'updated_at', render: (value, type, row) => type === 'display' ? OS.date(value, true) : value + String(row.id).padStart(10, '0')}
        ]
    }));
    applyColumnFilters(table);
    table.on('draw', () => { document.getElementById('os-records').textContent = table.rows({search: 'applied'}).count(); });
    document.querySelector('#tabla-ordenes-servicio tbody').addEventListener('change', async event => {
        const select = event.target.closest('.os-status-select');
        if (!select) { return; }
        const row = table.row(select.closest('tr'));
        const data = row.data();
        select.disabled = true;
        try {
            const result = await OS.request('status', {id: data.id, version: data.version, estatus: select.value}, true);
            row.data({...data, ...result.order}).draw(false);
            OS.message('mensaje_servicios', 'Estatus actualizado.', true);
        } catch (error) { OS.message('mensaje_servicios', error.message); row.data(data).draw(false); }
    });
    try {
        const result = await OS.request('list');
        table.rows.add(result.data).draw();
    } catch (error) { OS.message('mensaje_servicios', error.message); }
})();
