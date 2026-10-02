var mensajeEmpresa = document.getElementById('mensaje_empresa');
var contenidoEmpresa = document.getElementById('contenido_empresa');
var tituloEmpresa = document.getElementById('titulo_empresa');
var datosGenerales = document.getElementById('datos_generales');
var tablaDirecciones = document.getElementById('tabla_direcciones');
var tablaContactos = document.getElementById('tabla_contactos');
var botonAgregarDireccion = document.getElementById('btn_agregar_direccion');
var botonAgregarContacto = document.getElementById('btn_agregar_contacto');
var botonEditarEmpresa = document.getElementById('btn_editar_empresa');
var contenedorEmpresa = document.querySelector('.empresa-detalle');
var notasEmpresa = document.getElementById('empresa_notas');
var botonGuardarNotas = document.getElementById('btn_guardar_notas');
var estadoNotasEmpresa = document.getElementById('estado_notas_empresa');
var tokenNotasEmpresa = contenedorEmpresa.dataset.notasCsrf;
var notasGuardadas = '';
var notasConCambios = false;
var etiquetasEstatusOportunidad = {
    preparacion: 'En preparación',
    cotizada: 'Cotizada',
    negociacion: 'En negociación',
    ganada: 'Ganada',
    perdida: 'Perdida',
    cancelada: 'Cancelada'
};

// Escapa texto antes de insertarlo en el HTML generado por las tablas.
function escaparHtmlEmpresa(valor) {
    var elemento = document.createElement('span');
    elemento.textContent = valor === null || valor === undefined ? '' : String(valor);
    return elemento.innerHTML;
}

// Convierte importes almacenados en una cantidad ordenable y visible en moneda nacional.
function formatearImporteEmpresa(valor, tipo) {
    var numero = Number(String(valor || '0').replace(/,/g, ''));
    if (tipo !== 'display') {
        return Number.isFinite(numero) ? numero : 0;
    }
    return Number.isFinite(numero)
        ? numero.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
        : '-';
}

var oportunidadesEmpresa = new DataTable('#tabla_oportunidades_empresa', getDataTableOptions({
    data: [],
    order: [[6, 'desc']],
    columns: [
        { data: 'fecha', defaultContent: '', render: DataTable.render.text() },
        {
            data: 'contacto',
            defaultContent: '',
            render: function (valor, tipo, fila) {
                if (tipo !== 'display') {
                    return valor || '';
                }
                var contactoId = Number(fila.contacto_id);
                if (!contactoId) {
                    return valor ? escaparHtmlEmpresa(valor) : '—';
                }
                return '<a class="entity-link" href="ver_contacto.php?id=' + contactoId + '">'
                    + escaparHtmlEmpresa(valor || 'Ver contacto') + '</a>';
            }
        },
        { data: 'descripcion_corta', defaultContent: '', render: DataTable.render.text() },
        { data: 'importe', defaultContent: '', render: formatearImporteEmpresa },
        {
            data: 'estatus',
            defaultContent: '',
            render: function (valor, tipo) {
                var clave = String(valor || '').toLocaleLowerCase('es-MX');
                var etiqueta = etiquetasEstatusOportunidad[clave] || valor || 'Sin estatus';
                if (tipo !== 'display') {
                    return etiqueta;
                }
                var clase = Object.prototype.hasOwnProperty.call(etiquetasEstatusOportunidad, clave)
                    ? ' op-status-' + clave
                    : '';
                return '<span class="badge op-status' + clase + '">' + escaparHtmlEmpresa(etiqueta) + '</span>';
            }
        },
        {
            data: 'id',
            orderable: false,
            searchable: false,
            render: function (valor, tipo) {
                if (tipo !== 'display') {
                    return valor;
                }
                return '<a class="btn btn-outline-secondary btn-sm" href="ver_oportunidad.php?id='
                    + Number(valor) + '">Ver oportunidad</a>';
            }
        },
        {
            data: 'created_at',
            visible: false,
            searchable: false,
            render: function (valor, tipo, fila) {
                return String(valor || '') + String(fila.id).padStart(10, '0');
            }
        }
    ]
}));

var cotizacionesEmpresa = new DataTable('#tabla_cotizaciones_empresa', getDataTableOptions({
    data: [],
    order: [[7, 'desc']],
    columns: [
        { data: 'cot_fecha', defaultContent: '', render: DataTable.render.text() },
        { data: 'cot_numero', defaultContent: '', render: DataTable.render.text() },
        { data: 'cot_contacto', defaultContent: '', render: DataTable.render.text() },
        {
            data: 'cot_total',
            defaultContent: '',
            render: formatearImporteEmpresa
        },
        { data: 'cot_status', defaultContent: '', render: DataTable.render.text() },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function (valor, tipo, fila) {
                if (tipo !== 'display' || !fila.pdf_disponible) {
                    return tipo === 'display' ? '<span class="empresa-quote-no-pdf">Sin PDF</span>' : '';
                }
                var archivo = encodeURIComponent(String(fila.cot_archivo || ''));
                return '<a class="btn btn-outline-secondary btn-sm" href="../filesPDF/' + archivo
                    + '" target="_blank" rel="noopener">Ver PDF</a>';
            }
        },
        {
            data: 'id_coti',
            orderable: false,
            searchable: false,
            render: function (valor, tipo) {
                if (tipo !== 'display') {
                    return valor;
                }
                return '<span class="btn btn-outline-secondary btn-sm disabled empresa-quote-detail-pending"'
                    + ' aria-disabled="true" title="Disponible cuando exista la ficha individual de cotización"'
                    + ' data-cotizacion-id="' + Number(valor) + '">Detalle pendiente</span>';
            }
        },
        { data: 'id_coti', visible: false, searchable: false }
    ]
}));

