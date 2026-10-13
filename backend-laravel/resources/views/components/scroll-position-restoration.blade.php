<script>
/**
 * LumBarong Intelligent State & Scroll Position Preservation & Restoration Engine
 * Preserves exact location, scroll context, active tabs, and wizard progress across page reloads (F5, Ctrl+R, browser reload)
 * for both document window and nested dashboard scroll containers (<main>).
 * Does not reset or redirect user to the beginning of the page on refresh.
 */
(function() {
    'use strict';

    if (typeof window === 'undefined') return;

    var STORAGE_PREFIX = 'lumbarong_scroll_pos:';
    var FORM_SUBMIT_KEY = 'lumbarong_form_submitting';
    var MAX_STABILIZE_MS = 2500;
    var PRUNE_MAX_AGE_MS = 7200000; // 2 hours

    // Memory fallback if sessionStorage is restricted in incognito/webview
    var memoryStore = {};
    function safeSessionGet(key) {
        try {
            if (window.sessionStorage) return window.sessionStorage.getItem(key);
        } catch(e) {}
        return memoryStore[key] || null;
    }
    function safeSessionSet(key, val) {
        try {
            if (window.sessionStorage) {
                window.sessionStorage.setItem(key, val);
                return;
            }
        } catch(e) {}
        memoryStore[key] = val;
    }
    function safeSessionRemove(key) {
        try {
            if (window.sessionStorage) window.sessionStorage.removeItem(key);
        } catch(e) {}
        delete memoryStore[key];
    }

    // Clean up stale entries on boot
    try {
        var now = Date.now();
        var keysToRemove = [];
        if (window.sessionStorage) {
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
        }
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
        var pendingForm = safeSessionGet(FORM_SUBMIT_KEY);
        if (pendingForm) {
            var parsedForm = JSON.parse(pendingForm);
            if (parsedForm && parsedForm.url === (window.location.pathname + window.location.search) && (Date.now() - parsedForm.time < 20000)) {
                isSamePageFormSubmit = true;
            }
            safeSessionRemove(FORM_SUBMIT_KEY);
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
                safeSessionSet(FORM_SUBMIT_KEY, JSON.stringify({
                    url: window.location.pathname + window.location.search,
                    time: Date.now()
                }));
            }
        } catch(e) {}
    }, { capture: true, passive: true });

    // When clicking a link to a DIFFERENT page, clear any cached position for that target
    // so fresh page navigation always begins cleanly at the top.
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
                safeSessionRemove(getStorageKey(targetPathQuery));
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
    ['wheel', 'touchstart', 'touchmove', 'keydown', 'mousedown', 'click', 'pointerdown'].forEach(function(evt) {
        window.addEventListener(evt, function(e) {
            if (evt === 'keydown') {
                var keys = ['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '];
                if (keys.indexOf(e.key) === -1) return;
            }
            handleUserInterrupt();
        }, { passive: true, capture: true });
    });

    // =========================================================================
    // ACTIVE TAB & PROGRESS DISCOVERY & STATE MANAGEMENT
    // =========================================================================
    function isTabKey(k) {
        if (!k || typeof k !== 'string') return false;
        var lower = k.toLowerCase();
        if (['activetab', 'statusfilter', 'shopactivetab', 'viewtab', 'activesgtab', 
             'activepaymenttab', 'currenttab', 'tab', 'rectab', 'sizetab', 
             'activemobiletab'].indexOf(lower) !== -1) return true;
        return /(?:activetab$|[a-z0-9_]*tab$)/i.test(k) && !/table/i.test(k);
    }

    function isStepKey(k) {
        if (!k || typeof k !== 'string') return false;
        var lower = k.toLowerCase();
        if (['step', 'currentstep', 'emailstep', 'wizardstep'].indexOf(lower) !== -1) return true;
        return /(?:^step$|[a-z0-9_]*step$|^progress$)/i.test(k);
    }

    function getAlpineComponents() {
        var els = document.querySelectorAll('[x-data]');
        var list = [];
        els.forEach(function(el, idx) {
            var data = null;
            if (window.Alpine && typeof window.Alpine.$data === 'function') {
                try { data = window.Alpine.$data(el); } catch(e) {}
            }
            if (!data && el._x_dataStack && el._x_dataStack.length > 0) {
                data = el._x_dataStack[0];
            }
            if (data && typeof data === 'object') {
                list.push({ el: el, data: data, id: el.id || ('comp_' + idx) });
            }
        });
        return list;
    }

    function collectAlpineState() {
        var state = { tabs: {}, progress: {} };
        try {
            var components = getAlpineComponents();
            components.forEach(function(comp) {
                var data = comp.data;
                var prefix = comp.id + ':';
                for (var key in data) {
                    if (typeof data[key] === 'function') continue;
                    var val = data[key];
                    if (val === undefined || val === null) continue;

                    if (isTabKey(key) && (typeof val === 'string' || typeof val === 'number')) {
                        state.tabs[key] = val;
                        state.tabs[prefix + key] = val;
                    } else if (isStepKey(key) && (typeof val === 'number' || typeof val === 'string')) {
                        state.progress[key] = val;
                        state.progress[prefix + key] = val;
                    }
                }
            });
        } catch(e) {}
        return state;
    }

    var alpineStateRestored = false;
    function restoreAlpineState(saved) {
        if (alpineStateRestored || !saved || (!saved.tabs && !saved.progress)) return false;
        var didRestore = false;
        try {
            var components = getAlpineComponents();
            if (components.length === 0) return false;
            alpineStateRestored = true;
            components.forEach(function(comp) {
                var data = comp.data;
                var prefix = comp.id + ':';

                // Restore tabs
                if (saved.tabs) {
                    for (var key in data) {
                        if (isTabKey(key)) {
                            var targetVal = (saved.tabs[prefix + key] !== undefined)
                                ? saved.tabs[prefix + key]
                                : (comp.id && !comp.id.startsWith('comp_') ? undefined : saved.tabs[key]);
                            if (targetVal !== undefined && data[key] !== targetVal) {
                                try {
                                    data[key] = targetVal;
                                    didRestore = true;
                                } catch(e) {}
                            }
                        }
                    }
                }

                // Restore progress / steps
                if (saved.progress) {
                    for (var key in data) {
                        if (isStepKey(key)) {
                            var targetVal = (saved.progress[prefix + key] !== undefined)
                                ? saved.progress[prefix + key]
                                : saved.progress[key];
                            if (targetVal !== undefined && data[key] !== targetVal) {
                                try {
                                    data[key] = targetVal;
                                    didRestore = true;
                                } catch(e) {}
                            }
                        }
                    }
                }
            });
        } catch(e) {}
        return didRestore;
    }

    // Save current scroll position, active tabs, and progress
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

            var alpineState = collectAlpineState();

            var data = {
                windowY: winY,
                windowX: winX,
                mainY: mainY,
                mainX: mainX,
                tabs: alpineState.tabs,
                progress: alpineState.progress,
                hash: window.location.hash || '',
                timestamp: Date.now()
            };

            safeSessionSet(getStorageKey(), JSON.stringify(data));
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

    // Also monitor tab & button clicks to immediately record state changes
    document.addEventListener('click', function(e) {
        var el = e.target.closest('button, a, [role="tab"], [class*="tab"]');
        if (el) {
            setTimeout(saveScrollPosition, 60);
        }
    }, { capture: true, passive: true });

    // Save immediately before page unloads
    window.addEventListener('beforeunload', saveScrollPosition);
    window.addEventListener('pagehide', saveScrollPosition);
    window.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') saveScrollPosition();
    });

    // RESTORATION LOGIC
    var shouldRestore = (isReload || isBackForward || isSamePageFormSubmit);

    // If an explicit hash exists and this is NOT a reload, allow browser to navigate to anchor
    if (window.location.hash && !isReload) {
        shouldRestore = false;
    }

    if (shouldRestore) {
        var rawSaved = safeSessionGet(getStorageKey());

        if (rawSaved) {
            try {
                var saved = JSON.parse(rawSaved);
                if (saved) {
                    isRestoring = true;

                    // Disable automatic browser window restoration so it does not conflict with our controller
                    if ('scrollRestoration' in history) {
                        try {
                            history.scrollRestoration = 'manual';
                        } catch(e) {}
                    }

                    // 1. Restore URL hash if present
                    if (saved.hash && !window.location.hash) {
                        try {
                            history.replaceState(null, '', window.location.pathname + window.location.search + saved.hash);
                        } catch(e) {}
                    }

                    // 2. Early restoration of tabs & progress
                    restoreAlpineState(saved);

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

                    // Hook into Alpine initialization phases to ensure tabs & progress are injected early
                    document.addEventListener('alpine:init', function() {
                        if (window.Alpine && typeof window.Alpine.interceptInit === 'function') {
                            try {
                                window.Alpine.interceptInit(function(component, next) {
                                    if (saved && (saved.tabs || saved.progress)) {
                                        for (var k in component) {
                                            if (isTabKey(k) && saved.tabs && saved.tabs[k] !== undefined) {
                                                component[k] = saved.tabs[k];
                                            }
                                            if (isStepKey(k) && saved.progress && saved.progress[k] !== undefined) {
                                                component[k] = saved.progress[k];
                                            }
                                        }
                                    }
                                    if (typeof next === 'function') next();
                                });
                            } catch(e) {}
                        }
                    });

                    // Re-apply when Alpine finishes initialization
                    document.addEventListener('alpine:initialized', function() {
                        if (isRestoring && !userInteracted) {
                            restoreAlpineState(saved);
                            applyScroll();
                        }
                    }, { once: true });

                    // Re-apply on window load (after images and stylesheets finish loading)
                    window.addEventListener('load', function() {
                        if (isRestoring && !userInteracted) {
                            restoreAlpineState(saved);
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
