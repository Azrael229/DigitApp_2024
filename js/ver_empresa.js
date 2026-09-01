var mensajeEmpresa = document.getElementById('mensaje_empresa');
var contenidoEmpresa = document.getElementById('contenido_empresa');
var tituloEmpresa = document.getElementById('titulo_empresa');
var datosGenerales = document.getElementById('datos_generales');
var tablaDirecciones = document.getElementById('tabla_direcciones');
var tablaContactos = document.getElementById('tabla_contactos');
var botonAgregarDireccion = document.getElementById('btn_agregar_direccion');
var botonAgregarContacto = document.getElementById('btn_agregar_contacto');
var botonEditarEmpresa = document.getElementById('btn_editar_empresa');

// Obtiene el identificador de empresa enviado desde el directorio.
function obtenerEmpresaId() {
    var id = new URLSearchParams(window.location.search).get('id');

    return /^[1-9]\d*$/.test(id || '') ? id : null;
}

// Agrega una celda de texto sin insertar contenido HTML del usuario.
function agregarCelda(fila, valor) {
    var celda = document.createElement('td');
    celda.className = 'empresa-detail-cell';
    celda.textContent = valor || '-';
    fila.appendChild(celda);
}

// Agrega el nombre del contacto como enlace a su ficha y marca la empresa principal.
function agregarContactoEnlazado(fila, contacto) {
    var empresaId = obtenerEmpresaId();
    var celda = document.createElement('td');
    var enlace = document.createElement('a');

    celda.className = 'empresa-detail-cell';
    enlace.className = 'empresa-contact-link';
    enlace.href = 'ver_contacto.php?id=' + encodeURIComponent(contacto.id)
        + '&from=empresa&empresa_id=' + encodeURIComponent(empresaId);
    enlace.textContent = contacto.nombre || '-';
    celda.appendChild(enlace);

    if (Number(contacto.es_principal) === 1) {
        var principal = document.createElement('span');
        principal.className = 'badge empresa-contact-principal';
        principal.textContent = 'Principal';
        celda.appendChild(principal);
    }

    fila.appendChild(celda);
}

// Convierte el tipo almacenado en una etiqueta legible sin alterar su valor real.
function formatearTipoDireccion(tipo) {
    var valor = tipo === null || tipo === undefined ? '' : String(tipo).trim();

    return valor ? valor.charAt(0).toUpperCase() + valor.slice(1) : '-';
}

// Une las partes disponibles de una linea de direccion sin repetir contenido.
function unirPartesDireccion(partes) {
    var valores = [];
    var valoresNormalizados = new Set();

    partes.forEach(function (parte) {
        var texto = parte === null || parte === undefined ? '' : String(parte).trim();
        var clave = texto.toLocaleLowerCase('es-MX');

        if (texto && !valoresNormalizados.has(clave)) {
            valoresNormalizados.add(clave);
            valores.push(texto);
        }
    });

    return valores.join(', ');
}

// Construye la direccion como un bloque vertical legible con los datos disponibles.
function formatearDireccionEmpresa(direccion) {
    var listaDireccion = document.createElement('div');
    var numero = unirPartesDireccion([direccion.numero_exterior, direccion.numero_interior]);
    var lineas = [
        unirPartesDireccion([direccion.calle, numero]),
        direccion.colonia ? 'Col. ' + String(direccion.colonia).trim() : '',
        unirPartesDireccion([direccion.localidad, direccion.municipio, direccion.ciudad, direccion.estado]),
        direccion.codigo_postal ? 'C.P. ' + String(direccion.codigo_postal).trim() : ''
    ];

    listaDireccion.className = 'empresa-address-lines';
    lineas.forEach(function (linea) {
        if (!linea) {
            return;
        }

        var lineaDireccion = document.createElement('div');
        lineaDireccion.className = 'empresa-address-line';
        lineaDireccion.textContent = linea;
        listaDireccion.appendChild(lineaDireccion);
    });

    if (!listaDireccion.children.length) {
        var sinDatos = document.createElement('span');
        sinDatos.className = 'empresa-address-empty';
        sinDatos.textContent = 'Sin datos de dirección';
        listaDireccion.appendChild(sinDatos);
    }

    return listaDireccion;
}

