/* global contesPostGridData */
(function (wp) {
    if (!wp || !wp.hooks || !wp.compose || !wp.blockEditor) {
        return;
    }

    var addFilter = wp.hooks.addFilter;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;
    var TextControl = wp.components.TextControl;
    var Notice = wp.components.Notice;
    var Fragment = wp.element.Fragment;
    var __ = wp.i18n.__;

    var data = window.contesPostGridData || {};
    var defaultMode = data.defaultMode || 'locked';
    var defaultUrl = data.defaultUrl || '';

    var allowedBlocks = ['dahlia-blocks/post-grid'];

    var modeLabels = {
        locked: __('Bloquejat (blur + candau)', 'contes-subscription'),
        hidden: __('Ocult (no mostrar)', 'contes-subscription'),
        off: __('Desactivat', 'contes-subscription')
    };

    var addAttributes = function (settings, name) {
        if (allowedBlocks.indexOf(name) === -1) {
            return settings;
        }

        settings.attributes = Object.assign({}, settings.attributes, {
            contesLockMode: {
                type: 'string',
                default: 'default'
            },
            contesLockUseDefaultUrl: {
                type: 'boolean',
                default: true
            },
            contesLockCtaUrl: {
                type: 'string',
                default: ''
            },
            contesLockOnly: {
                type: 'boolean',
                default: false
            }
        });

        return settings;
    };

    addFilter(
        'blocks.registerBlockType',
        'contes-subscription/post-grid-attributes',
        addAttributes
    );

    var withPostGridControls = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            if (allowedBlocks.indexOf(props.name) === -1) {
                return wp.element.createElement(BlockEdit, props);
            }

            var attributes = props.attributes || {};
            var lockMode = attributes.contesLockMode || 'default';
            var useDefaultUrl = attributes.contesLockUseDefaultUrl !== false;
            var ctaUrl = attributes.contesLockCtaUrl || '';
            var lockOnly = !!attributes.contesLockOnly;

            var defaultLabel = modeLabels[defaultMode] || modeLabels.locked;

            return wp.element.createElement(
                Fragment,
                null,
                wp.element.createElement(BlockEdit, props),
                wp.element.createElement(
                    InspectorControls,
                    null,
                    wp.element.createElement(
                        PanelBody,
                        {
                            title: __('Contingut exclusiu', 'contes-subscription'),
                            initialOpen: true
                        },
                        wp.element.createElement(SelectControl, {
                            label: __('Mode de bloqueig', 'contes-subscription'),
                            value: lockMode,
                            options: [
                                {
                                    label: __('Per defecte', 'contes-subscription') + ' — ' + defaultLabel,
                                    value: 'default'
                                },
                                {
                                    label: modeLabels.locked,
                                    value: 'locked'
                                },
                                {
                                    label: modeLabels.hidden,
                                    value: 'hidden'
                                },
                                {
                                    label: modeLabels.off,
                                    value: 'off'
                                }
                            ],
                            onChange: function (value) {
                                props.setAttributes({ contesLockMode: value });
                            }
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: __('Usa la URL global de venda', 'contes-subscription'),
                            checked: useDefaultUrl,
                            onChange: function (value) {
                                props.setAttributes({ contesLockUseDefaultUrl: !!value });
                            }
                        }),
                        !useDefaultUrl
                            ? wp.element.createElement(TextControl, {
                                  label: __('URL CTA personalitzada', 'contes-subscription'),
                                  value: ctaUrl,
                                  placeholder: defaultUrl,
                                  onChange: function (value) {
                                      props.setAttributes({ contesLockCtaUrl: value });
                                  }
                              })
                            : null,
                        !useDefaultUrl && !ctaUrl
                            ? wp.element.createElement(
                                  Notice,
                                  { status: 'info', isDismissible: false },
                                  __('Si no indiques cap URL, es fara servir la global.', 'contes-subscription')
                              )
                            : null,
                        wp.element.createElement(ToggleControl, {
                            label: __('Mostra només contes bloquejats', 'contes-subscription'),
                            checked: lockOnly,
                            onChange: function (value) {
                                props.setAttributes({ contesLockOnly: !!value });
                            }
                        })
                    )
                )
            );
        };
    }, 'withPostGridControls');

    addFilter(
        'editor.BlockEdit',
        'contes-subscription/post-grid-controls',
        withPostGridControls
    );
})(window.wp);
