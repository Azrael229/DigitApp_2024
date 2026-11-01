# Dashboard administrativo

- `paginas/dashboard.php` presenta los indicadores históricos y los gráficos por periodo; su acceso se limita al permiso `dashboard`.
- `backend/dashboard/api.php` agrega oportunidades por `oportunidades_comerciales.fecha` y órdenes completadas por su primer cambio a completada en `orden_venta_seguimiento`. Las órdenes suman `importe_sin_iva`.
- Los gráficos de oportunidades y órdenes son de barras diarias en dos columnas. Un selector de mes y año, dentro de su misma sección, carga automáticamente todos los días del mes elegido, incluidos los que no tienen importe. Se permite seleccionar desde septiembre de 2026; el estatus solo filtra las oportunidades.
- Top 10 clientes presenta nombres en el eje vertical y el tooltip apunta al elemento bajo el cursor. Se retiró la gráfica de clientes y prospectos registrados por mes.
- Los indicadores históricos de ventas netas incluyen CFDI (subtotal) y remisiones (importe sin IVA), conforme a los datos preparados en `dashboardInvoiceData`.
