window.DashboardTheme = (function () {
    function updateThemeIcon() {
        const icon = document.getElementById('themeToggleIcon');
        if (!icon) return;
        if (document.documentElement.classList.contains('dark')) {
            icon.className = 'fa-solid fa-sun text-amber-400 text-sm';
        } else {
            icon.className = 'fa-solid fa-moon text-slate-600 text-sm';
        }
    }

    function initTheme() {
        const savedTheme = localStorage.getItem('azania_theme')
            || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', savedTheme === 'dark');
        updateThemeIcon();
    }

    function toggleDarkMode() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('azania_theme', isDark ? 'dark' : 'light');
        updateThemeIcon();
        if (typeof window.renderApp === 'function') window.renderApp();
    }

    return { initTheme, toggleDarkMode, updateThemeIcon };
})();
