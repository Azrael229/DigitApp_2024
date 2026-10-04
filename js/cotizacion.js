if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
     window.jQuery('#select_contacto').select2({
          width: '100%'
     });
}

function actualizarSelectContactoVisual() {
     if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
          window.jQuery('#select_contacto').trigger('change.select2');
     }
}

var formulario = document.getElementById('form_cotizacion');
var btnGuardar = document.getElementById('btn_guardar');
var inputNumCotizacion = document.getElementById('numero_coti');
var inputNombreEmpresa = document.getElementById('nombre_empresa');
var inputDirEmpresa = document.getElementById('dir_empresa');
var selectNombContacto = document.getElementById('select_contacto');
var selectDireccion = document.getElementById('select_direccion');
var inputNombreContacto = document.getElementById('nombre_contacto');
var inputCorreoContacto = document.getElementById('correo_contacto');
var inputCelContacto = document.getElementById('cel_contacto');
var inputDeptoContacto = document.getElementById('depto_contacto');
var inputEmpresaId = document.getElementById('cot_empresa_id');
var inputContactoId = document.getElementById('cot_contacto_id');
var inputDireccionId = document.getElementById('cot_direccion_id');
var inputCotizacionId = document.getElementById('cotizacion_id');
var selectCotizacionEstatus = document.getElementById('cot_status');
var contenedorPartidas = document.getElementById('partidas_cotizacion');
var btnAgregarPartida = document.getElementById('btn_agregar_partida');
var selectTiempoEntrega = document.getElementById('tiempo_entrega_tipo');
var contenedorTiempoCantidad = document.getElementById('contenedor_tiempo_entrega_cantidad');
var inputTiempoCantidad = document.getElementById('tiempo_entrega_cantidad');
var etiquetaTiempoEntrega = document.getElementById('etiqueta_tiempo_entrega');
var selectCondicionEntrega = document.getElementById('condicion_entrega');
var selectCondicionPago = document.getElementById('condicion_pago_tipo');
var contenedorPagoAnticipo = document.getElementById('contenedor_pago_anticipo');
var inputPagoAnticipo = document.getElementById('pago_anticipo_porcentaje');
var resumenPagoSaldo = document.getElementById('resumen_pago_saldo');
var contenedorPagoSaldoMomento = document.getElementById('contenedor_pago_saldo_momento');
var selectPagoSaldoMomento = document.getElementById('pago_saldo_momento');
var contenedorPagoCredito = document.getElementById('contenedor_pago_credito');
var inputPagoCreditoDias = document.getElementById('pago_credito_dias');
var selectGarantiaTipo = document.getElementById('garantia_tipo');
var contenedorGarantiaVigencia = document.getElementById('contenedor_garantia_vigencia');
var inputGarantiaVigencia = document.getElementById('garantia_vigencia');
var contenedorGarantiaUnidad = document.getElementById('contenedor_garantia_unidad');
var selectGarantiaUnidad = document.getElementById('garantia_unidad');
var selectCostosEnvio = document.getElementById('costos_envio');
var inputSubtotal = document.getElementById('subtotal');
var inputIva = document.getElementById('iva');
var inputTotal = document.getElementById('total');
var direccionAntigua = '';
var direccionesEmpresa = [];
var consecutivoPartida = 0;
var guardando = false;
var formularioModificado = false;
var cotizacionEditarId = formulario.dataset.cotizacionId;
var paginaRegreso = formulario.dataset.returnUrl || 'tablaCotizaciones.php';

formulario.addEventListener('input', function () {
     formularioModificado = true;
});
formulario.addEventListener('change', function () {
     formularioModificado = true;
});

window.addEventListener('beforeunload', function (event) {
     if (!formularioModificado || guardando) {
          return;
     }
     event.preventDefault();
     event.returnValue = '';
});

// Convierte una dirección estructurada en el texto que se muestra y se envía al PDF.
function formatearDireccion(direccion) {
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
     return [primeraLinea, ubicacion, direccion.entre_calles, direccion.referencia]
          .filter(Boolean).join(', ') || (direccion.direccion_original || '');
}

