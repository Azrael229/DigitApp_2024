'use strict';
// Inicializa el formulario, sus catálogos dependientes y el guardado de la oportunidad.
(async function () {
    const page = document.querySelector('.oportunidades-page');
    const form = document.getElementById('form-oportunidad');
    if (form.dataset.invalid) { return; }
    const fields = document.getElementById('op-fields');
    const company = document.getElementById('op-empresa');
    const contact = document.getElementById('op-contacto');
    const address = document.getElementById('op-direccion');
    const save = document.getElementById('op-guardar');
    let requestNumber = 0;
    let loading = false;
    let busy = false;
    let dirty = false;
    let initialized = false;
    // Recarga contactos y direcciones de la empresa, descartando respuestas obsoletas.
    async function loadOptions(contactId = '', addressId = '') {
        const current = ++requestNumber;
        loading = true; save.disabled = true; contact.disabled = true; address.disabled = true;
        Op.select(contact, [], 'Sin contacto asignado');
        Op.select(address, [], 'Sin dirección asignada');
        try {
            const data = company.value ? await Op.request('options', {empresa_id: company.value}) : {contacts: [], addresses: []};
            if (current !== requestNumber) { return; }
            Op.select(contact, data.contacts.map(c => ({id: c.id, text: c.nombre})), 'Sin contacto asignado', contactId);
            Op.select(address, data.addresses.map(d => ({id: d.id, text: [d.tipo_direccion, d.alias, Op.address(d)].filter(Boolean).join(' · ')})), 'Sin dirección asignada', addressId);
            loading = false;
            save.disabled = false; contact.disabled = !company.value; address.disabled = !company.value;
            Op.message('mensaje_oportunidad', '');
        } catch (error) {
            if (current === requestNumber) { Op.message('mensaje_oportunidad', error.message + ' Vuelve a seleccionar la empresa para reintentar.'); }
        }
    }
    form.addEventListener('input', () => { if (initialized) { dirty = true; } });
    form.addEventListener('change', () => { if (initialized) { dirty = true; } });
    window.addEventListener('beforeunload', event => { if (dirty && !busy) { event.preventDefault(); event.returnValue = ''; } });
    try {
        const [companies, result] = await Promise.all([
            Op.request('companies'), page.dataset.id ? Op.request('get', {id: page.dataset.id}) : Promise.resolve(null)
        ]);
        Op.select(company, companies.companies.map(c => ({id: c.id, text: c.empresa})), 'Seleccionar empresa', result ? result.opportunity.empresa_id : '');
        if (result) {
            const op = result.opportunity;
            ['fecha', 'estatus', 'descripcion_corta', 'descripcion_larga', 'importe', 'version'].forEach(name => { form.elements[name].value = op[name] == null ? '' : op[name]; });
            document.getElementById('op-auditoria').textContent = Op.audit(op);
            await loadOptions(op.contacto_id, op.direccion_id);
        }
        fields.disabled = false;
        contact.disabled = !company.value; address.disabled = !company.value;
        if (jQuery.fn.select2) {
            jQuery(company).select2({width: '100%'}).on('change', () => { dirty = initialized; loadOptions(); });
            jQuery(contact).select2({width: '100%'}).on('change', () => { dirty = initialized; });
            jQuery(address).select2({width: '100%'}).on('change', () => { dirty = initialized; });
        } else {
            company.addEventListener('change', () => loadOptions());
        }
        initialized = true;
    } catch (error) { Op.message('mensaje_oportunidad', error.message); }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || loading || !initialized || !form.reportValidity()) { return; }
        busy = true; save.disabled = true;
        const params = Object.fromEntries(new FormData(form));
        // Los selects deshabilitados por falta de empresa se representan como vacíos.
        params.contacto_id = contact.value; params.direccion_id = address.value;
        fields.disabled = true;
        try {
            const result = await Op.request('save', params, true);
            dirty = false;
            window.location.href = 'ver_oportunidad.php?id=' + encodeURIComponent(result.opportunity.id);
        } catch (error) { busy = false; fields.disabled = false; save.disabled = false; Op.message('mensaje_oportunidad', error.message); }
    });
})();
