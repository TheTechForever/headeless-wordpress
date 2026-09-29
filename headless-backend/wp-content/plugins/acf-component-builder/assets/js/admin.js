/* ACF Component Builder — admin JS (Phase 1: confirm dialogs + copy helpers). */
(function ($) {
    'use strict';
    $(function () {
        // Confirm destructive actions.
        $(document).on('click', '[data-acb-confirm]', function (e) {
            var msg = (window.ACB_ADMIN && ACB_ADMIN.i18n && ACB_ADMIN.i18n.confirmDelete) || 'Are you sure?';
            if (!window.confirm(msg)) { e.preventDefault(); }
        });

        // Click-to-copy render snippets.
        $(document).on('click', '.acb-table code, .acb-list code', function () {
            var text = $(this).text();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
                var $el = $(this);
                var orig = $el.css('background-color');
                $el.css('background-color', '#c6f6d5');
                setTimeout(function () { $el.css('background-color', orig); }, 400);
            }
        });
    });
})(jQuery);

/* Help tooltips + copy buttons (Phase 4 usability). */
(function () {
    'use strict';

    // --- Shared tooltip element ------------------------------------------
    var tip = null, pinned = null;
    function ensureTip() {
        if (tip) { return tip; }
        tip = document.createElement('div');
        tip.className = 'acb-tip-bubble';
        tip.setAttribute('role', 'tooltip');
        tip.hidden = true;
        document.body.appendChild(tip);
        return tip;
    }
    function showTip(btn) {
        var text = btn.getAttribute('data-acb-tip');
        if (!text) { return; }
        var t = ensureTip();
        t.textContent = text;
        t.hidden = false;
        var r = btn.getBoundingClientRect();
        var top = window.scrollY + r.bottom + 8;
        var left = window.scrollX + r.left + (r.width / 2) - (t.offsetWidth / 2);
        left = Math.max(8, Math.min(left, window.scrollX + document.documentElement.clientWidth - t.offsetWidth - 8));
        t.style.top = top + 'px';
        t.style.left = left + 'px';
    }
    function hideTip() {
        if (tip && !pinned) { tip.hidden = true; }
    }

    document.addEventListener('mouseover', function (e) {
        var btn = e.target.closest && e.target.closest('.acb-help');
        if (btn) { showTip(btn); }
    });
    document.addEventListener('mouseout', function (e) {
        var btn = e.target.closest && e.target.closest('.acb-help');
        if (btn && !pinned) { hideTip(); }
    });
    document.addEventListener('focusin', function (e) {
        var btn = e.target.closest && e.target.closest('.acb-help');
        if (btn) { showTip(btn); }
    });
    document.addEventListener('focusout', function (e) {
        var btn = e.target.closest && e.target.closest('.acb-help');
        if (btn && !pinned) { hideTip(); }
    });
    // Click pins/unpins (touch-friendly).
    document.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('.acb-help');
        if (btn) {
            e.preventDefault();
            if (pinned === btn) { pinned = null; hideTip(); }
            else { pinned = btn; showTip(btn); }
            return;
        }
        if (pinned) { pinned = null; hideTip(); }
    });

    // --- Copy buttons -----------------------------------------------------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('.acb-copy');
        if (!btn) { return; }
        e.preventDefault();
        var text = btn.getAttribute('data-copy') || '';
        function flash() {
            var orig = btn.textContent;
            btn.textContent = (window.ACB_ADMIN && ACB_ADMIN.i18n && ACB_ADMIN.i18n.copied) || 'Copied!';
            btn.classList.add('is-copied');
            setTimeout(function () { btn.textContent = orig; btn.classList.remove('is-copied'); }, 1200);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(flash, flash);
        } else {
            var ta = document.createElement('textarea');
            ta.value = text; document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); } catch (_) {}
            document.body.removeChild(ta); flash();
        }
    });
})();