// Carga las alternativas disponibles para la empresa seleccionada.
function cargarSelectorDirecciones() {
     selectDireccion.innerHTML = '';
     if (direccionAntigua) {
          selectDireccion.add(new Option('Dirección antigua', 'antigua'));
     }
     direccionesEmpresa.forEach(function (direccion) {
          var etiqueta = direccion.tipo_direccion === 'fiscal' ? 'Dirección fiscal' : 'Dirección de entrega';
          if (direccion.alias) {
               etiqueta += ' - ' + direccion.alias;
          }
          selectDireccion.add(new Option(etiqueta, 'nueva:' + direccion.id));
     });
     if (!selectDireccion.options.length) {
          selectDireccion.add(new Option('Sin direcciones registradas', ''));
     }
     actualizarDireccionSeleccionada();
}

// Mantiene sincronizados el texto de dirección y su identificador normalizado.
function actualizarDireccionSeleccionada() {
     if (selectDireccion.value === 'antigua') {
          inputDireccionId.value = '';
          inputDirEmpresa.value = direccionAntigua;
          return;
     }
     var direccionId = selectDireccion.value.replace('nueva:', '');
     var direccion = direccionesEmpresa.find(function (item) {
          return String(item.id) === direccionId;
     });
     inputDireccionId.value = direccion ? direccionId : '';
     inputDirEmpresa.value = direccion ? formatearDireccion(direccion) : '';
}

// Carga los datos del contacto y las direcciones disponibles de su empresa.
async function selectContacto() {
     var id = selectNombContacto.value;
     if (!id) {
          inputEmpresaId.value = '';
          inputContactoId.value = '';
          inputNombreEmpresa.value = '';
          inputNombreContacto.value = '';
          inputCorreoContacto.value = '';
          inputCelContacto.value = '';
          inputDeptoContacto.value = '';
          direccionAntigua = '';
          direccionesEmpresa = [];
          cargarSelectorDirecciones();
          return null;
     }

     try {
          var response = await fetch('../backend/contactos/query_id_contacto.php', {
               method: 'POST',
               body: id
          });
          var data = await response.json();
          if (!response.ok || data.error) {
               throw new Error(data.error || 'No fue posible cargar el contacto.');
          }
          inputEmpresaId.value = data.empresa_id || '';
          inputContactoId.value = data.id || id;
          inputNombreEmpresa.value = data.empresa || '';
          inputNombreContacto.value = data.nombre || '';
          inputCorreoContacto.value = data.correo || '';
          inputCelContacto.value = data.celular || '';
          inputDeptoContacto.value = data.depto || '';
          direccionAntigua = data.dir_entrega || '';
          direccionesEmpresa = data.direcciones || [];
          cargarSelectorDirecciones();
          return data;
     } catch (error) {
          direccionAntigua = '';
          direccionesEmpresa = [];
          cargarSelectorDirecciones();
          alert(error.message);
          return null;
     }
}

selectDireccion.addEventListener('change', actualizarDireccionSeleccionada);

// Precarga la empresa, contacto y dirección relacionados con la oportunidad.
async function precargarOportunidad() {
     var oportunidadId = formulario.dataset.oportunidadId;
     if (!oportunidadId) {
          cargarSelectorDirecciones();
          return;
     }
     try {
          var oportunidadRespuesta = await fetch('../backend/oportunidades/api.php?action=get&id=' + encodeURIComponent(oportunidadId));
          var oportunidadData = await oportunidadRespuesta.json();
          if (!oportunidadRespuesta.ok || oportunidadData.error) {
               throw new Error(oportunidadData.error || 'No fue posible cargar la oportunidad.');
          }
          var oportunidad = oportunidadData.opportunity;
          var opcionesRespuesta = await fetch('../backend/oportunidades/api.php?action=options&empresa_id=' + encodeURIComponent(oportunidad.empresa_id));
          var opciones = await opcionesRespuesta.json();
          if (!opcionesRespuesta.ok || opciones.error) {
               throw new Error(opciones.error || 'No fue posible cargar los datos de la empresa.');
          }

          inputEmpresaId.value = oportunidad.empresa_id;
          inputNombreEmpresa.value = oportunidad.empresa || '';
          direccionAntigua = '';
          direccionesEmpresa = opciones.addresses || [];

          if (oportunidad.contacto_id) {
               selectNombContacto.value = String(oportunidad.contacto_id);
               actualizarSelectContactoVisual();
               await selectContacto();
               inputEmpresaId.value = oportunidad.empresa_id;
               inputNombreEmpresa.value = oportunidad.empresa || '';
               direccionAntigua = '';
               direccionesEmpresa = opciones.addresses || [];
          } else {
               selectNombContacto.value = '';
               actualizarSelectContactoVisual();
               inputContactoId.value = '';
               inputNombreContacto.value = '';
               inputCorreoContacto.value = '';
               inputCelContacto.value = '';
               inputDeptoContacto.value = '';
          }

          cargarSelectorDirecciones();
          if (oportunidad.direccion_id) {
               var valorDireccion = 'nueva:' + oportunidad.direccion_id;
               if (Array.from(selectDireccion.options).some(function (opcion) { return opcion.value === valorDireccion; })) {
                    selectDireccion.value = valorDireccion;
                    actualizarDireccionSeleccionada();
               }
          }
          formularioModificado = false;
     } catch (error) {
          alert(error.message);
     }
}

