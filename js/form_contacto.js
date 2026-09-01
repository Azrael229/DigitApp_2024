var formularioContacto = document.getElementById('form_contacto');
var contenedorFormularioContacto = document.querySelector('.contacto-form-page');
var mensajeFormularioContacto = document.getElementById('mensaje_form_contacto');
var tituloFormularioContacto = document.getElementById('titulo_form_contacto');
var botonGuardarContacto = document.getElementById('btn_guardar_contacto');
var selectorDepartamento = document.getElementById('id_departamento');
var selectorEmpresas = document.getElementById('empresas');
var selectorPrincipal = document.getElementById('empresa_principal');
var botonCancelarContacto = document.getElementById('btn_cancelar_contacto');

// Muestra mensajes del formulario sin insertar texto remoto como HTML.
function mostrarMensajeContacto(texto, tipo) {
    mensajeFormularioContacto.textContent = texto;
    mensajeFormularioContacto.className = 'alert alert-' + tipo + ' mt-4';
}

// Devuelve los IDs seleccionados en el selector multiple de empresas.
function obtenerEmpresasSeleccionadas() {
    return Array.from(selectorEmpresas.selectedOptions).map(function (opcion) {
        return opcion.value;
    });
}

// Mantiene la empresa principal dentro de las empresas actualmente asociadas.
function sincronizarEmpresaPrincipal(preferida) {
    var empresas = obtenerEmpresasSeleccionadas();
    var principalAnterior = preferida || selectorPrincipal.value;

    selectorPrincipal.replaceChildren();
    var opcionVacia = document.createElement('option');
    opcionVacia.value = '';
    opcionVacia.textContent = 'Sin empresa principal';
    selectorPrincipal.appendChild(opcionVacia);

    empresas.forEach(function (empresaId) {
        var opcionEmpresa = selectorEmpresas.querySelector('option[value="' + empresaId + '"]');
        var opcionPrincipal = document.createElement('option');
        opcionPrincipal.value = empresaId;
        opcionPrincipal.textContent = opcionEmpresa ? opcionEmpresa.textContent : empresaId;
        selectorPrincipal.appendChild(opcionPrincipal);
    });

    selectorPrincipal.disabled = empresas.length === 0;
    if (empresas.length === 0) {
        return;
    }

    selectorPrincipal.value = empresas.includes(String(principalAnterior))
        ? String(principalAnterior)
        : empresas[0];
}

// Preselecciona la empresa de origen solo durante el alta de un nuevo contacto.
function aplicarEmpresaContextual(idEmpresa) {
    var opcion = selectorEmpresas.querySelector('option[value="' + idEmpresa + '"]');

    if (!opcion) {
        mostrarMensajeContacto('La empresa de origen no pudo cargarse. Puedes seleccionar una empresa manualmente.', 'warning');
        sincronizarEmpresaPrincipal();
        return;
    }

    opcion.selected = true;
    if (window.jQuery && jQuery.fn.select2) {
        jQuery(selectorEmpresas).trigger('change');
    }
    sincronizarEmpresaPrincipal(idEmpresa);
}

// Define un retorno interno a empresa solo cuando la relacion del contacto lo confirma.
function aplicarRetornoEmpresa(idEmpresa, empresas) {
    var relacionada = (empresas || []).some(function (empresa) {
        return String(empresa.id_empresa) === String(idEmpresa);
    });

    if (relacionada) {
        botonCancelarContacto.href = 'ver_empresa.php?id=' + encodeURIComponent(idEmpresa);
    }
}

// Carga el catalogo vigente para que el departamento siempre provenga del backend.
function cargarCatalogoDepartamentos(valorSeleccionado) {
    return fetch('../backend/contactos/query_catalogo_departamentos.php')
        .then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error('No fue posible cargar el catálogo de departamentos.');
            }
            return respuesta.json();
        })
        .then(function (departamentos) {
            departamentos.forEach(function (departamento) {
                var opcion = document.createElement('option');
                opcion.value = departamento.id;
                opcion.textContent = departamento.nombre;
                selectorDepartamento.appendChild(opcion);
            });
            selectorDepartamento.value = valorSeleccionado || '';
        });
}

// Carga los datos de edicion desde el endpoint especifico del nuevo modelo.
function cargarContactoParaEdicion(idContacto) {
    return fetch('../backend/contactos/query_detalle_contacto.php', {
        method: 'POST',
        body: idContacto
    })
        .then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error('No fue posible cargar el contacto solicitado.');
            }
            return respuesta.json();
        })
        .then(function (contacto) {
            document.getElementById('contacto_nombre').value = contacto.nombre || '';
            document.getElementById('contacto_cel').value = contacto.celular || '';
            document.getElementById('contacto_email').value = contacto.correo || '';
            document.getElementById('puesto').value = contacto.puesto || '';
            document.getElementById('activo').value = String(contacto.activo) === '0' ? '0' : '1';

            (contacto.empresas || []).forEach(function (empresa) {
                var opcion = selectorEmpresas.querySelector('option[value="' + String(empresa.id_empresa) + '"]');
                if (opcion) {
                    opcion.selected = true;
                }
            });

            if (window.jQuery && jQuery.fn.select2) {
                jQuery(selectorEmpresas).trigger('change');
            }

            var principal = (contacto.empresas || []).find(function (empresa) {
                return String(empresa.es_principal) === '1';
            });
            sincronizarEmpresaPrincipal(principal ? principal.id_empresa : null);
            if (empresaRetorno) {
                aplicarRetornoEmpresa(empresaRetorno, contacto.empresas);
            }
            selectorDepartamento.value = contacto.id_departamento || '';
            tituloFormularioContacto.textContent = 'Editar contacto';
            botonGuardarContacto.textContent = 'Guardar cambios';
        });
}

if (window.jQuery && jQuery.fn.select2) {
    jQuery(selectorEmpresas).select2({
        width: '100%',
        placeholder: 'Selecciona una o varias empresas'
    });
    jQuery(selectorEmpresas).on('change', function () {
        sincronizarEmpresaPrincipal();
    });
} else {
    selectorEmpresas.addEventListener('change', function () {
        sincronizarEmpresaPrincipal();
    });
}

formularioContacto.addEventListener('submit', function (evento) {
    var empresas = obtenerEmpresasSeleccionadas();
    if (empresas.length > 0 && !empresas.includes(selectorPrincipal.value)) {
        evento.preventDefault();
        mostrarMensajeContacto('Selecciona una empresa principal válida.', 'danger');
        return;
    }

    if (!formularioContacto.checkValidity()) {
        evento.preventDefault();
        formularioContacto.classList.add('was-validated');
        mostrarMensajeContacto('Completa los campos obligatorios antes de guardar.', 'danger');
    }
});

var idContacto = contenedorFormularioContacto.dataset.contactId;
var empresaContextual = contenedorFormularioContacto.dataset.contextCompanyId;
var empresaRetorno = contenedorFormularioContacto.dataset.returnCompanyId;
cargarCatalogoDepartamentos()
    .then(function () {
        if (!idContacto) {
            if (empresaContextual) {
                aplicarEmpresaContextual(empresaContextual);
                botonCancelarContacto.href = 'ver_empresa.php?id=' + encodeURIComponent(empresaContextual);
            } else {
                sincronizarEmpresaPrincipal();
            }
            return null;
        }
        return cargarContactoParaEdicion(idContacto);
    })
    .catch(function (error) {
        mostrarMensajeContacto(error.message || 'No fue posible preparar el formulario.', 'danger');
    });
