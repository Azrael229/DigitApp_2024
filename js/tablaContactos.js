// Conserva el orden del servidor para mostrar primero el contacto recién agregado.
var tablaContactos = new DataTable('#example', getDataTableOptions({
    order: [],
    responsive: false,
    scrollX: true,
    columnDefs: document.querySelector('#example thead .admin-delete-column')
        ? [{targets: -1, orderable: false, searchable: false}] : []
}));
applyColumnFilters(tablaContactos);

// Recarga las filas de contactos y sus empresas relacionadas sin reconstruir la página.
async function actualizarTablaContactosMaestros() {
    var respuesta = await fetch(location.href, {cache: 'no-store', credentials: 'same-origin'});
    if (!respuesta.ok) { throw new Error('No se pudieron actualizar los contactos.'); }
    var documento = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
    tablaContactos.clear().rows.add(Array.from(documento.querySelectorAll('#example tbody tr'))).draw();
}
window.DigitAppMasterSync?.subscribe(() => actualizarTablaContactosMaestros().catch(error => console.error(error)));
