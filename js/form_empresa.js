var campoEmpresaId = document.getElementById('empresa_id');
var tituloFormEmpresa = document.getElementById('titulo_form_empresa');
var botonGuardarEmpresa = document.getElementById('btn_guardar_empresa');
var botonCancelarEmpresa = document.getElementById('btn_cancelar_empresa');

// Llena el formulario con datos generales cuando se abre para editar una empresa.
function cargarEmpresaParaEditar() {
    var empresaId = campoEmpresaId.value;
    var campos = [
        'empresa', 'razon_social', 'rfc', 'rol', 'actividad_economica',
        'regimen_fiscal_codigo', 'regimen_fiscal_descripcion', 'regimen_capital',
        'tipo_persona', 'giro_mercantil', 'mercado', 'telefono_principal',
        'email_principal', 'pagina_web', 'estatus', 'origen_registro', 'observaciones',
        'id_e', 'created_at', 'updated_at'
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
                var input = document.getElementById(campo);
                if (input) {
                    input.value = datos.empresa[campo] || '';
                }
            });
            tituloFormEmpresa.textContent = 'Editar empresa';
            botonGuardarEmpresa.textContent = 'Guardar cambios';
            botonCancelarEmpresa.href = 'ver_empresa.php?id=' + empresaId;
        })
        .catch(function () {
            tituloFormEmpresa.textContent = 'No fue posible cargar la empresa';
        });
}

cargarEmpresaParaEditar();
