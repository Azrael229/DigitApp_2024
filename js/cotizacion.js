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
var selectEmpresaCotizacion = document.getElementById('select_empresa_cot');
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
var selectOportunidadCotizacion = document.getElementById('select_oportunidad_cot');
var textoOportunidadEncabezado = document.getElementById('cot-header-opportunity');
var textoCotizacionOrigen = document.getElementById('cot-header-source');
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
var direccionesEmpresa = [];
var contactosEmpresa = [];
var consecutivoPartida = 0;
var guardando = false;
var formularioModificado = false;
var cotizacionEditarId = formulario.dataset.cotizacionId;
var cotizacionDuplicarId = formulario.dataset.duplicateId;
var paginaRegreso = formulario.dataset.returnUrl || 'tablaCotizaciones.php';
var oportunidadesCotizacion = [];

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
     direccionesEmpresa.forEach(function (direccion) {
          var etiqueta = direccion.tipo_direccion === 'fiscal' ? 'Dirección fiscal' : 'Dirección de entrega';
          if (direccion.alias) {
               etiqueta += ' - ' + direccion.alias;
          }
          selectDireccion.add(new Option(etiqueta, String(direccion.id)));
     });
     if (!selectDireccion.options.length) {
          selectDireccion.add(new Option('Sin direcciones registradas', ''));
     }
     selectDireccion.disabled = direccionesEmpresa.length === 0;
     actualizarDireccionSeleccionada();
}

// Mantiene sincronizados el texto de dirección y su identificador normalizado.
function actualizarDireccionSeleccionada() {
     var direccionId = selectDireccion.value;
     var direccion = direccionesEmpresa.find(function (item) {
          return String(item.id) === direccionId;
     });
     inputDireccionId.value = direccion ? direccionId : '';
     inputDirEmpresa.value = direccion ? formatearDireccion(direccion) : '';
}

// Refleja exclusivamente los datos vigentes del contacto maestro seleccionado.
function selectContacto() {
     var contacto = contactosEmpresa.find(function (item) {
          return String(item.id) === selectNombContacto.value;
     });
     inputContactoId.value = contacto ? contacto.id : '';
     inputNombreContacto.value = contacto ? contacto.nombre || '' : '';
     inputCorreoContacto.value = contacto ? contacto.correo || '' : '';
     inputCelContacto.value = contacto ? contacto.celular || '' : '';
     inputDeptoContacto.value = contacto ? contacto.departamento || '' : '';
}

// Consulta los contactos y direcciones vigentes de la empresa maestra seleccionada.
async function cargarEmpresaCotizacion(contactoSeleccionado, direccionSeleccionada) {
     var id = selectEmpresaCotizacion.value;
     inputEmpresaId.value = id;
     inputNombreEmpresa.value = id ? selectEmpresaCotizacion.options[selectEmpresaCotizacion.selectedIndex].textContent.trim() : '';
     if (!id) {
          inputContactoId.value = '';
          contactosEmpresa = [];
          direccionesEmpresa = [];
          selectNombContacto.innerHTML = '<option value="">Selecciona primero una empresa</option>';
          selectNombContacto.disabled = true;
          cargarSelectorDirecciones();
          selectContacto();
          actualizarSelectContactoVisual();
          return;
     }
     try {
          var response = await fetch('../backend/oportunidades/api.php?action=options&empresa_id=' + encodeURIComponent(id));
          var data = await response.json();
          if (!response.ok || data.error) {
               throw new Error(data.error || 'No fue posible cargar los datos vigentes de la empresa.');
          }
          contactosEmpresa = data.contacts || [];
          direccionesEmpresa = data.addresses || [];
          selectNombContacto.innerHTML = '<option value="">Seleccionar contacto</option>';
          contactosEmpresa.forEach(function (contacto) {
               selectNombContacto.add(new Option(contacto.nombre, contacto.id));
          });
          selectNombContacto.disabled = contactosEmpresa.length === 0;
          var contacto = contactosEmpresa.find(function (item) { return String(item.id) === String(contactoSeleccionado || ''); })
               || contactosEmpresa.find(function (item) { return Number(item.es_principal) === 1; }) || contactosEmpresa[0];
          selectNombContacto.value = contacto ? String(contacto.id) : '';
          actualizarSelectContactoVisual();
          selectContacto();
          cargarSelectorDirecciones();
          var direccion = direccionesEmpresa.find(function (item) { return String(item.id) === String(direccionSeleccionada || ''); })
               || direccionesEmpresa.find(function (item) { return Number(item.es_principal) === 1; }) || direccionesEmpresa[0];
          selectDireccion.value = direccion ? String(direccion.id) : '';
          actualizarDireccionSeleccionada();
     } catch (error) {
          contactosEmpresa = [];
          direccionesEmpresa = [];
          cargarSelectorDirecciones();
          alert(error.message);
     }
}

