'use strict';

// Solicita la confirmación escrita y elimina un registro solo mediante el endpoint administrativo.
document.addEventListener('click', async function (event) {
    const button = event.target.closest('[data-admin-delete]');
    if (!button) { return; }

    const label = button.dataset.deleteLabel || 'este registro';
    const confirmation = window.prompt(`¿Está seguro de eliminar ${label}?\n\nEscriba BORRAR para continuar:`);
    if (confirmation === null) { return; }
    if (confirmation.trim().toLocaleUpperCase('es-MX') !== 'BORRAR') {
        window.alert('La eliminación fue cancelada porque no se escribió BORRAR.');
        return;
    }

    button.disabled = true;
    try {
        const root = document.querySelector('[data-admin-delete-csrf]');
        const response = await fetch('../backend/helpers/admin_delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest'},
            body: new URLSearchParams({
                entidad: button.dataset.deleteEntity || '',
                id: button.dataset.deleteId || '',
                empresa_id: button.dataset.deleteCompanyId || '',
                confirmacion: confirmation.trim(),
                csrf: root?.dataset.adminDeleteCsrf || ''
            })
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) {
            throw new Error(data.error || 'No fue posible eliminar el registro.');
        }
        window.alert(data.message || 'Registro eliminado correctamente.');
        window.location.reload();
    } catch (error) {
        button.disabled = false;
        window.alert(error.message);
    }
});
