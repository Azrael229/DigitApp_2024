# Órdenes de venta

El módulo formaliza el paso entre la venta y la operación en DigitApp 2024. Una cotización **Aceptada** marca automáticamente su oportunidad relacionada como **Ganada** y puede originar una orden de venta.

La orden de venta es un registro maestro interno y no se imprime. En el siguiente nivel podrá alojar varias órdenes operativas de dos clases:

- Orden de servicio, con tipos como calibración, diagnóstico, mantenimiento preventivo, mantenimiento correctivo, inspección, ajuste, recolección, recepción o entrega de equipo del cliente.
- Orden de suministro, exclusivamente para la venta o entrega de equipo nuevo, básculas, indicadores, celdas, cables, conectores, convertidores y refacciones.

## Folio

El formato es `OV-AAAA-NNNNN`. La secuencia se controla por año en `control_folios_comerciales`, inicia en `01001`, siempre aumenta y utiliza saltos de 2, 3, 5, 10 u 11. El identificador interno permanece independiente.

## Estados

- Pendiente de planificación.
- En planificación.
- En ejecución.
- Parcialmente atendida.
- Completada.
- Cancelada.

## Instalación local

1. Respaldar `servico1_digitapp2024`.
2. Elegir una sola ruta:
   - Instalación sin el piloto anterior: aplicar `database/migrations/20261004_ordenes_venta.sql`.
   - Base que todavía contiene las tablas `ordenes_servicio`: aplicar `database/migrations/20261004_02_ordenes_venta.sql`.
3. Incorporar conjuntamente el código de esta versión.
4. Aplicar `database/migrations/20261004_03_ordenes_servicio_hijas.sql` para habilitar las órdenes de servicio derivadas.
5. Validar la creación desde una oportunidad Ganada que tenga una cotización Aceptada.

La migración local no es un archivo de importación para HostGator. El archivo de producción se prepara únicamente cuando el usuario lo solicite y después de comparar el esquema real del servidor.

## Instalación en HostGator

El archivo `database/migrations/20261004_07_hostgator_ordenes_venta_servicio.sql` fue preparado contra la estructura real de producción en MySQL 5.7.44-48. Se importa manualmente desde phpMyAdmin y fija explícitamente la base `servico1_digitapp2024`. Crea únicamente las seis tablas faltantes del módulo y completa la tabla nueva de contactos por dirección mediante `INSERT IGNORE`; no elimina, reemplaza ni actualiza registros existentes. La paridad requerida entre XAMPP y HostGator es de tablas, columnas, índices y relaciones, no de contenido: nunca se copian los registros locales a producción. Su comprobación final usa nombres totalmente calificados para que una consulta a `information_schema` no cambie el contexto de la consulta siguiente.

## Funcionamiento