// Alterna el campo numérico según el tipo de tiempo de entrega seleccionado.
function actualizarTiempoEntrega() {
     var requiereCantidad = ['dias_habiles', 'semanas'].includes(selectTiempoEntrega.value);
     contenedorTiempoCantidad.classList.toggle('d-none', !requiereCantidad);
     etiquetaTiempoEntrega.classList.toggle('d-none', !requiereCantidad);
     inputTiempoCantidad.required = requiereCantidad;
     inputTiempoCantidad.disabled = !requiereCantidad;
     if (!requiereCantidad) {
          inputTiempoCantidad.value = '';
     }
     etiquetaTiempoEntrega.textContent = selectTiempoEntrega.value === 'semanas' ? 'semanas' : 'días hábiles';
}

// Muestra únicamente el dato adicional requerido por la condición de pago elegida.
function actualizarCondicionPago() {
     var requiereAnticipo = selectCondicionPago.value === 'anticipo_saldo';
     var requiereCredito = selectCondicionPago.value === 'credito';
     contenedorPagoAnticipo.classList.toggle('d-none', !requiereAnticipo);
     contenedorPagoSaldoMomento.classList.toggle('d-none', !requiereAnticipo);
     contenedorPagoCredito.classList.toggle('d-none', !requiereCredito);
     inputPagoAnticipo.required = requiereAnticipo;
     inputPagoAnticipo.disabled = !requiereAnticipo;
     selectPagoSaldoMomento.required = requiereAnticipo;
     selectPagoSaldoMomento.disabled = !requiereAnticipo;
     inputPagoCreditoDias.required = requiereCredito;
     inputPagoCreditoDias.disabled = !requiereCredito;
     if (requiereAnticipo) {
          var anticipo = Number(inputPagoAnticipo.value || 0);
          var momentoSaldo = selectPagoSaldoMomento.value === 'contra_aviso_entrega'
               ? 'contra aviso de entrega'
               : 'al finalizar';
          resumenPagoSaldo.textContent = anticipo > 0 && anticipo < 100
               ? (100 - anticipo) + '% ' + momentoSaldo + '.'
               : 'Indica un porcentaje entre 1 y 99.';
     }
}

// Solicita vigencia y unidad únicamente cuando se ofrece una garantía concreta.
function actualizarGarantia() {
     var requiereVigencia = ['producto', 'mano_obra'].includes(selectGarantiaTipo.value);
     contenedorGarantiaVigencia.classList.toggle('d-none', !requiereVigencia);
     contenedorGarantiaUnidad.classList.toggle('d-none', !requiereVigencia);
     inputGarantiaVigencia.required = requiereVigencia;
     inputGarantiaVigencia.disabled = !requiereVigencia;
     selectGarantiaUnidad.required = requiereVigencia;
     selectGarantiaUnidad.disabled = !requiereVigencia;
     if (!requiereVigencia) {
          inputGarantiaVigencia.value = '';
     }
}