// Convierte las fechas de la base de datos a una lectura local uniforme.
function formatearFechaEmpresa(fecha) {
    if (!fecha) {
        return '-';
    }

    var fechaLocal = new Date(fecha.replace(' ', 'T'));
    return Number.isNaN(fechaLocal.getTime()) ? fecha : fechaLocal.toLocaleString('es-MX');
}

// Agrega el acceso de edicion sin exponer la eliminacion en esta vista.
function agregarEdicionDireccion(fila, direccion) {
    var empresaId = obtenerEmpresaId();
    var celdaEditar = document.createElement('td');
    var enlaceEditar = document.createElement('a');

    enlaceEditar.className = 'btn btn-secondary btn-sm';
    enlaceEditar.href = 'form_direccion_empresa.php?empresa_id=' + empresaId + '&direccion_id=' + direccion.id;
    enlaceEditar.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i> Editar';
    enlaceEditar.setAttribute('aria-label', 'Editar dirección ' + (direccion.alias || 'sin alias'));
    celdaEditar.appendChild(enlaceEditar);
    fila.appendChild(celdaEditar);
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
    campos.forEach(function (campo, indice) {
        var columna = document.createElement('div');
        var campoFormulario = document.createElement('div');
        var etiqueta = document.createElement('label');
        var valor = document.createElement('span');
        columna.className = 'col-12 col-md-6 col-xl-4';
        campoFormulario.className = 'empresa-data-field';
        etiqueta.className = 'form-label empresa-data-label';
        valor.className = 'empresa-data-value';
        valor.id = 'empresa-dato-' + indice;
        valor.setAttribute('role', 'textbox');
        valor.setAttribute('aria-readonly', 'true');
        etiqueta.htmlFor = valor.id;
        etiqueta.textContent = campo[0];
        valor.textContent = campo[1] || '-';
        campoFormulario.appendChild(etiqueta);
        campoFormulario.appendChild(valor);
        columna.appendChild(campoFormulario);
        datosGenerales.appendChild(columna);
    });
}

// Llena la tabla con las direcciones asociadas a la empresa.
function mostrarDirecciones(direcciones) {
    tablaDirecciones.innerHTML = '';
    if (!direcciones.length) {
        var filaVacia = document.createElement('tr');
        agregarCelda(filaVacia, 'Sin direcciones registradas');
        filaVacia.cells[0].colSpan = 3;
        tablaDirecciones.appendChild(filaVacia);
        return;
    }

    direcciones.forEach(function (direccion) {
        var fila = document.createElement('tr');
        var celdaDireccion = document.createElement('td');
        fila.className = 'empresa-detail-row';
        agregarCelda(fila, formatearTipoDireccion(direccion.tipo_direccion));
        celdaDireccion.className = 'empresa-detail-cell empresa-address-cell';
        celdaDireccion.appendChild(formatearDireccionEmpresa(direccion));
        fila.appendChild(celdaDireccion);
        agregarEdicionDireccion(fila, direccion);
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
        fila.className = 'empresa-detail-row';
        agregarContactoEnlazado(fila, contacto);
        agregarCelda(fila, contacto.celular);
        agregarCelda(fila, contacto.correo);
        agregarCelda(fila, contacto.departamento);
        agregarCelda(fila, contacto.puesto);
        agregarCelda(fila, Number(contacto.activo) === 1 ? 'Activo' : 'Inactivo');
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
            botonAgregarContacto.href = 'form_contacto.php?empresa_id=' + encodeURIComponent(datos.empresa.id_e);
            botonAgregarContacto.classList.remove('disabled');
            botonAgregarContacto.removeAttribute('aria-disabled');
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
