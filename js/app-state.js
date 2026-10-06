document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const version = body.dataset.appVersion;
    const userId = body.dataset.userId;
    const versionKey = 'digitapp:version';
    const draftPrefix = `digitapp:draft:${userId}:`;
    const draftLifetime = 5 * 24 * 60 * 60 * 1000;

    // Elimina únicamente los borradores de DigitApp pertenecientes al usuario actual.
    function clearDrafts() {
        Object.keys(localStorage).filter((key) => key.startsWith(draftPrefix)).forEach((key) => localStorage.removeItem(key));
    }

    // Descarta formularios anteriores cuando la aplicación cambia de versión.
    function applyVersion(nextVersion) {
        const previous = localStorage.getItem(versionKey);
        if (previous && previous !== nextVersion) {
            clearDrafts();
            localStorage.setItem(versionKey, nextVersion);
            window.alert('DigitApp 2024 fue actualizado. La página se recargará y los datos no guardados serán descartados.');
            window.location.reload();
            return false;
        }
        localStorage.setItem(versionKey, nextVersion);
        return true;
    }

    // Construye una clave estable por usuario, ruta, registro y formulario.
    function draftKey(form) {
        return `${draftPrefix}${window.location.pathname}${window.location.search}:${form.id || 'formulario'}`;
    }

    // Serializa controles de texto y selección sin guardar archivos ni contraseñas.
    function serializeForm(form) {
        const fields = [];
        form.querySelectorAll('[name]').forEach((field) => {
            if (field.disabled || ['file', 'password', 'submit', 'button'].includes(field.type) || field.name === 'csrf') return;
            if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) return;
            fields.push([field.name, field.value]);
        });
        return fields;
    }

    // Restaura valores guardados y notifica a los módulos con selectores dinámicos.
    function restoreForm(form, fields) {
        const used = new Map();
        fields.forEach(([name, value]) => {
            const candidates = Array.from(form.querySelectorAll(`[name="${CSS.escape(name)}"]`));
            if (!candidates.length) return;
            if (candidates[0].type === 'radio' || candidates[0].type === 'checkbox') {
                candidates.forEach((field) => { if (field.value === value) field.checked = true; });
                return;
            }
            const index = used.get(name) || 0;
            const field = candidates[Math.min(index, candidates.length - 1)];
            field.value = value;
            used.set(name, index + 1);
        });
        form.dispatchEvent(new CustomEvent('digitapp:draft-restored', { bubbles: true }));
    }

    // Activa el borrador local de cinco días en los formularios marcados.
    function enableDraft(form) {
        const key = draftKey(form);
        try {
            const saved = JSON.parse(localStorage.getItem(key) || 'null');
            if (saved && Date.now() - saved.updatedAt <= draftLifetime) {
                restoreForm(form, saved.fields);
                window.setTimeout(() => restoreForm(form, saved.fields), 1200);
            } else if (saved) {
                localStorage.removeItem(key);
            }
        } catch (error) {
            localStorage.removeItem(key);
        }
        let timer;
        const save = () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => {
                try {
                    localStorage.setItem(key, JSON.stringify({ updatedAt: Date.now(), fields: serializeForm(form) }));
                } catch (error) {
                    form.dispatchEvent(new CustomEvent('digitapp:draft-error', { bubbles: true }));
                }
            }, 400);
        };
        form.addEventListener('input', save);
        form.addEventListener('change', save);
        form.addEventListener('submit', () => localStorage.removeItem(key));
        form.addEventListener('digitapp:draft-saved', () => localStorage.removeItem(key));
    }

    if (!version || !userId || !applyVersion(version)) return;
    document.querySelectorAll('form[data-local-draft="1"]').forEach(enableDraft);

    // Consulta periódicamente la versión para priorizar una publicación nueva.
    window.setInterval(async () => {
        try {
            const prefix = window.location.pathname.includes('/paginas/') ? '../' : '';
            const response = await fetch(`${prefix}backend/auth/version.php`, { cache: 'no-store', headers: { 'Accept': 'application/json' } });
            if (response.ok) applyVersion((await response.json()).version);
        } catch (error) {
            // Una interrupción temporal de red no modifica el formulario abierto.
        }
    }, 300000);
});

