/**
 * Reusable Load More (v2)
 * --------------------------------------------------------------------------
 * Each .js-load-more button is independent and configured by data attributes:
 *   data-lm-target     (required) CSS selector of items to reveal
 *   data-lm-scope                 container selector to scope the search
 *   data-lm-visible               initial count            (default 6)
 *   data-lm-step                  revealed per click        (default 6)
 *   data-lm-animation             fade | slide | none       (default fade)
 *   data-lm-duration              animation ms              (default 350)
 *   data-lm-autoload              "1" to reveal on scroll (button in viewport)
 *   data-lm-label                 button text
 *
 * Re-init after AJAX:  window.RLM.init();  /  window.RLM.init(container)
 * --------------------------------------------------------------------------
 */
(function () {
    'use strict';

    function toArray(n) { return Array.prototype.slice.call(n); }
    function attr(el, name, def) { var v = el.getAttribute(name); return v === null ? def : v; }

    // Safe selector queries — a malformed selector returns empty/null instead
    // of throwing a SyntaxError that could abort initialisation of other sections.
    function safeQueryAll(root, sel) {
        try { return toArray((root || document).querySelectorAll(sel)); }
        catch (e) { if (window.console) { console.warn('[Load More] bad selector:', sel); } return []; }
    }
    function safeQuery(sel) {
        try { return document.querySelector(sel); }
        catch (e) { if (window.console) { console.warn('[Load More] bad selector:', sel); } return null; }
    }

    function initButton(button) {
        if (button.getAttribute('data-lm-ready') === '1') { return; }

        var targetSel = attr(button, 'data-lm-target', '');
        if (!targetSel) { return; }

        var scopeSel = attr(button, 'data-lm-scope', '');
        var root = scopeSel ? (safeQuery(scopeSel) || document) : document;
        var items = safeQueryAll(root, targetSel);
        if (!items.length) { button.classList.add('lm-btn-hidden'); return; }

        var step      = parseInt(attr(button, 'data-lm-step', '6'), 10) || 6;
        var initial   = parseInt(attr(button, 'data-lm-visible', '6'), 10) || 6;
        var animation = attr(button, 'data-lm-animation', 'fade');
        var duration  = parseInt(attr(button, 'data-lm-duration', '350'), 10) || 0;
        var autoload  = attr(button, 'data-lm-autoload', '0') === '1';

        var visible = initial;

        if (animation !== 'none' && duration > 0) {
            items.forEach(function (item) {
                item.style.transition = 'opacity ' + duration + 'ms ease, transform ' + duration + 'ms ease';
            });
        }

        function animateIn(item) {
            if (animation === 'none' || duration <= 0) { return; }
            item.style.opacity = '0';
            if (animation === 'slide') { item.style.transform = 'translateY(14px)'; }
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    item.style.opacity = '1';
                    if (animation === 'slide') { item.style.transform = 'translateY(0)'; }
                });
            });
        }

        function render(animateFrom) {
            items.forEach(function (item, i) {
                var show = i < visible;
                if (show) {
                    item.classList.remove('lm-item-hidden');
                    if (typeof animateFrom === 'number' && i >= animateFrom) {
                        animateIn(item);
                    } else if (animation !== 'none') {
                        item.style.opacity = '1';
                        if (animation === 'slide') { item.style.transform = 'translateY(0)'; }
                    }
                } else {
                    item.classList.add('lm-item-hidden');
                }
            });
            updateButton();
        }

        function fullyShown() { return visible >= items.length; }

        function updateButton() {
            if (fullyShown()) {
                button.classList.add('lm-btn-hidden');
            } else {
                button.classList.remove('lm-btn-hidden');
            }
        }

        render();

        button.addEventListener('click', function (e) {
            e.preventDefault();
            var from = visible;
            visible += step;
            render(from);
        });

        // Auto-load: click step-by-step as the button enters the viewport.
        if (autoload && 'IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting && !fullyShown()) {
                        var from = visible;
                        visible += step;
                        render(from);
                    }
                    if (fullyShown()) { io.disconnect(); }
                });
            }, { rootMargin: '120px' });
            io.observe(button);
        }

        button.setAttribute('data-lm-ready', '1');

        /* ---- Optional Magnific lightbox ---- */
        if (attr(button, 'data-lm-popup', '0') === '1' &&
            window.jQuery && jQuery.fn && jQuery.fn.magnificPopup) {

            bindPopup(scopeSel, attr(button, 'data-lm-popup-delegate', 'a'), items);
        }
    }

    /* Bind Magnific to a gallery scope. Reused by buttons and popup-only markers. */
    function bindPopup(scopeSel, delegate, items) {
        if (!(window.jQuery && jQuery.fn && jQuery.fn.magnificPopup)) {
            return;
        }
        delegate = delegate || 'a';
        var $root;
        if (scopeSel && safeQuery(scopeSel)) {
            $root = jQuery(scopeSel);
        } else if (items && items.length && items[0].parentNode) {
            $root = jQuery(items[0].parentNode);
        } else {
            $root = jQuery(document);
        }
        $root.magnificPopup({
            delegate: delegate,
            type: 'image',
            gallery: { enabled: true, navigateByImgClick: true },
            mainClass: 'mfp-fade'
        });
    }

    /* Popup-only marker: no Load More button, just bind the lightbox. */
    function initPopupMarker(marker) {
        if (marker.getAttribute('data-lm-ready') === '1') { return; }
        var scopeSel = attr(marker, 'data-lm-scope', '');
        var targetSel = attr(marker, 'data-lm-target', '');
        var items = [];
        if (targetSel) {
            var root = scopeSel ? (safeQuery(scopeSel) || document) : document;
            items = safeQueryAll(root, targetSel);
        }
        bindPopup(scopeSel, attr(marker, 'data-lm-popup-delegate', 'a'), items);
        marker.setAttribute('data-lm-ready', '1');
    }

    function initAll(context) {
        var scope = context || document;

        // Each section is initialised independently. A failure in one section
        // must NEVER stop the others from initialising, so we isolate each call.
        toArray(scope.querySelectorAll('.js-load-more')).forEach(function (button) {
            try {
                initButton(button);
            } catch (err) {
                if (window.console) { console.error('[Load More] section init failed:', err, button); }
            }
        });

        toArray(scope.querySelectorAll('.js-rlm-popup')).forEach(function (marker) {
            try {
                initPopupMarker(marker);
            } catch (err) {
                if (window.console) { console.error('[Load More] popup init failed:', err, marker); }
            }
        });
    }

    window.RLM = { init: initAll, initButton: initButton };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(); });
    } else {
        initAll();
    }
})();
