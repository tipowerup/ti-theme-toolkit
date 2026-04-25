{{--
    Synchronous dark-mode class application — MUST be rendered as early as
    possible in <head>, before any stylesheet loads. Prevents flash-of-wrong-
    theme between initial paint and Alpine's async store init. Mirrors the
    localStorage + prefers-color-scheme logic in `js/dark-mode.js` so the
    two sources of truth stay in sync.
--}}
<script data-navigate-track>
    (function () {
        try {
            var saved = localStorage.getItem('darkMode');
            var isDark = saved === 'dark'
                || (saved === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) { /* storage blocked — leave default */ }
    })();
</script>
