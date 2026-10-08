let table = new DataTable('#example', getDataTableOptions({
    order: [[0, 'desc']],
    responsive: false,
    scrollX: true
}));

applyColumnFilters(table);

// Actualiza el rol comercial desde el listado sin abandonar la tabla.
document.querySelector('#example tbody').addEventListener('change', async function (event) {
    const select = event.target.closest('.empresa-role-select');
    if (!select) { return; }
    const previous = select.dataset.previous || select.value;
    select.disabled = true;
    try {
        const response = await fetch('../backend/empresas/actualizar_rol_empresa.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: new URLSearchParams({
                id: select.dataset.id,
                version: select.dataset.version,
                rol: select.value,
                csrf: document.querySelector('[data-empresas-csrf]').dataset.empresasCsrf
            })
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) { throw new Error(data.error || 'No fue posible actualizar el rol.'); }
        select.dataset.version = data.version;
        select.dataset.previous = data.rol;
    } catch (error) {
        select.value = previous;
        window.alert(error.message);
    } finally {
        select.disabled = false;
    }
});

document.querySelectorAll('.empresa-role-select').forEach(select => { select.dataset.previous = select.value; });
