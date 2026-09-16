<script>
(function() {
    function toggleBodyScrollLock() {
        const modals = document.querySelectorAll('div.fixed.inset-0');
        let isAnyModalOpen = false;

        modals.forEach(function(el) {
            // Skip non-modal elements like mobile drawers or non-backdrop overlays
            if (el.classList.contains('lg:hidden') && el.classList.contains('flex') && 
                !el.classList.contains('bg-black/80') && !el.classList.contains('bg-black/70') && 
                !el.classList.contains('bg-black/60') && !el.classList.contains('bg-black/50') && 
                !el.classList.contains('backdrop-blur-sm') && !el.classList.contains('backdrop-blur-md')) {
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

        const scrollContainers = document.querySelectorAll('main, #superadmin-main, #admin-main, #seller-main');

        if (isAnyModalOpen) {
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            scrollContainers.forEach(function(container) {
                if (container.style.overflow !== 'hidden') {
                    container.dataset.prevOverflow = container.style.overflowY || container.style.overflow || '';
                    container.style.overflow = 'hidden';
                }
            });
        } else {
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
            scrollContainers.forEach(function(container) {
                if (container.dataset.prevOverflow !== undefined) {
                    container.style.overflow = container.dataset.prevOverflow;
                    delete container.dataset.prevOverflow;
                } else {
                    container.style.overflow = '';
                }
            });
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
    window.addEventListener('resize', toggleBodyScrollLock);
})();
</script>
