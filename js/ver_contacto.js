var mensajeContacto = document.getElementById('mensaje_contacto');
var contenidoContacto = document.getElementById('contenido_contacto');
var tituloContacto = document.getElementById('titulo_contacto');
var estadoContacto = document.getElementById('estado_contacto');
var datosContacto = document.getElementById('datos_contacto');
var datosLaborales = document.getElementById('datos_laborales');
var empresasContacto = document.getElementById('empresas_contacto');
var botonEditarContacto = document.getElementById('btn_editar_contacto');
var botonVolverContacto = document.getElementById('btn_volver_contacto');

// Obtiene un identificador positivo sin aceptar valores parciales o ambiguos.
function obtenerIdContacto() {
    var valor = new URLSearchParams(window.location.search).get('id');

    return /^[1-9]\d*$/.test(valor || '') ? valor : null;
}

// Obtiene el contexto interno de empresa sin aceptar destinos ajenos a la aplicacion.
function obtenerContextoEmpresa() {
    var parametros = new URLSearchParams(window.location.search);
    var empresaId = parametros.get('empresa_id');

    if (parametros.get('from') !== 'empresa' || !/^[1-9]\d*$/.test(empresaId || '')) {
        return null;
    }

    return empresaId;
}

// Conserva el retorno a la empresa solo si esta vinculada realmente al contacto.
function resolverContextoEmpresa(contacto) {
    var empresaId = obtenerContextoEmpresa();
    var empresas = contacto.empresas || [];
    var relacionada = empresas.some(function (empresa) {
        return String(empresa.id_empresa) === empresaId;
    });

    return relacionada ? empresaId : null;
}

// Agrega un campo de solo lectura con etiqueta y contenido seguro.
function agregarCampoContacto(contenedor, etiquetaTexto, valor, columnas) {
    var columna = document.createElement('div');
    var campo = document.createElement('div');
    var etiqueta = document.createElement('label');
    var contenido = document.createElement('span');

    columna.className = columnas || 'col-12 col-md-6';
    campo.className = 'contacto-data-field';
    etiqueta.className = 'contacto-data-label';
    contenido.className = 'contacto-data-value';
    etiqueta.textContent = etiquetaTexto;
    contenido.textContent = valor || '—';
    campo.appendChild(etiqueta);
    campo.appendChild(contenido);
    columna.appendChild(campo);
    contenedor.appendChild(columna);
}

// Presenta los datos generales y laborales del contacto cargado.
function mostrarDatosContacto(contacto) {
    var activo = String(contacto.activo) !== '0';

    tituloContacto.textContent = contacto.nombre || 'Contacto';
    estadoContacto.textContent = activo ? 'Activo' : 'Inactivo';
    estadoContacto.className = 'badge contactos-status ' + (activo ? 'contactos-status-active' : 'contactos-status-inactive');

    datosContacto.replaceChildren();
    agregarCampoContacto(datosContacto, 'Teléfono', contacto.celular);
    agregarCampoContacto(datosContacto, 'Correo electrónico', contacto.correo);

    datosLaborales.replaceChildren();
    agregarCampoContacto(datosLaborales, 'Departamento', contacto.departamento);
    agregarCampoContacto(datosLaborales, 'Puesto', contacto.puesto);
}

// Construye la lista de empresas relacionadas sin insertar datos como HTML.
function mostrarEmpresasContacto(empresas) {
    empresasContacto.replaceChildren();
    if (!empresas.length) {
        var vacio = document.createElement('p');
        vacio.className = 'contacto-empty-state mb-0';
        vacio.textContent = 'Sin empresa asociada';
        empresasContacto.appendChild(vacio);
        return;
    }

    var lista = document.createElement('ul');
    lista.className = 'contacto-empresas-list';
    empresas.forEach(function (empresa) {
        var elemento = document.createElement('li');
        var enlace = document.createElement('a');

        elemento.className = 'contacto-empresa-item';
        enlace.className = 'contacto-empresa-link';
        enlace.href = 'ver_empresa.php?id=' + encodeURIComponent(empresa.id_empresa);
        enlace.textContent = empresa.empresa || 'Empresa';
        elemento.appendChild(enlace);

        if (String(empresa.es_principal) === '1') {
            var principal = document.createElement('span');
            principal.className = 'badge contacto-principal-badge';
            principal.textContent = 'Principal';
            elemento.appendChild(principal);
        }

        lista.appendChild(elemento);
    });
    empresasContacto.appendChild(lista);
}

// Consulta y muestra la ficha individual solicitada.
function cargarContacto() {
    var idContacto = obtenerIdContacto();
    if (!idContacto) {
        mensajeContacto.textContent = 'No se indicó un contacto válido para consultar.';
        mensajeContacto.className = 'alert alert-warning';
        estadoContacto.textContent = 'Sin datos';
        return;
    }

    fetch('../backend/contactos/query_detalle_contacto.php', {
        method: 'POST',
        body: idContacto
    })
        .then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error('No fue posible consultar el contacto solicitado.');
            }
            return respuesta.json();
        })
        .then(function (contacto) {
            if (contacto.error) {
                throw new Error(contacto.error);
            }
            mostrarDatosContacto(contacto);
            mostrarEmpresasContacto(contacto.empresas || []);
            var empresaContextual = resolverContextoEmpresa(contacto);
            botonEditarContacto.href = 'form_contacto.php?id=' + encodeURIComponent(contacto.id);
            if (empresaContextual) {
                botonVolverContacto.href = 'ver_empresa.php?id=' + encodeURIComponent(empresaContextual);
                botonEditarContacto.href += '&from=empresa&empresa_id=' + encodeURIComponent(empresaContextual);
            }
            botonEditarContacto.classList.remove('disabled');
            botonEditarContacto.removeAttribute('aria-disabled');
            mensajeContacto.classList.add('d-none');
            contenidoContacto.classList.remove('d-none');
        })
        .catch(function (error) {
            mensajeContacto.textContent = error.message || 'No fue posible cargar la información del contacto.';
            mensajeContacto.className = 'alert alert-danger';
            estadoContacto.textContent = 'No disponible';
        });
}

cargarContacto();