- Una cotización solo puede originar una orden de venta.
- Una oportunidad puede originar varias órdenes de venta cuando tiene distintas cotizaciones aceptadas.
- Desde el detalle de la cotización se puede cambiar su estatus y abrir la orden de venta enviando explícitamente `oportunidad_id` y `cotizacion_id`; el formulario selecciona esa pareja y precarga cliente, contacto, dirección, importe y descripciones del proyecto.
- El formulario directo selecciona primero la oportunidad y después una cotización relacionada.
- Los selectores buscables del formulario muestran exclusivamente oportunidades con estatus `ganada` y cotizaciones relacionadas con estatus `aceptada`; los demás registros no aparecen como opciones.
- Los eventos de Select2 sincronizan ambos controles y disparan la precarga de cliente, contacto y proyecto al elegir la cotización.
- El formulario configura únicamente fecha de generación, estatus y notas internas; la empresa se presenta como título principal.
- La sección de seguimiento es informativa y muestra la fecha de generación y las actualizaciones del registro.
- El detalle presenta primero los números de control, después el cliente y las descripciones breve y larga del proyecto; el importe y el alcance contratado no se muestran en esta pantalla.
- Una orden de venta puede alojar varias órdenes de servicio. La tabla de órdenes operativas muestra folio, fecha, tipo, estatus y última actualización.
- El botón **Nueva orden de servicio** abre un formulario precargado con la orden de venta, la empresa, la oportunidad, la cotización y las descripciones del proyecto.
- El folio de servicio usa el formato `OS-AAAA-NNNNN`, inicia en `12781` y aplica saltos irregulares crecientes de 2, 3, 5, 10 u 11 para no revelar el volumen real de trabajos.
- Los tipos iniciales son calibración, entrega o recolección de equipo del cliente, recepción, mantenimiento preventivo, ajuste, inspección, diagnóstico y mantenimiento correctivo.
- El formulario separa los datos básicos de la orden, los datos del cliente, las instrucciones y los equipos.
- Los datos del cliente se presentan en dos columnas: información fiscal tomada del expediente de la empresa y datos seleccionables de entrega o ejecución.
- La dirección de entrega se selecciona primero entre las direcciones registradas para la empresa; después el contacto se limita a las personas asociadas con esa dirección.
- `empresa_direccion_contactos` permite que un contacto se relacione con varias direcciones. `empresa_contactos` mantiene la relación existente de un contacto con varias empresas.
- El formulario de contactos permite seleccionar simultáneamente varias empresas y varias direcciones pertenecientes a esas empresas.
- Cada orden conserva una fotografía de los datos fiscales, de entrega y de los equipos seleccionados para que el documento firmado no cambie si después se actualizan los catálogos.
- La tabla de equipos de la orden replica las columnas del inventario de `Ver empresa`: descripción, ubicación, marca, modelo, identificación, serie, capacidad, divisiones y clase de exactitud.
- El listado general `Órdenes de servicio` reúne las órdenes operativas y mantiene vínculos a la orden de venta y a la empresa.
- El detalle permite editar la orden o generar su PDF institucional para entregar y firmar con el cliente. Las notas internas no se imprimen.
- El PDF muestra únicamente el título y folio de la orden de servicio; no imprime folios de venta, cotización ni oportunidad.
- El PDF se genera en hoja tamaño carta vertical, omite el estatus operativo y presenta los equipos en columnas independientes: descripción, marca, modelo, serie, identificación, ubicación, `Max`, `d`, `e` y clase.
- La tabla de equipos utiliza tipografía reforzada y filas altas para facilitar su lectura y permitir correcciones o anotaciones manuales sobre el formato impreso.
- El tipo de servicio se presenta en una franja destacada y los datos fiscales se separan visualmente de los datos de entrega.
- El PDF crece dinámicamente para admitir 20, 30 o más equipos, repite la cabecera de la tabla en cada página y reserva observaciones y firmas para el final del documento.
- La orden guarda relaciones y una fotografía de empresa, contacto, dirección e importe.
- Los cambios de estatus se registran en seguimiento.
- La edición usa una versión del registro para evitar sobrescrituras silenciosas.
- La orden de venta no genera un documento PDF.

## Archivos principales

- `paginas/ordenes_venta.php`: listado y filtros.
- `paginas/form_orden_venta.php`: creación y edición.
- `paginas/ver_orden_venta.php`: detalle y seguimiento.
- `paginas/form_orden_servicio.php`: creación de una orden de servicio derivada.
- `paginas/ordenes_servicio.php`: listado general de órdenes de servicio.
- `paginas/ver_orden_servicio.php`: detalle, edición e impresión.
- `backend/ordenes_venta/api.php`: consultas y escrituras validadas.
- `backend/ordenes_servicio/api.php`: precarga y creación de órdenes de servicio.
- `fpdf/ordenServicioPDF.php`: documento PDF de la orden de servicio y espacios de firma.
- `backend/cotizaciones/update_coti_status.php`: cambio directo y protegido del estatus.
