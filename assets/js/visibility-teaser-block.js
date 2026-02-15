/* global contesVisibilityTeaserData */
(function (wp) {
    if (!wp || !wp.blocks || !wp.element || !wp.blockEditor) {
        return;
    }

    var el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var InnerBlocks = wp.blockEditor.InnerBlocks;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelColorSettings = wp.blockEditor.PanelColorSettings;
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var TextControl = wp.components.TextControl;
    var TextareaControl = wp.components.TextareaControl;
    var RangeControl = wp.components.RangeControl;
    var SelectControl = wp.components.SelectControl;
    var Button = wp.components.Button;
    var ButtonGroup = wp.components.ButtonGroup;
    var CheckboxControl = wp.components.CheckboxControl;
    var Notice = wp.components.Notice;
    var __ = wp.i18n.__;

    var data = window.contesVisibilityTeaserData || {};
    var defaults = data.defaults || {};
    var levels = Array.isArray(data.levels) ? data.levels : [];
    var hasPmpro = !!data.hasPmpro;

    function toggleLevel(currentLevels, value, isChecked) {
        var next = currentLevels ? currentLevels.slice() : [];
        var stringValue = value + '';
        var hasValue = next.some(function (item) {
            return item + '' === stringValue;
        });

        if (isChecked && !hasValue) {
            next.push(stringValue);
        } else if (!isChecked && hasValue) {
            next = next.filter(function (item) {
                return item + '' !== stringValue;
            });
        }

        return next;
    }

    registerBlockType('contes/visibility-teaser', {
        title: __('Teaser de contingut (Membres)', 'contes-subscription'),
        description: __('Mostra un teaser del contingut restringit i convida a subscriure.', 'contes-subscription'),
        icon: 'visibility',
        category: 'pmpro',
        attributes: {
            invertRestrictions: {
                type: 'boolean',
                default: false
            },
            segment: {
                type: 'string',
                default: 'all'
            },
            levels: {
                type: 'array',
                default: []
            },
            useDefaultContent: {
                type: 'boolean',
                default: true
            },
            useDefaultStyle: {
                type: 'boolean',
                default: true
            },
            useDefaultLimit: {
                type: 'boolean',
                default: true
            },
            message: {
                type: 'string',
                default: ''
            },
            buttonLabel: {
                type: 'string',
                default: ''
            },
            buttonUrl: {
                type: 'string',
                default: ''
            },
            teaserHeight: {
                type: 'number',
                default: 120
            },
            teaserBlockCount: {
                type: 'number',
                default: 2
            },
            backgroundColor: {
                type: 'string',
                default: ''
            }
        },
        edit: function (props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            var useDefaultContent = attributes.useDefaultContent !== false;
            var useDefaultStyle = attributes.useDefaultStyle !== false;
            var useDefaultLimit = attributes.useDefaultLimit !== false;

            var previewMessage = useDefaultContent ? (defaults.message || '') : (attributes.message || '');
            var previewButtonLabel = useDefaultContent ? (defaults.button_label || '') : (attributes.buttonLabel || '');
            var previewHeight = useDefaultStyle ? (defaults.teaser_height || 120) : (attributes.teaserHeight || 120);
            var previewBg = useDefaultStyle ? (defaults.background_color || '#ffffff') : (attributes.backgroundColor || '#ffffff');
            var blockProps = useBlockProps({
                className: 'contes-visibility-teaser contes-visibility-teaser--editor',
                style: {
                    '--contes-teaser-height': (previewHeight || 120) + 'px',
                    '--contes-teaser-bg': previewBg || '#ffffff'
                }
            });

            var visibilityLabel = attributes.invertRestrictions
                ? __('Amaga el contingut de:', 'contes-subscription')
                : __('Mostra el contingut a:', 'contes-subscription');

            return [
                el(
                    InspectorControls,
                    { key: 'controls' },
                    el(
                        PanelBody,
                        { title: __('Visibilitat del contingut', 'contes-subscription'), initialOpen: true },
                        el(
                            ButtonGroup,
                            { className: 'contes-visibility-teaser__toggle' },
                            el(Button, {
                                icon: 'visibility',
                                variant: attributes.invertRestrictions ? 'secondary' : 'primary',
                                onClick: function () {
                                    setAttributes({ invertRestrictions: false });
                                }
                            }, __('Mostra', 'contes-subscription')),
                            el(Button, {
                                icon: 'hidden',
                                variant: attributes.invertRestrictions ? 'primary' : 'secondary',
                                onClick: function () {
                                    setAttributes({ invertRestrictions: true });
                                }
                            }, __('Amaga', 'contes-subscription'))
                        ),
                        el(SelectControl, {
                            label: visibilityLabel,
                            value: attributes.segment || 'all',
                            options: [
                                { label: __('Tots els membres', 'contes-subscription'), value: 'all' },
                                { label: __('Nivells específics', 'contes-subscription'), value: 'specific' },
                                { label: __('Usuaris loguejats', 'contes-subscription'), value: 'logged_in' }
                            ],
                            onChange: function (value) {
                                setAttributes({ segment: value, levels: [] });
                            }
                        }),
                        attributes.segment === 'specific'
                            ? el(
                                  'div',
                                  { className: 'contes-visibility-teaser__levels' },
                                  !hasPmpro
                                      ? el(Notice, { status: 'warning', isDismissible: false }, __('Cal PMPro per seleccionar nivells.', 'contes-subscription'))
                                      : null,
                                  hasPmpro && levels.length === 0
                                      ? el(Notice, { status: 'warning', isDismissible: false }, __('No hi ha nivells disponibles.', 'contes-subscription'))
                                      : null,
                                  levels.map(function (level) {
                                      return el(CheckboxControl, {
                                          key: level.value,
                                          label: level.label,
                                          checked: (attributes.levels || []).some(function (item) {
                                              return item + '' === level.value + '';
                                          }),
                                          onChange: function (isChecked) {
                                              var next = toggleLevel(attributes.levels || [], level.value, isChecked);
                                              setAttributes({ levels: next });
                                          }
                                      });
                                  })
                              )
                            : null
                    ),
                    el(
                        PanelBody,
                        { title: __('Missatge i botó', 'contes-subscription'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Usa el missatge per defecte', 'contes-subscription'),
                            checked: useDefaultContent,
                            onChange: function (value) {
                                setAttributes({ useDefaultContent: !!value });
                            }
                        }),
                        useDefaultContent
                            ? el(TextareaControl, {
                                  label: __('Missatge per defecte (lectura)', 'contes-subscription'),
                                  value: defaults.message || '',
                                  disabled: true
                              })
                            : el(TextareaControl, {
                                  label: __('Missatge personalitzat', 'contes-subscription'),
                                  value: attributes.message || '',
                                  onChange: function (value) {
                                      setAttributes({ message: value });
                                  }
                              }),
                        useDefaultContent
                            ? el(TextControl, {
                                  label: __('Botó per defecte (lectura)', 'contes-subscription'),
                                  value: defaults.button_label || '',
                                  disabled: true
                              })
                            : el(TextControl, {
                                  label: __('Etiqueta del botó', 'contes-subscription'),
                                  value: attributes.buttonLabel || '',
                                  onChange: function (value) {
                                      setAttributes({ buttonLabel: value });
                                  }
                              }),
                        useDefaultContent
                            ? el(TextControl, {
                                  label: __('URL per defecte (lectura)', 'contes-subscription'),
                                  value: defaults.button_url || '',
                                  disabled: true
                              })
                            : el(TextControl, {
                                  label: __('URL del botó', 'contes-subscription'),
                                  value: attributes.buttonUrl || '',
                                  onChange: function (value) {
                                      setAttributes({ buttonUrl: value });
                                  }
                              })
                    ),
                    el(
                        PanelBody,
                        { title: __('Teaser', 'contes-subscription'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Usa límit de blocs per defecte', 'contes-subscription'),
                            checked: useDefaultLimit,
                            onChange: function (value) {
                                setAttributes({ useDefaultLimit: !!value });
                            }
                        }),
                        useDefaultLimit
                            ? el(RangeControl, {
                                  label: __('Blocs visibles (lectura)', 'contes-subscription'),
                                  value: defaults.teaser_block_count || 2,
                                  min: 1,
                                  max: 10,
                                  disabled: true
                              })
                            : el(RangeControl, {
                                  label: __('Blocs visibles', 'contes-subscription'),
                                  value: attributes.teaserBlockCount || 2,
                                  min: 1,
                                  max: 10,
                                  onChange: function (value) {
                                      setAttributes({ teaserBlockCount: value });
                                  }
                              }),
                        el(ToggleControl, {
                            label: __('Usa estil per defecte', 'contes-subscription'),
                            checked: useDefaultStyle,
                            onChange: function (value) {
                                setAttributes({ useDefaultStyle: !!value });
                            }
                        }),
                        useDefaultStyle
                            ? el(RangeControl, {
                                  label: __('Alçada visible (lectura)', 'contes-subscription'),
                                  value: defaults.teaser_height || 120,
                                  min: 60,
                                  max: 400,
                                  disabled: true
                              })
                            : el(RangeControl, {
                                  label: __('Alçada visible (px)', 'contes-subscription'),
                                  value: attributes.teaserHeight || 120,
                                  min: 60,
                                  max: 400,
                                  onChange: function (value) {
                                      setAttributes({ teaserHeight: value });
                                  }
                              }),
                        useDefaultStyle
                            ? el(TextControl, {
                                  label: __('Color de fons per defecte (lectura)', 'contes-subscription'),
                                  value: defaults.background_color || '#ffffff',
                                  disabled: true
                              })
                            : el(
                                  PanelColorSettings,
                                  {
                                      title: __('Color de fons', 'contes-subscription'),
                                      disableCustomColors: false,
                                      colorSettings: [
                                          {
                                              label: __('Color de fons', 'contes-subscription'),
                                              value: attributes.backgroundColor || '',
                                              onChange: function (value) {
                                                  setAttributes({ backgroundColor: value });
                                              }
                                          }
                                      ]
                                  }
                              )
                    )
                ),
                el(
                    'div',
                    blockProps,
                    el(
                        'div',
                        { className: 'contes-visibility-teaser__content-wrap' },
                        el('div', { className: 'contes-visibility-teaser__content' }, el(InnerBlocks)),
                        el('div', { className: 'contes-visibility-teaser__fade', 'aria-hidden': 'true' })
                    ),
                    el(
                        'div',
                        { className: 'contes-visibility-teaser__cta' },
                        el(
                            'div',
                            { className: 'contes-visibility-teaser__icon-wrap' },
                            el('span', { className: 'contes-visibility-teaser__icon dahlia-icon dahlia-fi-rr-lock', 'aria-hidden': 'true' })
                        ),
                        previewMessage
                            ? el('p', { className: 'contes-visibility-teaser__message' }, previewMessage)
                            : null,
                        previewButtonLabel
                            ? el('span', { className: 'contes-visibility-teaser__button' }, previewButtonLabel)
                            : null
                    )
                )
            ];
        },
        save: function () {
            return el(InnerBlocks.Content);
        }
    });
})(window.wp);
