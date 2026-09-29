/**
 * "ACF Component" block — place any ACB component on a page/post with no code.
 *
 * Dynamic (server-rendered) block: the editor shows a live server preview via
 * ServerSideRender, and a dropdown in the sidebar chooses which component to
 * render. No JSX / build step — uses wp.element.createElement directly.
 */
(function (blocks, element, blockEditor, components, serverSideRender, i18n) {
    'use strict';

    var el = element.createElement;
    var __ = (i18n && i18n.__) ? i18n.__ : function (s) { return s; };
    var InspectorControls = blockEditor.InspectorControls;
    var useBlockProps = blockEditor.useBlockProps;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var Notice = components.Notice;
    var SSR = serverSideRender && (serverSideRender.default || serverSideRender);

    var data = window.ACB_BLOCK || { components: [], emptyText: '', noneText: '' };

    var options = [{ label: __('— Select a component —', 'acf-component-builder'), value: '' }];
    (data.components || []).forEach(function (c) {
        options.push({ label: c.label + ' (' + c.type + ')', value: c.slug });
    });

    blocks.registerBlockType('acb/component', {
        edit: function (props) {
            var slug = props.attributes.slug || '';
            var blockProps = useBlockProps ? useBlockProps() : {};

            var controls = el(
                InspectorControls, {},
                el(
                    PanelBody, { title: __('Component', 'acf-component-builder'), initialOpen: true },
                    (data.components && data.components.length)
                        ? el(SelectControl, {
                            label: __('Choose a component', 'acf-component-builder'),
                            value: slug,
                            options: options,
                            onChange: function (v) { props.setAttributes({ slug: v }); }
                        })
                        : el(Notice, { status: 'warning', isDismissible: false }, data.noneText || '')
                )
            );

            var body;
            if (!slug) {
                body = el('div', {
                    style: {
                        padding: '24px', border: '1px dashed #c3c4c7', borderRadius: '8px',
                        textAlign: 'center', color: '#646970', background: '#f6f7f7'
                    }
                }, data.emptyText || __('Select a component in the sidebar.', 'acf-component-builder'));
            } else if (SSR) {
                body = el(SSR, { block: 'acb/component', attributes: props.attributes });
            } else {
                body = el('div', {}, slug);
            }

            return el('div', blockProps, controls, body);
        },
        save: function () { return null; } // Dynamic: rendered by PHP.
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.serverSideRender,
    window.wp.i18n
);
