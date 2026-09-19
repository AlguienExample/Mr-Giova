// Sabor a Pueblo — Interruptor global Modo Claro / Oscuro
// Cambio INSTANTANEO sin recargar la pagina. Persiste en localStorage,
// se sincroniza entre pestanas y notifica a los graficos/tablas dinamicas.
(function () {
    var STORAGE_KEY = 'sabor-theme';
    var THEME_CHANGE_EVENT = 'sabor-theme-change';

    function getSavedTheme() {
        try {
            var t = localStorage.getItem(STORAGE_KEY);
            return t === 'light' ? 'light' : 'dark';
        } catch (e) {
            return 'dark';
        }
    }

    function applyThemeToDom(theme) {
        var isLight = theme === 'light';
        try {
            if (document.documentElement) {
                document.documentElement.classList.toggle('light-mode', isLight);
                document.documentElement.setAttribute('data-theme', theme);
            }
            if (document.body) {
                // toggle con segundo argumento = cambio instantaneo, sin reload
                document.body.classList.toggle('light-mode', isLight);
                document.body.setAttribute('data-theme', theme);
            }
        } catch (e) { /* DOM aun no listo */ }
    }

    function updateButtons(isLight) {
        try {
            document.querySelectorAll('.theme-toggle-btn').forEach(function (btn) {
                var icon = btn.querySelector('i');
                if (icon) {
                    var newIconClass = isLight ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
                    if (icon.className !== newIconClass) {
                        icon.className = newIconClass;
                    }
                }
                
                var newAriaPressed = isLight ? 'true' : 'false';
                if (btn.getAttribute('aria-pressed') !== newAriaPressed) {
                    btn.setAttribute('aria-pressed', newAriaPressed);
                }
                
                var newAriaLabel = isLight ? 'Cambiar a modo oscuro' : 'Cambiar a modo claro';
                if (btn.getAttribute('aria-label') !== newAriaLabel) {
                    btn.setAttribute('aria-label', newAriaLabel);
                }
                if (btn.title !== newAriaLabel) {
                    btn.title = newAriaLabel;
                }
                
                var label = btn.querySelector('.theme-label');
                if (label) {
                    var newLabelText = isLight ? 'Oscuro' : 'Claro';
                    if (label.textContent !== newLabelText) {
                        label.textContent = newLabelText;
                    }
                }
            });
        } catch (e) { /* sin botones aun */ }
    }

    function refreshCharts(theme) {
        try {
            if (window.Chart && window.Chart.defaults) {
                var isLight = theme === 'light';
                window.Chart.defaults.color = isLight ? '#5B6478' : '#94A3B8';
                window.Chart.defaults.borderColor = isLight
                    ? 'rgba(26,29,43,0.08)'
                    : 'rgba(255,255,255,0.08)';
            }
        } catch (e) { /* Chart.js opcional */ }
    }

    function applyTheme(theme, opts) {
        opts = opts || {};
        var normalized = theme === 'light' ? 'light' : 'dark';
        var isLight = normalized === 'light';
        applyThemeToDom(normalized);
        if (opts.persist !== false) {
            try {
                localStorage.setItem(STORAGE_KEY, normalized);
            } catch (e) { /* almacenamiento no disponible */ }
        }
        updateButtons(isLight);
        refreshCharts(normalized);
        // Avisar a graficos/contenido dinamico (ej. salesChart en admin) para
        // que se repinten SIN recargar la pagina.
        try {
            var evt;
            if (typeof window.CustomEvent === 'function') {
                evt = new window.CustomEvent(THEME_CHANGE_EVENT, { detail: { theme: normalized } });
            } else {
                evt = document.createEvent('CustomEvent');
                evt.initCustomEvent(THEME_CHANGE_EVENT, false, false, { theme: normalized });
            }
            window.dispatchEvent(evt);
        } catch (e) { /* eventos no disponibles */ }
    }

    // API publica
    window.applySaborTheme = applyTheme;
    window.getSaborTheme = getSavedTheme;
    window.toggleTheme = function () {
        // Lectura directa del DOM = cambio instantaneo, sin necesidad de refresh.
        var isLight = false;
        try {
            isLight = document.body
                ? document.body.classList.contains('light-mode')
                : document.documentElement.classList.contains('light-mode');
        } catch (e) { isLight = getSavedTheme() === 'light'; }
        applyTheme(isLight ? 'dark' : 'light');
    };

    // Aplica lo antes posible para evitar parpadeo (sin esperar al usuario).
    var initial = getSavedTheme();
    applyThemeToDom(initial);
    updateButtons(initial === 'light');

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            // Re-aplicar el tema guardado (por si el snippet <head> aun no corrio)
            // y refrescar iconos. NO recarga la pagina.
            applyTheme(getSavedTheme(), { persist: false });
        });
    } else {
        applyTheme(initial, { persist: false });
    }

    // Sincronizar entre pestanas: si cambia en otra pestana, aplicar al instante.
    try {
        window.addEventListener('storage', function (e) {
            if (e && e.key === STORAGE_KEY) {
                applyTheme(e.newValue === 'light' ? 'light' : 'dark', { persist: false });
            }
        });
    } catch (e) { /* storage events no disponibles */ }

    // Si los botones se inyectan tarde (contenido dinamico), actualizar iconos.
    try {
        if (typeof window.MutationObserver === 'function' && document.body) {
            var observer = new window.MutationObserver(function () {
                updateButtons(
                    document.body.classList.contains('light-mode')
                );
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    } catch (e) { /* observer opcional */ }
})();

// ─── Compatibilidad ngrok (plan gratuito) ────────────────────────────────────
// ngrok muestra una página intermedia de aviso en cada dispositivo nuevo y los
// fetch de la app (menú, cocina, caja, admin) recibirían HTML en vez de JSON.
// Este header la omite. Se parchea aquí porque theme-toggle.js se carga en
// TODAS las páginas. En localhost el header extra es inofensivo.
(function () {
    try {
        if (window.__ngrokFetchPatched || typeof window.fetch !== 'function') return;
        window.__ngrokFetchPatched = true;
        var originalFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            init = init || {};
            try {
                var headers = new Headers(init.headers || {});
                if (!headers.has('ngrok-skip-browser-warning')) {
                    headers.set('ngrok-skip-browser-warning', 'true');
                }
                init = Object.assign({}, init, { headers: headers });
            } catch (e) { /* Headers no disponible: seguir sin parche */ }
            return originalFetch(input, init);
        };
    } catch (e) { /* fetch no disponible */ }
})();
