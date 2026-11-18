# Dashboard administrativo

- `paginas/dashboard.php` presenta los indicadores históricos y la actividad de oportunidades; su acceso se limita al permiso `dashboard`.
- `backend/dashboard/api.php` agrupa por día las oportunidades según `created_at` y devuelve el mes calendario anterior y el actual, sin filtros manuales.
- Al final del Dashboard aparecen dos gráficos de barras en una misma fila: cantidad de oportunidades creadas por día durante el mes anterior y el actual. Ambos muestran todos los días calendario, también cuando no hubo registros.
- Debajo de ellos se mantiene el gráfico mensual de barras agrupadas de los últimos 12 meses, con oportunidades por estado: negociación, ganadas y perdidas/canceladas/rechazadas. El tooltip muestra el total de cada mes, incluidos otros estados.
- Inmediatamente después aparece el gráfico global de tres barras: oportunidades en negociación, ganadas y perdidas/canceladas/rechazadas. El título indica el total de oportunidades del proyecto, incluidos los demás estados; debajo se muestra la tasa de bateo (ganadas divididas entre el total). Ambos gráficos se actualizan con el evento de sincronización de la aplicación.
- Top 10 clientes presenta nombres en el eje vertical y el tooltip apunta al elemento bajo el cursor. Se retiró la gráfica de clientes y prospectos registrados por mes.
- Los indicadores históricos de ventas netas incluyen CFDI (subtotal) y remisiones (importe sin IVA), conforme a los datos preparados en `dashboardInvoiceData`.
