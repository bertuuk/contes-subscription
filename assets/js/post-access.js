/* global contesPostAccessData */
(function (wp) {
    if (!wp || !wp.plugins || !wp.editPost) {
        return;
    }

    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var ToggleControl = wp.components.ToggleControl;
    var CheckboxControl = wp.components.CheckboxControl;
    var TextControl = wp.components.TextControl;
    var Notice = wp.components.Notice;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var __ = wp.i18n.__;

    var data = window.contesPostAccessData || {};
    var roles = Array.isArray(data.roles) ? data.roles : [];
    var levels = Array.isArray(data.levels) ? data.levels : [];
    var allowedPostTypes = Array.isArray(data.postTypes) ? data.postTypes : [];
    var defaultSalesUrl = data.defaultSalesUrl || '';
    var useLevels = levels.length > 0;

    var PostAccessPanel = function () {
        var postType = useSelect(function (select) {
            return select('core/editor').getCurrentPostType();
        }, []);

        if (!postType || allowedPostTypes.indexOf(postType) === -1) {
            return null;
        }

        var meta = useSelect(function (select) {
            return select('core/editor').getEditedPostAttribute('meta') || {};
        }, []);

        var editPost = useDispatch('core/editor').editPost;

        var enabled = !!meta.contes_access_enabled;
        var selectedRoles = Array.isArray(meta.contes_access_roles) ? meta.contes_access_roles : [];
        var selectedLevels = Array.isArray(meta.contes_access_levels) ? meta.contes_access_levels : [];
        var redirect = !!meta.contes_access_redirect;
        var redirectUrl = meta.contes_access_redirect_url || '';

        var updateMeta = function (updates) {
            editPost({ meta: Object.assign({}, meta, updates) });
        };

        var toggleRole = function (role, isChecked) {
            var nextRoles = selectedRoles.slice();
            if (isChecked && nextRoles.indexOf(role) === -1) {
                nextRoles.push(role);
            } else if (!isChecked && nextRoles.indexOf(role) !== -1) {
                nextRoles = nextRoles.filter(function (item) {
                    return item !== role;
                });
            }
            updateMeta({ contes_access_roles: nextRoles });
        };

        var toggleLevel = function (levelId, isChecked) {
            var nextLevels = selectedLevels.slice();
            var numericId = parseInt(levelId, 10);
            if (isChecked && nextLevels.indexOf(numericId) === -1) {
                nextLevels.push(numericId);
            } else if (!isChecked && nextLevels.indexOf(numericId) !== -1) {
                nextLevels = nextLevels.filter(function (item) {
                    return item !== numericId;
                });
            }
            updateMeta({ contes_access_levels: nextLevels });
        };

        return wp.element.createElement(
            PluginDocumentSettingPanel,
            {
                name: 'contes-post-access',
                title: __('Accés i subscripció', 'contes-subscription'),
                className: 'contes-post-access-panel'
            },
            wp.element.createElement(ToggleControl, {
                label: __('Restringeix accés', 'contes-subscription'),
                checked: enabled,
                onChange: function (value) {
                    updateMeta({ contes_access_enabled: !!value });
                }
            }),
            enabled
                ? (useLevels ? levels : roles).map(function (item) {
                      return wp.element.createElement(CheckboxControl, {
                          key: item.value,
                          label: item.label,
                          checked: useLevels
                              ? selectedLevels.indexOf(parseInt(item.value, 10)) !== -1
                              : selectedRoles.indexOf(item.value) !== -1,
                          onChange: function (isChecked) {
                              if (useLevels) {
                                  toggleLevel(item.value, isChecked);
                              } else {
                                  toggleRole(item.value, isChecked);
                              }
                          }
                      });
                  })
                : null,
            enabled && (useLevels ? selectedLevels.length === 0 : selectedRoles.length === 0)
                ? wp.element.createElement(
                      Notice,
                      { status: 'warning', isDismissible: false },
                      useLevels
                          ? __('Selecciona com a mínim un nivell de subscripció.', 'contes-subscription')
                          : __('Selecciona com a mínim un rol.', 'contes-subscription')
                  )
                : null,
            wp.element.createElement(ToggleControl, {
                label: __('Redirigir si no té accés', 'contes-subscription'),
                checked: redirect,
                onChange: function (value) {
                    updateMeta({ contes_access_redirect: !!value });
                }
            }),
            redirect
                ? wp.element.createElement(TextControl, {
                      label: __('URL de venda (override)', 'contes-subscription'),
                      value: redirectUrl,
                      placeholder: defaultSalesUrl,
                      onChange: function (value) {
                          updateMeta({ contes_access_redirect_url: value });
                      }
                  })
                : null
        );
    };

    registerPlugin('contes-post-access', {
        render: PostAccessPanel,
        icon: null
    });
})(window.wp);
