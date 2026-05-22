<script data-navigate-track>
    (function () {
        try {
            var saved = localStorage.getItem('darkMode');
            var isDark = saved === 'dark'
                || (saved === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) { }
    })();
</script>