// Muestra el aviso vacío únicamente cuando la cotización aún no tiene partidas.
function actualizarEstadoPartidas() {
     var aviso = document.getElementById('partidas_vacias');
     var hayPartidas = contenedorPartidas.querySelector('.partida-cotizacion') !== null;
     if (!hayPartidas && !aviso) {
          contenedorPartidas.insertAdjacentHTML('beforeend', '<tr id="partidas_vacias"><td colspan="5" class="text-center contactos-muted py-4">Aún no hay partidas. Utiliza “Agregar partida” para comenzar.</td></tr>');
     } else if (hayPartidas && aviso) {
          aviso.remove();
     }
}

// Agrega una fila de captura independiente y sin límite de longitud en la descripción.
function agregarPartida(datos, enfocar) {
     datos = datos || {};
     enfocar = enfocar !== false;
     consecutivoPartida += 1;
     var indice = consecutivoPartida;
     contenedorPartidas.insertAdjacentHTML('beforeend', `
          <tr class="partida-cotizacion" data-partida="${indice}">
               <td><label class="visually-hidden" for="partida_cantidad_${indice}">Cantidad</label><input class="form-control partida-cantidad" type="number" min="0.001" step="0.001" name="partida_cantidad[]" id="partida_cantidad_${indice}" required></td>
               <td><label class="visually-hidden" for="partida_unidad_${indice}">Unidad</label><select class="form-select partida-unidad" name="partida_unidad[]" id="partida_unidad_${indice}" required><option value="">Selecciona</option><option value="Servicio">Servicio</option><option value="Pieza">Pieza</option></select></td>
               <td><label class="visually-hidden" for="partida_descripcion_${indice}">Descripción</label><textarea class="form-control partida-descripcion" name="partida_descripcion[]" id="partida_descripcion_${indice}" rows="3" placeholder="Descripción del servicio o equipo" required></textarea></td>
               <td><label class="visually-hidden" for="partida_valor_${indice}">Valor unitario</label><input class="form-control partida-valor" type="number" min="0" step="0.01" name="partida_valor_unitario[]" id="partida_valor_${indice}" required><input class="partida-total" type="hidden" name="partida_total[]" value="0.00"></td>
               <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-partida" aria-label="Eliminar partida ${indice}"><i class="bi bi-trash" aria-hidden="true"></i><span class="d-none d-lg-inline"> Eliminar</span></button></td>
          </tr>`);
     actualizarEstadoPartidas();
     var fila = contenedorPartidas.querySelector('[data-partida="' + indice + '"]');
     fila.querySelector('.partida-cantidad').value = datos.cantidad || '';
     var selectUnidad = fila.querySelector('.partida-unidad');
     if (datos.unidad && !Array.from(selectUnidad.options).some(function (opcion) { return opcion.value === datos.unidad; })) {
          selectUnidad.add(new Option(datos.unidad, datos.unidad));
     }
     selectUnidad.value = datos.unidad || '';
     fila.querySelector('.partida-descripcion').value = datos.descripcion || '';
     fila.querySelector('.partida-valor').value = datos.valor_unitario || '';
     if (enfocar) {
          document.getElementById('partida_cantidad_' + indice).focus();
     }
}

// Convierte notas históricas conocidas y conserva el resto como una sola nota adicional.
function cargarNotas(notas) {
     var notasPersonalizadas = [];
     var notasPagoAnteriores = {
          'Pago anticipado mediante transferencia bancaria.': {tipo: 'anticipado'},
          'Pago anticipado 60% mediante transferencia bancaria.': {tipo: 'anticipo_saldo', porcentaje: 60}
     };
     (notas || []).forEach(function (nota) {
          var pagoAnterior = notasPagoAnteriores[nota.texto];
          if (pagoAnterior) {
               if (!selectCondicionPago.value) {
                    selectCondicionPago.value = pagoAnterior.tipo;
                    if (pagoAnterior.porcentaje) {
                         inputPagoAnticipo.value = pagoAnterior.porcentaje;
                    }
               }
               return;
          }
          if (!selectGarantiaTipo.value && nota.texto === 'Garantía de 6 meses por defectos de fabricación.') {
               selectGarantiaTipo.value = 'producto';
               inputGarantiaVigencia.value = 6;
               selectGarantiaUnidad.value = 'meses';
               return;
          }
          if (!selectGarantiaTipo.value && nota.texto === 'Garantía de 5 días en mano de obra.') {
               selectGarantiaTipo.value = 'mano_obra';
               inputGarantiaVigencia.value = 5;
               selectGarantiaUnidad.value = 'dias';
               return;
          }
          if (!selectCostosEnvio.value && nota.texto === 'No incluye costos de envío.') {
               selectCostosEnvio.value = 'no_incluye';
               return;
          }
          if (nota.texto) {
               notasPersonalizadas.push(nota.texto);
          }
     });
     document.getElementById('notas7').value = notasPersonalizadas.join(' ').slice(0, 255);
     actualizarCondicionPago();
     actualizarGarantia();
}

