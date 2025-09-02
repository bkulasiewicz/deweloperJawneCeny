(function() {
    const { registerBlockType } = wp.blocks;
    const { createElement: el } = wp.element;
    
    registerBlockType('jawne-ceny/resources-list', {
        title: 'Lista Zasobów',
        description: 'Wyświetla listę mieszkań/zasobów z cenami',
        category: 'widgets',
        icon: 'list-view',
        keywords: ['mieszkania', 'zasoby', 'ceny', 'lista'],
        
        edit: function() {
            return el(
                'div',
                {
                    className: 'resources-list-block-placeholder',
                    style: {
                        padding: '20px',
                        textAlign: 'center',
                        border: '2px dashed #ccc',
                        borderRadius: '4px',
                        backgroundColor: '#f9f9f9'
                    }
                },
                el('p', { style: { margin: 0, color: '#666' } }, '📋 Lista Zasobów'),
                el('small', { style: { color: '#999' } }, 'Tabela z mieszkaniami zostanie wyświetlona na stronie')
            );
        },
        
        save: function() {
            // Return null since we're using server-side rendering
            return null;
        }
    });
})();