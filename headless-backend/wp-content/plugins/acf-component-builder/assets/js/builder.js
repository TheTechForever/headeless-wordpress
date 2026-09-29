/**
 * ACF Component Builder — Visual Template Builder (Phase 2).
 *
 * A dependency-light editor over the JSON node tree consumed by the PHP
 * Renderer. It provides:
 *   - a nested structure editor (add / nest / move / delete nodes),
 *   - a per-node inspector with responsive controls, and
 *   - a live, device-width preview rendered server-side over AJAX.
 *
 * Drag & drop and a reusable component library are intentionally deferred to
 * Phase 3; this phase uses explicit move/add controls.
 */
(function () {
    'use strict';

    var CFG = window.ACB_BUILDER || {};
    var wrap = document.querySelector('.acb-builder-wrap');
    if (!wrap || !wrap.dataset.slug) { return; }

    // ---- Node type metadata -------------------------------------------------
    var CONTAINERS = { row: 1, column: 1, container: 1, html: 1, repeater: 1, group: 1 };
    var LABELS = {
        row: 'Row', column: 'Column', container: 'Container', html: 'HTML block',
        spacer: 'Spacer', divider: 'Divider', field: 'Field',
        repeater: 'Repeater', group: 'Group'
    };
    var ADDABLE = ['row', 'column', 'container', 'html', 'field', 'repeater', 'group', 'spacer', 'divider'];

    // ---- State --------------------------------------------------------------
    var state = {
        id: parseInt(wrap.dataset.templateId, 10) || 0,
        slug: wrap.dataset.slug,
        title: '',
        engine: 'bootstrap',
        tree: { layout_engine: 'bootstrap', settings: { class: '', id: '', container: '' }, children: [] },
        fields: [],
        selected: null,      // array path to selected node, e.g. [0,1]
        previewPost: 0
    };

    var el = {
        title: document.getElementById('acb-tpl-title'),
        engine: document.getElementById('acb-tpl-engine'),
        container: document.getElementById('acb-tpl-container'),
        sectionClass: document.getElementById('acb-tpl-class'),
        tree: document.getElementById('acb-tree'),
        inspector: document.getElementById('acb-inspector'),
        addRootType: document.getElementById('acb-add-root-type'),
        addRoot: document.getElementById('acb-add-root'),
        save: document.getElementById('acb-tpl-save'),
        reset: document.getElementById('acb-tpl-reset'),
        status: document.getElementById('acb-save-status'),
        previewPost: document.getElementById('acb-preview-post'),
        previewFrame: document.getElementById('acb-preview-frame'),
        previewNote: document.getElementById('acb-preview-note'),
        previewRefresh: document.getElementById('acb-preview-refresh'),
        deviceBtns: document.querySelectorAll('.acb-device-toggle button')
    };
    if (!el.tree) { return; } // component not selected

    // Phase 3 state.
    var dragSrc = null;                 // node object being dragged
    var modal = document.getElementById('acb-modal');
    var modalTitle = document.getElementById('acb-modal-title');
    var modalBody = document.getElementById('acb-modal-body');

    // ---- AJAX helper --------------------------------------------------------
    function ajax(action, data) {
        var body = new URLSearchParams();
        body.set('action', action);
        body.set('nonce', CFG.nonce);
        Object.keys(data || {}).forEach(function (k) { body.set(k, data[k]); });
        return fetch(CFG.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    // ---- Tree path utilities -----------------------------------------------
    function nodeAt(path) {
        var node = state.tree;
        for (var i = 0; i < path.length; i++) {
            node = (node.children || [])[path[i]];
            if (!node) { return null; }
        }
        return node;
    }
    function parentOf(path) {
        if (!path.length) { return null; }
        return nodeAt(path.slice(0, -1)) || state.tree;
    }
    function samePath(a, b) {
        return a && b && a.length === b.length && a.every(function (v, i) { return v === b[i]; });
    }

    function makeNode(type) {
        var n = { type: type, class: '', id: '' };
        if (CONTAINERS[type]) { n.children = []; }
        if (type === 'row' || type === 'column' || type === 'container') { n.settings = {}; }
        if (type === 'container') { n.settings = { container: 'container' }; }
        if (type === 'html') { n.tag = 'div'; }
        if (type === 'spacer') { n.height = 24; }
        if (type === 'field') { n.field = ''; n.render = 'text'; }
        if (type === 'repeater' || type === 'group') { n.field = ''; }
        return n;
    }

    // ---- Rendering the tree -------------------------------------------------
    function render() {
        el.tree.innerHTML = '';
        (state.tree.children || []).forEach(function (child, i) {
            el.tree.appendChild(renderNode(child, [i]));
        });
        if (!state.tree.children || !state.tree.children.length) {
            var li = document.createElement('li');
            li.className = 'acb-tree-empty';
            li.textContent = 'Empty — add a Row or Container to begin.';
            el.tree.appendChild(li);
        }
    }

    function renderNode(node, path) {
        var li = document.createElement('li');
        li.className = 'acb-node acb-node--' + node.type;
        if (samePath(path, state.selected)) { li.classList.add('is-selected'); }

        var head = document.createElement('div');
        head.className = 'acb-node-head';
        head.draggable = true;
        head.addEventListener('dragstart', function (e) {
            dragSrc = node;
            li.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', 'acb-node'); } catch (_) {}
        });
        head.addEventListener('dragend', function () {
            dragSrc = null; li.classList.remove('is-dragging'); clearDropCues();
        });
        head.addEventListener('dragover', function (e) {
            if (!dragSrc || dragSrc === node) { return; }
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            setDropCue(head, zoneFor(e, head, node));
        });
        head.addEventListener('dragleave', function () {
            head.classList.remove('acb-drop-before', 'acb-drop-after', 'acb-drop-inside');
        });
        head.addEventListener('drop', function (e) {
            if (!dragSrc) { return; }
            e.preventDefault(); e.stopPropagation();
            var mode = zoneFor(e, head, node);
            var src = dragSrc; dragSrc = null; clearDropCues();
            dropNode(src, node, mode);
        });

        var label = document.createElement('button');
        label.type = 'button';
        label.className = 'acb-node-label';
        var meta = node.type === 'field' && node.field ? ' · ' + node.field
                 : (node.type === 'repeater' || node.type === 'group') && node.field ? ' · ' + node.field
                 : '';
        label.innerHTML = '<span class="acb-node-type">' + (LABELS[node.type] || node.type) + '</span>' +
                          '<span class="acb-node-meta">' + esc(meta) + '</span>';
        label.addEventListener('click', function () { select(path); });
        head.appendChild(label);

        var ctrls = document.createElement('div');
        ctrls.className = 'acb-node-ctrls';
        ctrls.appendChild(iconBtn('arrow-up-alt2', 'Move up', function () { move(path, -1); }));
        ctrls.appendChild(iconBtn('arrow-down-alt2', 'Move down', function () { move(path, 1); }));
        if (CONTAINERS[node.type]) {
            ctrls.appendChild(iconBtn('plus-alt2', 'Add child', function () { addChild(path); }));
        }
        ctrls.appendChild(iconBtn('star-filled', 'Save as block', function () { openBlockSave(node); }));
        ctrls.appendChild(iconBtn('trash', 'Delete', function () { removeNode(path); }));
        head.appendChild(ctrls);
        li.appendChild(head);

        if (CONTAINERS[node.type] && node.children) {
            var ul = document.createElement('ul');
            ul.className = 'acb-tree acb-tree--nested';
            node.children.forEach(function (c, i) {
                ul.appendChild(renderNode(c, path.concat(i)));
            });
            li.appendChild(ul);
        }
        return li;
    }

    function iconBtn(icon, title, cb) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'acb-icon-btn';
        b.title = title;
        b.setAttribute('aria-label', title);
        b.innerHTML = '<span class="dashicons dashicons-' + icon + '"></span>';
        b.addEventListener('click', function (e) { e.stopPropagation(); cb(); });
        return b;
    }

    // ---- Structure mutations ------------------------------------------------
    function addChild(path) {
        var node = nodeAt(path);
        if (!node.children) { node.children = []; }
        node.children.push(makeNode('field'));
        select(path.concat(node.children.length - 1));
        changed();
    }
    function addRoot(type) {
        state.tree.children.push(makeNode(type));
        select([state.tree.children.length - 1]);
        changed();
    }
    function move(path, dir) {
        var parent = parentOf(path);
        var idx = path[path.length - 1];
        var arr = parent.children;
        var to = idx + dir;
        if (to < 0 || to >= arr.length) { return; }
        var tmp = arr[idx]; arr[idx] = arr[to]; arr[to] = tmp;
        var np = path.slice(0, -1).concat(to);
        state.selected = np;
        changed();
    }
    function removeNode(path) {
        if (!window.confirm(CFG.i18n.confirmDel)) { return; }
        var parent = parentOf(path);
        parent.children.splice(path[path.length - 1], 1);
        state.selected = null;
        changed();
    }
    function select(path) {
        state.selected = path;
        render();
        renderInspector();
    }

    // ---- Inspector ----------------------------------------------------------
    function renderInspector() {
        var box = el.inspector;
        box.innerHTML = '';
        if (!state.selected) {
            box.innerHTML = '<p class="acb-inspector-empty">Select a node to edit its settings.</p>';
            return;
        }
        var node = nodeAt(state.selected);
        if (!node) { box.innerHTML = '<p class="acb-inspector-empty">Node not found.</p>'; return; }

        var h = document.createElement('h3');
        h.textContent = LABELS[node.type] || node.type;
        box.appendChild(h);

        // Common: class + id (not for spacer/divider id-less simplicity).
        if (node.type !== 'spacer' && node.type !== 'divider') {
            box.appendChild(textRow('CSS classes', node.class || '', function (v) { node.class = v; changed(); },
                'Space-separated CSS classes added to this node\'s wrapper. Use your theme or utility classes here.'));
            box.appendChild(textRow('CSS id', node.id || '', function (v) { node.id = v; changed(); },
                'Optional unique id for this node (for anchor links or custom CSS).'));
        }

        if (node.type === 'field') { inspectorField(box, node); }
        if (node.type === 'repeater' || node.type === 'group') { inspectorLoop(box, node); }
        if (node.type === 'html') { inspectorHtml(box, node); }
        if (node.type === 'spacer') {
            box.appendChild(numberRow('Height (px)', node.height || 24, function (v) { node.height = v; changed(); }));
        }
        if (node.type === 'row') { responsiveRow(box, node); }
        if (node.type === 'column') { responsiveColumn(box, node); }
        if (node.type === 'container') {
            node.settings = node.settings || {};
            box.appendChild(selectRow('Container', node.settings.container || 'container',
                [['container', 'container'], ['container-fluid', 'container-fluid']],
                function (v) { node.settings.container = v; changed(); }));
        }
    }

    function inspectorField(box, node) {
        var opts = [['', '— pick a field —']];
        flatFieldOptions(state.fields, opts);
        box.appendChild(selectRow('ACF field', node.field || '', opts, function (v) {
            node.field = v;
            var f = findField(v);
            if (f && f.render) { node.render = f.render; }
            changed(); renderInspector();
        }, 'Which ACF field supplies this node\'s content. Sub-fields of a repeater/group appear indented.'));
        var rends = (CFG.renderers || ['text']).map(function (r) { return [r, r]; });
        box.appendChild(selectRow('Render as', node.render || 'text', rends, function (v) {
            node.render = v; changed(); renderInspector();
        }, 'How the field value is output: text, heading, wysiwyg (safe HTML), image, link, or gallery.'));
        if (node.render === 'heading') {
            var tags = (CFG.headTags || ['h2']).map(function (t) { return [t, t]; });
            box.appendChild(selectRow('Heading tag', node.tag || 'h2', tags, function (v) { node.tag = v; changed(); }));
        }
        if (node.render === 'link') {
            box.appendChild(textRow('Link text (optional)', node.link_text || '', function (v) { node.link_text = v; changed(); }));
        }
    }

    function inspectorLoop(box, node) {
        var opts = [['', '— pick a field —']];
        flatFieldOptions(state.fields, opts, true);
        box.appendChild(selectRow('Loop field', node.field || '', opts, function (v) { node.field = v; changed(); render(); }));
        var hint = document.createElement('p');
        hint.className = 'acb-hint';
        hint.textContent = 'Children render once per row. Use Field nodes with the row\'s sub-field names.';
        box.appendChild(hint);
    }

    function inspectorHtml(box, node) {
        var tags = (CFG.htmlTags || ['div']).map(function (t) { return [t, t]; });
        box.appendChild(selectRow('Tag', node.tag || 'div', tags, function (v) { node.tag = v; changed(); }));
    }

    // ---- Responsive controls ------------------------------------------------
    function responsiveRow(box, node) {
        node.settings = node.settings || {};
        var s = node.settings;
        if (state.engine === 'bootstrap') {
            box.appendChild(selectRow('Gutter (0–5)', str(s.gutter), numRange(0, 5), function (v) { setNum(s, 'gutter', v); }));
            box.appendChild(selectRow('Justify', s.justify || '', justifyOpts(), function (v) { s.justify = v; changed(); }));
            box.appendChild(selectRow('Align', s.align || '', alignOpts(), function (v) { s.align = v; changed(); }));
        } else if (state.engine === 'flex') {
            box.appendChild(selectRow('Direction', s.direction || 'row', ['row', 'row-reverse', 'column', 'column-reverse'].map(dup), function (v) { s.direction = v; changed(); }));
            box.appendChild(selectRow('Wrap', s.wrap || 'wrap', ['wrap', 'nowrap', 'wrap-reverse'].map(dup), function (v) { s.wrap = v; changed(); }));
            box.appendChild(selectRow('Justify', s.justify || '', justifyOpts(), function (v) { s.justify = v; changed(); }));
            box.appendChild(selectRow('Align', s.align || '', alignOpts(), function (v) { s.align = v; changed(); }));
            box.appendChild(textRow('Gap (e.g. 16px)', s.gap || '', function (v) { s.gap = v; changed(); }));
        } else { // grid
            box.appendChild(fieldset('Columns per breakpoint', [
                selectRow('Desktop', str(s.columns), numRange(1, 12), function (v) { setNum(s, 'columns', v); }),
                selectRow('Tablet', str(s.tablet), numRange(1, 12), function (v) { setNum(s, 'tablet', v); }),
                selectRow('Mobile', str(s.mobile), numRange(1, 12), function (v) { setNum(s, 'mobile', v); })
            ]));
            box.appendChild(textRow('Gap (e.g. 24px)', s.gap || '', function (v) { s.gap = v; changed(); }));
        }
    }

    function responsiveColumn(box, node) {
        node.settings = node.settings || {};
        var s = node.settings;
        if (state.engine === 'bootstrap') {
            box.appendChild(fieldset('Width per breakpoint (1–12)', [
                selectRow('xs', str(s.xs), numRange(1, 12, true), function (v) { setNum(s, 'xs', v); }),
                selectRow('sm', str(s.sm), numRange(1, 12, true), function (v) { setNum(s, 'sm', v); }),
                selectRow('md', str(s.md), numRange(1, 12, true), function (v) { setNum(s, 'md', v); }),
                selectRow('lg', str(s.lg), numRange(1, 12, true), function (v) { setNum(s, 'lg', v); }),
                selectRow('xl', str(s.xl), numRange(1, 12, true), function (v) { setNum(s, 'xl', v); })
            ]));
            box.appendChild(selectRow('Offset (md)', str(s.offset_md), numRange(0, 11, true), function (v) { setNum(s, 'offset_md', v); }));
            box.appendChild(selectRow('Order', str(s.order), numRange(0, 12, true), function (v) { setNum(s, 'order', v); }));
            box.appendChild(selectRow('Align self', s.align_self || '', alignOpts(), function (v) { s.align_self = v; changed(); }));
        } else if (state.engine === 'flex') {
            box.appendChild(selectRow('Grow', str(s.grow), numRange(0, 12, true), function (v) { setNum(s, 'grow', v); }));
            box.appendChild(textRow('Basis (e.g. 50% / auto)', s.basis || '', function (v) { s.basis = v; changed(); }));
            box.appendChild(selectRow('Align self', s.align_self || '', alignOpts(), function (v) { s.align_self = v; changed(); }));
        } else {
            var hint = document.createElement('p');
            hint.className = 'acb-hint';
            hint.textContent = 'Grid places children automatically; set column counts on the parent Row.';
            box.appendChild(hint);
        }
    }

    // ---- Small form builders ------------------------------------------------
    function row(labelText, control, tip) {
        var w = document.createElement('label');
        w.className = 'acb-field';
        var s = document.createElement('span');
        s.className = 'acb-field-label';
        s.textContent = labelText;
        if (tip) {
            var help = document.createElement('button');
            help.type = 'button';
            help.className = 'acb-help';
            help.tabIndex = 0;
            help.setAttribute('aria-label', 'Help');
            help.setAttribute('data-acb-tip', tip);
            help.innerHTML = '<span class="dashicons dashicons-editor-help"></span>';
            s.appendChild(help);
        }
        w.appendChild(s);
        w.appendChild(control);
        return w;
    }
    function textRow(labelText, value, onInput, tip) {
        var i = document.createElement('input');
        i.type = 'text'; i.value = value;
        i.addEventListener('input', function () { onInput(i.value); });
        return row(labelText, i, tip);
    }
    function numberRow(labelText, value, onInput, tip) {
        var i = document.createElement('input');
        i.type = 'number'; i.value = value;
        i.addEventListener('input', function () { onInput(parseInt(i.value, 10) || 0); });
        return row(labelText, i, tip);
    }
    function selectRow(labelText, value, options, onChange, tip) {
        var sel = document.createElement('select');
        options.forEach(function (o) {
            var op = document.createElement('option');
            op.value = o[0]; op.textContent = o[1];
            if (String(o[0]) === String(value)) { op.selected = true; }
            sel.appendChild(op);
        });
        sel.addEventListener('change', function () { onChange(sel.value); });
        return row(labelText, sel, tip);
    }
    function fieldset(legendText, rows) {
        var fs = document.createElement('fieldset');
        fs.className = 'acb-fieldset';
        var lg = document.createElement('legend');
        lg.textContent = legendText;
        fs.appendChild(lg);
        rows.forEach(function (r) { fs.appendChild(r); });
        return fs;
    }

    // ---- Option helpers -----------------------------------------------------
    function numRange(min, max, blank) {
        var arr = [];
        if (blank) { arr.push(['', '—']); }
        for (var i = min; i <= max; i++) { arr.push([i, i]); }
        return arr;
    }
    function justifyOpts() { return [['', '—'], ['start', 'start'], ['center', 'center'], ['end', 'end'], ['between', 'between'], ['around', 'around'], ['evenly', 'evenly']]; }
    function alignOpts() { return [['', '—'], ['start', 'start'], ['center', 'center'], ['end', 'end'], ['stretch', 'stretch'], ['baseline', 'baseline']]; }
    function dup(v) { return [v, v]; }
    function str(v) { return (v === undefined || v === null) ? '' : String(v); }
    function setNum(obj, key, v) { if (v === '') { delete obj[key]; } else { obj[key] = parseInt(v, 10); } changed(); }

    function flatFieldOptions(fields, out, loopOnly) {
        (fields || []).forEach(function (f) {
            if (!loopOnly || f.is_loop) { out.push([f.name, f.label + ' [' + f.type + ']']); }
            if (f.sub_fields) { flatFieldOptions(f.sub_fields, out, loopOnly); }
        });
    }
    function findField(name) {
        var found = null;
        (function walk(list) {
            (list || []).forEach(function (f) {
                if (f.name === name && !found) { found = f; }
                if (f.sub_fields) { walk(f.sub_fields); }
            });
        })(state.fields);
        return found;
    }
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]; }); }

    // ---- Toolbar bindings ---------------------------------------------------
    el.engine.addEventListener('change', function () {
        state.engine = el.engine.value;
        state.tree.layout_engine = state.engine;
        renderInspector(); changed();
    });
    el.container.addEventListener('change', function () {
        state.tree.settings.container = el.container.value; changed();
    });
    el.sectionClass.addEventListener('input', function () {
        state.tree.settings.class = el.sectionClass.value; schedulePreview();
    });
    el.title.addEventListener('input', function () { state.title = el.title.value; });

    // Populate root add-type select.
    ADDABLE.forEach(function (t) {
        var o = document.createElement('option'); o.value = t; o.textContent = LABELS[t] || t;
        el.addRootType.appendChild(o);
    });
    el.addRoot.addEventListener('click', function () { addRoot(el.addRootType.value); });

    el.save.addEventListener('click', save);
    el.reset.addEventListener('click', function () {
        if (window.confirm('Discard changes and reload the auto-generated structure?')) { load(true); }
    });

    el.previewRefresh.addEventListener('click', function () { doPreview(); });
    el.previewPost.addEventListener('change', function () {
        state.previewPost = parseInt(el.previewPost.value, 10) || 0; doPreview();
    });
    el.deviceBtns.forEach(function (b) {
        b.addEventListener('click', function () {
            el.deviceBtns.forEach(function (x) { x.classList.remove('is-active'); });
            b.classList.add('is-active');
            el.previewFrame.style.width = b.dataset.w;
        });
    });

    // ---- Persistence & preview ---------------------------------------------
    function changed() { render(); schedulePreview(); }

    var previewTimer = null;
    function schedulePreview() {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(doPreview, 400);
    }

    function save() {
        setStatus(CFG.i18n.previewing ? 'Saving…' : 'Saving…');
        syncToolbarToTree();
        ajax('acb_save_template', {
            id: state.id,
            title: state.title || state.slug,
            slug: state.slug,
            tree: JSON.stringify(state.tree)
        }).then(function (res) {
            if (res && res.success) {
                state.id = res.data.id;
                wrap.dataset.templateId = String(state.id);
                setStatus(CFG.i18n.saved, 'ok');
            } else {
                setStatus((res && res.data && res.data.message) || CFG.i18n.saveError, 'bad');
            }
        }).catch(function () { setStatus(CFG.i18n.saveError, 'bad'); });
    }

    function doPreview() {
        syncToolbarToTree();
        el.previewNote.textContent = CFG.i18n.previewing;
        ajax('acb_preview', {
            slug: state.slug,
            post_id: state.previewPost,
            tree: JSON.stringify(state.tree)
        }).then(function (res) {
            if (res && res.success) {
                writeFrame(res.data.html, res.data.engine);
                el.previewNote.textContent = state.previewPost ? '' : CFG.i18n.noData;
            } else {
                el.previewNote.textContent = (res && res.data && res.data.message) || 'Preview failed.';
            }
        }).catch(function () { el.previewNote.textContent = 'Preview failed.'; });
    }

    function writeFrame(html, engine) {
        var css = [];
        if (engine === 'bootstrap' && CFG.bootstrapCss) {
            css.push('<link rel="stylesheet" href="' + CFG.bootstrapCss + '">');
        }
        if (CFG.frontendCss) {
            css.push('<link rel="stylesheet" href="' + CFG.frontendCss + '">');
        }
        var doc = '<!doctype html><html><head><meta charset="utf-8">' +
            '<meta name="viewport" content="width=device-width, initial-scale=1">' +
            css.join('') +
            '<base target="_blank">' +
            '<style>body{margin:0;padding:16px;font-family:system-ui,sans-serif;color:#1e1e1e;} img{max-width:100%;height:auto;}</style>' +
            '</head><body>' + (html || '') + '</body></html>';
        el.previewFrame.srcdoc = doc;
    }

    function syncToolbarToTree() {
        state.tree.layout_engine = state.engine;
        state.tree.settings = state.tree.settings || {};
        state.tree.settings.container = el.container.value;
        state.tree.settings.class = el.sectionClass.value;
    }

    function setStatus(msg, kind) {
        el.status.textContent = msg;
        el.status.className = 'acb-save-status' + (kind ? ' is-' + kind : '');
        if (kind === 'ok') { setTimeout(function () { el.status.textContent = ''; }, 2500); }
    }

    // ---- Phase 3: drag & drop ----------------------------------------------
    function zoneFor(e, head, node) {
        var r = head.getBoundingClientRect();
        var y = e.clientY - r.top;
        var h = r.height || 1;
        if (CONTAINERS[node.type]) {
            if (y < h * 0.28) { return 'before'; }
            if (y > h * 0.72) { return 'after'; }
            return 'inside';
        }
        return y < h / 2 ? 'before' : 'after';
    }
    function setDropCue(head, mode) {
        clearDropCues();
        head.classList.add('acb-drop-' + mode);
    }
    function clearDropCues() {
        Array.prototype.forEach.call(
            document.querySelectorAll('.acb-node-head'),
            function (h) { h.classList.remove('acb-drop-before', 'acb-drop-after', 'acb-drop-inside'); }
        );
    }
    function locate(target) {
        var res = null;
        (function walk(arr) {
            for (var i = 0; i < arr.length && !res; i++) {
                if (arr[i] === target) { res = { arr: arr, idx: i }; return; }
                if (arr[i].children) { walk(arr[i].children); }
            }
        })(state.tree.children);
        return res;
    }
    function containsNode(root, target) {
        if (root === target) { return true; }
        var kids = root.children || [];
        for (var i = 0; i < kids.length; i++) {
            if (containsNode(kids[i], target)) { return true; }
        }
        return false;
    }
    function dropNode(src, dest, mode) {
        if (src === dest || containsNode(src, dest)) { return; } // no self / descendant drops
        var from = locate(src);
        if (!from) { return; }
        from.arr.splice(from.idx, 1);
        if (mode === 'inside') {
            dest.children = dest.children || [];
            dest.children.push(src);
        } else {
            var to = locate(dest);
            if (!to) { from.arr.push(src); changed(); return; }
            to.arr.splice(to.idx + (mode === 'after' ? 1 : 0), 0, src);
        }
        state.selected = null;
        changed();
    }

    // ---- Phase 3: modal system ---------------------------------------------
    function openModal(title, contentEl) {
        modalTitle.textContent = title;
        modalBody.innerHTML = '';
        modalBody.appendChild(contentEl);
        modal.hidden = false;
    }
    function closeModal() { modal.hidden = true; modalBody.innerHTML = ''; }
    if (modal) {
        Array.prototype.forEach.call(modal.querySelectorAll('[data-acb-close]'), function (b) {
            b.addEventListener('click', closeModal);
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) { closeModal(); } });
    }

    // ---- Phase 3: save node as reusable block ------------------------------
    function openBlockSave(node) {
        var form = document.createElement('div');
        form.className = 'acb-modal-form';
        var name = inputEl('text', LABELS[node.type] + ' block');
        var cat = inputEl('text', '');
        var desc = document.createElement('textarea'); desc.rows = 3;
        form.appendChild(fieldWrap('Block name', name));
        form.appendChild(fieldWrap('Category (optional)', cat));
        form.appendChild(fieldWrap('Description (optional)', desc));
        var save = document.createElement('button');
        save.className = 'button button-primary';
        save.textContent = 'Save to library';
        save.addEventListener('click', function () {
            ajax('acb_save_block', {
                id: 0,
                title: name.value || LABELS[node.type],
                category: cat.value,
                description: desc.value,
                node: JSON.stringify(node)
            }).then(function (res) {
                closeModal();
                setStatus(res && res.success ? CFG.i18n.blockSaved : CFG.i18n.saveError, res && res.success ? 'ok' : 'bad');
            });
        });
        form.appendChild(save);
        openModal('Save as block', form);
    }

    // ---- Phase 3: block library --------------------------------------------
    function openLibrary() {
        var box = document.createElement('div');
        box.className = 'acb-library';
        box.innerHTML = '<p class="acb-hint">Insert a saved block into the selected container (or at the end of the template).</p>';
        var list = document.createElement('div');
        list.className = 'acb-library-list';
        list.textContent = 'Loading…';
        box.appendChild(list);
        openModal('Block library', box);

        ajax('acb_list_blocks', {}).then(function (res) {
            list.innerHTML = '';
            var blocks = (res && res.success && res.data.blocks) || [];
            if (!blocks.length) { list.innerHTML = '<p>' + esc(CFG.i18n.noBlocks) + '</p>'; return; }
            blocks.forEach(function (b) {
                var row = document.createElement('div');
                row.className = 'acb-library-item';
                row.innerHTML = '<div class="acb-library-meta"><strong>' + esc(b.title) + '</strong>' +
                    (b.category ? ' <span class="acb-badge">' + esc(b.category) + '</span>' : '') +
                    '<span class="acb-library-type">' + esc(b.type) + '</span>' +
                    (b.description ? '<div class="acb-library-desc">' + esc(b.description) + '</div>' : '') + '</div>';
                var actions = document.createElement('div');
                actions.className = 'acb-library-actions';
                var ins = document.createElement('button');
                ins.className = 'button button-small button-primary';
                ins.textContent = 'Insert';
                ins.addEventListener('click', function () { insertBlock(b.id); });
                var del = document.createElement('button');
                del.className = 'button button-small';
                del.textContent = 'Delete';
                del.addEventListener('click', function () {
                    if (!window.confirm('Delete this block from the library?')) { return; }
                    ajax('acb_delete_block', { id: b.id }).then(openLibrary);
                });
                actions.appendChild(ins); actions.appendChild(del);
                row.appendChild(actions);
                list.appendChild(row);
            });
        });
    }
    function insertBlock(id) {
        ajax('acb_get_block', { id: id }).then(function (res) {
            if (!res || !res.success || !res.data.node) { return; }
            var node = res.data.node;
            var target = state.selected ? nodeAt(state.selected) : null;
            if (target && CONTAINERS[target.type]) {
                target.children = target.children || [];
                target.children.push(node);
            } else {
                state.tree.children.push(node);
            }
            closeModal();
            changed();
        });
    }

    // ---- Phase 3: version history ------------------------------------------
    function openVersions() {
        var box = document.createElement('div');
        if (!state.id) {
            box.innerHTML = '<p>' + esc(CFG.i18n.noVersions) + '</p>';
            openModal('Version history', box);
            return;
        }
        box.textContent = 'Loading…';
        openModal('Version history', box);
        ajax('acb_list_versions', { id: state.id }).then(function (res) {
            box.innerHTML = '';
            var versions = (res && res.success && res.data.versions) || [];
            if (!versions.length) { box.innerHTML = '<p>' + esc(CFG.i18n.noVersions) + '</p>'; return; }
            var ul = document.createElement('ul');
            ul.className = 'acb-versions';
            versions.forEach(function (v) {
                var li = document.createElement('li');
                li.innerHTML = '<span>' + esc(v.time) + ' · ' + esc(v.author) + '</span>';
                var btn = document.createElement('button');
                btn.className = 'button button-small';
                btn.textContent = 'Restore';
                btn.addEventListener('click', function () { restoreVersion(v.index); });
                li.appendChild(btn);
                ul.appendChild(li);
            });
            box.appendChild(ul);
        });
    }
    function restoreVersion(index) {
        ajax('acb_restore_version', { id: state.id, index: index }).then(function (res) {
            if (!res || !res.success) { return; }
            state.tree = normalizeTree(res.data.tree);
            applyToolbarFromTree();
            closeModal();
            render(); renderInspector(); doPreview();
            setStatus(CFG.i18n.restored, 'ok');
        });
    }

    // ---- Phase 3: import / export ------------------------------------------
    function exportTemplate() {
        if (!state.id) { setStatus(CFG.i18n.saveFirst, 'bad'); return; }
        ajax('acb_export_template', { id: state.id }).then(function (res) {
            if (!res || !res.success) { setStatus(CFG.i18n.saveFirst, 'bad'); return; }
            var blob = new Blob([JSON.stringify(res.data.payload, null, 2)], { type: 'application/json' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'acb-template-' + state.slug + '.json';
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
        });
    }
    function openImport() {
        var box = document.createElement('div');
        box.className = 'acb-modal-form';
        var file = inputEl('file', '');
        file.accept = 'application/json,.json';
        var ta = document.createElement('textarea'); ta.rows = 8; ta.placeholder = '…or paste exported JSON here';
        file.addEventListener('change', function () {
            var f = file.files && file.files[0];
            if (!f) { return; }
            var reader = new FileReader();
            reader.onload = function () { ta.value = String(reader.result || ''); };
            reader.readAsText(f);
        });
        box.appendChild(fieldWrap('Import file', file));
        box.appendChild(fieldWrap('JSON', ta));
        var go = document.createElement('button');
        go.className = 'button button-primary';
        go.textContent = 'Import as new template';
        go.addEventListener('click', function () {
            ajax('acb_import_template', { payload: ta.value }).then(function (res) {
                if (res && res.success) {
                    setStatus(CFG.i18n.imported, 'ok');
                    window.location = CFG.managerUrl.replace('page=acb-templates', 'page=acb-builder') + '&template=' + res.data.id;
                } else {
                    setStatus((res && res.data && res.data.message) || CFG.i18n.importErr, 'bad');
                }
            });
        });
        box.appendChild(go);
        openModal('Import template', box);
    }

    // Small helpers for modal forms.
    function inputEl(type, value) { var i = document.createElement('input'); i.type = type; if (value) { i.value = value; } return i; }
    function fieldWrap(labelText, ctrl) {
        var w = document.createElement('label'); w.className = 'acb-field';
        var s = document.createElement('span'); s.className = 'acb-field-label'; s.textContent = labelText;
        w.appendChild(s); w.appendChild(ctrl); return w;
    }

    // Subbar wiring.
    byId('acb-how-to', function (b) { b.addEventListener('click', openHowTo); });
    byId('acb-open-library', function (b) { b.addEventListener('click', openLibrary); });
    byId('acb-open-versions', function (b) { b.addEventListener('click', openVersions); });
    byId('acb-export-tpl', function (b) { b.addEventListener('click', exportTemplate); });
    byId('acb-import-tpl', function (b) { b.addEventListener('click', openImport); });
    function byId(id, cb) { var elx = document.getElementById(id); if (elx) { cb(elx); } }

    // ---- Phase 4: "How to display this" -------------------------------------
    function openHowTo() {
        var u = CFG.usage || {};
        var box = document.createElement('div');
        box.className = 'acb-usage';
        box.appendChild(usageRow(
            'Option 1 — Block (no code)',
            'Edit any page or post, add the “ACF Component” block, and pick this component in the sidebar. Save this template first so your design is applied.',
            u.block || 'ACF Component block', false
        ));
        box.appendChild(usageRow(
            'Option 2 — Shortcode',
            'Add a Shortcode block to your page and paste this. Uses the current page’s ACF data unless you add post_id="123".',
            u.shortcode || '', true
        ));
        box.appendChild(usageRow(
            'Option 3 — PHP (theme)',
            'For developers: call this inside The Loop in your theme template.',
            u.php || '', true
        ));
        var note = document.createElement('p');
        note.className = 'acb-hint';
        note.textContent = 'Remember: building a template only controls how this component looks. It appears on a page only once you display it with one of the options above.';
        box.appendChild(note);
        openModal('How to display this component', box);
    }
    function usageRow(title, desc, code, copyable) {
        var row = document.createElement('div');
        row.className = 'acb-usage-row';
        var h = document.createElement('h4'); h.textContent = title; row.appendChild(h);
        var p = document.createElement('p'); p.textContent = desc; row.appendChild(p);
        var wrap = document.createElement('div'); wrap.className = 'acb-usage-code';
        var c = document.createElement('code'); c.textContent = code; wrap.appendChild(c);
        if (copyable && code) {
            var btn = document.createElement('button');
            btn.className = 'acb-copy'; btn.setAttribute('data-copy', code); btn.textContent = 'Copy';
            wrap.appendChild(btn);
        }
        row.appendChild(wrap);
        return row;
    }

    // ---- Bootstrapping ------------------------------------------------------
    function applyToolbarFromTree() {
        state.engine = state.tree.layout_engine || 'bootstrap';
        el.engine.value = state.engine;
        el.container.value = (state.tree.settings && state.tree.settings.container) || '';
        el.sectionClass.value = (state.tree.settings && state.tree.settings.class) || '';
        el.title.value = state.title || '';
    }

    function load(seedOnly) {
        var payload = seedOnly ? { slug: state.slug } : (state.id ? { id: state.id } : { slug: state.slug });
        ajax('acb_load_template', payload).then(function (res) {
            if (!res || !res.success) { return; }
            state.id = seedOnly ? state.id : (res.data.id || 0);
            state.title = res.data.title || state.slug;
            state.slug = res.data.slug || state.slug;
            state.tree = normalizeTree(res.data.tree);
            applyToolbarFromTree();
            render(); renderInspector(); doPreview();
        });
    }

    function normalizeTree(tree) {
        tree = tree || {};
        return {
            layout_engine: tree.layout_engine || 'bootstrap',
            settings: tree.settings || { class: '', id: '', container: '' },
            children: tree.children || []
        };
    }

    function loadFields() {
        return ajax('acb_component_fields', { slug: state.slug }).then(function (res) {
            if (res && res.success) { state.fields = res.data.fields || []; }
        });
    }

    function loadPreviewPosts() {
        return ajax('acb_preview_posts', {}).then(function (res) {
            if (!res || !res.success) { return; }
            el.previewPost.innerHTML = '';
            var first = document.createElement('option');
            first.value = ''; first.textContent = '— No content (structure only) —';
            el.previewPost.appendChild(first);
            (res.data.posts || []).forEach(function (p) {
                var o = document.createElement('option');
                o.value = p.id; o.textContent = p.label;
                el.previewPost.appendChild(o);
            });
            if (res.data.posts && res.data.posts.length) {
                el.previewPost.value = res.data.posts[0].id;
                state.previewPost = res.data.posts[0].id;
            }
        });
    }

    // Kick off.
    loadFields()
        .then(loadPreviewPosts)
        .then(function () { load(false); });
})();
