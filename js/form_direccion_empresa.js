var campoDireccionId = document.getElementById('direccion_id');
var tituloDireccion = document.getElementById('titulo_direccion');

// Carga los datos de una direccion existente cuando el formulario se abre en modo edición.
function cargarDireccionParaEditar() {
    var direccionId = campoDireccionId.value;
    var empresaId = new URLSearchParams(window.location.search).get('empresa_id');

    if (!direccionId || !empresaId) {
        return;
    }

    fetch('../backend/empresas/query_detalle_empresa.php', {
        method: 'POST',
        body: empresaId
    })
        .then(function (respuesta) { return respuesta.json(); })
        .then(function (datos) {
            var direccion = (datos.direcciones || []).find(function (item) {
                return String(item.id) === String(direccionId);
            });
            if (!direccion) {
                throw new Error('Dirección no encontrada');
            }
            document.getElementById('tipo_direccion').value = direccion.tipo_direccion || 'entrega';
            document.getElementById('alias').value = direccion.alias || '';
            document.getElementById('calle').value = direccion.calle || '';
            document.getElementById('numero_exterior').value = direccion.numero_exterior || '';
            document.getElementById('numero_interior').value = direccion.numero_interior || '';
            document.getElementById('colonia').value = direccion.colonia || '';
            document.getElementById('localidad').value = direccion.localidad || '';
            document.getElementById('municipio').value = direccion.municipio || '';
            document.getElementById('ciudad').value = direccion.ciudad || '';
            document.getElementById('estado').value = direccion.estado || '';
            document.getElementById('codigo_postal').value = direccion.codigo_postal || '';
            document.getElementById('pais').value = direccion.pais || 'México';
            document.getElementById('entre_calles').value = direccion.entre_calles || '';
            document.getElementById('referencia').value = direccion.referencia || '';
            document.getElementById('enlace_maps').value = direccion.enlace_maps || '';
            document.getElementById('es_principal').checked = Number(direccion.es_principal) === 1;
            tituloDireccion.textContent = 'Editar dirección';
        })
        .catch(function () {
            tituloDireccion.textContent = 'No fue posible cargar la dirección';
        });
}

cargarDireccionParaEditar();
