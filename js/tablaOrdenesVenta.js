'use strict';

// Inicializa el listado de órdenes, filtros y resumen monetario global.
(async function () {
    const table = new DataTable('#tabla-ordenes-venta', getDataTableOptions({
        data: [], order: [[8, 'desc']], orderCellsTop: true,
        columns: [
            {data: 'fecha_generacion', render: (value, type) => type === 'display' ? OV.date(value) : value},
            {data: 'numero_venta', render: (value, type, row) => type === 'display' ? `<a class="entity-link fw-semibold" href="ver_orden_venta.php?id=${Number(row.id)}">${OV.escape(value)}</a>` : value},
            {data: 'empresa_nombre', render: (value, type, row) => type === 'display' ? `<a class="entity-link" href="ver_empresa.php?id=${Number(row.empresa_id)}">${OV.escape(value)}</a>` : value},
            {data: 'contacto_nombre', defaultContent: '', render: (value, type, row) => type === 'display' ? (row.contacto_id ? `<a class="entity-link" href="ver_contacto.php?id=${Number(row.contacto_id)}">${OV.escape(value || 'Sin contacto')}</a>` : OV.escape(value || 'Sin contacto')) : (value || '')},
            {data: 'contacto_correo', defaultContent: '', render: DataTable.render.text()},
            {data: 'contacto_telefono', defaultContent: '', render: DataTable.render.text()},
            {data: 'importe_sin_iva', render: (value, type) => type === 'display' ? OV.money(value) : Number(value || 0)},
            {data: 'estatus', render: (value, type, row) => type === 'display'
                ? `<select class="form-select form-select-sm ov-status-select" aria-label="Estatus de ${OV.escape(row.numero_venta)}">${Object.entries(OV.statuses).map(([key, label]) => `<option value="${key}"${key === value ? ' selected' : ''}>${OV.escape(label)}</option>`).join('')}</select>`
                : (OV.statuses[value] || value)},
            {data: 'created_at', visible: false, searchable: false, render: (value, type, row) => value + String(row.id).padStart(10, '0')}
        ]
    }));
    applyColumnFilters(table);

    // Actualiza el total de órdenes y el importe de todas las filas filtradas.
    function refreshSummary() {
        const rows = table.rows({search: 'applied'}).data().toArray();
        document.getElementById('ov-registros').textContent = rows.length;
        document.getElementById('ov-subtotal').textContent = OV.money(rows.reduce((sum, row) => sum + Number(row.importe_sin_iva || 0), 0));
    }
    table.on('draw', refreshSummary);
    document.querySelector('#tabla-ordenes-venta tbody').addEventListener('change', async event => {
        const select = event.target.closest('.ov-status-select');
        if (!select) { return; }
        const row = table.row(select.closest('tr'));
        const data = row.data();
        select.disabled = true;
        try {
            const result = await OV.request('status', {id: data.id, version: data.version, estatus: select.value}, true);
            row.data({...data, ...result.order}).draw(false);
            OV.message('mensaje_ordenes', 'Estatus actualizado.', true);
        } catch (error) { OV.message('mensaje_ordenes', error.message); row.data(data).draw(false); }
    });
    try {
        const result = await OV.request('list');
        table.rows.add(result.data).draw();
    } catch (error) { OV.message('mensaje_ordenes', error.message); }
})();
