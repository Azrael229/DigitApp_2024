'use strict';
// Inicializa el detalle de la oportunidad y la gestión de cotizaciones vinculadas.
(async function () {
    const id = new URLSearchParams(location.search).get('id');
    if (!/^[1-9]\d*$/.test(id || '')) { Op.message('mensaje_oportunidad', 'Identificador de oportunidad no válido.'); return; }
    const quoteSelect = document.getElementById('op-cotizacion');
    const linkButton = document.getElementById('op-vincular-boton');
    const quoteBody = document.getElementById('op-cotizaciones');
    const linkedQuotesTable = new DataTable('#op-tabla-cotizaciones', getDataTableOptions({
        data: [],
        order: [[0, 'desc']],
        orderCellsTop: true,
        columns: [
            {data: 'cot_fecha', render: (value, type) => type === 'display' ? Op.date(value) : value},
            {data: 'cot_numero', render: (value, type, row) => type === 'display' ? `<a class="entity-link fw-semibold" href="ver_cotizacion.php?id=${Number(row.id_coti)}">${Op.escape(value || '#' + row.id_coti)}</a>` : (value || '')},
            {data: 'cot_contacto', defaultContent: '', render: DataTable.render.text()},
            {data: 'cot_total', render: (value, type) => type === 'display' ? Op.money(value) : Number(value || 0)},
            {data: 'id_coti', orderable: false, searchable: false, render: value => `<button type="button" class="btn btn-outline-secondary btn-sm" data-quote="${Number(value)}">Desvincular</button>`},
            {data: 'cot_status', defaultContent: '', render: (value, type) => type === 'display' ? CotStatus.badge(value) : CotStatus.labels[CotStatus.key(value)]}
        ]
    }));
    applyColumnFilters(linkedQuotesTable);
    let busy = false;
    // Recarga las cotizaciones vinculadas y las disponibles para esta empresa.
    async function loadQuotes() {
        const data = await Op.request('quotes', {id});
        linkedQuotesTable.clear().rows.add(data.linked).draw();
        linkedQuotesTable.columns.adjust();
        if (linkedQuotesTable.responsive) { linkedQuotesTable.responsive.recalc(); }
        Op.select(quoteSelect, data.available.map(q => ({id: q.id_coti, text: [q.cot_numero || '#' + q.id_coti, Op.date(q.cot_fecha), q.cot_total].filter(Boolean).join(' · ')})), data.available.length ? 'Seleccionar cotización' : 'No hay cotizaciones disponibles de esta empresa');
        quoteSelect.disabled = !data.available.length; linkButton.disabled = !data.available.length;
        return data;
    }
    // Vincula o desvincula una cotización y refresca el detalle sin borrar archivos.
    async function mutate(action, quoteId) {
        if (busy) { return; }
        busy = true; linkButton.disabled = true;
        quoteBody.querySelectorAll('button').forEach(button => { button.disabled = true; });
        try {
            const result = await Op.request(action, {id, cotizacion_id: quoteId}, true);
            document.getElementById('op-auditoria').textContent = Op.audit(result.opportunity);
            await loadQuotes();
            Op.message('mensaje_cotizaciones', action === 'link' ? 'Cotización vinculada.' : 'Cotización desvinculada.', true);
        } catch (error) { Op.message('mensaje_cotizaciones', error.message); }
        finally {
            busy = false; linkButton.disabled = quoteSelect.disabled;
            quoteBody.querySelectorAll('button').forEach(button => { button.disabled = false; });
        }
    }
    document.getElementById('op-vincular').addEventListener('submit', event => { event.preventDefault(); if (quoteSelect.value) { mutate('link', quoteSelect.value); } });
    quoteBody.addEventListener('click', event => { const button = event.target.closest('button[data-quote]'); if (button) { mutate('unlink', button.dataset.quote); } });
    try {
        const {opportunity: op} = await Op.request('get', {id});
        document.getElementById('op-numero').textContent = op.numero_oportunidad || '#' + id;
        const statusKey = Op.statuses[op.estatus] ? op.estatus : 'preparacion';
        const status = document.getElementById('op-estatus');
        status.textContent = Op.statuses[statusKey] || op.estatus;
        status.className = 'badge op-status op-title-status op-status-' + statusKey;
        const edit = document.getElementById('op-editar'); edit.href = 'form_oportunidad.php?id=' + encodeURIComponent(id); edit.classList.remove('d-none');
        const nuevaCotizacion = document.getElementById('op-nueva-cotizacion');
        nuevaCotizacion.href = 'nuevaCotizacion.php?oportunidad_id=' + encodeURIComponent(id);
        nuevaCotizacion.classList.remove('d-none');
        const company = document.getElementById('op-empresa');
        company.textContent = op.empresa || 'Empresa sin nombre';
        company.href = 'ver_empresa.php?id=' + Number(op.empresa_id);
        document.getElementById('op-direccion').textContent = op.direccion_id ? Op.address(op) : 'Sin dirección asignada';
        const contact = document.getElementById('op-contacto');
        contact.textContent = op.contacto || 'Sin contacto asignado';
        if (op.contacto_id) {
            contact.href = 'ver_contacto.php?id=' + Number(op.contacto_id);
        } else {
            contact.removeAttribute('href');
        }
        document.getElementById('op-contacto-telefono').textContent = op.contacto_telefono || 'Sin teléfono';
        document.getElementById('op-contacto-correo').textContent = op.contacto_correo || 'Sin correo electrónico';
        document.getElementById('op-descripcion-corta').textContent = op.descripcion_corta || 'Sin descripción corta.';
        document.getElementById('op-descripcion').textContent = op.descripcion_larga || 'Sin descripción detallada.';
        document.getElementById('op-fecha').textContent = Op.date(op.fecha);
        document.getElementById('op-importe').textContent = Op.money(op.importe);
        document.getElementById('op-auditoria').textContent = Op.audit(op);
        document.getElementById('op-detalle').classList.remove('d-none');
        try {
            const quotes = await loadQuotes();
            const accepted = quotes.linked.some(quote => CotStatus.key(quote.cot_status) === 'aceptada');
            if (op.estatus === 'ganada' && accepted) {
                const createOrder = document.getElementById('op-crear-orden');
                createOrder.href = 'form_orden_venta.php?oportunidad_id=' + encodeURIComponent(id);
                createOrder.classList.remove('d-none');
            }
        } catch (error) { Op.message('mensaje_cotizaciones', error.message); }
        if (window.jQuery && jQuery.fn.select2) { jQuery(quoteSelect).select2({width: '100%'}); }
    } catch (error) { Op.message('mensaje_oportunidad', error.message); }
})();
