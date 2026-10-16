document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const version = body.dataset.appVersion;
    const userId = body.dataset.userId;
    const versionKey = 'digitapp:version';
    const draftPrefix = `digitapp:draft:${userId}:`;
    const draftLifetime = 5 * 24 * 60 * 60 * 1000;
    const themeKey = 'digitapp:theme';
    const themeSwitch = document.getElementById('digitapp-theme-switch');

    // Sincroniza la barra contextual con la altura real del menú responsive.
    function updateHeaderOffset() {
        const navbar = document.querySelector('.navbar.fixed-top');
        const height = navbar ? Math.ceil(navbar.getBoundingClientRect().height) : 68;
        document.documentElement.style.setProperty('--app-navbar-offset', `${height + 4}px`);
    }

    // Contrae la navegación móvil al pulsar fuera de la barra cuando el menú está abierto.
    function initializeResponsiveNavbarDismiss() {
        const navbar = document.querySelector('.navbar');
        const menu = document.getElementById('navbarDigitApp');
        const toggler = navbar?.querySelector('.navbar-toggler');
        if (!navbar || !menu || !toggler) return;
        document.addEventListener('click', (event) => {
            if (!menu.classList.contains('show') || navbar.contains(event.target)
                || window.getComputedStyle(toggler).display === 'none') return;
            bootstrap.Collapse.getOrCreateInstance(menu, { toggle: false }).hide();
        });
    }

    // Asigna prioridad visual a las acciones principales de la barra contextual.
    function pageHeaderActionPriority(action) {
        if (action.classList.contains('btn-success')) return 100;
        if (action.classList.contains('btn-primary')) return 90;
        if (action.classList.contains('btn-warning')) return 80;
        if (action.classList.contains('btn-secondary')) return 70;
        if (action.classList.contains('btn-info')) return 60;
        if (action.classList.contains('btn-danger')) return 50;
        return 40;
    }

    // Distribuye las acciones: dos accesos directos en escritorio y un menú compacto en móvil.
    function initializePageHeaderActions(header, actionGroups) {
        const actions = Array.from(new Set(actionGroups.flatMap((group) => Array.from(group.querySelectorAll('a.btn, button.btn, input.btn')))));
        if (!actions.length) return;

        const primaryActions = new Set(actions
            .map((action, index) => ({ action, index, priority: pageHeaderActionPriority(action) }))
            .sort((left, right) => right.priority - left.priority || left.index - right.index)
            .slice(0, 2)
            .map(({ action }) => action));

        const actionBar = actionGroups[0];
        actionBar.classList.add('app-page-header-actions');
        if (!header.contains(actionBar)) header.appendChild(actionBar);

        actionGroups.slice(1).forEach((group) => {
            if (group !== actionBar) group.remove();
        });

        const more = document.createElement('div');
        more.className = 'dropdown app-page-header-more';
        more.innerHTML = '<button class="btn btn-secondary app-page-header-more-toggle" type="button" aria-expanded="false">Acciones</button><ul class="dropdown-menu dropdown-menu-end"></ul>';
        const toggle = more.querySelector('.app-page-header-more-toggle');
        const menu = more.querySelector('.dropdown-menu');
        const menuItems = new Map(actions.map((action) => {
            const item = document.createElement('li');
            return [action, item];
        }));
        actionBar.appendChild(more);

        // Cierra el menú compartido y sincroniza su estado accesible.
        const closeMenu = () => {
            menu.classList.remove('show');
            toggle.setAttribute('aria-expanded', 'false');
        };
        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const open = !menu.classList.contains('show');
            closeMenu();
            if (open) {
                menu.classList.add('show');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
        menu.addEventListener('click', (event) => {
            if (event.target.closest('a, button, input')) closeMenu();
        });
        document.addEventListener('click', (event) => {
            if (!more.contains(event.target)) closeMenu();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && menu.classList.contains('show')) {
                closeMenu();
                toggle.focus();
            }
        });

        const renderActions = () => {
            const compact = window.matchMedia('(max-width: 1199.98px)').matches;
            actions.forEach((action) => {
                const useMenu = compact || !primaryActions.has(action);
                action.classList.toggle('dropdown-item', useMenu);
                action.classList.toggle('app-page-header-menu-action', useMenu);
                if (useMenu) {
                    const item = menuItems.get(action);
                    item.appendChild(action);
                    menu.appendChild(item);
                } else {
                    actionBar.insertBefore(action, more);
                }
            });
            more.classList.toggle('d-none', !menu.children.length);
        };

        renderActions();
        window.addEventListener('resize', renderActions, { passive: true });
    }

    // Retira imágenes e iconos para que la barra contextual conserve su altura compacta.
    function removePageHeaderVisuals(header) {
        header.querySelectorAll('img, picture, svg, i, .bi, [class^="bi-"], [class*=" bi-"]').forEach((visual) => visual.remove());
    }

    // Convierte el primer encabezado de la pantalla en una barra contextual compacta.
    function initializePageHeader() {
        const container = document.querySelector('.contain');
        if (!container) return;

        let header = container.querySelector(':scope > .app-page-header');
        if (!header) {
            const title = container.querySelector('h1, #titulo_direccion, .dashboard-hero h2')
                || container.querySelector('h2, h3');
            header = title?.closest('.contacto-form-header, .contactos-header, .empresa-detalle-header, .contacto-detail-header, .dashboard-hero, .row');
        }
        if (!header) return;

        header.classList.add('app-page-header');
        const titleColumn = header.querySelector(':scope > .col, :scope > [class*="col-"]');
        titleColumn?.classList.add('app-page-header-copy');
        const title = header.querySelector('h1') || header.querySelector('h2') || header.querySelector('h3');
        title?.classList.add('app-page-header-title');
        header.querySelector('.contactos-kicker, .empresa-header-kicker, .dashboard-kicker')?.classList.add('app-page-header-kicker');

        const actionGroups = [];
        let directActionGroup = null;
        header.querySelectorAll('.btn').forEach((button) => {
            let group = button.closest('.d-flex, [class*="col-"]');
            if (group === header) {
                directActionGroup ||= document.createElement('div');
                directActionGroup.className = 'app-page-header-actions';
                directActionGroup.appendChild(button);
                group = directActionGroup;
            }
            if (group && group !== titleColumn && !actionGroups.includes(group)) actionGroups.push(group);
        });

        container.querySelectorAll('[data-page-header-actions]').forEach((actions) => {
            if (!actionGroups.includes(actions)) actionGroups.push(actions);
        });
        initializePageHeaderActions(header, actionGroups);
        removePageHeaderVisuals(header);
    }

    // Aplica y conserva la preferencia visual en este navegador y dispositivo.
    function applyTheme(theme) {
        const nextTheme = theme === 'dark' ? 'dark' : 'original';
        document.documentElement.dataset.theme = nextTheme;
        if (themeSwitch) themeSwitch.checked = nextTheme === 'dark';
        localStorage.setItem(themeKey, nextTheme);
    }

    applyTheme(localStorage.getItem(themeKey));
    themeSwitch?.addEventListener('change', () => applyTheme(themeSwitch.checked ? 'dark' : 'original'));

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

    updateHeaderOffset();
    initializeResponsiveNavbarDismiss();
    initializePageHeader();
    window.addEventListener('resize', updateHeaderOffset, { passive: true });
    document.getElementById('navbarDigitApp')?.addEventListener('shown.bs.collapse', updateHeaderOffset);
    document.getElementById('navbarDigitApp')?.addEventListener('hidden.bs.collapse', updateHeaderOffset);

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
