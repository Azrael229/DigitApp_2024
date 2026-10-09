var campoEmpresaId = document.getElementById('empresa_id');
var tituloFormEmpresa = document.getElementById('titulo_form_empresa');
var botonGuardarEmpresa = document.getElementById('btn_guardar_empresa');
var botonCancelarEmpresa = document.getElementById('btn_cancelar_empresa');
var formularioEmpresa = document.getElementById('formEmpresa');
var retornoInformeEmpresa = formularioEmpresa.querySelector('input[name="return_url"]')?.value || '';

// Llena el formulario con datos generales cuando se abre para editar una empresa.
function cargarEmpresaParaEditar() {
    var empresaId = campoEmpresaId.value;
    var campos = [
        'empresa', 'razon_social', 'rfc', 'rol', 'actividad_economica',
        'regimen_capital',
        'tipo_persona', 'giro_mercantil', 'mercado', 'telefono_principal',
        'email_principal', 'pagina_web', 'estatus',
        'id_e', 'created_at', 'updated_at', 'version'
    ];

    if (!empresaId) {
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
            campos.forEach(function (campo) {
                var input = document.getElementById(campo === 'version' ? 'empresa_version' : campo);
                if (input) {
                    var valor = datos.empresa[campo] || '';
                    if (campo === 'created_at' || campo === 'updated_at') {
                        valor = DigitAppDate.dateTime(valor);
                    }
                    if ('value' in input) {
                        input.value = valor;
                    } else {
                        input.textContent = valor || '—';
                    }
                }
            });
            tituloFormEmpresa.textContent = 'Editar empresa';
            botonGuardarEmpresa.textContent = 'Guardar cambios';
            if (!retornoInformeEmpresa) {
                botonCancelarEmpresa.href = 'ver_empresa.php?id=' + empresaId;
            }
        })
        .catch(function () {
            tituloFormEmpresa.textContent = 'No fue posible cargar la empresa';
        });
}

cargarEmpresaParaEditar();

DigitAppDuplicateWarning.protegerFormulario({
    formulario: formularioEmpresa,
    boton: botonGuardarEmpresa,
    idAviso: 'aviso-duplicado-empresa',
    titulo: 'Es posible que esta empresa ya exista',
    crearUrl: function () {
        var parametros = new URLSearchParams({
            tipo: 'empresa',
            nombre: document.getElementById('empresa').value,
            razon_social: document.getElementById('razon_social').value,
            rfc: document.getElementById('rfc').value,
            telefono: document.getElementById('telefono_principal').value,
            correo: document.getElementById('email_principal').value,
            excluir_id: campoEmpresaId.value || ''
        });
        return '../backend/helpers/query_posibles_duplicados.php?' + parametros.toString();
    },
    mostrarError: function (mensaje) {
        var alerta = document.createElement('div');
        alerta.className = 'alert alert-danger';
        alerta.textContent = mensaje;
        formularioEmpresa.prepend(alerta);
    }
});
