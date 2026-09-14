// Sabor a Pueblo — Interruptor global Modo Claro / Oscuro
// Persiste en localStorage y funciona en todos los apartados.
(function () {
    var STORAGE_KEY = 'sabor-theme';

    function getSavedTheme() {
        try {
            return localStorage.getItem(STORAGE_KEY) || 'dark';
        } catch (e) {
            return 'dark';
        }
    }

    function applyTheme(theme) {
        var isLight = theme === 'light';
        document.body.classList.toggle('light-mode', isLight);
        document.documentElement.setAttribute('data-theme', theme);
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) { /* almacenamiento no disponible */ }
        updateButtons(isLight);
    }

    function updateButtons(isLight) {
        document.querySelectorAll('.theme-toggle-btn').forEach(function (btn) {
            var icon = btn.querySelector('i');
            if (icon) {
                icon.className = isLight ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
            }
            btn.setAttribute('aria-pressed', isLight ? 'true' : 'false');
            btn.title = isLight ? 'Cambiar a modo oscuro' : 'Cambiar a modo claro';
            var label = btn.querySelector('.theme-label');
            if (label) {
                label.textContent = isLight ? 'Oscuro' : 'Claro';
            }
        });
    }

    window.toggleTheme = function () {
        var isLight = document.body.classList.contains('light-mode');
        applyTheme(isLight ? 'dark' : 'light');
    };

    // Aplica lo antes posible para evitar parpadeo
    var initial = getSavedTheme();
    if (document.body) {
        applyTheme(initial);
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            applyTheme(getSavedTheme());
        });
    }
    document.addEventListener('DOMContentLoaded', function () {
        applyTheme(getSavedTheme());
    });
})();
