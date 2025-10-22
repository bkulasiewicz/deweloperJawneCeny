(function() {
    const { registerBlockType } = wp.blocks;
    const { createElement: el, Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, CheckboxControl, TextControl, SelectControl } = wp.components;

    // Available fields with their default names - all fields from ResourceSingleShortcode
    const AVAILABLE_FIELDS = {
        // Basic information (9 fields)
        'unit_number': 'Numer lokalu',
        'property_type': 'Typ nieruchomości',
        'usable_area': 'Powierzchnia użytkowa',
        'status': 'Status',
        'floor_number': 'Piętro',
        'room_count': 'Pokoje',
        'additional_description': 'Opis',
        'garden_area': 'Ogród',
        'floor_plan_pdf': 'Plan',

        // Prices (3 fields)
        'price_per_m2': 'Cena za m²',
        'total_price': 'Cena całkowita',
        'price_with_extras': 'Cena z dodatkami',

        // Property part component (4 fields)
        'property_part_title': 'Część nieruchomości - tytuł',
        'property_part_designation': 'Część nieruchomości - oznaczenie',
        'property_part_price': 'Część nieruchomości - cena',
        'property_part_price_date': 'Część nieruchomości - data ceny',

        // Belonging room component (4 fields)
        'belonging_room_title': 'Pomieszczenie przynależne - tytuł',
        'belonging_room_designation': 'Pomieszczenie przynależne - oznaczenie',
        'belonging_room_price': 'Pomieszczenie przynależne - cena',
        'belonging_room_price_date': 'Pomieszczenie przynależne - data ceny',

        // Usage right component (3 fields)
        'usage_right_title': 'Prawo użytkowania - tytuł',
        'usage_right_price': 'Prawo użytkowania - cena',
        'usage_right_price_date': 'Prawo użytkowania - data ceny',

        // Other service component (3 fields)
        'other_service_title': 'Inna usługa - tytuł',
        'other_service_price': 'Inna usługa - cena',
        'other_service_price_date': 'Inna usługa - data ceny',

        // Functional (1 field)
        'historia_cen': 'Historia cen'
    };

    // Status display style options
    const STATUS_STYLE_OPTIONS = [
        { label: 'Badge (prostokąt z ramką)', value: 'badge' },
        { label: 'Pill (zaokrąglony)', value: 'pill' },
        { label: 'Highlight (podświetlenie)', value: 'highlight' },
        { label: 'Underline (podkreślenie)', value: 'underline' },
        { label: 'Plain (tylko tekst)', value: 'plain' }
    ];

    // Font weight options for labels
    const FONT_WEIGHT_OPTIONS = [
        { label: 'Normal', value: 'normal' },
        { label: 'Medium (500)', value: '500' },
        { label: 'Semi-bold (600)', value: '600' },
        { label: 'Bold', value: 'bold' }
    ];


    registerBlockType('jawne-ceny/resource-single', {
        title: 'Szczegóły zasobu',
        description: 'Wyświetla szczegółowe informacje o pojedynczym zasobie wykrytym z URL strony',
        category: 'widgets',
        icon: 'id-alt',
        keywords: ['zasób', 'lokal', 'szczegóły', 'mieszkanie', 'resource'],

        attributes: {
            // Visible fields configuration
            visibleFields: {
                type: 'array',
                default: [
                    'unit_number',
                    'property_type',
                    'usable_area',
                    'room_count',
                    'floor_number',
                    'total_price',
                    'price_per_m2',
                    'historia_cen'
                ]
            },
            fieldOrder: {
                type: 'array',
                default: [
                    'unit_number',
                    'property_type',
                    'usable_area',
                    'room_count',
                    'floor_number',
                    'total_price',
                    'price_per_m2',
                    'historia_cen'
                ]
            },
            fieldLabels: {
                type: 'object',
                default: {}
            },

            // Card styling
            cardBgColor: {
                type: 'string',
                default: '#ffffff'
            },
            cardBorderColor: {
                type: 'string',
                default: '#e1e5e9'
            },
            cardBorderRadius: {
                type: 'string',
                default: '8px'
            },
            cardPadding: {
                type: 'string',
                default: '24px'
            },
            cardTextColor: {
                type: 'string',
                default: '#333333'
            },
            cardLabelColor: {
                type: 'string',
                default: '#666666'
            },
            labelFontWeight: {
                type: 'string',
                default: 'bold'
            },
            fieldSpacing: {
                type: 'string',
                default: '12px'
            },

            // Status styling
            statusDisplayStyle: {
                type: 'string',
                default: 'badge'
            },
            statusAvailableBgColor: {
                type: 'string',
                default: '#28a745'
            },
            statusAvailableTextColor: {
                type: 'string',
                default: '#ffffff'
            },
            statusSoldBgColor: {
                type: 'string',
                default: '#dc3545'
            },
            statusSoldTextColor: {
                type: 'string',
                default: '#ffffff'
            },
            statusReservedBgColor: {
                type: 'string',
                default: '#ffc107'
            },
            statusReservedTextColor: {
                type: 'string',
                default: '#000000'
            },
            statusFontSize: {
                type: 'string',
                default: '0.875em'
            },
            statusPadding: {
                type: 'string',
                default: '4px 8px'
            },
            statusBorderRadius: {
                type: 'string',
                default: '4px'
            },
            statusFontWeight: {
                type: 'string',
                default: 'normal'
            },

            // Button styling
            historiaBtnText: {
                type: 'string',
                default: 'Historia cen'
            },
            historiaBtnBgColor: {
                type: 'string',
                default: '#007cba'
            },
            historiaBtnTextColor: {
                type: 'string',
                default: '#ffffff'
            },
            kartaBtnText: {
                type: 'string',
                default: 'Karta lokalu'
            },
            kartaBtnBgColor: {
                type: 'string',
                default: '#007cba'
            },
            kartaBtnTextColor: {
                type: 'string',
                default: '#ffffff'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;

            // Initialize compact color picker
            const CompactColorPicker = window.createCompactColorPickerFactory(wp)();

            // Helper function to add field to the end of visible list
            const addField = function(field) {
                if (!attributes.visibleFields.includes(field)) {
                    const newFields = [...attributes.visibleFields, field];
                    const newOrder = [...attributes.fieldOrder.filter(f => newFields.includes(f)), field];

                    setAttributes({
                        visibleFields: newFields,
                        fieldOrder: newOrder
                    });
                }
            };

            // Helper function to remove field from visible list
            const removeField = function(field) {
                const newFields = attributes.visibleFields.filter(f => f !== field);
                const newOrder = attributes.fieldOrder.filter(f => newFields.includes(f));

                // Also remove custom name if exists
                const newNames = { ...attributes.fieldLabels };
                delete newNames[field];

                setAttributes({
                    visibleFields: newFields,
                    fieldOrder: newOrder,
                    fieldLabels: newNames
                });
            };

            // Function for updating field names - immediate updates for smooth UX
            const updateFieldName = function(field, newName) {
                const newNames = { ...attributes.fieldLabels };

                if (newName && newName.trim() !== '' && newName !== AVAILABLE_FIELDS[field]) {
                    // Save custom name (preserve spaces)
                    newNames[field] = newName;
                } else {
                    // Remove custom name (use default)
                    delete newNames[field];
                }

                setAttributes({ fieldLabels: newNames });
            };

            // Drag & Drop handlers for field reordering
            const handleDragStart = function(e, draggedField) {
                e.dataTransfer.setData('text/plain', draggedField);
                e.dataTransfer.effectAllowed = 'move';

                // Add dragging class for styling
                e.target.classList.add('dragging');
            };

            const handleDragEnd = function(e) {
                // Remove dragging class
                e.target.classList.remove('dragging');
            };

            const handleDragOver = function(e) {
                e.preventDefault(); // Allow drop
                e.dataTransfer.dropEffect = 'move';

                // Add visual feedback
                e.currentTarget.classList.add('drag-over');
            };

            const handleDragLeave = function(e) {
                // Remove visual feedback
                e.currentTarget.classList.remove('drag-over');
            };

            const handleDrop = function(e, dropTarget) {
                e.preventDefault();

                // Remove visual feedback
                e.currentTarget.classList.remove('drag-over');

                const draggedField = e.dataTransfer.getData('text/plain');
                if (!draggedField || draggedField === dropTarget) {
                    return; // No change needed
                }

                // Create new field order
                const currentOrder = [...attributes.fieldOrder];
                const draggedIndex = currentOrder.indexOf(draggedField);
                const dropIndex = currentOrder.indexOf(dropTarget);

                if (draggedIndex === -1 || dropIndex === -1) {
                    return; // Invalid indexes
                }

                // Remove dragged item and insert at new position
                currentOrder.splice(draggedIndex, 1);
                currentOrder.splice(dropIndex, 0, draggedField);

                setAttributes({ fieldOrder: currentOrder });
            };

            return el(Fragment, {},
                // Auto-detection information banner (top of editor)
                el('div', {
                    style: {
                        padding: '12px 16px',
                        margin: '0 0 16px 0',
                        backgroundColor: '#e7f5ff',
                        borderLeft: '4px solid #007cba',
                        fontSize: '13px',
                        lineHeight: '1.5'
                    }
                },
                    el('strong', { style: { display: 'block', marginBottom: '4px' } }, '💡 Automatyczne wykrywanie zasobu'),
                    el('p', { style: { margin: '0' } },
                        'Numer lokalu jest automatycznie wykrywany z ostatniego segmentu URL strony (np. /mieszkania/A1 → "A1"). Umieść ten blok na stronie z numerem lokalu w adresie URL.'
                    )
                ),

                // InspectorControls (sidebar panels)
                el(InspectorControls, {},

                    // Panel 1: Kolejność i nazwy pól
                    el(PanelBody, {
                        title: 'Kolejność i nazwy pól',
                        initialOpen: true
                    },
                        el('div', {
                            style: {
                                marginBottom: '16px'
                            }
                        },
                            el('p', {
                                style: {
                                    margin: '0 0 12px',
                                    fontSize: '13px',
                                    color: '#666'
                                }
                            }, 'Przeciągnij pola aby zmienić kolejność. Kliknij ✕ aby usunąć.'),

                            // Visible fields list with drag&drop
                            attributes.visibleFields.length > 0 ?
                                attributes.fieldOrder
                                    .filter(field => attributes.visibleFields.includes(field))
                                    .map((field, index) => {
                                        const defaultName = AVAILABLE_FIELDS[field] || field;
                                        const customName = attributes.fieldLabels[field] || '';

                                        return el('div', {
                                            key: field,
                                            className: 'field-item',
                                            style: {
                                                display: 'flex',
                                                alignItems: 'center',
                                                marginBottom: '8px',
                                                padding: '8px',
                                                backgroundColor: '#f9f9f9',
                                                border: '1px solid #ddd',
                                                borderRadius: '4px',
                                                cursor: 'move'
                                            },
                                            draggable: true,
                                            onDragStart: (e) => handleDragStart(e, field),
                                            onDragEnd: handleDragEnd,
                                            onDragOver: handleDragOver,
                                            onDragLeave: handleDragLeave,
                                            onDrop: (e) => handleDrop(e, field)
                                        },
                                            // Drag handle
                                            el('span', {
                                                style: {
                                                    marginRight: '8px',
                                                    color: '#666',
                                                    fontSize: '14px',
                                                    cursor: 'grab'
                                                }
                                            }, '≡≡'),

                                            // Field number
                                            el('span', {
                                                style: {
                                                    marginRight: '8px',
                                                    minWidth: '20px',
                                                    fontSize: '12px',
                                                    color: '#666',
                                                    fontWeight: 'bold'
                                                }
                                            }, (index + 1) + '.'),

                                            // Field name input
                                            el('input', {
                                                type: 'text',
                                                placeholder: defaultName,
                                                value: customName,
                                                onChange: (e) => updateFieldName(field, e.target.value),
                                                style: {
                                                    flex: '1',
                                                    padding: '4px 6px',
                                                    border: '1px solid #ccd0d4',
                                                    borderRadius: '3px',
                                                    fontSize: '13px'
                                                }
                                            }),

                                            // Remove button
                                            el('button', {
                                                type: 'button',
                                                onClick: () => removeField(field),
                                                style: {
                                                    marginLeft: '8px',
                                                    padding: '4px 6px',
                                                    backgroundColor: '#dc3232',
                                                    color: 'white',
                                                    border: 'none',
                                                    borderRadius: '3px',
                                                    fontSize: '11px',
                                                    cursor: 'pointer'
                                                },
                                                title: 'Usuń pole'
                                            }, '✕')
                                        );
                                    }) :
                                el('p', {
                                    style: {
                                        color: '#666',
                                        fontStyle: 'italic',
                                        margin: '0'
                                    }
                                }, 'Brak widocznych pól. Dodaj pola poniżej.')
                        )
                    ),

                    // Panel 2: Dostępne pola
                    el(PanelBody, {
                        title: 'Dostępne pola',
                        initialOpen: false
                    },
                        el('div', {
                            style: {
                                display: 'grid',
                                gridTemplateColumns: '1fr 1fr',
                                gap: '8px'
                            }
                        },
                            Object.entries(AVAILABLE_FIELDS)
                                .filter(([field]) => !attributes.visibleFields.includes(field))
                                .map(([field, defaultName]) =>
                                    el('button', {
                                        key: field,
                                        type: 'button',
                                        onClick: () => addField(field),
                                        style: {
                                            padding: '6px 8px',
                                            backgroundColor: '#f0f0f1',
                                            border: '1px solid #c3c4c7',
                                            borderRadius: '3px',
                                            fontSize: '12px',
                                            cursor: 'pointer',
                                            textAlign: 'left'
                                        },
                                        title: 'Dodaj pole'
                                    }, '+ ' + defaultName)
                                )
                        ),
                        Object.entries(AVAILABLE_FIELDS).filter(([field]) => !attributes.visibleFields.includes(field)).length === 0 &&
                        el('p', {
                            style: {
                                color: '#666',
                                fontStyle: 'italic',
                                margin: '0'
                            }
                        }, 'Wszystkie pola są już dodane.')
                    ),

                    // Panel 3: Stylizacja karty
                    el(PanelBody, {
                        title: 'Stylizacja karty',
                        initialOpen: false
                    },
                        CompactColorPicker('cardBgColor', 'Kolor tła', attributes.cardBgColor,
                            (color) => setAttributes({ cardBgColor: color })),

                        CompactColorPicker('cardBorderColor', 'Kolor obramowania', attributes.cardBorderColor,
                            (color) => setAttributes({ cardBorderColor: color })),

                        el(TextControl, {
                            label: 'Zaokrąglenie rogów',
                            value: attributes.cardBorderRadius,
                            onChange: (value) => setAttributes({ cardBorderRadius: value }),
                            placeholder: '8px',
                            help: 'np. 8px, 4px, 0'
                        }),

                        el(TextControl, {
                            label: 'Padding wewnętrzny',
                            value: attributes.cardPadding,
                            onChange: (value) => setAttributes({ cardPadding: value }),
                            placeholder: '24px',
                            help: 'np. 24px, 16px'
                        }),

                        CompactColorPicker('cardTextColor', 'Kolor tekstu', attributes.cardTextColor,
                            (color) => setAttributes({ cardTextColor: color })),

                        CompactColorPicker('cardLabelColor', 'Kolor etykiet', attributes.cardLabelColor,
                            (color) => setAttributes({ cardLabelColor: color })),

                        el(SelectControl, {
                            label: 'Grubość etykiet',
                            value: attributes.labelFontWeight,
                            options: FONT_WEIGHT_OPTIONS,
                            onChange: (value) => setAttributes({ labelFontWeight: value })
                        }),

                        el(TextControl, {
                            label: 'Odstęp między polami',
                            value: attributes.fieldSpacing,
                            onChange: (value) => setAttributes({ fieldSpacing: value }),
                            placeholder: '12px',
                            help: 'np. 12px, 16px, 8px'
                        })
                    ),

                    // Panel 4: Stylizacja statusu
                    el(PanelBody, {
                        title: 'Stylizacja statusu',
                        initialOpen: false
                    },
                        el(SelectControl, {
                            label: 'Styl wyświetlania',
                            value: attributes.statusDisplayStyle,
                            options: STATUS_STYLE_OPTIONS,
                            onChange: (value) => setAttributes({ statusDisplayStyle: value })
                        }),

                        el('hr', { style: { margin: '16px 0' } }),

                        el('h4', { style: { margin: '0 0 12px', fontSize: '13px', fontWeight: '500' } }, 'Status: Dostępny'),
                        CompactColorPicker('statusAvailableBgColor', 'Kolor tła', attributes.statusAvailableBgColor,
                            (color) => setAttributes({ statusAvailableBgColor: color })),
                        CompactColorPicker('statusAvailableTextColor', 'Kolor tekstu', attributes.statusAvailableTextColor,
                            (color) => setAttributes({ statusAvailableTextColor: color })),

                        el('hr', { style: { margin: '16px 0' } }),

                        el('h4', { style: { margin: '0 0 12px', fontSize: '13px', fontWeight: '500' } }, 'Status: Sprzedany'),
                        CompactColorPicker('statusSoldBgColor', 'Kolor tła', attributes.statusSoldBgColor,
                            (color) => setAttributes({ statusSoldBgColor: color })),
                        CompactColorPicker('statusSoldTextColor', 'Kolor tekstu', attributes.statusSoldTextColor,
                            (color) => setAttributes({ statusSoldTextColor: color })),

                        el('hr', { style: { margin: '16px 0' } }),

                        el('h4', { style: { margin: '0 0 12px', fontSize: '13px', fontWeight: '500' } }, 'Status: Zarezerwowany'),
                        CompactColorPicker('statusReservedBgColor', 'Kolor tła', attributes.statusReservedBgColor,
                            (color) => setAttributes({ statusReservedBgColor: color })),
                        CompactColorPicker('statusReservedTextColor', 'Kolor tekstu', attributes.statusReservedTextColor,
                            (color) => setAttributes({ statusReservedTextColor: color })),

                        el('hr', { style: { margin: '16px 0' } }),

                        el('h4', { style: { margin: '0 0 12px', fontSize: '13px', fontWeight: '500' } }, 'Ogólne ustawienia statusu'),
                        el(TextControl, {
                            label: 'Rozmiar czcionki',
                            value: attributes.statusFontSize,
                            onChange: (value) => setAttributes({ statusFontSize: value }),
                            placeholder: '0.875em',
                            help: 'np. 0.875em, 14px'
                        }),

                        el(TextControl, {
                            label: 'Padding',
                            value: attributes.statusPadding,
                            onChange: (value) => setAttributes({ statusPadding: value }),
                            placeholder: '4px 8px',
                            help: 'np. 4px 8px, 6px 12px'
                        }),

                        el(TextControl, {
                            label: 'Zaokrąglenie rogów',
                            value: attributes.statusBorderRadius,
                            onChange: (value) => setAttributes({ statusBorderRadius: value }),
                            placeholder: '4px',
                            help: 'np. 4px, 50px (pill)'
                        }),

                        el(SelectControl, {
                            label: 'Grubość czcionki',
                            value: attributes.statusFontWeight,
                            options: FONT_WEIGHT_OPTIONS,
                            onChange: (value) => setAttributes({ statusFontWeight: value })
                        })
                    ),

                    // Panel 5: Stylizacja przycisków
                    el(PanelBody, {
                        title: 'Stylizacja przycisków',
                        initialOpen: false
                    },
                        el('h4', { style: { margin: '0 0 12px', fontSize: '13px', fontWeight: '500' } }, 'Przycisk "Historia cen"'),
                        el(TextControl, {
                            label: 'Tekst przycisku',
                            value: attributes.historiaBtnText,
                            onChange: (value) => setAttributes({ historiaBtnText: value }),
                            placeholder: 'Historia cen'
                        }),

                        CompactColorPicker('historiaBtnBgColor', 'Kolor tła', attributes.historiaBtnBgColor,
                            (color) => setAttributes({ historiaBtnBgColor: color })),

                        CompactColorPicker('historiaBtnTextColor', 'Kolor tekstu', attributes.historiaBtnTextColor,
                            (color) => setAttributes({ historiaBtnTextColor: color })),

                        el('hr', { style: { margin: '16px 0' } }),

                        el('h4', { style: { margin: '0 0 12px', fontSize: '13px', fontWeight: '500' } }, 'Przycisk "Karta lokalu"'),
                        el(TextControl, {
                            label: 'Tekst przycisku',
                            value: attributes.kartaBtnText,
                            onChange: (value) => setAttributes({ kartaBtnText: value }),
                            placeholder: 'Karta lokalu'
                        }),

                        CompactColorPicker('kartaBtnBgColor', 'Kolor tła', attributes.kartaBtnBgColor,
                            (color) => setAttributes({ kartaBtnBgColor: color })),

                        CompactColorPicker('kartaBtnTextColor', 'Kolor tekstu', attributes.kartaBtnTextColor,
                            (color) => setAttributes({ kartaBtnTextColor: color }))
                    )
                ),

                // Block placeholder in editor
                el('div', {
                    style: {
                        padding: '40px 20px',
                        textAlign: 'center',
                        border: '2px dashed #ccc',
                        borderRadius: '8px',
                        backgroundColor: '#f9f9f9',
                        color: '#666',
                        fontSize: '14px'
                    }
                },
                    el('span', {
                        className: 'dashicons dashicons-id-alt',
                        style: {
                            fontSize: '48px',
                            width: '48px',
                            height: '48px',
                            marginBottom: '12px',
                            display: 'block',
                            margin: '0 auto 12px'
                        }
                    }),
                    el('strong', { style: { display: 'block', marginBottom: '8px', fontSize: '16px' } },
                        'Blok: Szczegóły zasobu'
                    ),
                    el('p', { style: { margin: '0', lineHeight: '1.5' } },
                        'Ten blok wyświetli szczegółowe informacje o zasobie wykrytym z URL strony. ',
                        el('br'),
                        'Liczba widocznych pól: ' + attributes.visibleFields.length
                    )
                )
            );
        },

        save: function() {
            // Dynamic block - rendered server-side
            return null;
        }
    });
})();
