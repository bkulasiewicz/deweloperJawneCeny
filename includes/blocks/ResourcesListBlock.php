<?php

if (!defined('ABSPATH')) {
    exit;
}

class ResourcesListBlock {
    
    public static function render($attributes, $content) {
        // Use DI Container for getting Use Cases
        $useCase = DIContainer::get(GetAllResourcesUseCase::class);
        $resources = $useCase->execute();
        
        if (empty($resources)) {
            return '<p>Brak dostępnych zasobów.</p>';
        }
        
        ob_start();
        ?>
        <div class="resources-list-block">
            <table class="resources-table">
                <thead>
                    <tr>
                        <th>Numer</th>
                        <th>Typ</th>
                        <th>Powierzchnia</th>
                        <th>Cena za m²</th>
                        <th>Cena lokalu</th>
                        <th>Cena pełna</th>
                        <th>Status</th>
                        <th>Historia cen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $resource): ?>
                    <tr>
                        <td><?php echo esc_html($resource->nr_lokalu); ?></td>
                        <td><?php echo esc_html($resource->rodzaj_nieruchomosci); ?></td>
                        <td><?php echo esc_html(number_format($resource->powierzchnia_uzytkowa, 2, ',', ' ')); ?> m²</td>
                        <td>
                            <?php if ($resource->cena_m2 !== null && $resource->cena_m2 > 0): ?>
                                <?php echo esc_html(number_format($resource->cena_m2, 2, ',', ' ')); ?> zł
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($resource->cena_calkowita): ?>
                                <?php echo esc_html(number_format($resource->cena_calkowita, 2, ',', ' ')); ?> zł
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($resource->cena_z_dodatkami): ?>
                                <?php echo esc_html(number_format($resource->cena_z_dodatkami, 2, ',', ' ')); ?> zł
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-<?php echo esc_attr($resource->status); ?>">
                                <?php echo esc_html(ucfirst($resource->status)); ?>
                            </span>
                        </td>
                        <td>
                            <button class="price-history-btn" data-resource-id="<?php echo esc_attr($resource->id); ?>" data-resource-name="<?php echo esc_attr($resource->nr_lokalu); ?>">
                                Historia cen
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Price History Modal -->
            <div id="price-history-modal" class="price-history-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Historia cen - <span id="modal-resource-name"></span></h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="history-loading">Ładowanie...</div>
                        <div id="history-content"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}