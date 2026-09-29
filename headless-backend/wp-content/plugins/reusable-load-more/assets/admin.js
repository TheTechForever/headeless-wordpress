(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            // DOM already parsed (footer scripts often run after this) -> run now.
            fn();
        }
    }

    ready(function () {


        /* ---- Tabs ---- */
        var tabs   = document.querySelectorAll('.rlm-tabs .nav-tab');
        var panels = document.querySelectorAll('.rlm-tab-panel');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                tabs.forEach(function (t) { t.classList.remove('nav-tab-active'); });
                tab.classList.add('nav-tab-active');
                var id = tab.getAttribute('href');
                panels.forEach(function (p) { p.style.display = ('#' + p.id === id) ? '' : 'none'; });
            });
        });

        /* ------------------------------------------------------------------
         * Field help tooltips
         * Each field's documentation lives in a hidden .rlm-desc span next to
         * it. We inject a small (i) button into the field's label and show the
         * text in a popover on click. The popover is attached to <body> with
         * fixed positioning so it can't be clipped by the section card's
         * overflow:hidden.
         * ---------------------------------------------------------------- */
        var tipPop = null;
        var tipOwner = null; // the button that opened the popover

        function buildPop() {
            if (tipPop) { return tipPop; }
            tipPop = document.createElement('div');
            tipPop.className = 'rlm-pop';
            tipPop.setAttribute('role', 'dialog');
            tipPop.innerHTML = '<button type="button" class="rlm-pop-x" aria-label="Close">&times;</button><div class="rlm-pop-body"></div>';
            document.body.appendChild(tipPop);
            tipPop.querySelector('.rlm-pop-x').addEventListener('click', closeTip);
            return tipPop;
        }

        function closeTip() {
            if (tipPop) { tipPop.classList.remove('is-open'); }
            if (tipOwner) { tipOwner.setAttribute('aria-expanded', 'false'); }
            tipOwner = null;
        }

        function openTip(btn) {
            var desc = btn._rlmDesc;
            if (!desc) { return; }
            var pop = buildPop();
            pop.querySelector('.rlm-pop-body').innerHTML = desc.innerHTML;
            pop.classList.add('is-open');

            // Measure, then place below the button, flipping/clamping to stay
            // inside the viewport.
            var r = btn.getBoundingClientRect();
            var pw = pop.offsetWidth;
            var ph = pop.offsetHeight;
            var pad = 8;

            var left = r.left + (r.width / 2) - (pw / 2);
            left = Math.max(pad, Math.min(left, window.innerWidth - pw - pad));

            var top = r.bottom + 6;
            if (top + ph > window.innerHeight - pad) {
                var above = r.top - ph - 6;
                top = (above > pad) ? above : Math.max(pad, window.innerHeight - ph - pad);
            }

            pop.style.left = Math.round(left) + 'px';
            pop.style.top  = Math.round(top) + 'px';

            tipOwner = btn;
            btn.setAttribute('aria-expanded', 'true');
        }

        function initTips(root) {
            var descs = (root || document).querySelectorAll('.rlm-desc');
            Array.prototype.forEach.call(descs, function (desc) {
                if (desc.getAttribute('data-rlm-tip') === '1') { return; }
                desc.setAttribute('data-rlm-tip', '1');

                // Find the label this description belongs to: walk back through
                // siblings, else fall back to the first label in the field.
                var anchor = null;
                var prev = desc.previousElementSibling;
                while (prev) {
                    if (prev.tagName === 'LABEL') { anchor = prev; break; }
                    prev = prev.previousElementSibling;
                }
                if (!anchor && desc.parentNode) {
                    anchor = desc.parentNode.querySelector('label');
                }
                if (!anchor) { return; }

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'rlm-info';
                btn.setAttribute('aria-label', 'What is this?');
                btn.setAttribute('aria-expanded', 'false');
                btn.innerHTML = '<span class="dashicons dashicons-editor-help"></span>';
                btn._rlmDesc = desc;
                anchor.appendChild(btn);
            });
        }

        // Toggle on click (delegated so it survives added sections).
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.rlm-info');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                if (tipOwner === btn) { closeTip(); } else { closeTip(); openTip(btn); }
                return;
            }
            if (tipPop && !e.target.closest('.rlm-pop')) { closeTip(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeTip(); }
        });
        window.addEventListener('resize', closeTip);
        window.addEventListener('scroll', closeTip, true);

        initTips(document);

        /* ------------------------------------------------------------------
         * Posts-mode field visibility
         * Query source = "auto" hides the whole Category / Tag / Author filter
         * group (the section follows the current archive instead). In "manual"
         * mode those controls come back, and each dependent sub-field
         * (fixed category / fixed tag / specific author) shows only when its
         * own mode select calls for it. One function is the single source of
         * truth so the initial load, the auto toggle, the mode selects and
         * cloned sections all stay consistent.
         * ---------------------------------------------------------------- */
        function rlmApplyPostsVisibility(row) {
            if (!row) { return; }
            var ctxSel = row.querySelector('.rlm-context');
            var isAuto = ctxSel ? (ctxSel.value === 'auto') : false;

            row.querySelectorAll('.rlm-manual-scope').forEach(function (el) {
                el.style.display = isAuto ? 'none' : '';
            });
            if (isAuto) { return; }

            var catmode = row.querySelector('.rlm-catmode');
            var catfixed = row.querySelector('.rlm-catfixed');
            if (catmode && catfixed) { catfixed.style.display = (catmode.value === 'current') ? 'none' : ''; }

            var tagmode = row.querySelector('.rlm-tagmode');
            var tagfixed = row.querySelector('.rlm-tagfixed');
            if (tagmode && tagfixed) { tagfixed.style.display = (tagmode.value === 'fixed') ? '' : 'none'; }

            var authormode = row.querySelector('.rlm-authormode');
            var authorid = row.querySelector('.rlm-authorid');
            if (authormode && authorid) { authorid.style.display = (authormode.value === 'specific') ? '' : 'none'; }
        }

        /* Keep a hidden "manual" text input in sync with its helper dropdown.
         * Value "__manual__" reveals the text box for free typing; any other
         * value is copied into the (hidden) text box, which is what actually
         * gets saved. This gives "dropdown when available, manual fallback". */
        function rlmSyncSelectManual(sel, manualInput) {
            if (!sel || !manualInput) { return; }
            if (sel.value === '__manual__') {
                manualInput.style.display = '';
                try { manualInput.focus(); } catch (err) {}
            } else {
                manualInput.value = sel.value;
                manualInput.style.display = 'none';
            }
        }

        /* ---- Live "post type does not exist" warning ---- */
        function rlmCheckPostType(field) {
            if (!field) { return; }
            var input = field.querySelector('.rlm-pt-manual');
            var warn  = field.querySelector('.rlm-pt-warning');
            if (!input || !warn) { return; }

            var known = (window.RLM_ADMIN && RLM_ADMIN.post_types) ? RLM_ADMIN.post_types : [];
            var val = (input.value || '').trim();
            var bad = (val !== '' && known.length && known.indexOf(val) === -1);

            var slugEl = warn.querySelector('.rlm-pt-warning-slug');
            if (slugEl) { slugEl.textContent = val; }
            warn.style.display = bad ? '' : 'none';
        }

        document.addEventListener('input', function (e) {
            if (e.target.classList && e.target.classList.contains('rlm-pt-manual')) {
                rlmCheckPostType(e.target.closest('.rlm-field'));
            }
        });

        /* ---- Live shortcode preview ----
         * The shortcode bar reflects the ID / Slug as it is typed, so it no
         * longer takes a page refresh to appear. It also flags that the section
         * is unsaved, because the shortcode does nothing until it is stored.
         */
        function rlmSyncShortcode(row) {
            if (!row) { return; }
            var idEl = row.querySelector('input.rlm-id');
            var bar  = row.querySelector('.rlm-shortcode');
            if (!idEl || !bar) { return; }

            var id = (idEl.value || '').trim();
            var sc = '[load_more id="' + id + '"]';

            var codeEl = bar.querySelector('.rlm-sc-text');
            var phpEl  = bar.querySelector('.rlm-sc-php');
            var copyEl = bar.querySelector('.rlm-copy');

            if (codeEl) { codeEl.textContent = sc; }
            if (phpEl)  { phpEl.textContent = "rlm_load_more('" + id + "');"; }
            if (copyEl) { copyEl.setAttribute('data-copy', sc); }

            bar.classList.toggle('is-empty', id === '');

            // "Saved" means the stored slug matches what is in the box.
            var savedId = row.getAttribute('data-original-id') || '';
            bar.classList.toggle('is-unsaved', id !== '' && id !== savedId);
        }

        document.addEventListener('input', function (e) {
            if (e.target.classList && e.target.classList.contains('rlm-id')) {
                rlmSyncShortcode(e.target.closest('.rlm-instance'));
            }
        });

        function rlmSyncAllShortcodes(root) {
            var rows = (root || document).querySelectorAll('.rlm-instance');
            Array.prototype.forEach.call(rows, function (r) { rlmSyncShortcode(r); });
        }
        rlmSyncAllShortcodes(document);

        function rlmHandleSelectManual(target, row) {
            var map = [
                ['rlm-pt-select', '.rlm-pt-manual'],
                ['rlm-cat-select', '.rlm-cat-manual'],
                ['rlm-tag-select', '.rlm-tag-manual'],
                ['rlm-author-select', '.rlm-author-manual']
            ];
            for (var i = 0; i < map.length; i++) {
                if (target.classList.contains(map[i][0])) {
                    var field = target.closest('.rlm-field');
                    rlmSyncSelectManual(target, field && field.querySelector(map[i][1]));
                    if (map[i][0] === 'rlm-pt-select') { rlmCheckPostType(field); }
                    return true;
                }
            }
            return false;
        }

        /* ---- Add / remove / collapse instances ---- */
        var wrap   = document.getElementById('rlm-instances');
        var tplEl  = document.getElementById('rlm-template');
        var addBtn = document.getElementById('rlm-add');

        function nextIndex() {
            var rows = wrap.querySelectorAll('.rlm-instance');
            var max = -1;
            rows.forEach(function (r) {
                var i = parseInt(r.getAttribute('data-index'), 10);
                if (!isNaN(i) && i > max) { max = i; }
            });
            return max + 1;
        }

        // Apply posts-mode field visibility to every section already on the page.
        if (wrap) {
            wrap.querySelectorAll('.rlm-instance').forEach(function (r) {
                rlmApplyPostsVisibility(r);
            });
        }

        if (addBtn && tplEl && wrap) {
            addBtn.addEventListener('click', function () {
                var html = tplEl.innerHTML.replace(/__INDEX__/g, nextIndex());
                var temp = document.createElement('div');
                temp.innerHTML = html.trim();
                var node = temp.firstChild;
                wrap.appendChild(node);
                initTips(node);
                rlmApplyPostsVisibility(node);
                rlmSyncShortcode(node);
                node.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }

        // Document-level fallback so the per-section Save always works,
        // regardless of #rlm-instances lookup or script load order.
        document.addEventListener('click', function (e) {
            var saveBtn = e.target.closest('.rlm-save-section');
            if (saveBtn) {
                e.preventDefault();
                var srow2 = saveBtn.closest('.rlm-instance');
                if (srow2 && window.rlmSaveSection) {
                    window.rlmSaveSection(srow2, saveBtn);
                }
            }
        });

        if (wrap) {
            wrap.addEventListener('click', function (e) {
                if (e.target.closest('.rlm-remove')) {
                    var row = e.target.closest('.rlm-instance');
                    if (!row) { return; }

                    var idEl = row.querySelector('input.rlm-id');
                    var secId = idEl ? idEl.value.trim() : '';
                    var label = secId || 'this section';

                    if (!confirm('Remove "' + label + '"? This cannot be undone.')) { return; }

                    // Never given an ID = never saved to the database, so there
                    // is nothing on the server to delete.
                    if (!secId) { row.remove(); return; }

                    var delUrl = (window.RLM_ADMIN && RLM_ADMIN.ajax_url) ? RLM_ADMIN.ajax_url : (window.ajaxurl || '/wp-admin/admin-ajax.php');
                    var delNonce = (window.RLM_ADMIN && RLM_ADMIN.nonce) ? RLM_ADMIN.nonce : '';

                    var delData = new FormData();
                    delData.append('action', 'rlm_delete_section');
                    delData.append('nonce', delNonce);
                    delData.append('id', secId);

                    var rmBtn = e.target.closest('.rlm-remove');
                    rmBtn.disabled = true;

                    fetch(delUrl, { method: 'POST', credentials: 'same-origin', body: delData })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            if (res && res.success) {
                                row.remove();
                            } else {
                                rmBtn.disabled = false;
                                alert('Could not delete: ' + ((res && res.data && res.data.message) || 'unknown error'));
                            }
                        })
                        .catch(function (err) {
                            rmBtn.disabled = false;
                            alert('Could not delete: ' + err);
                        });
                    return;
                }

                // Per-section AJAX save
                if (e.target.closest('.rlm-save-section')) {
                    var srow = e.target.closest('.rlm-instance');
                    if (srow && window.rlmSaveSection) { window.rlmSaveSection(srow, e.target.closest('.rlm-save-section')); }
                    return;
                }

                var head = e.target.closest('.js-rlm-collapse');
                if (head) {
                    var thisRow = head.closest('.rlm-instance');
                    var isOpen = !thisRow.classList.contains('is-collapsed');

                    if (isOpen) {
                        // Trying to close: warn if there are unsaved changes.
                        if (thisRow.getAttribute('data-dirty') === '1') {
                            if (!confirm('You have unsaved changes in this section. Close without saving?')) {
                                return; // keep it open
                            }
                        }
                        thisRow.classList.add('is-collapsed');
                    } else {
                        // Opening this one: close all others first (accordion).
                        wrap.querySelectorAll('.rlm-instance').forEach(function (r) {
                            if (r !== thisRow) { r.classList.add('is-collapsed'); }
                        });
                        thisRow.classList.remove('is-collapsed');
                    }
                }
            });

            // Mark a section dirty when any field changes.
            function markDirty(e) {
                var row = e.target.closest('.rlm-instance');
                if (row) { row.setAttribute('data-dirty', '1'); }
            }
            wrap.addEventListener('input', markDirty);
            wrap.addEventListener('change', markDirty);

            wrap.addEventListener('input', function (e) {
                var row = e.target.closest('.rlm-instance');
                if (!row) { return; }
                if (e.target.matches('input[name$="[label]"], input.rlm-id')) {
                    var label = row.querySelector('input[name$="[label]"]').value;
                    var id = row.querySelector('input.rlm-id').value;
                    var t = row.querySelector('.rlm-title-text');
                    if (t) { t.textContent = label || id || 'New section'; }
                    var badge = row.querySelector('.rlm-badge-id');
                    if (badge) { badge.textContent = id; badge.style.display = id ? '' : 'none'; }
                }
            });

            // Show/hide the popup link-selector field with the popup checkbox
            wrap.addEventListener('change', function (e) {
                if (e.target.classList.contains('rlm-popup-toggle')) {
                    var row = e.target.closest('.rlm-instance');
                    var f = row && row.querySelector('.rlm-popup-delegate');
                    var h = row && row.querySelector('.rlm-popup-hint');
                    if (f) { f.style.display = e.target.checked ? '' : 'none'; }
                    if (h) { h.style.display = e.target.checked ? 'none' : ''; }
                }
                // Show/hide the Posts fieldset based on the "Render posts" tick
                if (e.target.classList.contains('rlm-source-toggle')) {
                    var row2 = e.target.closest('.rlm-instance');
                    var posts = row2 && row2.querySelector('.rlm-posts-fieldset');
                    if (posts) { posts.style.display = e.target.checked ? '' : 'none'; }
                    // Swap the explanatory banner in Identity & Targeting to match the mode.
                    var bPosts = row2 && row2.querySelector('.rlm-mode-posts');
                    var bSel   = row2 && row2.querySelector('.rlm-mode-selector');
                    if (bPosts) { bPosts.style.display = e.target.checked ? '' : 'none'; }
                    if (bSel)   { bSel.style.display   = e.target.checked ? 'none' : ''; }
                }
                // Show/hide the custom width field based on column width mode
                if (e.target.classList.contains('rlm-colmode')) {
                    var row3 = e.target.closest('.rlm-instance');
                    var mode = e.target.value;
                    var show = function (sel, on) {
                        var el = row3 && row3.querySelector(sel);
                        if (el) { el.style.display = on ? '' : 'none'; }
                    };
                    show('.rlm-colwidth', mode === 'percent');
                    show('.rlm-colmin', mode === 'auto');
                    show('.rlm-gridclass', mode === 'class');
                    show('.rlm-itemclass', mode === 'class');
                }
                // Toggle inline template / theme template-part / default-layout fields
                if (e.target.classList.contains('rlm-cardsource')) {
                    var row4 = e.target.closest('.rlm-instance');
                    var val = e.target.value;
                    var isPart = (val === 'template');
                    var isInline = (val === 'inline');
                    var isDefault = (val === 'wp_default');
                    var partF = row4 && row4.querySelector('.rlm-cardpart');
                    var partN = row4 && row4.querySelector('.rlm-cardpart-note');
                    var inlineF = row4 && row4.querySelector('.rlm-cardinline');
                    var defaultN = row4 && row4.querySelector('.rlm-carddefault-note');
                    if (partF)    { partF.style.display    = isPart ? '' : 'none'; }
                    if (partN)    { partN.style.display    = isPart ? '' : 'none'; }
                    if (inlineF)  { inlineF.style.display  = isInline ? '' : 'none'; }
                    if (defaultN) { defaultN.style.display = isDefault ? '' : 'none'; }
                }
                // Query source (manual vs auto) toggles the whole filter group.
                if (e.target.classList.contains('rlm-context')) {
                    rlmApplyPostsVisibility(e.target.closest('.rlm-instance'));
                }
                // Category / Tag / Author mode selects -> recompute via single fn.
                if (e.target.classList.contains('rlm-authormode') ||
                    e.target.classList.contains('rlm-catmode') ||
                    e.target.classList.contains('rlm-tagmode')) {
                    rlmApplyPostsVisibility(e.target.closest('.rlm-instance'));
                }
                // Helper dropdowns -> sync their hidden manual text input.
                rlmHandleSelectManual(e.target, e.target.closest('.rlm-instance'));
            });
        }

        /* ---- Icon media picker (delegated on document) ---- */
        document.addEventListener('click', function (e) {

            var pick = e.target.closest('.rlm-pick-icon');
            if (pick) {
                e.preventDefault();

                if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                    alert('WordPress media library failed to load on this page. Please reload and try again.');
                    return;
                }

                var field   = pick.closest('.rlm-icon-field');
                var input   = field.querySelector('.rlm-icon-url');
                var preview = field.querySelector('.rlm-icon-preview');

                var frame = wp.media({
                    title: 'Select button icon',
                    button: { text: 'Use this icon' },
                    multiple: false
                });

                frame.on('select', function () {
                    var att = frame.state().get('selection').first().toJSON();
                    var url = att.url;
                    if (input)   { input.value = url; }
                    if (preview) { preview.src = url; preview.style.display = ''; }
                });

                frame.open();
                return;
            }

            var clear = e.target.closest('.rlm-clear-icon');
            if (clear) {
                e.preventDefault();
                var f = clear.closest('.rlm-icon-field');
                var inp = f.querySelector('.rlm-icon-url');
                var prev = f.querySelector('.rlm-icon-preview');
                if (inp)  { inp.value = ''; }
                if (prev) { prev.src = ''; prev.style.display = 'none'; }
                return;
            }

            /* ---- Copy buttons ---- */
            var copy = e.target.closest('.rlm-copy');
            if (copy) {
                var text = '';
                if (copy.dataset.copy) {
                    text = copy.dataset.copy;
                } else if (copy.dataset.copyTarget) {
                    var el = document.querySelector(copy.dataset.copyTarget);
                    if (el) { text = (el.value !== undefined) ? el.value : el.textContent; }
                }
                if (!text) { return; }
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function () {
                        var orig = copy.textContent;
                        copy.textContent = 'Copied!';
                        setTimeout(function () { copy.textContent = orig; }, 1200);
                    });
                }
            }
        });

        /* ---- Per-section AJAX save ---- */
        /* ------------------------------------------------------------------
         * Rename prompt
         * Changing the ID / Slug is ambiguous: the user might be correcting a
         * typo (rename this section) or spinning off a copy (create a new one).
         * Guessing produces silent duplicates, so we ask.
         * ---------------------------------------------------------------- */
        function rlmAskRename(oldId, newId) {
            return new Promise(function (resolve) {
                var back = document.createElement('div');
                back.className = 'rlm-modal-back';
                back.innerHTML =
                    '<div class="rlm-modal" role="dialog" aria-modal="true">' +
                        '<h2>You changed the ID / Slug</h2>' +
                        '<p>This section was saved as <code>' + oldId + '</code> and you have changed it to <code>' + newId + '</code>. What would you like to do?</p>' +
                        '<div class="rlm-modal-opts">' +
                            '<button type="button" class="button button-primary" data-mode="rename">' +
                                'Rename this section' +
                                '<small>Keeps one section. Any shortcode using <code>' + oldId + '</code> must be updated to <code>' + newId + '</code>.</small>' +
                            '</button>' +
                            '<button type="button" class="button" data-mode="duplicate">' +
                                'Create a new section' +
                                '<small>Leaves <code>' + oldId + '</code> exactly as it is and saves these settings as <code>' + newId + '</code>.</small>' +
                            '</button>' +
                        '</div>' +
                        '<button type="button" class="button-link rlm-modal-cancel">Cancel</button>' +
                    '</div>';
                document.body.appendChild(back);

                function done(val) {
                    if (back.parentNode) { back.parentNode.removeChild(back); }
                    document.removeEventListener('keydown', onKey);
                    resolve(val);
                }
                function onKey(e) { if (e.key === 'Escape') { done(null); } }

                back.addEventListener('click', function (e) {
                    var choice = e.target.closest('[data-mode]');
                    if (choice) { done(choice.getAttribute('data-mode')); return; }
                    if (e.target.closest('.rlm-modal-cancel') || e.target === back) { done(null); }
                });
                document.addEventListener('keydown', onKey);
            });
        }

        function rlmSaveSection(row, btn) {
            var status = row.querySelector('.rlm-save-status');
            console.log('[RLM] Save clicked. status el:', status, 'RLM_ADMIN:', window.RLM_ADMIN);

            function setStatus(msg, cls) {
                if (status) {
                    status.textContent = msg;
                    status.className = 'rlm-save-status' + (cls ? ' ' + cls : '');
                } else {
                    // Fallback if the status span is missing for some reason.
                    console.log('[RLM]', msg);
                }
            }

            var idInput = row.querySelector('input.rlm-id');
            if (idInput && idInput.value.trim() === '') {
                setStatus('Add an ID / slug first.', 'is-error');
                return;
            }

            // Detect a slug change on an already-saved section and ask what it
            // means before writing anything. `silent` (Save All) skips the
            // prompt and leaves the section untouched rather than guessing.
            var originalId = row.getAttribute('data-original-id') || '';
            var currentId  = idInput ? idInput.value.trim() : '';
            if (originalId && currentId && originalId !== currentId) {
                if (btn === null) {
                    setStatus('ID changed — save this section on its own.', 'is-error');
                    return;
                }
                rlmAskRename(originalId, currentId).then(function (mode) {
                    if (!mode) {
                        setStatus('Save cancelled.', '');
                        setTimeout(function () { setStatus('', ''); }, 2000);
                        return;
                    }
                    rlmDoSave(row, btn, setStatus, originalId, mode);
                });
                return;
            }

            rlmDoSave(row, btn, setStatus, originalId, '');
        }

        function rlmDoSave(row, btn, setStatus, originalId, mode) {
            var ajaxUrl = (window.RLM_ADMIN && RLM_ADMIN.ajax_url) ? RLM_ADMIN.ajax_url : (window.ajaxurl || '/wp-admin/admin-ajax.php');
            var ajaxNonce = (window.RLM_ADMIN && RLM_ADMIN.nonce) ? RLM_ADMIN.nonce : '';

            // Collect all named fields within this section.
            var data = new FormData();
            data.append('action', 'rlm_save_section');
            data.append('nonce', ajaxNonce);
            if (originalId) { data.append('original_id', originalId); }
            if (mode) { data.append('mode', mode); }

            row.querySelectorAll('input, select, textarea').forEach(function (el) {
                if (!el.name) { return; }
                var m = el.name.match(/\[([^\]]+)\]\s*$/);
                if (!m) { return; }
                var key = m[1];
                if (el.type === 'checkbox') {
                    if (el.checked) { data.append('section[' + key + ']', el.value || '1'); }
                } else {
                    data.append('section[' + key + ']', el.value);
                }
            });

            if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
            setStatus('Saving…', '');

            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            })
            .then(function (r) { return r.text(); })
            .then(function (text) {
                if (btn) { btn.disabled = false; }
                var res;
                try { res = JSON.parse(text); }
                catch (err) {
                    console.error('[RLM] Non-JSON response:', text);
                    setStatus('Server error (see console).', 'is-error');
                    if (btn) { btn.textContent = 'Save Changes'; }
                    return;
                }
                if (res && res.success) {
                    row.setAttribute('data-dirty', '0');

                    // A "create new" leaves the original untouched on the server
                    // but the page still shows the edited copy in its place, so
                    // reload to get a truthful list.
                    if (res.data && res.data.reload) {
                        setStatus('✓ Created — reloading…', 'is-ok');
                        if (btn) { btn.textContent = '✓ Created'; }
                        setTimeout(function () { window.location.reload(); }, 700);
                        return;
                    }

                    // Rename succeeded: this row now *is* the new slug, so update
                    // the baseline or the next save would prompt again.
                    if (res.data && res.data.id) {
                        row.setAttribute('data-original-id', res.data.id);
                    }
                    rlmSyncShortcode(row);

                    var okMsg = (res.data && res.data.renamed) ? '✓ Renamed' : '✓ Saved';
                    setStatus(okMsg, 'is-ok');
                    if (btn) { btn.textContent = okMsg; }
                    setTimeout(function () {
                        setStatus('', '');
                        if (btn) { btn.textContent = 'Save Changes'; }
                    }, 2500);
                } else {
                    var msg = (res && res.data && res.data.message) ? res.data.message : 'Save failed.';
                    setStatus(msg, 'is-error');
                    if (btn) { btn.textContent = 'Save Changes'; }
                }
            })
            .catch(function (err) {
                if (btn) { btn.disabled = false; btn.textContent = 'Save Changes'; }
                console.error('[RLM] Fetch error:', err);
                setStatus('Network error.', 'is-error');
            });
        }
        // expose to the click handler above
        window.rlmSaveSection = rlmSaveSection;

        /* ---- Save All Sections ---- */
        var saveAllBtn = document.getElementById('rlm-save-all');
        if (saveAllBtn) {
            saveAllBtn.addEventListener('click', function () {
                var rows = wrap ? wrap.querySelectorAll('.rlm-instance') : [];
                if (!rows.length) { return; }
                saveAllBtn.disabled = true;
                var orig = saveAllBtn.textContent;
                saveAllBtn.textContent = 'Saving all…';
                rows.forEach(function (r) { rlmSaveSection(r, null); });
                setTimeout(function () {
                    saveAllBtn.disabled = false;
                    saveAllBtn.textContent = orig;
                }, 1500);
            });
        }

        /* ---- Export: download as .json file ---- */
        var dlBtn = document.getElementById('rlm-download-export');
        if (dlBtn) {
            dlBtn.addEventListener('click', function () {
                var text = document.getElementById('rlm-export').value || '{}';
                var blob = new Blob([text], { type: 'application/json' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                var stamp = new Date().toISOString().slice(0, 10);
                a.href = url;
                a.download = 'reusable-load-more-' + stamp + '.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });
        }

        /* ---- Import: read uploaded file into the textarea ---- */
        var importFile = document.getElementById('rlm-import-file');
        var importText = document.getElementById('rlm-import-text');
        if (importFile && importText) {
            importFile.addEventListener('change', function () {
                var file = this.files && this.files[0];
                if (!file) { return; }
                var reader = new FileReader();
                reader.onload = function (ev) {
                    importText.value = ev.target.result;
                    try { JSON.parse(ev.target.result); }
                    catch (err) { alert('That file is not valid JSON.'); }
                };
                reader.readAsText(file);
            });
        }

        /* Warn before leaving the page if any section has unsaved changes. */
        window.addEventListener('beforeunload', function (e) {
            var dirty = document.querySelector('.rlm-instance[data-dirty="1"]');
            if (dirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

    });
})();
