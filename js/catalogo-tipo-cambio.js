'use strict';

const exchangeRatePage = document.querySelector('[data-exchange-rate-page]');
const exchangeRateForm = document.getElementById('exchange-rate-form');
const exchangeRateButton = document.getElementById('exchange-rate-apply');

// Presenta una respuesta de estado sin interpretar contenido recibido como HTML.
function exchangeRateMessage(message, success = false) {
    const box = document.getElementById('exchange-rate-message');
    box.className = `alert ${success ? 'alert-success' : 'alert-danger'}`;
    box.textContent = message;
}

// Consulta la tasa vigente, el número de productos USD y la versión de configuración.
async function exchangeRateLoad() {
    const response = await fetch('../backend/catalogo/api.php?action=exchange_rate', {cache: 'no-store'});
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.error) throw new Error(data.error || 'No fue posible consultar la configuración.');

    const setting = data.setting;
    const currentRate = setting.tipo_cambio_usd_mxn;
    document.getElementById('exchange-rate-current').textContent = currentRate === null
        ? 'Sin configurar'
        : Number(currentRate).toLocaleString('es-MX', {minimumFractionDigits: 6, maximumFractionDigits: 6});
    document.getElementById('exchange-rate-updated').textContent = setting.fecha_vigencia || 'Sin actualización';
    document.getElementById('exchange-rate-products').textContent = String(data.product_count);
    document.getElementById('exchange-rate-version').value = setting.version;
    document.getElementById('exchange-rate-product-count').value = data.product_count;
    if (currentRate !== null) document.getElementById('exchange-rate-input').value = currentRate;
    exchangeRateButton.disabled = false;
    exchangeRateMessage('Tasa vigente consultada en la base de datos local.', true);
}

// Guarda la tasa y recalcula los costos USD en una única operación confirmada.
exchangeRateForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (!this.reportValidity()) return;

    const rate = document.getElementById('exchange-rate-input').value;
    const count = Number(document.getElementById('exchange-rate-product-count').value || 0);
    const message = count === 1
        ? 'Se recalculará 1 producto USD. Su precio público manual y las cotizaciones guardadas permanecerán intactos.'
        : `Se recalcularán ${count} productos USD. Sus precios públicos manuales y las cotizaciones guardadas permanecerán intactos.`;
    if (!window.confirm(message + '\n\n¿Deseas continuar?')) return;

    exchangeRateButton.disabled = true;
    exchangeRateMessage('Actualizando la tasa y los costos del catálogo...');
    try {
        const body = new URLSearchParams({
            action: 'apply_exchange_rate',
            csrf: exchangeRatePage.dataset.csrf,
            rate,
            version: document.getElementById('exchange-rate-version').value,
            product_count: String(count)
        });
        const response = await fetch('../backend/catalogo/api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) throw new Error(data.error || 'No fue posible aplicar la tasa.');
        await exchangeRateLoad();
        exchangeRateMessage(`Tasa aplicada. Se recalcularon ${data.product_count} productos USD.`, true);
    } catch (error) {
        exchangeRateMessage(error.message);
        exchangeRateButton.disabled = false;
    }
});

exchangeRateLoad().catch(error => exchangeRateMessage(error.message));
