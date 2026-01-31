/* global contesStoryValues */
(function (wp) {
    if (!wp || !wp.plugins || !wp.editPost) {
        return;
    }

    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editPost;
    const { FormTokenField, Notice, Spinner } = wp.components;
    const { useDispatch, useSelect } = wp.data;
    const { useEffect, useState } = wp.element;
    const { __ } = wp.i18n;
    const apiFetch = wp.apiFetch;

    const metaKey = contesStoryValues?.metaKey || 'contes_story_values';

    const StoryValuesPanel = () => {
        const metaValue = useSelect(
            (select) => {
                const meta = select('core/editor').getEditedPostAttribute('meta');
                return meta?.[metaKey] || [];
            },
            [metaKey]
        );
        const { editPost } = useDispatch('core/editor');
        const [suggestions, setSuggestions] = useState([]);
        const [isLoading, setIsLoading] = useState(true);
        const [error, setError] = useState('');

        useEffect(() => {
            let isMounted = true;

            apiFetch({ path: contesStoryValues?.restPath || '/contes-subscription/v1/story-values' })
                .then((values) => {
                    if (!isMounted) {
                        return;
                    }
                    setSuggestions(Array.isArray(values) ? values : []);
                    setIsLoading(false);
                })
                .catch(() => {
                    if (!isMounted) {
                        return;
                    }
                    setError(
                        __("No s'han pogut carregar els valors existents.", 'contes-subscription')
                    );
                    setIsLoading(false);
                });

            return () => {
                isMounted = false;
            };
        }, []);

        const handleChange = (nextValues) => {
            editPost({ meta: { [metaKey]: nextValues } });
        };

        return (
            <PluginDocumentSettingPanel
                name="contes-story-values"
                title={__('Valors del conte', 'contes-subscription')}
                className="contes-story-values-panel"
            >
                {error ? <Notice status="warning" isDismissible={false}>{error}</Notice> : null}
                {isLoading ? <Spinner /> : null}
                <FormTokenField
                    label={__('Valors', 'contes-subscription')}
                    value={metaValue}
                    suggestions={suggestions}
                    onChange={handleChange}
                    __experimentalExpandOnFocus
                />
            </PluginDocumentSettingPanel>
        );
    };

    registerPlugin('contes-story-values', {
        render: StoryValuesPanel,
        icon: null,
    });
})(window.wp);
