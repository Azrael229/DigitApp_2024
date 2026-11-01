// Conserva el orden del servidor para mostrar primero el contacto recién agregado.
var tablaContactos = new DataTable('#example', getDataTableOptions({
    order: [],
    responsive: false,
    scrollX: true,
    columnDefs: document.querySelector('#example thead .admin-delete-column')
        ? [{targets: -1, orderable: false, searchable: false}] : []
}));
var filtroEmpresaContactos = document.getElementById('filtro_empresa_contactos');

// Filtra contactos por la existencia de al menos una relación empresarial activa.
DataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'example' || filtroEmpresaContactos.value === 'todos') return true;
    var fila = settings.aoData[dataIndex] && settings.aoData[dataIndex].nTr;
    var tieneEmpresa = Number(fila && fila.dataset.companyCount || 0) > 0;
    return filtroEmpresaContactos.value === 'con_empresa' ? tieneEmpresa : !tieneEmpresa;
});

filtroEmpresaContactos.addEventListener('change', function () { tablaContactos.draw(); });

applyColumnFilters(tablaContactos);

// Recarga las filas de contactos y sus empresas relacionadas sin reconstruir la página.
async function actualizarTablaContactosMaestros() {
    var respuesta = await fetch(location.href, {cache: 'no-store', credentials: 'same-origin'});
    if (!respuesta.ok) { throw new Error('No se pudieron actualizar los contactos.'); }
    var documento = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
    tablaContactos.clear().rows.add(Array.from(documento.querySelectorAll('#example tbody tr'))).draw();
}
window.DigitAppMasterSync?.subscribe(() => actualizarTablaContactosMaestros().catch(error => console.error(error)));
