<script>
(function() {
    'use strict';

    var isLocked = false;

    function isElementTrulyVisible(el) {
        if (!el) return false;
        if (el.hasAttribute('x-cloak') || el.closest('[x-cloak]')) return false;

        var style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden' || parseFloat(style.opacity || '1') <= 0) {
            return false;
        }
        if (style.pointerEvents === 'none') {
            return false;
        }
        if (el.offsetWidth <= 0 && el.offsetHeight <= 0 && el.getClientRects().length === 0) {
            return false;
        }
        return true;
    }

    function toggleBodyScrollLock() {
        var modals = document.querySelectorAll('div.fixed.inset-0, [role="dialog"].fixed');
        var isAnyModalOpen = false;

        modals.forEach(function(el) {
            // Skip non-modal elements like mobile drawers or non-backdrop overlays
            if (el.classList.contains('lg:hidden') && el.classList.contains('flex') && 
                !el.classList.contains('bg-black/80') && !el.classList.contains('bg-black/70') && 
                !el.classList.contains('bg-black/60') && !el.classList.contains('bg-black/50') && 
                !el.classList.contains('backdrop-blur-sm') && !el.classList.contains('backdrop-blur-md')) {
                return;
            }

            if (isElementTrulyVisible(el)) {
                // Verify it actually has high z-index indicative of a modal overlay
                var z = parseInt(window.getComputedStyle(el).zIndex, 10);
                if (isNaN(z) || z >= 20) {
                    isAnyModalOpen = true;
                }
            }
        });

        var scrollContainers = document.querySelectorAll('main, #superadmin-main, #admin-main, #seller-main');

        if (isAnyModalOpen && !isLocked) {
            // Save scroll coordinates before locking
            var winY = window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
            document.body.dataset.lumbarongLockedWinY = winY;

            scrollContainers.forEach(function(container) {
                if (container.style.overflow !== 'hidden') {
                    container.dataset.prevOverflow = container.style.overflowY || container.style.overflow || '';
                    container.dataset.lumbarongLockedScrollY = container.scrollTop;
                    container.style.overflow = 'hidden';
                }
            });

            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            isLocked = true;
        } else if (!isAnyModalOpen && isLocked) {
            // Restore overflow and previous scroll coordinates
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';

            scrollContainers.forEach(function(container) {
                if (container.dataset.prevOverflow !== undefined) {
                    container.style.overflow = container.dataset.prevOverflow;
                    delete container.dataset.prevOverflow;
                } else {
                    container.style.overflow = '';
                }

                if (container.dataset.lumbarongLockedScrollY !== undefined) {
                    var prevTop = parseInt(container.dataset.lumbarongLockedScrollY, 10);
                    if (!isNaN(prevTop)) {
                        container.scrollTop = prevTop;
                    }
                    delete container.dataset.lumbarongLockedScrollY;
                }
            });

            if (document.body.dataset.lumbarongLockedWinY !== undefined) {
                var prevWinY = parseInt(document.body.dataset.lumbarongLockedWinY, 10);
                if (!isNaN(prevWinY)) {
                    window.scrollTo({ top: prevWinY, behavior: 'instant' });
                }
                delete document.body.dataset.lumbarongLockedWinY;
            }

            isLocked = false;
        }
    }

    var observer = new MutationObserver(function() {
        toggleBodyScrollLock();
    });

    function initObserver() {
        if (document.body) {
            observer.observe(document.body, {
                attributes: true,
                childList: true,
                subtree: true,
                attributeFilter: ['style', 'class', 'x-show', 'x-cloak']
            });
            // Give deferred Alpine a chance to evaluate before running initial check
            setTimeout(toggleBodyScrollLock, 60);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initObserver);
    } else {
        initObserver();
    }

    document.addEventListener('alpine:initialized', function() {
        setTimeout(toggleBodyScrollLock, 50);
    });

    window.addEventListener('popstate', toggleBodyScrollLock);
    window.addEventListener('resize', toggleBodyScrollLock);
})();
</script>
