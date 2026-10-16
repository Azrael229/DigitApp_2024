'use strict';

const catalogPage = document.querySelector('[data-catalog-type]');
const catalogType = catalogPage.dataset.catalogType;
const catalogId = catalogPage.dataset.catalogId;
const catalogReturn = catalogPage.dataset.catalogReturn;

// Muestra el resultado de las operaciones del formulario de catálogo.
function catalogMessage(text, success = false) {
    const box = document.getElementById('catalog-message');
    box.textContent = text;
    box.className = `alert ${success ? 'alert-success' : 'alert-danger'}`;
}

// Ejecuta las solicitudes del catálogo y conserva la protección CSRF en escrituras.
async function catalogRequest(action, values = {}, write = false) {
    const options = write ? {
        method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
        body: new URLSearchParams({...values, action, csrf: catalogPage.dataset.catalogCsrf})
    } : {};
    const url = '../backend/catalogo/api.php' + (write ? '' : '?' + new URLSearchParams({...values, action}));
    const response = await fetch(url, options);
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.error) { throw new Error(data.error || 'No fue posible completar la operación.'); }
    return data;
}

// Llena un selector conservando una opción inicial y una selección opcional.
function catalogFillSelect(select, items, placeholder, selected = '') {
    select.replaceChildren(new Option(placeholder, ''));
    items.forEach(item => select.add(new Option(item.nombre || item, item.id || item)));
    select.value = String(selected || '');
}

// Formatea importes calculados en pesos mexicanos.
function catalogMoney(value) {
    return Number(value || 0).toLocaleString('es-MX', {style: 'currency', currency: 'MXN'});
}

// Obtiene el costo después del descuento porcentual o monetario configurado.
function catalogDiscountedCost(cost) {
    const discount = Number(document.getElementById('catalog-discount').value || 0);
    const type = document.getElementById('catalog-discount-type').value;
    return Math.max(0, type === 'porcentaje' ? cost * (1 - discount / 100) : cost - discount);
}

// Actualiza todos los importes derivados sin sustituir el precio público capturado.
function catalogUpdatePrices() {
    const utility = Number(document.getElementById('catalog-utility').value || 0);
    const utilityType = document.getElementById('catalog-utility-type').value;
    let realCost = 0;
    if (catalogType === 'producto') {
        const rate = document.getElementById('catalog-currency').value === 'USD'
            ? Number(document.getElementById('catalog-exchange-rate').value || 0) : 1;
        const shippingCost = Number(document.getElementById('catalog-shipping-cost').value || 0);
        realCost = catalogDiscountedCost(Number(document.getElementById('catalog-base-cost').value || 0)) * rate + shippingCost;
        document.getElementById('catalog-real-cost').textContent = catalogMoney(realCost);
    } else {
        const thirdPartyCost = document.getElementById('catalog-third-party').value === '1'
            ? catalogDiscountedCost(Number(document.getElementById('catalog-third-party-cost').value || 0)) : 0;
        const operationTotal = Array.from(document.querySelectorAll('.catalog-operation-amount'))
            .reduce((total, input) => total + Number(input.value || 0), 0);
        document.getElementById('catalog-third-party-real-cost').textContent = catalogMoney(thirdPartyCost);
        document.getElementById('catalog-operation-total').textContent = catalogMoney(operationTotal);
        realCost = thirdPartyCost + operationTotal;
    }
    const calculated = utilityType === 'porcentaje'
        ? (utility >= 100 ? 0 : realCost / (1 - utility / 100)) : realCost + utility;
    document.getElementById('catalog-calculated-price').textContent = catalogMoney(calculated);
}

// Muestra el bloque del tercero y exige proveedor únicamente cuando corresponde.
function catalogUpdateThirdParty() {
    const selector = document.getElementById('catalog-third-party');
    const cost = document.getElementById('catalog-third-party-cost');
    if (!selector || !cost) { return; }
    const isThirdParty = selector.value === '1';
    document.getElementById('catalog-third-party-section').classList.toggle('d-none', !isThirdParty);
    cost.disabled = !isThirdParty;
    cost.required = isThirdParty;
    document.getElementById('catalog-provider').required = isThirdParty;
    catalogUpdatePrices();
}