selectDireccion.addEventListener('change', actualizarDireccionSeleccionada);
selectNombContacto.addEventListener('change', selectContacto);
selectEmpresaCotizacion.addEventListener('change', function () { cargarEmpresaCotizacion(); });

// Construye la etiqueta legible de una oportunidad dentro del selector principal.
function etiquetaOportunidadCotizacion(oportunidad) {
     var estatus = String(oportunidad.estatus || '').replaceAll('_', ' ');
     return [oportunidad.numero_oportunidad || '#' + oportunidad.id, oportunidad.empresa, estatus]
          .filter(Boolean).join(' · ');
}

// Carga todas las oportunidades desde la más reciente y conserva la selección solicitada.
async function cargarOportunidadesCotizacion(seleccionada) {
     var response = await fetch('../backend/oportunidades/api.php?action=list');
     var data = await response.json();
     if (!response.ok || data.error) {
          throw new Error(data.error || 'No fue posible cargar las oportunidades comerciales.');
     }
     oportunidadesCotizacion = data.data || [];
     selectOportunidadCotizacion.innerHTML = '<option value="">Sin oportunidad comercial</option>';
     oportunidadesCotizacion.forEach(function (oportunidad) {
          selectOportunidadCotizacion.add(new Option(etiquetaOportunidadCotizacion(oportunidad), oportunidad.id));
     });
     selectOportunidadCotizacion.value = seleccionada ? String(seleccionada) : '';
}

// Actualiza la empresa y reinicia contacto y dirección conforme a la oportunidad elegida.
async function aplicarOportunidadCotizacion(oportunidadId, contactoSeleccionado, direccionSeleccionada) {
     if (!oportunidadId) {
          document.getElementById('oportunidad_id').value = '';
          textoOportunidadEncabezado.textContent = 'Sin seleccionar';
          selectEmpresaCotizacion.disabled = false;
          selectEmpresaCotizacion.value = '';
          await cargarEmpresaCotizacion();
          return null;
     }
     try {
          var oportunidadRespuesta = await fetch('../backend/oportunidades/api.php?action=get&id=' + encodeURIComponent(oportunidadId));
          var oportunidadData = await oportunidadRespuesta.json();
          if (!oportunidadRespuesta.ok || oportunidadData.error) {
               throw new Error(oportunidadData.error || 'No fue posible cargar la oportunidad.');
          }
          var oportunidad = oportunidadData.opportunity;
          document.getElementById('oportunidad_id').value = oportunidad.id;
          selectOportunidadCotizacion.value = String(oportunidad.id);
          textoOportunidadEncabezado.textContent = oportunidad.numero_oportunidad || '#' + oportunidad.id;
          selectEmpresaCotizacion.value = String(oportunidad.empresa_id);
          selectEmpresaCotizacion.disabled = true;
          await cargarEmpresaCotizacion(
               contactoSeleccionado || oportunidad.contacto_id,
               direccionSeleccionada || oportunidad.direccion_id
          );
          return oportunidad;
     } catch (error) {
          alert(error.message);
          return null;
     }
}

