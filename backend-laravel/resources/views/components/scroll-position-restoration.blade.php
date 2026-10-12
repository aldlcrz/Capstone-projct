<script>
/**
 * LumBarong Intelligent Scroll Position Preservation & Restoration Engine
 * Preserves exact scroll context across page reloads (F5, Ctrl+R, browser reload)
 * for both document window and nested dashboard scroll containers (<main>).
 */
(function() {
    'use strict';

    if (typeof window === 'undefined' || !window.sessionStorage) return;

    var STORAGE_PREFIX = 'lumbarong_scroll_pos:';
    var FORM_SUBMIT_KEY = 'lumbarong_form_submitting';
    var MAX_STABILIZE_MS = 2500;
    var PRUNE_MAX_AGE_MS = 7200000; // 2 hours

    // Clean up stale entries on boot
    try {
        var now = Date.now();
        var keysToRemove = [];
        for (var i = 0; i < sessionStorage.length; i++) {
            var k = sessionStorage.key(i);
            if (k && k.indexOf(STORAGE_PREFIX) === 0) {
                try {
                    var val = JSON.parse(sessionStorage.getItem(k));
                    if (!val || !val.timestamp || (now - val.timestamp > PRUNE_MAX_AGE_MS)) {
                        keysToRemove.push(k);
                    }
                } catch(e) {
                    keysToRemove.push(k);
                }
            }
        }
        keysToRemove.forEach(function(k) { sessionStorage.removeItem(k); });
    } catch(e) {}

    function getStorageKey(url) {
        var pathAndQuery = url || (window.location.pathname + window.location.search);
        return STORAGE_PREFIX + pathAndQuery;
    }

    function getMainContainer() {
        return document.querySelector('#superadmin-main, #admin-main, #seller-main, main.overflow-y-auto, main[class*="overflow-y-auto"]') || document.querySelector('main');
    }

    // Determine navigation intent
    function detectNavigationType() {
        try {
            if (window.performance && performance.getEntriesByType) {
                var entries = performance.getEntriesByType('navigation');
                if (entries && entries.length > 0) {
                    return entries[0].type; // 'navigate', 'reload', 'back_forward', 'prerender'
                }
            }
            if (window.performance && window.performance.navigation) {
                var typeCode = window.performance.navigation.type;
                if (typeCode === 1) return 'reload';
                if (typeCode === 2) return 'back_forward';
                return 'navigate';
            }
        } catch(e) {}
        return 'navigate';
    }

    var navType = detectNavigationType();
    var isReload = (navType === 'reload');
    var isBackForward = (navType === 'back_forward');

    // Check if coming back from same-page form submission
    var isSamePageFormSubmit = false;
    try {
        var pendingForm = sessionStorage.getItem(FORM_SUBMIT_KEY);
        if (pendingForm) {
            var parsedForm = JSON.parse(pendingForm);
            if (parsedForm && parsedForm.url === (window.location.pathname + window.location.search) && (Date.now() - parsedForm.time < 20000)) {
                isSamePageFormSubmit = true;
            }
            sessionStorage.removeItem(FORM_SUBMIT_KEY);
        }
    } catch(e) {}

    // Track active state
    var isRestoring = false;
    var userInteracted = false;
    var saveTimer = null;

    // Track form submissions to enable scroll restore on post-redirect-get to same page
    document.addEventListener('submit', function(e) {
        try {
            var form = e.target;
            if (form && (!form.action || form.action === window.location.href || form.action.indexOf(window.location.pathname) !== -1)) {
                sessionStorage.setItem(FORM_SUBMIT_KEY, JSON.stringify({
                    url: window.location.pathname + window.location.search,
                    time: Date.now()
                }));
            }
        } catch(e) {}
    }, { capture: true, passive: true });

    // When clicking a link to a DIFFERENT page, clear any cached position for that target
    // so fresh page navigation always begins at the top.
    document.addEventListener('click', function(e) {
        var anchor = e.target.closest('a[href]');
        if (!anchor) return;
        var href = anchor.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

        try {
            var targetUrl = new URL(anchor.href, window.location.origin);
            var currentPathQuery = window.location.pathname + window.location.search;
            var targetPathQuery = targetUrl.pathname + targetUrl.search;
            if (targetUrl.origin === window.location.origin && targetPathQuery !== currentPathQuery) {
                sessionStorage.removeItem(getStorageKey(targetPathQuery));
            }
        } catch(e) {}
    }, { capture: true, passive: true });

    // Cancel restoration immediately if user touches, wheels, or presses scroll keys
    function handleUserInterrupt() {
        if (isRestoring) {
            userInteracted = true;
            isRestoring = false;
        }
    }
    ['wheel', 'touchstart', 'touchmove', 'keydown', 'mousedown'].forEach(function(evt) {
        window.addEventListener(evt, function(e) {
            if (evt === 'keydown') {
                var keys = ['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '];
                if (keys.indexOf(e.key) === -1) return;
            }
            handleUserInterrupt();
        }, { passive: true, capture: true });
    });

    // Save current scroll position
    function saveScrollPosition() {
        if (isRestoring) return;
        
        try {
            var winY = window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
            var winX = window.scrollX || window.pageXOffset || document.documentElement.scrollLeft || 0;
            
            var mainContainer = getMainContainer();
            var mainY = 0;
            var mainX = 0;
            if (mainContainer) {
                mainY = mainContainer.scrollTop || 0;
                mainX = mainContainer.scrollLeft || 0;
            }

            // Do not overwrite with zeros if document is hidden or unloading
            if (winY === 0 && mainY === 0) {
                if (document.hidden) return;
            }

            var data = {
                windowY: winY,
                windowX: winX,
                mainY: mainY,
                mainX: mainX,
                timestamp: Date.now()
            };

            sessionStorage.setItem(getStorageKey(), JSON.stringify(data));
        } catch(e) {}
    }

    function queueSave() {
        if (isRestoring) return;
        if (saveTimer) cancelAnimationFrame(saveTimer);
        saveTimer = requestAnimationFrame(function() {
            saveScrollPosition();
        });
    }

    // Attach passive scroll listeners
    window.addEventListener('scroll', queueSave, { passive: true });
    
    function attachContainerScrollListener() {
        var main = getMainContainer();
        if (main && !main._lumbarongScrollAttached) {
            main._lumbarongScrollAttached = true;
            main.addEventListener('scroll', queueSave, { passive: true });
        }
    }

    // Save immediately before page unloads
    window.addEventListener('beforeunload', saveScrollPosition);
    window.addEventListener('pagehide', saveScrollPosition);

    // RESTORATION LOGIC
    var shouldRestore = (isReload || isBackForward || isSamePageFormSubmit);

    // If an explicit hash exists and this is NOT a reload, allow browser to navigate to anchor
    if (window.location.hash && !isReload) {
        shouldRestore = false;
    }

    if (shouldRestore) {
        var rawSaved = null;
        try {
            rawSaved = sessionStorage.getItem(getStorageKey());
        } catch(e) {}

        if (rawSaved) {
            try {
                var saved = JSON.parse(rawSaved);
                if (saved && (saved.windowY > 0 || saved.mainY > 0 || saved.windowX > 0 || saved.mainX > 0)) {
                    isRestoring = true;

                    // Disable automatic browser window restoration so it does not conflict with our controller
                    if ('scrollRestoration' in history) {
                        try {
                            history.scrollRestoration = 'manual';
                        } catch(e) {}
                    }

                    var targetWinY = saved.windowY || 0;
                    var targetWinX = saved.windowX || 0;
                    var targetMainY = saved.mainY || 0;
                    var targetMainX = saved.mainX || 0;

                    var startTime = Date.now();
                    var stableFrames = 0;
                    var lastHeight = 0;
                    var resizeObserver = null;

                    function applyScroll() {
                        if (userInteracted) return;

                        // Check if form validation errors were surfaced and scrolled
                        var hasValidationErrors = document.querySelector('.is-invalid, [aria-invalid="true"], .border-red-500, .ring-red-400');
                        if (hasValidationErrors && isSamePageFormSubmit) {
                            // Let validation error remain in view
                            isRestoring = false;
                            return;
                        }

                        // Apply to window
                        if (targetWinY > 0 || targetWinX > 0) {
                            window.scrollTo({
                                top: targetWinY,
                                left: targetWinX,
                                behavior: 'instant'
                            });
                        }

                        // Apply to main container
                        var main = getMainContainer();
                        if (main && (targetMainY > 0 || targetMainX > 0)) {
                            main.scrollTop = targetMainY;
                            main.scrollLeft = targetMainX;
                        }
                    }

                    function checkStability() {
                        if (!isRestoring || userInteracted) {
                            cleanup();
                            return;
                        }

                        applyScroll();

                        var currentWinY = window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
                        var main = getMainContainer();
                        var currentMainY = main ? main.scrollTop : 0;
                        var currentHeight = Math.max(
                            document.documentElement.scrollHeight,
                            document.body.scrollHeight,
                            main ? main.scrollHeight : 0
                        );

                        var winReached = (targetWinY === 0 || Math.abs(currentWinY - targetWinY) <= 3);
                        var mainReached = (targetMainY === 0 || !main || Math.abs(currentMainY - targetMainY) <= 3);
                        var heightUnchanged = (currentHeight === lastHeight);
                        lastHeight = currentHeight;

                        if (winReached && mainReached && heightUnchanged) {
                            stableFrames++;
                        } else {
                            stableFrames = 0;
                        }

                        var elapsed = Date.now() - startTime;
                        // Finish when layout is stable for 3 consecutive frames or max timeout reached
                        if (stableFrames >= 3 || elapsed >= MAX_STABILIZE_MS) {
                            cleanup();
                        } else {
                            requestAnimationFrame(checkStability);
                        }
                    }

                    function cleanup() {
                        isRestoring = false;
                        if (resizeObserver) {
                            try { resizeObserver.disconnect(); } catch(e) {}
                            resizeObserver = null;
                        }
                        // Resume recording normal user scroll positions
                        attachContainerScrollListener();
                    }

                    // Apply immediately
                    applyScroll();

                    // Observe layout shifts / Alpine component expansions
                    if (window.ResizeObserver) {
                        try {
                            resizeObserver = new ResizeObserver(function() {
                                if (isRestoring && !userInteracted) {
                                    applyScroll();
                                }
                            });
                            if (document.body) resizeObserver.observe(document.body);
                            var mainEl = getMainContainer();
                            if (mainEl) resizeObserver.observe(mainEl);
                        } catch(e) {}
                    }

                    // Start requestAnimationFrame stabilization monitor
                    requestAnimationFrame(checkStability);

                    // Re-apply when Alpine finishes initialization
                    document.addEventListener('alpine:initialized', function() {
                        if (isRestoring && !userInteracted) {
                            applyScroll();
                        }
                    }, { once: true });

                    // Re-apply on window load (after images and stylesheets finish loading)
                    window.addEventListener('load', function() {
                        if (isRestoring && !userInteracted) {
                            applyScroll();
                        }
                    }, { once: true });
                }
            } catch(e) {
                isRestoring = false;
            }
        }
    }

    // Attach container scroll listener once DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachContainerScrollListener);
    } else {
        attachContainerScrollListener();
    }
})();
</script>
