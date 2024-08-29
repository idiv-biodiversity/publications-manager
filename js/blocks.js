(function() { //Immediately Invoked Function Expression (IIFE) to avoid conflicts
    
    const { registerBlockType } = wp.blocks;
    const { createElement } = wp.element; // Import createElement from wp.element
    const { useBlockProps } = wp.blockEditor;

    // ###################################################
    // iDiv Publikationsliste (vollständig)
    // ###################################################

    registerBlockType('publications-manager/all-publications', {
        title: 'Publication list (all)',
        icon: 'book',
        category: 'widgets',
        edit: function() {
            return el('div', { className: 'border p-2' },
                        el('div', { className: 'font-weight-bold' }, 'Publication list (all)')
                    );
        },
        save: function() {
            return null; // Content is rendered dynamically in PHP
        }
    });

    // ###################################################
    // iDiv Publikationsliste (Arbeitsgruppe)
    // ###################################################

    registerBlockType('publications-manager/group-publications', {
        title: 'Publication list (group)',
        icon: 'admin-users',
        category: 'widgets',
        attributes: {
            id: {
                type: 'number',
                default: 0,
            },
        },
        edit: function (props) {
            const { attributes, setAttributes } = props;
            const { id } = attributes;

            // Fetch group options
            const groupOptions = useSelect((select) => {
                const posts = select('core').getEntityRecords('postType', 'groups', { per_page: -1 });

                if (!posts) {
                    return [];
                }

                // Separate parent and child pages
                const parentPages = posts.filter(post => post.parent === 0);
                const childPages = posts.filter(post => post.parent !== 0);

                // Build options array with optgroup and options
                const options = parentPages.map(parent => {
                    const childOptions = childPages
                        .filter(child => child.parent === parent.id)
                        .map(child => ({
                            value: child.id,
                            label: child.title.rendered,
                        }));

                    return {
                        label: parent.title.rendered,
                        options: childOptions,
                    };
                });

                return options.reverse();
            }, []);

            // Create a ref for the select element
            const selectRef = React.useRef(null);

            // Use effect to initialize selectpicker
            React.useEffect(() => {
                // Initialize selectpicker when the component mounts
                if (selectRef.current) {
                    jQuery(selectRef.current).selectpicker('refresh');
                }
            }, [groupOptions]); // Re-run this effect if groupOptions changes

            // Render the select element with selectpicker
            return el('select', {
                ref: selectRef,
                className: 'selectpicker', // Add selectpicker class
                'data-live-search': 'true', // Enable live search in selectpicker
                title: 'Nothing selected',
                value: id !== 0 ? id : '',
                onChange: function (event) {
                    setAttributes({ id: parseInt(event.target.value, 10) });
                },
                children: [
                    ...groupOptions.map((group) =>
                        el('optgroup', { label: group.label, key: group.label },
                            group.options.map(option =>
                                el('option', { value: option.value, key: option.value }, option.label)
                            )
                        )
                    )
                ]
            });
        },
        save: function () {
            return null; // Content is rendered dynamically in PHP
        },
    });

    // ###################################################
    // iDiv Publikationsliste (individuell)
    // ###################################################

    registerBlockType('publications-manager/single-publications', {
        title: 'Publication list (single)',
        icon: 'admin-users',
        category: 'widgets',
        attributes: {
            id: {
                type: 'number',
                default: 0,
            },
        },
        edit: function (props) {
            const { attributes, setAttributes } = props;
            const { id } = attributes;
    
            // Fetch staff options
            const staffOptions = useSelect((select) => {
                const posts = select('core').getEntityRecords('postType', 'staff-manager', { per_page: -1 });
                if (!posts) {
                    return [];
                }
                return posts.map((post) => ({
                    value: post.id,
                    label: post.title.rendered,
                }));
            }, []);
    
            // Create a ref for the select element
            const selectRef = React.useRef(null);
    
            // Use effect to initialize selectpicker
            React.useEffect(() => {
                // Initialize selectpicker when the component mounts
                if (selectRef.current) {
                    jQuery(selectRef.current).selectpicker('refresh');
                }
            }, [staffOptions]); // Re-run this effect if staffOptions changes
    
            // Render the select element with selectpicker
            return el('select', {
                ref: selectRef,
                className: 'selectpicker', // Add selectpicker class
                'data-live-search': 'true', // Enable live search in selectpicker
                title: 'Nothing selected',
                value: id !== 0 ? id : '',
                onChange: function (event) {
                    setAttributes({ id: parseInt(event.target.value, 10) });
                },
                children: [
                    ...staffOptions.map((option) =>
                        el('option', { value: option.value, key: option.value }, option.label)
                    ),
                ],
            });
        },
        save: function () {
            return null; // Content is rendered dynamically in PHP
        },
    });


})();
