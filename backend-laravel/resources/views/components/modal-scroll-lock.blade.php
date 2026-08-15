<script>
(function() {
    function toggleBodyScrollLock() {
        // Find top-level modal containers only (ignore child nested backdrops)
        const modals = document.querySelectorAll('div.fixed.inset-0');
        let isAnyModalOpen = false;

        modals.forEach(function(el) {
            // Skip non-modal elements like mobile drawers or elements hidden by Alpine
            if (el.classList.contains('lg:hidden') && el.classList.contains('flex') && !el.classList.contains('bg-black/60') && !el.classList.contains('bg-black/50') && !el.classList.contains('backdrop-blur-sm')) {
                return;
            }

            // If inside a hidden parent or has display: none or x-cloak, skip
            if (el.hasAttribute('x-cloak') || el.closest('[x-cloak]')) {
                return;
            }

            const style = window.getComputedStyle(el);
            const isVisible = style.display !== 'none' && 
                              style.visibility !== 'hidden' && 
                              parseFloat(style.opacity || '1') > 0 &&
                              (el.offsetWidth > 0 || el.offsetHeight > 0 || el.getClientRects().length > 0);

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