// Recupera la cotización y precarga todos sus datos estructurados para edición.
async function cargarCotizacionEdicion() {
     try {
          var response = await fetch('../backend/cotizaciones/query_cotizacion.php?id=' + encodeURIComponent(cotizacionEditarId));
          var resultado = await response.json();
          if (!response.ok || resultado.error) {
               throw new Error(resultado.error || 'No fue posible cargar la cotización.');
          }
          var cotizacion = resultado.cotizacion;
          inputCotizacionId.value = cotizacion.id_coti;
          document.getElementById('oportunidad_id').value = cotizacion.oportunidad_id || '';
          inputNumCotizacion.value = cotizacion.cot_numero || '';
          document.getElementById('coti_fecha').value = cotizacion.cot_fecha || '';
          document.getElementById('coti_vigencia').value = cotizacion.cot_vigencia || '';
          if (selectCotizacionEstatus) {
               selectCotizacionEstatus.value = window.CotStatus
                    ? window.CotStatus.key(cotizacion.cot_status)
                    : (cotizacion.cot_status || 'preparacion');
          }

          if (cotizacion.contacto_id) {
               selectNombContacto.value = String(cotizacion.contacto_id);
               actualizarSelectContactoVisual();
               await selectContacto();
          } else {
               selectNombContacto.value = '';
               actualizarSelectContactoVisual();
               cargarSelectorDirecciones();
          }

          inputEmpresaId.value = cotizacion.empresa_id || '';
          inputContactoId.value = cotizacion.contacto_id || '';
          inputDireccionId.value = cotizacion.direccion_id || '';
          inputNombreEmpresa.value = cotizacion.cot_empresa || '';
          inputNombreContacto.value = cotizacion.cot_contacto || '';
          inputCorreoContacto.value = cotizacion.cot_correo || '';
          inputCelContacto.value = cotizacion.cot_telefono || '';
          inputDeptoContacto.value = cotizacion.cot_departamento || '';
          inputDirEmpresa.value = cotizacion.cot_direccion || '';

          if (cotizacion.direccion_id) {
               var valorDireccion = 'nueva:' + cotizacion.direccion_id;
               if (Array.from(selectDireccion.options).some(function (opcion) { return opcion.value === valorDireccion; })) {
                    selectDireccion.value = valorDireccion;
               }
          }

          contenedorPartidas.innerHTML = '';
          (cotizacion.partidas || []).forEach(function (partida) {
               agregarPartida(partida, false);
          });
          actualizarEstadoPartidas();

          selectTiempoEntrega.value = cotizacion.cot_tiempo_entrega_tipo || '';
          actualizarTiempoEntrega();
          if (cotizacion.cot_tiempo_entrega_tipo !== 'inmediato') {
               inputTiempoCantidad.value = cotizacion.cot_tiempo_entrega_cantidad || '';
          }
          selectCondicionEntrega.value = cotizacion.cot_condicion_entrega || '';
          selectCondicionPago.value = cotizacion.cot_condicion_pago_tipo || '';
          if (cotizacion.cot_pago_anticipo_porcentaje !== null) {
               inputPagoAnticipo.value = Number(cotizacion.cot_pago_anticipo_porcentaje);
          }
          if (cotizacion.cot_pago_credito_dias !== null) {
               inputPagoCreditoDias.value = cotizacion.cot_pago_credito_dias;
          }
          selectPagoSaldoMomento.value = cotizacion.cot_pago_saldo_momento || 'al_finalizar';
          selectGarantiaTipo.value = cotizacion.cot_garantia_tipo || '';
          if (cotizacion.cot_garantia_vigencia !== null) {
               inputGarantiaVigencia.value = cotizacion.cot_garantia_vigencia;
          }
          selectGarantiaUnidad.value = cotizacion.cot_garantia_unidad || 'dias';
          selectCostosEnvio.value = cotizacion.cot_costos_envio || '';
          actualizarCondicionPago();
          actualizarGarantia();
          cargarNotas(cotizacion.notas);
          totalizar();
          formularioModificado = false;
     } catch (error) {
          alert(error.message);
          window.location.href = 'tablaCotizaciones.php';
     }
}