selectOportunidadCotizacion.addEventListener('change', function () {
     aplicarOportunidadCotizacion(selectOportunidadCotizacion.value);
});

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
          contenedorPartidas.insertAdjacentHTML('beforeend', '<tr id="partidas_vacias"><td colspan="6" class="text-center contactos-muted py-4">Aún no hay partidas. Utiliza “Agregar partida” para comenzar.</td></tr>');
     } else if (hayPartidas && aviso) {
          aviso.remove();
     }
}

// Mantiene opcional el segundo factor y exige cantidad y unidad cuando se utiliza.
function actualizarPartidaSecundaria(fila) {
     var cantidadSecundaria = fila.querySelector('.partida-cantidad-secundaria');
     var unidadSecundaria = fila.querySelector('.partida-unidad-secundaria');
     var seUtiliza = cantidadSecundaria.value.trim() !== '' || unidadSecundaria.value !== '';
     cantidadSecundaria.required = seUtiliza;
     unidadSecundaria.required = seUtiliza;
}

// Presenta el subtotal calculado de la partida como moneda mexicana de solo lectura.
function formatearSubtotalPartida(importe) {
     return importe.toLocaleString('es-MX', {
          style: 'currency',
          currency: 'MXN',
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
     });
}

// Agrega una fila de captura independiente y sin límite de longitud en la descripción.
function agregarPartida(datos, enfocar) {
     datos = datos || {};
     enfocar = enfocar !== false;
     consecutivoPartida += 1;
     var indice = consecutivoPartida;
     contenedorPartidas.insertAdjacentHTML('beforeend', `
          <tr class="partida-cotizacion" data-partida="${indice}">
               <td class="cotizacion-partida-factor"><label class="visually-hidden" for="partida_cantidad_${indice}">Cantidad principal</label><input class="form-control partida-cantidad" type="number" min="0.001" step="0.001" name="partida_cantidad[]" id="partida_cantidad_${indice}" required><label class="form-label cotizacion-partida-secondary-label" for="partida_cantidad_secundaria_${indice}">Cantidad adicional</label><input class="form-control partida-cantidad-secundaria" type="number" min="0.001" step="0.001" name="partida_cantidad_secundaria[]" id="partida_cantidad_secundaria_${indice}" placeholder="Opcional"></td>
               <td class="cotizacion-partida-factor"><label class="visually-hidden" for="partida_unidad_${indice}">Unidad principal</label><select class="form-select partida-unidad" name="partida_unidad[]" id="partida_unidad_${indice}" required><option value="">Selecciona</option><option value="Servicio">Servicio</option><option value="Equipo">Equipo</option><option value="Pieza">Pieza</option></select><label class="form-label cotizacion-partida-secondary-label" for="partida_unidad_secundaria_${indice}">Unidad adicional</label><select class="form-select partida-unidad-secundaria" name="partida_unidad_secundaria[]" id="partida_unidad_secundaria_${indice}"><option value="">No aplica</option><option value="Servicio">Servicio</option><option value="Equipo">Equipo</option><option value="Pieza">Pieza</option></select></td>
               <td><label class="visually-hidden" for="partida_descripcion_${indice}">Descripción</label><textarea class="form-control partida-descripcion" name="partida_descripcion[]" id="partida_descripcion_${indice}" rows="3" placeholder="Descripción del servicio o equipo" required></textarea></td>
               <td><label class="visually-hidden" for="partida_valor_${indice}">Valor unitario</label><input class="form-control partida-valor" type="number" min="0" step="0.01" name="partida_valor_unitario[]" id="partida_valor_${indice}" required></td>
               <td><label class="visually-hidden" for="partida_subtotal_${indice}">Subtotal calculado</label><input class="form-control partida-subtotal text-end" type="text" id="partida_subtotal_${indice}" value="$0.00" readonly aria-readonly="true" tabindex="-1"><input class="partida-total" type="hidden" name="partida_total[]" value="0.00"></td>
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
     fila.querySelector('.partida-cantidad-secundaria').value = datos.unidad_secundaria ? (datos.cantidad_secundaria || '') : '';
     var selectUnidadSecundaria = fila.querySelector('.partida-unidad-secundaria');
     if (datos.unidad_secundaria && !Array.from(selectUnidadSecundaria.options).some(function (opcion) { return opcion.value === datos.unidad_secundaria; })) {
          selectUnidadSecundaria.add(new Option(datos.unidad_secundaria, datos.unidad_secundaria));
     }
     selectUnidadSecundaria.value = datos.unidad_secundaria || '';
     fila.querySelector('.partida-descripcion').value = datos.descripcion || '';
     fila.querySelector('.partida-valor').value = datos.valor_unitario || '';
     actualizarPartidaSecundaria(fila);
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

// Copia partidas y condiciones comerciales comunes a edición y duplicación.
function cargarContenidoCotizacion(cotizacion) {
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
     inputPagoAnticipo.value = cotizacion.cot_pago_anticipo_porcentaje !== null
          ? Number(cotizacion.cot_pago_anticipo_porcentaje) : '';
     inputPagoCreditoDias.value = cotizacion.cot_pago_credito_dias !== null
          ? cotizacion.cot_pago_credito_dias : '';
     selectPagoSaldoMomento.value = cotizacion.cot_pago_saldo_momento || 'al_finalizar';
     selectGarantiaTipo.value = cotizacion.cot_garantia_tipo || '';
     inputGarantiaVigencia.value = cotizacion.cot_garantia_vigencia !== null
          ? cotizacion.cot_garantia_vigencia : '';
     selectGarantiaUnidad.value = cotizacion.cot_garantia_unidad || 'dias';
     selectCostosEnvio.value = cotizacion.cot_costos_envio || '';
     actualizarCondicionPago();
     actualizarGarantia();
     cargarNotas(cotizacion.notas);
     totalizar();
}

// Devuelve la fecha local actual sin conversiones de zona horaria.
function fechaLocalCotizacion() {
     var ahora = new Date();
     var local = new Date(ahora.getTime() - ahora.getTimezoneOffset() * 60000);
     return local.toISOString().slice(0, 10);
}

// Conserva el mismo periodo de vigencia al crear una nueva cotización duplicada.
function fechasCotizacionDuplicada(fechaOriginal, vigenciaOriginal) {
     var fecha = fechaLocalCotizacion();
     var inicioOriginal = new Date(fechaOriginal + 'T00:00:00');
     var finOriginal = new Date(vigenciaOriginal + 'T00:00:00');
     var dias = Number.isNaN(inicioOriginal.getTime()) || Number.isNaN(finOriginal.getTime())
          ? 0 : Math.max(0, Math.round((finOriginal - inicioOriginal) / 86400000));
     var vigencia = new Date(fecha + 'T00:00:00');
     vigencia.setDate(vigencia.getDate() + dias);
     var vigenciaLocal = new Date(vigencia.getTime() - vigencia.getTimezoneOffset() * 60000);
     return {fecha: fecha, vigencia: vigenciaLocal.toISOString().slice(0, 10)};
}

// Recupera una cotización y la prepara para editarla o generar una copia independiente.
async function cargarCotizacionBase(id, duplicar) {
     try {
          var response = await fetch('../backend/cotizaciones/query_cotizacion.php?id=' + encodeURIComponent(id));
          var resultado = await response.json();
          if (!response.ok || resultado.error) {
               throw new Error(resultado.error || 'No fue posible cargar la cotización.');
          }
          var cotizacion = resultado.cotizacion;
          await cargarOportunidadesCotizacion(cotizacion.oportunidad_id);
          if (duplicar) {
               var fechas = fechasCotizacionDuplicada(cotizacion.cot_fecha || '', cotizacion.cot_vigencia || '');
               inputCotizacionId.value = '';
               document.getElementById('cotizacion_version').value = '';
               inputNumCotizacion.value = '';
               document.getElementById('coti_fecha').value = fechas.fecha;
               document.getElementById('coti_vigencia').value = fechas.vigencia;
               if (textoCotizacionOrigen) {
                    textoCotizacionOrigen.textContent = cotizacion.cot_numero || '#' + cotizacion.id_coti;
               }
          } else {
               inputCotizacionId.value = cotizacion.id_coti;
               document.getElementById('cotizacion_version').value = cotizacion.version || '';
               inputNumCotizacion.value = cotizacion.cot_numero || '';
               document.getElementById('coti_fecha').value = cotizacion.cot_fecha || '';
               document.getElementById('coti_vigencia').value = cotizacion.cot_vigencia || '';
          }
          if (!duplicar && selectCotizacionEstatus) {
               selectCotizacionEstatus.value = window.CotStatus
                    ? window.CotStatus.key(cotizacion.cot_status)
                    : (cotizacion.cot_status || 'preparacion');
          }

          if (cotizacion.oportunidad_id) {
               await aplicarOportunidadCotizacion(
                    cotizacion.oportunidad_id,
                    cotizacion.contacto_id,
                    cotizacion.direccion_id
               );
          } else {
               document.getElementById('oportunidad_id').value = '';
               textoOportunidadEncabezado.textContent = 'Sin seleccionar';
               selectEmpresaCotizacion.disabled = false;
               selectEmpresaCotizacion.value = String(cotizacion.empresa_id || '');
               await cargarEmpresaCotizacion(cotizacion.contacto_id, cotizacion.direccion_id);
          }
          if (!duplicar && cotizacion.orden_venta_id) {
               selectOportunidadCotizacion.disabled = true;
               document.getElementById('cot-oportunidad-ayuda').textContent = 'La oportunidad no puede cambiarse porque la cotización ya tiene una orden de venta.';
          }
          cargarContenidoCotizacion(cotizacion);
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
          await cargarCotizacionBase(cotizacionEditarId, false);
          return;
     }
     if (cotizacionDuplicarId) {
          await cargarCotizacionBase(cotizacionDuplicarId, true);
          return;
     }
     try {
          await cargarOportunidadesCotizacion(formulario.dataset.oportunidadId);
          if (formulario.dataset.oportunidadId) {
               await aplicarOportunidadCotizacion(formulario.dataset.oportunidadId);
          } else {
               selectEmpresaCotizacion.disabled = false;
               cargarSelectorDirecciones();
          }
          formularioModificado = false;
     } catch (error) {
          alert(error.message);
     }
}

// Recalcula cada importe y el resumen visual; el servidor confirma los montos al guardar.
function totalizar() {
     var subtotal = 0;
     contenedorPartidas.querySelectorAll('.partida-cotizacion').forEach(function (fila) {
          var cantidad = Number(fila.querySelector('.partida-cantidad').value || 0);
          var cantidadSecundariaEntrada = fila.querySelector('.partida-cantidad-secundaria').value;
          var cantidadSecundaria = cantidadSecundariaEntrada === '' ? 1 : Number(cantidadSecundariaEntrada);
          var valor = Number(fila.querySelector('.partida-valor').value || 0);
          var importe = cantidad * cantidadSecundaria * valor;
          fila.querySelector('.partida-total').value = importe.toFixed(2);
          fila.querySelector('.partida-subtotal').value = formatearSubtotalPartida(importe);
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
     if (event.target.matches('.partida-cantidad, .partida-cantidad-secundaria, .partida-valor')) {
          actualizarPartidaSecundaria(event.target.closest('.partida-cotizacion'));
          totalizar();
     }
});
contenedorPartidas.addEventListener('change', function (event) {
     if (event.target.matches('.partida-unidad-secundaria')) {
          actualizarPartidaSecundaria(event.target.closest('.partida-cotizacion'));
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
     contenedorPartidas.querySelectorAll('.partida-cotizacion').forEach(actualizarPartidaSecundaria);
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
