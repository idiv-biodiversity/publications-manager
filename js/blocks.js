(function() { //Immediately Invoked Function Expression (IIFE) to avoid conflicts
    
    const { registerBlockType } = wp.blocks;
    const { useSelect } = wp.data;
    const { useEffect, useRef } = wp.element;
    const el = wp.element.createElement;

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

            const selectRef = useRef(null);

            const groupOptions = useSelect((select) => {
                const posts = select('core').getEntityRecords('postType', 'groups', { per_page: -1 });
            
                if (!posts) {
                    return [];
                }
            
                const groupedData = posts.reduce((acc, post) => {
                    const parentPost = posts.find(p => p.id === post.parent);
                    const group_name = parentPost ? parentPost.title.rendered : post.title.rendered;
                    const department_name = post.title.rendered;
            
                    if (!acc[group_name]) {
                        acc[group_name] = [];
                    }
                    // Add the department object with id and name
                    if (department_name !== group_name) {
                        acc[group_name].push({ id: post.id, name: department_name });
                    }
            
                    return acc;
                }, {});
            
                // Create formatted departments and reverse the order
                const formattedDepartments = Object.entries(groupedData).map(([group, departments]) => ({
                    label: group,
                    options: departments.map(department => ({
                        value: department.id, // Use the department ID for the value
                        label: department.name // Use the department name for the label
                    }))
                })).reverse();
            
                return formattedDepartments;
            }, []);            

            //console.log(groupOptions);

            useEffect(() => {
                    if (selectRef.current) {
                    jQuery(selectRef.current).selectpicker('destroy'); // Destroy previous instance if any
                    jQuery(selectRef.current).selectpicker();
                }
            }, [groupOptions]);

            // Render the select element with selectpicker
            return el('select', {
                ref: selectRef,
                className: 'selectpicker',
                'data-live-search': true,
                value: id !== 0 ? id : '',
                onChange: (event) => {
                    const selectedValue = event.target.value;
                    //console.log('Selected Value:', selectedValue);
                    setAttributes({ id: selectedValue });
                }                
                },
                groupOptions.map((group, index) =>
                        el('optgroup', { label: group.label, key: index }, // Use index for unique keys
                            group.options.map(option =>
                            el('option', { key: option.value, value: option.value }, option.label)
                        )
                    )
                )
            );
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
    
            const selectRef = useRef(null);
    
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
    
            useEffect(() => {
                if (selectRef.current) {
                    jQuery(selectRef.current).selectpicker('destroy'); // Destroy previous instance if any
                    jQuery(selectRef.current).selectpicker();
                }
            }, [staffOptions]);
    
            // Render the select element with selectpicker
            return el('select', {
                ref: selectRef,
                className: 'selectpicker',
                'data-live-search': true,
                value: id !== 0 ? id : '',
                onChange: (event) => {
                    setAttributes({ id: parseInt(event.target.value, 10) });
                }
            },
                staffOptions.map((option) =>
                    el('option', { value: option.value, key: option.value }, option.label)
                )
            );
        },
        save: function () {
            return null; // Content is rendered dynamically in PHP
        },
    });
    


})();
