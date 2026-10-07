var formularioContacto = document.getElementById('form_contacto');
var contenedorFormularioContacto = document.querySelector('.contacto-form-page');
var mensajeFormularioContacto = document.getElementById('mensaje_form_contacto');
var tituloFormularioContacto = document.getElementById('titulo_form_contacto');
var botonGuardarContacto = document.getElementById('btn_guardar_contacto');
var selectorDepartamento = document.getElementById('id_departamento');
var selectorEmpresas = document.getElementById('empresas');
var selectorPrincipal = document.getElementById('empresa_principal');
var selectorDirecciones = document.getElementById('direcciones');
var catalogoDirecciones = Array.from(selectorDirecciones.options).map(function (opcion) {
    return {value: opcion.value, label: opcion.textContent, companyId: opcion.dataset.companyId};
});
var botonCancelarContacto = document.getElementById('btn_cancelar_contacto');
var retornoInformeContacto = contenedorFormularioContacto.dataset.returnUrl || '';
var campoTelefonoContacto = document.getElementById('contacto_cel');
var campoCorreoContacto = document.getElementById('contacto_email');
var entradaDepartamento = document.getElementById('nuevo_departamento');
var botonAgregarDepartamento = document.getElementById('btn_agregar_departamento');
var estadoDepartamento = document.getElementById('estado_departamento');

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
    selectorPrincipal.setCustomValidity('');
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

// Limita las direcciones a las empresas asociadas y conserva selecciones válidas.
function sincronizarDirecciones(preferidas) {
    var empresas = obtenerEmpresasSeleccionadas();
    var seleccionadas = preferidas || Array.from(selectorDirecciones.selectedOptions).map(function (opcion) {
        return opcion.value;
    });
    selectorDirecciones.replaceChildren();
    catalogoDirecciones.forEach(function (direccion) {
        if (!empresas.includes(String(direccion.companyId))) {
            return;
        }
        var opcion = document.createElement('option');
        opcion.value = direccion.value;
        opcion.textContent = direccion.label;
        opcion.dataset.companyId = direccion.companyId;
        opcion.selected = seleccionadas.includes(String(direccion.value));
        selectorDirecciones.appendChild(opcion);
    });
    selectorDirecciones.disabled = empresas.length === 0;
    if (window.jQuery && jQuery.fn.select2) {
        jQuery(selectorDirecciones).trigger('change.select2');
    }
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
    sincronizarDirecciones();
}

// Define un retorno interno a empresa solo cuando la relacion del contacto lo confirma.
function aplicarRetornoEmpresa(idEmpresa, empresas) {
    if (retornoInformeContacto) {
        botonCancelarContacto.href = retornoInformeContacto;
        return;
    }
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

// Crea un departamento en el catálogo vigente y lo selecciona sin abandonar el formulario.
function agregarDepartamentoManual() {
    var nombre = entradaDepartamento.value.trim().replace(/\s+/g, ' ');
    if (!nombre) {
        estadoDepartamento.textContent = 'Escribe el nombre del departamento.';
        estadoDepartamento.className = 'empresa-notes-status empresa-notes-status-error mt-1';
        entradaDepartamento.focus();
        return;
    }
    botonAgregarDepartamento.disabled = true;
    estadoDepartamento.textContent = 'Guardando...';
    fetch('../backend/contactos/query_catalogo_departamentos.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
        body: new URLSearchParams({nombre: nombre, csrf: document.getElementById('contacto_csrf').value})
    })
        .then(function (respuesta) { return respuesta.json().then(function (datos) { return {respuesta: respuesta, datos: datos}; }); })
        .then(function (resultado) {
            if (!resultado.respuesta.ok || resultado.datos.error) {
                throw new Error(resultado.datos.error || 'No fue posible guardar el departamento.');
            }
            var departamento = resultado.datos.departamento;
            var opcion = selectorDepartamento.querySelector('option[value="' + departamento.id + '"]');
            if (!opcion) {
                opcion = document.createElement('option');
                opcion.value = departamento.id;
                selectorDepartamento.appendChild(opcion);
            }
            opcion.textContent = departamento.nombre;
            selectorDepartamento.value = String(departamento.id);
            entradaDepartamento.value = '';
            estadoDepartamento.textContent = departamento.nombre + ' quedó disponible y seleccionado.';
            estadoDepartamento.className = 'empresa-notes-status empresa-notes-status-success mt-1';
        })
        .catch(function (error) {
            estadoDepartamento.textContent = error.message;
            estadoDepartamento.className = 'empresa-notes-status empresa-notes-status-error mt-1';
        })
        .finally(function () { botonAgregarDepartamento.disabled = false; });
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
            document.getElementById('contacto_version').value = contacto.version || '';

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
            sincronizarDirecciones((contacto.direcciones || []).map(function (direccion) {
                return String(direccion.direccion_id);
            }));
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
        sincronizarDirecciones();
    });
    jQuery(selectorDirecciones).select2({width: '100%', placeholder: 'Selecciona una o varias direcciones'});
} else {
    selectorEmpresas.addEventListener('change', function () {
        sincronizarEmpresaPrincipal();
        sincronizarDirecciones();
    });
}

