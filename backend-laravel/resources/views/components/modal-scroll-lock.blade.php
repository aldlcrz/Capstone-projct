<script>
(function() {
    function toggleBodyScrollLock() {
        const modals = document.querySelectorAll('.fixed.inset-0');
        let isAnyModalOpen = false;

        modals.forEach(function(el) {
            // Skip non-modal elements like sidebar drawers
            if (el.classList.contains('lg:hidden') && el.classList.contains('flex') && !el.classList.contains('bg-black/60') && !el.classList.contains('bg-black/50') && !el.classList.contains('backdrop-blur-sm')) {
                return;
            }

            const style = window.getComputedStyle(el);
            const isVisible = style.display !== 'none' && 
                              style.visibility !== 'hidden' && 
                              parseFloat(style.opacity || '1') > 0;

            if (isVisible) {
                isAnyModalOpen = true;
            }
        });

        if (isAnyModalOpen) {
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        }
    }

    const observer = new MutationObserver(toggleBodyScrollLock);

    document.addEventListener('DOMContentLoaded', function() {
        observer.observe(document.body, {
            attributes: true,
            childList: true,
            subtree: true,
            attributeFilter: ['style', 'class', 'x-show', 'x-cloak']
        });
        toggleBodyScrollLock();
    });

    window.addEventListener('popstate', toggleBodyScrollLock);
})();
</script>