// Habilita el tipo de cambio exclusivamente cuando el proveedor cotiza en USD.
function catalogUpdateCurrency() {
    const exchange = document.getElementById('catalog-exchange-rate');
    const usesDollars = document.getElementById('catalog-currency').value === 'USD';
    exchange.disabled = !usesDollars;
    exchange.required = usesDollars;
    if (!usesDollars) exchange.value = '';
    catalogUpdatePrices();
}

// Agrega una fila de costo operativo y la integra de inmediato al cálculo.
function catalogAddOperationCost(concept = '', amount = '', fixed = false) {
    const row = document.createElement('div');
    row.className = 'row g-2 catalog-operation-row';
    row.innerHTML = `<div class="col-md-7"><input class="form-control catalog-operation-concept" maxlength="120" placeholder="Concepto" ${fixed ? 'readonly' : ''}></div><div class="col"><input class="form-control catalog-operation-amount" type="number" min="0" step="0.01" placeholder="Importe"></div><div class="col-auto"><button type="button" class="btn btn-danger catalog-operation-remove" ${fixed ? 'disabled' : ''}>Quitar</button></div>`;
    row.querySelector('.catalog-operation-concept').value = concept;
    row.querySelector('.catalog-operation-amount').value = amount;
    row.querySelector('.catalog-operation-amount').addEventListener('input', catalogUpdatePrices);
    row.querySelector('.catalog-operation-remove').addEventListener('click', () => { row.remove(); catalogUpdatePrices(); });
    document.getElementById('catalog-operation-costs').appendChild(row);
}

// Precarga catálogos y valores al crear o editar un producto o servicio.
async function catalogLoadForm() {
    const result = await catalogRequest('form', {type: catalogType, id: catalogId});
    const item = result.item || {};
    catalogFillSelect(document.getElementById('catalog-category'), result.categories || [], 'Selecciona una categoría', item.categoria_id);
    catalogFillSelect(document.getElementById('catalog-unit'), result.units || [], 'Selecciona una unidad', item.unidad);
    catalogFillSelect(document.getElementById('catalog-provider'), result.providers || [], 'Sin proveedor relacionado', item.proveedor_id);
    document.getElementById('catalog-code').value = item.codigo || 'Se asignará al guardar';
    document.getElementById('catalog-version').value = item.version || '';
    document.getElementById('catalog-description').value = item.descripcion || '';
    document.getElementById('catalog-currency').value = item.moneda_costo || 'MXN';
    document.getElementById('catalog-exchange-rate').value = item.tipo_cambio || '';
    document.getElementById('catalog-base-cost').value = item.costo_base || '';
    document.getElementById('catalog-public-price').value = item.precio_publico || '';
    document.getElementById('catalog-discount-type').value = item.descuento_costo_tipo || 'porcentaje';
    document.getElementById('catalog-discount').value = item.descuento_costo || '0';
    document.getElementById('catalog-shipping-cost').value = item.costo_envio || '0';
    document.getElementById('catalog-utility-type').value = item.utilidad_tipo || 'porcentaje';
    document.getElementById('catalog-utility').value = item.utilidad_valor || '0';
    document.getElementById('catalog-active').value = String(item.activo ?? 1);
    document.getElementById('catalog-price-updated').textContent = item.precio_actualizado_at || 'Pendiente de guardado';
    if (catalogType === 'servicio') {
        document.getElementById('catalog-third-party').value = String(item.es_tercero || 0);
        document.getElementById('catalog-third-party-cost').value = item.costo_tercero || '';
        const costs = item.costos_operacion?.length ? item.costos_operacion : [
            {concepto: 'Mano de obra', importe: ''},
            {concepto: 'Consumibles', importe: ''},
            {concepto: 'Gastos administrativos', importe: ''}
        ];
        costs.forEach((cost, index) => catalogAddOperationCost(cost.concepto, cost.importe, !item.costos_operacion?.length || index < 3));
    }
    catalogUpdateThirdParty();
    if (catalogType === 'producto') catalogUpdateCurrency();
    catalogUpdatePrices();
    document.getElementById('catalog-message').classList.add('d-none');
    document.getElementById('catalog-form').classList.remove('d-none');
    document.getElementById('catalog-save').disabled = false;
}

