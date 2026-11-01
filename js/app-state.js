// Propaga cambios confirmados en datos maestros entre pestañas del mismo navegador.
window.DigitAppMasterSync = {
    key: 'digitapp:master-data-update',
    // Publica el registro guardado para que las demás pestañas consulten de nuevo sus datos.
    publish(entity, id) {
        const detail = {entity, id: Number(id) || null, updatedAt: Date.now()};
        try { localStorage.setItem(this.key, JSON.stringify(detail)); } catch (_) {}
        window.dispatchEvent(new CustomEvent('digitapp:master-data-updated', {detail}));
    },
    // Registra una actualización local de datos maestros, oportunidades o documentos.
    subscribe(callback) {
        window.addEventListener('digitapp:master-data-updated', event => callback(event.detail));
    }
};

window.addEventListener('storage', event => {
    if (event.key !== window.DigitAppMasterSync.key || !event.newValue) { return; }
    try {
        const detail = JSON.parse(event.newValue);
        window.dispatchEvent(new CustomEvent('digitapp:master-data-updated', {detail}));
    } catch (_) {}
});

document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const version = body.dataset.appVersion;
    const userId = body.dataset.userId;
    const versionKey = 'digitapp:version';
    const draftPrefix = `digitapp:draft:${userId}:`;
    const draftLifetime = 5 * 24 * 60 * 60 * 1000;
    const themeKey = 'digitapp:theme';
    const themeSwitch = document.getElementById('digitapp-theme-switch');
    // Cámbialo a false para volver al diseño móvil anterior sin alterar el escritorio.
    const mobileNavigationEnabled = true;

    const masterUpdate = new URLSearchParams(location.search);
    const masterEntity = masterUpdate.get('master_sync');
    const masterId = masterUpdate.get('master_id');
    if (masterEntity && masterId) {
        window.DigitAppMasterSync.publish(masterEntity, masterId);
        masterUpdate.delete('master_sync');
        masterUpdate.delete('master_id');
        const query = masterUpdate.toString();
        history.replaceState(null, '', location.pathname + (query ? '?' + query : '') + location.hash);
    }

    // Mantiene abierto el grupo de la página actual sin cerrar otros grupos desplegados.
    function initializeSidebarDropdownPersistence() {
        const groups = Array.from(document.querySelectorAll('#navbarDigitApp .navbar-nav > .nav-item.dropdown'));
        if (!groups.length || !window.bootstrap?.Dropdown) return;

        groups.forEach((group) => {
            const toggle = group.querySelector('[data-bs-toggle="dropdown"]');
            toggle?.addEventListener('click', (event) => {
                if (mobileNavigationEnabled && window.matchMedia('(max-width: 767.98px)').matches) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (group.classList.contains('active') && group.classList.contains('show')) return;

                    const open = !group.classList.contains('show');
                    group.classList.toggle('show', open);
                    group.querySelector(':scope > .dropdown-menu')?.classList.toggle('show', open);
                    toggle.setAttribute('aria-expanded', String(open));
                    return;
                }
                if (group.classList.contains('active') && group.classList.contains('show')) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
        });

        const currentGroup = groups.find((group) => group.classList.contains('active'));
        if (currentGroup) {
            // Bootstrap cierra desplegables al abrir el menú del usuario; conserva visible la sección actual.
            currentGroup.addEventListener('hide.bs.dropdown', (event) => event.preventDefault());
            bootstrap.Dropdown.getOrCreateInstance(currentGroup.querySelector('[data-bs-toggle="dropdown"]')).show();
        }
    }

    // Sincroniza la barra contextual con la altura real del menú responsive.
    function updateHeaderOffset() {
        const navbar = document.querySelector('.navbar.fixed-top');
        if (mobileNavigationEnabled && window.matchMedia('(max-width: 767.98px)').matches) {
            document.documentElement.style.setProperty('--app-navbar-offset', '0px');
            return;
        }
        if (navbar?.classList.contains('app-sidebar') && window.matchMedia('(min-width: 1200px)').matches) {
            document.documentElement.style.setProperty('--app-navbar-offset', '0px');
            return;
        }
        const height = navbar ? Math.ceil(navbar.getBoundingClientRect().height) : 68;
        document.documentElement.style.setProperty('--app-navbar-offset', `${height + 4}px`);
    }

    // Contrae la navegación móvil al pulsar fuera de la barra cuando el menú está abierto.
    function initializeResponsiveNavbarDismiss() {
        const navbar = document.querySelector('.navbar');
        const menu = document.getElementById('navbarDigitApp');
        const toggler = document.querySelector('.app-page-header .navbar-toggler') || navbar?.querySelector('.navbar-toggler');
        if (!navbar || !menu || !toggler) return;

        const mobileViewport = window.matchMedia('(max-width: 767.98px)');
        const backdrop = document.createElement('div');
        backdrop.className = 'app-mobile-nav-backdrop';
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.appendChild(backdrop);

        const setDrawerOpen = (open) => {
            document.body.classList.toggle('app-mobile-nav-open', open);
            toggler.setAttribute('aria-expanded', String(open));
        };

        const syncNavigationMode = () => {
            const useDrawer = mobileNavigationEnabled && mobileViewport.matches;
            document.body.classList.toggle('app-mobile-crm-layout', useDrawer);
            if (useDrawer) {
                toggler.removeAttribute('data-bs-toggle');
                toggler.removeAttribute('data-bs-target');
                setDrawerOpen(false);
            } else {
                setDrawerOpen(false);
                toggler.setAttribute('data-bs-toggle', 'collapse');
                toggler.setAttribute('data-bs-target', '#navbarDigitApp');
            }
        };

        toggler.addEventListener('click', (event) => {
            if (!mobileNavigationEnabled || !mobileViewport.matches) return;
            event.preventDefault();
            event.stopPropagation();
            setDrawerOpen(!document.body.classList.contains('app-mobile-nav-open'));
        });

        document.addEventListener('click', (event) => {
            if (mobileNavigationEnabled && mobileViewport.matches) {
                if (!document.body.classList.contains('app-mobile-nav-open')) return;
                if (!navbar.contains(event.target) && !toggler.contains(event.target)) setDrawerOpen(false);
                if (event.target.closest('#navbarDigitApp .dropdown-item, #navbarDigitApp .nav-item:not(.dropdown) > .nav-link')) {
                    setDrawerOpen(false);
                }
                return;
            }
            if (!menu.classList.contains('show') || navbar.contains(event.target)
                || window.getComputedStyle(toggler).display === 'none') return;
            bootstrap.Collapse.getOrCreateInstance(menu, { toggle: false }).hide();
        }, true);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && mobileNavigationEnabled && mobileViewport.matches
                && document.body.classList.contains('app-mobile-nav-open')) {
                setDrawerOpen(false);
                toggler.focus();
            }
        });
        mobileViewport.addEventListener('change', syncNavigationMode);
        syncNavigationMode();
    }

    // Mueve temporalmente el control del panel al encabezado compacto en teléfonos.
    function initializeMobileHeaderNavigation(header) {
        if (!mobileNavigationEnabled) return;
        const navbar = document.querySelector('.app-sidebar');
        const toggler = navbar?.querySelector('.navbar-toggler');
        if (!navbar || !toggler) return;

        const placeholder = document.createComment('Posición original del botón de navegación');
        toggler.before(placeholder);
        const mobileViewport = window.matchMedia('(max-width: 767.98px)');
        const placeToggler = () => {
            if (mobileViewport.matches && header.isConnected) {
                header.insertBefore(toggler, header.firstElementChild);
            } else if (placeholder.parentNode) {
                placeholder.parentNode.insertBefore(toggler, placeholder);
            }
        };
        placeToggler();
        mobileViewport.addEventListener('change', placeToggler);
    }

    // Gestiona el menú del usuario con el toque en móvil, sin depender del cierre global de Bootstrap.
    function initializeMobileUserMenu() {
        const dropdown = document.querySelector('.app-sidebar-user');
        const toggle = dropdown?.querySelector('[data-bs-toggle="dropdown"]');
        const menu = dropdown?.querySelector('.dropdown-menu');
        if (!mobileNavigationEnabled || !dropdown || !toggle || !menu) return;

        const mobileViewport = window.matchMedia('(max-width: 767.98px)');
        const closeMenu = () => {
            dropdown.classList.remove('show');
            menu.classList.remove('show');
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', (event) => {
            if (!mobileViewport.matches) return;
            event.preventDefault();
            event.stopPropagation();
            const open = !menu.classList.contains('show');
            dropdown.classList.toggle('show', open);
            menu.classList.toggle('show', open);
            toggle.setAttribute('aria-expanded', String(open));
        });

        document.addEventListener('click', (event) => {
            if (!mobileViewport.matches || !menu.classList.contains('show')) return;
            if (!dropdown.contains(event.target)
                || (menu.contains(event.target) && event.target.closest('a, button, input, select'))) closeMenu();
        }, true);
        mobileViewport.addEventListener('change', closeMenu);
    }

    // Muestra las acciones directamente fuera del teléfono y las compacta solo en móvil.
    function initializePageHeaderActions(header, actionGroups) {
        const actions = Array.from(new Set(actionGroups.flatMap((group) => Array.from(group.querySelectorAll('a.btn, button.btn, input.btn')))));
        if (!actions.length) return;

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
            const compact = window.matchMedia('(max-width: 767.98px)').matches;
            actions.forEach((action) => {
                const useMenu = compact;
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

    // Reemplaza el texto del encabezado sin eliminar números, estados ni controles dinámicos.
    function setCanonicalPageTitle(titleElement, label) {
        if (!titleElement || !label) return;
        const walker = document.createTreeWalker(titleElement, NodeFilter.SHOW_TEXT);
        const plainTextNodes = [];
        while (walker.nextNode()) {
            const node = walker.currentNode;
            let ancestor = node.parentElement;
            let protectedFragment = false;
            while (ancestor && ancestor !== titleElement) {
                if (ancestor.matches('[id], .badge, [data-preserve-title-fragment]')) {
                    protectedFragment = true;
                    break;
                }
                ancestor = ancestor.parentElement;
            }
            if (protectedFragment) continue;
            plainTextNodes.push(node);
        }
        const firstTextNode = plainTextNodes.find((node) => node.nodeValue.trim() !== '');
        if (!firstTextNode) {
            titleElement.prepend(document.createTextNode(label));
            return;
        }
        firstTextNode.nodeValue = label;
        plainTextNodes.filter((node) => node !== firstTextNode).forEach((node) => { node.nodeValue = ''; });
    }

    // Prepara el encabezado contextual y lo fija fuera del panel desplazable.
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

        header.classList.remove('row', 'text-center', 'dashboard-hero', 'contacto-form-header', 'contactos-header', 'empresa-detalle-header', 'contacto-detail-header');
        header.classList.add('app-page-header');
        const titleColumn = header.querySelector(':scope > .col, :scope > [class*="col-"]');
        titleColumn?.classList.add('app-page-header-copy');
        const title = header.querySelector('h1') || header.querySelector('h2') || header.querySelector('h3');
        title?.classList.add('app-page-header-title');
        setCanonicalPageTitle(title, body.dataset.sectionTitle);
        const kicker = header.querySelector('.contactos-kicker, .empresa-header-kicker, .dashboard-kicker');
        kicker?.remove();

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
        body.insertBefore(header, container);
        initializeMobileHeaderNavigation(header);
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
    initializeSidebarDropdownPersistence();
    initializePageHeader();
    initializeMobileUserMenu();
    initializeResponsiveNavbarDismiss();
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