formularioContacto.addEventListener('submit', function (evento) {
    var empresas = obtenerEmpresasSeleccionadas();
    var tieneCanal = campoTelefonoContacto.value.trim() !== '' || campoCorreoContacto.value.trim() !== '';
    campoTelefonoContacto.setCustomValidity(tieneCanal ? '' : 'Captura un teléfono o un correo electrónico.');
    if (!tieneCanal) {
        evento.preventDefault();
        formularioContacto.classList.add('was-validated');
        mostrarMensajeContacto('Captura al menos un teléfono o un correo electrónico.', 'danger');
        campoTelefonoContacto.focus();
        return;
    }
    if (empresas.length > 0 && !empresas.includes(selectorPrincipal.value)) {
        evento.preventDefault();
        selectorPrincipal.setCustomValidity('Selecciona una empresa principal válida.');
        mostrarMensajeContacto('Selecciona una empresa principal válida.', 'danger');
        selectorPrincipal.reportValidity();
        return;
    }
    selectorPrincipal.setCustomValidity('');

    if (!formularioContacto.checkValidity()) {
        evento.preventDefault();
        formularioContacto.classList.add('was-validated');
        mostrarMensajeContacto('Completa los campos obligatorios antes de guardar.', 'danger');
    }
});

[campoTelefonoContacto, campoCorreoContacto].forEach(function (campo) {
    campo.addEventListener('input', function () { campoTelefonoContacto.setCustomValidity(''); });
});
botonAgregarDepartamento.addEventListener('click', agregarDepartamentoManual);
entradaDepartamento.addEventListener('keydown', function (evento) {
    if (evento.key === 'Enter') {
        evento.preventDefault();
        agregarDepartamentoManual();
    }
});

var idContacto = contenedorFormularioContacto.dataset.contactId;
var empresaContextual = contenedorFormularioContacto.dataset.contextCompanyId;
var empresaRetorno = contenedorFormularioContacto.dataset.returnCompanyId;
if (retornoInformeContacto) {
    botonCancelarContacto.href = retornoInformeContacto;
}
DigitAppDuplicateWarning.protegerFormulario({
    formulario: formularioContacto,
    boton: botonGuardarContacto,
    idAviso: 'aviso-duplicado-contacto',
    titulo: 'Es posible que este contacto ya exista',
    crearUrl: function () {
        var parametros = new URLSearchParams({
            tipo: 'contacto',
            nombre: document.getElementById('contacto_nombre').value,
            telefono: campoTelefonoContacto.value,
            correo: campoCorreoContacto.value,
            excluir_id: idContacto || ''
        });
        return '../backend/helpers/query_posibles_duplicados.php?' + parametros.toString();
    },
    mostrarError: function (mensaje) { mostrarMensajeContacto(mensaje, 'danger'); }
});
cargarCatalogoDepartamentos()
    .then(function () {
        if (!idContacto) {
            if (empresaContextual) {
                aplicarEmpresaContextual(empresaContextual);
                if (!retornoInformeContacto) {
                    botonCancelarContacto.href = 'ver_empresa.php?id=' + encodeURIComponent(empresaContextual);
                }
            } else {
                sincronizarEmpresaPrincipal();
                sincronizarDirecciones();
            }
            return null;
        }
        return cargarContactoParaEdicion(idContacto);
    })
    .catch(function (error) {
        mostrarMensajeContacto(error.message || 'No fue posible preparar el formulario.', 'danger');
    });
