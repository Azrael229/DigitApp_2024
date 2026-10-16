# Catálogo comercial

## Alcance

El módulo administra productos y servicios en directorios separados, con un catálogo maestro común para su selección desde cotizaciones.

## Códigos

- Productos: `PRD-{categoría}-{id}`.
- Servicios: `SRV-{categoría}-{id}`.
- El código se asigna al crear el registro y no cambia cuando se edita su categoría o descripción.

## Categorías iniciales

- Productos: Accesorios, Celdas de carga, Indicadores, Impresores, Plataformas y Básculas.
- Servicios: Asesoría, Calibración, Inspección, Mantenimiento preventivo y ajuste, Diagnóstico, Entregas, Recolección, Viáticos y Renta.
- Los formularios permiten agregar categorías sin abandonar la captura.

## Proveedores

Los proveedores se seleccionan exclusivamente desde empresas con rol `Proveedor` o `Cliente-Proveedor`. El módulo no duplica empresas ni crea un directorio paralelo.

## Formación de precios

- Productos: moneda MXN o USD, tipo de cambio manual cuando aplica, costo del proveedor, descuento, costo de envío en MXN, costo real en MXN, utilidad por margen porcentual o cantidad, precio calculado y precio público final editable. El costo real suma la paquetería después de convertir el costo descontado a pesos.
- Servicios propios: suma de mano de obra, consumibles, gastos administrativos y costos operativos adicionales; después se aplica un margen porcentual para obtener el precio base.
- Servicios de terceros: costo externo menos su descuento, más los costos operativos de SERVICOM; posteriormente se aplica el margen porcentual.
- Los viáticos no forman parte del costo operativo del servicio y se cotizan por separado.
- Ambos directorios muestran la fecha de la última actualización del precio en formato `AAAA-MM-DD` y permiten filtrarla con el filtro ordinario de la columna.
- Los directorios siguen el patrón compartido de tablas: filtros por columna, conteo filtrado, descripciones amplias limitadas a dos líneas y desplazamiento horizontal nativo para conservar columnas legibles.
- El directorio de servicios muestra como únicos importes el costo global del servicio, la utilidad, el precio base calculado y el precio al público final, en ese orden. Unidad, origen y proveedor permanecen en el registro y en su detalle, pero no se muestran como columnas del directorio.
- El directorio de productos expresa sus importes en pesos mexicanos y muestra únicamente costo real en MXN, utilidad, precio calculado y precio al público final. La moneda y la unidad permanecen en el registro y en su detalle, pero no se muestran como columnas del directorio.
- El código abre la vista completa del producto o servicio y sustituye la columna de acciones en ambos directorios. El administrador dispone de `Editar` en la vista y todos los usuarios autorizados pueden volver al directorio.

Solo el rol `administrador` puede crear categorías o agregar y editar productos y servicios. Los demás roles autorizados conservan acceso de consulta a los directorios y al selector usado en cotizaciones.

## Cotizaciones

Cada partida puede relacionarse con un producto o servicio mediante su código. Al seleccionarlo se precargan descripción, unidad y precio público, pero estos valores permanecen editables. La partida conserva una fotografía de los valores utilizados para que cambios posteriores del catálogo no alteren cotizaciones históricas.

El descuento por partida admite porcentaje o cantidad monetaria. El servidor recalcula importe bruto, descuento, importe neto, subtotal, IVA y total. La vista y el PDF muestran los descuentos aplicados.

## Inventario futuro

El catálogo no representa existencias físicas. Los productos quedan preparados para integrarse posteriormente con almacenes, códigos de barras, movimientos y reservaciones. Una cotización no descuenta inventario.

## Base de datos local

Las migraciones locales están en `database/migrations/20261015_catalogo_comercial.sql`, `database/migrations/20261015_02_calculo_precios_catalogo.sql` y `database/migrations/20261015_03_costo_envio_productos.sql`. Antes de incorporar el costo de envío se creó el respaldo `database/backups/antes_costo_envio_catalogo_20261015.sql`.

La migración crea `catalogo_categorias_comerciales`, `catalogo_items_comerciales` y `catalogo_item_costos_operacion`, y amplía `cotizacion_partidas` sin eliminar registros existentes. El directorio `database/` es local y está excluido de Git.

La base de producción `servico1_digitapp2024` en HostGator fue homologada el 15 de octubre de 2026 mediante una migración aditiva compatible con Percona Server 5.7.44. Se verificaron las tres tablas, 15 categorías iniciales, siete columnas nuevas en `cotizacion_partidas`, su índice y su relación con el catálogo. Producción conserva sus 160 partidas anteriores y no recibió productos, servicios ni costos operativos de prueba.

El archivo local `database/20261015_catalogo_pruebas.sql` carga cinco productos y cinco servicios marcados con `[PRUEBA]`, sin relacionarlos con proveedores reales. Sirven únicamente para revisar las columnas y pueden identificarse por códigos terminados en `TEST01` a `TEST05`.

## Órdenes de servicio relacionadas

- La vista de una orden permite añadir directamente equipos registrados de la misma empresa.
- El formulario de la orden conserva su función original para administrar equipos.
- La dirección fija `Instalaciones de SERVICOM Básculas Digitales` se guarda como dato operativo de la orden, sin crear una dirección dentro del registro maestro del cliente.
