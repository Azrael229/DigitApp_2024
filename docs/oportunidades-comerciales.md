# Oportunidades comerciales

Primera implementación: listado, formulario de creación/edición y detalle con cotizaciones relacionadas.

## Pantallas

- `paginas/oportunidades.php`: filtros por columna, rango de fechas inclusivo con controles nativos del navegador, paginación, subtotal sin IVA sobre todos los resultados filtrados, empresa/contacto enlazados a sus fichas y edición del importe con botón de guardado.
- `paginas/form_oportunidad.php`: fecha, empresa, contactos y direcciones asociados, descripciones, importe y estatus. Contacto y dirección pueden quedar pendientes.
- `paginas/ver_oportunidad.php`: datos completos, fechas de control y cotizaciones vinculadas con descarga del PDF.

Se reutilizan Bootstrap 5.3.2, DataTables 1.13.7, Select2 y los estilos del directorio. La entrada de menú aparece antes de Cotizaciones.

## Base de datos e instalación

1. Respaldar la base de datos de destino.
2. Aplicar `database/migrations/20261001_oportunidades_comerciales.sql` una vez. Crea dos tablas nuevas y no modifica las filas actuales de clientes o cotizaciones.
3. Incorporar los archivos de esta rama al proyecto original. Conservar su `config/conexion.php` y los archivos locales excluidos de Git.
4. Abrir el menú `OP C`. La migración y el código deben instalarse juntos. Sin migración, la pantalla muestra que el módulo todavía no está habilitado.

La migración corresponde a los tipos de identificadores verificados en MariaDB 10.4.32. Se aplicó a la base original el 1 de octubre de 2026 después de generar un respaldo completo; las tablas quedaron inicialmente vacías.

## Criterios de funcionamiento

- La fecha visible es editable; `created_at` registra automáticamente cuándo se capturó la oportunidad. El orden inicial sigue `created_at` e ID descendentes incluso para proyectos con fecha anterior.
- Los filtros Desde/Hasta permiten consultar uno o varios meses. El subtotal suma todas las páginas filtradas, usando el importe propuesto de cada oportunidad, sin sumar sus cotizaciones.
- Los importes se almacenan como `DECIMAL(12,2)` en MXN, entre cero y 9,999,999,999.99. No incluyen IVA.
- Estatus: En preparación, Cotizada, En negociación, Ganada, Perdida y Cancelada. Los cambios son manuales; vincular una cotización no cambia el estatus automáticamente.
- Contactos filtrados con `empresa_contactos.activo = 1` y `contactos.activo = 1`; direcciones filtradas por empresa. El servidor valida estas relaciones además del formulario.
- Las fechas de creación y última modificación se generan automáticamente. `created_by` y `updated_by` quedan en NULL hasta incorporar el sistema de usuarios; las pantallas lo indican sin atribuir una identidad ficticia.
- Las ediciones comprueban una versión del registro para evitar sobrescribir silenciosamente cambios realizados desde otra pantalla.
- Las escrituras usan POST, token de sesión CSRF, consultas preparadas y transacciones. Este módulo no introduce autenticación; debe quedar detrás del mismo acceso protegido que la aplicación actual.

## Cotizaciones

Una oportunidad admite varias cotizaciones; una cotización se vincula a una sola oportunidad. La vinculación y desvinculación se realizan desde el detalle, sin borrar la cotización ni su PDF.

El detalle sólo ofrece la descarga cuando el archivo PDF existe físicamente; los registros históricos cuyo archivo ya no está disponible se muestran como `Sin PDF`.

El sistema actual registra empresa y contacto como texto en cotizaciones. Por eso el selector ofrece cotizaciones cuyo nombre de empresa coincide con el actual y exige selección explícita. No se asignan automáticamente los registros históricos. Si existen empresas con el mismo nombre o se renombra una empresa, se requiere revisar esa coincidencia antes de vincular; una relación ya establecida permanece por identificador.

El formulario y generador actuales de cotizaciones conservan su funcionamiento. La creación de una cotización nueva **desde** una oportunidad con asociación automática se reserva para la siguiente integración; en esta primera etapa se puede generar por el flujo actual y vincular desde el detalle.

## Validación

Verificación realizada:

- Sintaxis PHP y JavaScript de todos los archivos nuevos; revisión de espacios y conflictos con `git diff --check`.
- Migración ejecutada correctamente en una base aislada con los mismos esquemas de referencia y datos ficticios.
- 64 comprobaciones API: catálogos, creación/consulta/edición, contactos multempresa, direcciones, importes, fechas, estatus, CSRF, edición concurrente, varias cotizaciones, vinculación/desvinculación y orden del listado.
- 19 comprobaciones JavaScript: centavos exactos, importes grandes, rango inclusivo, septiembre/octubre/dos meses, filtro combinado, 26 oportunidades repartidas entre dos páginas según la configuración compartida y direcciones migradas.
- Revisión visual y funcional en navegador sobre una copia aislada de la base real: acceso desde el menú, estilos, DataTables, Select2, creación, consulta, edición, selectores dependientes, edición rápida de importe, subtotal global, paginación con 26 registros, filtros de septiembre y de septiembre-octubre, estado vacío, mensajes de error, vínculos a empresa/contacto/detalle, dos cotizaciones vinculadas y descarga de un PDF existente.
- Se comprobó que desvincular conserva la cotización y que el servidor rechaza un contacto que no pertenece a la empresa con HTTP 422.
- La consola del navegador no presentó errores ni advertencias durante la revisión.

Las pruebas de escritura se realizaron exclusivamente en una base aislada, eliminada al finalizar junto con los datos y archivos temporales creados para la comprobación. No se agregaron oportunidades de prueba a la base original. El módulo y su migración quedaron instalados directamente en el repositorio original de `htdocs`.
