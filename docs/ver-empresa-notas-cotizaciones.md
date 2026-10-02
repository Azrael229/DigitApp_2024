# Notas e historial comercial en la ficha de empresa

La vista `paginas/ver_empresa.php` permite editar notas operativas y consultar el historial vinculado mediante el identificador de la empresa.

## Base de datos

1. Respaldar la base de datos de destino.
2. Aplicar una vez `database/migrations/20261002_relacionar_cotizaciones_empresas.sql`.
3. Incorporar el código de la misma versión.

Las notas reutilizan `empresas.observaciones`; no requieren una tabla adicional. La migración agrega `empresa_id` y `contacto_id` opcionales a `cotizaciones`, con índices y claves foráneas que usan `ON DELETE SET NULL`.

El proceso histórico relaciona únicamente nombres de empresa únicos y contactos únicos dentro de una empresa ya identificada. Los registros ambiguos permanecen sin relación para evitar asociaciones incorrectas.

## Funcionamiento

- Las notas se guardan de forma explícita, conservan saltos de línea y actualizan `empresas.updated_at`.
- El historial comercial abre por defecto la pestaña de oportunidades y ofrece una segunda pestaña para cotizaciones.
- Las oportunidades muestran fecha, contacto, proyecto, importe sin IVA, estatus y acceso directo a su ficha.
- La tabla de cotizaciones usa paginación, búsqueda y filtros por columna.
- El orden inicial utiliza el ID de cotización descendente para mostrar primero el registro más reciente.
- Los PDF se ofrecen únicamente cuando el nombre almacenado es seguro y el archivo existe.
- La columna de detalle de cotización queda visible pero deshabilitada hasta que exista una pantalla individual de cotización.
- Las cotizaciones nuevas resuelven la empresa y el contacto desde la selección existente, en lugar de confiar solo en los nombres enviados por el formulario.
