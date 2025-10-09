(function() {
    const { registerBlockType } = wp.blocks;
    const { createElement: el, Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, CheckboxControl, TextControl, SelectControl } = wp.components;

    // Available columns with their default names - keys match PHP DTO constants
    const AVAILABLE_COLUMNS = {
        // ResourceDto fields
        'unit_number': 'Numer lokalu',                    // ResourceDto::FIELD_NR_LOKALU
        'property_type': 'Typ nieruchomości',             // ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI
        'usable_area': 'Powierzchnia',                    // ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA
        'status': 'Status',                               // ResourceDto::FIELD_STATUS
        'floor_number': 'Piętro',                         // ResourceDto::FIELD_FLOOR_NUMBER
        'room_count': 'Pokoje',                           // ResourceDto::FIELD_ROOM_COUNT
        'additional_description': 'Opis',                 // ResourceDto::FIELD_ADDITIONAL_DESCRIPTION
        'garden_area': 'Ogród',                           // ResourceDto::FIELD_GARDEN_AREA
        'floor_plan_pdf': 'Plan',                         // ResourceDto::FIELD_FLOOR_PLAN_PDF

        // PriceHistoryDto fields
        'price_per_m2': 'Cena za m²',                     // PriceHistoryDto::FIELD_CENA_M2
        'total_price': 'Cena całkowita',                  // PriceHistoryDto::FIELD_CENA_CALKOWITA
        'price_with_extras': 'Cena z dodatkami',          // PriceHistoryDto::FIELD_CENA_Z_DODATKAMI

        // Functional columns
        'historia_cen': 'Historia cen',                    // ResourceTableRenderer::COLUMN_HISTORIA_CEN
        'zobacz_wiecej': ''                                // ResourceTableRenderer::COLUMN_ZOBACZ_WIECEJ
    };

    // Available property types
    const PROPERTY_TYPES = {
        'residential_unit': 'Lokale mieszkalne',
        'single_family_house': 'Domy jednorodzinne',
        'service_premises': 'Lokale usługowe',
        'parking_space': 'Miejsca postojowe',
        'storage_room': 'Komórki lokatorskie',
        'garage': 'Garaże'
    };

    // Font options
    const FONT_OPTIONS = [
        { label: 'Domyślna', value: 'inherit' },
        { label: 'Arial', value: 'Arial, sans-serif' },
        { label: 'Helvetica', value: 'Helvetica, sans-serif' },
        { label: 'Georgia', value: 'Georgia, serif' },
        { label: 'Times New Roman', value: '"Times New Roman", serif' },
        { label: 'Verdana', value: 'Verdana, sans-serif' }
    ];

    // Status display style options
    const STATUS_STYLE_OPTIONS = [
        { label: 'Badge (prostokąt z ramką)', value: 'badge' },
        { label: 'Pill (zaokrąglony)', value: 'pill' },
        { label: 'Highlight (podświetlenie)', value: 'highlight' },
        { label: 'Underline (podkreślenie)', value: 'underline' },
        { label: 'Plain (tylko tekst)', value: 'plain' }
    ];

    // Sorting options
    const SORT_OPTIONS = [
        { label: 'Brak sortowania', value: '' },
        { label: 'ID', value: 'id' },
        { label: 'Numer lokalu', value: 'unit_number' },
        { label: 'Typ nieruchomości', value: 'property_type' },
        { label: 'Powierzchnia', value: 'usable_area' },
        { label: 'Cena', value: 'total_price' },
        { label: 'Status', value: 'status' },
        { label: 'Piętro', value: 'floor_number' },
        { label: 'Pokoje', value: 'room_count' }
    ];

    // Status filter options
    const STATUS_FILTER_OPTIONS = [
        { label: 'Dostępny', value: 'available' },
        { label: 'Sprzedany', value: 'sold' },
        { label: 'Zarezerwowany', value: 'reserved' }
    ];

    registerBlockType('jawne-ceny/resources-list', {
        title: 'Lista Zasobów',
        description: 'Wyświetla listę mieszkań/zasobów z cenami - z pełną personalizacją',
        category: 'widgets',
        icon: 'list-view',
        keywords: ['mieszkania', 'zasoby', 'ceny', 'lista', 'nieruchomości'],

        attributes: {
            // Column configuration
            visibleColumns: {
                type: 'array',
                default: ['unit_number', 'property_type', 'usable_area', 'total_price', 'status', 'historia_cen']
            },
            columnOrder: {
                type: 'array',
                default: ['unit_number', 'property_type', 'usable_area', 'total_price', 'status', 'historia_cen']
            },
            columnNames: {
                type: 'object',
                default: {}
            },

            // Property type filtering
            selectedTypes: {
                type: 'array',
                default: ['residential_unit']
            },

            // Styling options
            headerBgColor: {
                type: 'string',
                default: '#f9f9f9'
            },
            headerTextColor: {
                type: 'string',
                default: '#333333'
            },
            textColor: {
                type: 'string',
                default: '#333333'
            },
            hoverBgColor: {
                type: 'string',
                default: '#f5f5f5'
            },
            headerFontFamily: {
                type: 'string',
                default: 'inherit'
            },
            contentFontFamily: {
                type: 'string',
                default: 'inherit'
            },

            // Display Mode Options
            displayMode: {
                type: 'string',
                default: 'table' // 'table' | 'cards'
            },

            // Card-specific Options
            cardsPerRow: {
                type: 'number',
                default: 3
            },
            cardGap: {
                type: 'string',
                default: '20px'
            },
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
                default: '16px'
            },
            cardShadow: {
                type: 'string',
                default: '0 2px 4px rgba(0,0,0,0.1)'
            },
            cardHoverShadow: {
                type: 'string',
                default: '0 4px 12px rgba(0,0,0,0.15)'
            },

            // Interactive options
            detailPageUrl: {
                type: 'string',
                default: ''
            },

            // Navigation mode
            navigationMode: {
                type: 'string',
                default: ''
            },
            zobaczBtnText: {
                type: 'string',
                default: 'Zobacz więcej'
            },
            zobaczBtnBgColor: {
                type: 'string',
                default: '#007cba'
            },
            zobaczBtnTextColor: {
                type: 'string',
                default: '#ffffff'
            },
            zobaczBtnPadding: {
                type: 'string',
                default: '6px 12px'
            },
            zobaczBtnBorderRadius: {
                type: 'string',
                default: '4px'
            },
            zobaczBtnFontSize: {
                type: 'string',
                default: '0.875em'
            },

            // Button customization
            historiaBtnText: {
                type: 'string',
                default: 'Historia'
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
            },

            // Status styling options
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

            // Filtering and sorting options
            enableFilters: {
                type: 'boolean',
                default: false
            },
            priceMin: {
                type: 'number',
                default: null
            },
            priceMax: {
                type: 'number',
                default: null
            },
            areaMin: {
                type: 'number',
                default: null
            },
            areaMax: {
                type: 'number',
                default: null
            },
            statusFilter: {
                type: 'array',
                default: []
            },
            floorFilter: {
                type: 'array',
                default: []
            },
            roomsFilter: {
                type: 'array',
                default: []
            },
            sortBy: {
                type: 'string',
                default: ''
            },
            sortOrder: {
                type: 'string',
                default: 'ASC'
            },


            // URL filter integration
            readFiltersFromUrl: {
                type: 'boolean',
                default: false
            },

            // Frontend sorting
            enableFrontendSorting: {
                type: 'boolean',
                default: false
            },

            // Layout & Spacing Options (Priority 1)
            containerMargin: {
                type: 'string',
                default: '20px 0'
            },
            tablePadding: {
                type: 'string',
                default: '12px 15px'
            },
            buttonPadding: {
                type: 'string',
                default: '6px 12px'
            },
            buttonBorderRadius: {
                type: 'string',
                default: '4px'
            },
            buttonFontSize: {
                type: 'string',
                default: '0.875em'
            },

            // Modal Options (Priority 1)
            modalBackgroundColor: {
                type: 'string',
                default: '#ffffff'
            },
            modalBorderRadius: {
                type: 'string',
                default: '8px'
            },
            modalMaxWidth: {
                type: 'string',
                default: '600px'
            },
            modalPadding: {
                type: 'string',
                default: '20px'
            },

            // Advanced Responsive Options (Priority 2)
            mobileBreakpoint: {
                type: 'string',
                default: '768px'
            },
            tabletBreakpoint: {
                type: 'string',
                default: '1024px'
            },
            mobileHiddenColumns: {
                type: 'array',
                default: ['property_type']
            },
            smallMobileHiddenColumns: {
                type: 'array',
                default: ['property_type', 'total_price']
            },
            mobileTableFontSize: {
                type: 'string',
                default: '0.875em'
            },
            mobilePadding: {
                type: 'string',
                default: '8px 10px'
            },
            smallMobilePadding: {
                type: 'string',
                default: '6px 8px'
            },

            // Advanced Table Behavior (Priority 2)
            tableMinWidth: {
                type: 'string',
                default: '600px'
            },

            // Advanced Button Options (Priority 2)
            buttonFontWeight: {
                type: 'string',
                default: 'normal'
            },
            buttonTransition: {
                type: 'string',
                default: 'all 0.2s ease'
            },
            buttonHoverTransform: {
                type: 'string',
                default: 'none'
            },
            buttonHoverBoxShadow: {
                type: 'string',
                default: 'none'
            },
            buttonBorderWidth: {
                type: 'string',
                default: '0'
            },
            buttonBorderColor: {
                type: 'string',
                default: 'transparent'
            },


            // Advanced Modal Options (Priority 2)
            modalAnimationDuration: {
                type: 'string',
                default: '0.3s'
            },
            modalBoxShadow: {
                type: 'string',
                default: '0 10px 30px rgba(0,0,0,0.3)'
            },
            modalHeaderBorderColor: {
                type: 'string',
                default: '#dee2e6'
            },
            modalCloseBtnColor: {
                type: 'string',
                default: '#666666'
            },
            modalCloseBtnHoverColor: {
                type: 'string',
                default: '#000000'
            },
            modalZIndex: {
                type: 'string',
                default: '10000'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;

            // Initialize compact color picker
            const CompactColorPicker = window.createCompactColorPickerFactory(wp)();

            // Helper function to handle property type selection
            const togglePropertyType = function(type, checked) {
                const newTypes = checked
                    ? [...attributes.selectedTypes, type]
                    : attributes.selectedTypes.filter(t => t !== type);

                setAttributes({ selectedTypes: newTypes });
            };

            // Helper function to add column to the end of visible list
            const addColumn = function(column) {
                if (!attributes.visibleColumns.includes(column)) {
                    const newColumns = [...attributes.visibleColumns, column];
                    const newOrder = [...attributes.columnOrder.filter(col => newColumns.includes(col)), column];

                    setAttributes({
                        visibleColumns: newColumns,
                        columnOrder: newOrder
                    });
                }
            };

            // Helper function to remove column from visible list
            const removeColumn = function(column) {
                const newColumns = attributes.visibleColumns.filter(col => col !== column);
                const newOrder = attributes.columnOrder.filter(col => newColumns.includes(col));

                // Also remove custom name if exists
                const newNames = { ...attributes.columnNames };
                delete newNames[column];

                setAttributes({
                    visibleColumns: newColumns,
                    columnOrder: newOrder,
                    columnNames: newNames
                });
            };

            // Function for updating column names - immediate updates for smooth UX
            const updateColumnName = function(column, newName) {
                const newNames = { ...attributes.columnNames };

                if (newName && newName.trim() !== '' && newName !== AVAILABLE_COLUMNS[column]) {
                    // Save custom name (preserve spaces)
                    newNames[column] = newName;
                } else {
                    // Remove custom name (use default)
                    delete newNames[column];
                }

                setAttributes({ columnNames: newNames });
            };

            // Drag & Drop handlers for column reordering
            const handleDragStart = function(e, draggedColumn) {
                e.dataTransfer.setData('text/plain', draggedColumn);
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

                const draggedColumn = e.dataTransfer.getData('text/plain');
                if (!draggedColumn || draggedColumn === dropTarget) {
                    return; // No change needed
                }

                // Create new column order
                const currentOrder = [...attributes.columnOrder];
                const draggedIndex = currentOrder.indexOf(draggedColumn);
                const dropIndex = currentOrder.indexOf(dropTarget);

                if (draggedIndex === -1 || dropIndex === -1) {
                    return; // Invalid indexes
                }

                // Remove dragged item and insert at new position
                currentOrder.splice(draggedIndex, 1);
                currentOrder.splice(dropIndex, 0, draggedColumn);

                setAttributes({ columnOrder: currentOrder });
            };

            return el(Fragment, {},
                // Inspector Controls (sidebar panel)
                el(InspectorControls, {},

                    // Display Mode Panel - FIRST
                    el(PanelBody, {
                        title: 'Sposób wyświetlania',
                        initialOpen: true
                    },
                        el(SelectControl, {
                            label: 'Widok',
                            value: attributes.displayMode,
                            options: [
                                { label: 'Tabela', value: 'table' },
                                { label: 'Kafelki', value: 'cards' }
                            ],
                            onChange: (value) => setAttributes({ displayMode: value })
                        }),

                        // Conditional Card Settings - only show when cards mode is selected
                        attributes.displayMode === 'cards' && el(Fragment, {},
                            el('hr', { style: { margin: '16px 0' } }),
                            el('h4', { style: { margin: '0 0 12px', fontSize: '14px' } }, 'Ustawienia kafelków'),

                            el(SelectControl, {
                                label: 'Kafelków na rząd',
                                value: attributes.cardsPerRow,
                                options: [
                                    { label: '2 kolumny', value: 2 },
                                    { label: '3 kolumny', value: 3 },
                                    { label: '4 kolumny', value: 4 }
                                ],
                                onChange: (value) => setAttributes({ cardsPerRow: parseInt(value) })
                            }),

                            el(TextControl, {
                                label: 'Odstęp między kafelkami',
                                value: attributes.cardGap,
                                onChange: (value) => setAttributes({ cardGap: value }),
                                placeholder: '20px'
                            }),

                            el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginTop: '12px' } },
                                CompactColorPicker('cardBgColor', 'Tło kafelka', attributes.cardBgColor, (color) => setAttributes({ cardBgColor: color })),
                                CompactColorPicker('cardBorderColor', 'Kolor ramki', attributes.cardBorderColor, (color) => setAttributes({ cardBorderColor: color }))
                            ),

                            el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginTop: '8px' } },
                                el(TextControl, {
                                    label: 'Zaokrąglenie rogów',
                                    value: attributes.cardBorderRadius,
                                    onChange: (value) => setAttributes({ cardBorderRadius: value }),
                                    placeholder: '8px'
                                }),
                                el(TextControl, {
                                    label: 'Padding kafelka',
                                    value: attributes.cardPadding,
                                    onChange: (value) => setAttributes({ cardPadding: value }),
                                    placeholder: '16px'
                                })
                            ),

                            el(TextControl, {
                                label: 'Cień kafelka',
                                value: attributes.cardShadow,
                                onChange: (value) => setAttributes({ cardShadow: value }),
                                placeholder: '0 2px 4px rgba(0,0,0,0.1)',
                                help: 'CSS box-shadow dla kafelka'
                            }),

                            el(TextControl, {
                                label: 'Cień po hover',
                                value: attributes.cardHoverShadow,
                                onChange: (value) => setAttributes({ cardHoverShadow: value }),
                                placeholder: '0 4px 12px rgba(0,0,0,0.15)',
                                help: 'CSS box-shadow po najechaniu myszką'
                            })
                        )
                    ),

                    // Property Types Panel
                    el(PanelBody, {
                        title: 'Typy nieruchomości',
                        initialOpen: false
                    },
                        Object.entries(PROPERTY_TYPES).map(([value, label]) =>
                            el(CheckboxControl, {
                                key: value,
                                label: label,
                                checked: attributes.selectedTypes.includes(value),
                                onChange: (checked) => togglePropertyType(value, checked)
                            })
                        )
                    ),

                    // Column Order Panel
                    el(PanelBody, {
                        title: 'Kolejność i nazwy kolumn',
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
                            }, 'Przeciągnij kolumny aby zmienić kolejność. Kliknij ✕ aby usunąć.'),

                            // Visible columns list with drag&drop
                            attributes.visibleColumns.length > 0 ?
                                attributes.columnOrder
                                    .filter(col => attributes.visibleColumns.includes(col))
                                    .map((column, index) => {
                                        const defaultName = AVAILABLE_COLUMNS[column] || column;
                                        const customName = attributes.columnNames[column] || '';

                                        return el('div', {
                                            key: column,
                                            className: 'column-item',
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
                                            onDragStart: (e) => handleDragStart(e, column),
                                            onDragEnd: handleDragEnd,
                                            onDragOver: handleDragOver,
                                            onDragLeave: handleDragLeave,
                                            onDrop: (e) => handleDrop(e, column)
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

                                            // Column number
                                            el('span', {
                                                style: {
                                                    marginRight: '8px',
                                                    minWidth: '20px',
                                                    fontSize: '12px',
                                                    color: '#666',
                                                    fontWeight: 'bold'
                                                }
                                            }, (index + 1) + '.'),

                                            // Column name input
                                            el('input', {
                                                type: 'text',
                                                placeholder: defaultName,
                                                value: customName,
                                                onChange: (e) => updateColumnName(column, e.target.value),
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
                                                onClick: () => removeColumn(column),
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
                                                title: 'Usuń kolumnę'
                                            }, '✕')
                                        );
                                    }) :
                                el('p', {
                                    style: {
                                        color: '#666',
                                        fontStyle: 'italic',
                                        margin: '0'
                                    }
                                }, 'Brak widocznych kolumn. Dodaj kolumny poniżej.')
                        )
                    ),

                    // Available Columns Panel
                    el(PanelBody, {
                        title: 'Dostępne kolumny',
                        initialOpen: false
                    },
                        el('div', {
                            style: {
                                display: 'grid',
                                gridTemplateColumns: '1fr 1fr',
                                gap: '8px'
                            }
                        },
                            Object.entries(AVAILABLE_COLUMNS)
                                .filter(([column]) => !attributes.visibleColumns.includes(column))
                                .map(([column, defaultName]) =>
                                    el('button', {
                                        key: column,
                                        type: 'button',
                                        onClick: () => addColumn(column),
                                        style: {
                                            padding: '6px 8px',
                                            backgroundColor: '#f0f0f1',
                                            border: '1px solid #c3c4c7',
                                            borderRadius: '3px',
                                            fontSize: '12px',
                                            cursor: 'pointer',
                                            textAlign: 'left'
                                        },
                                        title: 'Dodaj kolumnę'
                                    }, '+ ' + defaultName)
                                )
                        ),
                        Object.entries(AVAILABLE_COLUMNS).filter(([column]) => !attributes.visibleColumns.includes(column)).length === 0 &&
                        el('p', {
                            style: {
                                color: '#666',
                                fontStyle: 'italic',
                                margin: '0'
                            }
                        }, 'Wszystkie kolumny są już dodane.')
                    ),

                    // Styling Panel
                    el(PanelBody, {
                        title: 'Stylizacja tabeli',
                        initialOpen: false
                    },
                        CompactColorPicker('headerBgColor', 'Kolor tła nagłówka', attributes.headerBgColor, (color) => setAttributes({ headerBgColor: color })),
                        CompactColorPicker('headerTextColor', 'Kolor tekstu nagłówka', attributes.headerTextColor, (color) => setAttributes({ headerTextColor: color })),
                        CompactColorPicker('textColor', 'Kolor tekstu treści', attributes.textColor, (color) => setAttributes({ textColor: color })),
                        CompactColorPicker('hoverBgColor', 'Kolor hover', attributes.hoverBgColor, (color) => setAttributes({ hoverBgColor: color })),
                        el(SelectControl, {
                            label: 'Czcionka nagłówków',
                            value: attributes.headerFontFamily,
                            options: FONT_OPTIONS,
                            onChange: (value) => setAttributes({ headerFontFamily: value })
                        }),
                        el(SelectControl, {
                            label: 'Czcionka treści',
                            value: attributes.contentFontFamily,
                            options: FONT_OPTIONS,
                            onChange: (value) => setAttributes({ contentFontFamily: value })
                        })
                    ),

                    // Interactive Features Panel
                    // === NOWA SEKCJA: Nawigacja do szczegółów ===
                    el(PanelBody, {
                        title: 'Nawigacja do szczegółów',
                        initialOpen: false
                    },
                        // URL input (zawsze widoczny)
                        el(TextControl, {
                            label: 'URL strony szczegółów',
                            placeholder: '/mieszkania/',
                            value: attributes.detailPageUrl,
                            onChange: (value) => setAttributes({ detailPageUrl: value }),
                            help: 'Podaj URL bazowy (np. /mieszkania/). Numer lokalu zostanie dodany automatycznie.'
                        }),

                        // Separator + Navigation mode picker (tylko gdy URL ustawiony)
                        attributes.detailPageUrl && el(Fragment, {},
                            el('hr', { style: { margin: '16px 0' } }),
                            el('h4', { style: { margin: '0 0 8px', fontSize: '13px', fontWeight: '500' } }, 'Sposób nawigacji'),
                            el('div', { style: { marginBottom: '12px' } },
                                el('label', {
                                    style: { display: 'block', marginBottom: '8px', cursor: 'pointer' }
                                },
                                    el('input', {
                                        type: 'radio',
                                        name: 'navigationMode',
                                        value: '',
                                        checked: attributes.navigationMode === '',
                                        onChange: () => setAttributes({ navigationMode: '' }),
                                        style: { marginRight: '6px' }
                                    }),
                                    'Klikalne wiersze/karty'
                                ),
                                el('label', {
                                    style: { display: 'block', cursor: 'pointer' }
                                },
                                    el('input', {
                                        type: 'radio',
                                        name: 'navigationMode',
                                        value: 'button',
                                        checked: attributes.navigationMode === 'button',
                                        onChange: () => setAttributes({ navigationMode: 'button' }),
                                        style: { marginRight: '6px' }
                                    }),
                                    'Przycisk "Zobacz więcej"'
                                )
                            )
                        ),

                        // Separator + Button styling (tylko gdy button mode wybrany)
                        attributes.detailPageUrl && attributes.navigationMode === 'button' && el(Fragment, {},
                            el('hr', { style: { margin: '16px 0' } }),
                            el('h4', { style: { margin: '0 0 12px', fontSize: '13px', fontWeight: '500' } }, 'Stylizacja przycisku "Zobacz więcej"'),

                            el(TextControl, {
                                label: 'Tekst przycisku',
                                value: attributes.zobaczBtnText,
                                onChange: (value) => setAttributes({ zobaczBtnText: value }),
                                placeholder: 'Zobacz więcej'
                            }),

                            CompactColorPicker('zobaczBtnBgColor', 'Kolor tła', attributes.zobaczBtnBgColor, (color) => setAttributes({ zobaczBtnBgColor: color })),

                            CompactColorPicker('zobaczBtnTextColor', 'Kolor tekstu', attributes.zobaczBtnTextColor, (color) => setAttributes({ zobaczBtnTextColor: color })),

                            el(TextControl, {
                                label: 'Padding',
                                value: attributes.zobaczBtnPadding,
                                onChange: (value) => setAttributes({ zobaczBtnPadding: value }),
                                placeholder: '6px 12px',
                                help: 'Odstęp wewnętrzny (np. 6px 12px)'
                            }),

                            el(TextControl, {
                                label: 'Border radius',
                                value: attributes.zobaczBtnBorderRadius,
                                onChange: (value) => setAttributes({ zobaczBtnBorderRadius: value }),
                                placeholder: '4px',
                                help: 'Zaokrąglenie rogów (np. 4px)'
                            }),

                            el(TextControl, {
                                label: 'Rozmiar czcionki',
                                value: attributes.zobaczBtnFontSize,
                                onChange: (value) => setAttributes({ zobaczBtnFontSize: value }),
                                placeholder: '0.875em',
                                help: 'Rozmiar tekstu (np. 0.875em lub 14px)'
                            })
                        )
                    ),

                    el(PanelBody, {
                        title: 'Funkcje interaktywne',
                        initialOpen: false
                    },
                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Przycisk Historia Cen'),
                        el(TextControl, {
                            label: 'Tekst przycisku',
                            value: attributes.historiaBtnText,
                            onChange: (value) => setAttributes({ historiaBtnText: value })
                        }),
                        CompactColorPicker('historiaBtnBgColor', 'Kolor tła', attributes.historiaBtnBgColor, (color) => setAttributes({ historiaBtnBgColor: color })),
                        CompactColorPicker('historiaBtnTextColor', 'Kolor tekstu', attributes.historiaBtnTextColor, (color) => setAttributes({ historiaBtnTextColor: color })),
                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Przycisk Karta Lokalu'),
                        el(TextControl, {
                            label: 'Tekst przycisku',
                            value: attributes.kartaBtnText,
                            onChange: (value) => setAttributes({ kartaBtnText: value })
                        }),
                        CompactColorPicker('kartaBtnBgColor', 'Kolor tła', attributes.kartaBtnBgColor, (color) => setAttributes({ kartaBtnBgColor: color })),
                        CompactColorPicker('kartaBtnTextColor', 'Kolor tekstu', attributes.kartaBtnTextColor, (color) => setAttributes({ kartaBtnTextColor: color }))
                    ),

                    // Status Styling Panel
                    el(PanelBody, {
                        title: 'Stylizacja statusów',
                        initialOpen: false
                    },
                        el(SelectControl, {
                            label: 'Styl wyświetlania statusów',
                            value: attributes.statusDisplayStyle,
                            options: STATUS_STYLE_OPTIONS,
                            onChange: (value) => setAttributes({ statusDisplayStyle: value })
                        }),
                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Status: Dostępny'),
                        CompactColorPicker('statusAvailableBgColor', 'Kolor tła', attributes.statusAvailableBgColor, (color) => setAttributes({ statusAvailableBgColor: color })),
                        CompactColorPicker('statusAvailableTextColor', 'Kolor tekstu', attributes.statusAvailableTextColor, (color) => setAttributes({ statusAvailableTextColor: color })),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Status: Sprzedany'),
                        CompactColorPicker('statusSoldBgColor', 'Kolor tła', attributes.statusSoldBgColor, (color) => setAttributes({ statusSoldBgColor: color })),
                        CompactColorPicker('statusSoldTextColor', 'Kolor tekstu', attributes.statusSoldTextColor, (color) => setAttributes({ statusSoldTextColor: color })),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Status: Zarezerwowany'),
                        CompactColorPicker('statusReservedBgColor', 'Kolor tła', attributes.statusReservedBgColor, (color) => setAttributes({ statusReservedBgColor: color })),
                        CompactColorPicker('statusReservedTextColor', 'Kolor tekstu', attributes.statusReservedTextColor, (color) => setAttributes({ statusReservedTextColor: color })),
                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Dodatkowe opcje stylizacji'),
                        el(TextControl, {
                            label: 'Rozmiar czcionki',
                            value: attributes.statusFontSize,
                            onChange: (value) => setAttributes({ statusFontSize: value }),
                            placeholder: '0.875em'
                        }),
                        el(TextControl, {
                            label: 'Padding',
                            value: attributes.statusPadding,
                            onChange: (value) => setAttributes({ statusPadding: value }),
                            placeholder: '4px 8px'
                        }),
                        el(TextControl, {
                            label: 'Border radius',
                            value: attributes.statusBorderRadius,
                            onChange: (value) => setAttributes({ statusBorderRadius: value }),
                            placeholder: '4px'
                        }),
                        el(SelectControl, {
                            label: 'Grubość czcionki',
                            value: attributes.statusFontWeight,
                            options: [
                                { label: 'Normalna', value: 'normal' },
                                { label: 'Pogrubiona', value: 'bold' },
                                { label: 'Cienka', value: '300' },
                                { label: 'Średnia', value: '500' },
                                { label: 'Bardzo pogrubiona', value: '700' }
                            ],
                            onChange: (value) => setAttributes({ statusFontWeight: value })
                        })
                    ),

                    // Filtering and Sorting Panel
                    el(PanelBody, {
                        title: 'Filtrowanie i sortowanie',
                        initialOpen: false
                    },
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Sortowanie'),
                        el(SelectControl, {
                            label: 'Sortuj według',
                            value: attributes.sortBy,
                            options: SORT_OPTIONS,
                            onChange: (value) => setAttributes({ sortBy: value })
                        }),
                        attributes.sortBy && el(SelectControl, {
                            label: 'Kierunek sortowania',
                            value: attributes.sortOrder,
                            options: [
                                { label: 'Rosnąco (A-Z, 1-9)', value: 'ASC' },
                                { label: 'Malejąco (Z-A, 9-1)', value: 'DESC' }
                            ],
                            onChange: (value) => setAttributes({ sortOrder: value })
                        }),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Filtrowanie cenowe'),
                        el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' } },
                            el(TextControl, {
                                label: 'Cena od (PLN)',
                                type: 'number',
                                value: attributes.priceMin || '',
                                onChange: (value) => setAttributes({ priceMin: value ? parseFloat(value) : null }),
                                placeholder: 'np. 300000'
                            }),
                            el(TextControl, {
                                label: 'Cena do (PLN)',
                                type: 'number',
                                value: attributes.priceMax || '',
                                onChange: (value) => setAttributes({ priceMax: value ? parseFloat(value) : null }),
                                placeholder: 'np. 800000'
                            })
                        ),

                        el('h4', { style: { margin: '16px 0 8px' } }, 'Filtrowanie powierzchni'),
                        el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' } },
                            el(TextControl, {
                                label: 'Powierzchnia od (m²)',
                                type: 'number',
                                value: attributes.areaMin || '',
                                onChange: (value) => setAttributes({ areaMin: value ? parseFloat(value) : null }),
                                placeholder: 'np. 40'
                            }),
                            el(TextControl, {
                                label: 'Powierzchnia do (m²)',
                                type: 'number',
                                value: attributes.areaMax || '',
                                onChange: (value) => setAttributes({ areaMax: value ? parseFloat(value) : null }),
                                placeholder: 'np. 120'
                            })
                        ),

                        el('h4', { style: { margin: '16px 0 8px' } }, 'Filtrowanie według statusu'),
                        el('p', { style: { fontSize: '12px', color: '#666', margin: '0 0 8px 0' } },
                            'Wybierz statusy, które mają być widoczne w liście (pozostaw puste, aby pokazać wszystkie):'
                        ),
                        el('div', { style: { display: 'grid', gap: '8px' } },
                            STATUS_FILTER_OPTIONS.map(option =>
                                el(CheckboxControl, {
                                    key: option.value,
                                    label: option.label,
                                    checked: attributes.statusFilter.includes(option.value),
                                    onChange: (checked) => {
                                        const newFilter = checked
                                            ? [...attributes.statusFilter, option.value]
                                            : attributes.statusFilter.filter(status => status !== option.value);
                                        setAttributes({ statusFilter: newFilter });
                                    }
                                })
                            )
                        ),

                        el('h4', { style: { margin: '16px 0 8px' } }, 'Filtrowanie według pięter'),
                        el(TextControl, {
                            label: 'Numery pięter (oddzielone przecinkami)',
                            value: attributes.floorFilter.join(', '),
                            onChange: (value) => {
                                const floors = value.split(',').map(f => f.trim()).filter(f => f !== '');
                                setAttributes({ floorFilter: floors });
                            },
                            placeholder: 'np. 0, 1, 2, 3 (0 = parter)',
                            help: 'Wybierz konkretne piętra, które mają być widoczne w liście'
                        }),

                        el('h4', { style: { margin: '16px 0 8px' } }, 'Filtrowanie według liczby pokoi'),
                        el(TextControl, {
                            label: 'Liczba pokoi (oddzielone przecinkami)',
                            value: attributes.roomsFilter.join(', '),
                            onChange: (value) => {
                                const rooms = value.split(',').map(r => r.trim()).filter(r => r !== '');
                                setAttributes({ roomsFilter: rooms });
                            },
                            placeholder: 'np. 1, 2, 3, 4',
                            help: 'Wybierz konkretne liczby pokoi, które mają być widoczne w liście'
                        }),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Integracja z widgetem filtrów'),
                        el(CheckboxControl, {
                            label: 'Czytaj filtry z URL',
                            checked: attributes.readFiltersFromUrl,
                            onChange: (checked) => setAttributes({ readFiltersFromUrl: checked }),
                            help: 'Lista będzie czytała filtry ustawione przez widget "Filtry Zasobów" z parametrów URL'
                        }),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Sortowanie na stronie'),
                        el(CheckboxControl, {
                            label: 'Włącz sortowanie dla użytkowników',
                            checked: attributes.enableFrontendSorting,
                            onChange: (checked) => setAttributes({ enableFrontendSorting: checked }),
                            help: 'Użytkownicy będą mogli sortować kolumny klikając nagłówki'
                        })
                    ),

                    // Layout & Spacing Panel
                    el(PanelBody, {
                        title: 'Layout i odstępy',
                        initialOpen: false
                    },
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Kontener'),
                        el(TextControl, {
                            label: 'Margin kontenera',
                            value: attributes.containerMargin,
                            onChange: (value) => setAttributes({ containerMargin: value }),
                            placeholder: '20px 10px',
                            help: 'Format: "top right bottom left" lub "vertical horizontal" - wymagane jednostki CSS (px, em, rem)'
                        }),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Tabela'),
                        el(TextControl, {
                            label: 'Padding komórek tabeli',
                            value: attributes.tablePadding,
                            onChange: (value) => setAttributes({ tablePadding: value }),
                            placeholder: '12px 15px'
                        }),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Przyciski'),
                        el(TextControl, {
                            label: 'Padding przycisków',
                            value: attributes.buttonPadding,
                            onChange: (value) => setAttributes({ buttonPadding: value }),
                            placeholder: '6px 12px'
                        }),
                        el(TextControl, {
                            label: 'Border radius przycisków',
                            value: attributes.buttonBorderRadius,
                            onChange: (value) => setAttributes({ buttonBorderRadius: value }),
                            placeholder: '4px'
                        }),
                        el(TextControl, {
                            label: 'Rozmiar czcionki przycisków',
                            value: attributes.buttonFontSize,
                            onChange: (value) => setAttributes({ buttonFontSize: value }),
                            placeholder: '0.875em'
                        })
                    ),

                    // Modal Styling Panel
                    el(PanelBody, {
                        title: 'Modal historii cen',
                        initialOpen: false
                    },
                        el('h4', { style: { margin: '16px 0 8px 0' } }, 'Podstawowe ustawienia'),
                        CompactColorPicker('modalBackgroundColor', 'Tło modala', attributes.modalBackgroundColor, (color) => setAttributes({ modalBackgroundColor: color })),
                        el(TextControl, {
                            label: 'Maksymalna szerokość',
                            value: attributes.modalMaxWidth,
                            onChange: (value) => setAttributes({ modalMaxWidth: value }),
                            placeholder: '600px'
                        }),
                        el(TextControl, {
                            label: 'Padding modala',
                            value: attributes.modalPadding,
                            onChange: (value) => setAttributes({ modalPadding: value }),
                            placeholder: '20px'
                        }),
                        el(TextControl, {
                            label: 'Border radius modala',
                            value: attributes.modalBorderRadius,
                            onChange: (value) => setAttributes({ modalBorderRadius: value }),
                            placeholder: '8px'
                        }),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Animacje'),
                        el(TextControl, {
                            label: 'Czas animacji otwarcia',
                            value: attributes.modalAnimationDuration,
                            onChange: (value) => setAttributes({ modalAnimationDuration: value }),
                            placeholder: '0.3s',
                            help: 'Czas animacji pojawiania się modala'
                        }),
                        el(TextControl, {
                            label: 'Cień modala',
                            value: attributes.modalBoxShadow,
                            onChange: (value) => setAttributes({ modalBoxShadow: value }),
                            placeholder: '0 10px 30px rgba(0,0,0,0.3)',
                            help: 'Box-shadow dla okna modala'
                        }),
                        el(TextControl, {
                            label: 'Z-index modala',
                            value: attributes.modalZIndex,
                            onChange: (value) => setAttributes({ modalZIndex: value }),
                            placeholder: '10000',
                            help: 'Wartość z-index dla warstwy modala'
                        }),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Stylizacja elementów'),
                        CompactColorPicker('modalHeaderBorderColor', 'Kolor ramki nagłówka', attributes.modalHeaderBorderColor, (color) => setAttributes({ modalHeaderBorderColor: color })),
                        CompactColorPicker('modalCloseBtnColor', 'Kolor przycisku zamknięcia', attributes.modalCloseBtnColor, (color) => setAttributes({ modalCloseBtnColor: color })),
                        CompactColorPicker('modalCloseBtnHoverColor', 'Kolor hover przycisku', attributes.modalCloseBtnHoverColor, (color) => setAttributes({ modalCloseBtnHoverColor: color }))
                    ),

                    // Advanced Responsive Options Panel (Priority 2)
                    el(PanelBody, {
                        title: 'Zaawansowane opcje responsywne',
                        initialOpen: false
                    },
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Punkty przełamania'),
                        el(TextControl, {
                            label: 'Breakpoint mobile',
                            value: attributes.mobileBreakpoint,
                            onChange: (value) => setAttributes({ mobileBreakpoint: value }),
                            placeholder: '768px',
                            help: 'Szerokość ekranu poniżej której stosowane są style mobile'
                        }),
                        el(TextControl, {
                            label: 'Breakpoint tablet',
                            value: attributes.tabletBreakpoint,
                            onChange: (value) => setAttributes({ tabletBreakpoint: value }),
                            placeholder: '1024px'
                        }),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Rozmiary czcionek responsive'),
                        el(TextControl, {
                            label: 'Rozmiar czcionki mobile',
                            value: attributes.mobileTableFontSize,
                            onChange: (value) => setAttributes({ mobileTableFontSize: value }),
                            placeholder: '0.875em'
                        }),

                    ),


                    // Advanced Button Options Panel (Priority 2)
                    el(PanelBody, {
                        title: 'Zaawansowane opcje przycisków',
                        initialOpen: false
                    },
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Typografia przycisków'),
                        el(SelectControl, {
                            label: 'Grubość czcionki przycisków',
                            value: attributes.buttonFontWeight,
                            options: [
                                { label: 'Normalna', value: 'normal' },
                                { label: 'Pogrubiona', value: 'bold' },
                                { label: 'Cienka', value: '300' },
                                { label: 'Średnia', value: '500' },
                                { label: 'Bardzo pogrubiona', value: '700' }
                            ],
                            onChange: (value) => setAttributes({ buttonFontWeight: value })
                        }),

                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Animacje przycisków'),
                        el(TextControl, {
                            label: 'Czas przejścia',
                            value: attributes.buttonTransition,
                            onChange: (value) => setAttributes({ buttonTransition: value }),
                            placeholder: 'all 0.2s ease',
                            help: 'CSS transition dla efektów hover'
                        }),
                        el(TextControl, {
                            label: 'Transformacja hover',
                            value: attributes.buttonHoverTransform,
                            onChange: (value) => setAttributes({ buttonHoverTransform: value }),
                            placeholder: 'translateY(-2px)',
                            help: 'np. "translateY(-2px)" dla uniesienia'
                        }),
                        el(TextControl, {
                            label: 'Cień hover',
                            value: attributes.buttonHoverBoxShadow,
                            onChange: (value) => setAttributes({ buttonHoverBoxShadow: value }),
                            placeholder: '0 4px 8px rgba(0,0,0,0.1)',
                            help: 'Box-shadow dla przycisków po najechaniu'
                        }),
                        el('hr'),
                        el('h4', { style: { margin: '16px 0 8px' } }, 'Ramki przycisków'),
                        el(TextControl, {
                            label: 'Grubość ramki',
                            value: attributes.buttonBorderWidth,
                            onChange: (value) => setAttributes({ buttonBorderWidth: value }),
                            placeholder: '0px'
                        }),
                        CompactColorPicker('buttonBorderColor', 'Kolor ramki', attributes.buttonBorderColor, (color) => setAttributes({ buttonBorderColor: color }))

                    ),


                ),

                // Block preview in editor
                el('div', {
                    className: 'resources-list-block-placeholder',
                    style: {
                        padding: '20px',
                        textAlign: 'center',
                        border: '2px dashed #ccc',
                        borderRadius: '4px',
                        backgroundColor: '#f9f9f9',
                        position: 'relative'
                    }
                },
                    el('div', {
                        style: {
                            fontSize: '24px',
                            marginBottom: '8px'
                        }
                    }, attributes.displayMode === 'cards' ? '🎴' : '📋'),
                    el('h3', {
                        style: {
                            margin: '0 0 8px',
                            color: '#666',
                            fontSize: '18px'
                        }
                    }, 'Lista Zasobów'),
                    el('p', {
                        style: {
                            margin: '0 0 16px',
                            color: '#999',
                            fontSize: '14px'
                        }
                    }, attributes.displayMode === 'cards' ?
                        'Spersonalizowane kafelki z nieruchomościami' :
                        'Spersonalizowana tabela z nieruchomościami'
                    ),

                    // Configuration summary
                    el('div', {
                        style: {
                            backgroundColor: 'white',
                            border: '1px solid #ddd',
                            borderRadius: '4px',
                            padding: '12px',
                            textAlign: 'left',
                            fontSize: '12px',
                            color: '#666'
                        }
                    },
                        el('div', { style: { marginBottom: '4px' } },
                            attributes.displayMode === 'cards' ? '🎴' : '📋',
                            ' Widok: ',
                            attributes.displayMode === 'cards' ? 'Kafelki' : 'Tabela'
                        ),
                        el('div', { style: { marginBottom: '4px' } },
                            '🏠 Typy: ', attributes.selectedTypes.length,
                            attributes.selectedTypes.length === 1 ? ' typ' : ' typy'
                        ),
                        el('div', { style: { marginBottom: '4px' } },
                            '📊 Pola: ', attributes.visibleColumns.length,
                            attributes.visibleColumns.length === 1 ? ' pole' : ' pól'
                        ),
                        attributes.displayMode === 'cards' && el('div', { style: { marginBottom: '4px' } },
                            '📐 Layout: ', attributes.cardsPerRow, ' kafelki na rząd'
                        ),
                        el('div', {},
                            '🎨 Kolory: ',
                            (attributes.displayMode === 'table' ?
                                (attributes.headerBgColor !== '#f9f9f9' ||
                                 attributes.headerTextColor !== '#333333' ||
                                 attributes.textColor !== '#333333') :
                                (attributes.cardBgColor !== '#ffffff' ||
                                 attributes.cardBorderColor !== '#e1e5e9' ||
                                 attributes.textColor !== '#333333')) ? 'Spersonalizowane' : 'Domyślne'
                        )
                    )
                )
            );
        },

        save: function() {
            // Return null since we're using server-side rendering
            return null;
        }
    });

})();