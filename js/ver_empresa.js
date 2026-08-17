var mensajeEmpresa = document.getElementById('mensaje_empresa');
var contenidoEmpresa = document.getElementById('contenido_empresa');
var tituloEmpresa = document.getElementById('titulo_empresa');
var datosGenerales = document.getElementById('datos_generales');
var tablaDirecciones = document.getElementById('tabla_direcciones');
var tablaContactos = document.getElementById('tabla_contactos');
var botonAgregarDireccion = document.getElementById('btn_agregar_direccion');
var botonEditarEmpresa = document.getElementById('btn_editar_empresa');

// Obtiene el identificador de empresa enviado desde el directorio.
function obtenerEmpresaId() {
    return new URLSearchParams(window.location.search).get('id');
}

// Agrega una celda de texto sin insertar contenido HTML del usuario.
function agregarCelda(fila, valor) {
    var celda = document.createElement('td');
    celda.textContent = valor || '-';
    fila.appendChild(celda);
}

// Une los componentes disponibles en una direccion legible.
function formatearDireccionEmpresa(direccion) {
    var primeraLinea = [
        direccion.calle,
        direccion.numero_exterior ? 'No. ' + direccion.numero_exterior : '',
        direccion.numero_interior ? 'Int. ' + direccion.numero_interior : ''
    ].filter(Boolean).join(' ');
    var ubicacion = [
        direccion.colonia ? 'Col. ' + direccion.colonia : '',
        direccion.localidad,
        direccion.municipio && direccion.municipio !== direccion.ciudad ? direccion.municipio : '',
        direccion.ciudad,
        direccion.estado,
        direccion.codigo_postal ? 'C.P. ' + direccion.codigo_postal : '',
        direccion.pais
    ].filter(Boolean).join(', ');

    return [primeraLinea, ubicacion].filter(Boolean).join(', ') || '-';
}

// Convierte las fechas de la base de datos a una lectura local uniforme.
function formatearFechaEmpresa(fecha) {
    if (!fecha) {
        return '-';
    }

    var fechaLocal = new Date(fecha.replace(' ', 'T'));
    return Number.isNaN(fechaLocal.getTime()) ? fecha : fechaLocal.toLocaleString('es-MX');
}

// Agrega los controles para editar o eliminar una direccion de forma individual.
function agregarAccionesDireccion(fila, direccion) {
    var empresaId = obtenerEmpresaId();
    var celdaEditar = document.createElement('td');
    var enlaceEditar = document.createElement('a');
    var celdaEliminar = document.createElement('td');
    var formularioEliminar = document.createElement('form');
    var campoEmpresa = document.createElement('input');
    var campoDireccion = document.createElement('input');
    var botonEliminar = document.createElement('button');

    enlaceEditar.className = 'btn btn-secondary btn-sm';
    enlaceEditar.href = 'form_direccion_empresa.php?empresa_id=' + empresaId + '&direccion_id=' + direccion.id;
    enlaceEditar.textContent = 'Editar';
    celdaEditar.appendChild(enlaceEditar);

    formularioEliminar.method = 'POST';
    formularioEliminar.action = '../backend/empresas/eliminar_direccion_empresa.php';
    formularioEliminar.addEventListener('submit', function (evento) {
        if (!window.confirm('¿Deseas eliminar esta dirección? Esta acción no se puede deshacer.')) {
            evento.preventDefault();
        }
    });
    campoEmpresa.type = 'hidden';
    campoEmpresa.name = 'empresa_id';
    campoEmpresa.value = empresaId;
    campoDireccion.type = 'hidden';
    campoDireccion.name = 'direccion_id';
    campoDireccion.value = direccion.id;
    botonEliminar.type = 'submit';
    botonEliminar.className = 'btn btn-danger btn-sm';
    botonEliminar.textContent = 'Eliminar';
    formularioEliminar.appendChild(campoEmpresa);
    formularioEliminar.appendChild(campoDireccion);
    formularioEliminar.appendChild(botonEliminar);
    celdaEliminar.appendChild(formularioEliminar);

    fila.appendChild(celdaEditar);
    fila.appendChild(celdaEliminar);
}