async function inicializarFormulario() {
     totalizar();
     actualizarEstadoPartidas();
     actualizarTiempoEntrega();
     actualizarCondicionPago();
     actualizarGarantia();
     if (cotizacionEditarId) {
          await cargarCotizacionEdicion();
          return;
     }
     await precargarOportunidad();
}

// Recalcula cada importe y el resumen visual; el servidor confirma los montos al guardar.
function totalizar() {
     var subtotal = 0;
     contenedorPartidas.querySelectorAll('.partida-cotizacion').forEach(function (fila) {
          var cantidad = Number(fila.querySelector('.partida-cantidad').value || 0);
          var valor = Number(fila.querySelector('.partida-valor').value || 0);
          var importe = cantidad * valor;
          fila.querySelector('.partida-total').value = importe.toFixed(2);
          subtotal += importe;
     });
     var iva = subtotal * 0.16;
     inputSubtotal.value = subtotal.toFixed(2);
     inputIva.value = iva.toFixed(2);
     inputTotal.value = (subtotal + iva).toFixed(2);
}

btnAgregarPartida.addEventListener('click', agregarPartida);
selectTiempoEntrega.addEventListener('change', actualizarTiempoEntrega);
selectCondicionPago.addEventListener('change', actualizarCondicionPago);
inputPagoAnticipo.addEventListener('input', actualizarCondicionPago);
selectPagoSaldoMomento.addEventListener('change', actualizarCondicionPago);
selectGarantiaTipo.addEventListener('change', actualizarGarantia);
contenedorPartidas.addEventListener('input', function (event) {
     if (event.target.matches('.partida-cantidad, .partida-valor')) {
          totalizar();
     }
});
contenedorPartidas.addEventListener('click', function (event) {
     var boton = event.target.closest('.btn-eliminar-partida');
     if (!boton) {
          return;
     }
     boton.closest('.partida-cotizacion').remove();
     actualizarEstadoPartidas();
     totalizar();
});

formulario.addEventListener('submit', async function (event) {
     event.preventDefault();
     if (btnGuardar.disabled) {
          return;
     }
     if (!formulario.reportValidity()) {
          return;
     }
     if (!contenedorPartidas.querySelector('.partida-cotizacion')) {
          alert('Agrega al menos una partida a la cotización.');
          btnAgregarPartida.focus();
          return;
     }
     var mensajeConfirmacion = cotizacionEditarId
          ? 'Se guardarán los cambios y se abrirá el archivo PDF actualizado. ¿Deseas continuar?'
          : 'Se guardará la cotización y se abrirá su archivo PDF. ¿Deseas continuar?';
     if (!confirm(mensajeConfirmacion)) {
          return;
     }

     var ventanaPdf = window.open('', 'cotizacion_pdf');
     btnGuardar.disabled = true;
     totalizar();
     try {
          var response = await fetch('../backend/cotizaciones/add_cotizacion.php', {
               method: 'POST',
               body: new FormData(formulario)
          });
          var resultado = await response.json();
          if (!response.ok || resultado.error) {
               throw new Error(resultado.error || 'No fue posible guardar la cotización.');
          }
          inputNumCotizacion.value = resultado.numero;
          inputCotizacionId.value = resultado.id;
          inputSubtotal.value = resultado.subtotal;
          inputIva.value = resultado.iva;
          inputTotal.value = resultado.total;
          guardando = true;
          formularioModificado = false;
          HTMLFormElement.prototype.submit.call(formulario);
          if (ventanaPdf) {
               ventanaPdf.blur();
          }
          window.focus();
          window.setTimeout(function () {
               window.focus();
               window.location.assign(paginaRegreso);
          }, 150);
     } catch (error) {
          if (ventanaPdf) {
               ventanaPdf.close();
          }
          alert(error.message);
          btnGuardar.disabled = false;
     }
});

inicializarFormulario();