applyColumnFilters(oportunidadesEmpresa);
applyColumnFilters(cotizacionesEmpresa);

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
    enlace.className = 'entity-link';
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

// Presenta las notas almacenadas y habilita su edición directa.
function mostrarNotasEmpresa(empresa) {
    notasGuardadas = String(empresa.observaciones || '');
    notasEmpresa.value = notasGuardadas;
    notasEmpresa.disabled = false;
    notasConCambios = false;
    botonGuardarNotas.disabled = true;
    estadoNotasEmpresa.textContent = empresa.updated_at
        ? 'Última actualización: ' + formatearFechaEmpresa(empresa.updated_at)
        : 'Sin modificaciones registradas.';
    estadoNotasEmpresa.className = 'empresa-notes-status mt-2';
}

// Carga en la tabla las oportunidades vinculadas por identificador de empresa.
function mostrarOportunidadesEmpresa(oportunidades) {
    oportunidadesEmpresa.clear();
    if (oportunidades.length) {
        oportunidadesEmpresa.rows.add(oportunidades);
    }
    oportunidadesEmpresa.draw();
}

// Carga en la tabla las cotizaciones vinculadas por identificador de empresa.
function mostrarCotizacionesEmpresa(cotizaciones) {
    cotizacionesEmpresa.clear();
    if (cotizaciones.length) {
        cotizacionesEmpresa.rows.add(cotizaciones);
    }
    cotizacionesEmpresa.draw();
}

// Guarda las notas y actualiza su estado sin recargar la ficha de empresa.
async function guardarNotasEmpresa() {
    var empresaId = obtenerEmpresaId();
    if (!empresaId || !notasConCambios) {
        return;
    }

    botonGuardarNotas.disabled = true;
    estadoNotasEmpresa.textContent = 'Guardando notas...';
    estadoNotasEmpresa.className = 'empresa-notes-status mt-2';

    try {
        var respuesta = await fetch('../backend/empresas/guardar_notas_empresa.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: new URLSearchParams({
                empresa_id: empresaId,
                notas: notasEmpresa.value,
                csrf: tokenNotasEmpresa
            })
        });
        var datos = await respuesta.json();
        if (!respuesta.ok || datos.error) {
            throw new Error(datos.error || 'No fue posible guardar las notas.');
        }

        notasGuardadas = String(datos.notas || '');
        notasEmpresa.value = notasGuardadas;
        notasConCambios = false;
        estadoNotasEmpresa.textContent = 'Notas guardadas. Última actualización: ' + formatearFechaEmpresa(datos.updated_at);
        estadoNotasEmpresa.className = 'empresa-notes-status empresa-notes-status-success mt-2';
        var fechaActualizacion = document.getElementById('empresa-dato-8');
        if (fechaActualizacion) {
            fechaActualizacion.textContent = formatearFechaEmpresa(datos.updated_at);
        }
    } catch (error) {
        botonGuardarNotas.disabled = false;
        estadoNotasEmpresa.textContent = error.message;
        estadoNotasEmpresa.className = 'empresa-notes-status empresa-notes-status-error mt-2';
    }
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
            mostrarNotasEmpresa(datos.empresa);
            mostrarDirecciones(datos.direcciones || []);
            mostrarContactos(datos.contactos || []);
            mostrarOportunidadesEmpresa(datos.oportunidades || []);
            mostrarCotizacionesEmpresa(datos.cotizaciones || []);
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
            oportunidadesEmpresa.columns.adjust();
        })
        .catch(function () {
            mensajeEmpresa.textContent = 'No fue posible cargar la información de la empresa.';
            mensajeEmpresa.className = 'alert alert-danger';
        });
}

// Marca cambios pendientes y evita guardar cuando el contenido no cambió.
notasEmpresa.addEventListener('input', function () {
    notasConCambios = notasEmpresa.value !== notasGuardadas;
    botonGuardarNotas.disabled = !notasConCambios;
    estadoNotasEmpresa.textContent = notasConCambios ? 'Cambios sin guardar.' : 'Sin cambios pendientes.';
    estadoNotasEmpresa.className = 'empresa-notes-status mt-2';
});

botonGuardarNotas.addEventListener('click', guardarNotasEmpresa);

// Recalcula la tabla visible después de cambiar de pestaña.
document.querySelectorAll('#empresa-historial-tabs [data-bs-toggle="tab"]').forEach(function (boton) {
    boton.addEventListener('click', function () {
        window.setTimeout(function () {
            var tabla = boton.id === 'empresa-cotizaciones-tab' ? cotizacionesEmpresa : oportunidadesEmpresa;
            tabla.columns.adjust();
        }, 0);
    });
});

// Advierte antes de abandonar la ficha cuando existen notas sin guardar.
window.addEventListener('beforeunload', function (evento) {
    if (!notasConCambios) {
        return;
    }
    evento.preventDefault();
    evento.returnValue = '';
});

cargarDetalleEmpresa();