// Muestra los datos generales de la empresa en la tarjeta superior.
function mostrarDatosGenerales(empresa) {
    var campos = [
        ['Razón social', empresa.razon_social],
        ['RFC', empresa.rfc],
        ['Rol', empresa.rol],
        ['Actividad económica', empresa.actividad_economica],
        ['Teléfono', empresa.telefono_principal],
        ['Correo', empresa.email_principal],
        ['Estatus', empresa.estatus],
        ['Fecha de creación', formatearFechaEmpresa(empresa.created_at)],
        ['Última modificación', formatearFechaEmpresa(empresa.updated_at)]
    ];

    tituloEmpresa.textContent = empresa.empresa || 'Empresa';
    datosGenerales.innerHTML = '';
    campos.forEach(function (campo) {
        var columna = document.createElement('div');
        var etiqueta = document.createElement('strong');
        columna.className = 'col-12 col-md-6 mb-2';
        etiqueta.textContent = campo[0] + ': ';
        columna.appendChild(etiqueta);
        columna.appendChild(document.createTextNode(campo[1] || '-'));
        datosGenerales.appendChild(columna);
    });
}

// Llena la tabla con las direcciones asociadas a la empresa.
function mostrarDirecciones(direcciones) {
    tablaDirecciones.innerHTML = '';
    if (!direcciones.length) {
        var filaVacia = document.createElement('tr');
        agregarCelda(filaVacia, 'Sin direcciones registradas');
        filaVacia.cells[0].colSpan = 6;
        tablaDirecciones.appendChild(filaVacia);
        return;
    }

    direcciones.forEach(function (direccion) {
        var fila = document.createElement('tr');
        agregarCelda(fila, direccion.tipo_direccion);
        agregarCelda(fila, direccion.alias);
        agregarCelda(fila, formatearDireccionEmpresa(direccion));
        agregarCelda(fila, Number(direccion.es_principal) === 1 ? 'Sí' : 'No');
        agregarAccionesDireccion(fila, direccion);
        tablaDirecciones.appendChild(fila);
    });
}

// Llena la tabla con los contactos vinculados a la empresa.
function mostrarContactos(contactos) {
    tablaContactos.innerHTML = '';
    if (!contactos.length) {
        var filaVacia = document.createElement('tr');
        agregarCelda(filaVacia, 'Sin contactos registrados');
        filaVacia.cells[0].colSpan = 6;
        tablaContactos.appendChild(filaVacia);
        return;
    }

    contactos.forEach(function (contacto) {
        var fila = document.createElement('tr');
        agregarCelda(fila, contacto.nombre);
        agregarCelda(fila, contacto.celular);
        agregarCelda(fila, contacto.correo);
        agregarCelda(fila, contacto.depto);
        agregarCelda(fila, '-');
        agregarCelda(fila, '-');
        tablaContactos.appendChild(fila);
    });
}

// Consulta y muestra la empresa solicitada junto con sus registros relacionados.
function cargarDetalleEmpresa() {
    var empresaId = obtenerEmpresaId();
    if (!empresaId) {
        mensajeEmpresa.textContent = 'No se indicó una empresa para consultar.';
        mensajeEmpresa.className = 'alert alert-warning';
        return;
    }

    fetch('../backend/empresas/query_detalle_empresa.php', {
        method: 'POST',
        body: empresaId
    })
        .then(function (respuesta) { return respuesta.json(); })
        .then(function (datos) {
            if (datos.error) {
                throw new Error(datos.error);
            }
            mostrarDatosGenerales(datos.empresa);
            mostrarDirecciones(datos.direcciones || []);
            mostrarContactos(datos.contactos || []);
            botonAgregarDireccion.href = 'form_direccion_empresa.php?empresa_id=' + datos.empresa.id_e;
            botonAgregarDireccion.classList.remove('disabled');
            botonAgregarDireccion.removeAttribute('aria-disabled');
            botonEditarEmpresa.href = 'form_empresa.php?id=' + datos.empresa.id_e;
            botonEditarEmpresa.classList.remove('disabled');
            botonEditarEmpresa.removeAttribute('aria-disabled');
            mensajeEmpresa.classList.add('d-none');
            contenidoEmpresa.classList.remove('d-none');
        })
        .catch(function () {
            mensajeEmpresa.textContent = 'No fue posible cargar la información de la empresa.';
            mensajeEmpresa.className = 'alert alert-danger';
        });
}

cargarDetalleEmpresa();
