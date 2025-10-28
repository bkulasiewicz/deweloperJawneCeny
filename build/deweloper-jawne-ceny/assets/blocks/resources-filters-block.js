(function() {
    const { registerBlockType } = wp.blocks;
    const { createElement: el, Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, CheckboxControl, TextControl, SelectControl, __experimentalNumberControl: NumberControl } = wp.components;

    // Available database fields
    const DATABASE_FIELDS = [
        { label: 'Cena lokalu', value: 'total_price' },
        { label: 'Cena za m²', value: 'price_per_m2' },
        { label: 'Powierzchnia użytkowa', value: 'usable_area' },
        { label: 'Powierzchnia ogrodu', value: 'garden_area' },
        { label: 'Liczba pokoi', value: 'room_count' },
        { label: 'Piętro', value: 'floor_number' },
        { label: 'Status', value: 'status' },
        { label: 'Typ nieruchomości', value: 'property_type' },
        { label: 'Numer lokalu', value: 'unit_number' }
    ];

    // Filter type options
    const FILTER_TYPES = [
        { label: 'Zakres (od-do)', value: 'range' },
        { label: 'Minimum (powyżej)', value: 'min' },
        { label: 'Maximum (poniżej)', value: 'max' },
        { label: 'Dokładna wartość', value: 'exact' },
        { label: 'Lista wartości', value: 'list' }
    ];

    registerBlockType('jawne-ceny/resources-filters', {
        title: 'Filtry Zasobów',
        description: 'Widget do filtrowania listy zasobów/mieszkań',
        category: 'widgets',
        icon: 'filter',
        keywords: ['filtry', 'zasoby', 'mieszkania', 'filtrowanie'],

        attributes: {
            filterGroups: {
                type: 'array',
                default: [
                    {
                        type: 'section',
                        id: 'section_floor',
                        title: 'Wybierz piętro:'
                    },
                    {
                        type: 'filter',
                        id: 'floor_ground',
                        enabled: true,
                        label: 'Parter',
                        field: 'floor_number',
                        filterType: 'exact',
                        conditions: {
                            value: '0'
                        }
                    },
                    {
                        type: 'filter',
                        id: 'floor_1',
                        enabled: true,
                        label: 'Piętro 1',
                        field: 'floor_number',
                        filterType: 'exact',
                        conditions: {
                            value: '1'
                        }
                    },
                    {
                        type: 'filter',
                        id: 'floor_2',
                        enabled: true,
                        label: 'Piętro 2',
                        field: 'floor_number',
                        filterType: 'exact',
                        conditions: {
                            value: '2'
                        }
                    },
                    {
                        type: 'section',
                        id: 'section_area',
                        title: 'Metraż:'
                    },
                    {
                        type: 'filter',
                        id: 'area_40_50',
                        enabled: true,
                        label: '40-50m²',
                        field: 'usable_area',
                        filterType: 'range',
                        conditions: {
                            min: 40,
                            max: 50
                        }
                    },
                    {
                        type: 'filter',
                        id: 'area_above_50',
                        enabled: true,
                        label: 'pow. 50m²',
                        field: 'usable_area',
                        filterType: 'min',
                        conditions: {
                            min: 50
                        }
                    }
                ]
            },

            // Styling options
            headerBgColor: {
                type: 'string',
                default: '#ffffff'
            },
            headerTextColor: {
                type: 'string',
                default: '#333'
            },
            borderColor: {
                type: 'string',
                default: '#e1e5e9'
            },
            groupBgColor: {
                type: 'string',
                default: '#fafafa'
            },
            groupHoverBgColor: {
                type: 'string',
                default: '#f8f9fa'
            },
            textColor: {
                type: 'string',
                default: '#333'
            },
            applyBtnBgColor: {
                type: 'string',
                default: '#007cba'
            },
            applyBtnTextColor: {
                type: 'string',
                default: '#ffffff'
            },
            clearBtnBgColor: {
                type: 'string',
                default: '#f6f7f7'
            },
            clearBtnTextColor: {
                type: 'string',
                default: '#50575e'
            },
            headerFontFamily: {
                type: 'string',
                default: 'inherit'
            },
            contentFontFamily: {
                type: 'string',
                default: 'inherit'
            },

            // Button text customization
            applyBtnText: {
                type: 'string',
                default: 'Zastosuj filtry'
            },
            clearBtnText: {
                type: 'string',
                default: 'Wyczyść'
            },
            widgetTitle: {
                type: 'string',
                default: ''
            },

            // Layout & Spacing Options (Priority 1) - only working ones
            widgetPadding: {
                type: 'string',
                default: '20px'
            },
            widgetBorderRadius: {
                type: 'string',
                default: '8px'
            },
            widgetBorderWidth: {
                type: 'string',
                default: '1px'
            },
            filterGroupPadding: {
                type: 'string',
                default: '12px'
            },
            filterGroupBorderRadius: {
                type: 'string',
                default: '6px'
            },
            buttonPadding: {
                type: 'string',
                default: '10px 20px'
            },
            buttonBorderRadius: {
                type: 'string',
                default: '5px'
            },

            // Typography Options (Priority 1) - only working ones
            sectionTitleFontSize: {
                type: 'string',
                default: '1.1em'
            },
            buttonFontSize: {
                type: 'string',
                default: '0.95em'
            },

            // Border Colors (Priority 1) - only working ones
            sectionTitleBorderColor: {
                type: 'string',
                default: '#f0f0f1'
            },
            filterGroupBorderColor: {
                type: 'string',
                default: '#f0f0f1'
            },
            actionsBorderColor: {
                type: 'string',
                default: '#f0f0f1'
            },

            // Advanced Spacing Attributes
            filtersGap: {
                type: 'string',
                default: '16px'
            },
            filtersContainerGap: {
                type: 'string',
                default: '12px'
            },
            actionsGap: {
                type: 'string',
                default: '12px'
            },

            // Margins and Paddings Attributes
            actionsPaddingTop: {
                type: 'string',
                default: '16px'
            },
            titlePaddingBottom: {
                type: 'string',
                default: '8px'
            },
            titleMarginBottom: {
                type: 'string',
                default: '16px'
            },

            // Advanced Button Options Attributes
            buttonFontWeight: {
                type: 'string',
                default: '500'
            },
            buttonTransition: {
                type: 'string',
                default: 'all 0.2s ease'
            },
            buttonHoverTransform: {
                type: 'string',
                default: 'translateY(-1px)'
            },

            // Focus States Attributes
            focusOutlineColor: {
                type: 'string',
                default: '#007cba'
            },
            focusOutlineWidth: {
                type: 'string',
                default: '2px'
            },
            focusOutlineOffset: {
                type: 'string',
                default: '2px'
            },

            // Typography - Filter Text Font Size
            filterTextFontSize: {
                type: 'string',
                default: '0.95em'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;

            // Initialize compact color picker
            const CompactColorPicker = window.createCompactColorPickerFactory(wp)();

            // Helper functions
            const updateFilterGroup = function(groupIndex, property, value) {
                const newGroups = [...attributes.filterGroups];
                newGroups[groupIndex] = {
                    ...newGroups[groupIndex],
                    [property]: value
                };
                setAttributes({ filterGroups: newGroups });
            };

            const updateCondition = function(groupIndex, conditionKey, value) {
                const newGroups = [...attributes.filterGroups];
                newGroups[groupIndex] = {
                    ...newGroups[groupIndex],
                    conditions: {
                        ...newGroups[groupIndex].conditions,
                        [conditionKey]: value
                    }
                };
                setAttributes({ filterGroups: newGroups });
            };

            const removeFilter = function(groupIndex) {
                const newGroups = attributes.filterGroups.filter((_, index) => index !== groupIndex);
                setAttributes({ filterGroups: newGroups });
            };

            const addNewFilter = function() {
                const newFilter = {
                    type: 'filter',
                    id: 'filter_' + Date.now(),
                    enabled: false,
                    label: 'Nowy filtr',
                    field: 'total_price',
                    filterType: 'range',
                    conditions: {
                        min: 0,
                        max: 1000000
                    }
                };
                setAttributes({
                    filterGroups: [...attributes.filterGroups, newFilter]
                });
            };

            const addNewSection = function() {
                const newSection = {
                    type: 'section',
                    id: 'section_' + Date.now(),
                    title: 'Nowa sekcja'
                };
                setAttributes({
                    filterGroups: [...attributes.filterGroups, newSection]
                });
            };

            const generateId = function(label) {
                return label.toLowerCase()
                    .replace(/[^a-z0-9]/g, '_')
                    .replace(/_+/g, '_')
                    .replace(/^_|_$/g, '');
            };

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, {
                        title: 'Instrukcje konfiguracji',
                        initialOpen: true
                    },
                        el('p', {}, 'Skonfiguruj filtry, które będą dostępne dla użytkowników. Admin definiuje warunki filtrowania, a użytkownicy mogą tylko zaznaczać checkboxy.'),

                        el('div', { style: { backgroundColor: '#fff3cd', padding: '12px', borderRadius: '4px', marginBottom: '12px', border: '1px solid #ffeaa7' } },
                            el('h4', { style: { margin: '0 0 8px', color: '#856404' } }, '⚠️ Ważne: Konfiguracja połączenia z listą zasobów'),
                            el('p', { style: { margin: '0 0 8px', fontSize: '13px', color: '#856404' } }, 'Aby filtry działały, musisz dodać do tej samej strony blok "Lista Zasobów" i włączyć w nim opcję:'),
                            el('p', { style: { margin: '0 0 8px', fontSize: '14px', fontWeight: 'bold', color: '#856404', backgroundColor: '#fff', padding: '4px 8px', borderRadius: '3px' } }, '"Czytaj filtry z URL" - w ustawieniach bloku Lista Zasobów'),
                            el('p', { style: { margin: '0', fontSize: '12px', color: '#856404' } }, 'Bez tej opcji filtry nie będą wpływać na wyświetlaną listę mieszkań/zasobów.')
                        ),

                        el('div', { style: { backgroundColor: '#d1ecf1', padding: '12px', borderRadius: '4px', marginBottom: '12px', border: '1px solid #bee5eb' } },
                            el('h4', { style: { margin: '0 0 8px', color: '#0c5460' } }, '📋 Instrukcja krok po kroku:'),
                            el('ol', { style: { margin: '0', paddingLeft: '20px', fontSize: '13px', color: '#0c5460' } },
                                el('li', {}, 'Skonfiguruj filtry w tym bloku (domyślnie: piętro i metraż)'),
                                el('li', {}, 'Dodaj blok "Lista Zasobów" do tej samej strony'),
                                el('li', {}, 'W bloku "Lista Zasobów" włącz opcję "Czytaj filtry z URL"'),
                                el('li', {}, 'Zapisz stronę - filtry będą działać!')
                            )
                        ),

                        el('div', { style: { backgroundColor: '#f8d7da', padding: '12px', borderRadius: '4px', border: '1px solid #f5c6cb' } },
                            el('h4', { style: { margin: '0 0 8px', color: '#721c24' } }, '🔧 Rozwiązywanie problemów:'),
                            el('ul', { style: { margin: '0', paddingLeft: '20px', fontSize: '12px', color: '#721c24' } },
                                el('li', {}, 'Filtry nie działają? Sprawdź czy "Czytaj filtry z URL" jest włączone'),
                                el('li', {}, 'Lista nie filtruje? Upewnij się, że oba bloki są na tej samej stronie'),
                                el('li', {}, 'Brak danych? Sprawdź czy masz dodane zasoby w panelu administracyjnym')
                            )
                        )
                    ),

                    // Filter groups and sections panels
                    attributes.filterGroups.map((item, groupIndex) =>
                        el(PanelBody, {
                            key: item.id || groupIndex,
                            title: item.type === 'section' ? (item.title || `Sekcja ${groupIndex + 1}`) : (item.label || `Filtr ${groupIndex + 1}`),
                            initialOpen: false
                        },
                            // Section interface
                            item.type === 'section' && el(Fragment, {},
                                el(TextControl, {
                                    label: 'Nazwa sekcji',
                                    value: item.title,
                                    onChange: (value) => updateFilterGroup(groupIndex, 'title', value),
                                    placeholder: 'np. Cena i powierzchnia'
                                }),

                                el('hr'),
                                el('div', { style: { textAlign: 'center' } },
                                    el('button', {
                                        type: 'button',
                                        onClick: () => removeFilter(groupIndex),
                                        className: 'button',
                                        style: { color: '#d63638' }
                                    }, 'Usuń tę sekcję')
                                )
                            ),

                            // Filter interface
                            item.type !== 'section' && el(Fragment, {},
                                el(CheckboxControl, {
                                    label: 'Włącz ten filtr',
                                    checked: item.enabled,
                                    onChange: (checked) => updateFilterGroup(groupIndex, 'enabled', checked)
                                }),

                                item.enabled && el(Fragment, {},
                                el(TextControl, {
                                    label: 'Etykieta filtru (co widzi użytkownik)',
                                    value: item.label,
                                    onChange: (value) => {
                                        updateFilterGroup(groupIndex, 'label', value);
                                    },
                                    placeholder: 'np. Cena 300k-500k'
                                }),

                                el(SelectControl, {
                                    label: 'Pole z bazy danych',
                                    value: item.field,
                                    options: DATABASE_FIELDS,
                                    onChange: (value) => updateFilterGroup(groupIndex, 'field', value)
                                }),

                                el(SelectControl, {
                                    label: 'Typ filtrowania',
                                    value: item.filterType,
                                    options: FILTER_TYPES,
                                    onChange: (value) => updateFilterGroup(groupIndex, 'filterType', value)
                                }),

                                // Conditional configuration based on filter type
                                item.filterType === 'range' && el(Fragment, {},
                                    el('h4', { style: { margin: '16px 0 8px' } }, 'Zakres wartości:'),
                                    el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' } },
                                        el(NumberControl, {
                                            label: 'Od',
                                            value: item.conditions?.min || 0,
                                            onChange: (value) => updateCondition(groupIndex, 'min', parseFloat(value) || 0)
                                        }),
                                        el(NumberControl, {
                                            label: 'Do',
                                            value: item.conditions?.max || 0,
                                            onChange: (value) => updateCondition(groupIndex, 'max', parseFloat(value) || 0)
                                        })
                                    )
                                ),

                                item.filterType === 'min' && el(NumberControl, {
                                    label: 'Wartość minimalna',
                                    value: item.conditions?.min || 0,
                                    onChange: (value) => updateCondition(groupIndex, 'min', parseFloat(value) || 0)
                                }),

                                item.filterType === 'max' && el(NumberControl, {
                                    label: 'Wartość maksymalna',
                                    value: item.conditions?.max || 0,
                                    onChange: (value) => updateCondition(groupIndex, 'max', parseFloat(value) || 0)
                                }),

                                item.filterType === 'exact' && el(TextControl, {
                                    label: 'Dokładna wartość',
                                    value: item.conditions?.value || '',
                                    onChange: (value) => updateCondition(groupIndex, 'value', value)
                                }),

                                item.filterType === 'list' && el(Fragment, {},
                                    el('h4', { style: { margin: '16px 0 8px' } }, 'Lista wartości:'),
                                    el(TextControl, {
                                        label: 'Wartości (oddzielone przecinkami)',
                                        value: Array.isArray(item.conditions?.values) ? item.conditions.values.join(', ') : '',
                                        onChange: (value) => {
                                            const values = value.split(',').map(v => v.trim()).filter(v => v);
                                            updateCondition(groupIndex, 'values', values);
                                        },
                                        placeholder: '1, 2, 3',
                                        help: 'Wprowadź wartości oddzielone przecinkami'
                                    })
                                ),

                                el('hr'),
                                el('div', { style: { textAlign: 'center' } },
                                    el('button', {
                                        type: 'button',
                                        onClick: () => removeFilter(groupIndex),
                                        className: 'button',
                                        style: { color: '#d63638' }
                                    }, 'Usuń ten filtr')
                                )
                                ) // Zamknięcie item.enabled && el(Fragment
                            ) // Zamknięcie // Filter interface
                        )
                    ),

                    // Add new items buttons
                    el(PanelBody, {
                        title: 'Dodaj elementy',
                        initialOpen: false
                    },
                        el('div', { style: { display: 'grid', gap: '12px' } },
                            el('button', {
                                type: 'button',
                                onClick: addNewSection,
                                className: 'button button-secondary',
                                style: { width: '100%' }
                            }, '+ Dodaj sekcję'),
                            el('button', {
                                type: 'button',
                                onClick: addNewFilter,
                                className: 'button button-primary',
                                style: { width: '100%' }
                            }, '+ Dodaj filtr')
                        )
                    ),

                    // Styling panel
                    el(PanelBody, {
                        title: 'Stylizacja widgetu',
                        initialOpen: false
                    },
                        el(TextControl, {
                            label: 'Tytuł widgetu',
                            value: attributes.widgetTitle,
                            onChange: (value) => setAttributes({ widgetTitle: value })
                        }),
                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Kolory podstawowe'),
                        CompactColorPicker('headerBgColor', 'Tło nagłówka', attributes.headerBgColor, (color) => setAttributes({ headerBgColor: color })),
                        CompactColorPicker('headerTextColor', 'Tekst nagłówka', attributes.headerTextColor, (color) => setAttributes({ headerTextColor: color })),
                        CompactColorPicker('borderColor', 'Kolor ramki', attributes.borderColor, (color) => setAttributes({ borderColor: color })),
                        CompactColorPicker('groupBgColor', 'Tło grup filtrów', attributes.groupBgColor, (color) => setAttributes({ groupBgColor: color })),
                        CompactColorPicker('textColor', 'Tekst filtrów', attributes.textColor, (color) => setAttributes({ textColor: color })),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Przycisk "Zastosuj"'),
                        el(TextControl, {
                            label: 'Tekst przycisku',
                            value: attributes.applyBtnText,
                            onChange: (value) => setAttributes({ applyBtnText: value })
                        }),
                        CompactColorPicker('applyBtnBgColor', 'Tło przycisku', attributes.applyBtnBgColor, (color) => setAttributes({ applyBtnBgColor: color })),
                        CompactColorPicker('applyBtnTextColor', 'Tekst przycisku', attributes.applyBtnTextColor, (color) => setAttributes({ applyBtnTextColor: color })),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Przycisk "Wyczyść"'),
                        el(TextControl, {
                            label: 'Tekst przycisku',
                            value: attributes.clearBtnText,
                            onChange: (value) => setAttributes({ clearBtnText: value })
                        }),
                        CompactColorPicker('clearBtnBgColor', 'Tło przycisku', attributes.clearBtnBgColor, (color) => setAttributes({ clearBtnBgColor: color })),
                        CompactColorPicker('clearBtnTextColor', 'Tekst przycisku', attributes.clearBtnTextColor, (color) => setAttributes({ clearBtnTextColor: color })),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Czcionki'),
                        el(SelectControl, {
                            label: 'Czcionka nagłówka',
                            value: attributes.headerFontFamily,
                            options: [
                                { label: 'Domyślna', value: 'inherit' },
                                { label: 'Arial', value: 'Arial, sans-serif' },
                                { label: 'Helvetica', value: 'Helvetica, sans-serif' },
                                { label: 'Georgia', value: 'Georgia, serif' },
                                { label: 'Times New Roman', value: '"Times New Roman", serif' },
                                { label: 'Verdana', value: 'Verdana, sans-serif' }
                            ],
                            onChange: (value) => setAttributes({ headerFontFamily: value })
                        }),
                        el(SelectControl, {
                            label: 'Czcionka treści',
                            value: attributes.contentFontFamily,
                            options: [
                                { label: 'Domyślna', value: 'inherit' },
                                { label: 'Arial', value: 'Arial, sans-serif' },
                                { label: 'Helvetica', value: 'Helvetica, sans-serif' },
                                { label: 'Georgia', value: 'Georgia, serif' },
                                { label: 'Times New Roman', value: '"Times New Roman", serif' },
                                { label: 'Verdana', value: 'Verdana, sans-serif' }
                            ],
                            onChange: (value) => setAttributes({ contentFontFamily: value })
                        })
                    ),

                    // Layout & Spacing Panel
                    el(PanelBody, {
                        title: 'Layout i odstępy',
                        initialOpen: false
                    },
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Widget'),
                        el(TextControl, {
                            label: 'Padding widgetu',
                            value: attributes.widgetPadding,
                            onChange: (value) => setAttributes({ widgetPadding: value }),
                            placeholder: '20px'
                        }),
                        el(TextControl, {
                            label: 'Border radius widgetu',
                            value: attributes.widgetBorderRadius,
                            onChange: (value) => setAttributes({ widgetBorderRadius: value }),
                            placeholder: '8px'
                        }),
                        el(TextControl, {
                            label: 'Grubość ramki widgetu',
                            value: attributes.widgetBorderWidth,
                            onChange: (value) => setAttributes({ widgetBorderWidth: value }),
                            placeholder: '1px'
                        }),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Grupy filtrów'),
                        el(TextControl, {
                            label: 'Padding grup filtrów',
                            value: attributes.filterGroupPadding,
                            onChange: (value) => setAttributes({ filterGroupPadding: value }),
                            placeholder: '12px'
                        }),
                        el(TextControl, {
                            label: 'Border radius grup',
                            value: attributes.filterGroupBorderRadius,
                            onChange: (value) => setAttributes({ filterGroupBorderRadius: value }),
                            placeholder: '6px'
                        }),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Przyciski'),
                        el(TextControl, {
                            label: 'Padding przycisków',
                            value: attributes.buttonPadding,
                            onChange: (value) => setAttributes({ buttonPadding: value }),
                            placeholder: '10px 20px'
                        }),
                        el(TextControl, {
                            label: 'Border radius przycisków',
                            value: attributes.buttonBorderRadius,
                            onChange: (value) => setAttributes({ buttonBorderRadius: value }),
                            placeholder: '5px'
                        })
                    ),

                    // Typography Panel
                    el(PanelBody, {
                        title: 'Typografia',
                        initialOpen: false
                    },
                        el(TextControl, {
                            label: 'Rozmiar tytułów sekcji',
                            value: attributes.sectionTitleFontSize,
                            onChange: (value) => setAttributes({ sectionTitleFontSize: value }),
                            placeholder: '1.1em'
                        }),
                        el(TextControl, {
                            label: 'Rozmiar czcionki przycisków',
                            value: attributes.buttonFontSize,
                            onChange: (value) => setAttributes({ buttonFontSize: value }),
                            placeholder: '0.95em'
                        }),
                        el(TextControl, {
                            label: 'Rozmiar czcionki filtrów',
                            value: attributes.filterTextFontSize,
                            onChange: (value) => setAttributes({ filterTextFontSize: value }),
                            placeholder: '0.95em'
                        })
                    ),

                    // Border Colors Panel
                    el(PanelBody, {
                        title: 'Kolory ramek i dodatków',
                        initialOpen: false
                    },
                        CompactColorPicker('sectionTitleBorderColor', 'Ramka tytułów sekcji', attributes.sectionTitleBorderColor, (color) => setAttributes({ sectionTitleBorderColor: color })),
                        CompactColorPicker('filterGroupBorderColor', 'Ramka grup filtrów', attributes.filterGroupBorderColor, (color) => setAttributes({ filterGroupBorderColor: color })),
                        CompactColorPicker('actionsBorderColor', 'Ramka sekcji przycisków', attributes.actionsBorderColor, (color) => setAttributes({ actionsBorderColor: color }))
                    ),

                    // Advanced Spacing Panel (Priority 2)
                    el(PanelBody, {
                        title: 'Zaawansowane odstępy',
                        initialOpen: false
                    },
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Odstępy między elementami'),
                        el(TextControl, {
                            label: 'Gap formularza',
                            value: attributes.filtersGap,
                            onChange: (value) => setAttributes({ filtersGap: value }),
                            placeholder: '16px',
                            help: 'Odstęp między sekcjami formularza'
                        }),
                        el(TextControl, {
                            label: 'Gap kontenera filtrów',
                            value: attributes.filtersContainerGap,
                            onChange: (value) => setAttributes({ filtersContainerGap: value }),
                            placeholder: '12px',
                            help: 'Odstęp między grupami filtrów'
                        }),
                        el(TextControl, {
                            label: 'Gap przycisków',
                            value: attributes.actionsGap,
                            onChange: (value) => setAttributes({ actionsGap: value }),
                            placeholder: '12px',
                            help: 'Odstęp między przyciskami akcji'
                        }),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Marginsy i paddingi'),
                        el(TextControl, {
                            label: 'Padding-top sekcji przycisków',
                            value: attributes.actionsPaddingTop,
                            onChange: (value) => setAttributes({ actionsPaddingTop: value }),
                            placeholder: '16px'
                        }),
                        el(TextControl, {
                            label: 'Padding-bottom tytułu',
                            value: attributes.titlePaddingBottom,
                            onChange: (value) => setAttributes({ titlePaddingBottom: value }),
                            placeholder: '8px'
                        }),
                        el(TextControl, {
                            label: 'Margin-bottom tytułu',
                            value: attributes.titleMarginBottom,
                            onChange: (value) => setAttributes({ titleMarginBottom: value }),
                            placeholder: '16px'
                        })
                    ),

                    // Advanced Button Options (Priority 2)
                    el(PanelBody, {
                        title: 'Zaawansowane opcje przycisków',
                        initialOpen: false
                    },
                        el(SelectControl, {
                            label: 'Grubość czcionki przycisków',
                            value: attributes.buttonFontWeight,
                            options: [
                                { label: 'Normal (400)', value: '400' },
                                { label: 'Medium (500)', value: '500' },
                                { label: 'Półgruby (600)', value: '600' },
                                { label: 'Gruby (700)', value: '700' }
                            ],
                            onChange: (value) => setAttributes({ buttonFontWeight: value })
                        }),
                        el(TextControl, {
                            label: 'Animacja przycisku',
                            value: attributes.buttonTransition,
                            onChange: (value) => setAttributes({ buttonTransition: value }),
                            placeholder: 'all 0.2s ease',
                            help: 'CSS transition property'
                        }),
                        el(TextControl, {
                            label: 'Transform on hover',
                            value: attributes.buttonHoverTransform,
                            onChange: (value) => setAttributes({ buttonHoverTransform: value }),
                            placeholder: 'translateY(-1px)',
                            help: 'CSS transform na hover'
                        })
                    ),

                    // Focus States Panel (Priority 2)
                    el(PanelBody, {
                        title: 'Stany focus i dostępność',
                        initialOpen: false
                    },
                        CompactColorPicker('focusOutlineColor', 'Kolor outline focus', attributes.focusOutlineColor, (color) => setAttributes({ focusOutlineColor: color })),
                        el(TextControl, {
                            label: 'Grubość outline focus',
                            value: attributes.focusOutlineWidth,
                            onChange: (value) => setAttributes({ focusOutlineWidth: value }),
                            placeholder: '2px'
                        }),
                        el(TextControl, {
                            label: 'Offset outline focus',
                            value: attributes.focusOutlineOffset,
                            onChange: (value) => setAttributes({ focusOutlineOffset: value }),
                            placeholder: '2px',
                            help: 'Odstęp outline od elementu'
                        })
                    )
                ),

                // Block preview in editor
                el('div', {
                    className: 'resources-filters-block-placeholder',
                    style: {
                        padding: '20px',
                        border: '2px dashed #ddd',
                        borderRadius: '8px',
                        textAlign: 'center',
                        backgroundColor: '#f9f9f9'
                    }
                },
                    el('h3', { style: { margin: '0 0 16px' } }, 'Filtry Zasobów'),

                    // Show sections and enabled filters preview
                    attributes.filterGroups.filter(group => group.type === 'section' || group.enabled).length > 0 ?
                        el('div', { style: { textAlign: 'left' } },
                            el('h4', {}, 'Konfiguracja filtrów:'),
                            el('ul', {},
                                attributes.filterGroups
                                    .filter(group => group.type === 'section' || group.enabled)
                                    .map(group =>
                                        el('li', {
                                            key: group.id,
                                            style: group.type === 'section' ? {
                                                fontWeight: 'bold',
                                                color: '#2271b1',
                                                marginTop: '8px'
                                            } : {}
                                        }, group.type === 'section' ? `📁 ${group.title}` : `✓ ${group.label}`)
                                    )
                            )
                        ) :
                        el('p', { style: { color: '#666', fontStyle: 'italic' } },
                            'Brak włączonych filtrów. Skonfiguruj filtry w panelu po prawej.'
                        ),

                    el('p', { style: { fontSize: '12px', color: '#666', marginTop: '16px' } },
                        'Ten widget będzie wyświetlał formularz z checkboxami dla skonfigurowanych filtrów.'
                    )
                )
            );
        },

        save: function() {
            // Dynamic block - rendered in PHP
            return null;
        }
    });
})();