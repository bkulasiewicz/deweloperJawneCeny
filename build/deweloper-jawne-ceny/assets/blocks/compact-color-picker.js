/**
 * Compact Color Picker Component
 * Shared component for Gutenberg block color selection
 * Provides dropdown-style color picker with ColorIndicator + Button + Popover
 */
(function() {
    // Export to global scope for use in block files
    window.createCompactColorPickerFactory = function(wp) {
        const { createElement: el, useState } = wp.element;
        const { ColorPicker, ColorIndicator, Popover, Button } = wp.components;

        /**
         * Factory function that creates CompactColorPicker component with shared state
         */
        return function createCompactColorPicker() {
            // Shared state for all color pickers in the component
            const [colorPickerOpen, setColorPickerOpen] = useState({});

            // Helper functions for color picker
            const toggleColorPicker = function(colorKey) {
                setColorPickerOpen(prev => ({
                    ...prev,
                    [colorKey]: !prev[colorKey]
                }));
            };

            const closeColorPicker = function(colorKey) {
                setColorPickerOpen(prev => ({
                    ...prev,
                    [colorKey]: false
                }));
            };

            // Compact color picker component
            const CompactColorPicker = function(colorKey, label, currentColor, onChange) {
                return el('div', { style: { marginBottom: '16px' } },
                    el('label', {
                        style: {
                            display: 'block',
                            marginBottom: '8px',
                            fontSize: '11px',
                            fontWeight: '500',
                            textTransform: 'uppercase',
                            color: '#1e1e1e'
                        }
                    }, label),
                    el('div', {
                        style: {
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            padding: '8px 12px',
                            border: '1px solid #ddd',
                            borderRadius: '4px',
                            backgroundColor: '#fff',
                            minHeight: '36px',
                            transition: 'border-color 0.15s ease-in-out'
                        }
                    },
                        el('div', { style: { display: 'flex', alignItems: 'center', gap: '8px' } },
                            el('div', {
                                style: {
                                    width: '20px',
                                    height: '20px',
                                    backgroundColor: currentColor || '#cccccc',
                                    border: '1px solid #ddd',
                                    borderRadius: '2px',
                                    flexShrink: 0,
                                    cursor: 'pointer'
                                },
                                onClick: () => toggleColorPicker(colorKey),
                                title: `Aktualny kolor: ${currentColor || '#cccccc'}`
                            }),
                            el('span', {
                                style: {
                                    fontSize: '12px',
                                    color: '#666',
                                    fontFamily: 'Monaco, Consolas, monospace',
                                    userSelect: 'all'
                                }
                            }, currentColor || '#cccccc')
                        ),
                        el(Button, {
                            variant: 'secondary',
                            size: 'small',
                            onClick: () => toggleColorPicker(colorKey),
                            children: 'Zmień',
                            style: {
                                fontSize: '11px',
                                height: '28px',
                                minWidth: '50px'
                            }
                        }),
                        colorPickerOpen[colorKey] && el(Popover, {
                            position: 'middle left',
                            onClose: () => closeColorPicker(colorKey),
                            className: 'compact-color-picker-popover',
                            children: el('div', {
                                style: {
                                    padding: '16px',
                                    minWidth: '250px'
                                }
                            },
                                el(ColorPicker, {
                                    color: currentColor || '#cccccc',
                                    onChangeComplete: (color) => {
                                        onChange(color.hex);
                                        closeColorPicker(colorKey);
                                    },
                                    disableAlpha: true
                                })
                            )
                        })
                    )
                );
            };

            return CompactColorPicker;
        };
    };
})();