// Crea una categoría desde el mismo formulario y la deja seleccionada.
document.getElementById('catalog-add-category').addEventListener('click', async function () {
    const input = document.getElementById('catalog-new-category');
    if (!input.value.trim()) { input.focus(); return; }
    this.disabled = true;
    try {
        const result = await catalogRequest('category_add', {type: catalogType, name: input.value.trim()}, true);
        const category = result.category;
        const select = document.getElementById('catalog-category');
        let option = Array.from(select.options).find(item => item.value === String(category.id));
        if (!option) { option = new Option(category.nombre, category.id); select.add(option); }
        select.value = String(category.id);
        input.value = '';
        catalogMessage('Categoría disponible y seleccionada.', true);
    } catch (error) {
        catalogMessage(error.message);
    } finally {
        this.disabled = false;
    }
});

['catalog-base-cost', 'catalog-exchange-rate', 'catalog-third-party-cost', 'catalog-discount', 'catalog-shipping-cost', 'catalog-utility']
    .forEach(id => document.getElementById(id)?.addEventListener('input', catalogUpdatePrices));
['catalog-discount-type', 'catalog-utility-type']
    .forEach(id => document.getElementById(id)?.addEventListener('change', catalogUpdatePrices));
document.getElementById('catalog-third-party')?.addEventListener('change', catalogUpdateThirdParty);
document.getElementById('catalog-currency')?.addEventListener('change', catalogUpdateCurrency);
document.getElementById('catalog-add-operation-cost')?.addEventListener('click', () => catalogAddOperationCost());

// Guarda el catálogo y regresa al directorio correspondiente.
document.getElementById('catalog-form').addEventListener('submit', async function (event) {
    event.preventDefault();
    if (!this.reportValidity()) { return; }
    const button = document.getElementById('catalog-save');
    button.disabled = true;
    try {
        await catalogRequest('save', {
            type: catalogType, id: catalogId, version: document.getElementById('catalog-version').value,
            category_id: document.getElementById('catalog-category').value,
            provider_id: document.getElementById('catalog-provider').value,
            description: document.getElementById('catalog-description').value,
            unit: document.getElementById('catalog-unit').value,
            currency: document.getElementById('catalog-currency').value,
            exchange_rate: document.getElementById('catalog-exchange-rate').value,
            base_cost: document.getElementById('catalog-base-cost').value,
            third_party: document.getElementById('catalog-third-party')?.value || '0',
            third_party_cost: document.getElementById('catalog-third-party-cost')?.value || '',
            public_price: document.getElementById('catalog-public-price').value,
            cost_discount_type: document.getElementById('catalog-discount-type').value,
            cost_discount: document.getElementById('catalog-discount').value,
            shipping_cost: document.getElementById('catalog-shipping-cost').value,
            utility_type: document.getElementById('catalog-utility-type').value,
            utility_value: document.getElementById('catalog-utility').value,
            operation_costs: JSON.stringify(Array.from(document.querySelectorAll('.catalog-operation-row')).map(row => ({
                concepto: row.querySelector('.catalog-operation-concept').value,
                importe: row.querySelector('.catalog-operation-amount').value
            }))),
            active: document.getElementById('catalog-active').value
        }, true);
        location.href = catalogReturn;
    } catch (error) {
        catalogMessage(error.message);
        button.disabled = false;
    }
});

catalogLoadForm().catch(error => catalogMessage(error.message));
