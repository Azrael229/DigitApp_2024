'use strict';
// Inicializa el detalle de la oportunidad y la gestión de cotizaciones vinculadas.
(async function () {
    const id = new URLSearchParams(location.search).get('id');
    if (!/^[1-9]\d*$/.test(id || '')) { Op.message('mensaje_oportunidad', 'Identificador de oportunidad no válido.'); return; }
    const quoteSelect = document.getElementById('op-cotizacion');
    const linkButton = document.getElementById('op-vincular-boton');
    const quoteBody = document.getElementById('op-cotizaciones');
    let busy = false;
    // Agrega un dato etiquetado al resumen visual de la oportunidad.
    function field(label, value, href) {
        const column = document.createElement('div'); column.className = 'col-md-6';
        const title = document.createElement('div'); title.className = 'contactos-muted mb-1'; title.textContent = label;
        const content = document.createElement(href ? 'a' : 'div'); content.className = href ? 'entity-link' : 'op-multiline';
        content.textContent = value || '—'; if (href) { content.href = href; }
        column.append(title, content); document.getElementById('op-datos').append(column);
    }
    // Recarga las cotizaciones vinculadas y las disponibles para esta empresa.
    async function loadQuotes() {
        const data = await Op.request('quotes', {id});
        quoteBody.replaceChildren();
        data.linked.forEach(quote => {
            const row = document.createElement('tr');
            [Op.date(quote.cot_fecha), quote.cot_numero || '#' + quote.id_coti, quote.cot_total || '—'].forEach(value => { const cell = document.createElement('td'); cell.textContent = value; row.append(cell); });
            const pdf = document.createElement('td');
            const file = String(quote.cot_archivo || '');
            if (quote.pdf_disponible && file && !/[\\/]/.test(file) && /\.pdf$/i.test(file)) {
                const anchor = document.createElement('a'); anchor.href = '../filesPDF/' + encodeURIComponent(file); anchor.download = file; anchor.textContent = 'Descargar PDF'; pdf.append(anchor);
            } else { pdf.textContent = 'Sin PDF'; }
            const action = document.createElement('td'); const button = document.createElement('button');
            button.type = 'button'; button.className = 'btn btn-outline-secondary btn-sm'; button.textContent = 'Desvincular'; button.dataset.quote = quote.id_coti;
            action.append(button); row.append(pdf, action); quoteBody.append(row);
        });
        if (!data.linked.length) { const row = document.createElement('tr'); const cell = document.createElement('td'); cell.colSpan = 5; cell.textContent = 'Esta oportunidad todavía no tiene cotizaciones vinculadas.'; row.append(cell); quoteBody.append(row); }
        Op.select(quoteSelect, data.available.map(q => ({id: q.id_coti, text: [q.cot_numero || '#' + q.id_coti, Op.date(q.cot_fecha), q.cot_total].filter(Boolean).join(' · ')})), data.available.length ? 'Seleccionar cotización' : 'No hay cotizaciones disponibles de esta empresa');
        quoteSelect.disabled = !data.available.length; linkButton.disabled = !data.available.length;
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
        document.getElementById('op-titulo').textContent = op.descripcion_corta;
        const edit = document.getElementById('op-editar'); edit.href = 'form_oportunidad.php?id=' + encodeURIComponent(id); edit.classList.remove('d-none');
        field('Fecha', Op.date(op.fecha)); field('Estatus', Op.statuses[op.estatus]);
        field('Empresa', op.empresa, 'ver_empresa.php?id=' + Number(op.empresa_id));
        field('Contacto', op.contacto, op.contacto_id ? 'ver_contacto.php?id=' + Number(op.contacto_id) : null);
        field('Dirección', op.direccion_id ? Op.address(op) : 'Sin dirección asignada');
        field('Importe propuesto sin IVA (MXN)', Op.money(op.importe));
        if (op.direccion_id) {
            field('Tipo y alias de dirección', [op.tipo_direccion, op.direccion_alias].filter(Boolean).join(' · '));
            if (op.entre_calles) { field('Entre calles', op.entre_calles); }
            if (op.referencia) { field('Referencia', op.referencia); }
            if (/^https?:\/\//i.test(op.enlace_maps || '')) { field('Ubicación', 'Abrir mapa', op.enlace_maps); }
        }
        document.getElementById('op-descripcion').textContent = op.descripcion_larga || 'Sin descripción detallada.';
        document.getElementById('op-auditoria').textContent = Op.audit(op);
        document.getElementById('op-detalle').classList.remove('d-none');
        try { await loadQuotes(); } catch (error) { Op.message('mensaje_cotizaciones', error.message); }
        if (jQuery.fn.select2) { jQuery(quoteSelect).select2({width: '100%'}); }
    } catch (error) { Op.message('mensaje_oportunidad', error.message); }
})();
